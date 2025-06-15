<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if (empty($_POST)) {
    $rawData = file_get_contents('php://input');
    $jsonData = json_decode($rawData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
        $_POST = $jsonData;
    }
}
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

        if (empty($username) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => '請輸入用戶名和密碼']);
            exit;
        }

        if (!isset($_SESSION['_authnum']) || md5($captcha) !== $_SESSION['_authnum']) {
            echo json_encode(['status' => 'error', 'message' => '驗證碼錯誤']);
            exit;
        }

        $user = executeQuery(
            'SELECT user_id, username, password FROM users WHERE username = ?',
            [$username],
            'one'
        );

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status' => 'error', 'message' => '用戶名或密碼錯誤']);
            exit;
        }

        // 清除舊 session 和無效的 auth_token
        session_unset();
        if (isset($_COOKIE['auth_token'])) {
            executeNonQuery('DELETE FROM auth_tokens WHERE token = ? OR expires_at < NOW()', [$_COOKIE['auth_token']]);
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        }

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        unset($_SESSION['_authnum']);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_regenerate_id(true);
        $token = bin2hex(random_bytes(32));
        $expires_at = (new DateTime())->modify('+30 days')->format('Y-m-d H:i:s');

        if ($remember_me) {
            echo json_encode(['status' => 'success', 'message' => '登入成功', 'redirect' => '/tracking']);
            executeNonQuery(
                'INSERT INTO auth_tokens (user_id, token, expires_at, remember_me) VALUES (?, ?, ?, ?)',
                [$user['user_id'], $token, $expires_at, 1]
            );
            setcookie('auth_token', $token, [
                'expires' => time() + (30 * 24 * 3600),
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Strict'
            ]);
        } else {
            echo json_encode(['status' => 'success', 'message' => '登入成功', 'redirect' => '/tracking']);
            if (executeQuery(
                'SELECT token FROM auth_tokens WHERE user_id = ? AND remember_me = 0',
                [$user['user_id']],
                'one'
            )) {
                executeNonQuery('UPDATE auth_tokens SET token = ?, expires_at = ?, remember_me = ? WHERE user_id = ? AND remember_me = 0', [$token, $expires_at, 0, $user['id']]);
            } else {
                executeNonQuery(
                    'INSERT INTO auth_tokens (user_id, token, expires_at, remember_me) VALUES (?, ?, ?, ?)',
                    [$user['user_id'], $token, $expires_at, 0]
                );
            }
            setcookie('auth_token', $token, [
                'expires' => 0,
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Strict'
            ]);
            $_SESSION['first_login'] = true;
        }
        exit;


    case 'logout':
        if (isset($_COOKIE['auth_token'])) {
            executeNonQuery('DELETE FROM auth_tokens WHERE token = ?', [$_COOKIE['auth_token']]);
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        }
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/', '', false, true);
        }
        //echo json_encode(['status' => 'success', 'message' => '已登出', 'redirect' => '/']);
        header('Location: /');
        exit;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($password) || empty($password_confirm)) {
            echo json_encode(['status' => 'error', 'message' => '請填寫所有欄位']);
            exit;
        }

        if ($password !== $password_confirm) {
            echo json_encode(['status' => 'error', 'message' => '密碼不一致']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['status' => 'error', 'message' => '用戶名需至少3個字符']);
            exit;
        }

        $existing_user = executeQuery(
            'SELECT user_id FROM users WHERE username = ?',
            [$username],
            'one'
        );
        if ($existing_user) {
            echo json_encode(['status' => 'error', 'message' => '用戶名已存在']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            executeNonQuery(
                'INSERT INTO users (username, password) VALUES (?, ?)',
                [$username, $hashed_password]
            );
            executeNonQuery(
                'INSERT INTO personal_page (username) VALUES (?)',
                [$username]
            );
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => '註冊成功，請登入', 'redirect' => '/']);
        } catch (PDOException $e) {
            $pdo->rollback();
            echo json_encode(['status' => 'error', 'message' => '註冊失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'record':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $transport = $_POST['transport'] ?? '';
        $distance = floatval($_POST['distance'] ?? 0);

        if (empty($transport) || $distance <= 0) {
            echo json_encode(['status' => 'error', 'message' => '無效的交通方式或距離']);
            exit;
        }

        $factors = [
            "步行" => 0,
            "腳踏車" => 0,
            "機車" => 0.079,
            "汽車" => 0.104,
            "大眾運輸" => 0.078
        ];
        $footprint = $distance * ($factors[$transport] ?? 0);

        $points = 0;
        if ($transport === "步行") {
            $points = floor($distance / 0.5);
        } else if ($transport === "腳踏車") {
            $points = floor($distance / 1);
        } else if ($transport === "大眾運輸") {
            $points = floor($distance / 5);
        }

        try {
            executeNonQuery(
                'INSERT INTO travel_records (user_id, transport, distance, footprint, points) VALUES (?, ?, ?, ?, ?)',
                [$_SESSION['user_id'], $transport, $distance, $footprint, $points]
            );
            echo json_encode([
                'status' => 'success',
                'message' => '紀錄成功',
                'points' => $points,
                'footprint' => $footprint
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '儲存記錄失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'redeem':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $reward_id = intval($_POST['reward_id'] ?? 0);

        $reward = executeQuery(
            'SELECT name, points_required FROM rewards WHERE reward_id = ?',
            [$reward_id],
            'one'
        );

        if (!$reward) {
            echo json_encode(['status' => 'error', 'message' => '無效的獎勵']);
            exit;
        }

        $user_points = executeQuery(
            'SELECT COALESCE(SUM(points), 0) AS total_points FROM travel_records WHERE user_id = ?',
            [$_SESSION['user_id']],
            'one'
        );

        $used_points = executeQuery(
            'SELECT COALESCE(SUM(points_used), 0) AS total_used_points FROM redeem_history WHERE user_id = ?',
            [$_SESSION['user_id']],
            'one'
        );

        if (!$user_points || !$used_points) {
            echo json_encode(['status' => 'error', 'message' => '無法獲取用戶積分數據']);
            exit;
        }

        $available_points = $user_points['total_points'] - $used_points['total_used_points'];

        if ($available_points < $reward['points_required']) {
            echo json_encode(['status' => 'error', 'message' => '點數不足']);
            exit;
        }

        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            executeNonQuery(
                'INSERT INTO redeem_history (user_id, reward_id, reward_name, points_used, redeem_time) VALUES (?, ?, ?, ?, NOW())',
                [$_SESSION['user_id'], $reward_id, $reward['name'], $reward['points_required']]
            );
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => '兌換成功']);
        } catch (PDOException $e) {
            $pdo->rollback();
            echo json_encode(['status' => 'error', 'message' => '兌換失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'upload_avatar':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            echo json_encode(['status' => 'error', 'message' => '請選擇圖像文件']);
            exit;
        }

        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => '僅支持 JPG、PNG 或 GIF 格式']);
            exit;
        }

        if ($file['size'] > $max_size) {
            echo json_encode(['status' => 'error', 'message' => '檔案大小超過 5MB']);
            exit;
        }

        $avatar_dir = __DIR__ . '/../avatars/';
        if (!is_dir($avatar_dir)) {
            mkdir($avatar_dir, 0755, true);
        }

        $avatar_path = $avatar_dir . $_SESSION['user_id'] . '.jpg';
        if (!move_uploaded_file($file['tmp_name'], $avatar_path)) {
            echo json_encode(['status' => 'error', 'message' => '圖像上傳失敗']);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => '頭像上傳成功', 'avatar' => "/avatars/{$_SESSION['user_id']}.jpg"]);
        exit;

    case 'update_profile':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $bio = trim($_POST['bio'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $activity_level = trim($_POST['activity_level'] ?? '');

        if (strlen($bio) > 500) {
            echo json_encode(['status' => 'error', 'message' => '自我介紹最多 500 字']);
            exit;
        }

        $valid_countries = ['台灣', '美國', '日本'];
        if (!empty($country_code) && !in_array($country_code, $valid_countries)) {
            echo json_encode(['status' => 'error', 'message' => '無效的國家代碼']);
            exit;
        }

        $valid_cities = [
            '台灣' => ['台北', '其他'],
            '美國' => ['紐約', '其他'],
            '日本' => ['東京', '其他']
        ];
        if (!empty($city) && (!isset($valid_cities[$country_code]) || !in_array($city, $valid_cities[$country_code]))) {
            echo json_encode(['status' => 'error', 'message' => '無效的城市']);
            exit;
        }

        $valid_genders = ['不公開', '男', '女', '其他'];
        if (!empty($gender) && !in_array($gender, $valid_genders)) {
            echo json_encode(['status' => 'error', 'message' => '無效的性別']);
            exit;
        }

        if (!empty($birthdate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            echo json_encode(['status' => 'error', 'message' => '生日格式無效（YYYY-MM-DD）']);
            exit;
        }

        $valid_activity_levels = ['不公開', '低', '中', '高'];
        if (!empty($activity_level) && !in_array($activity_level, $valid_activity_levels)) {
            echo json_encode(['status' => 'error', 'message' => '無效的活躍程度']);
            exit;
        }

        $pdo = getPDO();
        try {
            $pdo->beginTransaction();
            if (executeQuery('SELECT user_id FROM personal_page WHERE user_id = ?', [$_SESSION['user_id']], 'one')) {
                executeNonQuery(
                    'INSERT INTO personal_page (user_id, username, bio, country_code, city, gender, birthdate, activity_level, last_update)
                     VALUES (?, (SELECT username FROM users WHERE user_id = ?), ?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                     bio = VALUES(bio), country_code = VALUES(country_code), city = VALUES(city), 
                     gender = VALUES(gender), birthdate = VALUES(birthdate), activity_level = VALUES(activity_level), 
                     last_update = VALUES(last_update)',
                    [
                        $_SESSION['user_id'],
                        $_SESSION['user_id'],
                        $bio,
                        $country_code,
                        $city,
                        $gender,
                        $birthdate,
                        $activity_level
                    ]
                );
            } else {
                executeNonQuery(
                    'INSERT INTO personal_page (user_id, username, bio, country_code, city, gender, birthdate, activity_level, last_update)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $_SESSION['user_id'],
                        $_SESSION['username'],
                        $bio,
                        $country_code,
                        $city,
                        $gender,
                        $birthdate,
                        $activity_level
                    ]
                );
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => '個人資料更新成功']);
        } catch (PDOException $e) {
            $pdo->rollback();
            echo json_encode(['status' => 'error', 'message' => '更新失敗: ' . $e->getMessage()]);
        }
        exit;
    case 'add_friend':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $friend_id = intval($_POST['friend_id'] ?? 0);
        if ($friend_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '無效的好友 ID']);
            exit;
        }

        if ($friend_id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => '不能添加自己為好友']);
            exit;
        }

        // 確保 user_id < friend_id
        $smaller_id = min($_SESSION['user_id'], $friend_id);
        $larger_id = max($_SESSION['user_id'], $friend_id);
        $initiator_id = $_SESSION['user_id'];

        $existing_request = executeQuery(
            'SELECT status FROM friends WHERE user_id = ? AND friend_id = ?',
            [$smaller_id, $larger_id],
            'one'
        );

        if ($existing_request) {
            if ($existing_request['status'] === 'accepted') {
                echo json_encode(['status' => 'error', 'message' => '已是好友']);
                exit;
            } else if ($existing_request['status'] === 'pending') {
                echo json_encode(['status' => 'error', 'message' => '好友請求已存在']);
                exit;
            }
        }

        try {
            if ($existing_request['status'] == 'rejected') {
                // 如果已經有 pending 狀態的請求，則更新 initiator_id
                executeNonQuery(
                    'UPDATE friends SET initiator_id = ?,status = ? WHERE user_id = ? AND friend_id = ?',
                    [$initiator_id, 'pending', $smaller_id, $larger_id]
                );
            } else {
                executeNonQuery(
                    'INSERT INTO friends (user_id, friend_id, status, initiator_id) VALUES (?, ?, ?, ?)',
                    [$smaller_id, $larger_id, 'pending', $initiator_id]
                );
            }

            echo json_encode(['status' => 'success', 'message' => '好友請求已發送']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '發送好友請求失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'respond_friend_request':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $request_id = intval($_POST['request_id'] ?? 0);
        $response = $_POST['response'] ?? '';

        if (!in_array($response, ['accept', 'reject'])) {
            echo json_encode(['status' => 'error', 'message' => '無效的回應']);
            exit;
        }

        $request = executeQuery(
            'SELECT user_id, friend_id, initiator_id FROM friends WHERE id = ? AND status = ?',
            [$request_id, 'pending'],
            'one'
        );

        if (!$request || $request['initiator_id'] == $_SESSION['user_id'] || ($request['friend_id'] != $_SESSION['user_id'] && $request['user_id'] != $_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '無效的好友請求']);
            exit;
        }

        try {
            $status = $response === 'accept' ? 'accepted' : 'rejected';
            executeNonQuery(
                'UPDATE friends SET status = ? WHERE id = ?',
                [$status, $request_id]
            );
            echo json_encode(['status' => 'success', 'message' => $response === 'accept' ? '已接受好友請求' : '已拒絕好友請求']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '處理好友請求失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'get_friend_status':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $friend_id = intval($_POST['friend_id'] ?? 0);
        if ($friend_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '無效的好友 ID']);
            exit;
        }

        $smaller_id = min($_SESSION['user_id'], $friend_id);
        $larger_id = max($_SESSION['user_id'], $friend_id);

        $status = executeQuery(
            'SELECT status FROM friends WHERE user_id = ? AND friend_id = ?',
            [$smaller_id, $larger_id],
            'one'
        );

        echo json_encode(['status' => 'success', 'friend_status' => $status ? $status['status'] : 'none']);
        exit;

    case 'get_pending_requests':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $requests = executeQuery(
            'SELECT f.id AS request_id, u.username 
             FROM friends f 
             JOIN users u ON u.user_id = f.initiator_id 
             WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = "pending" AND f.initiator_id != ?',
            [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']],
            'all'
        );

        echo json_encode(['status' => 'success', 'requests' => $requests]);
        exit;
    case 'delete_friend':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $friend_id = intval($_POST['friend_id'] ?? 0);
        if ($friend_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '無效的好友 ID']);
            exit;
        }

        $smaller_id = min($_SESSION['user_id'], $friend_id);
        $larger_id = max($_SESSION['user_id'], $friend_id);

        $friendship = executeQuery(
            'SELECT id FROM friends WHERE user_id = ? AND friend_id = ? AND status = ?',
            [$smaller_id, $larger_id, 'accepted'],
            'one'
        );

        if (!$friendship) {
            echo json_encode(['status' => 'error', 'message' => '好友關係不存在或尚未建立']);
            exit;
        }

        try {
            executeNonQuery(
                'DELETE FROM friends WHERE id = ?',
                [$friendship['id']]
            );
            echo json_encode(['status' => 'success', 'message' => '已刪除好友']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '刪除好友失敗: ' . $e->getMessage()]);
        }
        exit;
    case 'get_friends':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $friends = executeQuery(
            'SELECT u.user_id, u.username 
         FROM friends f 
         JOIN users u ON (u.user_id = f.friend_id OR u.user_id = f.user_id) 
         WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = "accepted" AND u.user_id != ?',
            [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']],
            'all'
        );

        echo json_encode(['status' => 'success', 'friends' => $friends]);
        exit;
    default:
        echo json_encode(['status' => 'error', 'message' => '無效的操作']);
        exit;
}

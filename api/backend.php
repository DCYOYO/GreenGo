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
            echo json_encode(['status'=>'error','error' => '請輸入用戶名和密碼']);
            exit;
        }

        if (!isset($_SESSION['_authnum']) || md5($captcha) !== $_SESSION['_authnum']) {
            echo json_encode(['status'=>'error','error' => '驗證碼錯誤']);
            exit;
        }

        $user = executeQuery(
            'SELECT id, username, password FROM users WHERE username = ?',
            [$username],
            'one'
        );

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status'=>'error','error' => '用戶名或密碼錯誤']);
            exit;
        }

        // 清除舊 session 和無效的 auth_token
        session_unset();
        if (isset($_COOKIE['auth_token'])) {
            executeNonQuery('DELETE FROM auth_tokens WHERE token = ? OR expires_at < NOW()', [$_COOKIE['auth_token']]);
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        }

        $_SESSION['user_id'] = $user['id'];
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

            executeNonQuery(
                'INSERT INTO auth_tokens (user_id, token, expires_at, remember_me) VALUES (?, ?, ?, ?)',
                [$user['id'], $token, $expires_at, 1]
            );

            setcookie('auth_token', $token, [
                'expires' => time() + (30 * 24 * 3600),
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Strict'
            ]);
        } else {
            if (executeQuery(
                'SELECT token FROM auth_tokens WHERE user_id = ? AND remember_me = 0',
                [$user['id']],
                'one'
            )) {
                executeNonQuery('UPDATE user_id=?,token=?,expires_at=?,remember_me=? WHERE user_id=? AND remember_me=0', [$user['id'], $token, $expires_at, 0, $user['id']]);
            } else
                executeNonQuery(
                    'INSERT INTO auth_tokens (user_id, token, expires_at, remember_me) VALUES (?, ?, ?, ?)',
                    [$user['id'], $token, $expires_at, 0]
                );
            setcookie('auth_token', $token, [
                'expires' => 0,
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Strict'
            ]);
            $_SESSION['first_login'] = true;
        }
        //echo json_encode(['status' => 'success', 'message' => '登入成功']);
        header('Location: /tracking');
        //echo json_encode(['status' => 'success']);
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
        header('Location: /');
        exit;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($password) || empty($password_confirm)) {
            echo json_encode(['error' => '請填寫所有欄位']);
            exit;
        }

        if ($password !== $password_confirm) {
            echo json_encode(['error' => '密碼不一致']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['error' => '用戶名需至少3個字符']);
            exit;
        }

        $existing_user = executeQuery(
            'SELECT id FROM users WHERE username = ?',
            [$username],
            'one'
        );
        if ($existing_user) {
            echo json_encode(['error' => '用戶名已存在']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            executeNonQuery(
                'INSERT INTO users (username, password, total_points, total_footprint) VALUES (?, ?, 0, 0)',
                [$username, $hashed_password]
            );
            executeNonQuery(
                'INSERT INTO personal_page (username) VALUES (?)',
                [$username]
            );
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => '註冊成功，請登入']);
        } catch (PDOException $e) {
            $pdo->rollback();
            echo json_encode(['error' => '註冊失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'record':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => '請先登入']);
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

        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            executeNonQuery(
                'INSERT INTO travel_records (user_id, transport, distance, footprint, points) VALUES (?, ?, ?, ?, ?)',
                [$_SESSION['user_id'], $transport, $distance, $footprint, $points]
            );
            executeNonQuery(
                'UPDATE users SET total_points = total_points + ?, total_footprint = total_footprint + ? WHERE id = ?',
                [$points, $footprint, $_SESSION['user_id']]
            );
            $pdo->commit();
            echo json_encode([
                'status' => 'success',
                'message' => '紀錄成功',
                'points' => $points,
                'footprint' => $footprint
            ]);
        } catch (PDOException $e) {
            $pdo->rollback();
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
            'SELECT name, points_required FROM rewards WHERE id = ?',
            [$reward_id],
            'one'
        );

        if (!$reward) {
            echo json_encode(['status' => 'error', 'message' => '無效的獎勵']);
            exit;
        }

        $user = executeQuery(
            'SELECT total_points FROM users WHERE id = ?',
            [$_SESSION['user_id']],
            'one'
        );

        if ($user['total_points'] < $reward['points_required']) {
            echo json_encode(['status' => 'error', 'message' => '點數不足']);
            exit;
        }

        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            executeNonQuery(
                'UPDATE users SET total_points = total_points - ? WHERE id = ?',
                [$reward['points_required'], $_SESSION['user_id']]
            );
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
   
    default:
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?: '/');
        exit;
}

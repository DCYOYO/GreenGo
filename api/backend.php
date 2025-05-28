<?php
// api/backend.php
session_start();

// 資料庫連接配置
$host = 'localhost';
$dbname = 'carbon_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫連接失敗: ' . $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?: '/'));
    exit;
}

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

        if (!isset($_SESSION['_authnum']) || md5($captcha) !== $_SESSION['_authnum']) {
            $_SESSION['error'] = '驗證碼錯誤';
            header('Location: /');
            exit;
        }

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = '請輸入用戶名和密碼';
            header('Location: /');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            session_write_close();
            unset($_SESSION['_authnum']);
            header('Location: /tracking');
            exit;
        } else {
            $_SESSION['error'] = '用戶名或密碼錯誤';
            header('Location: /');
            exit;
        }
        break;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($password) || empty($password_confirm)) {
            $_SESSION['error'] = '請填寫所有欄位';
            header('Location: /register');
            exit;
        }

        if ($password !== $password_confirm) {
            $_SESSION['error'] = '密碼不一致';
            header('Location: /register');
            exit;
        }

        if (strlen($username) < 3) {
            $_SESSION['error'] = '用戶名需至少3個字符';
            header('Location: /register');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '用戶名已存在';
            header('Location: /register');
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, total_points, total_footprint) VALUES (?, ?, 0, 0)');
        try {
            $stmt->execute([$username, $hashed_password]);
            $_SESSION['error'] = '註冊成功，請登入';
            header('Location: /');
        } catch (PDOException $e) {
            $_SESSION['error'] = '註冊失敗: ' . $e->getMessage();
            header('Location: /register');
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header('Location: /');
        break;

    case 'record':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $transport = $_POST['transport'] ?? '';
        $distance = floatval($_POST['distance'] ?? 0);

        if (empty($transport) || $distance <= 0) {
            $_SESSION['error'] = '無效的交通方式或距離';
            header('Location: /tracking');
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

        $stmt = $pdo->prepare('INSERT INTO travel_records (user_id, transport, distance, footprint, points) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$_SESSION['user_id'], $transport, $distance, $footprint, $points]);
            $stmt = $pdo->prepare('UPDATE users SET total_points = total_points + ?, total_footprint = total_footprint + ? WHERE id = ?');
            $stmt->execute([$points, $footprint, $_SESSION['user_id']]);
            echo json_encode([
                'status' => "success",
                'message' => '紀錄成功',
                'points' => $points,
                'footprint' => $footprint
            ]);
        } catch (PDOException $e) {
            $_SESSION['error'] = '儲存記錄失敗: ' . $e->getMessage();
            header('Location: /tracking');
        }
        break;

    case 'redeem':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $reward_id = intval($_POST['reward_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT name, points_required FROM rewards WHERE id = ?');
        $stmt->execute([$reward_id]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reward) {
            echo json_encode(['status' => 'error', 'message' => '無效的獎勵']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT total_points FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['total_points'] < $reward['points_required']) {
            echo json_encode(['status' => 'error', 'message' => '點數不足']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE users SET total_points = total_points - ? WHERE id = ?');
            $stmt->execute([$reward['points_required'], $_SESSION['user_id']]);

            $stmt = $pdo->prepare('INSERT INTO redeem_history (user_id, reward_id, reward_name, points_used, redeem_time) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$_SESSION['user_id'], $reward_id, $reward['name'], $reward['points_required']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => '兌換成功']);
        } catch (PDOException $e) {
            $pdo->rollback();
            echo json_encode(['status' => 'error', 'message' => '兌換失敗: ' . $e->getMessage()]);
        }
        break;

    case 'upload_profile_picture':
        header('Content-Type: application/json; charset=UTF-8');
        if (!isset($_SESSION['username'])) {
            echo json_encode(['error' => '請先登入']);
            exit;
        }

        $username = $_SESSION['username'];
        $upload_dir = 'Uploads/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log('Failed to create upload directory: ' . $upload_dir);
                echo json_encode(['error' => '無法創建上傳目錄，請檢查伺服器權限。']);
                exit;
            }
        }

        if (!is_writable($upload_dir)) {
            error_log('Upload directory is not writable: ' . $upload_dir);
            echo json_encode(['error' => '上傳目錄不可寫，請檢查伺服器權限。']);
            exit;
        }

        $file = $_FILES['profile_picture'];
        error_log('File upload attempt: ' . json_encode($file));

        if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $username . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $sql = "UPDATE personal_page SET profile_picture = :profile_picture WHERE username = :username";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['profile_picture' => $destination, 'username' => $username]);
                echo json_encode(['success' => '頭像上傳成功！', 'profile_picture' => $destination]);
            } else {
                error_log('Failed to move uploaded file to: ' . $destination);
                echo json_encode(['error' => '頭像上傳失敗，請稍後再試。']);
            }
        } else {
            $error_msg = '無效的檔案格式或檔案過大（上限 5MB）。';
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error_msg = '檔案上傳錯誤，代碼：' . $file['error'];
            }
            error_log('File upload error: ' . $error_msg);
            echo json_encode(['error' => $error_msg]);
        }
        exit;
        break;

    case 'edit_profile':
        header('Content-Type: application/json; charset=UTF-8');
        if (!isset($_SESSION['username'])) {
            echo json_encode(['error' => '請先登入']);
            exit;
        }

        $username = $_SESSION['username'];
        $bio = trim($_POST['bio'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $gender = in_array($_POST['gender'] ?? '', ['M', 'F', 'Other']) ? $_POST['gender'] : null;
        $birthdate = trim($_POST['birthdate'] ?? '');
        $activity_level = in_array($_POST['activity_level'] ?? '', ['Low', 'Medium', 'High']) ? $_POST['activity_level'] : null;

        error_log('Updating profile for user: ' . $username . ', data: ' . json_encode($_POST));

        $sql = "UPDATE personal_page SET bio = :bio, country_code = :country_code, gender = :gender, birthdate = :birthdate, activity_level = :activity_level WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'bio' => $bio ?: null,
            'country_code' => $country_code ?: null,
            'gender' => $gender,
            'birthdate' => $birthdate ?: null,
            'activity_level' => $activity_level,
            'username' => $username
        ]);
        echo json_encode(['success' => '個人資料更新成功！']);
        exit;
        break;

    default:
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?: '/'));
        break;
}
?>
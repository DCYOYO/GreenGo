<?php
header('Content-Type: application/json; charset=UTF-8');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// 資料庫連接配置（請根據你的環境更新）
$host = 'localhost';
$dbname = 'carbon_tracker';
$username = 'root'; // 替換為你的資料庫用戶名
$password = ''; // 替換為你的資料庫密碼

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫連接失敗: ' . $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?: '/');
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

// 驗證 CSRF token（除了 logout）
if (!in_array($action, ['logout'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'CSRF token 驗證失敗']);
        exit;
    }
}
switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

        if (empty($username) || empty($password)) {
            echo json_encode(['error' => '請輸入用戶名和密碼']);
            exit;
        }

        if (!isset($_SESSION['_authnum']) || md5($captcha) !== $_SESSION['_authnum']) {
            echo json_encode(['error' => '驗證碼錯誤']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['error' => '用戶名或密碼錯誤']);
            exit;
        }

        // 清除舊 session 數據
        session_unset();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        unset($_SESSION['_authnum']);

        // 設置臨時 session cookie
        session_set_cookie_params([
            'lifetime' => 0, // 瀏覽器關閉時失效
            'path' => '/',
            'secure' => false, // localhost 不使用 HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_regenerate_id(true);

        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expires_at = (new DateTime())->modify('+30 days')->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $token, $expires_at]);

            setcookie('auth_token', $token, [
                'expires' => time() + (30 * 24 * 3600),
                'path' => '/',
                'httponly' => true,
                'secure' => false, // localhost 不使用 HTTPS
                'samesite' => 'Strict'
            ]);
        } else {
            // 清除任何現有的 auth_token
            if (isset($_COOKIE['auth_token'])) {
                $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = ?');
                $stmt->execute([$_COOKIE['auth_token']]);
                setcookie('auth_token', '', time() - 3600, '/', '', false, true);
            }
        }

        echo json_encode(['status' => 'success']);
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

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => '用戶名已存在']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, total_points, total_footprint) VALUES (?, ?, 0, 0)');
        try {
            $stmt->execute([$username, $hashed_password]);
            $stmt = $pdo->prepare('INSERT INTO personal_page (username) VALUES (?)');
            $stmt->execute([$username]);
            echo json_encode(['status' => 'success', 'message' => '註冊成功，請登入']);
        } catch (PDOException $e) {
            echo json_encode(['error' => '註冊失敗: ' . $e->getMessage()]);
        }
        exit;

    case 'logout':
        if (isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
            $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = ?');
            $stmt->execute([$token]);
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
        }
        // 清除所有 session 數據
        session_unset();
        session_destroy();
        // 明確清除 PHPSESSID Cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/', '', false, true);
        }
        header('Location: /');
        exit;
    case 'record':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $transport = $_POST['transport'] ?? '';
        $distance = floatval($_POST['distance'] ?? 0);

        if (empty($transport) || $distance <= 0) {
            echo json_encode([
                'status' => "false",
                'message' => '無效的交通方式或距離',
            ]);
            //$_SESSION['error'] = '無效的交通方式或距離';
            //header('Location: /tracking');
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
            //header('Location: /tracking');
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

    default:
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?: '/');
        break;
}

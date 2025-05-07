<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
session_start();

$host = 'localhost';
$dbname = 'carbon_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '資料庫連接失敗: ' . $e->getMessage()]);
    exit;
}

// 解析 JSON 請求體
$data = json_decode(file_get_contents('php://input'), true);

// 優先從 JSON 數據中獲取 action，如果不存在則檢查 GET 和 POST
$action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : ''));

switch ($action) {
    case 'login':
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => '請輸入用戶名和密碼']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['status' => 'success', 'message' => '登入成功']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '用戶名或密碼錯誤']);
        }
        break;

    case 'register':
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => '請輸入用戶名和密碼']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['status' => 'error', 'message' => '用戶名需至少3個字符']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => '用戶名已存在']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, total_points, total_footprint) VALUES (?, ?, 0, 0)');
        try {
            $stmt->execute([$username, $hashed_password]);
            echo json_encode(['status' => 'success', 'message' => '註冊成功']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '註冊失敗: ' . $e->getMessage()]);
        }
        break;

    case 'logout':
        // 為了兼容原始設計，僅返回成功響應，前端負責處理登出邏輯
        // 如果需要清除 PHP 會話，可以取消以下註釋
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => '登出成功']);
        break;

    case 'check_login':
        if (isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'success', 'username' => $_SESSION['username']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => '未登入']);
        }
        break;

    case 'record':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $transport = $data['transport'] ?? '';
        $distance = floatval($data['distance'] ?? 0);
        $footprint = floatval($data['footprint'] ?? 0);
        $points = intval($data['points'] ?? 0);

        if (empty($transport) || $distance <= 0) {
            echo json_encode(['status' => 'error', 'message' => '無效的交通方式或距離']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO travel_records (user_id, transport, distance, footprint, points) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$_SESSION['user_id'], $transport, $distance, $footprint, $points]);
            $stmt = $pdo->prepare('UPDATE users SET total_points = total_points + ?, total_footprint = total_footprint + ? WHERE id = ?');
            $stmt->execute([$points, $footprint, $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'message' => '記錄已儲存']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '儲存記錄失敗: ' . $e->getMessage()]);
        }
        break;

    case 'history':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT transport, distance, footprint, points, record_time FROM travel_records WHERE user_id = ? ORDER BY record_time DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($records);
        break;

    case 'user_points':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT total_points AS points FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'points' => $result['points']]);
        break;

    case 'leaderboard':
        $stmt = $pdo->prepare('SELECT username, total_points, total_footprint FROM users ORDER BY total_points DESC, total_footprint ASC LIMIT 10');
        $stmt->execute();
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($leaderboard);
        break;

    case 'rewards':
        $stmt = $pdo->prepare('SELECT id, name, points_required, description FROM rewards');
        $stmt->execute();
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rewards);
        break;

    case 'redeem':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => '請先登入']);
            exit;
        }

        $reward_id = intval($data['reward_id'] ?? 0);

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
        echo json_encode(['status' => 'error', 'message' => '無效的操作']);
        break;
}
?>

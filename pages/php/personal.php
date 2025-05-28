<?php

// 記錄會話日誌
error_log('Session username: ' . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set'));

// 資料庫連線
try {
    $pdo = new PDO('mysql:host=localhost;dbname=carbon_tracker;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    return [
        'error' => '資料庫連線失敗：' . htmlspecialchars($e->getMessage()),
        'user' => null,
        'username' => $_GET['username'] ?? '',
        'session_username' => $_SESSION['username'] ?? ''
    ];
}

// 查詢個人主頁
$user = null;
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $username = trim($_GET['username']);
    $sql = "SELECT pp.*, u.total_points, u.total_footprint 
            FROM personal_page pp 
            LEFT JOIN users u ON pp.username = u.username 
            WHERE pp.username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log('Queried username: ' . ($user ? $user['username'] : 'Not found'));
    error_log('Session matches user: ' . (isset($_SESSION['username']) && $user && $_SESSION['username'] === $user['username'] ? 'Yes' : 'No'));
}

return [
    'user' => $user,
    'username' => $username,
    'session_username' => $_SESSION['username'] ?? '',
    'error' => null
];
?>
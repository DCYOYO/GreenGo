<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$host = 'localhost';
$dbname = 'carbon_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT total_points FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $points = $user['total_points'] ?? 0;
} catch (PDOException $e) {
    return [
        'error' => '資料庫連接失敗: ' . $e->getMessage(),
        'points' => 0,
        'username' => $_SESSION['username']
    ];
}

return [
    'points' => $points,
    'username' => $_SESSION['username'],
    'error' => null
];
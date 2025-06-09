<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id'])&& !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

$user = executeQuery(
    'SELECT total_points FROM users WHERE id = ?',
    [$_SESSION['user_id']],
    'one'
);

if (!$user) {
    return [
        'error' => '無法獲取用戶數據',
        'points' => 0,
        'username' => $_SESSION['username']
    ];
}

return [
    'points' => $user['total_points'] ?? 0,
    'username' => $_SESSION['username'],
    'error' => null
];
?>
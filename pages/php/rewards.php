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
} catch (PDOException $e) {
    return [
        'error' => '資料庫連接失敗: ' . $e->getMessage(),
        'rewards_html' => '',
        'username' => $_SESSION['username']
    ];
}

$out = '';
$stmt = $pdo->prepare('SELECT id, name, points_required, description FROM rewards');
$stmt->execute();
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rewards as &$record) {
    $out .= "<div class='card-body card mb-2'>" .
            "<h6 class='card-title'>" . htmlspecialchars($record['name']) . "</h6>" .
            "<p class='card-text'>需要" . htmlspecialchars($record['points_required']) . "</p>" .
            "<p class='card-text'>" . htmlspecialchars($record['description']) . "</p>" .
            "<button class='btn btn-primary' onclick=\"redeemReward(" . $record['id'] . ")\">兌換</button>" .
            "</div>";
}

return [
    'rewards_html' => $out,
    'username' => $_SESSION['username'],
    'error' => null
];
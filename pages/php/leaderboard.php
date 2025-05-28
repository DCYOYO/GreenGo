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
        'records_html' => '',
        'username' => $_SESSION['username']
    ];
}

$count = 1;
$out = '';
$stmt = $pdo->prepare('SELECT username, total_points, total_footprint FROM users ORDER BY total_points DESC, total_footprint ASC LIMIT 10');
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($records as &$record) {
    $record['total_footprint'] = number_format($record['total_footprint'], 2);
    $out .= "<tr>" .
            "<td>$count</td>" .
            "<td>" . htmlspecialchars($record['username']) . "</td>" .
            "<td>" . htmlspecialchars($record['total_points']) . "</td>" .
            "<td>" . htmlspecialchars($record['total_footprint']) . "</td>" .
            "</tr>";
    $count++;
}

return [
    'records_html' => $out,
    'username' => $_SESSION['username'],
    'error' => null
];
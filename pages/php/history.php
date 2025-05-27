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
        'points' => 0,
        'records_html' => '',
        'username' => $_SESSION['username']
    ];
}

$count = 0;
$out = '';
$stmt = $pdo->prepare('SELECT transport, distance, footprint, points, record_time FROM travel_records WHERE user_id = ? ORDER BY record_time DESC');
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($records as &$record) {
    $count++;
    $record['record_time'] = date('Y-m-d H:i:s', strtotime($record['record_time']));
    $record['points'] = number_format($record['points']);
    $record['footprint'] = number_format($record['footprint'], 2);
    $record['distance'] = number_format($record['distance'], 2);
    $record['transport'] = htmlspecialchars($record['transport']);
    $out .= "<li class='list-group-item'>第{$count}筆-{$record['record_time']}-{$record['transport']}-{$record['distance']} 公里 - 碳排放 {$record['footprint']} kg CO₂ - 獲得 {$record['points']} 點</li>";
}

$stmt = $pdo->prepare('SELECT total_points AS points FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$points = $records[0]['points'] ?? 0;

return [
    'points' => $points,
    'records_html' => $out,
    'username' => $_SESSION['username'],
    'error' => null
];
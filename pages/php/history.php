<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

$records = executeQuery(
    'SELECT record_time, transport, distance, footprint, points 
     FROM travel_records 
     WHERE user_id = ? 
     ORDER BY record_time DESC 
     LIMIT 10',
    [$_SESSION['user_id']],
    'all'
);

$user = executeQuery(
    'SELECT total_points FROM users WHERE id = ?',
    [$_SESSION['user_id']],
    'one'
);

if (!$user) {
    return [
        'error' => '無法獲取用戶數據',
        'points' => 0,
        'username' => $_SESSION['username'],
        'records_html' => ''
    ];
}

$count = 1;
$out = '';
foreach ($records as &$record) {
    $record['distance'] = number_format($record['distance'], 2);
    $record['footprint'] = number_format($record['footprint'], 2);
    $out .= "<li class='list-group-item'>第{$count}筆-{$record['record_time']}-{$record['transport']}-{$record['distance']} 公里 - 碳排放 {$record['footprint']} kg CO₂ - 獲得 {$record['points']} 點</li>";
    $count++;
}

return [
    'records_html' => $out,
    'points' => $user['total_points'] ?? 0,
    'username' => $_SESSION['username'],
    'error' => null
];
?>

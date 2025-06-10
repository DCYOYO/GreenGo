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
    $out .= "<div class='hist-record-card'>" .
            "<div class='hist-card-header'>第 {$count} 筆</div>" .
            "<div class='hist-card-body'>" .
            "<p><strong>時間:</strong> " . htmlspecialchars($record['record_time']) . "</p>" .
            "<p><strong>交通方式:</strong> " . htmlspecialchars($record['transport']) . "</p>" .
            "<p><strong>距離:</strong> " . htmlspecialchars($record['distance']) . " 公里</p>" .
            "<p><strong>碳排放:</strong> " . htmlspecialchars($record['footprint']) . " kg CO₂</p>" .
            "<p><strong>獲得點數:</strong> " . htmlspecialchars($record['points']) . " 點</p>" .
            "</div>" .
            "</div>";
    $count++;
}

return [
    'records_html' => $out,
    'points' => $user['total_points'] ?? 0,
    'username' => $_SESSION['username'],
    'error' => null
];
?>

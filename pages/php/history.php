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

$user_points = executeQuery(
    'SELECT COALESCE(SUM(points), 0) AS total_points FROM travel_records WHERE user_id = ?',
    [$_SESSION['user_id']],
    'one'
);

$used_points = executeQuery(
    'SELECT COALESCE(SUM(points_used), 0) AS total_used_points FROM redeem_history WHERE user_id = ?',
    [$_SESSION['user_id']],
    'one'
);

$available_points = $user_points['total_points'] - ($used_points['total_used_points'] ?? 0);

// 處理排序參數
$sort_field = $_GET['sort'] ?? 'record_time';
$sort_order = $_GET['order'] ?? 'desc';
$valid_fields = ['record_time', 'distance', 'footprint', 'points'];

if (!in_array($sort_field, $valid_fields)) {
    $sort_field = 'record_time';
}
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

usort($records, function ($a, $b) use ($sort_field, $sort_order) {
    $val_a = $a[$sort_field];
    $val_b = $b[$sort_field];

    if ($sort_field === 'record_time') {
        $val_a = strtotime($val_a);
        $val_b = strtotime($val_b);
    }

    if ($val_a == $val_b) {
        return 0;
    }

    $result = ($val_a < $val_b) ? -1 : 1;
    return ($sort_order === 'asc') ? $result : -$result;
});

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

$sort_options = [
    'record_time' => '時間',
    'distance' => '距離',
    'footprint' => '碳排放',
    'points' => '獲得點數'
];

return [
    'records_html' => $out,
    'points' => $available_points,
    'username' => $_SESSION['username'],
    'error' => null,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order,
    'sort_options' => $sort_options
];

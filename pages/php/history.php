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

// $count = 1;
// $out = '';
// foreach ($records as &$record) {
//     $record['distance'] = number_format($record['distance'], 2);
//     $record['footprint'] = number_format($record['footprint'], 2);
//     $out .= "<div class='hist-record-card'>" .
//             "<div class='hist-card-header'>第 {$count} 筆</div>" .
//             "<div class='hist-card-body'>" .
//             "<p><strong>時間:</strong> " . htmlspecialchars($record['record_time']) . "</p>" .
//             "<p><strong>交通方式:</strong> " . htmlspecialchars($record['transport']) . "</p>" .
//             "<p><strong>距離:</strong> " . htmlspecialchars($record['distance']) . " 公里</p>" .
//             "<p><strong>碳排放:</strong> " . htmlspecialchars($record['footprint']) . " kg CO₂</p>" .
//             "<p><strong>獲得點數:</strong> " . htmlspecialchars($record['points']) . " 點</p>" .
//             "</div>" .
//             "</div>";
//     $count++;
// }

// return [
//     'records_html' => $out,
//     'points' => $user['total_points'] ?? 0,
//     'username' => $_SESSION['username'],
//     'error' => null
// ];

// 假設 $records 是從資料庫取得的記錄陣列
// 假設 $user 包含使用者資訊，$_SESSION['username'] 已設置

// 處理排序參數
$sort_field = $_GET['sort'] ?? 'record_time'; // 預設按時間排序
$sort_order = $_GET['order'] ?? 'desc'; // 預設降序
$valid_fields = ['record_time', 'distance', 'footprint', 'points']; // 允許的排序欄位

// 驗證排序欄位
if (!in_array($sort_field, $valid_fields)) {
    $sort_field = 'record_time';
}
// 驗證排序方向
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

// 對記錄進行排序
usort($records, function ($a, $b) use ($sort_field, $sort_order) {
    $val_a = $a[$sort_field];
    $val_b = $b[$sort_field];
    
    // 處理時間欄位的比較
    if ($sort_field === 'record_time') {
        $val_a = strtotime($val_a);
        $val_b = strtotime($val_b);
    }
    
    // 數值比較
    if ($val_a == $val_b) {
        return 0;
    }
    
    $result = ($val_a < $val_b) ? -1 : 1;
    return ($sort_order === 'asc') ? $result : -$result;
});

// 生成記錄的 HTML
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

// 排序選單選項
$sort_options = [
    'record_time' => '時間',
    'distance' => '距離',
    'footprint' => '碳排放',
    'points' => '獲得點數'
];

// 返回資料給前端
return [
    'records_html' => $out,
    'points' => $user['total_points'] ?? 0,
    'username' => $_SESSION['username'],
    'error' => null,
    'sort_field' => $sort_field,
    'sort_order' => $sort_order,
    'sort_options' => $sort_options
];
?>

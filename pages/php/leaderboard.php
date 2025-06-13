<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}



$count = 1;
$out = '';
$records = executeQuery(
    'SELECT 
    u.username,
    COALESCE(SUM(tr.points), 0) AS total_points,
    COALESCE(SUM(tr.footprint), 0) AS total_footprint
    FROM users u
    LEFT JOIN travel_records tr ON tr.user_id = u.id
    GROUP BY u.id, u.username
    ORDER BY total_points DESC, total_footprint ASC
    LIMIT 10',
    [],
    'all'
);
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

<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

$user = executeQuery(
    'SELECT total_points FROM users WHERE id = ?',
    [$_SESSION['user_id']],
    'one'
);

$rewards = executeQuery(
    'SELECT reward_id, name, points_required, description 
     FROM rewards 
     ORDER BY points_required ASC',
    [],
    'all'
);

if (!$user) {
    return [
        'error' => '無法獲取用戶數據',
        'points' => 0,
        'username' => $_SESSION['username'],
        'rewards_html' => ''
    ];
}

$count = 1;
$out = '';
foreach ($rewards as &$reward) {
    $out .= "<div class='card-body card mb-2'>" .
            "<h6 class='card-title'>" . htmlspecialchars($reward['name']) . "</h6>" .
            "<p class='card-text'>需要" . htmlspecialchars($reward['points_required']) . "</p>" .
            "<p class='card-text'>" . htmlspecialchars($reward['description']) . "</p>" .
            "<button class='btn btn-primary' onclick=\"redeemReward(" . $reward['reward_id'] . ")\">兌換</button>" .
            "</div>";
    $count++;
}

return [
    'rewards_html' => $out,
    'points' => $user['total_points'] ?? 0,
    'username' => $_SESSION['username'],
    'error' => null
];
?>

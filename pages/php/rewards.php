<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

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

$rewards = executeQuery(
    'SELECT reward_id, name, points_required, description 
     FROM rewards 
     WHERE points_required <= ? 
     ORDER BY points_required ASC',
    [$available_points],
    'all'
);

$count = 1;
$out = '';
foreach ($rewards as &$reward) {
    $out .= "<div class='card-body card mb-2'>" .
        "<h6 class='card-title'>" . htmlspecialchars($reward['name']) . "</h6>" .
        "<p class='card-text'>需要" . htmlspecialchars($reward['points_required']) . " 點</p>" .
        "<p class='card-text'>" . htmlspecialchars($reward['description']) . "</p>" .
        "<button class='btn btn-primary' onclick=\"redeemReward(" . $reward['reward_id'] . ")\">兌換</button>" .
        "</div>";
    $count++;
}

return [
    'rewards_html' => $out,
    'points' => $available_points,
    'username' => $_SESSION['username'],
    'error' => null
];

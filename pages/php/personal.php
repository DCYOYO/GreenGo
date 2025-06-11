<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

$search_username = trim($_POST['username'] ?? '');
$target_user_id = null;
$data = [
    'error' => null,
    'username' =>empty($search_username)??$_SESSION['username'],
    'is_owner' => true,
    'user' => [],
    'csrf_token' => generate_csrf_token()
];

$user_id = $_SESSION['user_id'];

if (!empty($search_username)) {
    $target_user = executeQuery(
        'SELECT id FROM users WHERE username = ?',
        [$search_username],
        'one'
    );

    if (!$target_user) {
        $data['error'] = "找不到用戶：" . htmlspecialchars($search_username);
        return $data;
    }

    $target_user_id = $target_user['id'];
    $data['is_owner'] = $target_user_id === $user_id;
} else {
    $target_user_id = $user_id;
}

$user_data = executeQuery(
    'SELECT u.username, u.total_points, u.total_footprint, p.bio, p.country_code, p.city, p.gender, p.birthdate, p.activity_level, p.last_update 
     FROM users u 
     LEFT JOIN personal_page p ON u.username = p.username 
     WHERE u.id = ?',
    [$target_user_id],
    'one'
);

if ($user_data) {
    $data['user'] = [
        'username' => htmlspecialchars($user_data['username']),
        'total_points' => $user_data['total_points'] ?? 0,
        'total_footprint' => number_format($user_data['total_footprint'] ?? 0, 2),
        'bio' => htmlspecialchars($user_data['bio'] ?? ''),
        'country_code' => htmlspecialchars($user_data['country_code'] ?? ''),
        'city' => htmlspecialchars($user_data['city'] ?? ''),
        'gender' => htmlspecialchars($user_data['gender'] ?? '不公開'),
        'birthdate' => htmlspecialchars($user_data['birthdate'] ?? ''),
        'activity_level' => htmlspecialchars($user_data['activity_level'] ?? '不公開'),
        'last_update' => htmlspecialchars($user_data['last_update'] ?? ''),
        'avatar' => file_exists(__DIR__ . "/../../avatars/$target_user_id.jpg") ? "/avatars/$target_user_id.jpg" : null
    ];
} else {
    $data['error'] = '無法獲取用戶數據';
}

return $data;
?>
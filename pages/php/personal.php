<?php
require_once __DIR__ . '/../../api/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['auth_token'])) {
    header('Location: /');
    exit;
}

$search_username = trim($_GET['username'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$target_user_id = null;
$data = [
    'error' => null,
    'username' => $_SESSION['username'],
    'is_owner' => true,
    'user' => []
];

$user_id = $_SESSION['user_id'];

if ($method === 'GET' && !empty($search_username)) {
    $target_user = executeQuery(
        'SELECT id FROM users WHERE username = ?',
        [$search_username],
        'one'
    );

    if (!$target_user) {
        $data['error'] = "找不到用戶：$search_username";
        return $data;
    }

    $target_user_id = $target_user['id'];
    $data['is_owner'] = $target_user_id === $user_id;
} else {
    $target_user_id = $user_id;
}

$user_data = executeQuery(
    'SELECT u.username, u.total_points, u.total_footprint, p.bio, p.country_code, p.city, p.gender, p.birthdate, p.activity_level, p.last_login 
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
        'gender' => htmlspecialchars($user_data['gender'] ?? ''),
        'birthday' => htmlspecialchars($user_data['birthday'] ?? ''),
        'activity_level' => htmlspecialchars($user_data['activity_level'] ?? ''),
        'last_login' => htmlspecialchars($user_data['last_login'] ?? ''),
        'avatar' => file_exists(__DIR__ . "/../../avatars/$target_user_id.jpg") ? "/avatars/$target_user_id.jpg" : null
    ];
} else {
    $data['error'] = '無法獲取用戶數據';
}

if ($method === 'POST' && $data['is_owner']) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if ($file['error'] === UPLOAD_ERR_OK) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false || !in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                $data['error'] = '無效的圖像檔案';
            } else {
                $upload_dir = __DIR__ . '/../../avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $upload_path = $upload_dir . $user_id . '.jpg';
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $data['user']['avatar'] = "/avatars/$user_id.jpg";
                } else {
                    $data['error'] = '頭像上傳失敗';
                }
            }
        } else {
            $data['error'] = '檔案上傳錯誤';
        }
    } else if ($action === 'edit_profile') {
        $bio = trim($_POST['bio'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $activity_level = trim($_POST['activity_level'] ?? '');

        $bio = strlen($bio) > 500 ? substr($bio, 0, 500) : $bio;
        $allowed_countries = ['台灣', '美國', '日本'];
        $allowed_genders = ['不公開', '男', '女', '其他'];
        $allowed_activity_levels = ['不公開', '低', '中', '高'];

        if (!in_array($country_code, $allowed_countries)) {
            $country_code = '';
        }
        if (!in_array($gender, $allowed_genders)) {
            $gender = '不公開';
        }
        if (!in_array($activity_level, $allowed_activity_levels)) {
            $activity_level = '不公開';
        }

        if ($birthday && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
            $birthday = '';
        }

        $updated = executeNonQuery(
            'UPDATE personal_page 
             SET bio = ?, country_code = ?, city = ?, gender = ?, birthday = ?, activity_level = ?, last_update = NOW() 
             WHERE username = ?',
            [$bio, $country_code, $city, $gender, $birthday, $activity_level, $_SESSION['username']]
        );

        if ($updated !== false) {
            $data['user']['bio'] = htmlspecialchars($bio);
            $data['user']['country_code'] = htmlspecialchars($country_code);
            $data['user']['city'] = htmlspecialchars($city);
            $data['user']['gender'] = htmlspecialchars($gender);
            $data['user']['birthday'] = htmlspecialchars($birthday);
            $data['user']['activity_level'] = htmlspecialchars($activity_level);
        } else {
            $data['error'] = '更新個人資料失敗';
        }
    }
}

return $data;
?>
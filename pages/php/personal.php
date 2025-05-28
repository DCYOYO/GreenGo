<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=carbon_tracker;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    return [
        'error' => '資料庫連線失敗：' . $e->getMessage(),
        'user' => null,
        'username' => $_GET['username'] ?? '',
        'session_username' => $_SESSION['username'] ?? ''
    ];
}

// 處理圖像上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']) && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $upload_dir = 'Uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['profile_picture'];
    if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $username . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $sql = "UPDATE personal_page SET profile_picture = :profile_picture WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['profile_picture' => $destination, 'username' => $username]);
            echo json_encode(['success' => '頭像上傳成功！', 'profile_picture' => $destination]);
            exit;
        } else {
            echo json_encode(['error' => '頭像上傳失敗，請稍後再試。']);
            exit;
        }
    } else {
        echo json_encode(['error' => '無效的檔案格式或檔案過大（上限 5MB）。']);
        exit;
    }
}

// 處理個人資料編輯
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile']) && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $bio = trim($_POST['bio'] ?? '');
    $country_code = trim($_POST['country_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $gender = in_array($_POST['gender'] ?? '', ['M', 'F', 'Other']) ? $_POST['gender'] : null;
    $birthdate = trim($_POST['birthdate'] ?? '');
    $activity_level = in_array($_POST['activity_level'] ?? '', ['Low', 'Medium', 'High']) ? $_POST['activity_level'] : null;

    $sql = "UPDATE personal_page SET bio = :bio, country_code = :country_code, city = :city, gender = :gender, birthdate = :birthdate, activity_level = :activity_level WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'bio' => $bio ?: null,
        'country_code' => $country_code ?: null,
        'city' => $city ?: null,
        'gender' => $gender,
        'birthdate' => $birthdate ?: null,
        'activity_level' => $activity_level,
        'username' => $username
    ]);
    echo json_encode(['success' => '個人資料更新成功！']);
    exit;
}

// 查詢個人主頁
$user = null;
$username = '';
if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $username = trim($_GET['username']);
    $sql = "SELECT pp.*, u.total_points, u.total_footprint 
            FROM personal_page pp 
            LEFT JOIN users u ON pp.username = u.username 
            WHERE pp.username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

return [
    'user' => $user,
    'username' => $username,
    'session_username' => $_SESSION['username'] ?? '',
    'error' => null
];
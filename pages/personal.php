<?php
session_start();

// 添加會話日誌以排查問題
error_log('Session username: ' . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set'));

// 資料庫連線設定
try {
    $pdo = new PDO('mysql:host=localhost;dbname=carbon_tracker;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => '資料庫連線失敗：' . htmlspecialchars($e->getMessage())]);
    exit;
}

// 處理圖像上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']) && isset($_SESSION['username'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $username = $_SESSION['username'];
    $upload_dir = 'Uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log('Failed to create upload directory: ' . $upload_dir);
            echo json_encode(['error' => '無法創建上傳目錄，請檢查伺服器權限。']);
            exit;
        }
    }

    if (!is_writable($upload_dir)) {
        error_log('Upload directory is not writable: ' . $upload_dir);
        echo json_encode(['error' => '上傳目錄不可寫，請檢查伺服器權限。']);
        exit;
    }

    $file = $_FILES['profile_picture'];
    error_log('File upload attempt: ' . json_encode($file));

    if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $username . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $sql = "UPDATE personal_page SET profile_picture = :profile_picture WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['profile_picture' => $destination, 'username' => $username]);
            echo json_encode(['success' => '頭像上傳成功！', 'profile_picture' => $destination]);
        } else {
            error_log('Failed to move uploaded file to: ' . $destination);
            echo json_encode(['error' => '頭像上傳失敗，請稍後再試。']);
        }
    } else {
        $error_msg = '無效的檔案格式或檔案過大（上限 5MB）。';
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_msg = '檔案上傳錯誤，代碼：' . $file['error'];
        }
        error_log('File upload error: ' . $error_msg);
        echo json_encode(['error' => $error_msg]);
    }
    exit;
}

// 處理個人資料編輯
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile']) && isset($_SESSION['username'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $username = $_SESSION['username'];
    $bio = trim($_POST['bio'] ?? '');
    $country_code = trim($_POST['country_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $gender = in_array($_POST['gender'] ?? '', ['M', 'F', 'Other']) ? $_POST['gender'] : null;
    $birthdate = trim($_POST['birthdate'] ?? '');
    $activity_level = in_array($_POST['activity_level'] ?? '', ['Low', 'Medium', 'High']) ? $_POST['activity_level'] : null;

    error_log('Updating profile for user: ' . $username . ', data: ' . json_encode($_POST));

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

// 查詢個人主頁 (僅在 GET 請求中渲染 HTML)
$user = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $username = trim($_GET['username']);
    $sql = "SELECT pp.*, u.total_points, u.total_footprint 
            FROM personal_page pp 
            LEFT JOIN users u ON pp.username = u.username 
            WHERE pp.username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log('Queried username: ' . ($user ? $user['username'] : 'Not found'));
    error_log('Session matches user: ' . (isset($_SESSION['username']) && $user && $_SESSION['username'] === $user['username'] ? 'Yes' : 'No'));
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人主頁查詢</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">個人主頁查詢</h1>
        
        <form class="row g-3 mb-4" method="GET">
            <div class="col-auto">
                <input type="text" class="form-control" name="username" placeholder="輸入用戶名稱" value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">查詢</button>
            </div>
        </form>

        <?php if ($user): ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h2 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <div id="profile-picture-container">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="頭像" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                        <?php else: ?>
                            <p class="text-muted">無頭像</p>
                        <?php endif; ?>
                    </div>
                    <p>總積分：<?php echo htmlspecialchars($user['total_points'] ?? 0); ?> | 總碳足跡：<?php echo htmlspecialchars($user['total_footprint'] ?? 0); ?> kg</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>簡介：</strong> <span id="bio"><?php echo htmlspecialchars($user['bio'] ?? '未設定'); ?></span></p>
                    <p><strong>國家：</strong> <span id="country_code"><?php echo htmlspecialchars($user['country_code'] ?? '未設定'); ?></span></p>
                    <p><strong>性別：</strong> <span id="gender"><?php echo htmlspecialchars($user['gender'] === 'M' ? '男' : ($user['gender'] === 'F' ? '女' : ($user['gender'] === 'Other' ? '其他' : '未設定'))); ?></span></p>
                    <p><strong>生日：</strong> <span id="birthdate"><?php echo htmlspecialchars($user['birthdate'] ?? '未設定'); ?></span></p>
                    <p><strong>活躍程度：</strong> <span id="activity_level"><?php echo htmlspecialchars($user['activity_level'] ?? '未設定'); ?></span></p>
                    <p><strong>上次登入：</strong> <?php echo htmlspecialchars($user['last_login'] ?? '未知'); ?></p>
                </div>
            </div>

            <button class="btn btn-outline-secondary mb-3 me-2" id="upload-toggle-btn">上傳頭像</button>
            <button class="btn btn-outline-secondary mb-3" id="edit-toggle-btn">編輯個人資料</button>

            <div class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h3>上傳頭像</h3>
                    <div id="upload-message" class="alert d-none"></div>
                    <form id="upload-form" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">選擇圖像（jpg, png, gif，最大 5MB）</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" required>
                        </div>
                        <div class="mb-3">
                            <img id="image-preview" class="img-fluid rounded-circle d-none" style="max-width: 150px;" alt="圖像預覽">
                        </div>
                        <button type="submit" class="btn btn-primary">上傳</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h3>編輯個人資料</h3>
                    <div id="edit-message" class="alert d-none"></div>
                    <form id="edit-profile-form">
                        <div class="mb-3">
                            <label for="bio" class="form-label fw-bold">自我介紹</label>
                            <textarea class="form-control" name="bio" id="bio" rows="3" placeholder="輸入你的自我介紹..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="country_code" class="form-label fw-bold">國家</label>
                            <select id="country_code" name="country_code" class="form-select" required>
                                <option value="">請選擇國家</option>
                                <option value="TW" <?php echo ($user['country_code'] === 'TW') ? 'selected' : ''; ?>>台灣</option>
                                <option value="US" <?php echo ($user['country_code'] === 'US') ? 'selected' : ''; ?>>美國</option>
                                <option value="JP" <?php echo ($user['country_code'] === 'JP') ? 'selected' : ''; ?>>日本</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label fw-bold">性別</label>
                            <select class="form-select" name="gender" id="gender">
                                <option value="" <?php echo empty($user['gender']) ? 'selected' : ''; ?>>不公開</option>
                                <option value="M" <?php echo ($user['gender'] === 'M') ? 'selected' : ''; ?>>男</option>
                                <option value="F" <?php echo ($user['gender'] === 'F') ? 'selected' : ''; ?>>女</option>
                                <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="birthdate" class="form-label fw-bold">生日</label>
                            <input type="date" class="form-control" name="birthdate" id="birthdate" value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>">
                        </div>
                        <input type="hidden" name="edit_profile" value="1">
                        <button type="submit" class="btn btn-success w-100 mt-3">儲存變更</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                找不到用戶：<?php echo htmlspecialchars($username ?? '未提供'); ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/personal.js?v=<?php echo time(); ?>"></script>
</body>
</html>
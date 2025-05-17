<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// 資料庫連線設定
$host = '127.0.0.1'; // 你的資料庫主機
$db_user = 'root';    // 你的資料庫用戶名
$db_pass = '';        // 你的資料庫密碼
$db_name = 'carbon_tracker'; // 資料庫名稱

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查詢當前用戶的個人主頁資訊
    $sql = "SELECT pp.*, u.username, u.total_points, u.total_footprint 
            FROM personal_page pp 
            LEFT JOIN users u ON pp.username = u.username 
            WHERE pp.username = (SELECT username FROM users WHERE id = :user_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = '資料庫連線失敗：' . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGo - 個人主頁</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div id="user-info" class="user-info-box d-none">
        <div class="d-flex align-items-center">
            <div class="user-avatar rounded-circle me-2 d-flex align-items-center justify-content-center">
                <span id="avatar-initial" class="text-white fw-bold"></span>
            </div>
            <span id="username-display" class="me-3 fw-bold"></span>
            <button onclick="logout()" class="btn btn-outline-light btn-sm">登出</button>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <h1 class="mb-4 text-success-emphasis fw-bold">
                <i class="bi bi-leaf" style="color: #90ee90;">GreenGo&nbsp;</i>
                <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
            </h1>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="tracking.php">追蹤</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">歷史紀錄</a></li>
                    <li class="nav-item"><a class="nav-link" href="leaderboard.php">排行榜</a></li>
                    <li class="nav-item"><a class="nav-link" href="rewards.php">獎勵</a></li>
                    <li class="nav-item"><a class="nav-link active" href="personal.php">個人主頁</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <div id="personal-section" class="card mb-4" style="border-radius: 15px;">
            <div class="card-body" style="border-radius: 15px;">
                <h5 class="card-title">個人主頁</h5>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php elseif ($user): ?>
                    <div class="d-flex flex-column flex-md-row align-items-center mb-4">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="頭像" class="rounded-circle me-4 mb-3 mb-md-0" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-4 mb-3 mb-md-0" style="width: 100px; height: 100px;">
                                <span class="text-white fw-bold"><?php echo htmlspecialchars(substr($user['username'], 0, 1)); ?></span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h3 class="h5"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p class="text-muted">總積分：<?php echo htmlspecialchars($user['total_points']); ?> 點 | 總碳足跡：<?php echo htmlspecialchars($user['total_footprint']); ?> kg</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>簡介：</strong> <?php echo $user['bio'] ? htmlspecialchars($user['bio']) : '未填寫'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>國家/城市：</strong> <?php echo ($user['country_code'] ? htmlspecialchars($user['country_code']) : '未填寫') . ($user['city'] ? ' / ' . htmlspecialchars($user['city']) : ''); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>性別：</strong> <?php echo $user['gender'] ? ($user['gender'] === 'M' ? '男' : ($user['gender'] === 'F' ? '女' : '其他')) : '未填寫'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>生日：</strong> <?php echo $user['birthdate'] ? htmlspecialchars($user['birthdate']) : '未填寫'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>活躍程度：</strong> <?php echo $user['activity_level'] ? htmlspecialchars($user['activity_level']) : '未填寫'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>上次登入：</strong> <?php echo $user['last_login'] ? htmlspecialchars($user['last_login']) : '未登入'; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        尚未設定個人主頁資訊。
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/common.js"></script>
    <script src="../assets/js/personal.js"></script>
</body>
</html>
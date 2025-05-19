<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人主頁 - Carbon Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto p-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-center mb-6">個人主頁查詢</h1>

        <!-- 查詢表單 -->
        <form method="GET" action="" class="mb-8 flex justify-center">
            <input type="text" name="username" placeholder="輸入用戶名稱" value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>" class="p-2 border rounded-l-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
            <button type="submit" class="bg-green-500 text-white p-2 rounded-r-md hover:bg-green-600">查詢</button>
        </form>

        <?php
        // 資料庫連線設定
        $host = '127.0.0.1'; // 你的資料庫主機
        $db_user = 'root';    // 你的資料庫用戶名
        $db_pass = '';        // 你的資料庫密碼
        $db_name = 'carbon_tracker'; // 資料庫名稱

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo '<div class="bg-red-100 text-red-700 p-4 rounded-md text-center">資料庫連線失敗：' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }

        // 查詢個人主頁
        if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
            $username = trim($_GET['username']);
            $sql = "SELECT pp.*, u.total_points, u.total_footprint 
                    FROM personal_page pp 
                    LEFT JOIN users u ON pp.username = u.username 
                    WHERE pp.username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center mb-4">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="頭像" class="w-24 h-24 rounded-full mr-4 object-cover">
                        <?php else: ?>
                            <div class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center mr-4">
                                <span class="text-gray-600">無頭像</span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($user['username']); ?></h2>
                            <p class="text-gray-600">總積分：<?php echo htmlspecialchars($user['total_points']); ?> | 總碳足跡：<?php echo htmlspecialchars($user['total_footprint']); ?> kg</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <p><strong>簡介：</strong> <?php echo $user['bio'] ? htmlspecialchars($user['bio']) : '未填寫'; ?></p>
                        <p><strong>國家/城市：</strong> <?php echo ($user['country_code'] ? htmlspecialchars($user['country_code']) : '未填寫') . ($user['city'] ? ' / ' . htmlspecialchars($user['city']) : ''); ?></p>
                        <p><strong>性別：</strong> <?php echo $user['gender'] ? htmlspecialchars($user['gender']) : '未填寫'; ?></p>
                        <p><strong>生日：</strong> <?php echo $user['birthdate'] ? htmlspecialchars($user['birthdate']) : '未填寫'; ?></p>
                        <p><strong>活躍程度：</strong> <?php echo $user['activity_level'] ? htmlspecialchars($user['activity_level']) : '未填寫'; ?></p>
                        <p><strong>上次登入：</strong> <?php echo $user['last_login'] ? htmlspecialchars($user['last_login']) : '未登入'; ?></p>
                    </div>
                </div>
                <?php
            } else {
                echo '<div class="bg-yellow-100 text-yellow-700 p-4 rounded-md text-center">找不到用戶：' . htmlspecialchars($username) . '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
<?php
// 檢查是否已登入，若已登入則重定向到 tracking 頁面
if (isset($_SESSION['user_id'])) {
    header('Location: /tracking');
    exit;
}

// 顯示錯誤訊息（如果有）
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGo - 碳足跡追蹤</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div>
            <h1 class="mb-4 text-success-emphasis fw-bold">
                <i class="bi bi-leaf" style="color: #90ee90;">GreenGo </i>
                <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
            </h1>
        </div>
        <div id="login-form" class="card p-4">
            <h2 class="text-center">登入</h2>
            <form action="/api/backend.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label for="login-username" class="form-label">使用者名稱</label>
                    <input type="text" class="form-control" id="login-username" name="username" placeholder="輸入使用者名稱" required>
                </div>
                <div class="mb-3">
                    <label for="login-password" class="form-label">密碼</label>
                    <input type="password" class="form-control" id="login-password" name="password" placeholder="輸入密碼" required>
                </div>
                <div class="mb-3">
                      <label for="login-captcha" class="form-label">驗證碼</label>
                      <div class="d-flex align-items-center">
                          <input type="text" class="form-control me-2" id="login-captcha" name="captcha" placeholder="輸入驗證碼" required>
                          <img src="/pages/captcha.php" alt="點擊刷新" id="captcha-image" style="cursor: pointer;" title="點擊刷新" onclick="this.src='/pages/captcha.php'">
                      </div>
                  </div>
                <button type="submit" class="btn btn-primary w-100" id="login-btn">登入</button>
                <?php if ($error): ?>
                    <div class="text-danger mt-2 text-center" id="login-error"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <div id="login-error" class="text-danger mt-2 text-center d-none"></div>
                <?php endif; ?>
            </form>
            <p class="mt-3 text-center">還沒有帳號？ <a href="#" onclick="showRegisterForm()">註冊</a></p>
        </div>
        <div id="register-form" class="card p-4 d-none">
            <h2 class="text-center">註冊</h2>
            <form action="/api/backend.php" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="mb-3">
                    <label for="register-username" class="form-label">使用者名稱</label>
                    <input type="text" class="form-control" id="register-username" name="username" placeholder="輸入使用者名稱" required>
                </div>
                <div class="mb-3">
                    <label for="register-password" class="form-label">密碼</label>
                    <input type="password" class="form-control" id="register-password" name="password" placeholder="輸入密碼" required>
                </div>
                <div class="mb-3">
                    <label for="register-password-confirm" class="form-label">確認密碼</label>
                    <input type="password" class="form-control" id="register-password-confirm" name="password_confirm" placeholder="再次輸入密碼" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="register-btn">註冊</button>
                <?php if ($error): ?>
                    <div class="text-danger mt-2 text-center" id="register-error"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <div id="register-error" class="text-danger mt-2 text-center d-none"></div>
                <?php endif; ?>
            </form>
            <p class="mt-3 text-center">已有帳號？ <a href="#" onclick="showLoginForm()">登入</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/login.js"></script>
</body>
</html>
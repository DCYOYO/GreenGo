<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: pages/tracking.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenGo - 登入/註冊</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
  <div class="container py-5">
    <div>
      <h1 class="mb-4 text-success-emphasis fw-bold">
        <i class="bi bi-leaf" style="color: #90ee90;">GreenGo </i>
        <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
      </h1>
    </div>
    <div id="login-form" class="card mb-4" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">登入</h5>
        <div id="login-error" class="text-danger d-none mb-3"></div>
        <div class="mb-3">
          <label for="login-username" class="form-label">使用者名稱</label>
          <input type="text" id="login-username" class="form-control" placeholder="輸入使用者名稱" required>
        </div>
        <div class="mb-3">
          <label for="login-password" class="form-label">密碼</label>
          <input type="password" id="login-password" class="form-control" placeholder="輸入密碼" required>
        </div>
        <button onclick="login()" class="btn btn-primary me-2">登入</button>
        <button onclick="showRegisterForm()" class="btn btn-secondary">註冊</button>
      </div>
    </div>
    <div id="register-form" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">註冊</h5>
        <div id="register-error" class="text-danger d-none mb-3"></div>
        <div class="mb-3">
          <label for="register-username" class="form-label">使用者名稱</label>
          <input type="text" id="register-username" class="form-control" placeholder="輸入使用者名稱" required>
        </div>
        <div class="mb-3">
          <label for="register-password" class="form-label">密碼</label>
          <input type="password" id="register-password" class="form-control" placeholder="輸入密碼" required>
        </div>
        <div class="mb-3">
          <label for="register-password-confirm" class="form-label">確認密碼</label>
          <input type="password" id="register-password-confirm" class="form-control" placeholder="再次輸入密碼" required>
        </div>
        <button onclick="register()" class="btn btn-primary me-2">註冊</button>
        <button onclick="showLoginForm()" class="btn btn-secondary">返回登入</button>
      </div>
    </div>
  </div>
  <script src="assets/js/common.js"></script>
  <script src="assets/js/index.js"></script>
</body>
</html>
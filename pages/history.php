<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenGo - 歷史紀錄</title>
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
      <div>
      <h1 class="mb-4 text-success-emphasis fw-bold">
        <i class="bi bi-leaf" style="color: #90ee90;">GreenGo </i>
        <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
      </h1>
    </div>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="tracking.php">追蹤</a></li>
          <li class="nav-item"><a class="nav-link active" href="history.php">歷史紀錄</a></li>
          <li class="nav-item"><a class="nav-link" href="leaderboard.php">排行榜</a></li>
          <li class="nav-item"><a class="nav-link" href="rewards.php">獎勵</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container py-5">
    <div id="history-section" class="card mb-4" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">目前累積點數：<span id="points">0</span> 點</h5>
        <ul id="history-list" class="list-group list-group-flush mt-3"></ul>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/common.js"></script>
  <script src="../assets/js/history.js"></script>
</body>
</html>
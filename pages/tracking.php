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
  <title>GreenGo - 追蹤</title>
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
      <a class="navbar-brand" href="tracking.php">GreenGo</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="tracking.php">追蹤</a></li>
          <li class="nav-item"><a class="nav-link" href="history.php">歷史紀錄</a></li>
          <li class="nav-item"><a class="nav-link" href="leaderboard.php">排行榜</a></li>
          <li class="nav-item"><a class="nav-link" href="rewards.php">獎勵</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container py-5">
    <div>
      <h1 class="mb-4 text-success-emphasis fw-bold">
        <i class="bi bi-leaf" style="color: #90ee90;">GreenGo </i>
        <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
      </h1>
    </div>
    <div id="tracking-section" class="card mb-4" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <div class="mb-3">
          <label class="form-label">選擇交通方式：</label><br>
          <div id="transport-options" role="group"></div>
        </div>
        <div class="card mb-4" style="border-radius: 15px;">
          <div class="card-body" style="border-radius: 15px;">
            <h5 class="mb-3">使用地圖選取起點與終點</h5>
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <input id="start" class="form-control" placeholder="輸入起點或在地圖上點選" />
              </div>
              <div class="col-md-6">
                <input id="end" class="form-control" placeholder="輸入終點或在地圖上點選" />
              </div>
            </div>
            <div id="map" style="height: 50vh; min-height: 300px;" class="mb-3"></div>
            <button onclick="calculateRoute()" class="btn btn-outline-primary">計算地圖距離</button>
            <p class="mt-2 text-muted" id="map-distance"></p>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">或輸入今日里程 (公里)：</label>
          <input type="number" id="distance" class="form-control" placeholder="例如：5">
        </div>
        <button onclick="handleSubmit()" class="btn btn-success me-2">手動紀錄</button>
        <button onclick="startTracking()" class="btn btn-primary me-2">開始追蹤</button>
        <button onclick="stopTracking()" class="btn btn-danger">停止並紀錄</button>
      </div>
    </div>
    <div id="eco-suggestion" class="alert alert-success d-none"></div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/common.js"></script>
  <script src="../assets/js/tracking.js"></script>
  <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAphkYf9MXbZuU0QdWHmu7dER5YtYEijrQ&libraries=places&callback=initMap"></script>
</body>
</html>
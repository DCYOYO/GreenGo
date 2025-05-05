<!DOCTYPE html>
<html lang="zh-Hant">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenGo網站</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
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

  <div class="container py-5">

    <div>
      <h1 class="mb-4 text-success-emphasis fw-bold">
        <i class="bi bi-leaf " style="color: #90ee90;">GreenGo&nbsp;</i>
        <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
      </h1>
    </div>



    <div id="login-form" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">登入</h5>
        <div class="mb-3">
          <label for="login-username" class="form-label">使用者名稱</label>
          <input type="text" id="login-username" class="form-control" placeholder="輸入使用者名稱">
        </div>
        <div class="mb-3">
          <label for="login-password" class="form-label">密碼</label>
          <input type="password" id="login-password" class="form-control" placeholder="輸入密碼">
        </div>
        <button onclick="login()" class="btn btn-primary me-2">登入</button>
        <button onclick="showRegisterForm()" class="btn btn-secondary">註冊</button>
      </div>
    </div>

    <div id="register-form" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">註冊</h5>
        <div class="mb-3">
          <label for="register-username" class="form-label">使用者名稱</label>
          <input type="text" id="register-username" class="form-control" placeholder="輸入使用者名稱">
        </div>
        <div class="mb-3">
          <label for="register-password" class="form-label">密碼</label>
          <input type="password" id="register-password" class="form-control" placeholder="輸入密碼">
        </div>
        <button onclick="register()" class="btn btn-primary me-2">註冊</button>
        <button onclick="showLoginForm()" class="btn btn-secondary">返回登入</button>
      </div>
    </div>

    <div id="tracking-section" class="card mb-4 d-none" style="border-radius: 15px;">
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
            <div id="map" style="height: 500px;" class="mb-3"></div>
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

    <div id="history-section" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">目前累積點數：<span id="points">0</span> 點</h5>
        <ul id="history-list" class="list-group list-group-flush mt-3"></ul>
      </div>
    </div>

    <div id="leaderboard-section" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">排行榜</h5>
        <table class="table table-dark">
          <thead>
            <tr>
              <th>排名</th>
              <th>使用者</th>
              <th>總點數</th>
              <th>總碳排放</th>
            </tr>
          </thead>
          <tbody id="leaderboard-list"></tbody>
        </table>
      </div>
    </div>

    <div id="rewards-section" class="card mb-4 d-none" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">兌換獎勵</h5>
        <div id="rewards-list"></div>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
  <script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAphkYf9MXbZuU0QdWHmu7dER5YtYEijrQ&libraries=places&callback=initMap">
  </script>
</body>

</html>
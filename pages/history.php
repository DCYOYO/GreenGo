<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}
$host = 'localhost';
$dbname = 'carbon_tracker';
$username = 'root';
$password = '';
$out = '';
$count=0;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error = '資料庫連接失敗: ' . $e->getMessage();
    $points = 0;
}
$stmt = $pdo->prepare('SELECT transport, distance, footprint, points, record_time FROM travel_records WHERE user_id = ? ORDER BY record_time DESC');
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($records as &$record) {
    $count++;
    $record['record_time'] = date('Y-m-d H:i:s', strtotime($record['record_time']));
    $record['points'] = number_format($record['points']);
    $record['footprint'] = number_format($record['footprint'],2);
    $record['distance'] = number_format($record['distance'], 2);
    $record['transport'] = htmlspecialchars($record['transport']);
    $out .= "<li class='list-group-item'>第{$count}筆-{$record['record_time']}-{$record['transport']}-{$record['distance']} 公里 - 碳排放 {$record['footprint']} kg CO₂ - 獲得 {$record['points']} 點</li>";
}
$stmt = $pdo->prepare('SELECT total_points AS points FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$points = $records[0]['points'] ?? 0;
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGo - 歷史紀錄</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
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
    <button onclick="location.href='/tracking'" class="btn btn-link text-decoration-none">
      <h1 class="mb-4 text-success-emphasis fw-bold" >
        <i class="bi bi-leaf" style="color: #90ee90;">GreenGo </i>
        <i class="bi bi-leaf" style="color:rgb(200, 255, 200);font-size:19px;">讓環保充斥在你我生活之間</i>
      </h1>
    </button>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/tracking">追蹤</a></li>
          <li class="nav-item"><a class="nav-link active" href="/history">歷史紀錄</a></li>
          <li class="nav-item"><a class="nav-link" href="/leaderboard">排行榜</a></li>
          <li class="nav-item"><a class="nav-link" href="/rewards">獎勵</a></li>
        </ul>
      </div>
            <form action="/api/backend.php" method="POST"  class="d-flex align-items-center">
        <div class="user-avatar rounded-circle me-2 d-flex align-items-center justify-content-center">
          <span class="text-white fw-bold"><?php echo $_SESSION['username'][0] ?></span>
        </div>
        <span class="me-3 fw-bold "><?php echo $_SESSION['username'] ?></span>

        <input type="hidden" name="action" value="logout">
        <button type="submit" class="btn btn-outline-danger btn-sm">登出</button>
      </form>
    </div>
  </nav>
  <div class="container py-5">
    <div id="history-section" class="card mb-4" style="border-radius: 15px;">
      <div class="card-body" style="border-radius: 15px;">
        <h5 class="card-title">目前累積點數：<span id="points"><?php echo $points?></span> 點</h5>
        <ul class="list-group list-group-flush mt-3"><?php echo $out?></ul>
      </div>
    </div>
  </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/common.js"></script>
    <script src="/assets/js/history.js"></script>
</body>
</html>
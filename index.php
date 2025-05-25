<?php
session_start();

// ====== 設定區 ======
define('DEBUG_MODE', true); // 生產環境請改為 false

// 可訪問的頁面定義
$pages = [
    '' => 'pages/login.php',
    'register' => 'pages/login.php',
    'tracking' => 'pages/tracking.php',
    'history' => 'pages/history.php',
    'leaderboard' => 'pages/leaderboard.php',
    'rewards' => 'pages/rewards.php',
    'captcha'=>'pages/captcha'
];

// ====== 共用函式 ======
function debug_log($message) {
    if (DEBUG_MODE) {
        echo "<pre>Debug: $message</pre>";
    }
}

function require_with_check($filePath) {
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        echo "404 - 檔案未找到: " . htmlspecialchars($filePath);
        exit;
    }
}

function clean_path($uri) {
    $request = strtok($uri, '?');
    $path = trim($request, '/');
    $path = preg_replace('/\.php$/', '', $path);
    return $path;
}

function handle_api_request($path) {
    if (preg_match('#^api/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
        $apiFile = __DIR__ . '/api/' . $matches[1] . '.php';
        debug_log("API 路由到: $apiFile");
        require_with_check($apiFile);
        exit;
    }
}

function handle_page_request($path, $pages) {
    if (array_key_exists($path, $pages)) {
        // 除了 login 與 register，其餘頁面需登入
        if (!in_array($path, ['', 'register']) && !isset($_SESSION['user_id'])) {
            echo "⚠️ 從 index.php 檢查到未登入，導回 /<br>";
            header('Location: /');
            exit;
        }

        $pageFile = __DIR__ . '/' . $pages[$path];
        //echo $pageFile;
        //debug_log("載入頁面: $pageFile");
        require_with_check($pageFile);
        exit;
    }
}

// ====== 路由流程 ======
$path = clean_path($_SERVER['REQUEST_URI']);
//debug_log("Request path: $path");

// API 路由處理
handle_api_request($path);

// 頁面處理
handle_page_request($path, $pages);

// 預設 404 處理
http_response_code(404);
echo "404 - 頁面未找到: " . htmlspecialchars($path);
exit;
?>
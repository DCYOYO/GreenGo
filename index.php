<?php
// index.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.use_strict_mode', 1);

// ====== 設定區 ======
define('DEBUG_MODE', true);
define('CONTROLLER_PATH', __DIR__ . '/pages/php/'); // 修改為 php
define('TEMPLATE_PATH', __DIR__ . '/pages/html/');  // 修改為 html

// 路由表：映射路徑到控制器和模板
$pages = [
    '' => ['controller' => 'login.php', 'template' => 'login.html'],
    'register' => ['controller' => 'login.php', 'template' => 'register.html'],
    'tracking' => ['controller' => 'tracking.php', 'template' => 'tracking.html'],
    'history' => ['controller' => 'history.php', 'template' => 'history.html'],
    'leaderboard' => ['controller' => 'leaderboard.php', 'template' => 'leaderboard.html'],
    'rewards' => ['controller' => 'rewards.php', 'template' => 'rewards.html'],
    'personal' => ['controller' => 'personal.php', 'template' => 'personal.html'],
    'captcha' => ['controller' => 'captcha.php', 'template' => null],
];

// 無需登錄的頁面
$public_pages = ['', 'register', 'captcha'];

// ====== 共用函式 ======
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function debug_log($message) {
    if (DEBUG_MODE) {
        error_log("Debug: $message", 3, __DIR__ . '/logs/debug.log');
    }
}

function require_with_check($filePath, $type = 'controller') {
    if (file_exists($filePath)) {
        if ($type === 'controller') {
            return require_once $filePath; // 返回控制器數據
        } else {
            return file_get_contents($filePath); // 模板作為字符串返回
        }
    } else {
        http_response_code(404);
        error_log("404 - $type not found: $filePath");
        require_once TEMPLATE_PATH . '404.html';
        exit;
    }
}

function clean_path($uri) {
    $path = parse_url($uri, PHP_URL_PATH);
    $path = trim($path, '/');
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }
    $path = str_replace(['..', '//', '\\'], '', $path);
    return $path;
}

function handle_api_request($path) {
    if (str_starts_with($path, 'api/')) {
        $apiName = substr($path, 4);
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $apiName)) {
            $apiFile = __DIR__ . '/api/' . $apiName . '.php';
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed']);
                exit;
            }
            require_with_check($apiFile, 'controller');
            exit;
        }
    }
}

function render_template($templateFile, $data = []) {
    extract($data, EXTR_SKIP);
    ob_start();
    include $templateFile;
    return ob_get_clean();
}

function handle_page_request($path, $pages, $public_pages) {
    if (!array_key_exists($path, $pages)) {
        http_response_code(404);
        require_with_check(TEMPLATE_PATH . '404.html', 'template');
        exit;
    }

    $route = $pages[$path];
    $controllerFile = CONTROLLER_PATH . $route['controller'];
    $templateFile = $route['template'] ? TEMPLATE_PATH . $route['template'] : null;

    // 特殊處理 CAPTCHA
    if ($path === 'captcha') {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        require_with_check($controllerFile, 'controller');
        exit;
    }

    // 非公開頁面需要 SESSION
    if (!in_array($path, $public_pages)) {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            debug_log("未登入，導回 /");
            header('Location: /');
            exit;
        }
    }

    // 執行控制器並獲取數據
    $data = require_with_check($controllerFile, 'controller') ?: [];

    // 渲染模板（如果有）
    if ($templateFile) {
        echo render_template($templateFile, $data);
    }
    exit;
}

// ====== 路由流程 ======
$path = clean_path($_SERVER['REQUEST_URI']);
debug_log("Request path: $path");

handle_api_request($path);
handle_page_request($path, $pages, $public_pages);

http_response_code(404);
require_with_check(TEMPLATE_PATH . '404.html', 'template');
exit;
?>
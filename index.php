<?php
// index.php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.use_strict_mode', 1);

function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function debug_log($message) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Debug: $message", 3, __DIR__ . '/logs/debug.log');
    }
}

function require_with_check($filePath) {
    if (!str_ends_with($filePath, '.php')) {
        $filePath .= '.php';
    }
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        error_log("404 - File not found: $filePath");
        require_with_check(__DIR__ . '/pages/404.php');
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

function handle_api_request($path, $api_path) {
    if (preg_match('#^api/([a-zA-Z0-9_-]+)/?([0-9]*)#', $path, $matches)) {
        $resource = $matches[1];
        $id = $matches[2] ?? null;
        $apiFile = $api_path . $resource . '.php';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $_REQUEST['id'] = $id;
        require_with_check($apiFile);
        exit;
    }
}

function handle_page_request($path, $pages, $base_path) {
    if ($path === 'captcha') {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        require_with_check($base_path . $pages[$path]);
        exit;
    }
    session_start();
    if (array_key_exists($path, $pages)) {
        if (!in_array($path, ['', 'register']) && !isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        require_with_check($base_path . $pages[$path]);
        exit;
    }
}

// 路由流程
$config = require_once __DIR__ . '/config.php';
define('DEBUG_MODE', $config['debug_mode']);
$path = clean_path($_SERVER['REQUEST_URI']);
debug_log("Request path: $path");

handle_api_request($path, $config['api_path']);
handle_page_request($path, $config['pages'], $config['base_path']);

http_response_code(404);
require_with_check(__DIR__ . '/pages/404.php');
exit;
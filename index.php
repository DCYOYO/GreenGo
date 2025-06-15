<?php
// index.php
require_once __DIR__ . '/api/db.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 600); // 10 分鐘
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// 設定區
define('DEBUG_MODE', false);
define('CONTROLLER_PATH', __DIR__ . '/pages/php/');
define('TEMPLATE_PATH', __DIR__ . '/pages/html/');

// 路由表
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

// 共用函式
function safe_output($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function debug_log($message)
{
    if (DEBUG_MODE) {
        $context = sprintf(
            "User: %s, Method: %s, Path: %s",
            $_SESSION['user_id'] ?? 'Guest',
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        );
        error_log("[$context] $message", 3, __DIR__ . '/logs/debug.log');
    }
}

function require_with_check($filePath, $type = 'controller')
{
    if (file_exists($filePath)) {
        if ($type === 'controller') {
            return require_once $filePath;
        } else {
            return file_get_contents($filePath);
        }
    } else {
        http_response_code(404);
        error_log("404 - $type not found: $filePath");
        require_once TEMPLATE_PATH . '404.html';
        exit;
    }
}

function clean_path($uri)
{
    $path = parse_url($uri, PHP_URL_PATH);
    $path = trim($path, '/');
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }
    $path = str_replace(['..', '//', '\\'], '', $path);
    return $path;
}

function handle_api_request($path)
{
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

function render_template($templateFile, $data = [])
{
    extract($data, EXTR_SKIP);
    ob_start();
    include $templateFile;
    return ob_get_clean();
}

function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_auth_token($pdo, $path, $public_pages)
{
    if (!in_array($path, $public_pages)) {
        return;
    }

    if (!isset($_COOKIE['auth_token']) || isset($_SESSION['user_id'])) {
        return;
    }

    $token = $_COOKIE['auth_token'];
    $auth = executeQuery(
        'SELECT id, expires_at, remember_me FROM auth_tokens WHERE token = ?',
        [$token],
        'one'
    );

    if ($auth && $auth['remember_me'] == 1 && new DateTime($auth['expires_at']) > new DateTime()) {
        $user = executeQuery(
            'SELECT id, username FROM users WHERE id = ?',
            [$auth['user_id']],
            'one'
        );

        if ($user) {
            session_unset();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_regenerate_id(true);
            debug_log("Restored session for user: {$user['username']} via auth token");
            header('Location: /tracking');
            exit;
        }
    }
    // 清除無效或非 remember_me 的 token
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    executeNonQuery('DELETE FROM auth_tokens WHERE token = ?', [$token]);
}

function handle_page_request($path, $pages, $public_pages)
{
    if (!array_key_exists($path, $pages)) {
        http_response_code(404);
        require_with_check(TEMPLATE_PATH . '404.html', 'template');
        exit;
    }

    $route = $pages[$path];
    $controllerFile = CONTROLLER_PATH . $route['controller'];
    $templateFile = $route['template'] ? TEMPLATE_PATH . $route['template'] : null;

    if ($path === 'captcha') {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        require_with_check($controllerFile, 'controller');
        exit;
    }

    session_start();
    $check['remember_me'] = 0;
    if (isset($_SESSION['user_id']) && isset($_COOKIE['auth_token']))
        // 檢查 remember_me 狀態
        $check = executeQuery(
            'SELECT remember_me FROM auth_tokens WHERE user_id = ?',
            [$_SESSION['user_id']]
        );
    if (in_array($path, $public_pages) && isset($_SESSION['user_id']) && isset($_COOKIE['auth_token']) && $check['remember_me'] == 1) {
        header('Location: /tracking');
        exit;
    } else if ((in_array($path, $public_pages) && isset($_SESSION['user_id']) && isset($_COOKIE['auth_token']) && $check['remember_me'] == 0) || (in_array($path, $public_pages) && isset($_SESSION['user_id']) && isset($_COOKIE['auth_token']) && $check['remember_me'] == 0 && isset($_SESSION['first_login']) && $path == '')) {
        if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == true) {
            unset($_SESSION['first_login']);
        } else {
            if (isset($_COOKIE['auth_token'])) {
                executeNonQuery('DELETE FROM auth_tokens WHERE token = ?', [$_COOKIE['auth_token']]);
            }
            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
        }
    }

    $pdo = getPDO();
    check_auth_token($pdo, $path, $public_pages);
    generate_csrf_token();

    if (!in_array($path, $public_pages) && !isset($_SESSION['user_id'])) {
        debug_log("未登入，導回 /");
        header('Location: /');
        exit;
    }

    $data = require_with_check($controllerFile, 'controller') ?: [];

    if ($templateFile) {
        echo render_template($templateFile, $data);
    }
    exit;
}

// 路由流程
$path = clean_path($_SERVER['REQUEST_URI']);
debug_log("Request path: " . $path);

handle_api_request($path);
handle_page_request($path, $pages, $public_pages);

http_response_code(404);
require_with_check(TEMPLATE_PATH . '404.html', 'template');
exit;

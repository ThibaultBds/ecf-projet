<?php

$appEnv = getenv('APP_ENV') ?: 'production';
if ($appEnv === 'local' || $appEnv === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $appEnv !== 'local' && $appEnv !== 'development' ? 1 : 0);
ini_set('session.use_strict_mode', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 7200);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$GLOBALS['cspNonce'] = bin2hex(random_bytes(16));

function csp_nonce(): string
{
    return htmlspecialchars($GLOBALS['cspNonce'] ?? '', ENT_QUOTES, 'UTF-8');
}

header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'nonce-{$GLOBALS['cspNonce']}' https://cdn.jsdelivr.net; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'self'"
);

$router = new Router();

// Charger les routes
require_once __DIR__ . '/../routes/web.php';

$router->dispatch();

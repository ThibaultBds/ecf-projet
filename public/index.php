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
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Database;
use App\Core\DatabaseSessionHandler;

if (session_status() === PHP_SESSION_NONE) {
    try {
        $sessionHandler = new DatabaseSessionHandler(Database::getInstance()->getConnection());
        session_set_save_handler($sessionHandler, true);
    } catch (\Throwable $e) {
        // Fallback to default file sessions if DB not available
    }
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new Router();

// Charger les routes
require_once __DIR__ . '/../routes/web.php';

$router->dispatch();

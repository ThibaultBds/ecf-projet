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
use App\Core\Database;
use App\Core\DatabaseSessionHandler;

if (session_status() === PHP_SESSION_NONE) {
    $sessionStarted = false;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $sessionHandler = new DatabaseSessionHandler(Database::getInstance()->getConnection());
            session_set_save_handler($sessionHandler, true);
            session_start();
            $sessionStarted = true;
            break;
        } catch (\Throwable $e) {
            error_log("Session DB handler attempt $attempt failed: " . $e->getMessage());
            if ($attempt < 2) usleep(200000); // 200ms entre chaque tentative
        }
    }
    if (!$sessionStarted) {
        session_start(); // dernier recours : sessions fichier
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new Router();

// Charger les routes
require_once __DIR__ . '/../routes/web.php';

$router->dispatch();

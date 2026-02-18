<?php

$appEnv = getenv('APP_ENV') ?: 'production';
if ($appEnv === 'local' || $appEnv === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new Router();

// Charger les routes
require_once __DIR__ . '/../routes/web.php';

$router->dispatch();

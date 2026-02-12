<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Générer un token CSRF si absent
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Controllers/BaseController.php';
require_once __DIR__ . '/../src/Core/Auth/AuthManager.php';

// Charger les routes
require_once __DIR__ . '/../routes/web.php';

// Dispatcher la requête
$router = new Router();
$router->dispatch();

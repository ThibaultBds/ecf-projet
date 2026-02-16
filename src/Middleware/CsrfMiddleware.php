<?php

namespace App\Middleware;

class CsrfMiddleware
{
    public function handle()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return true;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('Token CSRF invalide ou expiré');
        }

        return true;
    }
}

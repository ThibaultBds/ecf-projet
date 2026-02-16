<?php

namespace App\Middleware;

use App\Core\Router;

class RoleMiddleware
{
    public function handle($requiredRole = null)
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role'])) {
            header('Location: /login');
            exit;
        }

        $userRole = strtolower(trim($_SESSION['user']['role']));
        $requiredRole = strtolower(trim($requiredRole));

        if ($userRole !== $requiredRole) {
            if ($userRole === 'admin') {
                return true;
            }

            Router::abort(403, 'Accès interdit : rôle insuffisant');
        }

        return true;
    }
}

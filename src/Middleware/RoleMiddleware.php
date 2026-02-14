<?php

namespace App\Middleware;

use App\Core\Router;

class RoleMiddleware
{
    /**
     * Gérer la requête avec un rôle requis
     *
     * @param string $requiredRole Le rôle requis (Administrateur, Moderateur, etc.)
     * @return bool True si autorisé, False sinon
     */
    public function handle($requiredRole = null)
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['role'])) {
            // Rediriger vers la page de connexion
            header('Location: /login');
            exit;
        }

        $userRole = $_SESSION['user']['role'];

        // Normaliser les rôles pour comparaison
        $userRole = strtolower(trim($userRole));
        $requiredRole = strtolower(trim($requiredRole));

        // Vérifier le rôle
        if ($userRole !== $requiredRole) {
            // Si admin, autoriser tout
            if ($userRole === 'admin') {
                return true;
            }

            // Sinon, accès interdit
            Router::abort(403, 'Accès interdit : rôle insuffisant');
        }

        return true;
    }
}

<?php

/**
 * Middleware d'authentification
 * Vérifie si l'utilisateur est connecté
 */
class AuthMiddleware
{
    /**
     * Gérer la requête
     *
     * @return bool True si autorisé, False sinon
     */
    public function handle()
    {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            // Sauvegarder l'URL demandée pour redirection après login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];

            // Rediriger vers la page de connexion
            header('Location: /login');
            exit;
        }

        return true;
    }
}

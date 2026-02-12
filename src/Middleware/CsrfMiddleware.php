<?php

/**
 * Middleware de protection CSRF
 * Vérifie le token CSRF sur les requêtes POST
 */
class CsrfMiddleware
{
    /**
     * Gérer la requête
     *
     * @return bool True si autorisé, False sinon
     */
    public function handle()
    {
        // Générer un token CSRF s'il n'existe pas
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Vérifier uniquement les requêtes POST, PUT, DELETE
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return true;
        }

        // Récupérer le token depuis la requête
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Vérifier le token
        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('Token CSRF invalide ou expiré');
        }

        return true;
    }
}

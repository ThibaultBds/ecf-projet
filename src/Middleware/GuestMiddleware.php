<?php

/**
 * Middleware Guest
 * Redirige les utilisateurs déjà connectés (pour les pages login/register)
 */
class GuestMiddleware
{
    /**
     * Gérer la requête
     *
     * @return bool True si autorisé, False sinon
     */
    public function handle()
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers le profil
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            header('Location: /profile');
            exit;
        }

        return true;
    }
}

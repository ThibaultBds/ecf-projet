<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Models\User;

class UserController extends BaseController
{
    /**
     * Afficher le profil de l'utilisateur
     */
    public function profile()
    {
        $userId = AuthManager::id();
        $userData = User::find($userId);
        $myTrips = User::recentTrips($userId, 10);

        // Messages flash
        $error = $_SESSION['flash_error'] ?? '';
        $success = $_SESSION['flash_success'] ?? '';
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $this->render('user/profile', [
            'title' => 'Mon Profil - EcoRide',
            'userData' => $userData,
            'myTrips' => $myTrips,
            'error' => $error,
            'success' => $success
        ]);
    }

    /**
     * Mettre à jour le rôle de l'utilisateur
     */
    public function update()
    {
        $userId = AuthManager::id();
        $role = $_POST['role'] ?? '';

        if (in_array($role, ['passager', 'chauffeur'])) {
            User::update($userId, ['role' => $role]);
            $_SESSION['user']['role'] = $role;
            $_SESSION['flash_success'] = 'Profil mis à jour avec succès !';
        } else {
            $_SESSION['flash_error'] = 'Rôle invalide.';
        }

        header('Location: /profile');
        exit;
    }
}

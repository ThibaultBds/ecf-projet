<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Core/Auth/AuthManager.php';

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
     * Mettre à jour le type d'utilisateur
     */
    public function update()
    {
        $userId = AuthManager::id();
        $userType = $_POST['user_type'] ?? '';

        if (in_array($userType, ['passager', 'chauffeur', 'les_deux'])) {
            User::update($userId, ['user_type' => $userType]);
            $_SESSION['user']['type'] = $userType;
            $_SESSION['flash_success'] = 'Profil mis à jour avec succès !';
        } else {
            $_SESSION['flash_error'] = 'Type d\'utilisateur invalide.';
        }

        header('Location: /profile');
        exit;
    }
}

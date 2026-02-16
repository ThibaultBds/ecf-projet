<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Models\User;

class UserController extends BaseController
{
    public function profile()
    {
        $userId = AuthManager::id();
        $userData = User::find($userId);
        $myTrips = User::recentTrips($userId, 10);

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

    public function update()
    {
        $userId = AuthManager::id();
        $type = $_POST['user_type'] ?? '';

        $isDriver = false;
        $isPassenger = false;

        if ($type === 'chauffeur') {
            $isDriver = true;
        } elseif ($type === 'passager') {
            $isPassenger = true;
        } elseif ($type === 'les_deux') {
            $isDriver = true;
            $isPassenger = true;
        } else {
            $_SESSION['flash_error'] = 'Type invalide.';
            header('Location: /profile');
            exit;
        }

        User::update($userId, [
            'is_driver' => $isDriver ? 1 : 0,
            'is_passenger' => $isPassenger ? 1 : 0
        ]);

        $_SESSION['user']['is_driver'] = $isDriver;
        $_SESSION['user']['is_passenger'] = $isPassenger;

        $_SESSION['flash_success'] = 'Profil mis à jour avec succès !';

        // If user just became a driver and has no vehicle, redirect to vehicle page
        if ($isDriver) {
            $vehicles = \App\Models\Vehicle::byUser($userId);
            if (empty($vehicles)) {
                $_SESSION['flash_success'] = 'Profil mis à jour ! Veuillez maintenant ajouter un véhicule.';
                header('Location: /driver/vehicles');
                exit;
            }
        }

        header('Location: /profile');
        exit;
    }

    public function uploadPhoto()
    {
        $userId = AuthManager::id();

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
            $_SESSION['flash_error'] = "Erreur upload.";
            header('Location: /profile');
            exit;
        }

        $file = $_FILES['photo'];

        // Check actual MIME type (not just the extension) to prevent spoofing
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realType = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png'
        ];

        if (!array_key_exists($realType, $allowedTypes)) {
            $_SESSION['flash_error'] = "Format invalide (jpg ou png uniquement).";
            header('Location: /profile');
            exit;
        }

        $extension = $allowedTypes[$realType];

        $newFileName = 'user_' . $userId . '_' . time() . '.' . $extension;

        $destination = __DIR__ . '/../../public/uploads/' . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['flash_error'] = "Erreur sauvegarde fichier.";
            header('Location: /profile');
            exit;
        }

        User::updatePhoto($userId, $newFileName);

        $_SESSION['flash_success'] = "Photo mise à jour.";
        header('Location: /profile');
        exit;
    }

    public function deletePhoto()
    {
        $userId = AuthManager::id();
        $user = User::find($userId);

        if (!empty($user['photo'])) {

        $filePath = __DIR__ . '/../..public/uploads./' . $user['photo'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        User::updatePhoto($userId, null);
        }

        $_SESSION['flash_success'] = "Photo supprimée.";
        header('Location: /profile');
        exit;
    }
}

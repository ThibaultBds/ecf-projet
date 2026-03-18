<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;

class UserController extends BaseController
{
    public function profile()
    {
        $userId   = AuthManager::id();
        $userRepo = new UserRepository();

        $userData = $userRepo->findById($userId);
        $myTrips  = $userRepo->recentTrips($userId, 10);

        $error   = $_SESSION['flash_error'] ?? '';
        $success = $_SESSION['flash_success'] ?? '';
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $this->render('user/profile', [
            'title'    => 'Mon Profil - EcoRide',
            'userData' => $userData,
            'myTrips'  => $myTrips,
            'error'    => $error,
            'success'  => $success,
        ]);
    }

    public function update()
    {
        $userId   = AuthManager::id();
        $userRepo = new UserRepository();
        $type     = $_POST['user_type'] ?? '';

        $isDriver    = false;
        $isPassenger = false;

        if ($type === 'chauffeur') {
            $isDriver = true;
        } elseif ($type === 'passager') {
            $isPassenger = true;
        } elseif ($type === 'les_deux') {
            $isDriver    = true;
            $isPassenger = true;
        } else {
            $_SESSION['flash_error'] = 'Type invalide.';
            header('Location: /profile');
            exit;
        }

        $userRepo->update($userId, [
            'is_driver'    => $isDriver ? 1 : 0,
            'is_passenger' => $isPassenger ? 1 : 0,
        ]);

        $_SESSION['user']['is_driver']    = $isDriver;
        $_SESSION['user']['is_passenger'] = $isPassenger;

        $_SESSION['flash_success'] = 'Profil mis à jour avec succès !';

        if ($isDriver) {
            $vehicleRepo = new VehicleRepository();
            if (empty($vehicleRepo->byUser($userId))) {
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
        $userId   = AuthManager::id();
        $userRepo = new UserRepository();

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
            $_SESSION['flash_error'] = "Erreur upload.";
            header('Location: /profile');
            exit;
        }

        $file     = $_FILES['photo'];
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realType = $finfo->file($file['tmp_name']);

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if (!array_key_exists($realType, $allowedTypes)) {
            $_SESSION['flash_error'] = "Format invalide (jpg ou png uniquement).";
            header('Location: /profile');
            exit;
        }

        $extension   = $allowedTypes[$realType];
        $newFileName = 'user_' . $userId . '_' . time() . '.' . $extension;
        $uploadDir   = __DIR__ . '/../../public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $newFileName)) {
            $_SESSION['flash_error'] = "Erreur sauvegarde fichier.";
            header('Location: /profile');
            exit;
        }

        $userRepo->updatePhoto($userId, $newFileName);

        $_SESSION['flash_success'] = "Photo mise à jour.";
        header('Location: /profile');
        exit;
    }

    public function deletePhoto()
    {
        $userId   = AuthManager::id();
        $userRepo = new UserRepository();
        $user     = $userRepo->findById($userId);

        if ($user && !empty($user->photo)) {
            $filePath = __DIR__ . '/../../public/uploads/' . $user->photo;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $userRepo->updatePhoto($userId, null);
        }

        $_SESSION['flash_success'] = "Photo supprimée.";
        header('Location: /profile');
        exit;
    }
}

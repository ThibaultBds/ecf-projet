<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends BaseController
{
    public function profile()
    {
        $userId      = AuthManager::id();
        $userRepo    = new UserRepository();
        $userService = new UserService($userRepo);

        $userData = $userRepo->findById($userId);
        $myTrips  = $userService->recentTrips($userId, 10);

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
        $userId = AuthManager::id();
        $type   = $_POST['user_type'] ?? '';

        $result = (new UserService())->updateUserType($userId, $type);

        if (!($result['success'] ?? false)) {
            $_SESSION['flash_error'] = $result['message'] ?? 'Type invalide.';
            header('Location: /profile');
            exit;
        }

        $_SESSION['user']['is_driver'] = $result['is_driver'];
        $_SESSION['user']['is_passenger'] = $result['is_passenger'];
        $_SESSION['flash_success'] = $result['success_message'];

        if ($result['needs_vehicle']) {
            $_SESSION['flash_success'] = 'Profil mis a jour ! Veuillez maintenant ajouter un vehicule.';
            header('Location: /driver/vehicles');
            exit;
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

        $_SESSION['flash_success'] = "Photo mise a jour.";
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

        $_SESSION['flash_success'] = "Photo supprimee.";
        header('Location: /profile');
        exit;
    }
}

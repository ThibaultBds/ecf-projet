<?php

namespace App\Controllers;

use App\Repositories\AdminRepository;
use App\Repositories\ContactRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;

class AdminController extends BaseController
{
    public function index()
    {
        $adminRepo   = new AdminRepository();
        $contactRepo = new ContactRepository();

        $stats         = $adminRepo->stats();
        $tripsPerDay   = $adminRepo->tripsPerDay();
        $creditsPerDay = $adminRepo->creditsPerDay();
        $users         = $adminRepo->recentUsers();

        try {
            $contactMessages = $contactRepo->findAll();
        } catch (\Throwable $e) {
            $contactMessages = [];
        }

        $this->render('admin/index', [
            'title'           => 'Administration - EcoRide',
            'stats'           => $stats,
            'users'           => $users,
            'tripsPerDay'     => $tripsPerDay,
            'creditsPerDay'   => $creditsPerDay,
            'contactMessages' => $contactMessages,
            'success'         => $_SESSION['flash_success'] ?? '',
            'error'           => $_SESSION['flash_error'] ?? '',
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function trips()
    {
        $trips = (new TripRepository())->scheduledTrips();

        $this->render('admin/trips', [
            'title' => 'Trajets planifies - Admin',
            'trips' => $trips,
        ]);
    }

    public function suspendUser()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            (new UserRepository())->update($userId, ['suspended' => 1]);
            $_SESSION['flash_success'] = 'Utilisateur suspendu.';
        }
        header('Location: /admin');
        exit;
    }

    public function activateUser()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            (new UserRepository())->update($userId, ['suspended' => 0]);
            $_SESSION['flash_success'] = 'Utilisateur réactivé.';
        }
        header('Location: /admin');
        exit;
    }

    public function markMessageRead()
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        if ($id > 0) {
            (new ContactRepository())->markAsRead($id);
        }
        header('Location: /admin#messages');
        exit;
    }

    public function addCredits()
    {
        $userId  = (int) ($_POST['user_id'] ?? 0);
        $credits = (int) ($_POST['credits'] ?? 0);

        if ($userId > 0 && $credits > 0) {
            (new AdminRepository())->addCreditsToUser($userId, $credits);
            $_SESSION['flash_success'] = "{$credits} credit(s) ajoute(s).";
        }

        header('Location: /admin');
        exit;
    }

    public function createEmployee()
    {
        $userRepo = new UserRepository();
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $role     = $_POST['role'] ?? 'employe';

        if (empty($email) || empty($password) || empty($username)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
            header('Location: /admin');
            exit;
        }

        if (!in_array($role, ['employe', 'user'], true)) {
            $_SESSION['flash_error'] = 'Role invalide.';
            header('Location: /admin');
            exit;
        }

        if ($userRepo->exists($email, $username)) {
            $_SESSION['flash_error'] = 'Email ou pseudo deja utilise.';
            header('Location: /admin');
            exit;
        }

        $userRepo->create([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'credits'  => 0,
            'role'     => $role,
        ]);

        $_SESSION['flash_success'] = "Compte {$role} créé avec succès !";
        header('Location: /admin');
        exit;
    }
}

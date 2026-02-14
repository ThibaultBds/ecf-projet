<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\BaseModel;

class AdminController extends BaseController
{
    public function index()
    {
        // Stats
        $stats = [
            'users' => User::count(),
            'trips' => (int) BaseModel::query("SELECT COUNT(*) as total FROM trips WHERE status = 'scheduled'")->fetch()['total'],
            'pending_reviews' => (int) BaseModel::query("SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'")->fetch()['total'],
            'platform_credits' => (int) BaseModel::query("SELECT COALESCE(SUM(credits), 0) as total FROM users")->fetch()['total']
        ];

        // Derniers utilisateurs
        $users = BaseModel::query(
            "SELECT * FROM users ORDER BY user_id DESC LIMIT 20"
        )->fetchAll();

        $this->render('admin/index', [
            'title' => 'Administration - EcoRide',
            'stats' => $stats,
            'users' => $users,
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? ''
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function suspendUser()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            User::update($userId, ['suspended' => 1]);
            $_SESSION['flash_success'] = 'Utilisateur suspendu.';
        }

        header('Location: /admin');
        exit;
    }

    public function activateUser()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            User::update($userId, ['suspended' => 0]);
            $_SESSION['flash_success'] = 'Utilisateur réactivé.';
        }

        header('Location: /admin');
        exit;
    }

    public function createEmployee()
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'employe';

        if (empty($email) || empty($password) || empty($username)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
            header('Location: /admin');
            exit;
        }

        if (!in_array($role, ['employe', 'admin'])) {
            $_SESSION['flash_error'] = 'Rôle invalide.';
            header('Location: /admin');
            exit;
        }

        if (User::exists($email, $username)) {
            $_SESSION['flash_error'] = 'Email ou pseudo déjà utilisé.';
            header('Location: /admin');
            exit;
        }

        User::create([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'credits' => 0,
            'role' => $role
        ]);

        $_SESSION['flash_success'] = "Compte $role créé avec succès !";
        header('Location: /admin');
        exit;
    }
}

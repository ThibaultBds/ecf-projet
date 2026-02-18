<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\BaseModel;

class AdminController extends BaseController
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'trips' => (int) BaseModel::query("SELECT COUNT(*) as total FROM trips WHERE status = 'scheduled'")->fetch()['total'],
            'pending_reviews' => (int) BaseModel::query("SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'")->fetch()['total'],
            'platform_credits' => (int) BaseModel::query("SELECT COALESCE(SUM(amount), 0) as total FROM credit_logs WHERE type = 'platform_fee'")->fetch()['total']
        ];

        $tripsPerDay = BaseModel::query(
            "SELECT DATE(departure_datetime) AS jour, COUNT(*) AS total
             FROM trips
             WHERE departure_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY jour ORDER BY jour"
        )->fetchAll();

        $creditsPerDay = BaseModel::query(
            "SELECT DATE(created_at) AS jour, SUM(amount) AS total
             FROM credit_logs
             WHERE type = 'platform_fee' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY jour ORDER BY jour"
        )->fetchAll();

        $users = BaseModel::query(
            "SELECT * FROM users ORDER BY user_id DESC LIMIT 20"
        )->fetchAll();

        try {
            $contactMessages = BaseModel::query(
                "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $contactMessages = [];
        }

        $this->render('admin/index', [
            'title' => 'Administration - EcoRide',
            'stats' => $stats,
            'users' => $users,
            'tripsPerDay' => $tripsPerDay,
            'creditsPerDay' => $creditsPerDay,
            'contactMessages' => $contactMessages,
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? ''
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function trips()
    {
        $trips = BaseModel::query(
            "SELECT t.*, u.username AS chauffeur
             FROM trips t
             JOIN users u ON t.chauffeur_id = u.user_id
             WHERE t.status = 'scheduled'
             ORDER BY t.departure_datetime ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin/trips', [
            'title' => 'Trajets planifiés - Admin',
            'trips' => $trips,
        ]);
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

    public function markMessageRead()
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        if ($id > 0) {
            BaseModel::query("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$id]);
        }
        header('Location: /admin#messages');
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

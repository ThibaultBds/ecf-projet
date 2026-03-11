<?php

namespace App\Controllers;

use App\Models\BaseModel;
use App\Models\User;

class AdminController extends BaseController
{
    public function index()
    {
        $userModel = new User();

        $stats = [
            'users' => $userModel->count(),
            'trips' => (int) BaseModel::query("SELECT COUNT(*) AS total FROM trips WHERE status = 'scheduled'")->fetch()['total'],
            'pending_reviews' => (int) BaseModel::query("SELECT COUNT(*) AS total FROM reviews WHERE status = 'pending'")->fetch()['total'],
            'platform_credits' => (int) BaseModel::query("SELECT COALESCE(-SUM(amount), 0) AS total FROM credit_logs WHERE type = 'platform_fee'")->fetch()['total'],
        ];

        $tripsPerDay = BaseModel::query(
            "SELECT DATE(departure_datetime) AS jour, COUNT(*) AS total
             FROM trips
             WHERE departure_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY jour ORDER BY jour"
        )->fetchAll();

        $creditsPerDay = BaseModel::query(
            "SELECT DATE(created_at) AS jour, -SUM(amount) AS total
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
            'error' => $_SESSION['flash_error'] ?? '',
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
            'title' => 'Trajets planifies - Admin',
            'trips' => $trips,
        ]);
    }

    public function suspendUser()
    {
        $userModel = new User();
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            $userModel->update($userId, ['suspended' => 1]);
            $_SESSION['flash_success'] = 'Utilisateur suspendu.';
        }

        header('Location: /admin');
        exit;
    }

    public function activateUser()
    {
        $userModel = new User();
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId > 0) {
            $userModel->update($userId, ['suspended' => 0]);
            $_SESSION['flash_success'] = 'Utilisateur reactive.';
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

    public function addCredits()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $credits = (int) ($_POST['credits'] ?? 0);

        if ($userId > 0 && $credits > 0) {
            BaseModel::query(
                "UPDATE users SET credits = credits + ? WHERE user_id = ?",
                [$credits, $userId]
            );
            $_SESSION['flash_success'] = "{$credits} credit(s) ajoute(s).";
        }

        header('Location: /admin');
        exit;
    }

    public function createEmployee()
    {
        $userModel = new User();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'employe';

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

        if ($userModel->exists($email, $username)) {
            $_SESSION['flash_error'] = 'Email ou pseudo deja utilise.';
            header('Location: /admin');
            exit;
        }

        $userModel->create([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'credits' => 0,
            'role' => $role,
        ]);

        $_SESSION['flash_success'] = "Compte {$role} cree avec succes !";
        header('Location: /admin');
        exit;
    }
}

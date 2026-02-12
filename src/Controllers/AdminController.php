<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/BaseModel.php';
require_once __DIR__ . '/../Core/Auth/AuthManager.php';

class AdminController extends BaseController
{
    public function index()
    {
        // Stats
        $stats = [
            'users' => User::count("status = 'actif'"),
            'trips' => BaseModel::query("SELECT COUNT(*) as total FROM trips WHERE status = 'planifie'")->fetch()['total'],
            'reports' => BaseModel::query("SELECT COUNT(*) as total FROM reports WHERE status IN ('ouvert','en_cours')")->fetch()['total'] ?? 0,
            'platform_credits' => BaseModel::query("SELECT COALESCE(SUM(credits), 0) as total FROM users")->fetch()['total']
        ];

        // Derniers utilisateurs
        $users = BaseModel::query(
            "SELECT * FROM users ORDER BY id DESC LIMIT 20"
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
        if ($userId) {
            User::update($userId, ['status' => 'suspendu']);
            $_SESSION['flash_success'] = 'Utilisateur suspendu.';
        }
        header('Location: /admin');
        exit;
    }

    public function activateUser()
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId) {
            User::update($userId, ['status' => 'actif']);
            $_SESSION['flash_success'] = 'Utilisateur réactivé.';
        }
        header('Location: /admin');
        exit;
    }

    public function createEmployee()
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $pseudo = trim($_POST['pseudo'] ?? '');
        $role = $_POST['role'] ?? 'Moderateur';

        if (empty($email) || empty($password) || empty($pseudo)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
            header('Location: /admin');
            exit;
        }

        if (!in_array($role, ['Moderateur', 'Administrateur'])) {
            $_SESSION['flash_error'] = 'Rôle invalide.';
            header('Location: /admin');
            exit;
        }

        if (User::exists($email, $pseudo)) {
            $_SESSION['flash_error'] = 'Email ou pseudo déjà utilisé.';
            header('Location: /admin');
            exit;
        }

        User::create([
            'pseudo' => $pseudo,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'credits' => 0,
            'role' => $role,
            'status' => 'actif',
            'user_type' => 'passager'
        ]);

        $_SESSION['flash_success'] = "Employé ($role) créé avec succès !";
        header('Location: /admin');
        exit;
    }
}

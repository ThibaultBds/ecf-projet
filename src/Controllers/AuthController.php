<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Models\User;
use Exception;

class AuthController extends BaseController
{
    public function showLogin()
    {
        if (!empty($_GET['redirect'])) {
            $_SESSION['intended_url'] = $_GET['redirect'];
        }
        $this->render('auth/login', [
            'title' => 'Connexion - EcoRide',
            'error' => '',
            'email' => ''
        ]);
    }

    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => 'Veuillez remplir tous les champs.',
                'email' => $email
            ]);
        }

        $result = AuthManager::login($email, $password);

        if (!$result['success']) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => $result['message'],
                'email' => $email
            ]);
        }

        $redirectUrl = AuthManager::intendedUrl(AuthManager::redirectUrlByRole());
        session_write_close();
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function showRegister()
    {
        if (!empty($_GET['redirect'])) {
            $_SESSION['intended_url'] = $_GET['redirect'];
        }
        $this->render('auth/register', [
            'title' => 'Inscription - EcoRide',
            'error' => '',
            'success' => '',
            'old' => []
        ]);
    }

    public function register()
    {
        $username = trim($_POST['username'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $old = ['username' => $username, 'email' => $email];

        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Veuillez remplir tous les champs.',
                'success' => '',
                'old' => $old
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Adresse email invalide.',
                'success' => '',
                'old' => $old
            ]);
        }

        if (strlen($password) < 8) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'success' => '',
                'old' => $old
            ]);
        }

        if ($password !== $passwordConfirm) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Les mots de passe ne correspondent pas.',
                'success' => '',
                'old' => $old
            ]);
        }

        if (User::exists($email, $username)) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Cet email ou ce pseudo est déjà utilisé.',
                'success' => '',
                'old' => $old
            ]);
        }

        try {
            User::create([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'credits' => 20,
            'role' => 'user',
            'is_driver' => 0,
            'is_passenger' => 1
]);


            $_SESSION['flash_success'] = 'Compte créé avec succès ! Connectez-vous.';
            header('Location: /login');
            exit;
        } catch (Exception $e) {
            error_log("Erreur inscription : " . $e->getMessage());
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Une erreur est survenue lors de l\'inscription.',
                'success' => '',
                'old' => $old
            ]);
        }
    }

    public function logout()
    {
        $csrfToken = $_SESSION['csrf_token'] ?? null;
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        session_start();
        $_SESSION['csrf_token'] = $csrfToken ?? bin2hex(random_bytes(32));

        header('Location: /');
        exit;
    }
}

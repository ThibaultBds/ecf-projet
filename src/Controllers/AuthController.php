<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Services\UserService;
use Exception;

class AuthController extends BaseController
{
    public function showLogin()
    {
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect !== '' && preg_match('#^/[^/]#', $redirect)) {
            $_SESSION['intended_url'] = $redirect;
        }

        $this->render('auth/login', [
            'title' => 'Connexion - EcoRide',
            'error' => '',
            'email' => '',
        ]);
    }

    public function login()
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => 'Veuillez remplir tous les champs.',
                'email' => $email,
            ]);
        }

        $result = AuthManager::login($email, $password);
        if (!$result['success']) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => $result['message'],
                'email' => $email,
            ]);
        }

        $redirectUrl = AuthManager::intendedUrl(AuthManager::redirectUrlByRole());
        session_write_close();
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function showRegister()
    {
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect !== '' && preg_match('#^/[^/]#', $redirect)) {
            $_SESSION['intended_url'] = $redirect;
        }

        $this->render('auth/register', [
            'title'   => 'Inscription - EcoRide',
            'error'   => '',
            'success' => '',
            'old'     => [],
        ]);
    }

    public function register()
    {
        try {
            $result = (new UserService())->register($_POST);

            if (!($result['success'] ?? false)) {
                return $this->render('auth/register', [
                    'title' => 'Inscription - EcoRide',
                    'error' => $result['message'] ?? 'Erreur lors de l inscription.',
                    'success' => '',
                    'old' => $result['old'] ?? [],
                ]);
            }

            $_SESSION['flash_success'] = 'Compte cree avec succes ! Connectez-vous.';
            $redirectAfter = $_SESSION['intended_url'] ?? null;
            session_write_close();
            header('Location: /login' . ($redirectAfter ? '?redirect=' . urlencode($redirectAfter) : ''));
            exit;
        } catch (Exception $e) {
            error_log('Erreur inscription : ' . $e->getMessage());

            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Une erreur est survenue lors de l inscription.',
                'success' => '',
                'old' => [
                    'username' => trim($_POST['username'] ?? ''),
                    'email' => strtolower(trim($_POST['email'] ?? '')),
                ],
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

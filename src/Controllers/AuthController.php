<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Core/Auth/AuthManager.php';
require_once __DIR__ . '/../Models/User.php';

class AuthController extends BaseController
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLogin()
    {
        $this->render('auth/login', [
            'title' => 'Connexion - EcoRide',
            'error' => '',
            'email' => ''
        ]);
    }

    /**
     * Traiter la connexion
     */
    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation basique
        if (empty($email) || empty($password)) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => 'Veuillez remplir tous les champs.',
                'email' => $email
            ]);
        }

        // Tenter la connexion
        $result = AuthManager::login($email, $password);

        if (!$result['success']) {
            return $this->render('auth/login', [
                'title' => 'Connexion - EcoRide',
                'error' => $result['message'],
                'email' => $email
            ]);
        }

        // Redirection selon le rôle
        $redirectUrl = AuthManager::intendedUrl(AuthManager::redirectUrlByRole());
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Afficher le formulaire d'inscription
     */
    public function showRegister()
    {
        $this->render('auth/register', [
            'title' => 'Inscription - EcoRide',
            'error' => '',
            'success' => '',
            'old' => []
        ]);
    }

    /**
     * Traiter l'inscription
     */
    public function register()
    {
        $pseudo = trim($_POST['pseudo'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $old = ['pseudo' => $pseudo, 'email' => $email];

        // Validation
        if (empty($pseudo) || empty($email) || empty($password) || empty($passwordConfirm)) {
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

        // Vérifier si l'email ou pseudo existe déjà
        if (User::exists($email, $pseudo)) {
            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => 'Cet email ou ce pseudo est déjà utilisé.',
                'success' => '',
                'old' => $old
            ]);
        }

        // Créer l'utilisateur
        try {
            User::create([
                'pseudo' => $pseudo,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'credits' => 20,
                'role' => 'Utilisateur',
                'status' => 'actif',
                'user_type' => 'passager'
            ]);

            return $this->render('auth/register', [
                'title' => 'Inscription - EcoRide',
                'error' => '',
                'success' => 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.',
                'old' => []
            ]);
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

    /**
     * Déconnexion
     */
    public function logout()
    {
        AuthManager::logout();

        // Redémarrer une session propre
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: /');
        exit;
    }
}

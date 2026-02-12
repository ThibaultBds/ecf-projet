<?php

require_once __DIR__ . '/../Database.php';

/**
 * Gestionnaire d'authentification centralisé
 * Gère login, logout, vérification de session et rate limiting
 */
class AuthManager
{
    private static $maxLoginAttempts = 5;
    private static $lockoutDuration = 900; // 15 minutes

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function check()
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    /**
     * Obtenir l'ID de l'utilisateur connecté
     */
    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Obtenir les données de l'utilisateur connecté
     */
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($role)
    {
        if (!self::check()) {
            return false;
        }

        $userRole = strtolower($_SESSION['user']['role'] ?? '');
        $requiredRole = strtolower($role);

        // Admin a accès à tout
        if ($userRole === 'administrateur') {
            return true;
        }

        return $userRole === $requiredRole;
    }

    /**
     * Tenter une connexion
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function login($email, $password)
    {
        $pdo = Database::getInstance()->getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Vérifier le rate limiting
        if (!self::checkLoginAttempts($pdo, $ip)) {
            return [
                'success' => false,
                'message' => 'Trop de tentatives de connexion. Réessayez dans 15 minutes.'
            ];
        }

        // Valider l'email
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Adresse email invalide.'];
        }

        // Rechercher l'utilisateur
        $stmt = $pdo->prepare(
            "SELECT id, email, password, pseudo, role, credits, status, user_type
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Vérifier le mot de passe
        if (!$user || !password_verify($password, $user['password'])) {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        // Vérifier si le compte est actif
        if ($user['status'] !== 'actif') {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Votre compte a été suspendu.'];
        }

        // Connexion réussie
        self::logAttempt($pdo, $ip, $email, true);
        self::clearAttempts($pdo, $ip);

        // Créer la session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'pseudo' => $user['pseudo'],
            'role' => $user['role'],
            'type' => $user['user_type'] ?? 'passager',
            'credits' => (int) $user['credits']
        ];

        // Générer un token CSRF
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return ['success' => true, 'message' => 'Connexion réussie.'];
    }

    /**
     * Déconnecter l'utilisateur
     */
    public static function logout()
    {
        // Logger la déconnexion
        if (self::check()) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare(
                    "INSERT INTO activity_logs (user_id, action, details, ip_address)
                     VALUES (?, 'logout', 'Déconnexion', ?)"
                );
                $stmt->execute([
                    $_SESSION['user']['id'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            } catch (Exception $e) {
                // Ne pas bloquer la déconnexion en cas d'erreur de log
            }
        }

        // Détruire la session
        session_unset();
        session_destroy();
    }

    /**
     * Obtenir l'URL de redirection selon le rôle
     */
    public static function redirectUrlByRole()
    {
        $role = strtolower($_SESSION['user']['role'] ?? 'utilisateur');

        switch ($role) {
            case 'administrateur':
                return '/admin';
            case 'moderateur':
                return '/moderator';
            default:
                return '/profile';
        }
    }

    /**
     * Récupérer l'URL initialement demandée (avant redirection vers login)
     */
    public static function intendedUrl($default = '/profile')
    {
        $url = $_SESSION['intended_url'] ?? $default;
        unset($_SESSION['intended_url']);
        return $url;
    }

    /**
     * Rafraîchir les crédits depuis la BDD
     */
    public static function refreshCredits()
    {
        if (!self::check()) {
            return;
        }

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt->execute([self::id()]);
        $result = $stmt->fetch();

        if ($result) {
            $_SESSION['user']['credits'] = (int) $result['credits'];
        }
    }

    // -------------------------------------------------------
    // Rate Limiting
    // -------------------------------------------------------

    private static function checkLoginAttempts($pdo, $ip)
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as attempts FROM login_attempts
             WHERE ip_address = ? AND success = FALSE
             AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->execute([$ip, self::$lockoutDuration]);
        $result = $stmt->fetch();

        return $result['attempts'] < self::$maxLoginAttempts;
    }

    private static function logAttempt($pdo, $ip, $email, $success)
    {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO login_attempts (ip_address, email, success, user_agent)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $ip,
                $email,
                $success ? 1 : 0,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Table login_attempts peut ne pas exister - ignorer silencieusement
        }
    }

    private static function clearAttempts($pdo, $ip)
    {
        try {
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip]);
        } catch (Exception $e) {
            // Ignorer
        }
    }
}

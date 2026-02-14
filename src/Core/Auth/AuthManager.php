<?php

namespace App\Core\Auth;

use App\Core\Database;
use PDO;
use Exception;

class AuthManager
{
    private static $maxLoginAttempts = 5;
    private static $lockoutDuration = 900; // 15 minutes

    // -------------------------------------------------------
    // Vérification session
    // -------------------------------------------------------

    public static function check()
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function hasRole($role)
    {
        if (!self::check()) {
            return false;
        }

        $userRole = strtolower($_SESSION['user']['role']);
        $requiredRole = strtolower($role);

        // Admin a accès à tout
        if ($userRole === 'admin') {
            return true;
        }

        return $userRole === $requiredRole;
    }

    // -------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------

    public static function login($email, $password)
    {
        $pdo = Database::getInstance()->getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!self::checkLoginAttempts($pdo, $ip)) {
            return [
                'success' => false,
                'message' => 'Trop de tentatives. Réessayez dans 15 minutes.'
            ];
        }

        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Email invalide.'];
        }

        $stmt = $pdo->prepare(
            "SELECT user_id, username, email, password, role, credits
             FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        // Connexion réussie
        self::logAttempt($pdo, $ip, $email, true);
        self::clearAttempts($pdo, $ip);

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'credits' => (int) $user['credits']
        ];

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return ['success' => true, 'message' => 'Connexion réussie.'];
    }

    // -------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------

    public static function logout()
    {
        session_unset();
        session_destroy();
    }

    // -------------------------------------------------------
    // Redirection selon rôle
    // -------------------------------------------------------

    public static function redirectUrlByRole()
    {
        if (!self::check()) {
            return '/login';
        }

        $role = $_SESSION['user']['role'];

        switch ($role) {
            case 'admin':
                return '/admin';
            case 'employe':
                return '/moderator';
            case 'chauffeur':
                return '/driver';
            default:
                return '/profile';
        }
    }

    public static function intendedUrl($default = '/profile')
    {
        $url = $_SESSION['intended_url'] ?? $default;
        unset($_SESSION['intended_url']);
        return $url;
    }

    // -------------------------------------------------------
    // Rafraîchir crédits
    // -------------------------------------------------------

    public static function refreshCredits()
    {
        if (!self::check()) {
            return;
        }

        $pdo = Database::getInstance()->getConnection();

        $stmt = $pdo->prepare(
            "SELECT credits FROM users WHERE user_id = ?"
        );

        $stmt->execute([self::id()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $_SESSION['user']['credits'] = (int) $result['credits'];
        }
    }

    // -------------------------------------------------------
    // RATE LIMITING
    // -------------------------------------------------------

    private static function checkLoginAttempts($pdo, $ip)
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as attempts FROM login_attempts
             WHERE ip_address = ?
             AND success = 0
             AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );

        $stmt->execute([$ip, self::$lockoutDuration]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['attempts'] < self::$maxLoginAttempts;
    }

    private static function logAttempt($pdo, $ip, $email, $success)
    {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO login_attempts (ip_address, email, success)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $ip,
                $email,
                $success ? 1 : 0
            ]);
        } catch (Exception $e) {
            // Ignore si table absente
        }
    }

    private static function clearAttempts($pdo, $ip)
    {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM login_attempts WHERE ip_address = ?"
            );
            $stmt->execute([$ip]);
        } catch (Exception $e) {
            // Ignore
        }
    }
}

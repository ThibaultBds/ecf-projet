<?php

namespace App\Core\Auth;

use App\Core\Database;
use PDO;
use Exception;

class AuthManager
{
    private static $maxLoginAttempts = 5;
    private static $lockoutDuration = 900; // 15 minutes

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

        // Admin has access to everything
        if ($userRole === 'admin') {
            return true;
        }

        return $userRole === $requiredRole;
    }

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
            "SELECT user_id, username, email, password, role, credits, is_driver, is_passenger, suspended
             FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            self::logAttempt($pdo, $ip, $email, false);
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        if (!empty($user['suspended'])) {
            return ['success' => false, 'message' => 'Votre compte a été suspendu. Contactez l\'administration.'];
        }

        self::logAttempt($pdo, $ip, $email, true);
        self::clearAttempts($pdo, $ip);

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'credits' => (int) $user['credits'],
            'is_driver' => (bool) $user['is_driver'],
            'is_passenger' => (bool) $user['is_passenger']
        ];

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return ['success' => true, 'message' => 'Connexion réussie.'];
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
    }

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
            // Ignore if table doesn't exist
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

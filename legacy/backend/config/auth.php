<?php
/**
 * Classe de gestion de base de données pour EcoRide
 */
class Database {
    private $connection;
    private $host = 'localhost';
    private $dbname = 'ecoride';
    private $username = 'root';
    private $password = '';

    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            die("Erreur de connexion à la base de données");
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function initDatabase() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            user_type ENUM('Utilisateur', 'Moderateur', 'Administrateur') DEFAULT 'Utilisateur',
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            email_verified BOOLEAN DEFAULT FALSE,
            verification_token VARCHAR(255),
            reset_token VARCHAR(255),
            reset_token_expires TIMESTAMP NULL
        );

        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            email VARCHAR(255),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE,
            user_agent TEXT
        );

        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        ";

        try {
            $this->connection->exec($sql);

            // Insérer des utilisateurs de test s'ils n'existent pas
            $this->createDefaultUsers();

        } catch (PDOException $e) {
            error_log("Erreur lors de l'initialisation de la base de données: " . $e->getMessage());
        }
    }

    private function createDefaultUsers() {
        $defaultUsers = [
            [
                'email' => 'admin@ecoride.fr',
                'password' => 'admin123',
                'type' => 'Administrateur',
                'first_name' => 'Admin',
                'last_name' => 'EcoRide'
            ],
            [
                'email' => 'modo@ecoride.fr',
                'password' => 'modo123',
                'type' => 'Moderateur',
                'first_name' => 'Modérateur',
                'last_name' => 'EcoRide'
            ],
            [
                'email' => 'user@ecoride.fr',
                'password' => 'test123',
                'type' => 'Utilisateur',
                'first_name' => 'Utilisateur',
                'last_name' => 'Test'
            ]
        ];

        foreach ($defaultUsers as $user) {
            $stmt = $this->connection->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$user['email']]);

            if (!$stmt->fetch()) {
                $passwordHash = password_hash($user['password'], PASSWORD_ARGON2ID);

                $insertStmt = $this->connection->prepare("
                    INSERT INTO users (email, password_hash, user_type, first_name, last_name, email_verified)
                    VALUES (?, ?, ?, ?, ?, TRUE)
                ");

                $insertStmt->execute([
                    $user['email'],
                    $passwordHash,
                    $user['type'],
                    $user['first_name'],
                    $user['last_name']
                ]);
            }
        }
    }
}

/**
 * Classe de gestion de l'authentification
 */
class AuthManager {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function login($email, $password, $rememberMe = false) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Validation préliminaire
        $validationResult = $this->validateLoginRequest($ip, $email, $userAgent);
        if (!$validationResult['success']) {
            return $validationResult;
        }

        // Rechercher et vérifier l'utilisateur
        $authResult = $this->authenticateUser($email, $password, $ip, $userAgent);
        if (!$authResult['success']) {
            return $authResult;
        }

        // Connexion réussie - créer la session
        return $this->createSuccessfulSession($authResult['user'], $ip, $userAgent, $rememberMe);
    }

    private function validateLoginRequest($ip, $email, $userAgent) {
        // Vérifier les tentatives de connexion
        if (!$this->checkLoginAttempts($ip)) {
            $this->logLoginAttempt($ip, $email, false, $userAgent);
            return ['success' => false, 'message' => 'Trop de tentatives de connexion. Réessayez dans 15 minutes.'];
        }

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logLoginAttempt($ip, $email, false, $userAgent);
            return ['success' => false, 'message' => 'Adresse email invalide.'];
        }

        return ['success' => true];
    }

    private function authenticateUser($email, $password, $ip, $userAgent) {
        // Rechercher l'utilisateur
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logLoginAttempt($ip, $email, false, $userAgent);
            return ['success' => false, 'message' => 'Identifiants incorrects.'];
        }

        return ['success' => true, 'user' => $user];
    }

    private function createSuccessfulSession($user, $ip, $userAgent, $rememberMe) {
        // Connexion réussie
        $this->logLoginAttempt($ip, $user['email'], true, $userAgent);
        $this->clearLoginAttempts($ip);

        // Créer la session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'type' => $user['user_type'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ];

        // Enregistrer la session en base
        $this->createUserSession($user['id'], $ip, $userAgent, $rememberMe);

        return ['success' => true, 'message' => 'Connexion réussie.', 'user' => $_SESSION['user']];
    }

    public function logout() {
        if (isset($_SESSION['user']['id'])) {
            // Désactiver la session en base
            $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = FALSE WHERE user_id = ? AND session_id = ?");
            $stmt->execute([$_SESSION['user']['id'], session_id()]);
        }

        // Détruire la session
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    private function checkLoginAttempts($ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE ip_address = ?
            AND success = FALSE
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $this->lockoutDuration]);
        $result = $stmt->fetch();

        return $result['attempts'] < $this->maxLoginAttempts;
    }

    private function logLoginAttempt($ip, $email, $success, $userAgent) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, email, success, user_agent)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$ip, $email, $success, $userAgent]);
    }

    private function clearLoginAttempts($ip) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
    }

    private function createUserSession($userId, $ip, $userAgent, $rememberMe) {
        $sessionId = session_id();
        $expiresAt = $rememberMe
            ? date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)) // 30 jours
            : date('Y-m-d H:i:s', time() + (2 * 60 * 60)); // 2 heures

        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $sessionId, $ip, $userAgent, $expiresAt]);
    }

    public function isValidSession() {
        if (!isset($_SESSION['user']['id'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM user_sessions
            WHERE user_id = ?
            AND session_id = ?
            AND is_active = TRUE
            AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['user']['id'], session_id()]);

        return $stmt->fetch() !== false;
    }
}

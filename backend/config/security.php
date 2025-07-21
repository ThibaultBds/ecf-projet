<?php
/**
 * Configuration de sécurité pour EcoRide
 */

// Configuration de session sécurisée avec vérifications
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    // Activer session.cookie_secure seulement en HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.use_strict_mode', 1);
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_samesite', 'Strict');
    }
}

// Régénération de l'ID de session
function regenerateSession() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        return true;
    } catch (Exception $e) {
        error_log("Erreur régénération session: " . $e->getMessage());
        return false;
    }
}

// Protection CSRF
function generateCsrfToken() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                throw new RuntimeException('Impossible de démarrer la session');
            }
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    } catch (Exception $e) {
        error_log("Erreur génération CSRF: " . $e->getMessage());
        return false;
    }
}

function verifyCsrfToken($token) {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }
        return isset($_SESSION['csrf_token']) && is_string($token) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
    } catch (Exception $e) {
        error_log("Erreur vérification CSRF: " . $e->getMessage());
        return false;
    }
}

// Nettoyage des entrées utilisateur
function cleanInput($data) {
    if ($data === null) {
        return '';
    }
    if (!is_string($data)) {
        $data = (string) $data;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validation email
function validateEmail($email) {
    if (!is_string($email) || empty($email)) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 254;
}

// Hachage sécurisé des mots de passe
function hashPassword($password) {
    if (!is_string($password)) {
        throw new InvalidArgumentException('Le mot de passe doit être une chaîne');
    }
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Mot de passe trop court (minimum 8 caractères)');
    }
    if (strlen($password) > 4096) {
        throw new InvalidArgumentException('Mot de passe trop long');
    }
    
    // Vérifier si Argon2ID est disponible
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    } else {
        // Fallback vers PASSWORD_DEFAULT
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

function verifyPassword($password, $hash) {
    if (!is_string($password) || !is_string($hash)) {
        return false;
    }
    return password_verify($password, $hash);
}

// Limitation des tentatives de connexion
function checkLoginAttempts($ip) {
    if (!is_string($ip) || empty($ip)) {
        return false;
    }
    
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes

    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }

        if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        $attempts = &$_SESSION['login_attempts'];

        // Nettoyer les anciennes tentatives
        $current_time = time();
        foreach ($attempts as $attempt_ip => $data) {
            if (!is_array($data) || !isset($data['last_attempt']) || 
                $current_time - $data['last_attempt'] > $lockout_time) {
                unset($attempts[$attempt_ip]);
            }
        }

        if (isset($attempts[$ip]) && is_array($attempts[$ip]) && 
            isset($attempts[$ip]['count']) && $attempts[$ip]['count'] >= $max_attempts) {
            if (isset($attempts[$ip]['last_attempt']) && 
                $current_time - $attempts[$ip]['last_attempt'] < $lockout_time) {
                return false; // Compte bloqué
            }
            unset($attempts[$ip]); // Réinitialiser après expiration
        }

        return true;
    } catch (Exception $e) {
        error_log("Erreur vérification tentatives: " . $e->getMessage());
        return false;
    }
}

function recordFailedLogin($ip) {
    if (!is_string($ip) || empty($ip)) {
        return false;
    }
    
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }

        if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        $attempts = &$_SESSION['login_attempts'];

        if (!isset($attempts[$ip]) || !is_array($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
        }

        $attempts[$ip]['count'] = (int)($attempts[$ip]['count'] ?? 0) + 1;
        $attempts[$ip]['last_attempt'] = time();
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur enregistrement échec: " . $e->getMessage());
        return false;
    }
}

function resetLoginAttempts($ip) {
    if (!is_string($ip) || empty($ip)) {
        return false;
    }
    
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (isset($_SESSION['login_attempts'][$ip])) {
        unset($_SESSION['login_attempts'][$ip]);
    }
    
    return true;
}

// Protection contre les attaques par déni de service
function rateLimitRequest($key, $maxRequests = 60, $timeWindow = 60) {
    if (!is_string($key) || empty($key)) {
        return false;
    }
    
    if (!is_int($maxRequests) || $maxRequests <= 0) {
        $maxRequests = 60;
    }
    
    if (!is_int($timeWindow) || $timeWindow <= 0) {
        $timeWindow = 60;
    }
    
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }
        
        if (!isset($_SESSION['rate_limits']) || !is_array($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        $windowStart = $now - $timeWindow;
        
        // Nettoyer les anciennes entrées
        foreach ($_SESSION['rate_limits'] as $k => $data) {
            if (!is_array($data) || !isset($data['timestamp']) || $data['timestamp'] < $windowStart) {
                unset($_SESSION['rate_limits'][$k]);
            }
        }
        
        // Compter les requêtes actuelles
        $currentRequests = 0;
        foreach ($_SESSION['rate_limits'] as $data) {
            if (is_array($data) && isset($data['key'], $data['timestamp']) &&
                $data['key'] === $key && $data['timestamp'] >= $windowStart) {
                $currentRequests++;
            }
        }
        
        if ($currentRequests >= $maxRequests) {
            if (!headers_sent()) {
                http_response_code(429);
                header('Content-Type: application/json');
            }
            die(json_encode(['error' => 'Trop de requêtes. Veuillez patienter.']));
        }
        
        // Enregistrer cette requête
        $_SESSION['rate_limits'][] = [
            'key' => $key,
            'timestamp' => $now
        ];
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur rate limiting: " . $e->getMessage());
        return false;
    }
}

// Fonction pour nettoyer et valider les uploads
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 2097152) {
    if (!is_array($file) || !isset($file['tmp_name'], $file['name'], $file['size'])) {
        throw new InvalidArgumentException('Fichier invalide.');
    }
    
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Fichier non uploadé correctement.');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        throw new InvalidArgumentException('Type de fichier non autorisé: ' . $extension);
    }
    
    if ($file['size'] > $maxSize) {
        throw new InvalidArgumentException('Fichier trop volumineux: ' . $file['size'] . ' bytes');
    }
    
    // Vérification MIME
    if (!function_exists('finfo_open')) {
        throw new RuntimeException('Extension fileinfo non disponible');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new RuntimeException('Impossible d\'initialiser finfo');
    }
    
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($mimeType === false) {
        throw new RuntimeException('Impossible de déterminer le type MIME');
    }
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
        throw new InvalidArgumentException('Type MIME non autorisé: ' . $mimeType);
    }
    
    return true;
}

// Headers de sécurité
function setSecurityHeaders() {
    if (headers_sent()) {
        return false;
    }
    
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // CSP (Content Security Policy)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data:; " .
           "connect-src 'self';";

    header("Content-Security-Policy: $csp");
    
    return true;
}

// Vérification de l'authentification
function requireAuth() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                if (!headers_sent()) {
                    header('Location: login.php');
                }
                exit();
            }
        }
        
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['email']) || empty($_SESSION['user']['email'])) {
            if (!headers_sent()) {
                header('Location: login.php');
            }
            exit();
        }
    } catch (Exception $e) {
        error_log("Erreur auth: " . $e->getMessage());
        if (!headers_sent()) {
            header('Location: login.php');
        }
        exit();
    }
}

// Vérification du rôle utilisateur
function requireRole($required_role) {
    if (!is_string($required_role) || empty($required_role)) {
        throw new InvalidArgumentException('Rôle requis invalide');
    }
    
    requireAuth();

    $user_role = $_SESSION['user']['type'] ?? 'Utilisateur';

    $hierarchy = [
        'Utilisateur' => 1,
        'Moderateur' => 2,
        'Administrateur' => 3
    ];

    $user_level = $hierarchy[$user_role] ?? 0;
    $required_level = $hierarchy[$required_role] ?? 99;

    if ($user_level < $required_level) {
        if (!headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }
        die('Accès refusé. Permissions insuffisantes.');
    }
}

// Journalisation des événements de sécurité
function logSecurityEvent($event, $details = '') {
    if (!is_string($event) || empty($event)) {
        return false;
    }
    
    try {
        // Assurer que la session est démarrée pour accéder aux données utilisateur
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $log_file = __DIR__ . '/../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = getRealIpAddr();
        $user = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : 'anonymous';

        // Nettoyer les données avant l'écriture
        $event = cleanInput($event);
        $details = cleanInput($details);
        
        $log_entry = "[$timestamp] IP: $ip | User: $user | Event: $event | Details: $details" . PHP_EOL;

        // Créer le dossier de logs s'il n'existe pas
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir, 0755, true)) {
                error_log("Impossible de créer le dossier de logs: $log_dir");
                return false;
            }
        }

        return file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) !== false;
        
    } catch (Exception $e) {
        error_log("Erreur lors de la journalisation: " . $e->getMessage());
        return false;
    }
}

// Fonction utilitaire pour obtenir l'IP réelle
function getRealIpAddr() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            $ip = trim($_SERVER[$key]);
            // Gérer les IPs multiples (séparées par des virgules)
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            
            // Valider l'IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            } elseif (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip; // Accepter les IPs privées en développement
            }
        }
    }
    
    return 'unknown';
}

// Fonction pour nettoyer la session
function cleanSession() {
    try {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Nettoyer les données expirées
            $current_time = time();
            
            // Nettoyer les tentatives de connexion expirées
            if (isset($_SESSION['login_attempts']) && is_array($_SESSION['login_attempts'])) {
                foreach ($_SESSION['login_attempts'] as $ip => $data) {
                    if (!is_array($data) || !isset($data['last_attempt']) || 
                        $current_time - $data['last_attempt'] > 900) {
                        unset($_SESSION['login_attempts'][$ip]);
                    }
                }
            }
            
            // Nettoyer les limites de taux expirées
            if (isset($_SESSION['rate_limits']) && is_array($_SESSION['rate_limits'])) {
                foreach ($_SESSION['rate_limits'] as $k => $data) {
                    if (!is_array($data) || !isset($data['timestamp']) || 
                        $current_time - $data['timestamp'] > 60) {
                        unset($_SESSION['rate_limits'][$k]);
                    }
                }
            }
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur nettoyage session: " . $e->getMessage());
        return false;
    }
}

// Fonction pour initialiser la sécurité
function initSecurity() {
    try {
        // Démarrer la session si nécessaire
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Définir les headers de sécurité
        setSecurityHeaders();
        
        // Nettoyer la session
        cleanSession();
        
        // Journaliser l'initialisation
        logSecurityEvent('SECURITY_INIT', 'Système de sécurité initialisé');
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'initialisation de la sécurité: " . $e->getMessage());
        return false;
    }
}
?>

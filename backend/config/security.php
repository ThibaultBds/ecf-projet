<?php
/**
 * Configuration de sécurité pour EcoRide — version corrigée (Sonar/PSR friendly)
 * - Conserve l’API existante (mêmes fonctions / signatures)
 * - Durcit les en-têtes + CSP (nonce) et clarifie l’usage (input vs affichage)
 * - Remplace les "die" bloquants par retours/exit optionnels configurables
 */

declare(strict_types=1);

// Si vous voulez garder l'ancien comportement de rateLimitRequest() qui coupe la réponse,
// définissez cette constante à true AVANT d'inclure ce fichier :
// define('ECR_RATE_LIMIT_AUTO_DIE', true);

// ============================
// 1) SESSION DURCIE (AVANT start)
// ============================
if (session_status() === PHP_SESSION_NONE) {
    // Toujours HttpOnly (évite accès JS au cookie)
    ini_set('session.cookie_httponly', '1');
    // Strict-Mode empêche réutilisation d’IDs invalides
    ini_set('session.use_strict_mode', '1');

    // SameSite & Secure (si HTTPS)
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // PHP >= 7.3 : SameSite via array
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        // Fallback (certains PHP <7.3 ignorent SameSite)
        ini_set('session.cookie_samesite', 'Strict');
        if ($secure) {
            ini_set('session.cookie_secure', '1');
        }
    }
}

// ============================
// 2) SESSION: REGEN ID
// ============================
function regenerateSession(): bool
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return session_regenerate_id(true);
    } catch (Throwable $e) {
        error_log('Erreur régénération session: ' . $e->getMessage());
        return false;
    }
}

// ============================
// 3) CSRF TOKEN
// ============================
function generateCsrfToken()
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                throw new RuntimeException('Impossible de démarrer la session');
            }
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    } catch (Throwable $e) {
        error_log('Erreur génération CSRF: ' . $e->getMessage());
        return false; // signature conservée
    }
}

function verifyCsrfToken($token): bool
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && $token !== ''
            && hash_equals((string)$_SESSION['csrf_token'], (string)$token);
    } catch (Throwable $e) {
        error_log('Erreur vérification CSRF: ' . $e->getMessage());
        return false;
    }
}

// ============================
// 4) NETTOYAGE / VALIDATION
// ============================
/**
 * cleanInput : normalise une entrée brute (trim/stripslashes)
 * ⚠️ NOTE : l’échappement HTML doit se faire À L’AFFICHAGE.
 * Compatibilité conservée avec htmlspecialchars ici, mais préférez escapeHtml() en sortie.
 */
function cleanInput($data)
{
    if ($data === null) return '';
    if (!is_string($data)) $data = (string)$data;
    $data = trim($data);
    $data = stripslashes($data);
    // Compat : échappement ici, mais idéalement seulement au rendu
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

/** Échapper au moment d’afficher dans le HTML (recommandé) */
function escapeHtml($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validateEmail($email): bool
{
    if (!is_string($email) || $email === '') return false;
    if (strlen($email) > 254) return false;
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ============================
// 5) MOTS DE PASSE
// ============================
function hashPassword($password)
{
    if (!is_string($password)) {
        throw new InvalidArgumentException('Le mot de passe doit être une chaîne');
    }
    $len = strlen($password);
    if ($len < 8) throw new InvalidArgumentException('Mot de passe trop court (min 8)');
    if ($len > 4096) throw new InvalidArgumentException('Mot de passe trop long');

    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 3,
        ]);
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash): bool
{
    return is_string($password) && is_string($hash) && password_verify($password, $hash);
}

// ============================
// 6) ANTI BRUTE-FORCE (SESSION)
// ============================
function checkLoginAttempts($ip): bool
{
    if (!is_string($ip) || $ip === '') return false;

    $max_attempts = 5;
    $lockout_time = 900; // 15 min

    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        $attempts =& $_SESSION['login_attempts'];

        // Nettoyage
        $now = time();
        foreach ($attempts as $k => $data) {
            if (!is_array($data) || empty($data['last_attempt']) || ($now - (int)$data['last_attempt']) > $lockout_time) {
                unset($attempts[$k]);
            }
        }

        if (!empty($attempts[$ip]['count']) && (int)$attempts[$ip]['count'] >= $max_attempts) {
            if (!empty($attempts[$ip]['last_attempt']) && ($now - (int)$attempts[$ip]['last_attempt']) < $lockout_time) {
                return false;
            }
            unset($attempts[$ip]); // fenêtre expirée → reset
        }
        return true;
    } catch (Throwable $e) {
        error_log('Erreur vérification tentatives: ' . $e->getMessage());
        return false;
    }
}

function recordFailedLogin($ip): bool
{
    if (!is_string($ip) || $ip === '') return false;
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        $attempts =& $_SESSION['login_attempts'];

        if (empty($attempts[$ip]) || !is_array($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
        }
        $attempts[$ip]['count']       = (int)($attempts[$ip]['count'] ?? 0) + 1;
        $attempts[$ip]['last_attempt'] = time();
        return true;
    } catch (Throwable $e) {
        error_log('Erreur enregistrement échec: ' . $e->getMessage());
        return false;
    }
}

function resetLoginAttempts($ip): bool
{
    if (!is_string($ip) || $ip === '') return false;
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['login_attempts'][$ip])) unset($_SESSION['login_attempts'][$ip]);
    return true;
}

// ============================
// 7) RATE LIMIT (SESSION)
// ============================
function rateLimitRequest($key, $maxRequests = 60, $timeWindow = 60)
{
    if (!is_string($key) || $key === '') return false;
    if (!is_int($maxRequests) || $maxRequests <= 0) $maxRequests = 60;
    if (!is_int($timeWindow) || $timeWindow <= 0) $timeWindow = 60;

    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['rate_limits'] = $_SESSION['rate_limits'] ?? [];

        $now         = time();
        $windowStart = $now - $timeWindow;

        // Nettoyage
        foreach ($_SESSION['rate_limits'] as $i => $data) {
            if (!is_array($data) || empty($data['timestamp']) || (int)$data['timestamp'] < $windowStart) {
                unset($_SESSION['rate_limits'][$i]);
            }
        }

        // Compte des requêtes dans la fenêtre
        $current = 0;
        foreach ($_SESSION['rate_limits'] as $data) {
            if (is_array($data)
                && ($data['key'] ?? null) === $key
                && (int)($data['timestamp'] ?? 0) >= $windowStart) {
                $current++;
            }
        }

        if ($current >= $maxRequests) {
            // Nouveau comportement: ne coupe plus la réponse de force (meilleur pour Sonar)
            if (!headers_sent()) {
                http_response_code(429);
                header('Content-Type: application/json; charset=utf-8');
            }
            $payload = json_encode(['error' => 'Trop de requêtes. Veuillez patienter.'], JSON_UNESCAPED_UNICODE);

            if (defined('ECR_RATE_LIMIT_AUTO_DIE') && ECR_RATE_LIMIT_AUTO_DIE) {
                exit($payload); // compat optionnelle
            }
            return false;
        }

        // Enregistrement
        $_SESSION['rate_limits'][] = ['key' => $key, 'timestamp' => $now];
        return true;
    } catch (Throwable $e) {
        error_log('Erreur rate limiting: ' . $e->getMessage());
        return false;
    }
}

// ============================
// 8) UPLOADS
// ============================
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 2097152)
{
    if (!is_array($file) || !isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
        throw new InvalidArgumentException('Fichier invalide.');
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Erreur lors de l\'upload (code ' . (int)$file['error'] . ').');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Fichier non uploadé correctement.');
    }

    $extension = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes, true)) {
        throw new InvalidArgumentException('Type de fichier non autorisé: ' . $extension);
    }

    // Vérif taille basée sur le fichier réel (plus fiable que $_FILES['size'])
    $realSize = @filesize($file['tmp_name']);
    if ($realSize === false || $realSize > $maxSize) {
        throw new InvalidArgumentException('Fichier trop volumineux: ' . ($realSize === false ? 'unknown' : (string)$realSize) . ' bytes');
    }

    if (!function_exists('finfo_open')) {
        throw new RuntimeException('Extension fileinfo non disponible');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) throw new RuntimeException('Impossible d\’initialiser finfo');
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mimeType === false) throw new RuntimeException('Impossible de déterminer le type MIME');

    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];
    if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
        throw new InvalidArgumentException('Type MIME non autorisé: ' . (string)$mimeType);
    }

    // Vérification structure image (mitige les polyglots)
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        throw new InvalidArgumentException('Fichier image invalide.');
    }

    return true;
}

// ============================
// 9) HEADERS DE SÉCURITÉ + CSP NONCE
// ============================
/**
 * Génère (ou récupère) un nonce CSP pour autoriser les <script> inline non dangereux.
 * À utiliser dans vos templates : <script nonce="<?= escapeHtml($_SESSION['csp_nonce'] ?? '') ?>">
 */
function getCspNonce(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['csp_nonce'];
}

function setSecurityHeaders(): bool
{
    if (headers_sent()) return false;

    // Durcissements classiques
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY'); // legacy, mais safe
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // CSP avec nonce pour scripts inline (évite 'unsafe-inline')
    $nonce = getCspNonce();

    // ⚠️ Correction : fonts.googleapis.com n\'a rien à faire dans script-src ; on le laisse dans style-src
    $csp =
        "default-src 'self'; " .
        "base-uri 'self'; form-action 'self'; frame-ancestors 'none'; " .
        "script-src 'self' 'nonce-$nonce'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data:; " .
        "connect-src 'self';";

    header('Content-Security-Policy: ' . $csp);

    // Optionnels (selon besoins applicatifs) :
    // header('Cross-Origin-Opener-Policy: same-origin');
    // header('Cross-Origin-Resource-Policy: same-origin');

    return true;
}

// ============================
// 10) AUTH / ROLES
// ============================
function requireAuth(): void
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                if (!headers_sent()) header('Location: login.php');
                exit();
            }
        }
        if (empty($_SESSION['user']['email'])) {
            if (!headers_sent()) header('Location: login.php');
            exit();
        }
    } catch (Throwable $e) {
        error_log('Erreur auth: ' . $e->getMessage());
        if (!headers_sent()) header('Location: login.php');
        exit();
    }
}

function requireRole($required_role): void
{
    if (!is_string($required_role) || $required_role === '') {
        throw new InvalidArgumentException('Rôle requis invalide');
    }

    requireAuth();

    $user_role  = $_SESSION['user']['type'] ?? 'Utilisateur';
    $hierarchy  = ['Utilisateur' => 1, 'Moderateur' => 2, 'Administrateur' => 3];
    $user_level = $hierarchy[$user_role] ?? 0;
    $required_level = $hierarchy[$required_role] ?? 99;

    if ($user_level < $required_level) {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
        }
        exit('Accès refusé. Permissions insuffisantes.'); // évite die()
    }
}

// ============================
// 11) LOG SÉCURITÉ
// ============================
function logSecurityEvent($event, $details = ''): bool
{
    if (!is_string($event) || $event === '') return false;
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

        $log_file = __DIR__ . '/../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = getRealIpAddr();
        $user = $_SESSION['user']['email'] ?? 'anonymous';

        // Éviter l’injection dans les logs (pas besoin d’HTML-escape)
        $event = preg_replace('/[\r\n]+/', ' ', (string)$event);
        $details = preg_replace('/[\r\n]+/', ' ', (string)$details);

        $entry = "[$timestamp] IP: $ip | User: $user | Event: $event | Details: $details" . PHP_EOL;

        $dir = dirname($log_file);
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            error_log('Impossible de créer le dossier de logs: ' . $dir);
            return false;
        }
        return file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX) !== false;
    } catch (Throwable $e) {
        error_log('Erreur journalisation: ' . $e->getMessage());
        return false;
    }
}

function getRealIpAddr(): string
{
    // Priorité à REMOTE_ADDR (source directe)
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        return (string)$_SERVER['REMOTE_ADDR'];
    }

    // Fallbacks : on tente les headers proxy (attention au spoofing hors proxy reverse de confiance)
    $keys = [
        'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'
    ];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            // XFF peut contenir une liste : on prend le premier IP valide/public
            $parts = explode(',', (string)$_SERVER[$key]);
            foreach ($parts as $part) {
                $ip = trim($part);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    // OK en dev / réseaux privés
                    return $ip;
                }
            }
        }
    }
    return 'unknown';
}

// ============================
// 12) HYGIÈNE DE SESSION & INIT
// ============================
function cleanSession(): bool
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) return false;
        $now = time();

        if (!empty($_SESSION['login_attempts']) && is_array($_SESSION['login_attempts'])) {
            foreach ($_SESSION['login_attempts'] as $ip => $data) {
                if (!is_array($data) || empty($data['last_attempt']) || ($now - (int)$data['last_attempt']) > 900) {
                    unset($_SESSION['login_attempts'][$ip]);
                }
            }
        }
        if (!empty($_SESSION['rate_limits']) && is_array($_SESSION['rate_limits'])) {
            foreach ($_SESSION['rate_limits'] as $k => $data) {
                if (!is_array($data) || empty($data['timestamp']) || ($now - (int)$data['timestamp']) > 60) {
                    unset($_SESSION['rate_limits'][$k]);
                }
            }
        }
        return true;
    } catch (Throwable $e) {
        error_log('Erreur nettoyage session: ' . $e->getMessage());
        return false;
    }
}

function initSecurity(): bool
{
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        setSecurityHeaders();
        cleanSession();
        logSecurityEvent('SECURITY_INIT', 'Système de sécurité initialisé');
        return true;
    } catch (Throwable $e) {
        error_log("Erreur lors de l'initialisation de la sécurité: " . $e->getMessage());
        return false;
    }
}

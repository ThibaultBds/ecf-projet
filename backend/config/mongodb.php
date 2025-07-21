<?php
/**
 * Configuration MongoDB pour logs NoSQL
 * Requis par le cahier des charges
 */

class MongoLogger {
    private $logDir;

    public function __construct() {
        try {
            // Pour la démo, on simule MongoDB avec des fichiers JSON
            // En production, utilisez MongoDB réel
            $this->logDir = __DIR__ . '/../logs/';
            if (!is_dir($this->logDir)) {
                mkdir($this->logDir, 0777, true);
            }
        } catch (Exception $e) {
            // Fallback silencieux
        }
    }

    public function logActivity($userId, $action, $details, $ipAddress) {
        $logEntry = [
            '_id' => uniqid(),
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ipAddress,
            'timestamp' => (new DateTime())->format(DateTime::ATOM),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Simuler MongoDB avec fichier JSON
            $logFile = $this->logDir . 'activity_' . date('Y-m') . '.json';
            $logs = [];
            if (file_exists($logFile)) {
                $logs = json_decode(file_get_contents($logFile), true) ?: [];
            }
            $logs[] = $logEntry;
            file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

            // Aussi en MySQL pour compatibilité
            // Remplacer require_once par un autoload moderne ou s'assurer qu'il n'est pas dupliqué
            // Supposons ici que database.php est correctement autoloadé via Composer ou un autre autoloader
            // use App\Database; (exemple de namespace si tu en utilises un)
            $pdo = getDatabase();
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $details, $ipAddress]);
        } catch (Exception $e) {
            // Log silencieux
        }
    }

    public function getRecentLogs($limit = 100) {
        try {
            $logFile = $this->logDir . 'activity_' . date('Y-m') . '.json';
            if (file_exists($logFile)) {
                $logs = json_decode(file_get_contents($logFile), true) ?: [];
                return array_slice(array_reverse($logs), 0, $limit);
            }
        } catch (Exception $e) {
            // Fallback
        }
        return [];
    }
}

// Instance globale
function getMongoLogger() {
    static $logger = null;
    if ($logger === null) {
        $logger = new MongoLogger();
    }
    return $logger;
}

<?php 
/**
 * Configuration et gestion de la base de données EcoRide
 * Compatible Heroku JawsDB & Docker (MySQL 8)
 *
 * En local avec Docker :
 * - host = ecoride-db (nom du service dans docker-compose.yml)
 * - user = root
 * - mdp = root
 * - base = ecoride
 * - port = 3306
 *
 * En prod Heroku/JawsDB :
 * - La config est récupérée automatiquement via JAWSDB_URL ou CLEARDB_DATABASE_URL.
 */

class DatabaseConnectionException extends Exception {}
class InsufficientCreditsException extends Exception {}
class NoAvailablePlaceException extends Exception {}

class Database {
    private $host;
    private $dbName;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // === CONFIGURATION DOCKER (par défaut) ===
        $this->host     = 'ecoride-db';   // ⚡ nom du service MySQL Docker
        $this->dbName   = 'ecoride';
        $this->username = 'root';
        $this->password = 'root';
        $this->port     = 3306;

        // === CONFIGURATION HEROKU/JAWSDB (auto si variable d'env détectée) ===
        $herokuUrl = getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL');
        if ($herokuUrl) {
            $dbparts        = parse_url($herokuUrl);
            $this->host     = $dbparts['host'] ?? $this->host;
            $this->username = $dbparts['user'] ?? $this->username;
            $this->password = $dbparts['pass'] ?? $this->password;
            $this->dbName   = isset($dbparts['path']) ? ltrim($dbparts['path'], '/') : $this->dbName;
            $this->port     = isset($dbparts['port']) ? (int)$dbparts['port'] : $this->port;
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            throw new DatabaseConnectionException(
                "ERREUR DB : " . $exception->getMessage() .
                ". Vérifie dans backend/config/database.php que tes identifiants sont bons !"
            );
        }
        return $this->conn;
    }
}

// --- UTILITAIRES DE CONNEXION ---

function getDatabase() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}

// --- FONCTIONS D'ACCÈS AUX DONNÉES ---

function getTrips($depart = '', $arrivee = '', $date = '') {
    try {
        $pdo = getDatabase();
        $sql = "SELECT t.*, u.pseudo as conducteur, v.marque, v.modele, v.energie, u.rating,
                       TIMESTAMPDIFF(HOUR, NOW(), t.date_depart) as heures_restantes,
                       TIMESTAMPDIFF(MINUTE, NOW(), t.date_depart) as minutes_restantes,
                       CASE
                           WHEN u.pseudo = 'Marc D.' THEN 'images/sebastien.jpg'
                           WHEN u.pseudo = 'Sophie L.' THEN 'images/lucie.jpg'
                           ELSE 'images/default_avatar.png'
                       END as conducteur_avatar_url
                FROM trips t
                JOIN users u ON t.chauffeur_id = u.id
                JOIN vehicles v ON t.vehicle_id = v.id
                WHERE t.status = 'planifie' AND t.places_restantes > 0 AND t.date_depart > NOW()";
        $params = [];
        if (!empty($depart)) {
            $sql .= " AND LOWER(t.ville_depart) LIKE LOWER(?)";
            $params[] = "%$depart%";
        }
        if (!empty($arrivee)) {
            $sql .= " AND LOWER(t.ville_arrivee) LIKE LOWER(?)";
            $params[] = "%$arrivee%";
        }
        if (!empty($date)) {
            $sql .= " AND DATE(t.date_depart) = ?";
            $params[] = $date;
        }
        $sql .= " ORDER BY t.date_depart ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();



        return $results;
    } catch (Exception $e) {
        error_log("Erreur getTrips: " . $e->getMessage());
        return [];
    }
}

function getUserById($id) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

function getTripById($id) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("SELECT t.*, u.pseudo as conducteur, v.marque, v.modele, v.energie,
                               CASE
                                   WHEN u.pseudo = 'Marc D.' THEN 'images/sebastien.jpg'
                                   WHEN u.pseudo = 'Sophie L.' THEN 'images/lucie.jpg'
                                   ELSE 'images/default_avatar.png'
                               END as conducteur_avatar_url
                               FROM trips t
                               JOIN users u ON t.chauffeur_id = u.id
                               JOIN vehicles v ON t.vehicle_id = v.id
                               WHERE t.id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

function getReviewsByDriverId($chauffeur_id) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("
            SELECT r.*, u.pseudo as reviewer_pseudo
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.reviewed_id = ? AND r.status = 'valide'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$chauffeur_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function createTrip($chauffeur_id, $vehicle_id, $ville_depart, $ville_arrivee, $date_depart, $prix, $places, $description = '') {
    try {
        $pdo = getDatabase();
        // Déterminer si le trajet est écologique
        $stmt = $pdo->prepare("SELECT energie FROM vehicles WHERE id = ? LIMIT 1");
        $stmt->execute([$vehicle_id]);
        $vehicleData = $stmt->fetch();
        $is_ecological = ($vehicleData && strtolower($vehicleData['energie']) === 'electrique') ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO trips (chauffeur_id, vehicle_id, ville_depart, ville_arrivee, date_depart, prix, places_totales, places_restantes, is_ecological, description, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planifie')
        ");
        return $stmt->execute([
            $chauffeur_id, $vehicle_id, $ville_depart, $ville_arrivee,
            $date_depart, $prix, $places, $places, $is_ecological, $description
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function getVehicleByUserId($user_id) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

function createDefaultVehicle($user_id, $places = 4) {
    try {
        $pdo = getDatabase();
        $plaque = 'AB-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT) . '-CD';
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (user_id, marque, modele, couleur, plaque, date_immatriculation, energie, places_disponibles)
            VALUES (?, 'Renault', 'Clio', 'Blanc', ?, '2020-01-01', 'essence', ?)
        ");
        if ($stmt->execute([$user_id, $plaque, $places])) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

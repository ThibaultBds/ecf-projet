<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class TripRepository
{
    private function pdo(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public function search(array $filters): array
    {
        $sql = "SELECT t.*,
                       u.username AS conducteur,
                       u.photo AS conducteur_photo,
                       v.brand,
                       v.model,
                       v.energy_type,
                       cd.name AS ville_depart,
                       ca.name AS ville_arrivee,
                       COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.driver_id = u.user_id AND r.status = 'approved'), 0) AS note_conducteur
                FROM trips t
                JOIN users u ON t.chauffeur_id = u.user_id
                JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                JOIN cities cd ON t.city_depart_id = cd.city_id
                JOIN cities ca ON t.city_arrival_id = ca.city_id
                WHERE t.status = 'scheduled'
                  AND t.available_seats > 0
                  AND t.departure_datetime > NOW()";

        $params = [];

        if (!empty($filters['depart'])) {
            $sql .= " AND cd.name LIKE ?";
            $params[] = '%' . $filters['depart'] . '%';
        }

        if (!empty($filters['arrivee'])) {
            $sql .= " AND ca.name LIKE ?";
            $params[] = '%' . $filters['arrivee'] . '%';
        }

        if (!empty($filters['date'])) {
            $sql .= " AND DATE(t.departure_datetime) = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['prix_max'])) {
            $sql .= " AND t.price <= ?";
            $params[] = (float) $filters['prix_max'];
        }

        if (!empty($filters['ecologique'])) {
            $sql .= " AND v.energy_type = 'electrique'";
        }

        if (!empty($filters['duree_max'])) {
            $sql .= " AND TIMESTAMPDIFF(MINUTE, t.departure_datetime, t.arrival_datetime) <= ?";
            $params[] = (int) $filters['duree_max'] * 60;
        }

        if (!empty($filters['note_min'])) {
            $sql .= " HAVING note_conducteur >= ?";
            $params[] = (float) $filters['note_min'];
        }

        $sql .= " ORDER BY t.departure_datetime ASC";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM trips WHERE trip_id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.*,
                    u.username AS conducteur,
                    u.user_id AS chauffeur_user_id,
                    u.photo AS conducteur_photo,
                    v.brand,
                    v.model,
                    v.color,
                    v.energy_type,
                    v.seats_available,
                    cd.name AS ville_depart,
                    ca.name AS ville_arrivee
             FROM trips t
             JOIN users u ON t.chauffeur_id = u.user_id
             JOIN vehicles v ON t.vehicle_id = v.vehicle_id
             JOIN cities cd ON t.city_depart_id = cd.city_id
             JOIN cities ca ON t.city_arrival_id = ca.city_id
             WHERE t.trip_id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function nearestDate(string $depart, string $arrivee): ?string
    {
        $sql = "SELECT DATE(t.departure_datetime) AS nearest_date
                FROM trips t
                JOIN cities cd ON t.city_depart_id = cd.city_id
                JOIN cities ca ON t.city_arrival_id = ca.city_id
                WHERE t.status = 'scheduled'
                  AND t.available_seats > 0
                  AND t.departure_datetime > NOW()";

        $params = [];

        if ($depart !== '') {
            $sql .= " AND cd.name LIKE ?";
            $params[] = '%' . $depart . '%';
        }
        if ($arrivee !== '') {
            $sql .= " AND ca.name LIKE ?";
            $params[] = '%' . $arrivee . '%';
        }

        $sql .= " ORDER BY t.departure_datetime ASC LIMIT 1";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? $result['nearest_date'] : null;
    }

    public function findByDriver(int $driverId): array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM trips WHERE chauffeur_id = ?");
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    public function byDriver($driverId)
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.*,
                    cd.name AS ville_depart,
                    ca.name AS ville_arrivee,
                    (SELECT COUNT(*)
                     FROM trip_participants tp
                     WHERE tp.trip_id = t.trip_id) AS nb_participants
             FROM trips t
             JOIN cities cd ON t.city_depart_id = cd.city_id
             JOIN cities ca ON t.city_arrival_id = ca.city_id
             WHERE t.chauffeur_id = ?
             ORDER BY t.departure_datetime DESC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    public function byPassenger($passengerId)
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.*,
                    u.username AS conducteur,
                    cd.name AS ville_depart,
                    ca.name AS ville_arrivee,
                    tp.status AS participant_status,
                    (SELECT COUNT(*) FROM reviews r2
                     WHERE r2.trip_id = t.trip_id AND r2.reviewer_id = tp.user_id) AS has_reviewed
             FROM trips t
             JOIN trip_participants tp ON t.trip_id = tp.trip_id
             JOIN users u ON t.chauffeur_id = u.user_id
             JOIN cities cd ON t.city_depart_id = cd.city_id
             JOIN cities ca ON t.city_arrival_id = ca.city_id
             WHERE tp.user_id = ?
             ORDER BY t.departure_datetime DESC"
        );
        $stmt->execute([$passengerId]);
        return $stmt->fetchAll();
    }
}

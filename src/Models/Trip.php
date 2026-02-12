<?php

require_once __DIR__ . '/BaseModel.php';

class Trip extends BaseModel
{
    protected static $table = 'trips';

    /**
     * Rechercher des trajets avec filtres
     */
    public static function search($filters = [])
    {
        $pdo = static::getConnection();

        $sql = "SELECT t.*,
                       u.username AS conducteur,
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

        if (!empty($filters['note_min'])) {
            $sql .= " HAVING note_conducteur >= ?";
            $params[] = (float) $filters['note_min'];
        }

        $sql .= " ORDER BY t.departure_datetime ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Récupérer un trajet avec toutes les infos
     */
    public static function findWithDetails($id)
    {
        return static::query(
            "SELECT t.*,
                    u.username AS conducteur,
                    u.user_id AS chauffeur_user_id,
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
             WHERE t.trip_id = ?",
            [$id]
        )->fetch() ?: null;
    }

    /**
     * Récupérer les trajets d'un chauffeur
     */
    public static function byDriver($driverId)
    {
        return static::query(
            "SELECT t.*,
                    (SELECT COUNT(*) 
                     FROM trip_participants tp 
                     WHERE tp.trip_id = t.trip_id) AS nb_participants
             FROM trips t
             WHERE t.chauffeur_id = ?
             ORDER BY t.departure_datetime DESC",
            [$driverId]
        )->fetchAll();
    }

    /**
     * Récupérer les trajets d'un passager
     */
    public static function byPassenger($passengerId)
    {
        return static::query(
            "SELECT t.*, 
                    u.username AS conducteur
             FROM trips t
             JOIN trip_participants tp ON t.trip_id = tp.trip_id
             JOIN users u ON t.chauffeur_id = u.user_id
             WHERE tp.user_id = ?
             ORDER BY t.departure_datetime DESC",
            [$passengerId]
        )->fetchAll();
    }
}

<?php

require_once __DIR__ . '/BaseModel.php';

class Review extends BaseModel
{
    protected static $table = 'reviews';

    /**
     * Récupérer les avis d'un chauffeur
     */
    public static function byDriver($driverId)
    {
        return static::query(
            "SELECT r.*, u.username AS reviewer_name
             FROM reviews r
             JOIN users u ON r.reviewer_id = u.user_id
             WHERE r.driver_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            [$driverId]
        )->fetchAll();
    }

    /**
     * Calculer la note moyenne d'un chauffeur
     */
    public static function averageRating($driverId)
    {
        $result = static::query(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as total
             FROM reviews
             WHERE driver_id = ? AND status != 'rejected'",
            [$driverId]
        )->fetch();

        return [
            'average' => $result['avg_rating'] ? round((float)$result['avg_rating'], 1) : null,
            'count' => (int)$result['total']
        ];
    }
}

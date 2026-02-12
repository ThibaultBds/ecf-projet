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
            "SELECT r.*, u.pseudo as reviewer_name
             FROM reviews r
             JOIN users u ON r.reviewer_id = u.id
             WHERE r.reviewed_id = ?
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
            "SELECT AVG(note) as avg_rating, COUNT(*) as total
             FROM reviews
             WHERE reviewed_id = ? AND status != 'rejete'",
            [$driverId]
        )->fetch();

        return [
            'average' => $result['avg_rating'] ? round((float)$result['avg_rating'], 1) : null,
            'count' => (int)$result['total']
        ];
    }
}

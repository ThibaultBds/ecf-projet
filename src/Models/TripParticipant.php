<?php

require_once __DIR__ . '/BaseModel.php';

class TripParticipant extends BaseModel
{
    protected static $table = 'trip_participants';

    /**
     * Vérifier si un utilisateur participe déjà à un trajet
     */
    public static function isParticipating($tripId, $userId)
    {
        $result = static::query(
            "SELECT id FROM trip_participants WHERE trip_id = ? AND passager_id = ? LIMIT 1",
            [$tripId, $userId]
        )->fetch();

        return $result !== false;
    }

    /**
     * Vérifier si un utilisateur a déjà laissé un avis pour un trajet
     */
    public static function hasReviewed($tripId, $userId)
    {
        $result = static::query(
            "SELECT has_reviewed FROM trip_participants WHERE trip_id = ? AND passager_id = ? LIMIT 1",
            [$tripId, $userId]
        )->fetch();

        return $result && $result['has_reviewed'];
    }

    /**
     * Marquer comme ayant laissé un avis
     */
    public static function markReviewed($tripId, $userId)
    {
        static::query(
            "UPDATE trip_participants SET has_reviewed = 1 WHERE trip_id = ? AND passager_id = ?",
            [$tripId, $userId]
        );
    }

    /**
     * Récupérer tous les participants d'un trajet
     */
    public static function byTrip($tripId)
    {
        return static::query(
            "SELECT tp.*, u.pseudo, u.email
             FROM trip_participants tp
             JOIN users u ON tp.passager_id = u.id
             WHERE tp.trip_id = ?",
            [$tripId]
        )->fetchAll();
    }
}

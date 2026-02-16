<?php

namespace App\Models;

class TripParticipant extends BaseModel
{
    protected static $table = 'trip_participants';

    public static function isParticipating($tripId, $userId)
    {
        $result = static::query(
            "SELECT id FROM trip_participants WHERE trip_id = ? AND user_id = ? LIMIT 1",
            [$tripId, $userId]
        )->fetch();

        return $result !== false;
    }

    public static function hasReviewed($tripId, $userId)
    {
        $result = static::query(
            "SELECT id FROM reviews WHERE trip_id = ? AND reviewer_id = ? LIMIT 1",
            [$tripId, $userId]
        )->fetch();

        return $result !== false;
    }

    public static function byTrip($tripId)
    {
        return static::query(
            "SELECT tp.*, u.username, u.email
             FROM trip_participants tp
             JOIN users u ON tp.user_id = u.user_id
             WHERE tp.trip_id = ?",
            [$tripId]
        )->fetchAll();
    }
}

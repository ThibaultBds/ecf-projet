<?php

namespace App\Models;

class TripParticipant extends BaseModel
{
    protected ?string $table = 'trip_participants';

    public function isParticipating($tripId, $userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM trip_participants WHERE trip_id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$tripId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function hasReviewed($tripId, $userId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM reviews WHERE trip_id = ? AND reviewer_id = ? LIMIT 1"
        );
        $stmt->execute([$tripId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function byTrip($tripId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT tp.*, u.username, u.email
             FROM trip_participants tp
             JOIN users u ON tp.user_id = u.user_id
             WHERE tp.trip_id = ?"
        );
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public function removeParticipation(int $tripId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM trip_participants WHERE trip_id = ? AND user_id = ?"
        );
        $stmt->execute([$tripId, $userId]);
    }
}

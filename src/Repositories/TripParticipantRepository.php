<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\TripParticipant;
use PDO;

class TripParticipantRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function isParticipating(int $tripId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM trip_participants WHERE trip_id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$tripId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function hasReviewed(int $tripId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM reviews WHERE trip_id = ? AND reviewer_id = ? LIMIT 1"
        );
        $stmt->execute([$tripId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function find(int $tripId, int $userId): ?TripParticipant
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM trip_participants WHERE trip_id = ? AND user_id = ?"
        );
        $stmt->execute([$tripId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? TripParticipant::hydrate($row) : null;
    }

    public function byTrip(int $tripId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT tp.*, u.username, u.email
             FROM trip_participants tp
             JOIN users u ON tp.user_id = u.user_id
             WHERE tp.trip_id = ?"
        );
        $stmt->execute([$tripId]);
        return TripParticipant::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt         = $this->pdo->prepare(
            "INSERT INTO trip_participants ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function removeParticipation(int $tripId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM trip_participants WHERE trip_id = ? AND user_id = ?"
        );
        $stmt->execute([$tripId, $userId]);
    }

    public function updateStatus(int $tripId, int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE trip_participants SET status = ? WHERE trip_id = ? AND user_id = ?"
        );
        $stmt->execute([$status, $tripId, $userId]);
    }
}

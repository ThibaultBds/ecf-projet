<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Review;
use PDO;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function byDriver(int $driverId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*, u.username AS reviewer_name
             FROM reviews r
             JOIN users u ON r.reviewer_id = u.user_id
             WHERE r.driver_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$driverId]);
        return Review::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function averageRating(int $driverId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as total
             FROM reviews WHERE driver_id = ? AND status != 'rejected'"
        );
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'average' => $result['avg_rating'] ? round((float) $result['avg_rating'], 1) : null,
            'count'   => (int) $result['total'],
        ];
    }

    public function pendingReviews(): array
    {
        $stmt = $this->pdo->query(
            "SELECT r.*,
                    reviewer.username AS reviewer_name, reviewer.email AS reviewer_email,
                    driver.username AS driver_name
             FROM reviews r
             JOIN users reviewer ON reviewer.user_id = r.reviewer_id
             JOIN users driver ON driver.user_id = r.driver_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC
             LIMIT 50"
        );
        return Review::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt         = $this->pdo->prepare("INSERT INTO reviews ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}

<?php

namespace App\Models;

class Review extends BaseModel
{
    protected ?string $table = 'reviews';

    public function byDriver($driverId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*, u.username AS reviewer_name
         FROM reviews r
         JOIN users u ON r.reviewer_id = u.user_id
         WHERE r.driver_id = ? AND r.status = 'approved'
         ORDER BY r.created_at DESC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }


    public function averageRating($driverId)
    {
        $stmt = $this->pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total
             FROM reviews
             WHERE driver_id = ? AND status != 'rejected'");
        $stmt->execute([$driverId]);
        return $stmt->fetch();
        return [
            'average' => $result['avg_rating'] ? round((float)$result['avg_rating'], 1) : null,
            'count' => (int)$result['total']
        ];
    }
}

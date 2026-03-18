<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

class AdminRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function stats(): array
    {
        return [
            'users'            => (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'trips'            => (int) $this->pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'scheduled'")->fetchColumn(),
            'pending_reviews'  => (int) $this->pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn(),
            'platform_credits' => (int) $this->pdo->query("SELECT COALESCE(-SUM(amount), 0) FROM credit_logs WHERE type = 'platform_fee'")->fetchColumn(),
        ];
    }

    public function tripsPerDay(): array
    {
        return $this->pdo->query(
            "SELECT DATE(departure_datetime) AS jour, COUNT(*) AS total
             FROM trips
             WHERE departure_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY jour ORDER BY jour"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function creditsPerDay(): array
    {
        return $this->pdo->query(
            "SELECT DATE(created_at) AS jour, -SUM(amount) AS total
             FROM credit_logs
             WHERE type = 'platform_fee' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY jour ORDER BY jour"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recentUsers(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("SELECT user_id, username, email, credits, role, is_driver, is_passenger, photo, suspended FROM users ORDER BY user_id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return User::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function addCreditsToUser(int $userId, int $credits): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE user_id = ?");
        $stmt->execute([$credits, $userId]);
    }
}

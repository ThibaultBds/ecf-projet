<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Trip;
use App\Models\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::hydrate($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::hydrate($row) : null;
    }

    public function findAll(): array
    {
        return User::hydrateAll(
            $this->pdo->query("SELECT user_id, username, email, credits, role, is_driver, is_passenger, photo, suspended FROM users")->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function exists(string $email, string $username): bool
    {
        return $this->findByEmail($email) !== null || $this->findByUsername($username) !== null;
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt         = $this->pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE users SET {$sets} WHERE user_id = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public function decrementCreditsIfEnough(int $userId, int $amount): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET credits = credits - ? WHERE user_id = ? AND credits >= ?"
        );
        $stmt->execute([$amount, $userId, $amount]);
        return $stmt->rowCount() > 0;
    }

    public function incrementCredits(int $userId, int $amount): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE user_id = ?");
        $stmt->execute([$amount, $userId]);
    }

    public function logCredit(int $userId, int $amount, string $type, ?string $reason = null, ?int $tripId = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO credit_logs (user_id, amount, type, reason, trip_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $amount, $type, $reason, $tripId]);
    }

    public function updatePhoto(int $userId, ?string $photoName): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET photo = ? WHERE user_id = ?");
        $stmt->execute([$photoName, $userId]);
    }

    public function recentTrips(int $userId, int $limit = 10): array
    {
        $limit = (int) $limit;
        $stmt  = $this->pdo->prepare(
            "SELECT t.*,
                cd.name AS ville_depart,
                ca.name AS ville_arrivee,
                CASE WHEN t.chauffeur_id = ? THEN 'chauffeur' ELSE 'passager' END as role_trajet
             FROM trips t
             JOIN cities cd ON t.city_depart_id = cd.city_id
             JOIN cities ca ON t.city_arrival_id = ca.city_id
             LEFT JOIN trip_participants tp ON t.trip_id = tp.trip_id AND tp.user_id = ?
             WHERE t.chauffeur_id = ? OR tp.user_id = ?
             ORDER BY t.departure_datetime DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return Trip::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

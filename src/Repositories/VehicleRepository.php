<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Vehicle;
use PDO;

class VehicleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?Vehicle
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Vehicle::hydrate($row) : null;
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return Vehicle::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function firstByUser(int $userId): ?Vehicle
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Vehicle::hydrate($row) : null;
    }

    public function belongsToUser(int $vehicleId, int $userId): bool
    {
        $vehicle = $this->findById($vehicleId);
        return $vehicle !== null && $vehicle->userId == $userId;
    }

    public function isValidPlate(string $plate): bool
    {
        return (bool) preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', strtoupper($plate));
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt         = $this->pdo->prepare("INSERT INTO vehicles ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE vehicles SET {$sets} WHERE vehicle_id = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public function destroy(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $stmt->execute([$id]);
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class BaseModel
{
    protected ?string $table = null;
    protected string $primaryKey = 'id';
    protected $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM $this->table");
        return $stmt->fetchAll();
    }

    public function find(int $id): mixed
    {
        $stmt = $this->pdo->prepare("SELECT * FROM $this->table WHERE $this->primaryKey = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findBy(string $column, mixed $value): mixed
    {
        $stmt = $this->pdo->prepare("SELECT * FROM $this->table WHERE $column = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->pdo->prepare("INSERT INTO $this->table ($columns) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE $this->table SET $sets WHERE $this->primaryKey = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM $this->table WHERE $this->primaryKey = ?");
        $stmt->execute([$id]);
    }
}

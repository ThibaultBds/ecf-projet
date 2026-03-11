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

    protected static function getConnection()
    {
        return Database::getInstance()->getConnection();
    }

    protected static function resolveTableName(): string
    {
        $model = new static();

        if (!empty($model->table)) {
            return $model->table;
        }

        $class = static::class;
        if (property_exists($class, 'table')) {
            $ref = new \ReflectionClass($class);
            if ($ref->hasProperty('table')) {
                $prop = $ref->getProperty('table');
                if ($prop->isStatic()) {
                    $prop->setAccessible(true);
                    $value = $prop->getValue();
                    if (is_string($value) && $value !== '') {
                        return $value;
                    }
                }
            }
        }

        throw new \RuntimeException("Table non définie pour le modèle {$class}");
    }

    protected static function resolvePrimaryKeyName(): string
    {
        $model = new static();

        if (!empty($model->primaryKey)) {
            return $model->primaryKey;
        }

        $class = static::class;
        if (property_exists($class, 'primaryKey')) {
            $ref = new \ReflectionClass($class);
            if ($ref->hasProperty('primaryKey')) {
                $prop = $ref->getProperty('primaryKey');
                if ($prop->isStatic()) {
                    $prop->setAccessible(true);
                    $value = $prop->getValue();
                    if (is_string($value) && $value !== '') {
                        return $value;
                    }
                }
            }
        }

        return 'id';
    }

    public static function query(string $sql, array $params = [])
    {
        $stmt = static::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function beginTransaction(): void
    {
        static::getConnection()->beginTransaction();
    }

    public static function commit(): void
    {
        static::getConnection()->commit();
    }

    public static function rollback(): void
    {
        static::getConnection()->rollBack();
    }

    public static function where(string $column, mixed $value): array
    {
        $table = static::resolveTableName();
        $stmt = static::getConnection()->prepare("SELECT * FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return static::findAll();
    }

    public static function count(): int
    {
        $table = static::resolveTableName();
        $stmt = static::getConnection()->query("SELECT COUNT(*) AS total FROM {$table}");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public static function findAll(): array
    {
        $table = static::resolveTableName();
        $stmt = static::getConnection()->query("SELECT * FROM {$table}");
        return $stmt->fetchAll();
    }

    public static function find(int $id): mixed
    {
        $table = static::resolveTableName();
        $primaryKey = static::resolvePrimaryKeyName();
        $stmt = static::getConnection()->prepare("SELECT * FROM {$table} WHERE {$primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findBy(string $column, mixed $value): mixed
    {
        $table = static::resolveTableName();
        $stmt = static::getConnection()->prepare("SELECT * FROM {$table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $table = static::resolveTableName();
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = static::getConnection()->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int) static::getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $table = static::resolveTableName();
        $primaryKey = static::resolvePrimaryKeyName();
        $sets = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $stmt = static::getConnection()->prepare("UPDATE {$table} SET {$sets} WHERE {$primaryKey} = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public static function delete(int $id): void
    {
        $table = static::resolveTableName();
        $primaryKey = static::resolvePrimaryKeyName();
        $stmt = static::getConnection()->prepare("DELETE FROM {$table} WHERE {$primaryKey} = ?");
        $stmt->execute([$id]);
    }

    public static function destroy(int $id): void
    {
        static::delete($id);
    }
}

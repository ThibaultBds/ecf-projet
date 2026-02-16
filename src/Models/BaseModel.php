<?php

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected static $table;
    protected static $primaryKey = 'id';
    protected $attributes = [];

    protected static function getConnection()
    {
        return Database::getInstance()->getConnection();
    }

    public static function query($sql, $params = [])
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function find($id)
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$pk} = ? LIMIT 1", [$id]);
        return $stmt->fetch() ?: null;
    }

    public static function all($orderBy = null)
    {
        $table = static::$table;
        $sql = "SELECT * FROM {$table}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return static::query($sql)->fetchAll();
    }

    public static function where($column, $value)
    {
        $table = static::$table;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$column} = ?", [$value]);
        return $stmt->fetchAll();
    }

    public static function findBy($column, $value)
    {
        $table = static::$table;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$column} = ? LIMIT 1", [$value]);
        return $stmt->fetch() ?: null;
    }

    public static function count($condition = null, $params = [])
    {
        $table = static::$table;
        $sql = "SELECT COUNT(*) as total FROM {$table}";

        if ($condition) {
            $sql .= " WHERE {$condition}";
        }

        $result = static::query($sql, $params)->fetch();
        return (int) $result['total'];
    }

    public static function create($data)
    {
        $table = static::$table;
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        static::query($sql, array_values($data));

        $pdo = static::getConnection();
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        $sets = [];
        $values = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $setString = implode(', ', $sets);
        $sql = "UPDATE {$table} SET {$setString} WHERE {$pk} = ?";

        static::query($sql, $values);
    }

    public static function destroy($id)
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        static::query("DELETE FROM {$table} WHERE {$pk} = ?", [$id]);
    }

    public static function beginTransaction()
    {
        static::getConnection()->beginTransaction();
    }

    public static function commit()
    {
        static::getConnection()->commit();
    }

    public static function rollback()
    {
        static::getConnection()->rollBack();
    }
}

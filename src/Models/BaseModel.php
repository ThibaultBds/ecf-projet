<?php

require_once __DIR__ . '/../Core/Database.php';

/**
 * Classe de base pour tous les modèles
 * Pattern Active Record simplifié
 */
abstract class BaseModel
{
    protected static $table;
    protected static $primaryKey = 'id';
    protected $attributes = [];

    /**
     * Obtenir la connexion PDO
     */
    protected static function getConnection()
    {
        return Database::getInstance()->getConnection();
    }

    /**
     * Exécuter une requête préparée
     */
    protected static function query($sql, $params = [])
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Trouver un enregistrement par son ID
     */
    public static function find($id)
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$pk} = ? LIMIT 1", [$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupérer tous les enregistrements
     */
    public static function all($orderBy = null)
    {
        $table = static::$table;
        $sql = "SELECT * FROM {$table}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return static::query($sql)->fetchAll();
    }

    /**
     * Rechercher par colonne
     */
    public static function where($column, $value)
    {
        $table = static::$table;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$column} = ?", [$value]);
        return $stmt->fetchAll();
    }

    /**
     * Rechercher un seul enregistrement par colonne
     */
    public static function findBy($column, $value)
    {
        $table = static::$table;
        $stmt = static::query("SELECT * FROM {$table} WHERE {$column} = ? LIMIT 1", [$value]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Compter les enregistrements
     */
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

    /**
     * Créer un enregistrement
     */
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

    /**
     * Mettre à jour un enregistrement par ID
     */
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

    /**
     * Supprimer un enregistrement par ID
     */
    public static function destroy($id)
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        static::query("DELETE FROM {$table} WHERE {$pk} = ?", [$id]);
    }

    /**
     * Démarrer une transaction
     */
    public static function beginTransaction()
    {
        static::getConnection()->beginTransaction();
    }

    /**
     * Valider une transaction
     */
    public static function commit()
    {
        static::getConnection()->commit();
    }

    /**
     * Annuler une transaction
     */
    public static function rollback()
    {
        static::getConnection()->rollBack();
    }
}

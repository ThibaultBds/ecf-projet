<?php

namespace App\Models;

class User extends BaseModel
{
    protected static $table = 'users';
    protected static $primaryKey = 'user_id';

    /**
     * Trouver un utilisateur par email
     */
    public static function findByEmail($email)
    {
        return static::findBy('email', strtolower(trim($email)));
    }

    /**
     * Trouver un utilisateur par username
     */
    public static function findByUsername($username)
    {
        return static::findBy('username', $username);
    }

    /**
     * Vérifier si un email ou username existe déjà
     */
    public static function exists($email, $username)
    {
        $stmt = static::query(
            "SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1",
            [strtolower(trim($email)), trim($username)]
        );
        return $stmt->fetch() !== false;
    }

    /**
     * Déduire des crédits
     */
    public static function deductCredits($userId, $amount)
    {
        static::query(
            "UPDATE users SET credits = credits - ? WHERE user_id = ? AND credits >= ?",
            [$amount, $userId, $amount]
        );
    }

    /**
     * Ajouter des crédits
     */
    public static function addCredits($userId, $amount)
    {
        static::query(
            "UPDATE users SET credits = credits + ? WHERE user_id = ?",
            [$amount, $userId]
        );
    }

    /**
     * Récupérer les derniers trajets d'un utilisateur
     */
    public static function recentTrips($userId, $limit = 10)
    {
        $limit = (int) $limit;
        return static::query(
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
             LIMIT {$limit}",
            [$userId, $userId, $userId, $userId]
        )->fetchAll();
    }
}

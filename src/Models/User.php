<?php

namespace App\Models;

class User extends BaseModel
{
    protected ?string $table = 'users';
    protected string $primaryKey = 'user_id';

    public static function findByEmail($email)
    {
        return static::findBy('email', strtolower(trim($email)));
    }

    public static function findByUsername($username)
    {
        return static::findBy('username', $username);
    }

    public static function exists($email, $username)
    {
        $stmt = static::getConnection()->prepare(
            "SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1"
        );
        $stmt->execute([strtolower(trim($email)), trim($username)]);
        return $stmt->fetch() !== false;
    }

    public static function deductCredits($userId, $amount, $type, $reason = null, $tripId = null)
    {
        $stmt = static::getConnection()->prepare(
            "UPDATE users 
            SET credits = credits - ?
            WHERE user_id = ? AND credits >= ?"
        );
        $stmt->execute([$amount, $userId, $amount]);

        if ($stmt->rowCount() > 0) {
            static::logCredit($userId, -$amount, $type, $reason, $tripId);
        }

        return $stmt->rowCount() > 0;
    }

    public static function addCredits($userId, $amount, $type, $reason = null, $tripId = null)
    {
        $stmt = static::getConnection()->prepare(
            "UPDATE users 
             SET credits = credits + ?
             WHERE user_id = ?"
        );
        $stmt->execute([$amount, $userId]);

        static::logCredit($userId, $amount, $type, $reason, $tripId);
    }

    public static function recentTrips($userId, $limit = 10)
    {
        $limit = (int) $limit;

        $stmt = static::getConnection()->prepare(
            "SELECT t.*,
                cd.name AS ville_depart,
                ca.name AS ville_arrivee,
                CASE WHEN t.chauffeur_id = ? THEN 'chauffeur' ELSE 'passager' END as role_trajet
         FROM trips t
         JOIN cities cd ON t.city_depart_id = cd.city_id
         JOIN cities ca ON t.city_arrival_id = ca.city_id
         LEFT JOIN trip_participants tp
            ON t.trip_id = tp.trip_id AND tp.user_id = ?
         WHERE t.chauffeur_id = ? OR tp.user_id = ?
         ORDER BY t.departure_datetime DESC
         LIMIT {$limit}"
        );

        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public static function logCredit($userId, $amount, $type, $reason = null, $tripId = null)
    {
        $stmt = static::getConnection()->prepare(
            "INSERT INTO credit_logs
             (user_id, amount, type, reason, trip_id, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $amount, $type, $reason, $tripId]);
    }

    public static function updatePhoto($userId, $photoName)
    {
        $stmt = static::getConnection()->prepare(
            "UPDATE users SET photo = ? WHERE user_id = ?"
        );
        $stmt->execute([$photoName, $userId]);
        return $stmt;
    }
}

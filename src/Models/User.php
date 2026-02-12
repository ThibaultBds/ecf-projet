<?php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    protected static $table = 'users';

    /**
     * Trouver un utilisateur par email
     */
    public static function findByEmail($email)
    {
        return static::findBy('email', strtolower(trim($email)));
    }

    /**
     * Trouver un utilisateur par pseudo
     */
    public static function findByPseudo($pseudo)
    {
        return static::findBy('pseudo', $pseudo);
    }

    /**
     * Vérifier si un email ou pseudo existe déjà
     */
    public static function exists($email, $pseudo)
    {
        $stmt = static::query(
            "SELECT id FROM users WHERE email = ? OR pseudo = ? LIMIT 1",
            [strtolower(trim($email)), trim($pseudo)]
        );
        return $stmt->fetch() !== false;
    }

    /**
     * Déduire des crédits
     */
    public static function deductCredits($userId, $amount)
    {
        static::query(
            "UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?",
            [$amount, $userId, $amount]
        );
    }

    /**
     * Ajouter des crédits
     */
    public static function addCredits($userId, $amount)
    {
        static::query(
            "UPDATE users SET credits = credits + ? WHERE id = ?",
            [$amount, $userId]
        );
    }

    /**
     * Récupérer les derniers trajets d'un utilisateur
     */
    public static function recentTrips($userId, $limit = 10)
    {
        return static::query(
            "SELECT t.*,
                    CASE WHEN t.chauffeur_id = ? THEN 'chauffeur' ELSE 'passager' END as role_trajet
             FROM trips t
             LEFT JOIN trip_participants tp ON t.id = tp.trip_id AND tp.passager_id = ?
             WHERE t.chauffeur_id = ? OR tp.passager_id = ?
             ORDER BY t.date_depart DESC
             LIMIT ?",
            [$userId, $userId, $userId, $userId, $limit]
        )->fetchAll();
    }
}

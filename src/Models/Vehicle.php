<?php

namespace App\Models;


class Vehicle extends BaseModel
{
    protected static $table = 'vehicles';
    protected static $primaryKey = 'vehicle_id';

    /**
     * Récupérer les véhicules d'un utilisateur
     */
    public static function byUser($userId)
    {
        return static::where('user_id', $userId);
    }

    /**
     * Récupérer le premier véhicule d'un utilisateur
     */
    public static function firstByUser($userId)
    {
        return static::findBy('user_id', $userId);
    }

    /**
     * Vérifier qu'un véhicule appartient à un utilisateur
     */
    public static function belongsToUser($vehicleId, $userId)
    {
        $vehicle = static::find($vehicleId);
        return $vehicle && $vehicle['user_id'] == $userId;
    }

    /**
     * Valider le format de la plaque d'immatriculation
     */
    public static function isValidPlate($plate)
    {
        return preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', strtoupper($plate));
    }
}

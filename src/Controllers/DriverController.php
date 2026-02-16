<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Database;
use App\Core\MongoDB;
use App\Models\User;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\BaseModel;
use Exception;

class DriverController extends BaseController
{
    /**
     * Dashboard chauffeur
     */
    public function dashboard()
    {
        $userId = AuthManager::id();
        $user = User::find($userId);
        $trips = Trip::byDriver($userId);

        $this->render('driver/dashboard', [
            'title' => 'Espace Chauffeur - EcoRide',
            'user' => $user,
            'trips' => $trips
        ]);
    }

    /**
     * Formulaire de création de trajet
     */
    public function createTrip()
    {
        $userId = AuthManager::id();
        $user = User::find($userId);
        $vehicles = Vehicle::byUser($userId);

        $this->render('driver/create-trip', [
            'title' => 'Créer un trajet - EcoRide',
            'user' => $user,
            'vehicles' => $vehicles,
            'error' => '',
            'success' => ''
        ]);
    }

    /**
     * Trouver ou créer une ville, retourne son city_id
     */
    private function findOrCreateCity($cityName)
    {
        $result = BaseModel::query(
            "SELECT city_id FROM cities WHERE name = ? LIMIT 1",
            [trim($cityName)]
        )->fetch();

        if ($result) {
            return $result['city_id'];
        }

        BaseModel::query(
            "INSERT INTO cities (name) VALUES (?)",
            [trim($cityName)]
        );

        return Database::getInstance()->getConnection()->lastInsertId();
    }

    /**
     * Enregistrer un nouveau trajet
     */
    public function storeTrip()
    {
        $userId = AuthManager::id();
        $user = User::find($userId);

        $villeDepart = trim($_POST['ville_depart'] ?? '');
        $villeArrivee = trim($_POST['ville_arrivee'] ?? '');
        $dateDepart = $_POST['date_depart'] ?? '';
        $heureDepart = $_POST['heure_depart'] ?? '';
        $heureArrivee = $_POST['heure_arrivee'] ?? '';
        $places = (int) ($_POST['places'] ?? 0);
        $prix = (float) ($_POST['prix'] ?? 0);

        // Validation
        if (
            empty($villeDepart) ||
            empty($villeArrivee) ||
            empty($dateDepart) ||
            empty($heureDepart) ||
            empty($heureArrivee)) 
            {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => 'Veuillez remplir tous les champs obligatoires.',
                'success' => ''
            ]);
        }

        if ($prix < 1 || $prix > 100) {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => 'Le prix doit être entre 1€ et 100€.',
                'success' => ''
            ]);
        }

        if ($places < 1 || $places > 4) {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => 'Le nombre de places doit être entre 1 et 4.',
                'success' => ''
            ]);
        }

        $departureDateTime = $dateDepart . ' ' . $heureDepart . ':00';
        if (strtotime($departureDateTime) <= time()) {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => 'La date de départ doit être dans le futur.',
                'success' => ''
            ]);
        }

        // Vérifier les crédits (prix + 2€ frais plateforme)
        $totalCost = $prix + 2;
        if ($user['credits'] < $totalCost) {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => "Crédits insuffisants. Vous avez {$user['credits']} crédits, il en faut {$totalCost} (prix + 2€ frais).",
                'success' => ''
            ]);
        }

        // Véhicule sélectionné
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            if (!Vehicle::belongsToUser($vehicleId, $userId)) {
                return $this->render('driver/create-trip', [
                    'title' => 'Créer un trajet - EcoRide',
                    'user' => $user,
                    'vehicles' => Vehicle::byUser($userId),
                    'error' => 'Véhicule invalide.',
                    'success' => ''
                ]);
            }
        } else {
            $vehicle = Vehicle::firstByUser($userId);
            if (!$vehicle) {
                return $this->render('driver/create-trip', [
                    'title' => 'Créer un trajet - EcoRide',
                    'user' => $user,
                    'vehicles' => [],
                    'error' => 'Vous devez d\'abord ajouter un véhicule.',
                    'success' => ''
                ]);
            }
            $vehicleId = $vehicle['vehicle_id'];
        }

        try {
            BaseModel::beginTransaction();

            // Trouver ou créer les villes
            $cityDepartId = $this->findOrCreateCity($villeDepart);
            $cityArrivalId = $this->findOrCreateCity($villeArrivee);

            // Heure d'arrivée estimée (+2h)
            $arrivalDateTime = $dateDepart . ' ' . $heureArrivee . ':00';

            if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'vehicles' => Vehicle::byUser($userId),
                'error' => "L'heure d'arrivée doit être après l'heure de départ.",
                'success' => ''
    ]);
}


            Trip::create([
                'chauffeur_id' => $userId,
                'vehicle_id' => $vehicleId,
                'city_depart_id' => $cityDepartId,
                'city_arrival_id' => $cityArrivalId,
                'departure_datetime' => $departureDateTime,
                'arrival_datetime' => $arrivalDateTime,
                'price' => $prix,
                'available_seats' => $places,
                'status' => 'scheduled'
            ]);

            User::deductCredits($userId, $prix, 'debit', 'Création trajet', null);
            User::deductCredits($userId, 2, 'platform_fee', 'Frais plateforme création', null);
            $_SESSION['user']['credits'] -= $totalCost;

            BaseModel::commit();

            header('Location: /driver/dashboard');
            exit;
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur création trajet : " . $e->getMessage());
            return $this->render('driver/create-trip', [
                'title' => 'Créer un trajet - EcoRide',
                'user' => $user,
                'error' => 'Une erreur est survenue lors de la création du trajet.',
                'success' => ''
            ]);
        }
    }

    /**
     * Afficher les préférences (stockées dans MongoDB)
     */
    public function preferences()
    {
        $userId = AuthManager::id();
        $mongo = MongoDB::getInstance();
        $prefs = $mongo->findOne('driver_preferences', ['user_id' => $userId]);

        $this->render('driver/preferences', [
            'title' => 'Mes Préférences - EcoRide',
            'prefs' => $prefs ?? [],
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? ''
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    /**
     * Sauvegarder les préférences dans MongoDB
     */
    public function savePreferences()
    {
        $userId = AuthManager::id();

        $prefs = [
            'user_id' => $userId,
            'fumeur' => $_POST['fumeur'] ?? 'non',
            'animaux' => $_POST['animaux'] ?? 'non',
            'musique' => $_POST['musique'] ?? 'non',
            'discussion' => $_POST['discussion'] ?? 'un_peu',
            'custom_preferences' => array_filter(
                array_map('trim', explode("\n", $_POST['custom_preferences'] ?? '')),
                fn($line) => $line !== ''
            ),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $mongo = MongoDB::getInstance();
            $mongo->upsert('driver_preferences', ['user_id' => $userId], $prefs);
            $_SESSION['flash_success'] = 'Préférences sauvegardées avec succès.';
        } catch (Exception $e) {
            error_log("Erreur MongoDB préférences : " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de la sauvegarde des préférences.';
        }

        header('Location: /driver/preferences');
        exit;
    }

    /**
     * Récupérer les préférences d'un conducteur (statique, pour les vues)
     */
    public static function getDriverPreferences(int $userId): array
    {
        try {
            $mongo = MongoDB::getInstance();
            return $mongo->findOne('driver_preferences', ['user_id' => $userId]) ?? [];
        } catch (Exception $e) {
            return [];
        }
    }
    
}

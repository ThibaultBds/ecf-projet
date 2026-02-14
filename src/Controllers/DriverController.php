<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Database;
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

        $this->render('driver/create-trip', [
            'title' => 'Créer un trajet - EcoRide',
            'user' => $user,
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
        $places = (int) ($_POST['places'] ?? 0);
        $prix = (float) ($_POST['prix'] ?? 0);

        // Validation
        if (empty($villeDepart) || empty($villeArrivee) || empty($dateDepart) || empty($heureDepart)) {
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

        // Récupérer ou créer un véhicule
        $vehicle = Vehicle::firstByUser($userId);
        if (!$vehicle) {
            $vehicleId = Vehicle::create([
                'user_id' => $userId,
                'brand' => 'Non renseigné',
                'model' => 'Non renseigné',
                'color' => 'Non renseigné',
                'license_plate' => 'AA-000-AA',
                'energy_type' => 'essence',
                'seats_available' => $places,
                'registration_date' => date('Y-m-d')
            ]);
        } else {
            $vehicleId = $vehicle['vehicle_id'];
        }

        try {
            BaseModel::beginTransaction();

            // Trouver ou créer les villes
            $cityDepartId = $this->findOrCreateCity($villeDepart);
            $cityArrivalId = $this->findOrCreateCity($villeArrivee);

            // Estimer l'heure d'arrivée (+2h par défaut)
            $arrivalDateTime = date('Y-m-d H:i:s', strtotime($departureDateTime) + 7200);

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

            User::deductCredits($userId, $totalCost);
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
     * Afficher les préférences (table non disponible)
     */
    public function preferences()
    {
        $this->render('driver/preferences', [
            'title' => 'Mes Préférences - EcoRide',
            'prefs' => [],
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => 'Les préférences ne sont pas encore disponibles.'
        ]);
        unset($_SESSION['flash_success']);
    }

    /**
     * Sauvegarder les préférences (table non disponible)
     */
    public function savePreferences()
    {
        $_SESSION['flash_error'] = 'Les préférences ne sont pas encore disponibles.';
        header('Location: /driver/preferences');
        exit;
    }
}

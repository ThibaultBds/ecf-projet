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
    public function dashboard()
    {
        $userId = AuthManager::id();
        $user = User::find($userId);
        $trips = Trip::byDriver($userId);

        $upcomingStatuses = ['scheduled', 'started'];
        $upcoming_trips = array_values(array_filter($trips, fn($t) => in_array($t['status'], $upcomingStatuses)));
        $past_trips     = array_values(array_filter($trips, fn($t) => !in_array($t['status'], $upcomingStatuses)));

        $this->render('driver/dashboard', [
            'title'         => 'Espace Chauffeur - EcoRide',
            'user'          => $user,
            'trips'         => $trips,
            'upcoming_trips' => $upcoming_trips,
            'past_trips'    => $past_trips,
        ]);
    }

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

    private function renderCreateTrip(array $user, array $vehicles, string $error = '')
    {
        return $this->render('driver/create-trip', [
            'title'    => 'Créer un trajet - EcoRide',
            'user'     => $user,
            'vehicles' => $vehicles,
            'error'    => $error,
            'success'  => ''
        ]);
    }

    public function storeTrip()
    {
        $userId   = AuthManager::id();
        $user     = User::find($userId);
        $vehicles = Vehicle::byUser($userId);

        $villeDepart  = trim($_POST['ville_depart'] ?? '');
        $villeArrivee = trim($_POST['ville_arrivee'] ?? '');
        $dateDepart   = $_POST['date_depart'] ?? '';
        $heureDepart  = $_POST['heure_depart'] ?? '';
        $heureArrivee = $_POST['heure_arrivee'] ?? '';
        $places       = (int) ($_POST['places'] ?? 0);
        $prix         = (float) ($_POST['prix'] ?? 0);

        if (!$villeDepart || !$villeArrivee || !$dateDepart || !$heureDepart || !$heureArrivee) {
            return $this->renderCreateTrip($user, $vehicles, 'Veuillez remplir tous les champs obligatoires.');
        }
        if ($prix < 1 || $prix > 100) {
            return $this->renderCreateTrip($user, $vehicles, 'Le prix doit être entre 1€ et 100€.');
        }
        if ($places < 1 || $places > 4) {
            return $this->renderCreateTrip($user, $vehicles, 'Le nombre de places doit être entre 1 et 4.');
        }

        $departureDateTime = $dateDepart . ' ' . $heureDepart . ':00';
        if (strtotime($departureDateTime) <= time()) {
            return $this->renderCreateTrip($user, $vehicles, 'La date de départ doit être dans le futur.');
        }

        $totalCost = $prix + 2;
        if ($user['credits'] < $totalCost) {
            return $this->renderCreateTrip($user, $vehicles, "Crédits insuffisants. Vous avez {$user['credits']} crédits, il en faut {$totalCost} (prix + 2€ frais).");
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            if (!Vehicle::belongsToUser($vehicleId, $userId)) {
                return $this->renderCreateTrip($user, $vehicles, 'Véhicule invalide.');
            }
        } else {
            $vehicle = Vehicle::firstByUser($userId);
            if (!$vehicle) {
                return $this->renderCreateTrip($user, [], 'Vous devez d\'abord ajouter un véhicule.');
            }
            $vehicleId = $vehicle['vehicle_id'];
        }

        $arrivalDateTime = $dateDepart . ' ' . $heureArrivee . ':00';
        if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
            return $this->renderCreateTrip($user, $vehicles, "L'heure d'arrivée doit être après l'heure de départ.");
        }

        try {
            BaseModel::beginTransaction();

            Trip::create([
                'chauffeur_id'       => $userId,
                'vehicle_id'         => $vehicleId,
                'city_depart_id'     => $this->findOrCreateCity($villeDepart),
                'city_arrival_id'    => $this->findOrCreateCity($villeArrivee),
                'departure_datetime' => $departureDateTime,
                'arrival_datetime'   => $arrivalDateTime,
                'price'              => $prix,
                'available_seats'    => $places,
                'status'             => 'scheduled'
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
            return $this->renderCreateTrip($user, $vehicles, 'Une erreur est survenue lors de la création du trajet.');
        }
    }

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

<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Database;
use App\Core\MongoDB;
use App\Models\BaseModel;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Exception;

class DriverController extends BaseController
{
    public function dashboard()
    {
        $userId = AuthManager::id();
        $userModel = new User();
        $tripModel = new Trip();

        $user = $userModel->find($userId);
        $trips = $tripModel->byDriver($userId);

        $upcomingStatuses = ['scheduled', 'started'];
        $upcomingTrips = array_values(array_filter($trips, fn($trip) => in_array($trip['status'], $upcomingStatuses, true)));
        $pastTrips = array_values(array_filter($trips, fn($trip) => !in_array($trip['status'], $upcomingStatuses, true)));

        $this->render('driver/dashboard', [
            'title' => 'Espace Chauffeur - EcoRide',
            'user' => $user,
            'trips' => $trips,
            'upcoming_trips' => $upcomingTrips,
            'past_trips' => $pastTrips,
        ]);
    }

    public function createTrip()
    {
        $userId = AuthManager::id();
        $userModel = new User();
        $vehicleModel = new Vehicle();

        $this->render('driver/create-trip', [
            'title' => 'Creer un trajet - EcoRide',
            'user' => $userModel->find($userId),
            'vehicles' => $vehicleModel->byUser($userId),
            'error' => '',
            'success' => '',
        ]);
    }

    private function findOrCreateCity(string $cityName): int
    {
        $result = BaseModel::query(
            'SELECT city_id FROM cities WHERE name = ? LIMIT 1',
            [trim($cityName)]
        )->fetch();

        if ($result) {
            return (int) $result['city_id'];
        }

        BaseModel::query('INSERT INTO cities (name) VALUES (?)', [trim($cityName)]);
        return (int) Database::getInstance()->getConnection()->lastInsertId();
    }

    private function renderCreateTrip(array $user, array $vehicles, string $error = '')
    {
        return $this->render('driver/create-trip', [
            'title' => 'Creer un trajet - EcoRide',
            'user' => $user,
            'vehicles' => $vehicles,
            'error' => $error,
            'success' => '',
        ]);
    }

    public function storeTrip()
    {
        $userId = AuthManager::id();
        $userModel = new User();
        $tripModel = new Trip();
        $vehicleModel = new Vehicle();

        $user = $userModel->find($userId);
        $vehicles = $vehicleModel->byUser($userId);

        $cityDeparture = trim($_POST['ville_depart'] ?? '');
        $cityArrival = trim($_POST['ville_arrivee'] ?? '');
        $departureDate = $_POST['date_depart'] ?? '';
        $departureTime = $_POST['heure_depart'] ?? '';
        $arrivalTime = $_POST['heure_arrivee'] ?? '';
        $seats = (int) ($_POST['places'] ?? 0);
        $price = (float) ($_POST['prix'] ?? 0);

        if (!$cityDeparture || !$cityArrival || !$departureDate || !$departureTime || !$arrivalTime) {
            return $this->renderCreateTrip($user, $vehicles, 'Veuillez remplir tous les champs obligatoires.');
        }

        if ($price < 1 || $price > 100) {
            return $this->renderCreateTrip($user, $vehicles, 'Le prix doit etre entre 1 et 100.');
        }

        if ($seats < 1 || $seats > 4) {
            return $this->renderCreateTrip($user, $vehicles, 'Le nombre de places doit etre entre 1 et 4.');
        }

        $departureDateTime = $departureDate . ' ' . $departureTime . ':00';
        if (strtotime($departureDateTime) <= time()) {
            return $this->renderCreateTrip($user, $vehicles, 'La date de depart doit etre dans le futur.');
        }

        $totalCost = $price + 2;
        if ((int) ($user['credits'] ?? 0) < $totalCost) {
            return $this->renderCreateTrip($user, $vehicles, "Credits insuffisants. Il faut {$totalCost} credits.");
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            if (!$vehicleModel->belongsToUser($vehicleId, $userId)) {
                return $this->renderCreateTrip($user, $vehicles, 'Vehicule invalide.');
            }
        } else {
            $vehicle = $vehicleModel->firstByUser($userId);
            if (!$vehicle) {
                return $this->renderCreateTrip($user, [], 'Vous devez d abord ajouter un vehicule.');
            }
            $vehicleId = (int) $vehicle['vehicle_id'];
        }

        $arrivalDateTime = $departureDate . ' ' . $arrivalTime . ':00';
        if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
            return $this->renderCreateTrip($user, $vehicles, 'L heure d arrivee doit etre apres l heure de depart.');
        }

        try {
            BaseModel::beginTransaction();

            $tripModel->create([
                'chauffeur_id' => $userId,
                'vehicle_id' => $vehicleId,
                'city_depart_id' => $this->findOrCreateCity($cityDeparture),
                'city_arrival_id' => $this->findOrCreateCity($cityArrival),
                'departure_datetime' => $departureDateTime,
                'arrival_datetime' => $arrivalDateTime,
                'price' => $price,
                'available_seats' => $seats,
                'status' => 'scheduled',
            ]);

            $userModel->deductCredits($userId, $price, 'debit', 'Creation trajet', null);
            $userModel->deductCredits($userId, 2, 'platform_fee', 'Frais plateforme creation', null);
            $_SESSION['user']['credits'] = (int) ($_SESSION['user']['credits'] ?? 0) - (int) $totalCost;

            BaseModel::commit();

            header('Location: /driver/dashboard');
            exit;
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log('Erreur creation trajet : ' . $e->getMessage());
            return $this->renderCreateTrip($user, $vehicles, 'Une erreur est survenue lors de la creation du trajet.');
        }
    }

    public function preferences()
    {
        $userId = AuthManager::id();
        $mongo = MongoDB::getInstance();
        $prefs = $mongo->findOne('driver_preferences', ['user_id' => $userId]);

        $this->render('driver/preferences', [
            'title' => 'Mes Preferences - EcoRide',
            'prefs' => $prefs ?? [],
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? '',
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
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $mongo = MongoDB::getInstance();
            $mongo->upsert('driver_preferences', ['user_id' => $userId], $prefs);
            $_SESSION['flash_success'] = 'Preferences sauvegardees avec succes.';
        } catch (Exception $e) {
            error_log('Erreur MongoDB preferences : ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de la sauvegarde des preferences.';
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

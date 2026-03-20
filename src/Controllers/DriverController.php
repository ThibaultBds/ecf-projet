<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\MongoDB;
use App\Models\User;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Services\TripService;
use Exception;

class DriverController extends BaseController
{
    public function dashboard()
    {
        $userId   = AuthManager::id();
        $userRepo = new UserRepository();
        $tripRepo = new TripRepository();

        $user  = $userRepo->findById($userId);
        $trips = $tripRepo->byDriver($userId);

        $upcomingStatuses = ['scheduled', 'started'];
        $upcomingTrips    = array_values(array_filter($trips, fn($t) => in_array($t->status, $upcomingStatuses, true)));
        $pastTrips        = array_values(array_filter($trips, fn($t) => !in_array($t->status, $upcomingStatuses, true)));

        $this->render('driver/dashboard', [
            'title'          => 'Espace Chauffeur - EcoRide',
            'user'           => $user,
            'trips'          => $trips,
            'upcoming_trips' => $upcomingTrips,
            'past_trips'     => $pastTrips,
        ]);
    }

    public function createTrip()
    {
        $userId      = AuthManager::id();
        $userRepo    = new UserRepository();
        $vehicleRepo = new VehicleRepository();

        $this->render('driver/create-trip', [
            'title'    => 'Créer un trajet - EcoRide',
            'user'     => $userRepo->findById($userId),
            'vehicles' => $vehicleRepo->byUser($userId),
            'error'    => '',
            'success'  => '',
        ]);
    }

    private function renderCreateTrip(User $user, array $vehicles, string $error = '')
    {
        return $this->render('driver/create-trip', [
            'title'    => 'Créer un trajet - EcoRide',
            'user'     => $user,
            'vehicles' => $vehicles,
            'error'    => $error,
            'success'  => '',
        ]);
    }

    public function storeTrip()
    {
        $userId      = AuthManager::id();
        $userRepo    = new UserRepository();
        $vehicleRepo = new VehicleRepository();
        $tripService = new TripService();

        $user     = $userRepo->findById($userId);
        $vehicles = $vehicleRepo->byUser($userId);

        $cityDeparture = trim($_POST['ville_depart'] ?? '');
        $cityArrival   = trim($_POST['ville_arrivee'] ?? '');
        $departureDate = $_POST['date_depart'] ?? '';
        $departureTime = $_POST['heure_depart'] ?? '';
        $arrivalTime   = $_POST['heure_arrivee'] ?? '';
        $seats         = (int) ($_POST['places'] ?? 0);
        $price         = (float) ($_POST['prix'] ?? 0);

        if (!$cityDeparture || !$cityArrival || !$departureDate || !$departureTime || !$arrivalTime) {
            return $this->renderCreateTrip($user, $vehicles, 'Veuillez remplir tous les champs obligatoires.');
        }

        if ($price < 1 || $price > 100) {
            return $this->renderCreateTrip($user, $vehicles, 'Le prix doit être entre 1 et 100.');
        }

        if ($seats < 1 || $seats > 4) {
            return $this->renderCreateTrip($user, $vehicles, 'Le nombre de places doit être entre 1 et 4.');
        }

        $departureDateTime = $departureDate . ' ' . $departureTime . ':00';
        if (strtotime($departureDateTime) <= time()) {
            return $this->renderCreateTrip($user, $vehicles, 'La date de départ doit être dans le futur.');
        }

        $totalCost = 2;
        if ($user->credits < $totalCost) {
            return $this->renderCreateTrip($user, $vehicles, "Crédits insuffisants. Il faut {$totalCost} crédits.");
        }

        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            if (!$vehicleRepo->belongsToUser($vehicleId, $userId)) {
                return $this->renderCreateTrip($user, $vehicles, 'Vehicule invalide.');
            }
        } else {
            $vehicle = $vehicleRepo->firstByUser($userId);
            if (!$vehicle) {
                return $this->renderCreateTrip($user, [], "Vous devez d'abord ajouter un véhicule.");
            }
            $vehicleId = $vehicle->vehicleId;
        }

        $arrivalDateTime = $departureDate . ' ' . $arrivalTime . ':00';
        if (strtotime($arrivalDateTime) <= strtotime($departureDateTime)) {
            return $this->renderCreateTrip($user, $vehicles, "L'heure d'arrivée doit être après l'heure de départ.");
        }

        try {
            $tripService->createTrip($userId, $vehicleId, [
                'city_depart'        => $cityDeparture,
                'city_arrival'       => $cityArrival,
                'departure_datetime' => $departureDateTime,
                'arrival_datetime'   => $arrivalDateTime,
                'price'              => $price,
                'available_seats'    => $seats,
            ]);

            $_SESSION['user']['credits'] = (int) ($_SESSION['user']['credits'] ?? 0) - (int) $totalCost;
            header('Location: /driver/dashboard');
            exit;
        } catch (Exception $e) {
            error_log('Erreur creation trajet : ' . $e->getMessage());
            return $this->renderCreateTrip($user, $vehicles, 'Une erreur est survenue lors de la creation du trajet.');
        }
    }

    public function preferences()
    {
        $userId = AuthManager::id();
        $mongo  = MongoDB::getInstance();
        $prefs  = $mongo->findOne('driver_preferences', ['user_id' => $userId]);

        $this->render('driver/preferences', [
            'title'   => 'Mes Préférences - EcoRide',
            'prefs'   => $prefs ?? [],
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => $_SESSION['flash_error'] ?? '',
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function savePreferences()
    {
        $userId = AuthManager::id();

        $prefs = [
            'user_id'            => $userId,
            'fumeur'             => $_POST['fumeur'] ?? 'non',
            'animaux'            => $_POST['animaux'] ?? 'non',
            'musique'            => $_POST['musique'] ?? 'non',
            'discussion'         => $_POST['discussion'] ?? 'un_peu',
            'custom_preferences' => array_filter(
                array_map('trim', explode("\n", $_POST['custom_preferences'] ?? '')),
                fn($line) => $line !== ''
            ),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $mongo = MongoDB::getInstance();
            $mongo->upsert('driver_preferences', ['user_id' => $userId], $prefs);
            $_SESSION['flash_success'] = 'Préférences sauvegardées avec succès.';
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

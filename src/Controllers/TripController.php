<?php

namespace App\Controllers;

use App\Controllers\DriverController;
use App\Core\Auth\AuthManager;
use App\Repositories\ReviewRepository;
use App\Repositories\TripParticipantRepository;
use App\Repositories\UserRepository;
use App\Services\TripService;

class TripController extends BaseController
{
    private TripService $tripService;

    public function __construct()
    {
        $this->tripService = new TripService();
    }

    public function index()
    {
        $filters    = $this->tripService->buildSearchFiltersFromQuery($_GET);
        $searchData = $this->tripService->searchTrips($filters);

        $this->render('trips/index', [
            'title'        => 'Covoiturages - EcoRide',
            'covoiturages' => $searchData['trips'],
            'filters'      => $searchData['filters'],
            'hasSearched'  => $searchData['hasSearched'],
            'nearestDate'  => $searchData['nearestDate'],
            'isDriver'     => !empty($_SESSION['user']['is_driver']),
            'isLoggedIn'   => AuthManager::check(),
        ]);
    }

    public function search()
    {
        $filters      = $this->tripService->buildSearchFiltersFromQuery($_GET);
        $covoiturages = $this->tripService->searchTripsForApi($filters);

        header('Content-Type: application/json');
        echo json_encode($covoiturages);
        exit;
    }

    public function show($id)
    {
        $covoiturage = $this->tripService->findTripWithDetails((int) $id);

        if (!$covoiturage) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Trajet non trouve']);
            return;
        }

        $reviewRepo = new ReviewRepository();
        $reviews    = $reviewRepo->byDriver($covoiturage->chauffeurId);

        $userCredit      = 0;
        $isParticipating = false;

        if (AuthManager::check()) {
            $userRepo        = new UserRepository();
            $participantRepo = new TripParticipantRepository();
            $user            = $userRepo->findById(AuthManager::id());
            $userCredit      = $user ? $user->credits : 0;
            $isParticipating = $participantRepo->isParticipating($id, AuthManager::id());
        }

        try {
            $driverPrefs = DriverController::getDriverPreferences((int) $covoiturage->chauffeurId);
        } catch (\Throwable $e) {
            $driverPrefs = [];
        }

        $this->render('trips/show', [
            'title'           => $covoiturage->villeDepart . ' -> ' . $covoiturage->villeArrivee . ' - EcoRide',
            'covoiturage'     => $covoiturage,
            'reviews'         => $reviews,
            'user_credit'     => $userCredit,
            'credit_requis'   => (int) $covoiturage->price,
            'isParticipating' => $isParticipating,
            'driverPrefs'     => $driverPrefs,
        ]);
    }

    public function myTrips()
    {
        $userId = AuthManager::id();
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $tripId = (int) ($_POST['trip_id'] ?? 0);

            if ($action === 'cancel_participation') {
                $this->tripService->cancelParticipation($tripId, $userId);
            } elseif ($action === 'update_trip_status') {
                $this->tripService->updateTripStatus($tripId, $userId, $_POST['status'] ?? '');
            } elseif ($action === 'validate_trip') {
                $this->tripService->validateTrip($tripId, $userId);
            } elseif ($action === 'report_problem') {
                $this->tripService->reportProblem($tripId, $userId, $_POST['problem_comment'] ?? '');
            }

            header('Location: /my-trips?success=Action effectuee avec succes');
            exit;
        }

        $isDriver          = !empty($_SESSION['user']['is_driver']);
        $tripsData         = $this->tripService->getMyTripsData($userId, $isDriver);
        $resolvedIncidents = $this->tripService->getResolvedIncidents($userId);

        $this->render('trips/my-trips', [
            'title'                   => 'Mes Trajets - EcoRide',
            'upcoming_conduits'       => $tripsData['upcoming_conduits'],
            'past_conduits'           => $tripsData['past_conduits'],
            'upcoming_participations' => $tripsData['upcoming_participations'],
            'past_participations'     => $tripsData['past_participations'],
            'resolvedIncidents'       => $resolvedIncidents,
            'error'                   => $error,
        ]);
    }
}

<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Auth\AuthManager;
use App\Services\TripService;

class TripApiController extends BaseController
{
    private TripService $tripService;

    public function __construct()
    {
        $this->tripService = new TripService();
    }

    public function join($id)
    {
        header('Content-Type: application/json');

        $userId = (int) AuthManager::id();
        $result = $this->tripService->joinTrip((int) $id, $userId);

        if (($result['success'] ?? false) && isset($result['new_credits'])) {
            $_SESSION['user']['credits'] = (int) $result['new_credits'];
        }

        echo json_encode($result);
    }

    public function cancel($id)
    {
        header('Content-Type: application/json');

        $userId = (int) AuthManager::id();
        $result = $this->tripService->cancelTrip((int) $id, $userId);

        echo json_encode($result);
    }
}

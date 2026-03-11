<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Models\Vehicle;

class VehicleController extends BaseController
{
    public function index()
    {
        $userId = AuthManager::id();
        $vehicleModel = new Vehicle();
        $vehicles = $vehicleModel->byUser($userId);

        $this->render('driver/vehicles', [
            'title' => 'Mes Véhicules - EcoRide',
            'vehicles' => $vehicles,
            'error' => $_SESSION['flash_error'] ?? '',
            'success' => $_SESSION['flash_success'] ?? ''
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function store()
    {
        $userId = AuthManager::id();
        $vehicleModel = new Vehicle();
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $licensePlate = strtoupper(trim($_POST['license_plate'] ?? ''));
        $energyType = $_POST['energy_type'] ?? 'essence';
        $seatsAvailable = (int) ($_POST['seats_available'] ?? 4);
        $registrationDate = $_POST['registration_date'] ?? date('Y-m-d');

        if (empty($brand) || empty($model) || empty($color) || empty($licensePlate)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
            header('Location: /driver/vehicles');
            exit;
        }

        if (!$vehicleModel->isValidPlate($licensePlate)) {
            $_SESSION['flash_error'] = 'Format de plaque invalide (ex: AB-123-CD).';
            header('Location: /driver/vehicles');
            exit;
        }

        if ($seatsAvailable < 1 || $seatsAvailable > 8) {
            $_SESSION['flash_error'] = 'Le nombre de places doit être entre 1 et 8.';
            header('Location: /driver/vehicles');
            exit;
        }

        try {
            $vehicleModel->create([
                'user_id' => $userId,
                'brand' => $brand,
                'model' => $model,
                'color' => $color,
                'license_plate' => $licensePlate,
                'energy_type' => $energyType,
                'seats_available' => $seatsAvailable,
                'registration_date' => $registrationDate
            ]);
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Cette plaque d\'immatriculation est déjà enregistrée.';
            header('Location: /driver/vehicles');
            exit;
        }

        $_SESSION['flash_success'] = 'Véhicule ajouté avec succès !';
        header('Location: /driver/vehicles');
        exit;
    }

    public function update()
    {
        $userId = AuthManager::id();
        $vehicleModel = new Vehicle();
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        if (!$vehicleModel->belongsToUser($vehicleId, $userId)) {
            $_SESSION['flash_error'] = 'Véhicule non trouvé.';
            header('Location: /driver/vehicles');
            exit;
        }

        $licensePlate = strtoupper(trim($_POST['license_plate'] ?? ''));
        if (!empty($licensePlate) && !$vehicleModel->isValidPlate($licensePlate)) {
            $_SESSION['flash_error'] = 'Format de plaque invalide.';
            header('Location: /driver/vehicles');
            exit;
        }

        $data = [];
        foreach (['brand', 'model', 'color', 'license_plate', 'energy_type'] as $field) {
            if (!empty($_POST[$field])) {
                $data[$field] = $field === 'license_plate' ? strtoupper(trim($_POST[$field])) : trim($_POST[$field]);
            }
        }
        if (isset($_POST['seats_available'])) {
            $data['seats_available'] = max(1, min(8, (int) $_POST['seats_available']));
        }

        if (!empty($data)) {
            $vehicleModel->update($vehicleId, $data);
        }

        $_SESSION['flash_success'] = 'Véhicule mis à jour !';
        header('Location: /driver/vehicles');
        exit;
    }

    public function destroy()
    {
        $userId = AuthManager::id();
        $vehicleModel = new Vehicle();
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        if (!$vehicleModel->belongsToUser($vehicleId, $userId)) {
            $_SESSION['flash_error'] = 'Véhicule non trouvé.';
            header('Location: /driver/vehicles');
            exit;
        }

        $vehicleModel->destroy($vehicleId);
        $_SESSION['flash_success'] = 'Véhicule supprimé.';
        header('Location: /driver/vehicles');
        exit;
    }
}

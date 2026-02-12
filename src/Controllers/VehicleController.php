<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Vehicle.php';
require_once __DIR__ . '/../Core/Auth/AuthManager.php';

class VehicleController extends BaseController
{
    public function index()
    {
        $userId = AuthManager::id();
        $vehicles = Vehicle::byUser($userId);

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
        $marque = trim($_POST['marque'] ?? '');
        $modele = trim($_POST['modele'] ?? '');
        $couleur = trim($_POST['couleur'] ?? '');
        $plaque = strtoupper(trim($_POST['plaque'] ?? ''));
        $energie = $_POST['energie'] ?? 'essence';
        $places = (int) ($_POST['places_disponibles'] ?? 4);

        if (empty($marque) || empty($modele) || empty($couleur) || empty($plaque)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
            header('Location: /driver/vehicles');
            exit;
        }

        if (!Vehicle::isValidPlate($plaque)) {
            $_SESSION['flash_error'] = 'Format de plaque invalide (ex: AB-123-CD).';
            header('Location: /driver/vehicles');
            exit;
        }

        if ($places < 1 || $places > 8) {
            $_SESSION['flash_error'] = 'Le nombre de places doit être entre 1 et 8.';
            header('Location: /driver/vehicles');
            exit;
        }

        Vehicle::create([
            'user_id' => $userId,
            'marque' => $marque,
            'modele' => $modele,
            'couleur' => $couleur,
            'plaque' => $plaque,
            'energie' => $energie,
            'places_disponibles' => $places
        ]);

        $_SESSION['flash_success'] = 'Véhicule ajouté avec succès !';
        header('Location: /driver/vehicles');
        exit;
    }

    public function update()
    {
        $userId = AuthManager::id();
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        if (!Vehicle::belongsToUser($vehicleId, $userId)) {
            $_SESSION['flash_error'] = 'Véhicule non trouvé.';
            header('Location: /driver/vehicles');
            exit;
        }

        $plaque = strtoupper(trim($_POST['plaque'] ?? ''));
        if (!empty($plaque) && !Vehicle::isValidPlate($plaque)) {
            $_SESSION['flash_error'] = 'Format de plaque invalide.';
            header('Location: /driver/vehicles');
            exit;
        }

        $data = [];
        foreach (['marque', 'modele', 'couleur', 'plaque', 'energie'] as $field) {
            if (!empty($_POST[$field])) {
                $data[$field] = $field === 'plaque' ? strtoupper(trim($_POST[$field])) : trim($_POST[$field]);
            }
        }
        if (isset($_POST['places_disponibles'])) {
            $data['places_disponibles'] = max(1, min(8, (int) $_POST['places_disponibles']));
        }

        if (!empty($data)) {
            Vehicle::update($vehicleId, $data);
        }

        $_SESSION['flash_success'] = 'Véhicule mis à jour !';
        header('Location: /driver/vehicles');
        exit;
    }

    public function destroy()
    {
        $userId = AuthManager::id();
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        if (!Vehicle::belongsToUser($vehicleId, $userId)) {
            $_SESSION['flash_error'] = 'Véhicule non trouvé.';
            header('Location: /driver/vehicles');
            exit;
        }

        Vehicle::destroy($vehicleId);
        $_SESSION['flash_success'] = 'Véhicule supprimé.';
        header('Location: /driver/vehicles');
        exit;
    }
}

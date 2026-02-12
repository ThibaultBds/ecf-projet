<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Trip.php';
require_once __DIR__ . '/../Models/Vehicle.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Core/Auth/AuthManager.php';

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
        $description = trim($_POST['description'] ?? '');

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

        $dateTimeDepart = $dateDepart . ' ' . $heureDepart . ':00';
        if (strtotime($dateTimeDepart) <= time()) {
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
            // Créer un véhicule par défaut
            $vehicleId = Vehicle::create([
                'user_id' => $userId,
                'marque' => 'Non renseigné',
                'modele' => 'Non renseigné',
                'couleur' => 'Non renseigné',
                'plaque' => 'AA-000-AA',
                'energie' => 'essence',
                'places_disponibles' => $places
            ]);
        } else {
            $vehicleId = $vehicle['id'];
        }

        try {
            BaseModel::beginTransaction();

            Trip::create([
                'chauffeur_id' => $userId,
                'vehicle_id' => $vehicleId,
                'ville_depart' => $villeDepart,
                'ville_arrivee' => $villeArrivee,
                'date_depart' => $dateTimeDepart,
                'prix' => $prix,
                'places_totales' => $places,
                'places_restantes' => $places,
                'description' => $description,
                'is_ecological' => ($vehicle && $vehicle['energie'] === 'electrique') ? 1 : 0,
                'status' => 'planifie'
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
     * Afficher les préférences
     */
    public function preferences()
    {
        $userId = AuthManager::id();
        $prefs = BaseModel::query(
            "SELECT * FROM user_preferences WHERE user_id = ?",
            [$userId]
        )->fetch();

        $this->render('driver/preferences', [
            'title' => 'Mes Préférences - EcoRide',
            'prefs' => $prefs ?: [],
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => ''
        ]);
        unset($_SESSION['flash_success']);
    }

    /**
     * Sauvegarder les préférences
     */
    public function savePreferences()
    {
        $userId = AuthManager::id();
        $musique = $_POST['musique'] ?? 'non';
        $animaux = $_POST['animaux'] ?? 'non';
        $discussion = $_POST['discussion'] ?? 'un_peu';
        $fumeur = $_POST['fumeur'] ?? 'non';

        try {
            BaseModel::query(
                "INSERT INTO user_preferences (user_id, musique, animaux, discussion, fumeur)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE musique=?, animaux=?, discussion=?, fumeur=?",
                [$userId, $musique, $animaux, $discussion, $fumeur,
                 $musique, $animaux, $discussion, $fumeur]
            );

            $_SESSION['flash_success'] = 'Préférences sauvegardées avec succès !';
        } catch (Exception $e) {
            error_log("Erreur préférences : " . $e->getMessage());
        }

        header('Location: /driver/preferences');
        exit;
    }
}

<?php
ob_start();
session_start();
require_once '../config/autoload.php';
useClass('Database');

// --- Exceptions personnalisées ---
class FormFieldException extends Exception {}
class PriceOrPlacesException extends Exception {}
class DateException extends Exception {}
class DatabaseException extends Exception {}

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// --- Traitement du formulaire de création de trajet ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_trip') {
    try {
        $ville_depart = trim($_POST['ville_depart'] ?? '');
        $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
        $date_depart = $_POST['date_depart'] ?? '';
        $heure_depart = $_POST['heure_depart'] ?? '';
        $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
        $places = isset($_POST['places']) ? intval($_POST['places']) : 0;
        $description = trim($_POST['description'] ?? '');

        if (empty($ville_depart) || empty($ville_arrivee) || empty($date_depart) || empty($heure_depart)) {
            throw new FormFieldException('Veuillez remplir tous les champs obligatoires.');
        }
        if ($prix < 1 || $prix > 100 || $places < 1 || $places > 4) {
            throw new PriceOrPlacesException('Le prix doit être compris entre 1 et 100€, et le nombre de places entre 1 et 4.');
        }

        $datetime_depart = $date_depart . ' ' . $heure_depart;
        if (strtotime($datetime_depart) <= time()) {
            throw new DateException('La date de départ doit être dans le futur.');
        }

        $pdo = getDatabase();

        $vehicle = getVehicleByUserId($user['id']);
        $vehicle_id = $vehicle ? $vehicle['id'] : createDefaultVehicle($user['id'], $places);

        $total_cost = $prix + 2;
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $current_credits = $stmt->fetchColumn();
        if ($current_credits < $total_cost) {
            throw new Exception('Crédits insuffisants. Vous avez ' . $current_credits . ' crédits, nécessaire ' . $total_cost . '.');
        }

        if (createTrip($user['id'], $vehicle_id, $ville_depart, $ville_arrivee, $datetime_depart, $prix, $places, $description)) {
            $stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
            $stmt->execute([$total_cost, $user['id']]);

            try {
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], 'Création trajet', "$ville_depart → $ville_arrivee", $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (Exception $e) {}

            $success = 'Trajet créé avec succès ! ' . $total_cost . ' crédits déduits.';
            $_POST = [];
        } else {
            throw new Exception('Erreur lors de la création du trajet.');
        }
    } catch (FormFieldException|PriceOrPlacesException|DateException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        $error = 'Une erreur inattendue est survenue.';
    }
}

// --- Récupérer les trajets du chauffeur ---
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare(
        "SELECT t.*, COUNT(tp.id) as participants
         FROM trips t
         LEFT JOIN trip_participants tp ON t.id = tp.trip_id
         WHERE t.chauffeur_id = ?
         GROUP BY t.id
         ORDER BY t.date_depart DESC"
    );
    $stmt->execute([$user['id']]);
    $mes_trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mes_trajets = [];
    $error = $error ?: "Erreur lors du chargement de vos trajets.";
}

function getStatusColor($status) {
    return [
        'planifie' => '#00b894',
        'en_cours' => '#f39c12',
        'termine'  => '#636e72',
        'annule'   => '#e74c3c'
    ][$status] ?? '#b2bec3';
}
function getStatusLabel($status) {
    return [
        'planifie' => 'Planifié',
        'en_cours' => 'En cours',
        'termine'  => 'Terminé',
        'annule'   => 'Annulé'
    ][$status] ?? 'Planifié';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Chauffeur - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css?v=2025">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header"></header>

    <main class="driver-dashboard">
        <div class="driver-card">
            <h2><span class="material-icons">drive_eta</span> Espace Chauffeur</h2>

            <?php if ($success): ?><div class="message-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <h3>Proposer un nouveau trajet</h3>

            <form method="POST" class="driver-form" novalidate autocomplete="off">
                <input type="hidden" name="action" value="create_trip">

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_depart">Ville de départ *</label>
                        <input type="text" id="ville_depart" name="ville_depart" required list="villes" placeholder="Ex: Paris">
                    </div>
                    <div class="form-group">
                        <label for="ville_arrivee">Ville d'arrivée *</label>
                        <input type="text" id="ville_arrivee" name="ville_arrivee" required list="villes" placeholder="Ex: Lyon">
                    </div>
                </div>

                <datalist id="villes">
                    <option value="Paris">
                    <option value="Lyon">
                    <option value="Marseille">
                    <option value="Nice">
                    <option value="Toulouse">
                    <option value="Bordeaux">
                    <option value="Lille">
                    <option value="Nantes">
                    <option value="Strasbourg">
                    <option value="Dijon">
                </datalist>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_depart">Date de départ *</label>
                        <input type="date" id="date_depart" name="date_depart" required>
                    </div>
                    <div class="form-group">
                        <label for="heure_depart">Heure de départ *</label>
                        <input type="time" id="heure_depart" name="heure_depart" required>
                    </div>
                    <div class="form-group">
                        <label for="places">Nombre de places *</label>
                        <select id="places" name="places" required>
                            <option value="">Choisir...</option>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> place<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prix">Prix par personne (€) *</label>
                    <input type="number" id="prix" name="prix" step="0.5" min="1" max="100" required placeholder="Ex: 15.50">
                    <small class="form-hint"><span class="material-icons">info</span> 2 crédits seront prélevés par la plateforme</small>
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea id="description" name="description" rows="3" maxlength="500" placeholder="Détails sur le trajet, conditions, etc."></textarea>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn-primary btn-large">
                        <span class="material-icons">add_road</span> Créer le trajet
                    </button>
                </div>
            </form>
        </div>

        <div class="driver-card">
            <h3>Mes trajets (<?= count($mes_trajets) ?>)</h3>

            <?php if (empty($mes_trajets)): ?>
                <div class="empty-state">
                    <span class="material-icons">route</span>
                    <p>Aucun trajet créé pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($mes_trajets as $trajet): ?>
                    <div class="trip-card">
                        <h4><?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?></h4>
                        <small>
                            <span class="material-icons">schedule</span>
                            <?= date('d/m/Y à H:i', strtotime($trajet['date_depart'])) ?>
                        </small>
                        <p><?= (int)$trajet['participants'] ?> participant(s) • <?= (int)$trajet['places_restantes'] ?> place(s) restante(s)</p>
                        <p><strong><?= number_format($trajet['prix'], 2) ?> €</strong></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="back-link">
                <a href="/frontend/public/pages/profil.php">← Retour au profil</a>
            </div>
        </div>
    </main>

    <script>
        window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    </script>
    <script src="../assets/js/navbar.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

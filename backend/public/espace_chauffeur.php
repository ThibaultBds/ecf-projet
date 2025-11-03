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
    header('Location: /pages/login_secure.php');
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
    <link rel="stylesheet" href="/assets/css/style.css?v=2025">
    <link rel="stylesheet" href="/assets/css/pages.css?v=2025">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1>
            <a href="/pages/index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
    </header>

    <main class="driver-dashboard">
        <div class="driver-card">
            <div class="page-header">
                <h2><span class="material-icons">drive_eta</span> Espace Chauffeur</h2>
                <p>Proposez des trajets écologiques et gagnez des crédits</p>
            </div>

            <?php if ($success): ?>
                <div class="message-success" id="success-message">
                    <span class="material-icons">check_circle</span>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message-error">
                    <span class="material-icons">error</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="driver-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-icons">account_balance_wallet</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php
                            try {
                                $pdo = getDatabase();
                                $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
                                $stmt->execute([$user['id']]);
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) {
                                echo '0';
                            }
                        ?></div>
                        <div class="stat-label">Crédits disponibles</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-icons">route</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?= count($mes_trajets) ?></div>
                        <div class="stat-label">Trajets créés</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-icons">group</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php
                            $totalParticipants = 0;
                            foreach ($mes_trajets as $trajet) {
                                $totalParticipants += (int)$trajet['participants'];
                            }
                            echo $totalParticipants;
                        ?></div>
                        <div class="stat-label">Passagers transportés</div>
                    </div>
                </div>
            </div>

            <h3 style="margin-top: 40px; color: #2d3436;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 10px;">add_road</span>
                Proposer un nouveau trajet
            </h3>

            <form method="POST" class="driver-form" id="trip-form" novalidate autocomplete="off">
                <input type="hidden" name="action" value="create_trip">

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_depart">Ville de départ *</label>
                        <input type="text" id="ville_depart" name="ville_depart" required list="villes"
                               placeholder="Ex: Paris" value="<?= htmlspecialchars($_POST['ville_depart'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_arrivee">Ville d'arrivée *</label>
                        <input type="text" id="ville_arrivee" name="ville_arrivee" required list="villes"
                               placeholder="Ex: Lyon" value="<?= htmlspecialchars($_POST['ville_arrivee'] ?? '') ?>">
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
                        <input type="date" id="date_depart" name="date_depart" required
                               value="<?= htmlspecialchars($_POST['date_depart'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="heure_depart">Heure de départ *</label>
                        <input type="time" id="heure_depart" name="heure_depart" required
                               value="<?= htmlspecialchars($_POST['heure_depart'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="places">Nombre de places *</label>
                        <select id="places" name="places" required>
                            <option value="">Choisir...</option>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>" <?= (isset($_POST['places']) && $_POST['places'] == $i) ? 'selected' : '' ?>>
                                    <?= $i ?> place<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prix">Prix par personne (€) *</label>
                    <input type="number" id="prix" name="prix" step="0.5" min="1" max="100" required
                           placeholder="Ex: 15.50" value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>">
                    <div class="price-info">
                        <span class="material-icons">info</span>
                        <span>2 crédits seront prélevés par la plateforme</span>
                        <span id="total-cost">Coût total: <strong>0€</strong></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea id="description" name="description" rows="3" maxlength="500"
                              placeholder="Détails sur le trajet, conditions, etc."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <small style="color: #636e72;"><span id="char-count">0</span>/500 caractères</small>
                </div>

                <div class="form-submit">
                    <button type="submit" class="btn-primary btn-large" id="create-btn">
                        <span class="material-icons">add_road</span>
                        Créer le trajet
                    </button>
                </div>
            </form>
        </div>

        <div class="driver-card">
            <h3 style="color: #2d3436;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 10px;">list</span>
                Mes trajets (<?= count($mes_trajets) ?>)
            </h3>

            <?php if (empty($mes_trajets)): ?>
                <div class="empty-state">
                    <span class="material-icons" style="font-size: 48px; color: #ddd;">route</span>
                    <h4>Aucun trajet créé pour le moment</h4>
                    <p>Créez votre premier trajet pour commencer à partager vos déplacements !</p>
                </div>
            <?php else: ?>
                <div class="trips-grid">
                    <?php foreach ($mes_trajets as $trajet): ?>
                        <div class="trip-card">
                            <div class="trip-header">
                                <h4>
                                    <span class="material-icons">location_on</span>
                                    <?= htmlspecialchars($trajet['ville_depart']) ?>
                                    <span class="material-icons" style="margin: 0 8px;">arrow_forward</span>
                                    <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                </h4>
                                <span class="trip-status" style="background: <?= getStatusColor($trajet['status']) ?>">
                                    <?= getStatusLabel($trajet['status']) ?>
                                </span>
                            </div>

                            <div class="trip-details">
                                <div class="trip-detail">
                                    <span class="material-icons">schedule</span>
                                    <?= date('d/m/Y à H:i', strtotime($trajet['date_depart'])) ?>
                                </div>
                                <div class="trip-detail">
                                    <span class="material-icons">group</span>
                                    <?= (int)$trajet['participants'] ?> / <?= (int)$trajet['places_totales'] ?> passagers
                                </div>
                                <div class="trip-detail">
                                    <span class="material-icons">euro</span>
                                    <?= number_format($trajet['prix'], 2) ?> € par personne
                                </div>
                            </div>

                            <div class="trip-actions">
                                <button class="btn-secondary" onclick="viewTrip(<?= $trajet['id'] ?>)">
                                    <span class="material-icons">visibility</span>
                                    Voir
                                </button>
                                <?php if ($trajet['status'] === 'planifie'): ?>
                                    <button class="btn-primary" onclick="editTrip(<?= $trajet['id'] ?>)">
                                        <span class="material-icons">edit</span>
                                        Modifier
                                    </button>
                                    <button class="btn-error" onclick="cancelTrip(<?= $trajet['id'] ?>)">
                                        <span class="material-icons">cancel</span>
                                        Annuler
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="back-link" style="text-align:center;margin-top:40px;">
                <a href="/frontend/public/pages/profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">
                    <span class="material-icons">arrow_back</span>
                    Retour au profil
                </a>
            </div>
        </div>
    </main>

    <script>
        window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    </script>
    <script src="/assets/js/navbar.js"></script>
    <script>
        // Rendu du menu avec navbar.js
        if (typeof renderMenu === 'function') {
            renderMenu(window.ecorideUser);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('trip-form');
            const createBtn = document.getElementById('create-btn');
            const successMessage = document.getElementById('success-message');
            const prixInput = document.getElementById('prix');
            const totalCostSpan = document.getElementById('total-cost');
            const descriptionTextarea = document.getElementById('description');
            const charCount = document.getElementById('char-count');

            // Calcul automatique du coût total
            function updateTotalCost() {
                const prix = parseFloat(prixInput.value) || 0;
                const total = prix + 2; // 2 crédits plateforme
                totalCostSpan.innerHTML = `Coût total: <strong>${total}€</strong>`;
            }

            prixInput.addEventListener('input', updateTotalCost);
            updateTotalCost(); // Calcul initial

            // Compteur de caractères
            descriptionTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;
                charCount.style.color = count > 450 ? '#e74c3c' : '#636e72';
            });

            // Validation de date (aujourd'hui minimum)
            const dateInput = document.getElementById('date_depart');
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);

            // Animation du bouton de création
            form.addEventListener('submit', function(e) {
                createBtn.innerHTML = '<span class="material-icons spinning">sync</span> Création en cours...';
                createBtn.disabled = true;
                createBtn.style.opacity = '0.7';
            });

            // Auto-disparition du message de succès
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 300);
                }, 3000);
            }
        });

        // Fonctions pour gérer les trajets
        function viewTrip(tripId) {
            window.location.href = `/backend/public/details.php?trip_id=${tripId}`;
        }

        function editTrip(tripId) {
            alert('Fonctionnalité de modification à venir pour le trajet #' + tripId);
        }

        function cancelTrip(tripId) {
            if (confirm('Êtes-vous sûr de vouloir annuler ce trajet ? Les passagers seront notifiés.')) {
                alert('Fonctionnalité d\'annulation à venir pour le trajet #' + tripId);
            }
        }
    </script>
</body>
</html>

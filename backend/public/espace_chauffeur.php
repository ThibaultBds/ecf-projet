<?php
session_start();
require_once 'config/autoload.php';
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

// Traitement du formulaire de création de trajet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_trip') {
    try {
        // Récupération et validation des champs
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

        // Vérifier que la date et l'heure sont dans le futur
        $datetime_depart = $date_depart . ' ' . $heure_depart;
        if (strtotime($datetime_depart) <= time()) {
            throw new DateException('La date de départ doit être dans le futur.');
        }

        $pdo = getDatabase();

        // Vérifier ou créer un véhicule pour l'utilisateur
        $vehicle = getVehicleByUserId($user['id']);
        
        if (!$vehicle) {
            $vehicle_id = createDefaultVehicle($user['id'], $places);
        } else {
            $vehicle_id = $vehicle['id'];
        }

        // Vérifier crédits suffisants (prix + 2 pour plateforme)
        $total_cost = $prix + 2;
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $current_credits = $stmt->fetchColumn();
        if ($current_credits < $total_cost) {
            throw new Exception('Crédits insuffisants. Vous avez ' . $current_credits . ' crédits, nécessaire ' . $total_cost . '.');
        }

        // Créer le trajet
        if (createTrip($user['id'], $vehicle_id, $ville_depart, $ville_arrivee, $datetime_depart, $prix, $places, $description)) {
            // Déduire crédits (prix + 2)
            $stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
            $stmt->execute([$total_cost, $user['id']]);

            // Log de l'activité
            try {
                $pdo = getDatabase();
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], 'Création trajet', "$ville_depart → $ville_arrivee", $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (Exception $e) {
                // Log silencieux en cas d'erreur
            }

            $success = 'Trajet créé avec succès ! ' . $total_cost . ' crédits déduits.';
            $_POST = [];
        } else {
            throw new Exception('Erreur lors de la création du trajet');
        }
    } catch (FormFieldException|PriceOrPlacesException|DateException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        $error = 'Une erreur inattendue est survenue.';
    }
}

// Récupérer les trajets du chauffeur
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
    if (!isset($error)) {
        $error = "Erreur lors du chargement de vos trajets.";
    }
}

// ----------- FONCTIONS UTILITAIRES ------------
function getStatusColor($status) {
    // Cette fonction avait trop de return, on la simplifie avec une map :
    $colors = [
        'planifie' => '#00b894',
        'en_cours' => '#f39c12',
        'termine' => '#636e72',
        'annule' => '#e74c3c'
    ];
    return $colors[$status] ?? '#b2bec3';
}

function getStatusLabel($status) {
    // Cette fonction avait trop de return, on la simplifie aussi avec une map :
    $labels = [
        'planifie' => 'Planifié',
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'annule' => 'Annulé'
    ];
    return $labels[$status] ?? 'Planifié';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Chauffeur - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1>
            <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
    </header>
    <main>
        <div style="max-width:1000px;margin:40px auto;padding:0 20px;">
            <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);margin-bottom:30px;">
                <h2 style="color:#2d3436;margin-bottom:30px;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">drive_eta</span>
                    Espace Chauffeur
                </h2>
                <?php if ($success): ?>
                    <div class="message-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <h3>Proposer un nouveau trajet</h3>
                <form method="POST" style="display:grid;gap:20px;" novalidate autocomplete="off">
                    <input type="hidden" name="action" value="create_trip">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label for="ville_depart" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Ville de départ *</label>
                            <input type="text" id="ville_depart" name="ville_depart" required list="villes" placeholder="Ex: Paris" value="<?= htmlspecialchars($_POST['ville_depart'] ?? '') ?>" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label for="ville_arrivee" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Ville d'arrivée *</label>
                            <input type="text" id="ville_arrivee" name="ville_arrivee" required list="villes" placeholder="Ex: Lyon" value="<?= htmlspecialchars($_POST['ville_arrivee'] ?? '') ?>" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
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
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                        <div>
                            <label for="date_depart" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Date de départ *</label>
                            <input type="date" id="date_depart" name="date_depart" required value="<?= htmlspecialchars($_POST['date_depart'] ?? '') ?>" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label for="heure_depart" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Heure de départ *</label>
                            <input type="time" id="heure_depart" name="heure_depart" required value="<?= htmlspecialchars($_POST['heure_depart'] ?? '') ?>" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label for="places" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Nombre de places *</label>
                            <select id="places" name="places" required style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                                <option value="">Choisir...</option>
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <option value="<?= $i ?>" <?= (isset($_POST['places']) && $_POST['places'] == $i) ? 'selected' : '' ?>><?= $i ?> place<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="prix" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Prix par personne (€) *</label>
                        <input type="number" id="prix" name="prix" step="0.5" min="1" max="100" required placeholder="Ex: 15.50" value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>" style="width:200px;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                        <small style="color:#636e72;margin-left:10px;">
                            <span class="material-icons" style="font-size:16px;vertical-align:middle;">info</span>
                            2 crédits seront prélevés par la plateforme
                        </small>
                    </div>
                    <div>
                        <label for="description" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Description (optionnel)</label>
                        <textarea id="description" name="description" rows="3" maxlength="500" placeholder="Détails sur le trajet, conditions, etc." style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;resize:vertical;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <small style="color:#636e72;">Maximum 500 caractères</small>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary" style="padding:15px 30px;font-size:18px;">
                            <span class="material-icons">add_road</span>
                            Créer le trajet
                        </button>
                    </div>
                </form>
            </div>
            <!-- Mes trajets -->
            <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <h3>Mes trajets (<?= count($mes_trajets) ?>)</h3>
                <?php if (empty($mes_trajets)): ?>
                    <div style="text-align:center;padding:40px;color:#636e72;">
                        <span class="material-icons" style="font-size:64px;margin-bottom:20px;opacity:0.5;">route</span>
                        <p>Aucun trajet créé pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:20px;">
                        <?php foreach ($mes_trajets as $trajet): ?>
                            <div style="border:1px solid #ddd;border-radius:8px;padding:20px;background:#f8f9fa;">
                                <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:15px;">
                                    <div>
                                        <h4 style="margin:0 0 10px 0;color:#2d3436;">
                                            <?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                        </h4>
                                        <p style="margin:5px 0;color:#636e72;">
                                            <span class="material-icons" style="vertical-align:middle;font-size:18px;">schedule</span>
                                            <?= date('d/m/Y à H:i', strtotime($trajet['date_depart'])) ?>
                                        </p>
                                        <p style="margin:5px 0;color:#636e72;">
                                            <span class="material-icons" style="vertical-align:middle;font-size:18px;">people</span>
                                            <?= (int)$trajet['participants'] ?> participant(s) • <?= (int)$trajet['places_restantes'] ?> place(s) restante(s)
                                        </p>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:20px;font-weight:bold;color:#00b894;"><?= number_format($trajet['prix'], 2) ?>€</div>
                                        <div style="margin-top:10px;">
                                            <span style="background:<?= getStatusColor($trajet['status'] ?? 'planifie') ?>;color:white;padding:4px 8px;border-radius:4px;font-size:12px;">
                                                <?= getStatusLabel($trajet['status'] ?? 'planifie') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div style="text-align:center;margin-top:30px;">
                    <a href="/profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
                </div>
            </div>
        </div>
    </main>
    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Définir la date minimum à aujourd'hui
            const dateInput = document.getElementById('date_depart');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.min = today;
            }
            // Validation côté client
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const dateDepart = document.getElementById('date_depart').value;
                    const heureDepart = document.getElementById('heure_depart').value;
                    if (dateDepart && heureDepart) {
                        const dateTimeDepart = new Date(dateDepart + 'T' + heureDepart);
                        const now = new Date();
                        if (dateTimeDepart <= now) {
                            e.preventDefault();
                            alert('La date et heure de départ doivent être dans le futur.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
            if (form) {
                form.addEventListener('submit', function(e) {
                    const dateDepart = document.getElementById('date_depart').value;
                    const heureDepart = document.getElementById('heure_depart').value;
                    if (dateDepart && heureDepart) {
                        const dateTimeDepart = new Date(dateDepart + 'T' + heureDepart);
                        const now = new Date();
                        if (dateTimeDepart <= now) {
                            e.preventDefault();
                            alert('La date et heure de départ doivent être dans le futur.');
                            return false;
                        }
                    }
                });
            }
        });
</body>
</html>




<?php
session_start();
require_once '../config/autoload.php';
useClass('Database');

if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];
$error = '';
$success = '';

// Récupérer les véhicules de l'utilisateur
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $vehicles = [];
    $error = "Erreur lors du chargement des véhicules.";
}

// Traitement du formulaire d'ajout
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_vehicle') {
    try {
        $marque = trim($_POST['marque'] ?? '');
        $modele = trim($_POST['modele'] ?? '');
        $couleur = trim($_POST['couleur'] ?? '');
        $plaque = trim($_POST['plaque'] ?? '');
        $energie = $_POST['energie'] ?? '';
        $places = (int)($_POST['places'] ?? 4);

        if (empty($marque) || empty($modele) || empty($couleur) || empty($plaque) || empty($energie)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        if ($places < 1 || $places > 8) {
            throw new Exception("Le nombre de places doit être entre 1 et 8.");
        }

        // Validation de la plaque d'immatriculation (format français basique)
        if (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', strtoupper($plaque))) {
            throw new Exception("Format de plaque invalide (ex: AB-123-CD).");
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (user_id, marque, modele, couleur, plaque, energie, places_disponibles, date_immatriculation)
            VALUES (?, ?, ?, ?, ?, ?, ?, '2020-01-01')
        ");
        $stmt->execute([$user['id'], $marque, $modele, $couleur, strtoupper($plaque), $energie, $places]);

        $success = "Véhicule ajouté avec succès !";
        $_POST = [];

        // Recharger les véhicules
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
// Traitement unifié : ajout, modification et suppression de véhicules
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add_vehicle' || $action === 'edit_vehicle') {
            $marque = trim($_POST['marque'] ?? '');
            $modele = trim($_POST['modele'] ?? '');
            $couleur = trim($_POST['couleur'] ?? '');
            $plaque = trim($_POST['plaque'] ?? '');
            $energie = $_POST['energie'] ?? '';
            $places = (int)($_POST['places'] ?? 4);
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

            if (empty($marque) || empty($modele) || empty($couleur) || empty($plaque) || empty($energie)) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            if ($places < 1 || $places > 8) {
                throw new Exception("Le nombre de places doit être entre 1 et 8.");
            }

            // Validation de la plaque d'immatriculation (format français basique)
            if (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', strtoupper($plaque))) {
                throw new Exception("Format de plaque invalide (ex: AB-123-CD).");
            }

            if ($action === 'add_vehicle') {
                $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, marque, modele, couleur, plaque, energie, places_disponibles, date_immatriculation) VALUES (?, ?, ?, ?, ?, ?, ?, '2020-01-01')");
                $stmt->execute([$user['id'], $marque, $modele, $couleur, strtoupper($plaque), $energie, $places]);
                $success = "Véhicule ajouté avec succès !";
                $_POST = [];
            } else {
                // Modification : vérifier la propriété
                if ($vehicle_id <= 0) throw new Exception('ID de véhicule invalide.');
                $stmt = $pdo->prepare("SELECT user_id FROM vehicles WHERE id = ?");
                $stmt->execute([$vehicle_id]);
                $owner = $stmt->fetchColumn();
                if (!$owner || $owner != $user['id']) throw new Exception('Véhicule introuvable ou accès refusé.');

                $stmt = $pdo->prepare("UPDATE vehicles SET marque = ?, modele = ?, couleur = ?, plaque = ?, energie = ?, places_disponibles = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$marque, $modele, $couleur, strtoupper($plaque), $energie, $places, $vehicle_id, $user['id']]);
                $success = "Véhicule mis à jour avec succès.";
            }

            // Recharger la liste
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user['id']]);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($action === 'delete_vehicle') {
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            if ($vehicle_id <= 0) throw new Exception('ID de véhicule invalide.');

            $stmt = $pdo->prepare("SELECT user_id FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicle_id]);
            $owner = $stmt->fetchColumn();
            if (!$owner || $owner != $user['id']) throw new Exception('Véhicule introuvable ou accès refusé.');

            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
            $stmt->execute([$vehicle_id, $user['id']]);
            $success = "Véhicule supprimé.";

            // Recharger la liste
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user['id']]);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Véhicules - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css?v=2025">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="vh-vehicles-container">
        <div class="page-header">
            <h2><span class="material-icons">directions_car</span> Mes Véhicules</h2>
            <p>Gérez vos véhicules pour proposer des trajets écologiques</p>
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

        <!-- Liste des véhicules -->
        <?php if (!empty($vehicles)): ?>
            <div class="vh-vehicles-grid">
                <?php foreach ($vehicles as $vehicle): 
                    // Préparer quelques variables pour l'affichage (plus lisible que des ternaires imbriqués)
                    $energie = strtolower(trim($vehicle['energie'] ?? ''));
                    $energie_icon = 'local_gas_station';
                    $energie_class = '';
                    if ($energie === 'electrique') {
                        $energie_icon = 'electric_bolt';
                        $energie_class = 'ecological';
                    } elseif ($energie === 'hybride') {
                        $energie_icon = 'battery_charging_full';
                    }
                ?>
                <div class="vh-vehicle-card" 
                    data-vehicle-id="<?= $vehicle['id'] ?>"
                    data-marque="<?= htmlspecialchars($vehicle['marque'], ENT_QUOTES) ?>"
                    data-modele="<?= htmlspecialchars($vehicle['modele'], ENT_QUOTES) ?>"
                    data-couleur="<?= htmlspecialchars($vehicle['couleur'], ENT_QUOTES) ?>"
                    data-plaque="<?= htmlspecialchars($vehicle['plaque'], ENT_QUOTES) ?>"
                    data-energie="<?= htmlspecialchars($vehicle['energie'], ENT_QUOTES) ?>"
                    data-places="<?= (int)$vehicle['places_disponibles'] ?>">
                        <div class="vh-vehicle-image <?= $energie_class ?>">
                            <span class="material-icons"><?= $energie_icon ?></span>
                        </div>
                        <div class="vh-vehicle-info">
                            <h3>
                                <?= htmlspecialchars($vehicle['marque']) ?> <?= htmlspecialchars($vehicle['modele']) ?>
                                <?php if ($vehicle['energie'] === 'electrique'): ?>
                                    <span class="material-icons ecological-badge" title="Véhicule écologique">eco</span>
                                <?php endif; ?>
                            </h3>

                            <div class="vh-vehicle-details">
                                <div class="vh-vehicle-detail">
                                    <span class="material-icons">palette</span>
                                    <?= htmlspecialchars($vehicle['couleur']) ?>
                                </div>
                                <div class="vh-vehicle-detail">
                                    <span class="material-icons">tag</span>
                                    <?= htmlspecialchars($vehicle['plaque']) ?>
                                </div>
                                <div class="vh-vehicle-detail">
                                    <span class="material-icons"><?= $energie_icon ?></span>
                                    <?= htmlspecialchars(ucfirst($vehicle['energie'])) ?>
                                </div>
                                <div class="vh-vehicle-detail">
                                    <span class="material-icons">group</span>
                                    <?= (int)$vehicle['places_disponibles'] ?> places
                                </div>
                            </div>
                            <div class="vh-vehicle-stats">
                                <h4><span class="material-icons">analytics</span> Statistiques</h4>
                                <div class="vh-vehicle-stats-grid">
                                    <div class="vh-stat-item">
                                        <div class="vh-stat-number">0</div>
                                        <div class="vh-stat-label">Trajets</div>
                                    </div>
                                    <div class="vh-stat-item">
                                        <div class="vh-stat-number">0</div>
                                        <div class="vh-stat-label">Passagers</div>
                                    </div>
                                    <div class="vh-stat-item">
                                        <div class="vh-stat-number">0€</div>
                                        <div class="vh-stat-label">Revenus</div>
                                    </div>
                                </div>
                            </div>

                            <div class="vh-vehicle-actions">
                                <button class="vh-btn-vehicle-edit" onclick="editVehicle(<?= $vehicle['id'] ?>)">
                                    <span class="material-icons">edit</span>
                                    Modifier
                                </button>
                                <button class="vh-btn-vehicle-delete" onclick="deleteVehicle(<?= $vehicle['id'] ?>)">
                                    <span class="material-icons">delete</span>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Section d'ajout de véhicule -->
        <div class="add-vehicle-section">
            <h3 class="section-title-center">
                <span class="material-icons icon-inline">add_circle</span>
                Ajouter un véhicule
            </h3>

            <form method="POST" class="add-vehicle-form" id="add-vehicle-form" novalidate>
                <input type="hidden" name="action" id="form-action" value="add_vehicle">
                <input type="hidden" name="vehicle_id" id="vehicle_id" value="">

                <div class="form-group">
                    <label for="marque">Marque *</label>
                    <input type="text" id="marque" name="marque" required
                           value="<?= htmlspecialchars($_POST['marque'] ?? '') ?>"
                           placeholder="Ex: Renault, Peugeot, Tesla...">
                </div>

                <div class="form-group">
                    <label for="modele">Modèle *</label>
                    <input type="text" id="modele" name="modele" required
                           value="<?= htmlspecialchars($_POST['modele'] ?? '') ?>"
                           placeholder="Ex: Clio, 308, Model 3...">
                </div>

                <div class="form-group">
                    <label for="couleur">Couleur *</label>
                    <input type="text" id="couleur" name="couleur" required
                           value="<?= htmlspecialchars($_POST['couleur'] ?? '') ?>"
                           placeholder="Ex: Blanc, Bleu, Rouge...">
                </div>

                <div class="form-group">
                    <label for="plaque">Plaque d'immatriculation *</label>
              <input type="text" id="plaque" name="plaque" required maxlength="9" inputmode="text" autocomplete="off"
                  value="<?= htmlspecialchars($_POST['plaque'] ?? '') ?>"
                  placeholder="Ex: AB-123-CD" pattern="[A-Z]{2}-\d{3}-[A-Z]{2}"
                  class="text-uppercase" style="width:100%;">
              <small class="small-muted">Format: AB-123-CD</small>
                </div>

                <div class="form-group">
                    <label for="energie">Type d'énergie *</label>
                    <select name="energie" id="energie" required>
                        <option value="">Choisir...</option>
                        <option value="essence" <?= ($_POST['energie'] ?? '') === 'essence' ? 'selected' : '' ?>>
                            Essence
                        </option>
                        <option value="diesel" <?= ($_POST['energie'] ?? '') === 'diesel' ? 'selected' : '' ?>>
                            Diesel
                        </option>
                        <option value="electrique" <?= ($_POST['energie'] ?? '') === 'electrique' ? 'selected' : '' ?>>
                            ⚡ Électrique (Écologique)
                        </option>
                        <option value="hybride" <?= ($_POST['energie'] ?? '') === 'hybride' ? 'selected' : '' ?>>
                            Hybride
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="places">Nombre de places disponibles *</label>
                    <input type="number" id="places" name="places" min="1" max="8"
                           value="<?= htmlspecialchars($_POST['places'] ?? '4') ?>" required>
                    <small class="small-muted">Places pour les passagers (1-8)</small>
                </div>

                <button type="submit" class="btn-add-vehicle" id="add-btn">
                    <span class="material-icons">add</span>
                    Ajouter le véhicule
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="/pages/profil.php" class="link-primary">
                <span class="material-icons">arrow_back</span>
                Retour au profil
            </a>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer-scripts.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('add-vehicle-form');
            const addBtn = document.getElementById('add-btn');
            const successMessage = document.getElementById('success-message');
            const plaqueInput = document.getElementById('plaque');

            // Format automatique de la plaque (préserve la position du curseur)
            plaqueInput.addEventListener('input', function(e) {
                const tgt = e.target;
                const raw = tgt.value;
                const sel = tgt.selectionStart || 0;

                // Count alnum characters before the caret in the raw value
                const alnumBefore = (raw.slice(0, sel).toUpperCase().replace(/[^A-Z0-9]/g, '')).length;

                // Clean value to letters+digits and uppercase
                let clean = raw.toUpperCase().replace(/[^A-Z0-9]/g, '');
                // Trim to max 7 alnum (2+3+2 = 7), we'll add 2 dashes -> total length 9
                if (clean.length > 7) clean = clean.substring(0, 7);

                // Apply formatting AB-123-CD
                let formatted = clean;
                if (formatted.length >= 2) formatted = formatted.substring(0,2) + '-' + formatted.substring(2);
                if (formatted.replace(/[^A-Z0-9]/g,'').length >= 5) {
                    // after the second block exists (2 + 3), insert second dash
                    // compute position in the clean string and insert
                    const c = formatted.replace(/[^A-Z0-9]/g,'');
                    if (c.length > 5) {
                        // ensure we have AB-123-CD
                        const first = c.substring(0,2);
                        const mid = c.substring(2,5);
                        const last = c.substring(5);
                        formatted = first + '-' + mid + (last ? '-' + last : '');
                    }
                }

                // compute new caret position based on alnumBefore
                let caretPos = 0;
                let seen = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/[A-Z0-9]/.test(formatted[i])) seen++;
                    caretPos = i + 1;
                    if (seen >= alnumBefore) break;
                }
                // if we didn't reach the desired count, put at end
                if (seen < alnumBefore) caretPos = formatted.length;

                tgt.value = formatted;
                try { tgt.setSelectionRange(caretPos, caretPos); } catch (err) { /* ignore */ }
            });

            // Animation du bouton d'ajout
            form.addEventListener('submit', function(e) {
                addBtn.innerHTML = '<span class="material-icons spinning">sync</span> Ajout en cours...';
                addBtn.disabled = true;
                addBtn.style.opacity = '0.7';
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

        // Fonctions pour gérer les véhicules (édition et suppression)
        function editVehicle(vehicleId) {
            // trouver la carte correspondante
            const card = document.querySelector('.vh-vehicle-card[data-vehicle-id="' + vehicleId + '"]');
            if (!card) return alert('Véhicule introuvable sur la page');

            // pré-remplir le formulaire
            document.getElementById('marque').value = card.dataset.marque || '';
            document.getElementById('modele').value = card.dataset.modele || '';
            document.getElementById('couleur').value = card.dataset.couleur || '';
            document.getElementById('plaque').value = card.dataset.plaque || '';
            document.getElementById('energie').value = card.dataset.energie || '';
            document.getElementById('places').value = card.dataset.places || '4';

            // switcher le formulaire en mode édition
            document.getElementById('form-action').value = 'edit_vehicle';
            document.getElementById('vehicle_id').value = vehicleId;
            const addBtn = document.getElementById('add-btn');
            addBtn.innerHTML = '<span class="material-icons">save</span> Enregistrer les modifications';
            addBtn.disabled = false;
            addBtn.style.opacity = '1';

            // scroller vers le formulaire
            document.querySelector('.add-vehicle-section').scrollIntoView({ behavior: 'smooth' });
        }

        function deleteVehicle(vehicleId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Cette action est irréversible.')) return;

            // créer un formulaire POST pour supprimer sans JS fetch
            const f = document.createElement('form');
            f.method = 'POST';
            f.style.display = 'none';
            const a = document.createElement('input'); a.type = 'hidden'; a.name = 'action'; a.value = 'delete_vehicle'; f.appendChild(a);
            const v = document.createElement('input'); v.type = 'hidden'; v.name = 'vehicle_id'; v.value = vehicleId; f.appendChild(v);
            document.body.appendChild(f);
            f.submit();
        }
    </script>

    <!-- Styles migrés vers frontend/public/assets/css/style.css -->
</body>
</html>

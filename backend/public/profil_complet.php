<?php
session_start();

require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login_secure.php");
    exit();
}

$user = $_SESSION['user'];
$db = DatabaseManager::getInstance();
$success = "";
$error = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userType = $_POST['user_type'] ?? '';
    $fumeur = isset($_POST['fumeur']) ? 1 : 0;
    $animaux = isset($_POST['animaux']) ? 1 : 0;
    $preferencesCustom = trim($_POST['preferences_custom'] ?? '');

    if (!empty($userType)) {
        try {
            // Mettre à jour le type d'utilisateur
            $sql = "UPDATE users SET user_type_preference = ? WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$userType, $user['id']]);

            // Mettre à jour ou créer les préférences
            $sql = "INSERT INTO user_preferences (user_id, fumeur, animaux, preference_custom)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE fumeur = VALUES(fumeur), animaux = VALUES(animaux), preference_custom = VALUES(preference_custom)";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$user['id'], $fumeur, $animaux, $preferencesCustom]);

            $_SESSION['user']['user_type_preference'] = $userType;
            $success = "Profil mis à jour avec succès !";
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// Récupérer les préférences actuelles
try {
    $sql = "SELECT u.user_type_preference, u.credits, p.* FROM users u
            LEFT JOIN user_preferences p ON u.id = p.user_id
            WHERE u.id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$user['id']]);
    $userPrefs = $stmt->fetch();
} catch (Exception $e) {
    $userPrefs = null;
}

// Récupérer les statistiques utilisateur
try {
    $sql = "SELECT COUNT(*) as trajets_count FROM trip_participants WHERE passager_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$user['id']]);
    $trajetsCount = $stmt->fetch()['trajets_count'] ?? 0;
    
    $sql = "SELECT AVG(note) as avg_rating FROM reviews WHERE reviewed_id = ? AND status = 'valide'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$user['id']]);
    $avgRating = $stmt->fetch()['avg_rating'] ?? 0;
} catch (Exception $e) {
    $trajetsCount = 0;
    $avgRating = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration Profil - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1>
            <a href="index.html" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
    </header>

    <main style="max-width:800px;margin:40px auto;padding:0 20px;">
        <div style="background:white;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <h2 style="text-align:center;color:#2d3436;margin-bottom:30px;">
                <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">settings</span>
                Configuration de votre profil
            </h2>

            <?php if (!empty($error)): ?>
                <div style="background:#ffeaea;color:#b8002e;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #ff4d6d;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">error</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div style="background:#e6f9ed;color:#00b894;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #00b894;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">check_circle</span>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="display:flex;flex-direction:column;gap:30px;">
                
                <!-- Type d'utilisateur -->
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                    <fieldset style="border:none;padding:0;margin:0;">
                        <legend style="margin:0 0 15px 0;color:#2d3436;font-size:1.17em;font-weight:bold;">
                            <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:8px;">person</span>
                            Type d'utilisateur
                        </legend>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">
                            <label for="user-type-passager" style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:15px;border:2px solid #ddd;border-radius:8px;transition:all 0.3s ease;">
                                <input type="radio" id="user-type-passager" name="user_type" value="passager" <?= ($userPrefs['user_type_preference'] ?? '') === 'passager' ? 'checked' : '' ?>>
                                <div>
                                    <div style="font-weight:600;">Passager</div>
                                    <div style="font-size:0.9rem;color:#636e72;">Je recherche des trajets</div>
                                </div>
                            </label>
                            
                            <label for="user-type-chauffeur" style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:15px;border:2px solid #ddd;border-radius:8px;transition:all 0.3s ease;">
                                <input type="radio" id="user-type-chauffeur" name="user_type" value="chauffeur" <?= ($userPrefs['user_type_preference'] ?? '') === 'chauffeur' ? 'checked' : '' ?>>
                                <div>
                                    <div style="font-weight:600;">Chauffeur</div>
                                    <div style="font-size:0.9rem;color:#636e72;">Je propose des trajets</div>
                                </div>
                            </label>
                            
                            <label for="user-type-les-deux" style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:15px;border:2px solid #ddd;border-radius:8px;transition:all 0.3s ease;">
                                <input type="radio" id="user-type-les-deux" name="user_type" value="les_deux" <?= ($userPrefs['user_type_preference'] ?? '') === 'les_deux' ? 'checked' : '' ?>>
                                <div>
                                    <div style="font-weight:600;">Les deux</div>
                                    <div style="font-size:0.9rem;color:#636e72;">Passager et chauffeur</div>
                                </div>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <!-- Préférences -->
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                    <h3 style="margin:0 0 15px 0;color:#2d3436;">
                        <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:8px;">tune</span>
                        Préférences de voyage
                    </h3>
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:20px;">
                        <label for="fumeur-checkbox" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" id="fumeur-checkbox" name="fumeur" <?= ($userPrefs['fumeur'] ?? 0) ? 'checked' : '' ?> style="transform:scale(1.2);">
                            <span>
                                <span class="material-icons" style="vertical-align:middle;color:#ff6b6b;margin-right:5px;">smoke_free</span>
                                Accepte les fumeurs
                            </span>
                        </label>
                        
                        <label for="animaux-checkbox" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" id="animaux-checkbox" name="animaux" <?= ($userPrefs['animaux'] ?? 0) ? 'checked' : '' ?> style="transform:scale(1.2);">
                            <span>
                                <span class="material-icons" style="vertical-align:middle;color:#4ecdc4;margin-right:5px;">pets</span>
                                Accepte les animaux
                            </span>
                        </label>
                    </div>
                    
                    <div>
                        <label for="preferences-textarea" style="display:block;margin-bottom:8px;font-weight:600;color:#2d3436;">Préférences personnalisées</label>
                        <textarea id="preferences-textarea" name="preferences_custom" rows="3" placeholder="Ex: Musique calme, pas de discussions, arrêts autorisés..." style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;resize:vertical;box-sizing:border-box;"><?= htmlspecialchars($userPrefs['preference_custom'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Informations compte -->
                <div style="background:#e6f9ed;padding:20px;border-radius:8px;border:1px solid #00b894;">
                    <h3 style="margin:0 0 15px 0;color:#2d3436;">
                        <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:8px;">account_balance_wallet</span>
                        Informations compte
                    </h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">
                        <div style="text-align:center;">
                            <div style="font-size:24px;font-weight:bold;color:#00b894;"><?= $userPrefs['credits'] ?? 20 ?></div>
                            <div style="font-size:14px;color:#636e72;">Crédits disponibles</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:24px;font-weight:bold;color:#0984e3;"><?= $trajetsCount ?></div>
                            <div style="font-size:14px;color:#636e72;">Trajets effectués</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:24px;font-weight:bold;color:#fdcb6e;"><?= number_format($avgRating, 1) ?></div>
                            <div style="font-size:14px;color:#636e72;">Note moyenne</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="align-self:flex-start;padding:15px 30px;">
                    <span class="material-icons">save</span>
                    Sauvegarder les préférences
                </button>
            </form>

            <div style="text-align:center;margin-top:30px;display:flex;justify-content:center;gap:20px;">
                <a href="gestion_vehicules.php" style="color:#0984e3;text-decoration:none;font-weight:600;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:5px;">directions_car</span>
                    Gérer mes véhicules
                </a>
                <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
            </div>
        </div>
    </main>

    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
    <script>
        // Effet visuel sur la sélection radio amélioré
        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label').forEach(label => {
                    if (label.querySelector('input[name="user_type"]')) {
                        label.style.borderColor = '#ddd';
                        label.style.background = 'white';
                        label.style.transform = 'scale(1)';
                    }
                });
                this.closest('label').style.borderColor = '#00b894';
                this.closest('label').style.background = '#e6f9ed';
                this.closest('label').style.transform = 'scale(1.02)';
            });
        });

        // Animation de validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<span class="material-icons">hourglass_empty</span> Sauvegarde...';
            button.disabled = true;
        });
    </script>
</body>
</html>




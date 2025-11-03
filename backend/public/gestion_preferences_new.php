<?php
ob_start();
session_start();
require_once '../config/autoload.php';
useClass('Database');

// --- Vérif connexion utilisateur ---
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// --- Gestion des préférences ---
try {
    $pdo = getDatabase();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $musique = trim($_POST['musique'] ?? '');
        $animaux = trim($_POST['animaux'] ?? '');
        $discussion = trim($_POST['discussion'] ?? '');
        $fumeur = trim($_POST['fumeur'] ?? '');

        // Validation
        $validOptions = ['oui', 'non'];
        $validDiscussion = ['plaisir', 'un_peu', 'silence'];

        if (!in_array($musique, $validOptions) || !in_array($animaux, $validOptions) ||
            !in_array($discussion, $validDiscussion) || !in_array($fumeur, $validOptions)) {
            throw new Exception('Options invalides sélectionnées.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, musique, animaux, discussion, fumeur)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            musique = VALUES(musique),
            animaux = VALUES(animaux),
            discussion = VALUES(discussion),
            fumeur = VALUES(fumeur)
        ");
        $stmt->execute([$user['id'], $musique, $animaux, $discussion, $fumeur]);
        $success = 'Vos préférences ont bien été enregistrées.';
    }

    $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des préférences.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Préférences - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../frontend/public/assets/css/style.css?v=2025">
    <link rel="stylesheet" href="../../frontend/public/assets/css/pages.css?v=2025">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header"></header>

    <main class="preferences-container">
        <div class="page-header">
            <h2><span class="material-icons">tune</span> Mes Préférences</h2>
            <p>Ces informations seront visibles par les passagers pour mieux correspondre à votre style de trajet.</p>
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

        <form method="POST" class="preferences-form" id="preferences-form" novalidate>
            <div class="preferences-grid">
                <!-- Musique -->
                <div class="preference-card">
                    <h3>
                        <span class="material-icons">music_note</span>
                        Musique
                    </h3>
                    <div class="preference-options">
                        <label class="preference-option <?= ($preferences['musique'] ?? '') === 'oui' ? 'selected' : '' ?>">
                            <input type="radio" name="musique" value="oui" <?= ($preferences['musique'] ?? '') === 'oui' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Avec plaisir</div>
                                <div class="option-description">J'adore partager de la musique pendant les trajets</div>
                            </div>
                        </label>

                        <label class="preference-option <?= ($preferences['musique'] ?? '') === 'non' ? 'selected' : '' ?>">
                            <input type="radio" name="musique" value="non" <?= ($preferences['musique'] ?? '') === 'non' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Préférablement silence</div>
                                <div class="option-description">Je préfère voyager dans le calme</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Animaux -->
                <div class="preference-card">
                    <h3>
                        <span class="material-icons">pets</span>
                        Animaux
                    </h3>
                    <div class="preference-options">
                        <label class="preference-option <?= ($preferences['animaux'] ?? '') === 'oui' ? 'selected' : '' ?>">
                            <input type="radio" name="animaux" value="oui" <?= ($preferences['animaux'] ?? '') === 'oui' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Acceptés</div>
                                <div class="option-description">Les animaux de compagnie sont les bienvenus</div>
                            </div>
                        </label>

                        <label class="preference-option <?= ($preferences['animaux'] ?? '') === 'non' ? 'selected' : '' ?>">
                            <input type="radio" name="animaux" value="non" <?= ($preferences['animaux'] ?? '') === 'non' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Non acceptés</div>
                                <div class="option-description">Désolé, pas d'animaux dans le véhicule</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Discussion -->
                <div class="preference-card">
                    <h3>
                        <span class="material-icons">chat</span>
                        Discussion
                    </h3>
                    <div class="preference-options">
                        <label class="preference-option <?= ($preferences['discussion'] ?? '') === 'plaisir' ? 'selected' : '' ?>">
                            <input type="radio" name="discussion" value="plaisir" <?= ($preferences['discussion'] ?? '') === 'plaisir' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Avec plaisir</div>
                                <div class="option-description">J'adore discuter pendant le trajet</div>
                            </div>
                        </label>

                        <label class="preference-option <?= ($preferences['discussion'] ?? '') === 'un_peu' ? 'selected' : '' ?>">
                            <input type="radio" name="discussion" value="un_peu" <?= ($preferences['discussion'] ?? '') === 'un_peu' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Un peu</div>
                                <div class="option-description">Quelques échanges sont appréciés</div>
                            </div>
                        </label>

                        <label class="preference-option <?= ($preferences['discussion'] ?? '') === 'silence' ? 'selected' : '' ?>">
                            <input type="radio" name="discussion" value="silence" <?= ($preferences['discussion'] ?? '') === 'silence' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Préférablement silence</div>
                                <div class="option-description">Je voyage en silence</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Fumeur -->
                <div class="preference-card">
                    <h3>
                        <span class="material-icons">smoking_rooms</span>
                        Tabac
                    </h3>
                    <div class="preference-options">
                        <label class="preference-option <?= ($preferences['fumeur'] ?? '') === 'oui' ? 'selected' : '' ?>">
                            <input type="radio" name="fumeur" value="oui" <?= ($preferences['fumeur'] ?? '') === 'oui' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Fumeur</div>
                                <div class="option-description">Je fume pendant les trajets</div>
                            </div>
                        </label>

                        <label class="preference-option <?= ($preferences['fumeur'] ?? '') === 'non' ? 'selected' : '' ?>">
                            <input type="radio" name="fumeur" value="non" <?= ($preferences['fumeur'] ?? '') === 'non' ? 'checked' : '' ?>>
                            <div class="radio-custom"></div>
                            <div class="option-content">
                                <div class="option-title">Non fumeur</div>
                                <div class="option-description">Véhicule non-fumeur</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="preferences-actions">
                <button type="submit" class="btn-save-preferences" id="save-btn">
                    <span class="material-icons">save</span>
                    Enregistrer les préférences
                </button>
            </div>
        </form>

        <div class="back-link" style="text-align:center;margin-top:40px;">
            <a href="/frontend/public/pages/profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">
                <span class="material-icons">arrow_back</span>
                Retour au profil
            </a>
        </div>
    </main>

    <script>
        window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    </script>
    <script src="../../frontend/public/assets/js/navbar.js"></script>
    <script src="../../frontend/public/assets/js/script.js"></script>

    <script>
        // JavaScript pour l'interactivité des préférences
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('preferences-form');
            const saveBtn = document.getElementById('save-btn');
            const successMessage = document.getElementById('success-message');

            // Gestion des options de préférences
            document.querySelectorAll('.preference-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Désélectionner les autres options du même groupe
                    const radio = this.querySelector('input[type="radio"]');
                    const groupName = radio.name;
                    document.querySelectorAll(`input[name="${groupName}"]`).forEach(r => {
                        r.closest('.preference-option').classList.remove('selected');
                    });

                    // Sélectionner cette option
                    this.classList.add('selected');
                    radio.checked = true;
                });
            });

            // Animation du bouton de sauvegarde
            form.addEventListener('submit', function(e) {
                saveBtn.innerHTML = '<span class="material-icons spinning">sync</span> Enregistrement...';
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.7';
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
    </script>

    <style>
        .spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .message-success, .message-error {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }

        .message-success {
            background: #e8f7f2;
            color: #219150;
            border: 1px solid #00b894;
        }

        .message-error {
            background: #ffeaea;
            color: #b8002e;
            border: 1px solid #ff4d6d;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h2 {
            color: #2d3436;
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .page-header p {
            color: #636e72;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .back-link a {
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            transform: translateX(-5px);
        }
    </style>
</body>
</html>

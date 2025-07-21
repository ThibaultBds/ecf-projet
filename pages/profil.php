<?php
session_start();

// Nouveau système d'import
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];

// Récupérer les données utilisateur mises à jour
try {
    $userData = getUserById($user['id']);
    if ($userData) {
        // Mettre à jour la session avec les données fraîches
        $_SESSION['user']['credits'] = $userData['credits'];
        $user = $_SESSION['user'];
    }
} catch (Exception $e) {
    $error = "Erreur lors du chargement du profil.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
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
    <script>
    window.ecorideUser = <?php echo isset($user) ? json_encode([
        'email' => $user['email'],
        'pseudo' => $user['pseudo'],
        'type' => $user['type']
    ]) : 'null'; ?>;
    </script>

    <main>
        <div class="member-container">
            <h2>
                <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">account_circle</span>
                Mon profil
            </h2>
            
            <?php if (isset($error)): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="profile-info" style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:30px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                    <div>
                        <strong>Email :</strong><br>
                        <span style="color:#636e72;"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div>
                        <strong>Pseudo :</strong><br>
                        <span style="color:#636e72;"><?= htmlspecialchars($user['pseudo']) ?></span>
                    </div>
                    <div>
                        <strong>Type de compte :</strong><br>
                        <span style="color:#00b894;font-weight:600;"><?= htmlspecialchars($user['type']) ?></span>
                    </div>
                    <div>
                        <strong>Crédits disponibles :</strong><br>
                        <span style="color:#00b894;font-weight:600;font-size:18px;">
                            <?= (int)$user['credits'] ?>
                            <span class="material-icons" style="vertical-align:middle;font-size:20px;">account_balance_wallet</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <h3>Actions disponibles</h3>
                <div class="action-buttons" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                    <a href="covoiturages.php" class="btn-primary">
                        <span class="material-icons">directions_car</span>
                        Voir les covoiturages
                    </a>
                    <a href="mes_trajets.php" class="btn-primary">
                        <span class="material-icons">history</span>
                        Mes covoiturages
                    </a>
                    <?php if ($user['type'] === 'Administrateur'): ?>
                        <a href="admin.php" class="btn-admin">
                            <span class="material-icons">admin_panel_settings</span>
                            Panel Administrateur
                        </a>
                    <?php elseif ($user['type'] === 'Moderateur'): ?>
                        <a href="moderateur.php" class="btn-moderator">
                            <span class="material-icons">moderation</span>
                            Panel Modérateur
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid #ddd;">
                <a href="logout.php" class="logout-link">
                    <span class="material-icons">logout</span>
                    Se déconnecter
                </a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EcoRide - Tous droits réservés</p>
        <div>
            <a href="#" id="openModalLegal">Mentions légales</a>
        </div>
    </footer>

    <!-- Modal Mentions Légales -->
    <dialog id="modal-legal" class="modal-legal-dialog">
        <form method="dialog" class="modal-legal-content">
            <button class="modal-legal-close" id="closeModalLegal" aria-label="Fermer la fenêtre" type="button">×</button>
            <h2>Mentions légales</h2>
            <div class="modal-legal-body">
                <p>
                    <strong>Nom de l'entreprise</strong> : EcoRide<br>
                    <strong>Statut</strong> : Société fictive dans le cadre d'un projet étudiant<br>
                    <strong>Adresse</strong> : 123 rue de la Planète Verte, 75000 Paris<br>
                    <strong>SIREN</strong> : 000 000 000<br>
                    <strong>Responsable de publication</strong> : Jules Fictif<br>
                    <strong>Email</strong> : contact@ecoride.fr<br>
                    <strong>Hébergeur</strong> : OVH, 2 rue Kellermann, 59100 Roubaix, France<br>
                </p>
                <p>
                    Ce site a été réalisé dans le cadre d'un projet étudiant et n'a pas vocation commerciale.<br>
                    Pour toute question, contactez-nous via le formulaire de contact.
                </p>
            </div>
        </form>
    </dialog>

    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.ecorideUser) {
            renderMenu(window.ecorideUser);
        } else {
            renderMenu();
        }
    });
    </script>
</body>
</html>
</html>
</html>




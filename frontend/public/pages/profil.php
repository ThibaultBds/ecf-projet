<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

require_once __DIR__ . '/../../../backend/config/guard.php';
requireLogin();

// Type/role de l'utilisateur
$type = $_SESSION['user']['type'] ?? ($_SESSION['user']['role'] ?? 'utilisateur');

// 🔧 Redirection intelligente : on ne redirige que depuis l'accueil
if (basename($_SERVER['PHP_SELF']) === 'index.php') {
    if ($type === 'admin' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Administrateur')) {
        header('Location: /admin.php'); 
        exit;
    }
    if ($type === 'moderateur' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Moderateur')) {
        header('Location: /moderateur.php'); 
        exit;
    }
}

// Unification pour le front
$auth = $_SESSION['auth'] ?? $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
$user = $_SESSION['user'];

try {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("SELECT id, email, pseudo, role, status, credits, user_type FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;

    $stmt = $pdo->prepare("
        SELECT id, ville_depart, ville_arrivee, date_depart, status
        FROM trips
        WHERE chauffeur_id = ?
        ORDER BY date_depart DESC
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $myTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $userData = $user;
    $myTrips = [];
    $error = "Erreur lors du chargement du profil.";
}

// Traitement mise à jour type utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user_type') {
    $new_type = $_POST['user_type'] ?? '';
    if (in_array($new_type, ['passager', 'chauffeur', 'les_deux'])) {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?");
            $stmt->execute([$new_type, $user['id']]);
            $userData['user_type'] = $new_type;
            $success = "Type d'utilisateur mis à jour.";
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
    <h1>
        <a href="/index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span> EcoRide
        </a>
    </h1>
    <nav id="navbar"></nav>
</header>

<script>
  window.ecorideUser = <?= $auth ? json_encode($auth, JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>

<main style="max-width:1000px;margin:40px auto;padding:0 20px;">
    <h2 style="color:#2d3436;font-size:2rem;margin-bottom:30px;display:flex;align-items:center;gap:12px;">
        <span class="material-icons" style="font-size:2.2rem;color:#00b894;">account_circle</span>
        Mon Profil
    </h2>

    <?php if (!empty($error)): ?>
      <div class="message-error" style="margin-bottom:25px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="message-success" style="margin-bottom:25px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Informations du profil -->
    <div style="border-radius:12px;padding:30px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:35px;">
        <h3 style="color:#2d3436;font-size:1.4rem;margin-bottom:25px;border-bottom:2px solid #00b894;padding-bottom:10px;">
            📋 Informations personnelles
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">email</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Email</strong>
                    <p style="margin:0;color:#2d3436;font-size:1rem;"><?= htmlspecialchars($userData['email'] ?? '') ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">person</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Pseudo</strong>
                    <p style="margin:0;color:#2d3436;font-size:1rem;"><?= htmlspecialchars($userData['pseudo'] ?? '') ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">admin_panel_settings</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Rôle</strong>
                    <p style="margin:0;"><span class="admin-badge <?= strtolower($userData['role'] ?? '') ?>"><?= htmlspecialchars($userData['role'] ?? '') ?></span></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">check_circle</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Statut</strong>
                    <p style="margin:0;"><span class="admin-badge <?= htmlspecialchars($userData['status'] ?? '') ?>"><?= htmlspecialchars($userData['status'] ?? '') ?></span></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">account_balance_wallet</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Crédits</strong>
                    <p style="margin:0;color:#00b894;font-size:1.5rem;font-weight:700;"><?= (int)($userData['credits'] ?? 0) ?></p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:15px;padding:15px;border-radius:8px;">
                <span class="material-icons" style="color:#00b894;font-size:32px;">directions_car</span>
                <div>
                    <strong style="color:#636e72;font-size:0.9rem;display:block;margin-bottom:4px;">Type d'utilisateur</strong>
                    <p style="margin:0;"><span class="admin-badge <?= htmlspecialchars($userData['user_type'] ?? 'passager') ?>"><?= htmlspecialchars($userData['user_type'] ?? 'passager') ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modifier le type d'utilisateur -->
    <div style="border-radius:12px;padding:30px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:35px;">
        <h3 style="color:#2d3436;font-size:1.4rem;margin-bottom:20px;border-bottom:2px solid #00b894;padding-bottom:10px;">
            🔄 Modifier mon type d'utilisateur
        </h3>
        <form method="POST" style="max-width:500px;">
            <input type="hidden" name="action" value="update_user_type">
            <label for="user_type" style="display:block;font-weight:600;color:#2d3436;margin-bottom:10px;font-size:1rem;">Je suis :</label>
            <select name="user_type" id="user_type" required style="width:100%;padding:12px 16px;border:2px solid #e8f4f0;border-radius:8px;font-size:1rem;margin-bottom:20px;">
                <option value="passager" <?= ($userData['user_type'] ?? 'passager') === 'passager' ? 'selected' : '' ?>>🚶 Passager</option>
                <option value="chauffeur" <?= ($userData['user_type'] ?? 'passager') === 'chauffeur' ? 'selected' : '' ?>>🚗 Chauffeur</option>
                <option value="les_deux" <?= ($userData['user_type'] ?? 'passager') === 'les_deux' ? 'selected' : '' ?>>🚗🚶 Les deux</option>
            </select>
            <button type="submit" class="btn-primary" style="padding:12px 30px;font-size:1rem;">Mettre à jour</button>
        </form>
    </div>

 <!-- Gestion du compte -->
<div class="profil-section compte-section">
    <h3 class="profil-titre">⚙️ Gestion de mon compte</h3>
    <?php 
    $user_type = $userData['user_type'] ?? 'passager';
    $is_driver = ($user_type === 'chauffeur' || $user_type === 'les_deux');
    ?>
    
    <div class="profil-liens-grid">
    <?php if ($is_driver): ?>
        <a href="/backend/gestion_vehicules.php" class="profil-lien lien-vehicules">
            <span class="material-icons">directions_car</span>
            <span>Gérer mes véhicules</span>
        </a>

        <a href="/backend/gestion_preferences.php" class="profil-lien lien-preferences">
            <span class="material-icons">settings</span>
            <span>Gérer mes préférences</span>
        </a>

        <a href="/backend/espace_chauffeur.php" class="profil-lien lien-espace-chauffeur">
            <span class="material-icons">add_road</span>
            <span>Espace chauffeur</span>
        </a>

        <a href="/backend/mes_trajets.php" class="profil-lien lien-trajets">
            <span class="material-icons">list</span>
            <span>Voir mes trajets</span>
        </a>
    <?php endif; ?>
    </div>
</div>

<!-- Derniers trajets -->
<div class="profil-section trajets-section">
    <h3 class="profil-titre">🚗 Vos derniers trajets</h3>
    <?php if (!$myTrips): ?>
        <p class="texte-vide">Aucun trajet pour le moment.</p>
    <?php else: ?>
        <div class="trajets-liste">
            <?php foreach ($myTrips as $t): ?>
                <div class="trajet-item">
                    <div class="trajet-infos">
                        <span class="material-icons">location_on</span>
                        <span class="trajet-ville">
                            <?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?>
                        </span>
                        <span class="trajet-date"><?= date('d/m/Y', strtotime($t['date_depart'])) ?></span>
                    </div>
                    <span class="admin-badge <?= htmlspecialchars($t['status']) ?>">
                        <?= htmlspecialchars($t['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bouton retour -->
<div class="retour-section">
    <a href="/index.php" class="btn-retour">
        <span class="material-icons">arrow_back</span>
        Retour à l'accueil
    </a>
</div>

</main>

<script src="/assets/js/navbar.js?v=1"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      if (typeof renderMenu === 'function') renderMenu(window.ecorideUser || null);
  });
</script>
</body>
</html>

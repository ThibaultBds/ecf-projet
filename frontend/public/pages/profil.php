<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

require_once __DIR__ . '/../../../backend/config/guard.php';
requireLogin();

// Type/role de l'utilisateur
$type = $_SESSION['user']['type'] ?? ($_SESSION['user']['role'] ?? 'utilisateur');
// Redirections éventuelles si tu sépares les dashboards
if ($type === 'admin' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Administrateur')) {
    header('Location: /admin.php'); exit;
}
if ($type === 'moderateur' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Moderateur')) {
    header('Location: /moderateur.php'); exit;
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
    <!-- CSS absolu -->
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

<!-- Session unifiée exposée au front -->
<script>
  window.ecorideUser = <?= $auth ? json_encode($auth, JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>

<main style="max-width:1000px;margin:40px auto;padding:0 20px;">
    <h2>Mon Profil</h2>

    <?php if (!empty($error)): ?>
      <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-section">
        <div class="profile-info">
            <div class="profile-item">
                <span class="material-icons">email</span>
                <div>
                    <strong>Email</strong>
                    <p><?= htmlspecialchars($userData['email'] ?? '') ?></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons">person</span>
                <div>
                    <strong>Pseudo</strong>
                    <p><?= htmlspecialchars($userData['pseudo'] ?? '') ?></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons">admin_panel_settings</span>
                <div>
                    <strong>Rôle</strong>
                    <p><span class="admin-badge <?= strtolower($userData['role'] ?? '') ?>"><?= htmlspecialchars($userData['role'] ?? '') ?></span></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons">check_circle</span>
                <div>
                    <strong>Statut</strong>
                    <p><span class="admin-badge <?= htmlspecialchars($userData['status'] ?? '') ?>"><?= htmlspecialchars($userData['status'] ?? '') ?></span></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons">account_balance_wallet</span>
                <div>
                    <strong>Crédits</strong>
                    <p class="number"><?= (int)($userData['credits'] ?? 0) ?></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons">directions_car</span>
                <div>
                    <strong>Type d'utilisateur</strong>
                    <p><span class="admin-badge <?= htmlspecialchars($userData['user_type'] ?? 'passager') ?>"><?= htmlspecialchars($userData['user_type'] ?? 'passager') ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <h3>Modifier mon type d'utilisateur</h3>
    <form method="POST" class="form-container">
        <input type="hidden" name="action" value="update_user_type">
        <label for="user_type">Je suis :</label>
        <select name="user_type" id="user_type" required>
            <option value="passager" <?= ($userData['user_type'] ?? 'passager') === 'passager' ? 'selected' : '' ?>>Passager</option>
            <option value="chauffeur" <?= ($userData['user_type'] ?? 'passager') === 'chauffeur' ? 'selected' : '' ?>>Chauffeur</option>
            <option value="les_deux" <?= ($userData['user_type'] ?? 'passager') === 'les_deux' ? 'selected' : '' ?>>Les deux</option>
        </select>
        <button type="submit" class="btn-primary">Mettre à jour</button>
    </form>

    <h3>Gestion de mon compte</h3>
    <div class="account-links">
    <a href="/backend/gestion_vehicules.php" class="account-link">

       <span class="material-icons">directions_car</span>
            Gérer mes véhicules
        </a>
        <a href="/backend/gestion_preferences.php" class="account-link">
    
            <span class="material-icons">settings</span>
            Gérer mes préférences
        </a>
        <a href="/backend/espace_chauffeur.php" class="account-link">
            <span class="material-icons">add_road</span>
            Espace chauffeur (créer un trajet)
        </a>
        <a href="mes_trajets.php" class="account-link">
            <span class="material-icons">list</span>
            Voir mes trajets
        </a>
    </div>

    <h3>Vos derniers trajets</h3>
    <?php if (!$myTrips): ?>
        <p>Aucun trajet.</p>
    <?php else: ?>
        <div class="trips-list">
            <?php foreach ($myTrips as $t): ?>
                <div class="trip-item">
                    <div class="trip-route">
                        <span class="material-icons">location_on</span>
                        <span><?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?></span>
                    </div>
                    <div class="trip-status">
                        <span class="admin-badge <?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p><a href="/index.php">← Retour à l’accueil</a></p>
</main>

<!-- JS absolu -->
<script src="/assets/js/navbar.js?v=1"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      if (typeof renderMenu === 'function') renderMenu(window.ecorideUser || null);
  });
</script>
</body>
</html>

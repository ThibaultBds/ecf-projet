<?php
session_start();
require_once 'config/autoload.php';
useClass('Database');

if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];
$error = '';
$success = '';

// Récupérer les préférences actuelles
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT preferences FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $current_prefs_json = $stmt->fetchColumn();
    $current_prefs = json_decode($current_prefs_json ?: '{}', true);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des préférences.";
    $current_prefs = [];
}

if ($_POST) {
    try {
        $preferences = [
            'fumeur' => $_POST['fumeur'] ?? 'non-specifie',
            'animaux' => $_POST['animaux'] ?? 'non-specifie',
            'custom' => trim($_POST['custom'] ?? '')
        ];
        $prefs_json = json_encode($preferences);

        $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE id = ?");
        $stmt->execute([$prefs_json, $user['id']]);
        $success = "Préférences mises à jour avec succès !";
        $current_prefs = $preferences;

    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Préférences - EcoRide</title>
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1><a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;"><span class="material-icons">eco</span> EcoRide</a></h1>
    </header>

    <main class="member-container">
        <h2><span class="material-icons">tune</span> Mes Préférences de Conduite</h2>
        <p>Ces informations seront visibles par les passagers.</p>

        <?php if ($success): ?><div class="message-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" class="form-container">
            <div>
                <label for="fumeur">Fumeurs</label>
                <select name="fumeur" id="fumeur">
                    <option value="non-specifie" <?= ($current_prefs['fumeur'] ?? '') === 'non-specifie' ? 'selected' : '' ?>>Non spécifié</option>
                    <option value="oui" <?= ($current_prefs['fumeur'] ?? '') === 'oui' ? 'selected' : '' ?>>J'accepte les fumeurs</option>
                    <option value="non" <?= ($current_prefs['fumeur'] ?? '') === 'non' ? 'selected' : '' ?>>Voyage non-fumeur</option>
                </select>
            </div>
            <div>
                <label for="animaux">Animaux</label>
                <select name="animaux" id="animaux">
                    <option value="non-specifie" <?= ($current_prefs['animaux'] ?? '') === 'non-specifie' ? 'selected' : '' ?>>Non spécifié</option>
                    <option value="oui" <?= ($current_prefs['animaux'] ?? '') === 'oui' ? 'selected' : '' ?>>J'accepte les animaux</option>
                    <option value="non" <?= ($current_prefs['animaux'] ?? '') === 'non' ? 'selected' : '' ?>>Je n'accepte pas les animaux</option>
                </select>
            </div>
            <div>
                <label for="custom">Autre préférence (ex: "Musique bienvenue", "Discussions tranquilles")</label>
                <input type="text" id="custom" name="custom" value="<?= htmlspecialchars($current_prefs['custom'] ?? '') ?>" maxlength="100">
            </div>
            <button type="submit" class="btn-primary">Enregistrer les préférences</button>
        </form>
        <div style="text-align:center;margin-top:30px;">
            <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
        </div>
    </main>
    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
</body>
</html>




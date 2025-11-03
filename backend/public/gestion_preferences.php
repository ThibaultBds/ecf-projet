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
        $musique = $_POST['musique'] ?? '';
        $animaux = $_POST['animaux'] ?? '';
        $discussion = $_POST['discussion'] ?? '';
        $fumeur = $_POST['fumeur'] ?? '';

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
    <link rel="stylesheet" href="../assets/css/style.css?v=2025">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header"></header>

    <main class="preferences-container">
        <h2><span class="material-icons">tune</span> Mes Préférences</h2>
        <p>Ces informations seront visibles par les passagers pour mieux correspondre à votre style de trajet.</p>

        <?php if ($success): ?><div class="message-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" class="preferences-form">
            <label for="musique">Musique</label>
            <select id="musique" name="musique" required>
                <option value="">Choisir...</option>
                <option value="oui" <?= ($preferences['musique'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                <option value="non" <?= ($preferences['musique'] ?? '') === 'non' ? 'selected' : '' ?>>Non</option>
            </select>

            <label for="animaux">Animaux acceptés</label>
            <select id="animaux" name="animaux" required>
                <option value="">Choisir...</option>
                <option value="oui" <?= ($preferences['animaux'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                <option value="non" <?= ($preferences['animaux'] ?? '') === 'non' ? 'selected' : '' ?>>Non</option>
            </select>

            <label for="discussion">Discussion pendant le trajet</label>
            <select id="discussion" name="discussion" required>
                <option value="">Choisir...</option>
                <option value="plaisir" <?= ($preferences['discussion'] ?? '') === 'plaisir' ? 'selected' : '' ?>>Avec plaisir</option>
                <option value="un_peu" <?= ($preferences['discussion'] ?? '') === 'un_peu' ? 'selected' : '' ?>>Un peu</option>
                <option value="silence" <?= ($preferences['discussion'] ?? '') === 'silence' ? 'selected' : '' ?>>Plutôt silence</option>
            </select>

            <label for="fumeur">Fumeur</label>
            <select id="fumeur" name="fumeur" required>
                <option value="">Choisir...</option>
                <option value="oui" <?= ($preferences['fumeur'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                <option value="non" <?= ($preferences['fumeur'] ?? '') === 'non' ? 'selected' : '' ?>>Non</option>
            </select>

            <button type="submit" class="btn-primary">
                <span class="material-icons">save</span> Enregistrer les préférences
            </button>
        </form>

        <div style="text-align:center;margin-top:30px;">
            <a href="/frontend/public/pages/profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
        </div>
    </main>

    <script>
        window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    </script>
    <script src="../assets/js/navbar.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

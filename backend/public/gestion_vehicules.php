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

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (user_id, marque, modele, couleur, plaque, energie, places_disponibles, date_immatriculation)
            VALUES (?, ?, ?, ?, ?, ?, ?, '2020-01-01')
        ");
        $stmt->execute([$user['id'], $marque, $modele, $couleur, $plaque, $energie, $places]);
        
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Véhicules - EcoRide</title>
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1><a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;"><span class="material-icons">eco</span> EcoRide</a></h1>
    </header>

    <main class="member-container">
        <h2><span class="material-icons">directions_car</span> Mes Véhicules</h2>

        <?php if ($success): ?><div class="message-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <h3>Ajouter un véhicule</h3>
        <form method="POST" class="form-container">
            <input type="hidden" name="action" value="add_vehicle">
            <div>
                <label for="marque">Marque</label>
                <input type="text" id="marque" name="marque" required value="<?= htmlspecialchars($_POST['marque'] ?? '') ?>">
            </div>
            <div>
                <label for="modele">Modèle</label>
                <input type="text" id="modele" name="modele" required value="<?= htmlspecialchars($_POST['modele'] ?? '') ?>">
            </div>
            <div>
                <label for="couleur">Couleur</label>
                <input type="text" id="couleur" name="couleur" required value="<?= htmlspecialchars($_POST['couleur'] ?? '') ?>">
            </div>
            <div>
                <label for="plaque">Plaque d'immatriculation</label>
                <input type="text" id="plaque" name="plaque" required value="<?= htmlspecialchars($_POST['plaque'] ?? '') ?>" placeholder="Ex: AB-123-CD">
            </div>
            <div>
                <label for="energie">Type d'énergie</label>
                <select name="energie" id="energie" required>
                    <option value="">Choisir...</option>
                    <option value="essence" <?= ($_POST['energie'] ?? '') === 'essence' ? 'selected' : '' ?>>Essence</option>
                    <option value="diesel" <?= ($_POST['energie'] ?? '') === 'diesel' ? 'selected' : '' ?>>Diesel</option>
                    <option value="electrique" <?= ($_POST['energie'] ?? '') === 'electrique' ? 'selected' : '' ?>>Électrique</option>
                    <option value="hybride" <?= ($_POST['energie'] ?? '') === 'hybride' ? 'selected' : '' ?>>Hybride</option>
                </select>
            </div>
            <div>
                <label for="places">Nombre de places</label>
                <input type="number" id="places" name="places" min="1" max="8" value="<?= htmlspecialchars($_POST['places'] ?? '4') ?>" required>
            </div>
            <button type="submit" class="btn-primary">Ajouter le véhicule</button>
        </form>

        <h3>Mes véhicules (<?= count($vehicles) ?>)</h3>
        <?php if (empty($vehicles)): ?>
            <p>Aucun véhicule enregistré.</p>
        <?php else: ?>
            <div class="vehicles-list">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card" style="border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:8px;">
                        <h4><?= htmlspecialchars($vehicle['marque']) ?> <?= htmlspecialchars($vehicle['modele']) ?></h4>
                        <p><strong>Couleur:</strong> <?= htmlspecialchars($vehicle['couleur']) ?></p>
                        <p><strong>Plaque:</strong> <?= htmlspecialchars($vehicle['plaque']) ?></p>
                        <p><strong>Énergie:</strong> <?= htmlspecialchars(ucfirst($vehicle['energie'])) ?></p>
                        <p><strong>Places:</strong> <?= (int)$vehicle['places_disponibles'] ?></p>
                        <?php if ($vehicle['energie'] === 'electrique'): ?>
                            <span style="background:#00b894;color:white;padding:2px 8px;border-radius:12px;font-size:12px;">⚡ Écologique</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align:center;margin-top:30px;">
            <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
        </div>
    </main>
    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
</body>
</html>




<?php
session_start();
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];
$error = '';
$success = '';

// Traitement des actions (annulation, changement de statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = getDatabase();
        $trip_id = (int)($_POST['trip_id'] ?? 0);

        if ($_POST['action'] === 'cancel_participation') {
            // Logique d'annulation pour un passager
            $stmt = $pdo->prepare("DELETE FROM trip_participants WHERE trip_id = ? AND user_id = ?");
            $stmt->execute([$trip_id, $user['id']]);

            $stmt = $pdo->prepare("UPDATE trips SET places_restantes = places_restantes + 1 WHERE id = ?");
            $stmt->execute([$trip_id]);
            
            // Rembourser les crédits (à implémenter)
            $success = "Votre participation a été annulée.";

        } elseif ($_POST['action'] === 'update_trip_status') {
            // Logique pour le chauffeur
            $new_status = $_POST['status'] ?? '';
            if (in_array($new_status, ['en_cours', 'termine', 'annule'])) {
                $stmt = $pdo->prepare("UPDATE trips SET status = ? WHERE id = ? AND chauffeur_id = ?");
                $stmt->execute([$new_status, $trip_id, $user['id']]);
                $success = "Le statut du trajet a été mis à jour.";
            }
        }
        header('Location: mes_trajets.php?success=' . urlencode($success));
        exit();
    } catch (Exception $e) {
        $error = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Récupérer les trajets conduits et les participations
try {
    $pdo = getDatabase();
    // Trajets conduits
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE chauffeur_id = ? ORDER BY date_depart DESC");
    $stmt->execute([$user['id']]);
    $trajets_conduits = $stmt->fetchAll();

    // Participations
    $stmt = $pdo->prepare("SELECT t.*, tp.has_reviewed FROM trips t JOIN trip_participants tp ON t.id = tp.trip_id WHERE tp.user_id = ? ORDER BY t.date_depart DESC");
    $stmt->execute([$user['id']]);
    $participations = $stmt->fetchAll();

} catch (Exception $e) {
    $error = "Erreur lors du chargement de vos trajets.";
    $trajets_conduits = [];
    $participations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Trajets - EcoRide</title>
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

    <main class="member-container" style="max-width:900px;margin:auto;padding:30px 0;">
        <h2 style="display:flex;align-items:center;gap:10px;color:#00b894;font-size:2.2rem;margin-bottom:30px;">
            <span class="material-icons" style="font-size:2.5rem;vertical-align:middle;">history</span> Mes Trajets
        </h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="message-success" style="margin-bottom:20px;"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="trajets-section" style="margin-bottom:40px;">
            <?php if (!empty($trajets_conduits)): ?>
                <h3 style="color:#0984e3;font-size:1.3rem;margin-bottom:15px;">Trajets que je conduis</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
                <?php foreach ($trajets_conduits as $trajet): ?>
                    <div class="ride-card-history" style="background:#f8f9fa;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:0;">
                        <p style="font-size:1.1rem;font-weight:600;color:#00b894;margin-bottom:8px;"><span class="material-icons" style="vertical-align:middle;margin-right:6px;">directions_car</span><?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?></p>
                        <p style="color:#636e72;margin-bottom:10px;">Départ : <?= date('d/m/Y H:i', strtotime($trajet['date_depart'])) ?> <span style="margin-left:10px;">Statut: <strong><?= ucfirst($trajet['status']) ?></strong></span></p>
                        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                            <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">
                            <?php if ($trajet['status'] === 'planifie'): ?>
                                <input type="hidden" name="status" value="en_cours">
                                <button type="submit" name="action" value="update_trip_status" class="btn-secondary">Démarrer</button>
                                <button type="submit" name="action" value="update_trip_status" formaction="mes_trajets.php" formmethod="post" name="status" value="annule" class="btn-danger">Annuler le trajet</button>
                            <?php elseif ($trajet['status'] === 'en_cours'): ?>
                                <input type="hidden" name="status" value="termine">
                                <button type="submit" name="action" value="update_trip_status" class="btn-primary">Terminer</button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="trajets-section" style="margin-bottom:40px;">
            <h3 style="color:#0984e3;font-size:1.3rem;margin-bottom:15px;">Trajets où je suis passager</h3>
            <?php if (empty($participations)): ?>
                <p style="color:#636e72;">Vous ne participez à aucun trajet. <a href="covoiturages.php" style="color:#00b894;font-weight:600;">Trouver un trajet</a></p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
                <?php foreach ($participations as $trajet): ?>
                    <div class="ride-card-history" style="background:#f8f9fa;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:0;">
                        <p style="font-size:1.1rem;font-weight:600;color:#00b894;margin-bottom:8px;"><span class="material-icons" style="vertical-align:middle;margin-right:6px;">person</span><?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?></p>
                        <p style="color:#636e72;margin-bottom:10px;">Départ : <?= date('d/m/Y H:i', strtotime($trajet['date_depart'])) ?> <span style="margin-left:10px;">Statut: <strong><?= ucfirst($trajet['status']) ?></strong></span></p>
                        <?php if ($trajet['status'] === 'planifie'): ?>
                            <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                                <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">
                                <button type="submit" name="action" value="cancel_participation" class="btn-danger">Annuler ma participation</button>
                            </form>
                        <?php elseif ($trajet['status'] === 'termine' && !$trajet['has_reviewed']): ?>
                            <details style="margin-top:10px;">
                                <summary style="cursor:pointer;font-weight:600;color:#0984e3;">Noter ce trajet</summary>
                                <form action="submit_review.php" method="POST" class="form-container" style="margin-top:10px;">
                                    <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">
                                    <input type="hidden" name="reviewed_id" value="<?= $trajet['chauffeur_id'] ?>">
                                    <label for="note-<?= $trajet['id'] ?>">Note (sur 5)</label>
                                    <input type="number" id="note-<?= $trajet['id'] ?>" name="note" min="1" max="5" required style="width:60px;">
                                    <label for="commentaire-<?= $trajet['id'] ?>">Commentaire</label>
                                    <textarea id="commentaire-<?= $trajet['id'] ?>" name="commentaire" required style="width:100%;min-height:60px;"></textarea>
                                    <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_problem"> Signaler un problème</label>
                                    <button type="submit" class="btn-primary">Envoyer</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align:center;margin-top:30px;">
            <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;font-size:1.1rem;display:inline-flex;align-items:center;gap:6px;"><span class="material-icons" style="vertical-align:middle;">arrow_back</span> Retour au profil</a>
        </div>
    </main>
    <script src="../assets/js/navbar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.ecorideUser && window.ecorideUser.email) {
        renderMenu(window.ecorideUser);
    } else {
        renderMenu();
    }
});
</script>
</body>
</html>



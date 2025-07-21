<?php
session_start();
require_once 'config/autoload.php';
useClass('Database');

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];

// Récupérer les participations de l'utilisateur
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("
        SELECT tp.*, t.ville_depart, t.ville_arrivee, t.date_depart, t.prix,
               u.pseudo as conducteur, v.marque, v.modele
        FROM trip_participants tp
        JOIN trips t ON tp.trip_id = t.id
        JOIN users u ON t.chauffeur_id = u.id
        JOIN vehicles v ON t.vehicle_id = v.id
        WHERE tp.user_id = ?
        ORDER BY t.date_depart DESC
    ");
    $stmt->execute([$user['id']]);
    $mes_participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mes_participations = [];
}

function getParticipationStatusColor($status) {
    $colors = [
        'confirme' => '#00b894',
        'annule'   => '#e74c3c',
        'termine'  => '#636e72'
    ];
    return $colors[$status] ?? '#636e72';
}

function getParticipationStatusLabel($status) {
    $labels = [
        'confirme' => 'Confirmé',
        'annule'   => 'Annulé',
        'termine'  => 'Terminé'
    ];
    return $labels[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes participations - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
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

    <main>
        <div style="max-width:1000px;margin:40px auto;padding:0 20px;">
            <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <h2 style="color:#2d3436;margin-bottom:30px;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">history</span>
                    Mes participations
                </h2>

                <?php if (empty($mes_participations)): ?>
                    <div style="text-align:center;padding:40px;color:#636e72;">
                        <span class="material-icons" style="font-size:64px;margin-bottom:20px;opacity:0.5;">event_busy</span>
                        <h3>Aucune participation</h3>
                        <p>Vous n'avez participé à aucun covoiturage pour le moment.</p>
                        <a href="covoiturages.php" class="btn-primary" style="margin-top:15px;">
                            Rechercher des covoiturages
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:20px;">
                        <?php foreach ($mes_participations as $participation): ?>
                            <div style="border:1px solid #ddd;border-radius:8px;padding:20px;background:#f8f9fa;">
                                <h4 style="margin:0 0 10px 0;color:#2d3436;">
                                    <?= htmlspecialchars($participation['ville_depart']) ?> → <?= htmlspecialchars($participation['ville_arrivee']) ?>
                                </h4>
                                <p style="margin:5px 0;color:#636e72;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:18px;">schedule</span>
                                    <?= date('d/m/Y à H:i', strtotime($participation['date_depart'])) ?>
                                </p>
                                <p style="margin:5px 0;color:#636e72;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:18px;">person</span>
                                    Conducteur: <?= htmlspecialchars($participation['conducteur']) ?>
                                </p>
                                <p style="margin:5px 0;color:#636e72;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:18px;">directions_car</span>
                                    <?= htmlspecialchars($participation['marque']) ?> <?= htmlspecialchars($participation['modele']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align:center;margin-top:30px;">
                    <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
                </div>
            </div>
        </div>
    </main>
    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
</body>
</html>




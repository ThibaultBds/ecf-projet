<?php
session_start();

// Bon chemin pour l'autoloader
require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/autoload.php';
useClass('Database');

// Vérification du rôle
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['type'], ['Moderateur', 'Administrateur'])) {
    header("Location: login_secure.php");
    exit();
}

$user = $_SESSION['user'];

// Récupérer les avis en attente et signalements
try {
    $pdo = getDatabase();
    $stmt = $pdo->query("
        SELECT r.*, t.ville_depart, t.ville_arrivee,
               u1.pseudo as evaluateur, u2.pseudo as evalue
        FROM reviews r
        JOIN trips t ON r.trip_id = t.id
        JOIN users u1 ON r.reviewer_id = u1.id
        JOIN users u2 ON r.reviewed_id = u2.id
        WHERE r.status = 'en_attente'
        ORDER BY r.created_at DESC
    ");
    $avis_en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT r.*, u.pseudo as user_pseudo FROM reports r JOIN users u ON r.user_id = u.id WHERE r.status = 'ouvert' ORDER BY r.created_at DESC");
    $signalements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $avis_en_attente = [];
    $signalements = [];
    $error = "Erreur lors du chargement des données.";
}

// Traitement des actions de modération
$message = '';
if (isset($_GET['action']) && isset($_GET['id']) && isset($pdo)) {
    try {
        $action = $_GET['action'];
        $id = (int)$_GET['id'];
        
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'valide', validated_by = ?, validated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $id]);
            $message = "Avis approuvé et publié.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'refuse', validated_by = ?, validated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $id]);
            $message = "Avis rejeté.";
        }
        // Log activité
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], 'Modération avis', "Action: $action, Avis ID: $id", $_SERVER['REMOTE_ADDR'] ?? '']);
        header('Location: moderateur.php?msg=' . urlencode($message));
        exit();
    } catch (Exception $e) {
        $error = "Erreur lors du traitement : " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modération - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- BON CHEMIN CSS -->
    <link rel="stylesheet" href="/ecoride/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR PAR JS (ou place ici le HTML de la nav si tu veux une nav statique) -->
    <nav id="main-navbar"></nav>
    <header class="container-header">
        <h1>
            <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
    </header>

    <main style="max-width:1200px;margin:40px auto;padding:0 20px;">
        <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <h2 style="color:#2d3436;margin-bottom:30px;">
                <span class="material-icons" style="vertical-align:middle;color:#f39c12;margin-right:10px;">moderation</span>
                Panel Modérateur
            </h2>
            
            <div style="background:#fff8e1;border:1px solid #f39c12;border-radius:8px;padding:15px;margin-bottom:30px;">
                <p style="margin:0;color:#f39c12;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">info</span>
                    <strong>Connecté en tant que modérateur :</strong> <?= htmlspecialchars($user['email']) ?>
                </p>
            </div>

            <?php if (isset($message)): ?>
                <div style="background:#e6f9ed;color:#00b894;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #00b894;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">check_circle</span>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h3>Avis en attente de validation (<?= count($avis_en_attente) ?>)</h3>
            
            <?php if (empty($avis_en_attente)): ?>
                <div style="text-align:center;padding:40px;color:#636e72;">
                    <span class="material-icons" style="font-size:64px;margin-bottom:20px;opacity:0.5;">check_circle</span>
                    <h4>Aucun avis en attente</h4>
                    <p>Tous les avis ont été traités !</p>
                </div>
            <?php else: ?>
                <div style="display:grid;gap:20px;margin-top:20px;">
                    <?php foreach ($avis_en_attente as $avis): ?>
                        <div style="border:1px solid #ddd;border-radius:12px;padding:20px;background:white;">
                            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px;">
                                <div>
                                    <h4 style="margin:0 0 5px 0;color:#2d3436;">
                                        Trajet: <?= htmlspecialchars($avis['ville_depart']) ?> → <?= htmlspecialchars($avis['ville_arrivee']) ?>
                                    </h4>
                                    <p style="margin:0;color:#636e72;font-size:14px;">
                                        <strong><?= htmlspecialchars($avis['evaluateur']) ?></strong> évalue
                                        <strong><?= htmlspecialchars($avis['evalue']) ?></strong>
                                    </p>
                                    <p style="margin:5px 0 0 0;color:#636e72;font-size:12px;">
                                        Le <?= date('d/m/Y à H:i', strtotime($avis['created_at'])) ?>
                                    </p>
                                </div>
                                <div style="text-align:right;">
                                    <div style="display:flex;align-items:center;gap:5px;margin-bottom:5px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="material-icons" style="color:<?= $i <= $avis['note'] ? '#ffd700' : '#ddd' ?>;font-size:20px;">star</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span style="font-size:14px;color:#636e72;"><?= $avis['note'] ?>/5</span>
                                </div>
                            </div>
                            <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:15px;">
                                <p style="margin:0;color:#2d3436;font-style:italic;">
                                    "<?= htmlspecialchars($avis['commentaire']) ?>"
                                </p>
                            </div>
                            <div style="display:flex;justify-content:flex-end;gap:10px;">
                                <button onclick="rejectReview(<?= $avis['id'] ?>)"
                                        style="background:#e74c3c;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons" style="font-size:18px;">close</span>
                                    Rejeter
                                </button>
                                <button onclick="approveReview(<?= $avis['id'] ?>)"
                                        style="background:#00b894;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons" style="font-size:18px;">check</span>
                                    Approuver
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 style="margin-top:40px;">Signalements (<?= count($signalements) ?>)</h3>
            <?php if (empty($signalements)): ?>
                <p>Aucun signalement à traiter.</p>
            <?php else: ?>
                <div style="display:grid;gap:20px;margin-top:20px;">
                    <?php foreach ($signalements as $report): ?>
                        <div style="border:1px solid #e74c3c;border-radius:12px;padding:20px;background:#fff5f5;">
                            <h4 style="margin:0 0 10px 0;">Signalement sur le trajet #<?= $report['trip_id'] ?></h4>
                            <p><strong>Signalé par :</strong> <?= htmlspecialchars($report['user_pseudo']) ?></p>
                            <p><strong>Détails :</strong> <?= htmlspecialchars($report['details']) ?></p>
                            <small>Le <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="text-align:center;margin-top:30px;">
                <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
            </div>
        </div>
    </main>

    <!-- BON CHEMIN JS NAVBAR -->
    <script src="/ecoride/frontend/public/assets/js/navbar.js"></script>
    <script>
        function approveReview(id) {
            if (confirm('Approuver cet avis ?')) {
                window.location.href = '?action=approve&id=' + id;
            }
        }
        function rejectReview(id) {
            if (confirm('Rejeter cet avis ? Cette action est irréversible.')) {
                window.location.href = '?action=reject&id=' + id;
            }
        }
    </script>
</body>
</html>
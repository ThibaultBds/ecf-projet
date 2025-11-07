<?php
session_start();

// Charger l'autoloader et importer la classe Database
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

// Générer token CSRF pour les formulaires
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer les paramètres de recherche

$depart   = $_GET['depart']   ?? '';
$arrivee  = $_GET['arrivee']  ?? '';
$date     = $_GET['date']     ?? '';
$prix_max = isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : null;
$note_min = isset($_GET['note_min']) ? (float)$_GET['note_min'] : null;
$ecologique = isset($_GET['ecologique']) ? $_GET['ecologique'] : '';

try {
    // Récupérer les covoiturages avec filtres de base via SQL
    $covoiturages = getTrips($depart, $arrivee, $date);
    
    // DEBUG: Afficher le nombre de résultats
    error_log("DEBUG covoiturages.php: Nombre de trajets trouvés = " . count($covoiturages));
    error_log("DEBUG covoiturages.php: Filtres - depart='$depart', arrivee='$arrivee', date='$date'");

    // Filtrage avancé côté serveur
    if ($prix_max !== null && $prix_max !== '') {
        $covoiturages = array_filter($covoiturages, function($c) use ($prix_max) {
            return $c['prix'] <= $prix_max;
        });
    }
    if ($note_min !== null && $note_min !== '') {
        $covoiturages = array_filter($covoiturages, function($c) use ($note_min) {
            return isset($c['rating']) ? $c['rating'] >= $note_min : true;
        });
    }
    if ($ecologique !== '') {
        $covoiturages = array_filter($covoiturages, function($c) use ($ecologique) {
            return $ecologique == '1' ? $c['is_ecological'] : !$c['is_ecological'];
        });
    }

    // Réindexer le tableau pour éviter les clés manquantes
    $covoiturages = array_values($covoiturages);

} catch (Exception $e) {
    $covoiturages = [];
    $error_message = "Erreur lors du chargement des covoiturages.";
    error_log("Erreur getTrips: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Covoiturages - EcoRide</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <section class="page-header">
            <h2>Covoiturages disponibles</h2>
            <p>Trouvez le trajet qui vous convient !</p>
        </section>

        <!-- Formulaire de recherche -->
        <section>
            <form method="GET" class="search-bar">
                <input type="text"   name="depart"   placeholder="Ville de départ" value="<?= htmlspecialchars($depart) ?>"   list="villes">
                <input type="text"   name="arrivee"  placeholder="Ville d'arrivée" value="<?= htmlspecialchars($arrivee) ?>" list="villes">
                <input type="date"   name="date"     value="<?= htmlspecialchars($date) ?>">
                <button type="submit">Rechercher</button>
                <datalist id="villes">
                    <option value="Paris"><option value="Lyon"><option value="Marseille">
                    <option value="Nice"><option value="Toulouse"><option value="Bordeaux">
                    <option value="Lille"><option value="Nantes"><option value="Strasbourg">
                    <option value="Dijon">
                </datalist>
            </form>
        </section>

        <section class="covoiturages-list" style="max-width:1000px;margin:0 auto;padding:20px;">
            <?php if (isset($error_message)): ?>
                <div style="background:#ffeaea;color:#e74c3c;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Filtres avancés -->
            <div style="text-align:center;margin-bottom:30px;">
                <button id="toggle-filters" style="background:#636e72;color:white;padding:10px 24px;border:none;border-radius:8px;font-weight:500;cursor:pointer;transition:background 0.2s;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">tune</span> Filtres avancés
                </button>
                <div id="advanced-filters" style="display:none;margin-top:20px;text-align:left;background:#f8f9fa;padding:20px;border-radius:8px;max-width:500px;margin-left:auto;margin-right:auto;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
                    <form method="get" action="covoiturages.php">
                        <input type="hidden" name="depart" value="<?= htmlspecialchars($depart) ?>">
                        <input type="hidden" name="arrivee" value="<?= htmlspecialchars($arrivee) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                        <label for="prix_max">Prix max :</label>
                        <input type="number" name="prix_max" id="prix_max" min="0" style="margin-right:20px;">
                        <label for="note_min">Note min :</label>
                        <input type="number" name="note_min" id="note_min" min="1" max="5" style="margin-right:20px;">
                        <label for="ecologique">Écologique :</label>
                        <select name="ecologique" id="ecologique">
                            <option value="">Tous</option>
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                        <button type="submit" style="margin-left:20px;background:#00b894;color:white;padding:8px 18px;border:none;border-radius:6px;font-weight:500;cursor:pointer;">Filtrer</button>
                    </form>
                </div>
            </div>

            <?php if (empty($covoiturages)): ?>
                <div style="text-align:center;padding:40px;background:white;border-radius:12px;margin-bottom:20px;">
                    <span class="material-icons" style="font-size:64px;color:#ddd;margin-bottom:20px;">search_off</span>
                    <h3 style="color:#636e72;margin-bottom:10px;">Aucun covoiturage trouvé</h3>
                    <p style="color:#636e72;">
                        Essayez de modifier vos critères de recherche.
                    </p>
                </div>
            <?php else: ?>
                <!-- Affichage des covoiturages -->
                <?php foreach ($covoiturages as $c): ?>
                    <div class="ride-card" data-price="<?= htmlspecialchars($c['prix']) ?>"
                         data-ecological="<?= $c['is_ecological'] ? 'true' : 'false' ?>"
                         data-rating="<?= htmlspecialchars($c['rating'] ?? 0) ?>"
                         data-duration="<?= htmlspecialchars($c['duree_heures'] ?? 0) ?>">
                        <div class="ride-header">
                            <h3><?= htmlspecialchars($c['ville_depart']) ?> → <?= htmlspecialchars($c['ville_arrivee']) ?></h3>
                            <div class="ride-price"><?= number_format($c['prix'], 2) ?>€</div>
                        </div>
                        <div class="ride-details">
                            <p><span class="material-icons">schedule</span> <?= date('d/m/Y à H:i', strtotime($c['date_depart'])) ?></p>
                            <p><span class="material-icons">person</span> <img src="../assets/<?= htmlspecialchars(($c['conducteur'] === 'marc') ? 'images/sebastien.jpg' : ($c['conducteur_avatar_url'] ?? 'images/default_avatar.png')) ?>" alt="Avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;image-rendering: -moz-crisp-edges; image-rendering: -o-crisp-edges; image-rendering: pixelated; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; filter: none;"> <?= htmlspecialchars($c['conducteur']) ?></p>
                            <p><span class="material-icons">directions_car</span> <?= htmlspecialchars($c['marque']) ?> <?= htmlspecialchars($c['modele']) ?></p>
                            <p><span class="material-icons">people</span> <?= (int)$c['places_restantes'] ?> places restantes</p>
                        </div>
                        <?php if ($c['is_ecological']): ?>
                            <div class="eco-badge">⚡ Écologique</div>
                        <?php endif; ?>
                        <div class="ride-actions">
                            <a href="details.php?id=<?= (int)$c['id'] ?>" class="btn-primary">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Suppression du bouton 'Proposer un trajet' -->
        </section>
    </main>

    <footer>
        <p>&copy; 2025 EcoRide - Tous droits réservés</p>
        <div><a href="#" id="openModalLegal">Mentions légales</a></div>
    </footer>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.ecorideUser) {
            renderMenu(window.ecorideUser);
        } else {
            renderMenu();
        }
        // Filtres avancés toggle
        const toggleBtn = document.getElementById('toggle-filters');
        const filtersBlock = document.getElementById('advanced-filters');
        if (toggleBtn && filtersBlock) {
            toggleBtn.addEventListener('click', function() {
                filtersBlock.style.display = (filtersBlock.style.display === 'none' || filtersBlock.style.display === '') ? 'block' : 'none';
            });
        }
    });
    </script>
</body>
</html>

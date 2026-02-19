<?php
$statusLabels = [
    'scheduled' => 'Planifié',
    'started'   => 'En cours',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
];
?>
<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">directions_car</span> Espace Chauffeur
    </h2>

    <!-- Stats -->
    <div class="stats-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;">
        <div class="stat-card" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#00b894;">account_balance_wallet</span>
            <h3><?= (int)($user['credits'] ?? 0) ?></h3>
            <p style="color:#636e72;">Cr&eacute;dits disponibles</p>
        </div>
        <div class="stat-card" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#0984e3;">map</span>
            <h3><?= count($trips) ?></h3>
            <p style="color:#636e72;">Trajets cr&eacute;&eacute;s</p>
        </div>
        <div class="stat-card" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#e17055;">event</span>
            <h3><?= count($upcoming_trips) ?></h3>
            <p style="color:#636e72;">&Agrave; venir</p>
        </div>
    </div>

    <!-- Créer un trajet -->
    <div style="text-align:center;margin-bottom:30px;">
        <a href="/driver/create-trip" class="btn-primary" style="padding:15px 30px;font-size:16px;">
            <span class="material-icons" style="vertical-align:middle;">add_circle</span> Cr&eacute;er un nouveau trajet
        </a>
    </div>

    <?php if (empty($trips)): ?>
        <div style="text-align:center;padding:40px;background:white;border-radius:12px;">
            <span class="material-icons" style="font-size:64px;color:#ddd;">route</span>
            <h3 style="color:#636e72;">Aucun trajet cr&eacute;&eacute;</h3>
            <p style="color:#636e72;">Cr&eacute;ez votre premier trajet pour commencer &agrave; covoiturer !</p>
        </div>
    <?php else: ?>

        <?php if (!empty($upcoming_trips)): ?>
            <h3 class="section-title">
                <span class="material-icons" style="vertical-align:middle;color:#0984e3;">event</span>
                Trajets &agrave; venir / en cours
            </h3>
            <div class="trips-grid" style="margin-bottom:30px;">
                <?php foreach ($upcoming_trips as $trip): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">directions_car</span>
                                <?= htmlspecialchars($trip['ville_depart']) ?> &rarr; <?= htmlspecialchars($trip['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                <?= date('d/m/Y H:i', strtotime($trip['departure_datetime'])) ?>
                                | <?= $trip['price'] ?>&euro;
                                | <?= $trip['nb_participants'] ?? 0 ?> passager(s)
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="admin-badge <?= $trip['status'] ?>"><?= $statusLabels[$trip['status']] ?? ucfirst($trip['status']) ?></span>
                            <?php if ($trip['status'] === 'scheduled'): ?>
                                <form method="POST" action="/my-trips" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="started">
                                    <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                    <button type="submit" style="background:#0984e3;color:white;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:13px;">▶ Démarrer</button>
                                </form>
                                <form method="POST" action="/my-trips" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="cancelled">
                                    <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                    <button type="submit" style="background:#d63031;color:white;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:13px;">Annuler</button>
                                </form>
                            <?php elseif ($trip['status'] === 'started'): ?>
                                <form method="POST" action="/my-trips" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="completed">
                                    <input type="hidden" name="trip_id" value="<?= $trip['trip_id'] ?>">
                                    <button type="submit" style="background:#00b894;color:white;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:13px;">✓ Terminer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_trips)): ?>
            <h3 class="section-title" style="margin-top:<?= !empty($upcoming_trips) ? '10px' : '0' ?>;">
                <span class="material-icons" style="vertical-align:middle;color:#636e72;">history</span>
                Trajets pass&eacute;s
            </h3>
            <div class="trips-grid">
                <?php foreach ($past_trips as $trip): ?>
                    <div class="ride-card-history card-light" style="opacity:<?= $trip['status'] === 'cancelled' ? '0.7' : '1' ?>;">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">directions_car</span>
                                <?= htmlspecialchars($trip['ville_depart']) ?> &rarr; <?= htmlspecialchars($trip['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                <?= date('d/m/Y H:i', strtotime($trip['departure_datetime'])) ?>
                                | <?= $trip['price'] ?>&euro;
                                | <?= $trip['nb_participants'] ?? 0 ?> passager(s)
                            </p>
                        </div>
                        <span class="admin-badge <?= $trip['status'] ?>"><?= $statusLabels[$trip['status']] ?? ucfirst($trip['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

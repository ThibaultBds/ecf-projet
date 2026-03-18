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

    <div class="stats-cards driver-stats-grid">
        <div class="stat-card driver-stat-card">
            <span class="material-icons driver-stat-icon driver-stat-green">account_balance_wallet</span>
            <h3><?= (int) $user->credits ?></h3>
            <p class="driver-stat-text">Crédits disponibles</p>
        </div>
        <div class="stat-card driver-stat-card">
            <span class="material-icons driver-stat-icon driver-stat-blue">map</span>
            <h3><?= count($trips) ?></h3>
            <p class="driver-stat-text">Trajets créés</p>
        </div>
        <div class="stat-card driver-stat-card">
            <span class="material-icons driver-stat-icon driver-stat-orange">event</span>
            <h3><?= count($upcoming_trips) ?></h3>
            <p class="driver-stat-text">À venir</p>
        </div>
    </div>

    <div class="driver-create-cta-wrap">
        <a href="/driver/create-trip" class="btn-primary driver-create-cta-btn">
            <span class="material-icons icon-middle">add_circle</span> Créer un nouveau trajet
        </a>
    </div>

    <?php if (empty($trips)): ?>
        <div class="driver-empty-state">
            <span class="material-icons driver-empty-icon">route</span>
            <h3 class="driver-empty-title">Aucun trajet créé</h3>
            <p class="driver-empty-text">Créez votre premier trajet pour commencer à covoiturer !</p>
        </div>
    <?php else: ?>

        <?php if (!empty($upcoming_trips)): ?>
            <h3 class="section-title">
                <span class="material-icons section-icon-driver">event</span>
                Trajets à venir / en cours
            </h3>
            <div class="trips-grid driver-upcoming-grid">
                <?php foreach ($upcoming_trips as $trip): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">directions_car</span>
                                <?= htmlspecialchars($trip->villeDepart) ?> &rarr; <?= htmlspecialchars($trip->villeArrivee) ?>
                            </p>
                            <p class="small-muted">
                                <?= date('d/m/Y H:i', strtotime($trip->departureDatetime)) ?>
                                | <?= $trip->price ?>€
                                | <?= $trip->nbParticipants ?? 0 ?> passager(s)
                            </p>
                        </div>
                        <div class="driver-trip-actions">
                            <span class="admin-badge <?= $trip->status ?>"><?= $statusLabels[$trip->status] ?? ucfirst($trip->status) ?></span>
                            <?php if ($trip->status === 'scheduled'): ?>
                                <form method="POST" action="/my-trips" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="started">
                                    <input type="hidden" name="trip_id" value="<?= $trip->tripId ?>">
                                    <button type="submit" class="driver-mini-btn driver-mini-btn-start">▶ Démarrer</button>
                                </form>
                                <form method="POST" action="/my-trips" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="cancelled">
                                    <input type="hidden" name="trip_id" value="<?= $trip->tripId ?>">
                                    <button type="submit" class="driver-mini-btn driver-mini-btn-cancel">Annuler</button>
                                </form>
                            <?php elseif ($trip->status === 'started'): ?>
                                <form method="POST" action="/my-trips" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="update_trip_status">
                                    <input type="hidden" name="status" value="completed">
                                    <input type="hidden" name="trip_id" value="<?= $trip->tripId ?>">
                                    <button type="submit" class="driver-mini-btn driver-mini-btn-finish">✓ Terminer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_trips)): ?>
            <h3 class="section-title <?= !empty($upcoming_trips) ? 'driver-past-title-gap' : 'section-title-no-gap' ?>">
                <span class="material-icons section-icon-muted">history</span>
                Trajets passés
            </h3>
            <div class="trips-grid">
                <?php foreach ($past_trips as $trip): ?>
                    <div class="ride-card-history card-light <?= $trip->status === 'cancelled' ? 'card-cancelled' : '' ?>">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">directions_car</span>
                                <?= htmlspecialchars($trip->villeDepart) ?> &rarr; <?= htmlspecialchars($trip->villeArrivee) ?>
                            </p>
                            <p class="small-muted">
                                <?= date('d/m/Y H:i', strtotime($trip->departureDatetime)) ?>
                                | <?= $trip->price ?>€
                                | <?= $trip->nbParticipants ?? 0 ?> passager(s)
                            </p>
                        </div>
                        <span class="admin-badge <?= $trip->status ?>"><?= $statusLabels[$trip->status] ?? ucfirst($trip->status) ?></span>
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

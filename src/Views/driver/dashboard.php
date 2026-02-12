<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">directions_car</span> Espace Chauffeur
    </h2>

    <!-- Stats -->
    <div class="stats-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;">
        <div class="stat-card" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#00b894;">account_balance_wallet</span>
            <h3><?= (int)($user['credits'] ?? 0) ?></h3>
            <p style="color:#636e72;">Crédits disponibles</p>
        </div>
        <div class="stat-card" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#0984e3;">map</span>
            <h3><?= count($trips) ?></h3>
            <p style="color:#636e72;">Trajets créés</p>
        </div>
    </div>

    <!-- Créer un trajet -->
    <div style="text-align:center;margin-bottom:30px;">
        <a href="/driver/create-trip" class="btn-primary" style="padding:15px 30px;font-size:16px;">
            <span class="material-icons" style="vertical-align:middle;">add_circle</span> Créer un nouveau trajet
        </a>
    </div>

    <!-- Liste des trajets -->
    <?php if (empty($trips)): ?>
        <div style="text-align:center;padding:40px;background:white;border-radius:12px;">
            <span class="material-icons" style="font-size:64px;color:#ddd;">route</span>
            <h3 style="color:#636e72;">Aucun trajet créé</h3>
            <p style="color:#636e72;">Créez votre premier trajet pour commencer à covoiturer !</p>
        </div>
    <?php else: ?>
        <h3 class="section-title">Mes trajets</h3>
        <div class="trips-grid">
            <?php foreach ($trips as $trip): ?>
                <div class="ride-card-history card-light">
                    <div class="ride-content">
                        <p class="ride-title">
                            <span class="material-icons ride-icon">directions_car</span>
                            <?= htmlspecialchars($trip['ville_depart']) ?> → <?= htmlspecialchars($trip['ville_arrivee']) ?>
                        </p>
                        <p class="small-muted">
                            <?= date('d/m/Y H:i', strtotime($trip['date_depart'])) ?>
                            | <?= $trip['prix'] ?>€
                            | <?= $trip['nb_participants'] ?? 0 ?> passager(s)
                        </p>
                    </div>
                    <span class="admin-badge <?= $trip['status'] ?>"><?= ucfirst($trip['status']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

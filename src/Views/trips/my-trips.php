<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">history</span> Mes Trajets
    </h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="message-success message-spacing"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="message-error message-spacing"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Trajets que je conduis -->
    <div class="trajets-section section-block">
        <?php if (!empty($trajets_conduits)): ?>
            <h3 class="section-title">Trajets que je conduis</h3>
            <div class="trips-grid">
                <?php foreach ($trajets_conduits as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">directions_car</span>
                                <?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet['date_depart'])) ?>
                                <span class="muted-status">Statut: <strong><?= ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>
                        <form method="POST" action="/my-trips" class="ride-actions">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">

                            <?php if ($trajet['status'] === 'planifie'): ?>
                                <input type="hidden" name="status" value="en_cours">
                                <button type="submit" name="action" value="update_trip_status" class="btn-secondary">Démarrer</button>
                                <button type="submit" name="action" value="update_trip_status" class="btn-danger"
                                        onclick="this.form.querySelector('[name=status]').value='annule';">
                                    Annuler le trajet
                                </button>
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

    <!-- Trajets où je suis passager -->
    <div class="trajets-section section-block">
        <h3 class="section-title">Trajets où je suis passager</h3>

        <?php if (empty($participations)): ?>
            <p class="no-participation">Vous ne participez à aucun trajet.
                <a href="/trips" class="link-highlight">Trouver un trajet</a>
            </p>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach ($participations as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">person</span>
                                <?= htmlspecialchars($trajet['ville_depart']) ?> → <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet['date_depart'])) ?>
                                <span class="muted-status">Statut: <strong><?= ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>

                        <?php if ($trajet['status'] === 'planifie'): ?>
                            <form method="POST" action="/my-trips" class="ride-actions">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">
                                <button type="submit" name="action" value="cancel_participation" class="btn-danger">Annuler ma participation</button>
                            </form>
                        <?php elseif ($trajet['status'] === 'termine' && !$trajet['has_reviewed']): ?>
                            <details class="details-compact">
                                <summary class="details-summary">Noter ce trajet</summary>
                                <form action="/api/review" method="POST" class="form-container form-small form-compact">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="trip_id" value="<?= $trajet['id'] ?>">
                                    <input type="hidden" name="reviewed_id" value="<?= $trajet['chauffeur_id'] ?? '' ?>">
                                    <label for="note-<?= $trajet['id'] ?>">Note (sur 5)</label>
                                    <input type="number" id="note-<?= $trajet['id'] ?>" name="note" min="1" max="5" required>
                                    <label for="commentaire-<?= $trajet['id'] ?>">Commentaire</label>
                                    <textarea id="commentaire-<?= $trajet['id'] ?>" name="commentaire" required></textarea>
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="is_problem"> Signaler un problème
                                    </label>
                                    <button type="submit" class="btn-primary">Envoyer</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons page-icon-small">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

<?php
$statusLabels = [
    'scheduled' => 'Planifié',
    'started' => 'En cours',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
    'confirmed' => 'Confirmé',
    'validated' => 'Validé',
    'disputed' => 'Litige',
];
?>
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
                                <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                <span class="muted-status">Statut: <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>
                        <form method="POST" action="/my-trips" class="ride-actions">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                            <input type="hidden" name="status" value="">

                            <?php if ($trajet['status'] === 'scheduled'): ?>
                                <button type="submit" name="action" value="update_trip_status" class="btn-primary"
                                        onclick="this.form.querySelector('[name=status]').value='started';"
                                        style="background:#0984e3;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:18px;">play_arrow</span> D&eacute;marrer
                                </button>
                                <button type="submit" name="action" value="update_trip_status" class="btn-danger"
                                        onclick="this.form.querySelector('[name=status]').value='cancelled';">
                                    Annuler
                                </button>

                            <?php elseif ($trajet['status'] === 'started'): ?>
                                <button type="submit" name="action" value="update_trip_status" class="btn-primary"
                                        onclick="this.form.querySelector('[name=status]').value='completed';"
                                        style="background:#00b894;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:18px;">flag</span> Arriv&eacute;e &agrave; destination
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Trajets où je suis passager -->
    <div class="trajets-section section-block">
        <h3 class="section-title">Trajets o&ugrave; je suis passager</h3>

        <?php if (empty($participations)): ?>
            <p class="no-participation">Vous ne participez &agrave; aucun trajet.
                <a href="/trips" class="link-highlight">Trouver un trajet</a>
            </p>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach ($participations as $trajet): ?>
                    <?php
                    // Récupérer le statut de participation
                    $pStatus = $trajet['participant_status'] ?? $trajet['status'];
                    ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <span class="material-icons ride-icon">person</span>
                                <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                | Conducteur : <?= htmlspecialchars($trajet['conducteur'] ?? '') ?>
                                <span class="muted-status">Statut trajet: <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>

                        <?php if ($trajet['status'] === 'scheduled'): ?>
                            <form method="POST" action="/my-trips" class="ride-actions">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                <button type="submit" name="action" value="cancel_participation" class="btn-danger">Annuler ma participation</button>
                            </form>

                        <?php elseif ($trajet['status'] === 'completed' && ($pStatus === 'confirmed')): ?>
                            <!-- Le trajet est terminé, le passager doit valider ou signaler -->
                            <div class="ride-actions" style="display:flex;flex-direction:column;gap:10px;">
                                <form method="POST" action="/my-trips" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                    <button type="submit" name="action" value="validate_trip" class="btn-primary" style="background:#00b894;">
                                        <span class="material-icons" style="vertical-align:middle;font-size:18px;">check_circle</span> Tout s'est bien pass&eacute;
                                    </button>
                                </form>

                                <details style="margin-top:5px;">
                                    <summary style="cursor:pointer;color:#e74c3c;font-weight:500;">
                                        <span class="material-icons" style="vertical-align:middle;font-size:18px;">report_problem</span> Signaler un probl&egrave;me
                                    </summary>
                                    <form method="POST" action="/my-trips" style="margin-top:10px;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                        <textarea name="problem_comment" rows="3" required placeholder="D&eacute;crivez le probl&egrave;me rencontr&eacute;..."
                                                  style="width:100%;border:1px solid #ddd;border-radius:6px;padding:8px;margin-bottom:8px;font-size:14px;"></textarea>
                                        <button type="submit" name="action" value="report_problem" class="btn-danger" style="width:100%;">Envoyer le signalement</button>
                                    </form>
                                </details>

                                <!-- Formulaire de notation -->
                                <details class="details-compact" style="margin-top:5px;">
                                    <summary class="details-summary">Noter ce trajet</summary>
                                    <form action="/api/review" method="POST" class="form-container form-small form-compact">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                        <input type="hidden" name="driver_id" value="<?= $trajet['chauffeur_id'] ?? '' ?>">
                                        <label for="rating-<?= $trajet['trip_id'] ?>">Note (sur 5)</label>
                                        <input type="number" id="rating-<?= $trajet['trip_id'] ?>" name="rating" min="1" max="5" required>
                                        <label for="comment-<?= $trajet['trip_id'] ?>">Commentaire</label>
                                        <textarea id="comment-<?= $trajet['trip_id'] ?>" name="comment" required></textarea>
                                        <button type="submit" class="btn-primary">Envoyer</button>
                                    </form>
                                </details>
                            </div>

                        <?php elseif ($pStatus === 'validated'): ?>
                            <span style="color:#00b894;font-weight:500;">
                                <span class="material-icons" style="vertical-align:middle;">check_circle</span> Valid&eacute;
                            </span>

                        <?php elseif ($pStatus === 'disputed'): ?>
                            <span style="color:#e74c3c;font-weight:500;">
                                <span class="material-icons" style="vertical-align:middle;">report</span> Litige en cours
                            </span>

                        <?php elseif ($trajet['status'] === 'started'): ?>
                            <span style="color:#0984e3;font-weight:500;">
                                <span class="material-icons" style="vertical-align:middle;">directions_car</span> Trajet en cours
                            </span>
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

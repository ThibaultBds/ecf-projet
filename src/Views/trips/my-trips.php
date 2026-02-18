<?php
$statusLabels = [
    'scheduled'       => 'Planifié',
    'started'         => 'En cours',
    'completed'       => 'Terminé',
    'cancelled'       => 'Annulé',
    'confirmed'       => 'Confirmé',
    'validated'       => 'Validé',
    'disputed'        => 'Litige',
];
?>
<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">history</span> Mes Trajets
    </h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="message-success message-spacing"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="message-success message-spacing"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message-error message-spacing"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php
    // ─── SECTION CHAUFFEUR ────────────────────────────────────────────────────
    $has_driver_section = !empty($upcoming_conduits) || !empty($past_conduits);
    if ($has_driver_section):
    ?>
    <div class="trajets-section section-block">

        <?php if (!empty($upcoming_conduits)): ?>
            <h3 class="section-title">
                <span class="material-icons" style="vertical-align:middle;color:#0984e3;">directions_car</span>
                Mes trajets &agrave; venir
            </h3>
            <div class="trips-grid">
                <?php foreach ($upcoming_conduits as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= (int)$trajet['trip_id'] ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons ride-icon">directions_car</span>
                                    <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                &nbsp;|&nbsp; <?= (int)($trajet['nb_participants'] ?? 0) ?> passager(s)
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong></span>
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

        <?php if (!empty($past_conduits)): ?>
            <h3 class="section-title" style="margin-top:<?= !empty($upcoming_conduits) ? '30px' : '0' ?>;">
                <span class="material-icons" style="vertical-align:middle;color:#636e72;">history</span>
                Mes trajets pass&eacute;s
            </h3>
            <div class="trips-grid">
                <?php foreach ($past_conduits as $trajet): ?>
                    <div class="ride-card-history card-light" style="opacity:<?= $trajet['status'] === 'cancelled' ? '0.7' : '1' ?>;">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= (int)$trajet['trip_id'] ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons ride-icon">directions_car</span>
                                    <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                &nbsp;|&nbsp; <?= (int)($trajet['nb_participants'] ?? 0) ?> passager(s)
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>
                        <?php if ($trajet['status'] === 'cancelled'): ?>
                            <span style="color:#636e72;font-size:13px;">
                                <span class="material-icons" style="vertical-align:middle;font-size:16px;">cancel</span> Annul&eacute;
                            </span>
                        <?php else: ?>
                            <span style="color:#00b894;font-size:13px;">
                                <span class="material-icons" style="vertical-align:middle;font-size:16px;">check_circle</span> Termin&eacute;
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php
    // ─── SECTION PASSAGER ─────────────────────────────────────────────────────
    $has_passenger_section = !empty($upcoming_participations) || !empty($past_participations);
    ?>
    <div class="trajets-section section-block" style="margin-top:<?= $has_driver_section ? '30px' : '0' ?>;">

        <?php if (!empty($upcoming_participations)): ?>
            <h3 class="section-title">
                <span class="material-icons" style="vertical-align:middle;color:#00b894;">person</span>
                Mes participations &agrave; venir
            </h3>
            <div class="trips-grid">
                <?php foreach ($upcoming_participations as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= (int)$trajet['trip_id'] ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons ride-icon">person</span>
                                    <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                | Conducteur : <?= htmlspecialchars($trajet['conducteur'] ?? '') ?>
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong></span>
                            </p>
                        </div>
                        <?php if ($trajet['status'] === 'scheduled'): ?>
                            <form method="POST" action="/my-trips" class="ride-actions">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                <button type="submit" name="action" value="cancel_participation" class="btn-danger">Annuler ma participation</button>
                            </form>
                        <?php elseif ($trajet['status'] === 'started'): ?>
                            <span style="color:#0984e3;font-weight:500;">
                                <span class="material-icons" style="vertical-align:middle;">directions_car</span> Trajet en cours
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_participations)): ?>
            <h3 class="section-title" style="margin-top:<?= !empty($upcoming_participations) ? '30px' : '0' ?>;">
                <span class="material-icons" style="vertical-align:middle;color:#636e72;">history</span>
                Mes participations pass&eacute;es
            </h3>
            <div class="trips-grid">
                <?php foreach ($past_participations as $trajet): ?>
                    <?php
                    $pStatus     = $trajet['participant_status'] ?? '';
                    $hasReviewed = !empty($trajet['has_reviewed']);
                    ?>
                    <div class="ride-card-history card-light" style="flex-direction:column;opacity:<?= $trajet['status'] === 'cancelled' ? '0.7' : '1' ?>;">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= (int)$trajet['trip_id'] ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:5px;">
                                    <span class="material-icons ride-icon">person</span>
                                    <?= htmlspecialchars($trajet['ville_depart']) ?> &rarr; <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                D&eacute;part : <?= date('d/m/Y H:i', strtotime($trajet['departure_datetime'])) ?>
                                | Conducteur : <?= htmlspecialchars($trajet['conducteur'] ?? '') ?>
                                &mdash; Statut : <strong><?= $statusLabels[$trajet['status']] ?? ucfirst($trajet['status']) ?></strong>
                            </p>
                        </div>

                        <?php if ($trajet['status'] === 'cancelled'): ?>
                            <span style="color:#636e72;font-size:13px;">
                                <span class="material-icons" style="vertical-align:middle;font-size:16px;">cancel</span> Annul&eacute; &mdash; cr&eacute;dits rembours&eacute;s
                            </span>

                        <?php elseif ($trajet['status'] === 'completed'): ?>
                            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;width:100%;">

                                <?php if ($pStatus === 'confirmed'): ?>
                                    <!-- Pas encore validé ni signalé -->
                                    <form method="POST" action="/my-trips" style="width:100%;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                        <button type="submit" name="action" value="validate_trip" class="btn-primary" style="background:#00b894;width:100%;">
                                            <span class="material-icons" style="vertical-align:middle;font-size:18px;">check_circle</span> Tout s&apos;est bien pass&eacute;
                                        </button>
                                    </form>
                                    <details style="width:100%;">
                                        <summary style="cursor:pointer;color:#e74c3c;font-weight:500;font-size:14px;list-style:none;">
                                            <span class="material-icons" style="vertical-align:middle;font-size:16px;">report_problem</span> Signaler un probl&egrave;me
                                        </summary>
                                        <form method="POST" action="/my-trips" style="margin-top:8px;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                            <textarea name="problem_comment" rows="3" required placeholder="D&eacute;crivez le probl&egrave;me..."
                                                      style="width:100%;border:1px solid #ddd;border-radius:6px;padding:8px;font-size:14px;margin-bottom:6px;box-sizing:border-box;"></textarea>
                                            <button type="submit" name="action" value="report_problem" class="btn-danger" style="width:100%;">Envoyer le signalement</button>
                                        </form>
                                    </details>

                                <?php elseif ($pStatus === 'validated'): ?>
                                    <span style="color:#00b894;font-weight:500;font-size:14px;">
                                        <span class="material-icons" style="vertical-align:middle;font-size:16px;">check_circle</span> Valid&eacute;
                                    </span>

                                <?php elseif ($pStatus === 'disputed'): ?>
                                    <span style="color:#e74c3c;font-weight:500;font-size:14px;">
                                        <span class="material-icons" style="vertical-align:middle;font-size:16px;">report</span>
                                        Litige en cours
                                    </span>
                                <?php endif; ?>

                                <!-- Formulaire de notation : visible si pas encore noté -->
                                <?php if (!$hasReviewed): ?>
                                    <details class="details-compact" style="width:100%;margin-top:4px;">
                                        <summary class="details-summary" style="font-size:14px;cursor:pointer;color:#0984e3;font-weight:500;list-style:none;">
                                            <span class="material-icons" style="vertical-align:middle;font-size:16px;">star</span> Noter ce trajet
                                        </summary>
                                        <form action="/api/review" method="POST" class="form-container form-small form-compact" style="margin-top:8px;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="trip_id" value="<?= $trajet['trip_id'] ?>">
                                            <input type="hidden" name="driver_id" value="<?= $trajet['chauffeur_id'] ?? '' ?>">
                                            <label for="rating-<?= $trajet['trip_id'] ?>">Note (1 &agrave; 5)</label>
                                            <input type="number" id="rating-<?= $trajet['trip_id'] ?>" name="rating" min="1" max="5" required>
                                            <label for="comment-<?= $trajet['trip_id'] ?>">Commentaire</label>
                                            <textarea id="comment-<?= $trajet['trip_id'] ?>" name="comment" required></textarea>
                                            <button type="submit" class="btn-primary">Envoyer</button>
                                        </form>
                                    </details>
                                <?php else: ?>
                                    <span style="color:#636e72;font-size:13px;">
                                        <span class="material-icons" style="vertical-align:middle;font-size:14px;">star</span> Avis d&eacute;j&agrave; envoy&eacute;
                                    </span>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_passenger_section && !$has_driver_section): ?>
            <p class="no-participation">Vous n&apos;avez aucun trajet.
                <a href="/trips" class="link-highlight">Trouver un trajet</a>
            </p>
        <?php elseif (!$has_passenger_section): ?>
            <p class="no-participation">Vous ne participez &agrave; aucun trajet.
                <a href="/trips" class="link-highlight">Trouver un trajet</a>
            </p>
        <?php endif; ?>

    </div>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons page-icon-small">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

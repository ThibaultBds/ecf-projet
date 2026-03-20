<?php
$statusLabels = [
    'scheduled' => 'Planifié',
    'started'   => 'En cours',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
    'confirmed' => 'Confirmé',
    'validated' => 'Validé',
    'disputed'  => 'Litige',
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
    $has_driver_section = !empty($upcoming_conduits) || !empty($past_conduits);
    if ($has_driver_section):
    ?>
    <div class="trajets-section section-block">

        <?php if (!empty($upcoming_conduits)): ?>
            <h3 class="section-title">
                <span class="material-icons section-icon-driver">directions_car</span>
                Mes trajets à venir
            </h3>
            <div class="trips-grid">
                <?php foreach ($upcoming_conduits as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= $trajet->tripId ?>" class="trip-link">
                                    <span class="material-icons ride-icon">directions_car</span>
                                    <?= htmlspecialchars($trajet->villeDepart) ?> &rarr; <?= htmlspecialchars($trajet->villeArrivee) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet->departureDatetime)) ?>
                                &nbsp;|&nbsp; <?= (int) ($trajet->nbParticipants ?? 0) ?> passager(s)
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet->status] ?? ucfirst($trajet->status) ?></strong></span>
                            </p>
                        </div>
                        <form method="POST" action="/my-trips" class="ride-actions">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                            <input type="hidden" name="status" value="">
                            <?php if ($trajet->status === 'scheduled'): ?>
                                <button type="submit" name="action" value="update_trip_status" class="btn-primary btn-status-started"
                                        onclick="this.form.querySelector('[name=status]').value='started';">
                                    <span class="material-icons trip-action-icon">play_arrow</span> Démarrer
                                </button>
                                <button type="submit" name="action" value="update_trip_status" class="btn-danger"
                                        onclick="this.form.querySelector('[name=status]').value='cancelled';">
                                    Annuler
                                </button>
                            <?php elseif ($trajet->status === 'started'): ?>
                                <button type="submit" name="action" value="update_trip_status" class="btn-primary btn-status-completed"
                                        onclick="this.form.querySelector('[name=status]').value='completed';">
                                    <span class="material-icons trip-action-icon">flag</span> Arrivée à destination
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_conduits)): ?>
            <h3 class="section-title <?= !empty($upcoming_conduits) ? 'section-title-gap' : 'section-title-no-gap' ?>">
                <span class="material-icons section-icon-muted">history</span>
                Mes trajets passés
            </h3>
            <div class="trips-grid">
                <?php foreach ($past_conduits as $trajet): ?>
                    <div class="ride-card-history card-light <?= $trajet->status === 'cancelled' ? 'card-cancelled' : '' ?>">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= $trajet->tripId ?>" class="trip-link">
                                    <span class="material-icons ride-icon">directions_car</span>
                                    <?= htmlspecialchars($trajet->villeDepart) ?> &rarr; <?= htmlspecialchars($trajet->villeArrivee) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet->departureDatetime)) ?>
                                &nbsp;|&nbsp; <?= (int) ($trajet->nbParticipants ?? 0) ?> passager(s)
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet->status] ?? ucfirst($trajet->status) ?></strong></span>
                            </p>
                        </div>
                        <?php if ($trajet->status === 'cancelled'): ?>
                            <span class="trip-status-note status-muted">
                                <span class="material-icons trip-status-icon">cancel</span> Annulé
                            </span>
                        <?php else: ?>
                            <span class="trip-status-note status-success">
                                <span class="material-icons trip-status-icon">check_circle</span> Terminé
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php
    $has_passenger_section = !empty($upcoming_participations) || !empty($past_participations);
    ?>
    <div class="trajets-section section-block <?= $has_driver_section ? 'section-offset' : '' ?>">

        <?php if (!empty($upcoming_participations)): ?>
            <h3 class="section-title">
                <span class="material-icons section-icon-success">person</span>
                Mes participations à venir
            </h3>
            <div class="trips-grid">
                <?php foreach ($upcoming_participations as $trajet): ?>
                    <div class="ride-card-history card-light">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= $trajet->tripId ?>" class="trip-link">
                                    <span class="material-icons ride-icon">person</span>
                                    <?= htmlspecialchars($trajet->villeDepart) ?> &rarr; <?= htmlspecialchars($trajet->villeArrivee) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet->departureDatetime)) ?>
                                | Conducteur : <?= htmlspecialchars($trajet->conducteur ?? '') ?>
                                <span class="muted-status">Statut : <strong><?= $statusLabels[$trajet->status] ?? ucfirst($trajet->status) ?></strong></span>
                            </p>
                        </div>
                        <?php if ($trajet->status === 'scheduled'): ?>
                            <form method="POST" action="/my-trips" class="ride-actions">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                <button type="submit" name="action" value="cancel_participation" class="btn-danger">Annuler ma participation</button>
                            </form>
                        <?php elseif ($trajet->status === 'started'): ?>
                            <span class="trip-status-note status-info-strong">
                                <span class="material-icons trip-status-icon-middle">directions_car</span> Trajet en cours
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past_participations)):
            $to_validate = array_values(array_filter($past_participations, fn($t) => ($t->participantStatus ?? '') === 'confirmed' && $t->status === 'completed'));
            $historique  = array_values(array_filter($past_participations, fn($t) => !(($t->participantStatus ?? '') === 'confirmed' && $t->status === 'completed')));
        ?>

            <?php if (!empty($to_validate)): ?>
            <h3 class="section-title <?= !empty($upcoming_participations) ? 'section-title-gap' : 'section-title-no-gap' ?>">
                <span class="material-icons section-icon-success">pending_actions</span>
                Mes participations à valider
            </h3>
            <div class="trips-grid">
                <?php foreach ($to_validate as $trajet): ?>
                    <?php $pStatus = 'confirmed'; $hasReviewed = !empty($trajet->hasReviewed); ?>
                    <div class="ride-card-history card-light ride-card-stack">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= $trajet->tripId ?>" class="trip-link">
                                    <span class="material-icons ride-icon">person</span>
                                    <?= htmlspecialchars($trajet->villeDepart) ?> &rarr; <?= htmlspecialchars($trajet->villeArrivee) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet->departureDatetime)) ?>
                                | Conducteur : <?= htmlspecialchars($trajet->conducteur ?? '') ?>
                                &mdash; Statut : <strong>Terminé</strong>
                            </p>
                        </div>
                        <div class="trip-actions-column">
                            <form method="POST" action="/my-trips" class="w-100">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                <button type="submit" name="action" value="validate_trip" class="btn-primary btn-status-completed w-100">
                                    <span class="material-icons trip-action-icon">check_circle</span> Tout s'est bien passé
                                </button>
                            </form>
                            <details class="w-100">
                                <summary class="trip-problem-summary">
                                    <span class="material-icons trip-status-icon">report_problem</span> Signaler un problème
                                </summary>
                                <form method="POST" action="/my-trips" class="trip-problem-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                    <textarea name="problem_comment" rows="3" required placeholder="Décrivez le problème..." class="trip-problem-textarea"></textarea>
                                    <button type="submit" name="action" value="report_problem" class="btn-danger w-100">Envoyer le signalement</button>
                                </form>
                            </details>
                            <?php if (!$hasReviewed): ?>
                                <details class="details-compact trip-review-details">
                                    <summary class="details-summary trip-review-summary">
                                        <span class="material-icons trip-status-icon">star</span> Noter ce trajet
                                    </summary>
                                    <form action="/api/review" method="POST" class="form-container form-small form-compact trip-review-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                        <input type="hidden" name="driver_id" value="<?= $trajet->chauffeurId ?? '' ?>">
                                        <label>Note (1 à 5)</label>
                                        <input type="number" name="rating" min="1" max="5" required>
                                        <label>Commentaire</label>
                                        <textarea name="comment" required></textarea>
                                        <button type="submit" class="btn-primary">Envoyer</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($historique)): ?>
            <h3 class="section-title section-title-gap">
                <span class="material-icons section-icon-muted">history</span>
                Mes participations passées
            </h3>
            <div class="trips-grid">
                <?php foreach ($historique as $trajet): ?>
                    <?php
                    $pStatus     = $trajet->participantStatus ?? '';
                    $hasReviewed = !empty($trajet->hasReviewed);
                    ?>
                    <div class="ride-card-history card-light ride-card-stack <?= $trajet->status === 'cancelled' ? 'card-cancelled' : '' ?>">
                        <div class="ride-content">
                            <p class="ride-title">
                                <a href="/trip/<?= $trajet->tripId ?>" class="trip-link">
                                    <span class="material-icons ride-icon">person</span>
                                    <?= htmlspecialchars($trajet->villeDepart) ?> &rarr; <?= htmlspecialchars($trajet->villeArrivee) ?>
                                </a>
                            </p>
                            <p class="small-muted">
                                Départ : <?= date('d/m/Y H:i', strtotime($trajet->departureDatetime)) ?>
                                | Conducteur : <?= htmlspecialchars($trajet->conducteur ?? '') ?>
                                &mdash; Statut : <strong><?= $statusLabels[$trajet->status] ?? ucfirst($trajet->status) ?></strong>
                            </p>
                        </div>

                        <?php if ($trajet->status === 'cancelled'): ?>
                            <span class="trip-status-note status-muted">
                                <span class="material-icons trip-status-icon">cancel</span> Annulé &mdash; crédits remboursés
                            </span>

                        <?php elseif ($trajet->status === 'completed'): ?>
                            <div class="trip-actions-column">

                                <?php if ($pStatus === 'confirmed'): ?>
                                    <form method="POST" action="/my-trips" class="w-100">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                        <button type="submit" name="action" value="validate_trip" class="btn-primary btn-status-completed w-100">
                                            <span class="material-icons trip-action-icon">check_circle</span> Tout s'est bien passé
                                        </button>
                                    </form>
                                    <details class="w-100">
                                        <summary class="trip-problem-summary">
                                            <span class="material-icons trip-status-icon">report_problem</span> Signaler un problème
                                        </summary>
                                        <form method="POST" action="/my-trips" class="trip-problem-form">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                            <textarea name="problem_comment" rows="3" required placeholder="Décrivez le problème..." class="trip-problem-textarea"></textarea>
                                            <button type="submit" name="action" value="report_problem" class="btn-danger w-100">Envoyer le signalement</button>
                                        </form>
                                    </details>

                                <?php elseif ($pStatus === 'validated'): ?>
                                    <span class="trip-status-note status-success-strong">
                                        <span class="material-icons trip-status-icon">check_circle</span> Validé
                                    </span>

                                <?php elseif ($pStatus === 'disputed'): ?>
                                    <?php $decision = $resolvedIncidents[$trajet->tripId] ?? null; ?>
                                    <?php if ($decision === 'favor_passenger'): ?>
                                        <span class="trip-status-note status-success-strong">
                                            <span class="material-icons trip-status-icon">verified</span>
                                            Litige résolu en votre faveur &mdash; crédits remboursés
                                        </span>
                                    <?php elseif ($decision === 'favor_driver'): ?>
                                        <span class="trip-status-note status-muted-strong">
                                            <span class="material-icons trip-status-icon">gavel</span>
                                            Litige résolu en faveur du chauffeur
                                        </span>
                                    <?php else: ?>
                                        <span class="trip-status-note status-danger-strong">
                                            <span class="material-icons trip-status-icon">report</span>
                                            Litige en cours &mdash; en attente de décision
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!$hasReviewed): ?>
                                    <details class="details-compact trip-review-details">
                                        <summary class="details-summary trip-review-summary">
                                            <span class="material-icons trip-status-icon">star</span> Noter ce trajet
                                        </summary>
                                        <form action="/api/review" method="POST" class="form-container form-small form-compact trip-review-form">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="trip_id" value="<?= $trajet->tripId ?>">
                                            <input type="hidden" name="driver_id" value="<?= $trajet->chauffeurId ?? '' ?>">
                                            <label for="rating-<?= $trajet->tripId ?>">Note (1 à 5)</label>
                                            <input type="number" id="rating-<?= $trajet->tripId ?>" name="rating" min="1" max="5" required>
                                            <label for="comment-<?= $trajet->tripId ?>">Commentaire</label>
                                            <textarea id="comment-<?= $trajet->tripId ?>" name="comment" required></textarea>
                                            <button type="submit" class="btn-primary">Envoyer</button>
                                        </form>
                                    </details>
                                <?php else: ?>
                                    <span class="trip-status-note status-muted">
                                        <span class="material-icons trip-status-icon-small">star</span> Avis déjà envoyé
                                    </span>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_passenger_section && !$has_driver_section): ?>
            <p class="no-participation">Vous n'avez aucun trajet.
                <a href="/trips" class="link-highlight">Trouver un trajet</a>
            </p>
        <?php elseif (!$has_passenger_section): ?>
            <p class="no-participation">Vous ne participez à aucun trajet.
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

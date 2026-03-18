<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">shield</span> Modération
    </h2>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <h3 class="profil-titre">Avis en attente de modération</h3>

        <?php if (empty($pendingReviews)): ?>
            <p class="moderator-empty-text">
                <span class="material-icons moderator-empty-icon">check_circle</span>
                Aucun avis en attente
            </p>
        <?php else: ?>
            <div class="table-wrap-x">
                <table class="admin-table moderator-reviews-table">
                    <thead>
                        <tr class="admin-row-head">
                            <th>ID</th>
                            <th>Auteur</th>
                            <th>Chauffeur</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReviews as $r): ?>
                            <tr class="admin-row">
                                <td><?= $r->id ?></td>
                                <td><?= htmlspecialchars($r->reviewerName) ?></td>
                                <td><?= htmlspecialchars($r->driverName) ?></td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="moderator-star <?= $i <= $r->rating ? 'is-on' : 'is-off' ?>">&#9733;</span>
                                    <?php endfor; ?>
                                </td>
                                <td class="moderator-comment-cell"><?= htmlspecialchars($r->comment ?? '') ?></td>
                                <td><?= date('d/m/Y', strtotime($r->createdAt)) ?></td>
                                <td class="moderator-actions-cell nowrap">
                                    <form method="POST" action="/moderator/approve-review" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="review_id" value="<?= $r->id ?>">
                                        <button type="submit" class="btn-primary admin-btn-sm">Approuver</button>
                                    </form>
                                    <form method="POST" action="/moderator/reject-review" class="inline-form moderator-inline-gap">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="review_id" value="<?= $r->id ?>">
                                        <button type="submit" class="btn-danger admin-btn-sm">Rejeter</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="profile-box moderator-box-gap">
        <h3 class="profil-titre">
            <span class="material-icons moderator-title-icon danger">report_problem</span>
            Signalements en cours
            <?php if (!empty($incidents)): ?>
                <span class="admin-unread-badge"><?= count($incidents) ?></span>
            <?php endif; ?>
        </h3>

        <?php if (empty($incidents)): ?>
            <p class="moderator-empty-text">
                <span class="material-icons moderator-empty-icon">check_circle</span>
                Aucun incident en attente
            </p>
        <?php else: ?>
            <?php foreach ($incidents as $inc): ?>
                <div class="moderator-incident-card">
                    <div class="moderator-incident-head">
                        <div>
                            <strong>Trajet #<?= (int)($inc['trip_id'] ?? 0) ?></strong>
                            &mdash;
                            <?= htmlspecialchars($inc['ville_depart'] ?? '?') ?> &rarr; <?= htmlspecialchars($inc['ville_arrivee'] ?? '?') ?>
                        </div>
                        <span class="moderator-incident-date">
                            <?= isset($inc['departure_datetime']) ? date('d/m/Y H:i', strtotime($inc['departure_datetime'])) : '' ?>
                            <?php if (!empty($inc['arrival_datetime'])): ?>
                                &rarr; <?= date('H:i', strtotime($inc['arrival_datetime'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="moderator-incident-grid">
                        <div>
                            <strong>Passager :</strong> <?= htmlspecialchars($inc['reporter_name'] ?? '?') ?>
                            <br><span class="moderator-subtext"><?= htmlspecialchars($inc['reporter_email'] ?? '') ?></span>
                        </div>
                        <div>
                            <strong>Chauffeur :</strong> <?= htmlspecialchars($inc['driver_name'] ?? '?') ?>
                            <br><span class="moderator-subtext"><?= htmlspecialchars($inc['driver_email'] ?? '') ?></span>
                        </div>
                    </div>
                    <?php if (!empty($inc['comment'])): ?>
                        <div class="moderator-incident-comment">
                            &laquo; <?= htmlspecialchars((string)$inc['comment']) ?> &raquo;
                        </div>
                    <?php endif; ?>
                    <div class="moderator-incident-footer">
                        <p class="moderator-incident-label">Décision de modération :</p>
                        <div class="moderator-incident-actions">
                            <form method="POST" action="/moderator/resolve-incident" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= (int)($inc['trip_id'] ?? 0) ?>">
                                <input type="hidden" name="reporter_id" value="<?= (int)($inc['reporter_id'] ?? 0) ?>">
                                <input type="hidden" name="credit_driver" value="0">
                                <button type="submit" class="btn-danger admin-btn-sm" title="Le signalement est fondé : le chauffeur n'est pas payé.">
                                    <span class="material-icons moderator-btn-icon">person_off</span>
                                    En faveur du passager
                                </button>
                            </form>
                            <form method="POST" action="/moderator/resolve-incident" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= (int)($inc['trip_id'] ?? 0) ?>">
                                <input type="hidden" name="reporter_id" value="<?= (int)($inc['reporter_id'] ?? 0) ?>">
                                <input type="hidden" name="credit_driver" value="1">
                                <button type="submit" class="btn-primary admin-btn-sm" title="Le signalement est non fondé : le chauffeur est crédité.">
                                    <span class="material-icons moderator-btn-icon">check_circle</span>
                                    En faveur du chauffeur
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($resolvedIncidents)): ?>
    <div class="profile-box moderator-box-gap moderator-resolved-box">
        <h3 class="profil-titre">
            <span class="material-icons moderator-title-icon success">check_circle</span>
            Signalements réglés
            <span class="moderator-resolved-count"><?= count($resolvedIncidents) ?></span>
        </h3>
        <?php foreach ($resolvedIncidents as $inc): ?>
            <?php $decisionLabel = ($inc['decision'] ?? '') === 'favor_driver' ? 'En faveur du chauffeur' : 'En faveur du passager'; ?>
            <?php $decisionClass = ($inc['decision'] ?? '') === 'favor_driver' ? 'decision-driver' : 'decision-passenger'; ?>
            <div class="moderator-resolved-card">
                <div class="moderator-resolved-head">
                    <div>
                        <strong>Trajet #<?= (int)($inc['trip_id'] ?? 0) ?></strong>
                        &mdash;
                        <?= htmlspecialchars($inc['ville_depart'] ?? '?') ?> &rarr; <?= htmlspecialchars($inc['ville_arrivee'] ?? '?') ?>
                    </div>
                    <span class="moderator-decision-badge <?= $decisionClass ?>">
                        <?= htmlspecialchars($decisionLabel) ?>
                    </span>
                </div>
                <div class="moderator-resolved-grid">
                    <div><strong>Passager :</strong> <?= htmlspecialchars($inc['reporter_name'] ?? '?') ?></div>
                    <div><strong>Chauffeur :</strong> <?= htmlspecialchars($inc['driver_name'] ?? '?') ?></div>
                </div>
                <?php if (!empty($inc['resolved_at'])): ?>
                    <div class="moderator-resolved-date">
                        Résolu le <?= date('d/m/Y à H:i', strtotime($inc['resolved_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="profile-box admin-messages-box" id="messages">
        <h3 class="profil-titre">
            <span class="material-icons moderator-title-icon info">mail</span>
            Messages de contact
            <?php $unread = count(array_filter($contactMessages ?? [], fn($m) => !$m->isRead)); ?>
            <?php if ($unread > 0): ?>
                <span class="admin-unread-badge"><?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h3>
        <?php if (empty($contactMessages)): ?>
            <p class="admin-empty-text">Aucun message reçu.</p>
        <?php else: ?>
            <?php foreach ($contactMessages as $msg): ?>
                <div class="admin-message-card <?= $msg->isRead ? 'is-read' : 'is-unread' ?>">
                    <div class="admin-message-head">
                        <div>
                            <strong><?= htmlspecialchars($msg->nom) ?></strong>
                            &lt;<a href="mailto:<?= htmlspecialchars($msg->email) ?>" class="admin-message-mail-link"><?= htmlspecialchars($msg->email) ?></a>&gt;
                            &mdash; <em><?= htmlspecialchars($msg->sujet) ?></em>
                        </div>
                        <div class="admin-message-meta">
                            <span class="admin-message-date"><?= date('d/m/Y H:i', strtotime($msg->createdAt)) ?></span>
                            <?php if (!$msg->isRead): ?>
                                <form method="POST" action="/moderator/mark-message-read" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="message_id" value="<?= $msg->id ?>">
                                    <button type="submit" class="btn-primary admin-btn-xs">Marquer lu</button>
                                </form>
                            <?php else: ?>
                                <span class="admin-read-check">&#10003; Lu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-message-body"><?= htmlspecialchars($msg->message) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

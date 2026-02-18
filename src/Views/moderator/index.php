<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">shield</span> Mod&eacute;ration
    </h2>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Avis en attente -->
    <div class="profile-box">
        <h3 class="profil-titre">Avis en attente de mod&eacute;ration</h3>

        <?php if (empty($pendingReviews)): ?>
            <p style="text-align:center;color:#636e72;padding:30px 0;">
                <span class="material-icons" style="font-size:48px;display:block;margin-bottom:10px;color:#00b894;">check_circle</span>
                Aucun avis en attente
            </p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:12px;text-align:left;">ID</th>
                            <th style="padding:12px;text-align:left;">Auteur</th>
                            <th style="padding:12px;text-align:left;">Chauffeur</th>
                            <th style="padding:12px;text-align:left;">Note</th>
                            <th style="padding:12px;text-align:left;">Commentaire</th>
                            <th style="padding:12px;text-align:left;">Date</th>
                            <th style="padding:12px;text-align:left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReviews as $r): ?>
                            <tr style="border-bottom:1px solid #f1f2f6;">
                                <td style="padding:12px;"><?= (int)$r['id'] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($r['reviewer_name']) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($r['driver_name']) ?></td>
                                <td style="padding:12px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span style="color:<?= $i <= $r['rating'] ? '#ffd700' : '#ddd' ?>;">&#9733;</span>
                                    <?php endfor; ?>
                                </td>
                                <td style="padding:12px;max-width:300px;"><?= htmlspecialchars($r['comment'] ?? '') ?></td>
                                <td style="padding:12px;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                                <td style="padding:12px;white-space:nowrap;">
                                    <form method="POST" action="/moderator/approve-review" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn-primary" style="padding:6px 12px;font-size:13px;">Approuver</button>
                                    </form>
                                    <form method="POST" action="/moderator/reject-review" style="display:inline;margin-left:4px;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn-danger" style="padding:6px 12px;font-size:13px;">Rejeter</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Incidents en attente -->
    <div class="profile-box" style="margin-top:30px;">
        <h3 class="profil-titre">
            <span class="material-icons" style="vertical-align:middle;color:#e74c3c;">report_problem</span>
            Signalements en cours
            <?php if (!empty($incidents)): ?>
                <span style="background:#e74c3c;color:white;border-radius:12px;padding:2px 8px;font-size:13px;margin-left:8px;"><?= count($incidents) ?></span>
            <?php endif; ?>
        </h3>

        <?php if (empty($incidents)): ?>
            <p style="text-align:center;color:#636e72;padding:30px 0;">
                <span class="material-icons" style="font-size:48px;display:block;margin-bottom:10px;color:#00b894;">check_circle</span>
                Aucun incident en attente
            </p>
        <?php else: ?>
            <?php foreach ($incidents as $inc): ?>
                <div style="border:1px solid #f1f2f6;border-radius:8px;padding:15px;margin-bottom:15px;background:#fff9f9;">
                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div>
                            <strong>Trajet #<?= (int)($inc['trip_id'] ?? 0) ?></strong>
                            &mdash;
                            <?= htmlspecialchars($inc['ville_depart'] ?? '?') ?> &rarr; <?= htmlspecialchars($inc['ville_arrivee'] ?? '?') ?>
                        </div>
                        <span style="color:#636e72;font-size:13px;">
                            <?= isset($inc['departure_datetime']) ? date('d/m/Y H:i', strtotime($inc['departure_datetime'])) : '' ?>
                            <?php if (!empty($inc['arrival_datetime'])): ?>
                                &rarr; <?= date('H:i', strtotime($inc['arrival_datetime'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;">
                        <div>
                            <strong>Passager :</strong> <?= htmlspecialchars($inc['reporter_name'] ?? '?') ?>
                            <br><span style="color:#636e72;"><?= htmlspecialchars($inc['reporter_email'] ?? '') ?></span>
                        </div>
                        <div>
                            <strong>Chauffeur :</strong> <?= htmlspecialchars($inc['driver_name'] ?? '?') ?>
                            <br><span style="color:#636e72;"><?= htmlspecialchars($inc['driver_email'] ?? '') ?></span>
                        </div>
                    </div>
                    <?php if (!empty($inc['comment'])): ?>
                        <div style="margin-top:10px;padding:10px;background:#f8f9fa;border-radius:6px;font-style:italic;color:#636e72;">
                            &laquo; <?= htmlspecialchars((string)$inc['comment']) ?> &raquo;
                        </div>
                    <?php endif; ?>
                    <div style="margin-top:12px;border-top:1px solid #f1f2f6;padding-top:12px;">
                        <p style="font-size:13px;color:#636e72;margin:0 0 10px 0;font-weight:500;">D&eacute;cision de mod&eacute;ration :</p>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                            <form method="POST" action="/moderator/resolve-incident" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= (int)($inc['trip_id'] ?? 0) ?>">
                                <input type="hidden" name="reporter_id" value="<?= (int)($inc['reporter_id'] ?? 0) ?>">
                                <input type="hidden" name="credit_driver" value="0">
                                <button type="submit" class="btn-danger" style="padding:6px 14px;font-size:13px;" title="Le signalement est fond&eacute; : le chauffeur n'est pas pay&eacute;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:16px;">person_off</span>
                                    En faveur du passager
                                </button>
                            </form>
                            <form method="POST" action="/moderator/resolve-incident" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="trip_id" value="<?= (int)($inc['trip_id'] ?? 0) ?>">
                                <input type="hidden" name="reporter_id" value="<?= (int)($inc['reporter_id'] ?? 0) ?>">
                                <input type="hidden" name="credit_driver" value="1">
                                <button type="submit" class="btn-primary" style="padding:6px 14px;font-size:13px;" title="Le signalement est non fond&eacute; : le chauffeur est cr&eacute;dit&eacute;">
                                    <span class="material-icons" style="vertical-align:middle;font-size:16px;">check_circle</span>
                                    En faveur du chauffeur
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Incidents résolus -->
    <?php if (!empty($resolvedIncidents)): ?>
    <div class="profile-box" style="margin-top:30px;opacity:0.85;">
        <h3 class="profil-titre">
            <span class="material-icons" style="vertical-align:middle;color:#00b894;">check_circle</span>
            Signalements r&eacute;gl&eacute;s
            <span style="background:#00b894;color:white;border-radius:12px;padding:2px 8px;font-size:13px;margin-left:8px;"><?= count($resolvedIncidents) ?></span>
        </h3>
        <?php foreach ($resolvedIncidents as $inc): ?>
            <?php $decisionLabel = ($inc['decision'] ?? '') === 'favor_driver' ? 'En faveur du chauffeur' : 'En faveur du passager'; ?>
            <?php $decisionColor = ($inc['decision'] ?? '') === 'favor_driver' ? '#0984e3' : '#e17055'; ?>
            <div style="border:1px solid #e0f5ef;border-radius:8px;padding:15px;margin-bottom:15px;background:#f0faf7;">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;align-items:center;">
                    <div>
                        <strong>Trajet #<?= (int)($inc['trip_id'] ?? 0) ?></strong>
                        &mdash;
                        <?= htmlspecialchars($inc['ville_depart'] ?? '?') ?> &rarr; <?= htmlspecialchars($inc['ville_arrivee'] ?? '?') ?>
                    </div>
                    <span style="background:<?= $decisionColor ?>;color:white;border-radius:12px;padding:3px 10px;font-size:13px;font-weight:500;">
                        <?= htmlspecialchars($decisionLabel) ?>
                    </span>
                </div>
                <div style="margin-top:8px;font-size:13px;color:#636e72;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div><strong>Passager :</strong> <?= htmlspecialchars($inc['reporter_name'] ?? '?') ?></div>
                    <div><strong>Chauffeur :</strong> <?= htmlspecialchars($inc['driver_name'] ?? '?') ?></div>
                </div>
                <?php if (!empty($inc['resolved_at'])): ?>
                    <div style="margin-top:6px;font-size:12px;color:#b2bec3;">
                        R&eacute;solu le <?= date('d/m/Y à H:i', strtotime($inc['resolved_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Messages de contact -->
    <div class="profile-box" id="messages" style="margin-top:30px;">
        <h3 class="profil-titre">
            <span class="material-icons" style="vertical-align:middle;color:#0984e3;">mail</span>
            Messages de contact
            <?php $unread = count(array_filter($contactMessages ?? [], fn($m) => !$m['is_read'])); ?>
            <?php if ($unread > 0): ?>
                <span style="background:#e74c3c;color:white;border-radius:12px;padding:2px 8px;font-size:13px;margin-left:8px;"><?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h3>
        <?php if (empty($contactMessages)): ?>
            <p style="text-align:center;color:#636e72;padding:30px 0;">Aucun message reçu.</p>
        <?php else: ?>
            <?php foreach ($contactMessages as $msg): ?>
                <div style="border:1px solid <?= $msg['is_read'] ? '#f1f2f6' : '#fdcb6e' ?>;border-radius:8px;padding:15px;margin-bottom:12px;background:<?= $msg['is_read'] ? '#fafafa' : '#fffdf0' ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <strong><?= htmlspecialchars($msg['nom']) ?></strong>
                            &lt;<a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color:#0984e3;"><?= htmlspecialchars($msg['email']) ?></a>&gt;
                            &mdash; <em><?= htmlspecialchars($msg['sujet']) ?></em>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:12px;color:#b2bec3;"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
                            <?php if (!$msg['is_read']): ?>
                                <form method="POST" action="/moderator/mark-message-read" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                    <button type="submit" class="btn-primary" style="padding:4px 10px;font-size:12px;">Marquer lu</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#00b894;font-size:12px;">&#10003; Lu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="margin-top:10px;padding:10px;background:white;border-radius:6px;color:#2d3436;font-size:14px;white-space:pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
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

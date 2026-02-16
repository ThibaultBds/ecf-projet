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

    <!-- Incidents de trajets -->
    <div class="profile-box" style="margin-top:30px;">
        <h3 class="profil-titre">
            <span class="material-icons" style="vertical-align:middle;color:#e74c3c;">report_problem</span>
            Trajets signal&eacute;s
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
                    <div style="margin-top:12px;text-align:right;">
                        <form method="POST" action="/moderator/resolve-incident" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="trip_id" value="<?= (int)($inc['trip_id'] ?? 0) ?>">
                            <input type="hidden" name="reporter_id" value="<?= (int)($inc['reporter_id'] ?? 0) ?>">
                            <button type="submit" class="btn-primary" style="padding:6px 16px;font-size:13px;">
                                <span class="material-icons" style="vertical-align:middle;font-size:16px;">check</span> Marquer comme r&eacute;solu
                            </button>
                        </form>
                    </div>
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

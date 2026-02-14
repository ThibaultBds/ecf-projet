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

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>

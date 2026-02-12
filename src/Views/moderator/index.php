<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">shield</span> Modération
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <h3 class="profil-titre">Signalements à traiter</h3>

        <?php if (empty($reports)): ?>
            <p style="text-align:center;color:#636e72;padding:30px 0;">
                <span class="material-icons" style="font-size:48px;display:block;margin-bottom:10px;color:#00b894;">check_circle</span>
                Aucun signalement en attente
            </p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:12px;text-align:left;">ID</th>
                            <th style="padding:12px;text-align:left;">Type</th>
                            <th style="padding:12px;text-align:left;">Message</th>
                            <th style="padding:12px;text-align:left;">Par</th>
                            <th style="padding:12px;text-align:left;">Statut</th>
                            <th style="padding:12px;text-align:left;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr style="border-bottom:1px solid #f1f2f6;">
                                <td style="padding:12px;"><?= (int)$r['id'] ?></td>
                                <td style="padding:12px;">
                                    <span class="admin-badge <?= htmlspecialchars($r['type']) ?>"><?= htmlspecialchars($r['type']) ?></span>
                                </td>
                                <td style="padding:12px;"><?= htmlspecialchars($r['message']) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($r['reporter'] ?? '—') ?></td>
                                <td style="padding:12px;">
                                    <span class="admin-badge <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span>
                                </td>
                                <td style="padding:12px;"><?= htmlspecialchars($r['created_at']) ?></td>
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

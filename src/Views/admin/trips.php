<?php
$statusLabels = ['scheduled' => 'Planifié', 'started' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'];
?>
<div style="max-width:1100px;margin:40px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="/admin" style="color:#00b894;text-decoration:none;">← Retour admin</a>
        <h2 style="margin:0;">Trajets planifiés</h2>
    </div>

    <?php if (empty($trips)): ?>
        <p style="color:#636e72;">Aucun trajet planifié.</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <thead style="background:#00b894;color:white;">
                <tr>
                    <th style="padding:12px 16px;text-align:left;">#</th>
                    <th style="padding:12px 16px;text-align:left;">Trajet</th>
                    <th style="padding:12px 16px;text-align:left;">Chauffeur</th>
                    <th style="padding:12px 16px;text-align:left;">Départ</th>
                    <th style="padding:12px 16px;text-align:left;">Places</th>
                    <th style="padding:12px 16px;text-align:left;">Prix</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $i => $trip): ?>
                    <tr style="border-bottom:1px solid #f0f0f0;<?= $i % 2 === 1 ? 'background:#f9f9f9;' : '' ?>">
                        <td style="padding:12px 16px;"><?= $trip['trip_id'] ?></td>
                        <td style="padding:12px 16px;"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['arrival_city']) ?></td>
                        <td style="padding:12px 16px;"><?= htmlspecialchars($trip['chauffeur']) ?></td>
                        <td style="padding:12px 16px;"><?= date('d/m/Y H:i', strtotime($trip['departure_datetime'])) ?></td>
                        <td style="padding:12px 16px;"><?= $trip['available_seats'] ?></td>
                        <td style="padding:12px 16px;"><?= $trip['price'] ?> cr.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

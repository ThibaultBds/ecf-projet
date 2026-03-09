<?php
$statusLabels = ['scheduled' => 'Planifié', 'started' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'];
?>
<div class="admin-trips-wrap">
    <div class="admin-trips-head">
        <a href="/admin" class="admin-trips-back">← Retour admin</a>
        <h2 class="admin-trips-title">Trajets planifiés</h2>
    </div>

    <?php if (empty($trips)): ?>
        <p class="admin-trips-empty">Aucun trajet planifié.</p>
    <?php else: ?>
        <table class="admin-trips-table">
            <thead class="admin-trips-thead">
                <tr>
                    <th>#</th>
                    <th>Trajet</th>
                    <th>Chauffeur</th>
                    <th>Départ</th>
                    <th>Places</th>
                    <th>Prix</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $i => $trip): ?>
                    <tr class="admin-trips-row <?= $i % 2 === 1 ? 'admin-trips-row-alt' : '' ?>">
                        <td><?= $trip['trip_id'] ?></td>
                        <td>
                            <a href="/covoiturages/<?= $trip['trip_id'] ?>" class="admin-trips-trip-link">
                                <?= htmlspecialchars($trip['ville_depart']) ?> → <?= htmlspecialchars($trip['ville_arrivee']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($trip['chauffeur']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($trip['departure_datetime'])) ?></td>
                        <td><?= $trip['available_seats'] ?></td>
                        <td><?= $trip['price'] ?> cr.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

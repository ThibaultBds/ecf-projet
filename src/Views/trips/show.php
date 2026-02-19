<section class="detail-container" style="max-width:800px;margin:0 auto;padding:20px;">
    <div class="detail-card" style="background:white;border-radius:12px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

        <!-- En-tête -->
        <div class="detail-header" style="border-bottom:2px solid #f1f2f6;padding-bottom:20px;margin-bottom:20px;">
            <h2 style="margin:0;color:#2d3436;font-size:28px;">
                <?= htmlspecialchars($covoiturage['ville_depart']) ?> → <?= htmlspecialchars($covoiturage['ville_arrivee']) ?>
            </h2>
            <p style="margin:5px 0;color:#636e72;font-size:18px;display:flex;align-items:center;gap:10px;">
                <?php if (!empty($covoiturage['conducteur_photo'])): ?>
                    <img src="/uploads/<?= htmlspecialchars($covoiturage['conducteur_photo']) ?>" alt="Photo" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <span class="material-icons" style="font-size:48px;color:#00b894;">account_circle</span>
                <?php endif; ?>
                <span>Conducteur : <?= htmlspecialchars($covoiturage['conducteur']) ?></span>
            </p>
            <?php if ($covoiturage['energy_type'] === 'electrique'): ?>
                <div style="display:inline-block;background:#00b894;color:white;padding:4px 12px;border-radius:15px;font-size:12px;font-weight:600;margin-top:10px;">
                    ⚡ Trajet écologique
                </div>
            <?php endif; ?>
        </div>

        <!-- Infos détaillées -->
        <div class="detail-info" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;">
            <div class="info-item">
                <h4 style="margin:0 0 10px 0;color:#2d3436;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;">schedule</span> Date et heure
                </h4>
                <p><?= date('d/m/Y à H:i', strtotime($covoiturage['departure_datetime'])) ?></p>
            </div>
            <div class="info-item">
                <h4 style="margin:0 0 10px 0;color:#2d3436;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;">directions_car</span> Véhicule
                </h4>
                <p><?= htmlspecialchars($covoiturage['brand']) ?> <?= htmlspecialchars($covoiturage['model']) ?></p>
                <p style="font-size:14px;color:#636e72;"><?= ucfirst(htmlspecialchars($covoiturage['energy_type'])) ?></p>
            </div>
            <div class="info-item">
                <h4 style="margin:0 0 10px 0;color:#2d3436;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;">people</span> Places disponibles
                </h4>
                <p><?= (int)$covoiturage['available_seats'] ?> / <?= (int)$covoiturage['seats_available'] ?> places</p>
            </div>
            <div class="info-item">
                <h4 style="margin:0 0 10px 0;color:#2d3436;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;">euro</span> Prix
                </h4>
                <p style="font-size:24px;font-weight:bold;color:#00b894;"><?= number_format($covoiturage['price'], 2) ?>€</p>
                <p style="font-size:14px;color:#636e72;">Crédits requis : <?= $credit_requis ?></p>
            </div>
        </div>

        <!-- Préférences du conducteur -->
        <?php if (!empty($driverPrefs)): ?>
        <div class="preferences-section" style="margin-bottom:30px;">
            <h3 style="border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:20px;">Préférences du conducteur</h3>
            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php
                $prefIcons = [
                    'fumeur' => ['smoking_rooms', 'Fumeur accepté', 'Non-fumeur'],
                    'animaux' => ['pets', 'Animaux acceptés', 'Pas d\'animaux'],
                    'musique' => ['music_note', 'Musique', 'Silence préféré'],
                    'discussion' => ['chat', '', ''],
                ];
                $discussionLabels = ['plaisir' => 'Discussion avec plaisir', 'un_peu' => 'Discussion modérée', 'silence' => 'Silence préféré'];

                foreach (['fumeur', 'animaux', 'musique'] as $key):
                    $val = $driverPrefs[$key] ?? 'non';
                    $icon = $prefIcons[$key][0];
                    $label = ($val === 'oui') ? $prefIcons[$key][1] : $prefIcons[$key][2];
                    $color = ($val === 'oui') ? '#00b894' : '#636e72';
                ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#f8f9fa;padding:8px 14px;border-radius:20px;font-size:14px;color:<?= $color ?>;">
                        <span class="material-icons" style="font-size:18px;"><?= $icon ?></span>
                        <?= htmlspecialchars($label) ?>
                    </span>
                <?php endforeach; ?>

                <?php $disc = $driverPrefs['discussion'] ?? 'un_peu'; ?>
                <span style="display:inline-flex;align-items:center;gap:5px;background:#f8f9fa;padding:8px 14px;border-radius:20px;font-size:14px;color:#636e72;">
                    <span class="material-icons" style="font-size:18px;">chat</span>
                    <?= htmlspecialchars($discussionLabels[$disc] ?? 'Discussion modérée') ?>
                </span>
            </div>

            <?php
            $custom = $driverPrefs['custom_preferences'] ?? [];
            if (is_array($custom) && !empty($custom)):
            ?>
                <div style="margin-top:15px;">
                    <h4 style="font-size:14px;color:#636e72;margin-bottom:8px;">Autres préférences :</h4>
                    <?php foreach ($custom as $pref): ?>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#fff3e0;padding:6px 12px;border-radius:15px;font-size:13px;color:#e67e22;margin:3px;">
                            <span class="material-icons" style="font-size:16px;">label</span>
                            <?= htmlspecialchars((string) $pref) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Avis -->
        <div class="reviews-section" style="margin-bottom:30px;">
            <h3 style="border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:20px;">Avis sur le conducteur</h3>
            <?php if (empty($reviews)): ?>
                <p>Ce conducteur n'a pas encore reçu d'avis.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" style="border:1px solid #f1f2f6;border-radius:8px;padding:15px;margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                            <div style="display:flex;align-items:center;gap:2px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="material-icons" style="font-size:16px;color:<?= $i <= $review['rating'] ? '#ffd700' : '#ddd' ?>;">star</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p style="margin:5px 0 0 0;font-style:italic;color:#636e72;">"<?= htmlspecialchars($review['comment']) ?>"</p>
                        <small style="color:#b2bec3;"><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Section participation -->
        <div class="participation-section" style="text-align:center;padding:20px;background:#f8f9fa;border-radius:8px;">
            <?php if (isset($_SESSION['user'])): ?>
                <?php if ($isParticipating): ?>
                    <p style="color:#636e72;">
                        <span class="material-icons" style="vertical-align:middle;">check_circle</span>
                        Vous participez déjà à ce trajet
                    </p>
                <?php elseif ((int)$covoiturage['chauffeur_id'] === ($_SESSION['user']['id'] ?? 0)): ?>
                    <p style="color:#636e72;">
                        <span class="material-icons" style="vertical-align:middle;">directions_car</span>
                        Vous êtes le conducteur de ce trajet
                    </p>
                <?php elseif ((int)$covoiturage['available_seats'] > 0): ?>
                    <?php if ($user_credit >= $credit_requis): ?>
                        <p style="color:#00b894;margin-bottom:15px;">
                            <span class="material-icons" style="vertical-align:middle;">account_balance_wallet</span>
                            Votre crédit : <?= $user_credit ?> crédits
                        </p>
                        <button id="participate-btn" class="btn-primary" style="padding:15px 30px;font-size:18px;">
                            <span class="material-icons">add_circle</span> Participer à ce covoiturage
                        </button>
                    <?php else: ?>
                        <p style="color:#e74c3c;margin-bottom:15px;">
                            <span class="material-icons" style="vertical-align:middle;">warning</span>
                            Crédit insuffisant (<?= $user_credit ?>/<?= $credit_requis ?> requis)
                        </p>
                        <button class="btn-secondary" disabled>Crédit insuffisant</button>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color:#e74c3c;"><span class="material-icons" style="vertical-align:middle;">event_busy</span> Aucune place disponible</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-invitation" style="background:linear-gradient(135deg, #00b894 0%, #00cec9 100%);border-radius:12px;padding:30px;color:white;">
                    <span class="material-icons" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.9;">lock_open</span>
                    <h3 style="margin:0 0 10px 0;font-size:24px;font-weight:600;">Rejoignez l'aventure EcoRide !</h3>
                    <p style="margin:0 0 20px 0;opacity:0.9;">Connectez-vous pour participer à ce covoiturage</p>
                    <div style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;">
                        <a href="/login?redirect=/trip/<?= $covoiturage['trip_id'] ?>" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);color:white;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:500;">
                            <span class="material-icons">login</span> Se connecter
                        </a>
                        <a href="/register?redirect=/trip/<?= $covoiturage['trip_id'] ?>" style="display:inline-flex;align-items:center;gap:8px;background:white;color:#00b894;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:500;">
                            <span class="material-icons">person_add</span> Créer un compte
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Retour -->
        <div style="text-align:center;margin-top:20px;display:flex;justify-content:center;gap:20px;flex-wrap:wrap;">
            <a href="/trips" style="color:#00b894;text-decoration:none;font-weight:500;">← Retour aux covoiturages</a>
            <a href="/" style="color:#636e72;text-decoration:none;">Retour à l'accueil</a>
        </div>
    </div>
</section>

<!-- Modal de confirmation -->
<dialog id="confirm-modal" style="border:none;padding:0;background:rgba(34,49,63,0.95);backdrop-filter:blur(4px);border-radius:18px;box-shadow:0 8px 40px rgba(0,0,0,0.25);">
    <div style="background:white;border-radius:18px;padding:40px 30px;max-width:350px;min-width:280px;text-align:center;">
        <span class="material-icons" style="font-size:48px;color:#00b894;margin-bottom:10px;">check_circle</span>
        <h3 style="margin:0 0 18px 0;color:#2d3436;">Confirmer votre participation</h3>
        <p><strong>Trajet :</strong> <?= htmlspecialchars($covoiturage['ville_depart']) ?> → <?= htmlspecialchars($covoiturage['ville_arrivee']) ?></p>
        <p><strong>Prix :</strong> <?= number_format($covoiturage['price'], 2) ?>€</p>
        <p><strong>Crédits :</strong> <?= $credit_requis ?></p>
        <div style="display:flex;gap:12px;justify-content:center;margin-top:22px;">
            <button type="button" id="cancel-btn" class="btn-secondary" style="padding:12px 24px;border-radius:8px;">Annuler</button>
            <button type="button" id="confirm-btn" class="btn-primary" style="padding:12px 24px;border-radius:8px;">Confirmer</button>
        </div>
    </div>
</dialog>

<!-- Modal de résultat -->
<dialog id="result-modal" style="border:none;padding:0;background:rgba(34,49,63,0.95);backdrop-filter:blur(4px);border-radius:18px;">
    <div style="background:white;border-radius:18px;padding:40px 30px;max-width:350px;min-width:280px;text-align:center;">
        <span class="material-icons" id="result-icon" style="font-size:48px;color:#00b894;">check_circle</span>
        <h3 id="result-title" style="margin:10px 0 12px 0;color:#2d3436;"></h3>
        <p id="result-msg" style="color:#636e72;margin-bottom:22px;"></p>
        <button type="button" id="result-close" class="btn-primary" style="padding:12px 24px;border-radius:8px;">Fermer</button>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var participateBtn = document.getElementById('participate-btn');
    var confirmModal = document.getElementById('confirm-modal');
    var cancelBtn = document.getElementById('cancel-btn');
    var confirmBtn = document.getElementById('confirm-btn');
    var resultModal = document.getElementById('result-modal');

    if (participateBtn) {
        participateBtn.addEventListener('click', function() { confirmModal.showModal(); });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() { confirmModal.close(); });
    }
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            confirmBtn.disabled = true;
            var fd = new FormData();
            fd.append('trip_id', '<?= (int)$covoiturage['trip_id'] ?>');
            fd.append('credits', '<?= $credit_requis ?>');
            fd.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>');

            fetch('/api/trip/<?= (int)$covoiturage['trip_id'] ?>/join', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    confirmModal.close();
                    var icon = document.getElementById('result-icon');
                    var title = document.getElementById('result-title');
                    var msg = document.getElementById('result-msg');
                    if (data.success) {
                        icon.textContent = 'check_circle'; icon.style.color = '#00b894';
                        title.textContent = 'Participation confirmée !';
                        msg.textContent = 'Nouveau solde : ' + data.new_credits + ' crédits';
                    } else {
                        icon.textContent = 'error'; icon.style.color = '#e74c3c';
                        title.textContent = 'Erreur';
                        msg.textContent = data.message || 'Erreur lors de la participation.';
                    }
                    resultModal.showModal();
                })
                .catch(function() {
                    confirmModal.close();
                    document.getElementById('result-icon').textContent = 'error';
                    document.getElementById('result-title').textContent = 'Erreur technique';
                    document.getElementById('result-msg').textContent = 'Réessayez plus tard.';
                    resultModal.showModal();
                })
                .finally(function() { confirmBtn.disabled = false; });
        });
    }
    var resultClose = document.getElementById('result-close');
    if (resultClose) {
        resultClose.addEventListener('click', function() {
            resultModal.close();
            if (document.getElementById('result-title').textContent === 'Participation confirmée !') {
                window.location.reload();
            }
        });
    }
});
</script>

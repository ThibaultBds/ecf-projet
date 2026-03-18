<section class="detail-container trip-detail-page">
    <div class="detail-card trip-detail-card">

        <div class="detail-header trip-detail-header">
            <h2 class="trip-detail-title">
                <?= htmlspecialchars($covoiturage->villeDepart) ?> → <?= htmlspecialchars($covoiturage->villeArrivee) ?>
            </h2>
            <p class="trip-driver-line">
                <?php if (!empty($covoiturage->conducteurPhoto)): ?>
                    <img src="/uploads/<?= htmlspecialchars($covoiturage->conducteurPhoto) ?>" alt="Photo" class="trip-driver-photo">
                <?php else: ?>
                    <span class="material-icons trip-driver-fallback-icon">account_circle</span>
                <?php endif; ?>
                <span>Conducteur : <?= htmlspecialchars($covoiturage->conducteur) ?></span>
            </p>
            <?php if ($covoiturage->energyType === 'electrique'): ?>
                <div class="trip-eco-badge">⚡ Trajet écologique</div>
            <?php endif; ?>
        </div>

        <div class="detail-info trip-detail-info-grid">
            <div class="info-item">
                <h4 class="trip-info-title">
                    <span class="material-icons trip-info-icon">schedule</span> Date et heure
                </h4>
                <p><?= date('d/m/Y à H:i', strtotime($covoiturage->departureDatetime)) ?></p>
            </div>
            <div class="info-item">
                <h4 class="trip-info-title">
                    <span class="material-icons trip-info-icon">directions_car</span> Véhicule
                </h4>
                <p><?= htmlspecialchars($covoiturage->brand) ?> <?= htmlspecialchars($covoiturage->model) ?></p>
                <p class="trip-subtext"><?= ucfirst(htmlspecialchars($covoiturage->energyType)) ?></p>
            </div>
            <div class="info-item">
                <h4 class="trip-info-title">
                    <span class="material-icons trip-info-icon">people</span> Places disponibles
                </h4>
                <p><?= (int) $covoiturage->availableSeats ?> / <?= (int) $covoiturage->seatsAvailable ?> places</p>
            </div>
            <div class="info-item">
                <h4 class="trip-info-title">
                    <span class="material-icons trip-info-icon">euro</span> Prix
                </h4>
                <p class="trip-price"><?= number_format($covoiturage->price, 2) ?>€</p>
                <p class="trip-subtext">Crédits requis : <?= $credit_requis ?></p>
            </div>
        </div>

        <?php if (!empty($driverPrefs)): ?>
        <div class="preferences-section trip-pref-section">
            <h3 class="trip-section-title">Préférences du conducteur</h3>
            <div class="trip-pref-tags">
                <?php
                $prefIcons = [
                    'fumeur'     => ['smoking_rooms', 'Fumeur accepté', 'Non-fumeur'],
                    'animaux'    => ['pets', 'Animaux acceptés', 'Pas d\'animaux'],
                    'musique'    => ['music_note', 'Musique', 'Silence préféré'],
                    'discussion' => ['chat', '', ''],
                ];
                $discussionLabels = ['plaisir' => 'Discussion avec plaisir', 'un_peu' => 'Discussion modérée', 'silence' => 'Silence préféré'];

                foreach (['fumeur', 'animaux', 'musique'] as $key):
                    $val        = $driverPrefs[$key] ?? 'non';
                    $icon       = $prefIcons[$key][0];
                    $label      = ($val === 'oui') ? $prefIcons[$key][1] : $prefIcons[$key][2];
                    $colorClass = ($val === 'oui') ? 'pref-positive' : 'pref-neutral';
                ?>
                    <span class="trip-pref-tag <?= $colorClass ?>">
                        <span class="material-icons trip-pref-tag-icon"><?= $icon ?></span>
                        <?= htmlspecialchars($label) ?>
                    </span>
                <?php endforeach; ?>

                <?php $disc = $driverPrefs['discussion'] ?? 'un_peu'; ?>
                <span class="trip-pref-tag pref-neutral">
                    <span class="material-icons trip-pref-tag-icon">chat</span>
                    <?= htmlspecialchars($discussionLabels[$disc] ?? 'Discussion modérée') ?>
                </span>
            </div>

            <?php
            $custom = $driverPrefs['custom_preferences'] ?? [];
            if (is_array($custom) && !empty($custom)):
            ?>
                <div class="trip-custom-pref-wrap">
                    <h4 class="trip-custom-pref-title">Autres préférences :</h4>
                    <?php foreach ($custom as $pref): ?>
                        <span class="trip-custom-pref-tag">
                            <span class="material-icons trip-custom-pref-icon">label</span>
                            <?= htmlspecialchars((string) $pref) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="reviews-section trip-reviews-section">
            <h3 class="trip-section-title">Avis sur le conducteur</h3>
            <?php if (empty($reviews)): ?>
                <p>Ce conducteur n'a pas encore reçu d'avis.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card trip-review-card">
                        <div class="trip-review-head">
                            <strong><?= htmlspecialchars($review->reviewerName) ?></strong>
                            <div class="trip-review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="material-icons trip-star <?= $i <= $review->rating ? 'trip-star-on' : 'trip-star-off' ?>">star</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="trip-review-comment">"<?= htmlspecialchars($review->comment) ?>"</p>
                        <small class="trip-review-date"><?= date('d/m/Y', strtotime($review->createdAt)) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="participation-section trip-participation-section">
            <?php if (isset($_SESSION['user'])): ?>
                <?php if ($isParticipating): ?>
                    <p class="trip-participation-note trip-note-neutral">
                        <span class="material-icons trip-inline-icon">check_circle</span>
                        Vous participez déjà à ce trajet
                    </p>
                <?php elseif ((int) $covoiturage->chauffeurId === ($_SESSION['user']['id'] ?? 0)): ?>
                    <p class="trip-participation-note trip-note-neutral">
                        <span class="material-icons trip-inline-icon">directions_car</span>
                        Vous êtes le conducteur de ce trajet
                    </p>
                <?php elseif ((int) $covoiturage->availableSeats > 0): ?>
                    <?php if ($user_credit >= $credit_requis): ?>
                        <p class="trip-participation-note trip-note-success trip-note-gap">
                            <span class="material-icons trip-inline-icon">account_balance_wallet</span>
                            Votre crédit : <?= $user_credit ?> crédits
                        </p>
                        <button id="participate-btn" class="btn-primary trip-participate-btn">
                            <span class="material-icons">add_circle</span> Participer à ce covoiturage
                        </button>
                    <?php else: ?>
                        <p class="trip-participation-note trip-note-danger trip-note-gap">
                            <span class="material-icons trip-inline-icon">warning</span>
                            Crédit insuffisant (<?= $user_credit ?>/<?= $credit_requis ?> requis)
                        </p>
                        <button class="btn-secondary" disabled>Crédit insuffisant</button>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="trip-participation-note trip-note-danger"><span class="material-icons trip-inline-icon">event_busy</span> Aucune place disponible</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-invitation trip-login-invitation">
                    <span class="material-icons trip-login-icon">lock_open</span>
                    <h3 class="trip-login-title">Rejoignez l'aventure EcoRide !</h3>
                    <p class="trip-login-text">Connectez-vous pour participer à ce covoiturage</p>
                    <div class="trip-login-links">
                        <a href="/login?redirect=/trip/<?= $covoiturage->tripId ?>" class="trip-login-link trip-login-link-soft">
                            <span class="material-icons">login</span> Se connecter
                        </a>
                        <a href="/register?redirect=/trip/<?= $covoiturage->tripId ?>" class="trip-login-link trip-login-link-solid">
                            <span class="material-icons">person_add</span> Créer un compte
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="trip-detail-links">
            <a href="/trips" class="trip-detail-link trip-detail-link-primary">← Retour aux covoiturages</a>
            <a href="/" class="trip-detail-link trip-detail-link-muted">Retour à l'accueil</a>
        </div>
    </div>
</section>

<dialog id="confirm-modal" class="trip-modal">
    <div class="trip-modal-card">
        <span class="material-icons trip-modal-ok-icon">check_circle</span>
        <h3 class="trip-modal-title">Confirmer votre participation</h3>
        <p><strong>Trajet :</strong> <?= htmlspecialchars($covoiturage->villeDepart) ?> → <?= htmlspecialchars($covoiturage->villeArrivee) ?></p>
        <p><strong>Prix :</strong> <?= number_format($covoiturage->price, 2) ?>€</p>
        <p><strong>Crédits :</strong> <?= $credit_requis ?></p>
        <div class="trip-modal-actions">
            <button type="button" id="cancel-btn" class="btn-secondary trip-modal-btn">Annuler</button>
            <button type="button" id="confirm-btn" class="btn-primary trip-modal-btn">Confirmer</button>
        </div>
    </div>
</dialog>

<dialog id="result-modal" class="trip-modal">
    <div class="trip-modal-card">
        <span class="material-icons trip-modal-result-icon is-success" id="result-icon">check_circle</span>
        <h3 id="result-title" class="trip-modal-result-title"></h3>
        <p id="result-msg" class="trip-modal-result-msg"></p>
        <button type="button" id="result-close" class="btn-primary trip-modal-btn">Fermer</button>
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
            fd.append('trip_id', '<?= $covoiturage->tripId ?>');
            fd.append('credits', '<?= $credit_requis ?>');
            fd.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>');

            fetch('/api/trip/<?= $covoiturage->tripId ?>/join', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    confirmModal.close();
                    var icon = document.getElementById('result-icon');
                    var title = document.getElementById('result-title');
                    var msg = document.getElementById('result-msg');
                    if (data.success) {
                        icon.textContent = 'check_circle';
                        icon.classList.remove('is-error');
                        icon.classList.add('is-success');
                        title.textContent = 'Participation confirmée !';
                        msg.textContent = 'Nouveau solde : ' + data.new_credits + ' crédits';
                    } else {
                        icon.textContent = 'error';
                        icon.classList.remove('is-success');
                        icon.classList.add('is-error');
                        title.textContent = 'Erreur';
                        msg.textContent = data.message || 'Erreur lors de la participation.';
                    }
                    resultModal.showModal();
                })
                .catch(function() {
                    confirmModal.close();
                    var icon = document.getElementById('result-icon');
                    icon.textContent = 'error';
                    icon.classList.remove('is-success');
                    icon.classList.add('is-error');
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

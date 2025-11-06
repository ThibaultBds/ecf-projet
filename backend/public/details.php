<?php
session_start();
require_once 'config/autoload.php';
useClass('Database');

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction utilitaire locale si elle n'existe pas déjà dans vos includes
if (!function_exists('getReviewsByDriverId')) {
    function getReviewsByDriverId($chauffeur_id) {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("
            SELECT r.*, u.pseudo as reviewer_pseudo
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.reviewed_id = ? AND r.status = 'valide'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$chauffeur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Récupérer l'ID du trajet
$covoiturage_id = (int)($_GET['id'] ?? 0);

if ($covoiturage_id <= 0) {
    header('Location: covoiturages.php');
    exit();
}

// Récupérer les données du trajet depuis la BDD
try {
    $covoiturage = getTripById($covoiturage_id);
    if (!$covoiturage) {
        header('Location: covoiturages.php?error=not_found');
        exit();
    }
    
    // Récupérer les avis sur le conducteur
    $reviews = getReviewsByDriverId($covoiturage['chauffeur_id']);
    
    // Décoder les préférences
    $preferences = json_decode($covoiturage['preferences'] ?? '{}', true);
    
} catch (Exception $e) {
    error_log("Erreur details.php: " . $e->getMessage());
    header('Location: covoiturages.php?error=db_error');
    exit();
}

// Récupérer le crédit utilisateur si connecté
$user_credit = 0;
if (isset($_SESSION['user'])) {
    try {
        $userData = getUserById($_SESSION['user']['id']);
        $user_credit = $userData['credits'] ?? 0;
        $_SESSION['user']['credits'] = $user_credit; // Mettre à jour la session
    } catch (Exception $e) {
        $user_credit = 0;
    }
}

// Calculer le crédit requis (prix du trajet en crédits)
$credit_requis = (int)$covoiturage['prix'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail du covoiturage - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main>
        <section class="detail-container" style="max-width:800px;margin:0 auto;padding:20px;">
            <div class="detail-card" style="background:white;border-radius:12px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <div class="detail-header" style="border-bottom:2px solid #f1f2f6;padding-bottom:20px;margin-bottom:20px;">
                    <h2 style="margin:0;color:#2d3436;font-size:28px;">
                        <?= htmlspecialchars($covoiturage['ville_depart']) ?> → <?= htmlspecialchars($covoiturage['ville_arrivee']) ?>
                    </h2>
                    <p style="margin:5px 0;color:#636e72;font-size:18px; display:flex; align-items:center; gap:10px;">
                        <img src="<?= htmlspecialchars($covoiturage['conducteur_avatar_url'] ?? 'assets/default_avatar.png') ?>" alt="Avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;image-rendering: -moz-crisp-edges; image-rendering: -o-crisp-edges; image-rendering: pixelated; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; filter: none;">
                        <span>Conducteur : <?= htmlspecialchars($covoiturage['conducteur']) ?></span>
                    </p>
                    <?php if ($covoiturage['is_ecological']): ?>
                        <div style="display:inline-block;background:#00b894;color:white;padding:4px 12px;border-radius:15px;font-size:12px;font-weight:600;margin-top:10px;">
                            ⚡ Trajet écologique
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-info" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;">
                    <div class="info-item">
                        <h4 style="margin:0 0 10px 0;color:#2d3436;">
                            <span class="material-icons" style="vertical-align:middle;color:#00b894;">schedule</span>
                            Date et heure
                        </h4>
                        <p><?= date('d/m/Y à H:i', strtotime($covoiturage['date_depart'])) ?></p>
                    </div>

                    <div class="info-item">
                        <h4 style="margin:0 0 10px 0;color:#2d3436;">
                            <span class="material-icons" style="vertical-align:middle;color:#00b894;">directions_car</span>
                            Véhicule
                        </h4>
                        <p><?= htmlspecialchars($covoiturage['marque']) ?> <?= htmlspecialchars($covoiturage['modele']) ?></p>
                        <p style="font-size:14px;color:#636e72;"><?= ucfirst($covoiturage['energie']) ?></p>
                    </div>

                    <div class="info-item">
                        <h4 style="margin:0 0 10px 0;color:#2d3436;">
                            <span class="material-icons" style="vertical-align:middle;color:#00b894;">people</span>
                            Places disponibles
                        </h4>
                        <p><?= $covoiturage['places_restantes'] ?> / <?= $covoiturage['places_totales'] ?> places</p>
                    </div>

                    <div class="info-item">
                        <h4 style="margin:0 0 10px 0;color:#2d3436;">
                            <span class="material-icons" style="vertical-align:middle;color:#00b894;">euro</span>
                            Prix
                        </h4>
                        <p style="font-size:24px;font-weight:bold;color:#00b894;"><?= number_format($covoiturage['prix'], 2) ?>€</p>
                        <p style="font-size:14px;color:#636e72;">Crédits requis : <?= $credit_requis ?></p>
                    </div>
                </div>

                <!-- Section Préférences -->
                <div class="preferences-section" style="margin-bottom:30px;">
                    <h3 style="border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:20px;">Préférences du conducteur</h3>
                    <div style="display:flex; gap: 20px; flex-wrap:wrap;">
                        <span><span class="material-icons" style="vertical-align:middle;"><?= ($preferences['fumeur'] ?? 'non') === 'oui' ? 'smoke_free' : 'smoking_rooms'; ?></span> <?= ($preferences['fumeur'] ?? 'non') === 'oui' ? 'Non-fumeur' : 'Fumeur accepté' ?></span>
                        <span><span class="material-icons" style="vertical-align:middle;"><?= ($preferences['animaux'] ?? 'non') === 'oui' ? 'pets' : 'do_not_disturb_on'; ?></span> <?= ($preferences['animaux'] ?? 'non') === 'oui' ? 'Animaux acceptés' : 'Pas d\'animaux' ?></span>
                        <?php if (!empty($preferences['custom'])): ?>
                            <span><span class="material-icons" style="vertical-align:middle;">info</span> <?= htmlspecialchars($preferences['custom']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section Avis -->
                <div class="reviews-section" style="margin-bottom:30px;">
                    <h3 style="border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:20px;">Avis sur le conducteur</h3>
                    <?php if (empty($reviews)): ?>
                        <p>Ce conducteur n'a pas encore reçu d'avis.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card" style="border:1px solid #f1f2f6;border-radius:8px;padding:15px;margin-bottom:10px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <strong><?= htmlspecialchars($review['reviewer_pseudo']) ?></strong>
                                    <div style="display:flex;align-items:center;gap:2px;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="material-icons" style="font-size:16px;color:<?= $i <= $review['note'] ? '#ffd700' : '#ddd' ?>;">star</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p style="margin:5px 0 0 0;font-style:italic;color:#636e72;">"<?= htmlspecialchars($review['commentaire']) ?>"</p>
                                <small style="color:#b2bec3;"><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="participation-section" style="text-align:center;padding:20px;background:#f8f9fa;border-radius:8px;">
                    <?php if (isset($_SESSION['user'])): ?>
                        <?php if ($covoiturage['places_restantes'] > 0): ?>
                            <?php if ($user_credit >= $credit_requis): ?>
                                <p style="color:#00b894;margin-bottom:15px;">
                                    <span class="material-icons" style="vertical-align:middle;">account_balance_wallet</span>
                                    Votre crédit : <?= $user_credit ?> crédits
                                </p>
                                <button id="participate-btn" class="btn-primary" style="padding:15px 30px;font-size:18px;">
                                    <span class="material-icons">add_circle</span>
                                    Participer à ce covoiturage
                                </button>
                            <?php else: ?>
                                <p style="color:#e74c3c;margin-bottom:15px;">
                                    <span class="material-icons" style="vertical-align:middle;">warning</span>
                                    Crédit insuffisant (<?= $user_credit ?>/<?= $credit_requis ?> requis)
                                </p>
                                <button class="btn-secondary" disabled>
                                    Crédit insuffisant
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color:#e74c3c;">
                                <span class="material-icons" style="vertical-align:middle;">event_busy</span>
                                Aucune place disponible
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="login-invitation" style="background:linear-gradient(135deg, #00b894 0%, #00cec9 100%);border-radius:12px;padding:30px;color:white;position:relative;overflow:hidden;">
                            <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;background:rgba(255,255,255,0.1);border-radius:50%;"></div>
                            <div style="position:absolute;bottom:-30px;left:-30px;width:100px;height:100px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
                            
                            <div style="position:relative;z-index:2;">
                                <div style="text-align:center;margin-bottom:20px;">
                                    <span class="material-icons" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.9;">lock_open</span>
                                    <h3 style="margin:0 0 10px 0;font-size:24px;font-weight:600;">Rejoignez l'aventure EcoRide !</h3>
                                    <p style="margin:0;opacity:0.9;font-size:16px;">Connectez-vous pour participer à ce covoiturage et découvrir tous nos services</p>
                                </div>
                                
                                <div style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;">
                                    <a href="login_secure.php" class="login-cta-btn" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);color:white;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:500;transition:all 0.3s ease;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.3);">
                                        <span class="material-icons">login</span>
                                        Se connecter
                                    </a>
                                    <a href="/pages/register.php" class="register-cta-btn" style="display:inline-flex;align-items:center;gap:8px;background:white;color:#00b894;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:500;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                                        <span class="material-icons">person_add</span>
                                        Créer un compte
                                    </a>
                                </div>
                                
                                <div style="text-align:center;margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.2);">
                                    <p style="margin:0;font-size:14px;opacity:0.8;">
                                        <span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:5px;">info</span>
                                        L'inscription est gratuite et ne prend que 2 minutes
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="text-align:center;margin-top:20px;">
                    <div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;">
                        <a href="covoiturages.php" style="color:#00b894;text-decoration:none;font-weight:500;">
                            ← Retour aux covoiturages
                        </a>
                        <a href="index.php" style="color:#636e72;text-decoration:none;">
                            🏠 Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal de confirmation -->
    <dialog id="confirm-modal" class="modal-legal-dialog" style="border:none;padding:0;background:rgba(34,49,63,0.95);backdrop-filter:blur(4px);border-radius:18px;box-shadow:0 8px 40px rgba(0,0,0,0.25);">
        <form method="dialog" class="modal-legal-content" style="background:transparent;box-shadow:none;padding:0;">
            <div style="background:white;border-radius:18px;padding:40px 30px 30px 30px;max-width:350px;min-width:280px;position:relative;box-shadow:0 4px 24px rgba(0,184,148,0.10);">
                <div style="display:flex;justify-content:center;align-items:center;margin-bottom:18px;">
                    <span class="material-icons" id="modal-anim-icon" style="font-size:48px;color:#00b894;transition:transform 0.3s;">check_circle</span>
                </div>
                <h3 style="text-align:center;margin:0 0 18px 0;color:#2d3436;font-size:1.3em;">Confirmer votre participation</h3>
                <div style="margin-bottom:18px;text-align:center;">
                    <p style="margin:0 0 6px 0;"><strong>Trajet :</strong> <?= htmlspecialchars($covoiturage['ville_depart']) ?> → <?= htmlspecialchars($covoiturage['ville_arrivee']) ?></p>
                    <p style="margin:0 0 6px 0;"><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($covoiturage['date_depart'])) ?></p>
                    <p style="margin:0 0 6px 0;"><strong>Prix :</strong> <?= number_format($covoiturage['prix'], 2) ?>€</p>
                    <p style="margin:0;"><strong>Crédits à utiliser :</strong> <?= $credit_requis ?></p>
                </div>
                <p style="color:#e74c3c;font-weight:bold;text-align:center;">Êtes-vous sûr de vouloir participer à ce covoiturage ?</p>
                <div style="display:flex;gap:12px;justify-content:center;margin-top:22px;">
                    <button type="button" id="cancel-btn" class="btn-secondary" style="padding:12px 24px;border-radius:8px;font-weight:600;">Annuler</button>
                    <button type="button" id="confirm-btn" class="btn-primary" style="padding:12px 24px;border-radius:8px;font-weight:600;background:#00b894;">Confirmer</button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- Modal de résultat participation -->
    <dialog id="result-modal" style="border:none;padding:0;background:rgba(34,49,63,0.95);backdrop-filter:blur(4px);border-radius:18px;box-shadow:0 8px 40px rgba(0,0,0,0.25);">
        <div style="background:white;border-radius:18px;padding:40px 30px 30px 30px;max-width:350px;min-width:280px;position:relative;box-shadow:0 4px 24px rgba(0,184,148,0.10);text-align:center;">
            <span class="material-icons" id="result-modal-icon" style="font-size:48px;color:#00b894;margin-bottom:10px;">check_circle</span>
            <h3 id="result-modal-title" style="margin:0 0 12px 0;color:#2d3436;font-size:1.2em;">Participation confirmée !</h3>
            <p id="result-modal-msg" style="color:#636e72;margin-bottom:22px;">Votre participation a bien été prise en compte.</p>
            <button type="button" id="result-modal-close" class="btn-primary" style="padding:12px 24px;border-radius:8px;font-weight:600;">Fermer</button>
        </div>
    </dialog>

    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation au hover pour les boutons de connexion
            const loginBtn = document.querySelector('.login-cta-btn');
            const registerBtn = document.querySelector('.register-cta-btn');
            
            if (loginBtn) {
                loginBtn.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(255,255,255,0.3)';
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.2)';
                });
                
                loginBtn.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(255,255,255,0.2)';
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            }
            
            if (registerBtn) {
                registerBtn.addEventListener('mouseenter', function() {
                    this.style.background = '#f8f9fa';
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,184,148,0.3)';
                });
                
                registerBtn.addEventListener('mouseleave', function() {
                    this.style.background = 'white';
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                });
            }

            // Animation d'apparition progressive
            const loginInvitation = document.querySelector('.login-invitation');
            if (loginInvitation) {
                loginInvitation.style.opacity = '0';
                loginInvitation.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    loginInvitation.style.transition = 'all 0.6s ease';
                    loginInvitation.style.opacity = '1';
                    loginInvitation.style.transform = 'translateY(0)';
                }, 300);
            }

            // Nouvelle modale de confirmation esthétique
            const participateBtn = document.getElementById('participate-btn');
            const confirmModal = document.getElementById('confirm-modal');
            const cancelBtn = document.getElementById('cancel-btn');
            const confirmBtn = document.getElementById('confirm-btn');
            const animIcon = document.getElementById('modal-anim-icon');

            const resultModal = document.getElementById('result-modal');
            const resultModalIcon = document.getElementById('result-modal-icon');
            const resultModalTitle = document.getElementById('result-modal-title');
            const resultModalMsg = document.getElementById('result-modal-msg');
            const resultModalClose = document.getElementById('result-modal-close');

            if (participateBtn) {
                participateBtn.addEventListener('click', function() {
                    if (confirmModal) confirmModal.showModal();
                    if (animIcon) {
                        animIcon.style.transform = 'scale(1.2)';
                        setTimeout(() => animIcon.style.transform = 'scale(1)', 250);
                    }
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    if (confirmModal) confirmModal.close();
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    confirmBtn.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('trip_id', '<?= (int)$covoiturage['id'] ?>');
                    formData.append('credits', '<?= (int)$credit_requis ?>');
                    formData.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token']) ?>');
                    
                    fetch('participate.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            confirmModal.close();
                            showResultModal(true, "Participation confirmée !", "Votre participation a bien été prise en compte. Nouveau solde : " + data.new_credits + " crédits");
                        } else {
                            confirmModal.close();
                            showResultModal(false, "Erreur", data.message || "Erreur lors de la participation.");
                        }
                    })
                    .catch(() => {
                        confirmModal.close();
                        showResultModal(false, "Erreur technique", "Erreur technique, réessayez plus tard.");
                    })
                    .finally(() => {
                        confirmBtn.disabled = false;
                    });
                });
            }

            function showResultModal(success, title, msg) {
                if (!resultModal) return;
                resultModalIcon.textContent = success ? "check_circle" : "error";
                resultModalIcon.style.color = success ? "#00b894" : "#e74c3c";
                resultModalTitle.textContent = title;
                resultModalMsg.textContent = msg;
                resultModal.showModal();
            }

            if (resultModalClose) {
                resultModalClose.addEventListener('click', function() {
                    resultModal.close();
                    if (resultModalTitle.textContent === "Participation confirmée !") {
                        window.location.reload();
                    }
                });
            }
        });
    </script>
</body>
</html>




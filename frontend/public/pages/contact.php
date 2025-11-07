<?php
session_start();

// Charger l’autoloader et importer Database
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

$success = '';
$error   = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom']     ?? '');
    $email   = trim($_POST['email']   ?? '');
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } elseif (strlen($nom) < 2) {
        $error = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (strlen($message) < 10) {
        $error = 'Le message doit contenir au moins 10 caractères.';
    } else {
        // Succès
        $success = 'Votre message a été envoyé avec succès ! Nous vous recontacterons bientôt.';
        
        // Enregistrer dans les logs
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $userId = $_SESSION['user']['id'] ?? null;
            $details = "De: $email, Sujet: $sujet";
            $stmt->execute([$userId, 'Contact formulaire', $details, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            // Silencieux en cas d'erreur de log
        }
        
        // Vider le formulaire
        $_POST = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <div style="max-width:800px;margin:40px auto;padding:0 20px;">
            <div style="background:white;padding:40px;border-radius:12px;
                        box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                <h2 style="text-align:center;color:#2d3436;margin-bottom:30px;">
                    <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">
                        contact_mail
                    </span>
                    Contactez-nous
                </h2>
                
                <div style="text-align:center;margin-bottom:40px;color:#636e72;">
                    <p>Une question ? Une suggestion ? N'hésitez pas à nous écrire !</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="message-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" style="display:grid;gap:20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label for="nom" style="display:block;margin-bottom:5px;
                                  font-weight:600;color:#2d3436;">Nom complet *</label>
                            <input type="text" id="nom" name="nom" required
                                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                                   style="width:100%;padding:12px;border:2px solid #ddd;
                                          border-radius:8px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label for="email" style="display:block;margin-bottom:5px;
                                  font-weight:600;color:#2d3436;">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   style="width:100%;padding:12px;border:2px solid #ddd;
                                          border-radius:8px;box-sizing:border-box;">
                        </div>
                    </div>
                    
                    <div>
                        <label for="sujet" style="display:block;margin-bottom:5px;
                              font-weight:600;color:#2d3436;">Sujet *</label>
                        <select id="sujet" name="sujet" required
                                style="width:100%;padding:12px;border:2px solid #ddd;
                                       border-radius:8px;box-sizing:border-box;">
                            <option value="">Choisissez un sujet</option>
                            <?php
                            $options = [
                              'Question générale', 'Problème technique',
                              'Signalement', 'Suggestion', 'Autre'
                            ];
                            foreach ($options as $opt): ?>
                                <option value="<?= $opt ?>"
                                  <?= ($_POST['sujet'] ?? '') === $opt ? 'selected' : '' ?>>
                                  <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" style="display:block;margin-bottom:5px;
                              font-weight:600;color:#2d3436;">Message *</label>
                        <textarea id="message" name="message" rows="8" required
                                  placeholder="Décrivez votre demande en détail..."
                                  style="width:100%;padding:12px;border:2px solid #ddd;
                                         border-radius:8px;box-sizing:border-box;
                                         resize:vertical;">
<?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <div style="text-align:center;">
                        <button type="submit" class="btn-primary"
                                style="padding:15px 40px;font-size:18px;">
                            <span class="material-icons">send</span>
                            Envoyer le message
                        </button>
                    </div>
                </form>
                
                <div style="margin-top:40px;padding-top:30px;border-top:1px solid #ddd;">
                    <h3 style="color:#2d3436;text-align:center;margin-bottom:20px;">
                      Autres moyens de contact
                    </h3>
                    <div style="display:grid;grid-template-columns:
                                repeat(auto-fit,minmax(200px,1fr));gap:20px;text-align:center;">
                        <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                            <span class="material-icons" style="font-size:32px;color:#00b894;">
                                email
                            </span>
                            <h4 style="margin:0 0 5px 0;color:#2d3436;">Email direct</h4>
                            <p style="margin:0;color:#636e72;">contact@ecoride.fr</p>
                        </div>
                        <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                            <span class="material-icons" style="font-size:32px;color:#00b894;">
                                schedule
                            </span>
                            <h4 style="margin:0 0 5px 0;color:#2d3436;">Délai de réponse</h4>
                            <p style="margin:0;color:#636e72;">Sous 24 h ouvrées</p>
                        </div>
                    </div>
                </div>
                
                <div style="text-align:center;margin-top:30px;">
                    <a href="index.php" style="color:#00b894;text-decoration:none;font-weight:600;">
                        ← Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EcoRide – Tous droits réservés</p>
        <div>
            <a href="#" id="openModalLegal">Mentions légales</a>
        </div>
    </footer>

    <!-- Modal Mentions légales -->
    <dialog id="modal-legal" class="modal-legal-dialog">
        <form method="dialog" class="modal-legal-content">
            <button id="closeModalLegal" class="modal-legal-close" type="button" aria-label="Fermer">×</button>
            <h2>Mentions légales</h2>
            <div class="modal-legal-body">
                <p>
                  <strong>Nom de l'entreprise</strong> : EcoRide<br>
                  <strong>Statut</strong> : Société fictive (projet étudiant)<br>
                  <strong>Adresse</strong> : 123 rue de la Planète Verte, 75000 Paris<br>
                  <strong>SIREN</strong> : 000 000 000<br>
                  <strong>Responsable de publication</strong> : Jules Fictif<br>
                  <strong>Email</strong> : contact@ecoride.fr<br>
                  <strong>Hébergeur</strong> : OVH, 2 rue Kellermann, 59100 Roubaix, France<br>
                </p>
                <p>
                  Ce site est développé dans le cadre d’un projet étudiant et n’a pas vocation commerciale.
                </p>
            </div>
        </form>
    </dialog>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        // Rendu du menu avec navbar.js
        if (typeof renderMenu === 'function') {
            renderMenu(window.ecorideUser);
        }
    </script>
</body>
</html>

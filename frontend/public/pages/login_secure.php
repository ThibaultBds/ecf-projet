<?php
session_start();

// Charger l’autoloader et la DB
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

$error = '';

// Si déjà connecté, rediriger
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    header('Location: profil.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // normalisation douce
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = getDatabase();

            // Récup explicite des colonnes utiles (inclut le rôle)
            $stmt = $pdo->prepare("
                SELECT id, email, password, pseudo, role, credits, status
                FROM users
                WHERE email = ? AND status = 'actif'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            var_dump($user);
exit;
var_dump(password_verify("test1234", $user['password']));
exit;


            if ($user && password_verify($password, $user['password'])) {
                // Sécurité anti-fixation
                session_regenerate_id(true);

                // Stocke le rôle (et alias 'type' pour compat front existant)
                $_SESSION['user'] = [
                    'id'      => (int)$user['id'],
                    'email'   => $user['email'],
                    'pseudo'  => $user['pseudo'],
                    'role'    => $user['role'],      // 'Utilisateur' | 'Moderateur' | 'Administrateur'
                    'type'    => $user['role'],      // <-- compat avec ton JS existant
                    'credits' => (int)$user['credits']
                ];

                header('Location: profil.php');
                exit();
            } else {
                // Par défaut, on ne précise pas lequel est faux
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Throwable $e) {
            // Pas d’info sensible qui fuite côté client
            $error = 'Erreur technique. Veuillez réessayer.';
            // (optionnel) log serveur :
            // error_log('[LOGIN] '.$e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
    <h1>
        <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span> EcoRide
        </a>
    </h1>
</header>

<script>
/* Expose utilisateur au front si connecté (compat: role + type) */
window.ecorideUser = <?php
    if (isset($_SESSION['user'])) {
        echo json_encode([
            'email' => $_SESSION['user']['email'],
            'pseudo'=> $_SESSION['user']['pseudo'],
            'role'  => $_SESSION['user']['role'], // nouveau champ clair
            'type'  => $_SESSION['user']['type'], // compat existante
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'null';
    }
?>;
</script>

<main>
    <div class="login-container">
        <h2 class="title-login">Connexion</h2>

        <?php if (!empty($error)): ?>
            <div class="message-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" class="form-connexion" novalidate>
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="votre@email.com"
            >

            <label for="password">Mot de passe</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Votre mot de passe"
            >

            <button type="submit">Se connecter</button>
        </form>

        <div class="login-links">
            <a href="register.php" class="forgot-link">Créer un compte</a>
            <span class="sep">|</span>
            <a href="index.php" class="forgot-link">Retour à l'accueil</a>
        </div>
    </div>
</main>

<script src="../assets/js/navbar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ton navbar.js peut lire role ou type (compat)
    if (window.ecorideUser) {
        renderMenu(window.ecorideUser);
    } else {
        renderMenu();
    }
});
</script>
</body>
</html>
<?php
<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

$error = '';

if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    header('Location: profil.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = getDatabase();

            $stmt = $pdo->prepare("
                SELECT id, email, password, pseudo, role, credits, status
                FROM users
                WHERE email = ? AND status = 'actif'
                LIMIT 1
            ");
            $stmt = $pdo->prepare("
    SELECT id, email, password, pseudo, role, credits, status
    FROM users
    WHERE email = ? AND status = 'actif'
    LIMIT 1
");
            $stmt->execute([$email]);

           // ➡️ Ici on récupère l'utilisateur
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// DEBUG : pour savoir ce qui se passe
error_log('LOGIN DEBUG - Email saisi: '.$email);
if ($user) {
    error_log('LOGIN DEBUG - Utilisateur trouvé: id='.$user['id'].' / email='.$user['email']);
    error_log('LOGIN DEBUG - Hash en DB: '.$user['password']);
    $check = password_verify($password, $user['password']);
    error_log('LOGIN DEBUG - Vérification du mot de passe: '.($check ? 'OK' : 'FAIL'));
} else {
    error_log('LOGIN DEBUG - Aucun utilisateur trouvé avec cet email');
}

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'      => (int)$user['id'],
        'email'   => $user['email'],
        'pseudo'  => $user['pseudo'],
        'role'    => $user['role'],
        'type'    => $user['role'],   // compat front
        'credits' => (int)$user['credits']
    ];
    header('Location: profil.php');
    exit();
} else {
    $error = 'Email ou mot de passe incorrect.';
}
        } catch (Throwable $e) {
            // LOG dans les erreurs serveur (XAMPP/Heroku)
            error_log('[LOGIN][ERR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            // Optionnel: log utile si ça vient de la DB
            // error_log('[LOGIN][TRACE] '.$e->getTraceAsString());

            // Message générique pour l'utilisateur
            $error = 'Erreur technique. Veuillez réessayer.';
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
        window.ecorideUser = <?php
                                if (isset($_SESSION['user'])) {
                                    echo json_encode([
                                        'email'  => $_SESSION['user']['email'],
                                        'pseudo' => $_SESSION['user']['pseudo'],
                                        'role'   => $_SESSION['user']['role'],
                                        'type'   => $_SESSION['user']['type'],
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
                <input type="email" id="email" name="email" required autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="votre@email.com">

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    placeholder="Votre mot de passe">

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
            if (window.ecorideUser) {
                renderMenu(window.ecorideUser);
            } else {
                renderMenu();
            }
        });
    </script>
</body>

</html>
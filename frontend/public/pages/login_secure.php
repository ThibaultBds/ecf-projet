<?php
session_start();

// Charger l’autoloader et importer Database
require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

$error = '';

// Si déjà connecté, rediriger
if (isset($_SESSION['user'])) {
    header('Location: profil.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo  = getDatabase();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'actif'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id'      => $user['id'],
                    'email'   => $user['email'],
                    'pseudo'  => $user['pseudo'],
                    'type'    => $user['role'],
                    'credits' => $user['credits']
                ];
                header('Location: profil.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
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
    window.ecorideUser = <?php echo isset($_SESSION['user']) ? json_encode([
        'email' => $_SESSION['user']['email'],
        'pseudo' => $_SESSION['user']['pseudo'],
        'type' => $_SESSION['user']['type']
    ]) : 'null'; ?>;
    </script>

    <main>
        <div class="login-container">
            <h2 class="title-login">Connexion</h2>
            
            <?php if ($error): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="form-connexion">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="votre@email.com">

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
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

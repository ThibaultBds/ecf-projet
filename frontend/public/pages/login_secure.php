<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

$error = '';

// Déjà connecté → profil
if (!empty($_SESSION['user']['id'])) {
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

            // Récupère l'utilisateur actif par email
            $stmt = $pdo->prepare("
                SELECT id, email, password, pseudo, role, credits, status
                FROM users
                WHERE email = ? AND status = 'actif'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Normaliser rôle
                $roleRaw  = strtolower(trim($user['role'] ?? ''));
                if ($roleRaw === 'administrateur') $roleRaw = 'admin';
                if ($roleRaw === 'modérateur' || $roleRaw === 'moderateur') $roleRaw = 'moderateur';
                if ($roleRaw === '' || $roleRaw === 'utilisateur') $roleRaw = 'utilisateur';

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'      => (int)$user['id'],
                    'email'   => $user['email'],
                    'pseudo'  => $user['pseudo'],
                    'role'    => $user['role'], // valeur brute DB
                    'type'    => $roleRaw,      // valeur normalisée pour la logique
                    'credits' => (int)$user['credits'],
                ];

                header('Location: profil.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Throwable $e) {
            error_log('[LOGIN][ERR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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
</body>
</html>

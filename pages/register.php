<?php
session_start();
require_once '../../../backend/config/autoload.php';
useClass('Database');

// --- Exceptions personnalisées ---
class RegistrationException extends Exception {}
class EmailInvalidException extends RegistrationException {}
class PasswordTooShortException extends RegistrationException {}
class PasswordMismatchException extends RegistrationException {}
class DuplicateUserException extends RegistrationException {}
class MissingFieldException extends RegistrationException {}

// --- Initialisation des messages ---
$error = '';
$success = '';

// --- Redirection si déjà connecté ---
if (isset($_SESSION['user'])) {
    header('Location: profil.php');
    exit();
}

// --- Traitement du formulaire ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // --- Récupération et nettoyage des champs ---
        $pseudo = trim($_POST['pseudo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // --- Validation des champs obligatoires ---
        if (empty($pseudo) || empty($email) || empty($password)) {
            throw new MissingFieldException("Tous les champs sont obligatoires.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new EmailInvalidException("L'adresse email est invalide.");
        }
        if (strlen($password) < 8) {
            throw new PasswordTooShortException("Le mot de passe doit contenir au moins 8 caractères.");
        }
        if ($password !== $password_confirm) {
            throw new PasswordMismatchException("Les mots de passe ne correspondent pas.");
        }

        // --- Connexion BDD ---
        $pdo = getDatabase();

        // --- Vérification unicité email/pseudo ---
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR pseudo = ?");
        $stmt->execute([$email, $pseudo]);
        if ($stmt->fetch()) {
            throw new DuplicateUserException("Cet email ou pseudo est déjà utilisé.");
        }

        // --- Insertion utilisateur ---
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $credits_initial = 20; // 20 crédits offerts

        $stmt = $pdo->prepare("INSERT INTO users (pseudo, email, password, credits, role, status) VALUES (?, ?, ?, ?, 'Utilisateur', 'actif')");
        $stmt->execute([$pseudo, $email, $password_hash, $credits_initial]);

        $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        $_POST = [];

    } catch (RegistrationException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        // Erreur inattendue
        $error = "Une erreur est survenue, veuillez réessayer.";
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - EcoRide</title>
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
            <form method="POST" class="login-form" autocomplete="off">
                <h2>Créer un compte</h2>
                <p>Rejoignez la communauté et commencez à covoiturer !</p>

                <?php if ($error): ?>
                    <div class="message-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="message-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" required value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="password">Mot de passe (8 caractères min.)</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit" class="btn-primary">S'inscrire</button>
                <div class="login-footer">
                    <p>Déjà un compte ? <a href="login_secure.php">Connectez-vous</a></p>
                </div>
            </form>
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
</main>
<script src="../assets/js/navbar.js"></script>
</body>
</html>



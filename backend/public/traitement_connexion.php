<?php
$Utilisateurs = [
    ["email" => "test@email.com", "mdp" => "azerty"],
    ["email" => "alice@email.com", "mdp" => "motdepasse"],
    ["email" => "bob@email.com", "mdp" => "123456"],
];

$email = $_POST['email'] ?? '';
$mdp = $_POST['mdp'] ?? '';

$trouve = false;
foreach ($Utilisateurs as $user) {
    if ($email === $user['email'] && $mdp === $user['mdp']) {
        $trouve = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Résultat de la connexion</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="result-container">
  <h2>Résultat de la connexion</h2>
  <?php if ($trouve): ?>
    <p class="message-success">Connexion réussie !</p>
  <?php else: ?>
    <p class="message-error">Identifiants incorrects.</p>
  <?php endif; ?>
  <pre><?php print_r($_POST); ?></pre>
</div>
</body>
</html>




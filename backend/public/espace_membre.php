<?php
session_start();
require_once '../config/autoload.php';
useClass('Database');

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Espace membre</title>
  <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
</head>
<body>
<header class="container-header">
  <!-- ...existing code for header/nav... -->
</header>
<div class="member-container">
  <h2>Bienvenue dans votre espace membre</h2>
  <p>Bonjour <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong> !</p>
  <a href="/frontend/public/pages/logout.php" class="logout-link">Se déconnecter</a>
  <!-- Ajoute ici tout contenu réservé aux membres -->
</div>
</body>
</html>




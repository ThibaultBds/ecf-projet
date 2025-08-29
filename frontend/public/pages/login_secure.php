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

            // 🔹 DEBUG pour vérifier le login
            var_dump("Mot de passe tapé :", $password);
            var_dump("Hash en base :", $user['password'] ?? null);
            if ($user) {
                var_dump("Résultat password_verify :", password_verify($password, $user['password']));
            } else {
                var_dump("Aucun utilisateur trouvé pour cet email.");
            }
            exit;
            // 🔹 Fin debug

            if ($user && password_verify($password, $user['password'])) {
                // Sécurité anti-fixation
                session_regenerate_id(true);

                // Stocke le rôle (et alias 'type' pour compat front existant)
                $_SESSION['user'] = [
                    'id'      => (int)$user['id'],
                    'email'   => $user['email'],
                    'pseudo'  => $user['pseudo'],
                    'role'    => $user['role'],      // 'Utilisateur' | 'Moderateur' | 'Administrateur'
                    'type'    => $user['role'],      // compat avec ton JS existant
                    'credits' => (int)$user['credits']
                ];

                header('Location: profil.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Throwable $e) {
            $error = 'Erreur technique. Veuillez réessayer.';
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
            <span class="m

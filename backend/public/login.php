<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - EcoRide</title>
    <link rel="stylesheet" href="/Ecoridegit/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1>
            <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
        <!-- Le menu sera injecté par navbar.js -->
    </header>
    <div class="login-container">
        <h2 class="title-login">Connexion</h2>
        
        <?php if (!empty($error)): ?>
            <div class="message-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="form-connexion" method="post" autocomplete="on">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="username" placeholder="ex : user@ecoride.fr">
            
            <label for="password">Mot de passe</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
                <span id="togglePwd" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#00b894;" title="Afficher/Masquer le mot de passe">
                    <span class="material-icons" id="eyePwd">visibility_off</span>
                </span>
            </div>
            
            <button type="submit">Se connecter</button>
        </form>
        
        <div style="margin-top:18px;font-size:0.98em;background:#eaf6fd;border-radius:8px;padding:10px 14px;">
            <b>Identifiants de test :</b><br>
            <span style="color:#00b894;">Utilisateur</span> : user@ecoride.fr / test123<br>
            <span style="color:#218c5a;">Modérateur</span> : modo@ecoride.fr / modo123<br>
            <span style="color:#b8002e;">Administrateur</span> : admin@ecoride.fr / admin123
        </div>
    </div>
    
    <script>
        document.getElementById('togglePwd').onclick = function() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eyePwd');
            if (pwd.type === "password") {
                pwd.type = "text";
                eye.textContent = "visibility";
            } else {
                pwd.type = "password";
                eye.textContent = "visibility_off";
            }
        };
    </script>
    <script src="/Ecoridegit/frontend/public/assets/js/navbar.js"></script>
</body>
</html>



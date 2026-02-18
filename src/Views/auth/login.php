<div class="login-container">
    <h2 class="title-login">Connexion</h2>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="message-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login" class="form-connexion" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($email ?? '') ?>"
               placeholder="votre@email.com">

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required
               placeholder="Votre mot de passe">

        <button type="submit" class="btn-primary">Se connecter</button>
    </form>

    <div class="login-links">
        <a href="/register" class="forgot-link">Créer un compte</a>
        <span class="sep">|</span>
        <a href="/" class="forgot-link">Retour à l'accueil</a>
    </div>
</div>

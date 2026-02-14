<div class="login-container">
    <form method="POST" action="/register" class="login-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <h2>Créer un compte</h2>
        <p>Rejoignez la communauté et commencez à covoiturer !</p>

        <?php if (!empty($error)): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="input-group">
            <label for="username">Pseudo</label>
            <input type="text" id="username" name="username" required
                   value="<?= htmlspecialchars($old['username'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>">
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
            <p>Déjà un compte ? <a href="/login">Connectez-vous</a></p>
        </div>
    </form>
</div>

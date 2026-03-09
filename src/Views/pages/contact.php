<main>
    <div class="contact-page-wrap">
        <div class="contact-page-card">
            <h2 class="contact-page-title">
                <span class="material-icons contact-title-icon">contact_mail</span>
                Contactez-nous
            </h2>

            <div class="contact-page-intro">
                <p>Une question ? Une suggestion ? N'hésitez pas à nous écrire !</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="message-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/contact" class="contact-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="contact-form-grid-two">
                    <div>
                        <label for="nom" class="contact-label">Nom complet *</label>
                        <input type="text" id="nom" name="nom" required
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                               class="contact-input">
                    </div>
                    <div>
                        <label for="email" class="contact-label">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               class="contact-input">
                    </div>
                </div>

                <div>
                    <label for="sujet" class="contact-label">Sujet *</label>
                    <select id="sujet" name="sujet" required class="contact-input">
                        <option value="">Choisissez un sujet</option>
                        <?php
                        $options = ['Question generale', 'Probleme technique', 'Signalement', 'Suggestion', 'Autre'];
                        foreach ($options as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($old['sujet'] ?? '') === $opt ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="message" class="contact-label">Message *</label>
                    <textarea id="message" name="message" rows="8" required
                              placeholder="Décrivez votre demande en détail..."
                              class="contact-textarea"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                </div>

                <div class="contact-submit-wrap">
                    <button type="submit" class="btn-primary contact-submit-btn">
                        <span class="material-icons contact-submit-icon">send</span>
                        Envoyer le message
                    </button>
                </div>
            </form>

            <div class="contact-alt-wrap">
                <h3 class="contact-alt-title">Autres moyens de contact</h3>
                <div class="contact-alt-grid">
                    <div class="contact-alt-card">
                        <span class="material-icons contact-alt-card-icon">email</span>
                        <h4 class="contact-alt-card-title">Email direct</h4>
                        <p class="contact-alt-card-text">contact@ecoride.fr</p>
                    </div>
                    <div class="contact-alt-card">
                        <span class="material-icons contact-alt-card-icon">schedule</span>
                        <h4 class="contact-alt-card-title">Délai de réponse</h4>
                        <p class="contact-alt-card-text">Sous 24 h ouvrées</p>
                    </div>
                </div>
            </div>

            <div class="contact-back-wrap">
                <a href="/" class="contact-back-link">
                    &larr; Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</main>

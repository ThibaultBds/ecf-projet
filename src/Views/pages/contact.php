<main>
    <div style="max-width:800px;margin:40px auto;padding:0 20px;">
        <div style="background:white;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <h2 style="text-align:center;color:#2d3436;margin-bottom:30px;">
                <span class="material-icons" style="vertical-align:middle;color:#00b894;margin-right:10px;">contact_mail</span>
                Contactez-nous
            </h2>

            <div style="text-align:center;margin-bottom:40px;color:#636e72;">
                <p>Une question ? Une suggestion ? N'hesitez pas a nous ecrire !</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="message-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/contact" style="display:grid;gap:20px;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <label for="nom" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Nom complet *</label>
                        <input type="text" id="nom" name="nom" required
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                               style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label for="email" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
                    </div>
                </div>

                <div>
                    <label for="sujet" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Sujet *</label>
                    <select id="sujet" name="sujet" required
                            style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;">
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
                    <label for="message" style="display:block;margin-bottom:5px;font-weight:600;color:#2d3436;">Message *</label>
                    <textarea id="message" name="message" rows="8" required
                              placeholder="Decrivez votre demande en detail..."
                              style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;box-sizing:border-box;resize:vertical;"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                </div>

                <div style="text-align:center;">
                    <button type="submit" class="btn-primary" style="padding:15px 40px;font-size:18px;">
                        <span class="material-icons" style="vertical-align:middle;">send</span>
                        Envoyer le message
                    </button>
                </div>
            </form>

            <div style="margin-top:40px;padding-top:30px;border-top:1px solid #ddd;">
                <h3 style="color:#2d3436;text-align:center;margin-bottom:20px;">Autres moyens de contact</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;text-align:center;">
                    <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                        <span class="material-icons" style="font-size:32px;color:#00b894;">email</span>
                        <h4 style="margin:0 0 5px 0;color:#2d3436;">Email direct</h4>
                        <p style="margin:0;color:#636e72;">contact@ecoride.fr</p>
                    </div>
                    <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                        <span class="material-icons" style="font-size:32px;color:#00b894;">schedule</span>
                        <h4 style="margin:0 0 5px 0;color:#2d3436;">Delai de reponse</h4>
                        <p style="margin:0;color:#636e72;">Sous 24 h ouvrees</p>
                    </div>
                </div>
            </div>

            <div style="text-align:center;margin-top:30px;">
                <a href="/" style="color:#00b894;text-decoration:none;font-weight:600;">
                    &larr; Retour a l'accueil
                </a>
            </div>
        </div>
    </div>
</main>

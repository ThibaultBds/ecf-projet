# EcoRide — Rapport projet

Date de génération: 2025-11-07

Ce document est une synthèse structurée du projet EcoRide extraite directement de l'arborescence du dépôt local (c:\xampp\htdocs\ecoride). Il reprend le sommaire fourni et documente la configuration, l'architecture, les principaux fichiers, les étapes d'installation et les annexes.

## Métadonnées
- Référence: DPROJET2emedept-TPDWWMGDW23295_378936_20250822145608
- Date du dépôt (fourni): 13/09/2025

## Résumé du projet
EcoRide est une plateforme de covoiturage électrique destinée aux utilisateurs soucieux de l'environnement. Le site est développé avec HTML/CSS/JS/PHP et une base MySQL. Il propose publication et recherche de trajets, gestion d'utilisateurs, système d'avis et administration.

## Stack technique détectée
- Langages : PHP 8, HTML5, CSS3, JavaScript
- Web : Apache (via XAMPP), PHP-FPM compatible
- Base de données : MySQL (fichiers SQL présents dans `backup/` et `docker/mysql-init/`)
- Outils / dépendances : Composer (fichiers `composer.json` / `composer.lock` présents), librairies JS/CSS locales

Fichiers repérés utiles :
- `composer.json` (racine)
- `backup/backup-ecoride.sql` et `docker/mysql-init/ecoride.sql` (dump SQL)

## Structure du projet (repères)
- frontend/public/: frontend public accessible (pages, assets, includes)
- frontend/public/assets/css/style.css : feuille de styles principale (modifiée pour harmoniser header/.hero)
- frontend/public/includes/header.php, footer.php, footer-scripts.php
- frontend/public/pages/ : pages utilisateur (index.php, covoiturages.php, details.php, login_secure.php, profil.php, etc.)
- backend/public/: pages backend / panneau chauffeur / admin
- backend/public/includes/header.php, footer.php, footer-scripts.php
- backend/public/espace_chauffeur_new.php : page chauffeur avec création de trajet
- backup/, docker/, docs/ : dumps et documentation

## Modifications récentes importantes (appliquées)
- Centralisation du footer : création de `frontend/public/includes/footer.php` et `backend/public/includes/footer.php`, et inclusion depuis les pages.
- Harmonisation header / .hero : centralisation du gradient via variables CSS (`--primary-start`, `--primary-end`) dans `frontend/public/assets/css/style.css`, suppression des styles inline dans `frontend/public/includes/header.php` et `backend/public/includes/header.php`.
- Ajout d'un petit script JS d'ajustement d'offset du header dans `footer-scripts.php` pour éviter le chevauchement (observer les mutations du header et recalculer padding-top sur `main`).
- Rendu visuel : suppression ou atténuation d'ombres et arrondis sur `.hero` et header pour une continuité visuelle.

## Installation locale (instructions rapides)
Prérequis : XAMPP (Apache + MySQL), PHP 8, Composer (optionnel), Git.

1. Placez le dossier `ecoride` dans le répertoire de votre hôte local (ex : `c:\xampp\htdocs\ecoride`).
2. Démarrez Apache et MySQL via le panneau XAMPP.
3. Importez la base de données :

   - phpMyAdmin : importez `backup/backup-ecoride.sql` (ou `docker/mysql-init/ecoride.sql`) dans une base nommée `ecoride`.

   - Commande MySQL (PowerShell) :

     mysql -u root -p ecoride < "c:/xampp/htdocs/ecoride/backup/backup-ecoride.sql"

4. Configurez la connexion DB si besoin :

   - Vérifiez `backend/config/database.php` et `backend/config/autoload.php` pour l'utilisation de variables d'environnement ou de constantes. Adaptez l'hôte, l'utilisateur et le mot de passe si nécessaire.

5. (Option) Installer les dépendances PHP :

   - Depuis la racine du projet : `composer install` (si un composer.json est utilisé).

6. Ouvrez le site local : http://localhost/ecoride/frontend/public/ (ou configurez un VirtualHost Apache pointant vers `frontend/public`).

## Scripts / actions utiles
- Lancer un reset / import SQL : utiliser phpMyAdmin ou la commande mysql ci-dessus.
- Vider cache CSS / JS : faire un hard refresh dans le navigateur (Ctrl+F5) après modifications de `style.css`.

## Schéma de la base (repères)
- Le dépôt contient des dumps SQL (`backup/backup-ecoride.sql`) — importer et examiner les tables : `users`, `roles`, `trips` (ou `trajets`), `trip_participants`, `activity_logs`, `activity_logs`, `avis` / `reviews`.
- Index recommandés : `trips(depart_ville, arrivee_ville, date_depart)` ; contraintes `ON DELETE` sur fk comme décrit dans le cahier des charges.

## Extraits de code importants
- `backend/public/espace_chauffeur_new.php` : logique de création de trajet, validations (prix, places, date), mise à jour crédits utilisateur et journalisation.
- `frontend/public/assets/js/navbar.js` : injection du menu côté client et toggling des liens selon `window.ecorideUser`.
- `frontend/public/assets/css/style.css` : centralisation des variables CSS et harmonisation header/.hero.

## Sécurité et bonnes pratiques (observations)
- Utilisation de PDO pour les accès SQL (préparés) — vérifier `backend/config/database.php`.
- Échappement côté affichage : recherche d'usage de `htmlspecialchars` avant affichage des valeurs utilisateurs (présent dans plusieurs pages modifiées).
- Sessions : le projet utilise `session_start()` ; vérifier les protections (cookie flags, durée, regeneration id).
- CSRF : rechercher tokens dans les formulaires (si absent, prévoir ajout de protections CSRF).

## Fichiers modifiés / touches récentes
- `frontend/public/assets/css/style.css` — harmonisation gradient, suppression d'ombres, ajouts de variables.
- `frontend/public/includes/header.php` — suppression du style inline; header utilise désormais CSS centralisé.
- `backend/public/includes/header.php` — idem.
- `frontend/public/includes/footer.php`, `backend/public/includes/footer.php` — créations.
- `frontend/public/includes/footer-scripts.php`, `backend/public/includes/footer-scripts.php` — ajouts de scripts d'offset et injection `window.ecorideUser`.

## Annexes / preuves
- Fichiers SQL : `backup/backup-ecoride.sql`, `docker/mysql-init/ecoride.sql`.
- Maquettes / docs : `docs/technical_doc.md`, `docs/project_management.md`.
- Répertoire `frontend/public/pages/` contient les pages HTML/PHP à inclure dans l'annexe.

## Comment générer un PDF (optionnel)
- Si vous voulez un PDF imprimable, installez `pandoc` + `wkhtmltopdf` ou utilisez un export PDF depuis VSCode :

  pandoc REPORT_ECORIDE.md -o REPORT_ECORIDE.pdf

## Prochaines étapes et recommandations
1. Vérifier en local après import SQL et corriger les éventuelles variables d'environnement (DB credentials).
2. Tester les pages problématiques (`index.php`, `covoiturages.php`) en hard-refresh pour valider l'harmonisation header/hero.
3. Nettoyer la règle d'urgence (`header.container-header * { ... }`) dans `style.css` si tout est visuellement correct, et remplacer par des overrides ciblés pour les éléments du nav.
4. Ajouter protections CSRF si manquantes et vérifier `session` hardening (cookie flags, use_strict_mode).
5. Si vous souhaitez, je peux générer automatiquement un PDF et joindre des captures d'écran (vous pouvez me fournir les captures ou me permettre d'exécuter commandes locales).

---
Fichier généré automatiquement à partir du workspace local `c:\xampp\htdocs\ecoride`.

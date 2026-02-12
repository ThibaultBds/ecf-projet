<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title><?= $title ?? 'EcoRide' ?></title>
</head>
<body>

<header class="container-header">
    <h1>
        <a href="/" class="site-brand" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span>
            EcoRide
        </a>
    </h1>

    <!-- Placeholder que ton JS va remplir -->
    <nav id="navbar"></nav>
</header>

<main>
    <?= $content ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> EcoRide - Tous droits réservés</p>
    <div><a href="#" id="openModalLegal">Mentions légales</a></div>
</footer>

<dialog id="modal-legal" class="modal-legal-dialog">
    <form method="dialog" class="modal-legal-content">
        <button class="modal-legal-close" id="closeModalLegal" aria-label="Fermer la fenêtre" type="button">&times;</button>
        <h2>Mentions légales</h2>
        <div class="modal-legal-body">
            <p>
                <strong>Nom de l'entreprise</strong> : EcoRide<br>
                <strong>Statut</strong> : Société fictive dans le cadre d'un projet étudiant<br>
                <strong>Adresse</strong> : 123 rue de la Planète Verte, 75000 Paris<br>
                <strong>SIREN</strong> : 000 000 000<br>
                <strong>Responsable de publication</strong> : Jules Fictif<br>
                <strong>Email</strong> : contact@ecoride.fr<br>
                <strong>Hébergeur</strong> : OVH, 2 rue Kellermann, 59100 Roubaix, France<br>
            </p>
            <p>
                Ce site a été réalisé dans le cadre d'un projet étudiant et n'a pas vocation commerciale.<br>
                Pour toute question, contactez-nous via le formulaire de contact.
            </p>
        </div>
    </form>
</dialog>

<!-- On charge ton ancien navbar.js -->
<script src="/assets/js/navbar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var open = document.getElementById('openModalLegal');
    var dlg = document.getElementById('modal-legal');
    var close = document.getElementById('closeModalLegal');
    if(!open || !dlg) return;
    open.addEventListener('click', function(e){
        e.preventDefault();
        if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.style.display = 'block';
    });
    if(close) close.addEventListener('click', function(){ try{ dlg.close(); }catch(e){ dlg.style.display='none'; } });
});
</script>

</body>
</html>

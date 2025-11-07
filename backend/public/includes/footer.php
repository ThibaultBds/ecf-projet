<?php
// Visual footer include for backend pages
// Contains simple footer markup and reuses footer-scripts for scripts + user JSON
?>
<?php
// Use the site's canonical footer (same markup as frontend index)
?>
<footer>
    <p>&copy; <?= date('Y') ?> EcoRide - Tous droits réservés</p>
    <div><a href="#" id="openModalLegal">Mentions légales</a></div>
</footer>

<dialog id="modal-legal" class="modal-legal-dialog">
    <form method="dialog" class="modal-legal-content">
      <button class="modal-legal-close" id="closeModalLegal" aria-label="Fermer la fenêtre" type="button">×</button>
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

<?php require_once __DIR__ . '/footer-scripts.php'; ?>

<script>
// small helper to open/close the legal modal when links clicked
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

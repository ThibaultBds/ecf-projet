// Exemple : afficher le profil si l'utilisateur est "connecté"
document.addEventListener("DOMContentLoaded", function() {
  // Simule un utilisateur connecté (à remplacer par une vraie détection PHP/session)
  var userConnected = false; // Passe à true pour tester l'affichage
  var userName = "Alice";

  if(userConnected) {
    document.getElementById("user-profile").style.display = "flex";
    document.getElementById("user-name").textContent = userName;
  }
});

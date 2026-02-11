<?php
// Exemple de page utilisant l'autoloader
session_start();
require_once 'config/autoload.php';
useClass('Database');

// Exemple d'utilisation
try {
    $pdo = getDatabase();
    echo "Connexion réussie à la base de données EcoRide";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>



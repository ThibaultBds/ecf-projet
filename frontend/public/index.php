<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test simple pour vérifier l'exécution
echo "Debug : index.php lancé<br>";

// Redirection HTTP vers pages/index.php
header('Location: pages/index.php');
exit;
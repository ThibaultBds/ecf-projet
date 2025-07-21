<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test simple pour vérifier l'exécution
echo "Debug : index.php lancé<br>";

// Attention à l'inclusion, adapte le chemin si besoin
if (file_exists(__DIR__ . '/pages/index.php')) {
    include __DIR__ . '/pages/index.php';
} else {
    echo "pages/index.php introuvable<br>";
}
<?php
/**
 * Autoloader et mécanisme d'import pour EcoRide
 *
 * Remplace les require_once directs par un système moderne
 * de chargement PSR-4 et un importeur pour les helpers.
 */

// Définir les chemins de base
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Autoloader PSR-4 simplifié
spl_autoload_register(function (string $className): void {
    // Transformer le namespace en chemin de fichier
    $path = str_replace('\\', '/', $className);
    $file = BASE_PATH . '/' . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Importeur de classes utilitaires (fichiers non-PSR-4)
function useClass(string $className): bool
{
    static $loaded = [];

    if (isset($loaded[$className])) {
        return true;
    }

    $map = [
        'Database'  => CONFIG_PATH . '/database.php',
        'Auth'      => CONFIG_PATH . '/auth.php',
    ];

    if (isset($map[$className]) && file_exists($map[$className])) {
        require_once $map[$className];
        $loaded[$className] = true;
        return true;
    }

    return false;
}

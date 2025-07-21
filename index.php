<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Test index.php racine Heroku<br>";

include __DIR__ . '/pages/index.php';
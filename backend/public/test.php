<?php
session_start();
$_SESSION['test'] = 'Ceci est un test';
echo 'Session créée. <a href="test2.php">Vérifier</a>';
?>




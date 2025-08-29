<?php
// test_pwd.php (à la racine du projet)
$hash = '$2y$10$cyUwI2HvMgmOmfxevb5H9epq.NTk4jHSSqXkBMoMJPK6AZP8AcZe6'; // hash de test1234
var_dump(password_verify('test1234', $hash)); // doit afficher: bool(true)

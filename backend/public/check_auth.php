<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user'])) {
    echo json_encode([
        'isLoggedIn' => true,
        'userEmail' => $_SESSION['user']['email'],
        'userType' => $_SESSION['user']['type']
    ]);
} else {
    echo json_encode([
        'isLoggedIn' => false
    ]);
}




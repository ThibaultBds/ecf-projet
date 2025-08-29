<?php
// backend/config/guard.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Vérifie qu'un utilisateur est connecté
 * Redirige vers la page de login si non connecté
 */
function requireLogin(): void {
    if (empty($_SESSION['user']['id'])) {
        header('Location: /frontend/public/pages/login_secure.php');
        exit();
    }
}

/**
 * Vérifie que l'utilisateur a un rôle autorisé
 * @param array $allowed Liste des rôles acceptés
 */
function requireRole(array $allowed): void {
    requireLogin();
    $role = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Accès refusé');
    }
}

/**
 * Vérifie que l'utilisateur a au minimum un certain rôle
 * Exemple : requireMinRole('Moderateur') => ok pour Moderateur et Administrateur
 */
function requireMinRole(string $min): void {
    requireLogin();
    $rank = [
        'Utilisateur'     => 1,
        'Moderateur'      => 2,
        'Administrateur'  => 3
    ];
    $userRole = $_SESSION['user']['role'] ?? 'Utilisateur';
    if (($rank[$userRole] ?? 0) < ($rank[$min] ?? 0)) {
        http_response_code(403);
        exit('Accès refusé');
    }
}

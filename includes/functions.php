<?php
// includes/functions.php

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function isEnseignant() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'enseignant';
}

function isEtudiant() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'etudiant';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getRoleLabel($role) {
    return match($role) {
        'admin' => 'Administrateur',
        'enseignant' => 'Enseignant',
        'etudiant' => 'Étudiant',
        default => $role
    };
}
?>
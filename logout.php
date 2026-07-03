<?php
// logout.php
session_start();

// Connexion à la base pour journaliser la déconnexion
require_once 'config/db.php';

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, 'Déconnexion', ?)");
    $log->execute([$userId, $ip]);
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>
<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Redirection vers login si pas connecté ET qu'on n'est pas déjà sur login.php
if (!isset($_SESSION['user']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENSAH - Gestion des Absences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fc; }
        .sidebar { min-height: 100vh; background-color: #1e3a8a; }
        .sidebar a { color: white; text-decoration: none; }
        .sidebar a:hover { background-color: #3b82f6; }
        .content { padding: 20px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar : affichée uniquement si connecté -->
        <?php if (isset($_SESSION['user'])): ?>
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="position-sticky pt-3">
                <h5 class="text-white text-center py-3">ENSAH Absences</h5>
                <ul class="nav flex-column">
                    <?php if ($_SESSION['user']['role'] == 'admin'): ?>
                        <li class="nav-item"><a href="index.php?page=dashboard" class="nav-link text-white"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                        <li class="nav-item"><a href="index.php?page=students" class="nav-link text-white"><i class="fas fa-user-graduate"></i> Étudiants</a></li>
                        <li class="nav-item"><a href="index.php?page=structure" class="nav-link text-white"><i class="fas fa-sitemap"></i> Structure</a></li>
                        <li class="nav-item"><a href="index.php?page=accounts" class="nav-link text-white"><i class="fas fa-users-cog"></i> Comptes</a></li>
                        <li class="nav-item"><a href="index.php?page=absences" class="nav-link text-white"><i class="fas fa-calendar-check"></i> Absences</a></li>
                        <li class="nav-item"><a href="index.php?page=logs" class="nav-link text-white"><i class="fas fa-history"></i> Logs</a></li>
                        <li class="nav-item"><a href="index.php?page=restore_students" class="nav-link"><i class="fas fa-trash-restore me-2"></i> Corbeille étudiants</a></li>
                    <?php elseif ($_SESSION['user']['role'] == 'enseignant'): ?>
                        <li class="nav-item"><a href="index.php?page=dashboard" class="nav-link text-white"><i class="fas fa-chart-pie"></i> Accueil</a></li>
                        <li class="nav-item"><a href="index.php?page=saisie" class="nav-link text-white"><i class="fas fa-pen-to-square"></i> Saisie absences</a></li>
                        <li class="nav-item"><a href="index.php?page=my_absences" class="nav-link text-white"><i class="fas fa-list-check"></i> Mes saisies</a></li>
                        <li class="nav-item"><a href="index.php?page=requests" class="nav-link text-white"><i class="fas fa-envelope-open-text"></i> Demandes</a></li>
                    <?php else: // étudiant ?>
                        <li class="nav-item"><a href="index.php?page=dashboard" class="nav-link text-white"><i class="fas fa-home"></i> Mon espace</a></li>
                        <li class="nav-item"><a href="index.php?page=my_absences" class="nav-link text-white"><i class="fas fa-calendar-xmark"></i> Mes absences</a></li>
                        <li class="nav-item"><a href="index.php?page=profile" class="nav-link text-white"><i class="fas fa-id-card"></i> Profil</a></li>
                        <li class="nav-item"><a href="index.php?page=ask_permission" class="nav-link"><i class="fas fa-envelope-open-text me-2"></i> Demander une permission</a></li>
                        <li class="nav-item"><a href="index.php?page=my_requests" class="nav-link"><i class="fas fa-tasks me-2"></i> Mes demandes</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </div>
        </nav>
        <?php endif; ?>

        <!-- Main content -->
        <main class="<?= isset($_SESSION['user']) ? 'col-md-10 ms-sm-auto px-md-4' : 'col-12' ?> content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?? 'Gestion des absences' ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (isset($_SESSION['user'])): ?>
                        <span class="text-muted">Connecté : <?= htmlspecialchars($_SESSION['user']['name_fr']) ?> (<?= $_SESSION['user']['role'] ?>)</span>
                    <?php else: ?>
                        <span class="text-muted">Non connecté</span>
                    <?php endif; ?>
                </div>
            </div>
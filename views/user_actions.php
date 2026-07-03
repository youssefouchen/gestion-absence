<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

$userId = $_GET['user_id'] ?? 0;
if (!$userId) die("Utilisateur non spécifié.");

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]);
$userInfo = $user->fetch();
if (!$userInfo) die("Utilisateur introuvable.");

// Récupération des logs de cet utilisateur
$logs = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 100");
$logs->execute([$userId]);
$userLogs = $logs->fetchAll();

// Récupération des connexions (action = 'Connexion réussie')
$connexions = $pdo->prepare("SELECT * FROM logs WHERE user_id = ? AND action = 'Connexion réussie' ORDER BY timestamp DESC");
$connexions->execute([$userId]);
$userConnexions = $connexions->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-history"></i> Actions de <?= htmlspecialchars($userInfo['name_fr']) ?> (<?= $userInfo['login'] ?>)
        <a href="?page=accounts" class="btn btn-sm btn-light float-end">Retour aux comptes</a>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="logTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all">Toutes actions</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#connexions">Connexions</button></li>
        </ul>
        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="all">
                <?php if (empty($userLogs)): ?>
                    <p class="text-muted">Aucune action enregistrée pour cet utilisateur.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead><tr><th>Action</th><th>IP</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach($userLogs as $log): ?>
                            <tr><td><?= htmlspecialchars($log['action']) ?></td><td><?= $log['ip'] ?></td><td><?= $log['timestamp'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="connexions">
                <?php if (empty($userConnexions)): ?>
                    <p class="text-muted">Aucune connexion enregistrée.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead><tr><th>IP</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach($userConnexions as $c): ?>
                            <tr><td><?= $c['ip'] ?></td><td><?= $c['timestamp'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
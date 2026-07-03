<?php
// views/logs.php
$logs = $pdo->query("SELECT l.*, u.name_fr FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT 100")->fetchAll();
?>
<div class="card">
    <div class="card-header">Historique des actions</div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Utilisateur</th><th>Action</th><th>IP</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr><td><?= htmlspecialchars($log['name_fr'] ?? 'Inconnu') ?></td><td><?= $log['action'] ?></td><td><?= $log['ip'] ?></td><td><?= $log['timestamp'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
// views/dashboard.php
$role = $_SESSION['user']['role'];
$user = $_SESSION['user'];

if ($role === 'etudiant') {
    // Récupérer l'ID de l'étudiant (uniquement s'il n'est pas supprimé)
    $stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$user['login']]);
    $etudiant = $stmt->fetch();
    
    if ($etudiant) {
        // Nombre total d'absences de l'étudiant
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id = ?");
        $stmt->execute([$etudiant['id']]);
        $totalAbsences = $stmt->fetchColumn();
        
        // Nombre d'absences justifiées
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id = ? AND statut = 'justifiee'");
        $stmt->execute([$etudiant['id']]);
        $justifiees = $stmt->fetchColumn();
        
        $stats = [
            'mes_absences' => $totalAbsences,
            'mes_justifiees' => $justifiees,
        ];
    } else {
        $stats = ['mes_absences' => 0, 'mes_justifiees' => 0];
    }
} else {
    // Admin ou enseignant : statistiques globales (exclure les étudiants supprimés)
    $stats = [
        'etudiants' => $pdo->query("SELECT COUNT(*) FROM etudiants WHERE deleted_at IS NULL")->fetchColumn(),
        'enseignants' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='enseignant'")->fetchColumn(),
        'absences' => $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn(),
        'justifiees' => $pdo->query("SELECT COUNT(*) FROM absences WHERE statut='justifiee'")->fetchColumn()
    ];
}

$logs = $pdo->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 10")->fetchAll();
?>

<div class="row">
    <?php if ($role === 'etudiant'): ?>
        <div class="col-md-6">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Mes absences</h5>
                    <p class="card-text fs-3"><?= $stats['mes_absences'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Dont justifiées</h5>
                    <p class="card-text fs-3"><?= $stats['mes_justifiees'] ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Étudiants</h5>
                    <p class="card-text fs-3"><?= $stats['etudiants'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Enseignants</h5>
                    <p class="card-text fs-3"><?= $stats['enseignants'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Absences totales</h5>
                    <p class="card-text fs-3"><?= $stats['absences'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Justifiées</h5>
                    <p class="card-text fs-3"><?= $stats['justifiees'] ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">Activité récente</div>
    <div class="card-body">
        <ul class="list-group">
            <?php foreach($logs as $log): ?>
                <li class="list-group-item"><?= htmlspecialchars($log['action']) ?> - <?= $log['timestamp'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
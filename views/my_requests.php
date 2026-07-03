<?php
// views/my_requests.php
if ($_SESSION['user']['role'] !== 'etudiant') die("Accès interdit");

$user = $_SESSION['user'];

// Récupérer l'ID de l'étudiant à partir de son email
$stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email = ?");
$stmt->execute([$user['login']]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    echo "<div class='alert alert-danger'>Erreur : votre compte n'est pas associé à un étudiant.</div>";
    $demandes = [];
} else {
    // Récupérer toutes les demandes de cet étudiant
    $stmt = $pdo->prepare("SELECT * FROM permissions WHERE etudiant_id = ? ORDER BY date_demande DESC");
    $stmt->execute([$etudiant['id']]);
    $demandes = $stmt->fetchAll();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list-alt"></i> Mes demandes de permission
        <a href="?page=ask_permission" class="btn btn-sm btn-light float-end">+ Nouvelle demande</a>
    </div>
    <div class="card-body">
        <?php if (empty($demandes)): ?>
            <div class="alert alert-info">Vous n'avez encore fait aucune demande.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date de la demande</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th>Date de réponse</th> <!-- facultatif, si vous avez un champ reponse_date -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $d): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($d['motif'])) ?></td>
                                <td>
                                    <?php
                                    $badge = match($d['statut']) {
                                        'ok' => 'success',
                                        'non' => 'danger',
                                        default => 'warning'
                                    };
                                    $texte = match($d['statut']) {
                                        'ok' => 'Acceptée',
                                        'non' => 'Refusée',
                                        default => 'En attente'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $texte ?></span>
                                </td>
                                <td>
                                    <?= isset($d['reponse_date']) ? date('d/m/Y', strtotime($d['reponse_date'])) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
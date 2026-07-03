<?php
// views/restore_students.php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

// Restauration d'un étudiant
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $id = $_GET['restore'];
    $stmt = $pdo->prepare("UPDATE etudiants SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    // Journalisation
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Restauration de l'étudiant ID $id", $_SERVER['REMOTE_ADDR']]);
    echo "<div class='alert alert-success'>Étudiant restauré avec succès.</div>";
}

// Suppression définitive (optionnel)
if (isset($_GET['delete_permanent']) && is_numeric($_GET['delete_permanent'])) {
    $id = $_GET['delete_permanent'];
    $stmt = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
    $stmt->execute([$id]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Suppression définitive de l'étudiant ID $id", $_SERVER['REMOTE_ADDR']]);
    echo "<div class='alert alert-warning'>Étudiant supprimé définitivement.</div>";
}

// Récupérer tous les étudiants supprimés
$stmt = $pdo->query("SELECT * FROM etudiants WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
$deletedStudents = $stmt->fetchAll();
?>

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <i class="fas fa-trash-restore"></i> Corbeille – Étudiants supprimés
        <a href="?page=students" class="btn btn-sm btn-light float-end">← Retour à la liste</a>
    </div>
    <div class="card-body">
        <?php if (empty($deletedStudents)): ?>
            <div class="alert alert-info">Aucun étudiant supprimé.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Nom</th><th>Prénom</th><th>Massar</th><th>Email</th><th>Date suppression</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedStudents as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nom']) ?></td>
                            <td><?= htmlspecialchars($s['prenom']) ?></td>
                            <td><?= htmlspecialchars($s['massar']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= $s['deleted_at'] ?></td>
                            <td>
                                <a href="?page=restore_students&restore=<?= $s['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Restaurer cet étudiant ?')"><i class="fas fa-undo"></i> Restaurer</a>
                                <a href="?page=restore_students&delete_permanent=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer définitivement ? Cette action est irréversible.')"><i class="fas fa-trash-alt"></i> Supprimer définitivement</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
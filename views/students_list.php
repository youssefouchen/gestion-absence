<?php
// views/students_list.php
// Traitement de la suppression douce (soft delete)
if (isset($_GET['action']) && $_GET['action'] === 'soft_delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE etudiants SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Suppression (soft delete) de l'étudiant ID $id", $_SERVER['REMOTE_ADDR']]);
    header("Location: ?page=students");
    exit;
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM etudiants WHERE (nom LIKE :search OR prenom LIKE :search OR massar LIKE :search) AND deleted_at IS NULL ORDER BY nom, prenom";
$stmt = $pdo->prepare($query);
$stmt->execute(['search' => "%$search%"]);
$students = $stmt->fetchAll();
?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between flex-wrap">
        <span><i class="fas fa-user-graduate me-2"></i> Liste des étudiants</span>
        <div>
            <a href="?page=restore_students" class="btn btn-sm btn-secondary me-2"><i class="fas fa-trash-restore"></i> Corbeille</a>
            <a href="?page=student_form&action=add" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Ajouter</a>
        </div>
    </div>
    <div class="card-body">
        <!-- Formulaire de recherche avec bouton -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="hidden" name="page" value="students">
                <input type="text" name="search" class="form-control" placeholder="Rechercher par nom, prénom, massar..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Rechercher</button>
                <?php if ($search): ?>
                    <a href="?page=students" class="btn btn-secondary">Réinitialiser</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Massar</th>
                        <th>CIN</th>
                        <th>Niveau</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nom']) ?></td>
                        <td><?= htmlspecialchars($s['prenom']) ?></td>
                        <td><?= htmlspecialchars($s['massar']) ?></td>
                        <td><?= htmlspecialchars($s['cin']) ?></td>
                        <td><?= htmlspecialchars($s['niveau']) ?></td>
                        <td>
                            <a href="?page=student_form&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="?page=students&action=soft_delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet étudiant ? (Vous pourrez le restaurer depuis la corbeille)')" title="Supprimer"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucun étudiant trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
// views/accounts.php
$users = $pdo->query("SELECT * FROM users ORDER BY role, login")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Changement de rôle de l'utilisateur $userId vers $newRole", $_SERVER['REMOTE_ADDR']]);
    header("Location: ?page=accounts");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    $id = $_POST['user_id'];
    $stmt = $pdo->prepare("SELECT enabled FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if ($user) {
        $newState = $user['enabled'] ? 0 : 1;
        $update = $pdo->prepare("UPDATE users SET enabled = ? WHERE id = ?");
        $update->execute([$newState, $id]);
    }
    header('Location: ?page=accounts');
    exit;
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Comptes utilisateurs</span>
        <a href="?page=account_form" class="btn btn-sm btn-success">+ Nouveau compte</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Login</th><th>Nom</th><th>Rôle</th><th>État</th><th>Actions</th><th>Changer rôle</th><th>Voir actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['login']) ?></td>
                        <td><?= htmlspecialchars($u['name_fr']) ?></td>
                        <td><?= $u['role'] ?></td>
                        <td><?= $u['enabled'] ? 'Actif' : 'Désactivé' ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="toggle" class="btn btn-sm btn-warning"><?= $u['enabled'] ? 'Désactiver' : 'Activer' ?></button>
                            </form>
                            <a href="?page=account_form&edit=<?= $u['id'] ?>" class="btn btn-sm btn-info">Modifier</a>
                        </td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" class="form-select form-select-sm d-inline-block w-auto">
                                    <option value="admin" <?= $u['role']=='admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="enseignant" <?= $u['role']=='enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                    <option value="etudiant" <?= $u['role']=='etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-sm btn-primary">Changer</button>
                            </form>
                        </td>
                        <td><a href="?page=user_actions&user_id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Voir actions</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
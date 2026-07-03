<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $module_id = $_POST['module_id'];
    $stmt = $pdo->prepare("INSERT INTO elements (module_id, titre) VALUES (?, ?)");
    $stmt->execute([$module_id, $titre]);
    header('Location: index.php?page=structure');
    exit;
}

$modules = $pdo->query("SELECT m.id, m.code, m.titre, f.alias as filiere_alias 
                        FROM modules m 
                        JOIN filieres f ON m.filiere_id = f.id 
                        ORDER BY f.alias, m.code")->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">Ajouter un élément (matière)</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Titre de l’élément (ex: C/C++, Réseaux, etc.)</label>
                <input type="text" name="titre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Module parent</label>
                <select name="module_id" class="form-select" required>
                    <?php foreach($modules as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['code'] . ' - ' . $m['titre'] . ' (' . $m['filiere_alias'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <a href="?page=structure" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
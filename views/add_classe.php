<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $filiere_id = $_POST['filiere_id'];
    $stmt = $pdo->prepare("INSERT INTO classes (filiere_id, nom) VALUES (?, ?)");
    $stmt->execute([$filiere_id, $nom]);
    header('Location: index.php?page=structure');
    exit;
}

$filieres = $pdo->query("SELECT id, alias, nom FROM filieres")->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">Ajouter une classe</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Nom de la classe (ex: GI1-A, GI2-B)</label>
                <input type="text" name="nom" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Filière associée</label>
                <select name="filiere_id" class="form-select" required>
                    <?php foreach($filieres as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['alias'] . ' - ' . $f['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <a href="?page=structure" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
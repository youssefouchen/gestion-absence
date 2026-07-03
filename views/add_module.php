<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $titre = $_POST['titre'];
    $niveau = $_POST['niveau'];
    $filiere_id = $_POST['filiere_id'];
    $stmt = $pdo->prepare("INSERT INTO modules (filiere_id, code, titre, niveau) VALUES (?, ?, ?, ?)");
    $stmt->execute([$filiere_id, $code, $titre, $niveau]);
    header('Location: index.php?page=structure');
    exit;
}

$filieres = $pdo->query("SELECT id, alias, nom FROM filieres")->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">Ajouter un module</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Code du module (ex: INF101)</label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Titre du module</label>
                <input type="text" name="titre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Niveau (S1, S2, S3...)</label>
                <input type="text" name="niveau" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Filière</label>
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
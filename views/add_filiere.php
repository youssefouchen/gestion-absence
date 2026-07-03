<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = $_POST['alias'];
    $nom = $_POST['nom'];
    $coord = !empty($_POST['coordonnateur_id']) ? $_POST['coordonnateur_id'] : null;
    $debut = $_POST['accreditation_debut'];
    $fin = $_POST['accreditation_fin'];
    $stmt = $pdo->prepare("INSERT INTO filieres (alias, nom, accreditation_debut, accreditation_fin, coordonnateur_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$alias, $nom, $debut, $fin, $coord]);
    header('Location: index.php?page=structure');
    exit;
}

$enseignants = $pdo->query("SELECT id, name_fr FROM users WHERE role='enseignant'")->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">Ajouter une filière</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label>Alias (ex: GI, GE, GC)</label>
                <input type="text" name="alias" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Nom complet de la filière</label>
                <input type="text" name="nom" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Coordonnateur (optionnel)</label>
                <select name="coordonnateur_id" class="form-select">
                    <option value="">-- Aucun --</option>
                    <?php foreach($enseignants as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name_fr']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label>Année début accréditation</label>
                    <input type="number" name="accreditation_debut" class="form-control" value="<?= date('Y') ?>">
                </div>
                <div class="col-md-6">
                    <label>Année fin accréditation</label>
                    <input type="number" name="accreditation_fin" class="form-control" value="<?= date('Y')+5 ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3">Enregistrer</button>
            <a href="?page=structure" class="btn btn-secondary mt-3">Annuler</a>
        </form>
    </div>
</div>
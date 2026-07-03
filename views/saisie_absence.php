<?php
// views/saisie_absence.php
$elements = $pdo->query("SELECT e.*, m.titre as module_titre FROM elements e JOIN modules m ON e.module_id = m.id")->fetchAll();
// Exclure les étudiants supprimés
$etudiants = $pdo->query("SELECT * FROM etudiants WHERE deleted_at IS NULL ORDER BY nom, prenom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $element_id = $_POST['element_id'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $absents = explode(',', $_POST['absents']);
    $enseignant_id = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("INSERT INTO absences (etudiant_id, element_id, date_absence, type_absence, enseignant_id) VALUES (?, ?, ?, ?, ?)");
    foreach ($absents as $etudiant_id) {
        $stmt->execute([$etudiant_id, $element_id, $date, $type, $enseignant_id]);
    }
    $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)")->execute([$enseignant_id, "Saisie de " . count($absents) . " absence(s)", $_SERVER['REMOTE_ADDR']]);
    echo "<div class='alert alert-success'>Absences enregistrées.</div>";
}
?>
<div class="card">
    <div class="card-header">Saisie des absences</div>
    <div class="card-body">
        <form method="POST" id="absenceForm">
            <div class="row mb-3">
                <div class="col-md-4"><label>Élément</label><select name="element_id" class="form-select"><?php foreach($elements as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['titre']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label>Type</label><select name="type" class="form-select"><option>Cours</option><option>TD</option><option>TP</option><option>Visite</option></select></div>
                <div class="col-md-4"><label>Date</label><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            </div>
            <div class="mb-3"><label>Étudiants (cliquez sur la carte pour marquer absent)</label>
                <div id="studentsGrid" class="row row-cols-2 row-cols-md-4 g-3">
                    <?php foreach($etudiants as $e): ?>
                    <div class="col"><div class="card student-card h-100" data-id="<?= $e['id'] ?>" onclick="toggleAbsent(this)"><div class="card-body p-2 text-center"><i class="fas fa-user-graduate fa-2x"></i><p class="small mt-1"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></p></div></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="absents" id="absentsList">
            <button type="submit" class="btn btn-danger w-100">Enregistrer les absences</button>
        </form>
    </div>
</div>
<script>
function toggleAbsent(card) {
    card.classList.toggle('bg-danger');
    card.classList.toggle('text-white');
    updateAbsentsList();
}
function updateAbsentsList() {
    let ids = [];
    document.querySelectorAll('.student-card.bg-danger').forEach(card => ids.push(card.dataset.id));
    document.getElementById('absentsList').value = ids.join(',');
}
</script>
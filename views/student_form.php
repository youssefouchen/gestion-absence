<?php
// views/student_form.php
$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;
$student = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) die("Étudiant introuvable.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO etudiants (massar, cin, nom, prenom, email, niveau) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['massar'], $data['cin'], $data['nom'], $data['prenom'], $data['email'], $data['niveau']]);
    } else {
        $stmt = $pdo->prepare("UPDATE etudiants SET massar=?, cin=?, nom=?, prenom=?, email=?, niveau=? WHERE id=?");
        $stmt->execute([$data['massar'], $data['cin'], $data['nom'], $data['prenom'], $data['email'], $data['niveau'], $id]);
    }
    header('Location: index.php?page=students');
    exit;
}
?>
<div class="card">
    <div class="card-header"><?= $action === 'add' ? 'Ajouter' : 'Modifier' ?> un étudiant</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label>Massar</label><input type="text" name="massar" class="form-control" value="<?= htmlspecialchars($student['massar'] ?? '') ?>" required></div>
            <div class="mb-3"><label>CIN</label><input type="text" name="cin" class="form-control" value="<?= htmlspecialchars($student['cin'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Nom</label><input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($student['nom'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Prénom</label><input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($student['prenom'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Niveau</label><input type="text" name="niveau" class="form-control" value="<?= htmlspecialchars($student['niveau'] ?? '') ?>" required></div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=students" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
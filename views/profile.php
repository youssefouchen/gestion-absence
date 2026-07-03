<?php
// views/profile.php
$login = $_SESSION['user']['login'];
$stmt = $pdo->prepare("SELECT * FROM etudiants WHERE email = ?");
$stmt->execute([$login . '@ensah.ma']); // adaptation selon votre logique
$etudiant = $stmt->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tel = $_POST['telephone'];
    if ($etudiant) {
        $pdo->prepare("UPDATE etudiants SET telephone = ? WHERE id = ?")->execute([$tel, $etudiant['id']]);
    }
    echo "<div class='alert alert-success'>Profil mis à jour.</div>";
}
?>
<div class="card">
    <div class="card-header">Mon profil</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3"><label>Nom complet</label><input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['name_fr']) ?>" disabled></div>
            <div class="mb-3"><label>Email</label><input type="email" class="form-control" value="<?= htmlspecialchars($etudiant['email'] ?? '') ?>" disabled></div>
            <div class="mb-3"><label>Téléphone</label><input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($etudiant['telephone'] ?? '') ?>"></div>
            <div class="mb-3"><label>Photo</label><input type="file" class="form-control"></div>
            <button type="submit" class="btn btn-primary">Sauvegarder</button>
        </form>
    </div>
</div>
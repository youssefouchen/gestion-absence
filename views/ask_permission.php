<?php
// views/ask_permission.php
if ($_SESSION['user']['role'] !== 'etudiant') die("Accès interdit");

$user = $_SESSION['user'];
$error = '';
$success = '';

// Récupérer l'ID de l'étudiant à partir de son email
$stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email = ?");
$stmt->execute([$user['login']]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    die("Erreur : votre compte n'est pas associé à un étudiant.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_demande = $_POST['date_demande'];
    $motif = trim($_POST['motif']);
    
    if (empty($motif)) {
        $error = "Veuillez saisir un motif.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO permissions (etudiant_id, date_demande, motif, statut) VALUES (?, ?, ?, 'en_cours')");
        $stmt->execute([$etudiant['id'], $date_demande, $motif]);
        $success = "Votre demande a été envoyée au professeur.";
        
        // Journalisation
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$user['id'], "Demande de permission envoyée", $_SERVER['REMOTE_ADDR']]);
    }
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-paper-plane"></i> Demander une permission / justifier une absence
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Date de la demande</label>
                <input type="date" name="date_demande" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Motif (détail de votre demande)</label>
                <textarea name="motif" rows="4" class="form-control" required placeholder="Ex: Absence du ... pour raison médicale, demande de sortie, etc."></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-send"></i> Envoyer la demande</button>
            <a href="?page=dashboard" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
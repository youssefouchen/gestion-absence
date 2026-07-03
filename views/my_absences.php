<?php
// views/my_absences.php
$role = $_SESSION['user']['role'];
$user = $_SESSION['user'];

if ($role === 'etudiant') {
    // Étape 1 : Chercher l'étudiant par son email (login complet)
    $stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email = ?");
    $stmt->execute([$user['login']]);
    $etudiant = $stmt->fetch();

    // Étape 2 : Si non trouvé, essayer avec la partie avant @ (au cas où le login serait différent)
    if (!$etudiant && strpos($user['login'], '@') !== false) {
        $partLogin = explode('@', $user['login'])[0];
        $stmt = $pdo->prepare("SELECT id FROM etudiants WHERE email LIKE ?");
        $stmt->execute([$partLogin . '%']);
        $etudiant = $stmt->fetch();
    }

    // Étape 3 : Si toujours pas trouvé, afficher une erreur claire
    if (!$etudiant) {
        echo "<div class='alert alert-danger'>
                <strong>Erreur :</strong> Aucun étudiant associé au compte '{$user['login']}'. 
                Vérifiez que la table 'etudiants' contient un enregistrement avec cet email.
              </div>";
        $absences = [];
    } else {
        // Récupérer les absences de l'étudiant
        $stmt = $pdo->prepare("SELECT a.*, el.titre as element_titre
                               FROM absences a
                               JOIN elements el ON a.element_id = el.id
                               WHERE a.etudiant_id = ?
                               ORDER BY a.date_absence DESC");
        $stmt->execute([$etudiant['id']]);
        $absences = $stmt->fetchAll();
    }
} else { // enseignant
    $stmt = $pdo->prepare("SELECT a.*, e.nom, e.prenom, el.titre as element_titre
                           FROM absences a
                           JOIN etudiants e ON a.etudiant_id = e.id
                           JOIN elements el ON a.element_id = el.id
                           WHERE a.enseignant_id = ?
                           ORDER BY a.date_absence DESC");
    $stmt->execute([$user['id']]);
    $absences = $stmt->fetchAll();
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-calendar-check"></i>
        <?= $role === 'etudiant' ? 'Mes absences' : 'Absences que j\'ai saisies' ?>
    </div>
    <div class="card-body">
        <?php if (empty($absences)): ?>
            <div class="alert alert-info">Aucune absence enregistrée.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <?php if ($role === 'enseignant'): ?>
                                <th>Étudiant</th>
                            <?php endif; ?>
                            <th>Élément</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <?php if ($role === 'etudiant'): ?>
                                <th>Justificatif</th>
                                <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absences as $a): ?>
                            <tr>
                                <?php if ($role === 'enseignant'): ?>
                                    <td><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($a['element_titre']) ?></td>
                                <td><?= $a['date_absence'] ?></td>
                                <td><?= $a['type_absence'] ?></td>
                                <td>
                                    <?php
                                    $badge = match($a['statut']) {
                                        'justifiee' => 'success',
                                        'annulee' => 'secondary',
                                        default => 'danger'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $a['statut'] ?></span>
                                </td>
                                <?php if ($role === 'etudiant'): ?>
                                    <td>
                                        <?php if ($a['justificatif_fichier']): ?>
                                            <a href="uploads/<?= $a['justificatif_fichier'] ?>" target="_blank" class="btn btn-sm btn-info">Voir</a>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['statut'] === 'non_justifiee'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="justifier(<?= $a['id'] ?>)">Justifier</button>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === 'etudiant'): ?>
<script>
function justifier(absenceId) {
    let fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'application/pdf';
    fileInput.onchange = function() {
        alert("Fonction d'envoi de justificatif à implémenter.\nID absence : " + absenceId);
        // Ici vous pourrez envoyer le fichier via AJAX ou formulaire
    };
    fileInput.click();
}
</script>
<?php endif; ?>
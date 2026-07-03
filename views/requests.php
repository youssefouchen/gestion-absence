<?php
// views/requests.php
if ($_SESSION['user']['role'] !== 'enseignant') die("Accès interdit");

// Traitement de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reponse'])) {
    $permId = $_POST['perm_id'];
    $statut = $_POST['reponse']; // 'ok' ou 'non'
    $stmt = $pdo->prepare("UPDATE permissions SET statut = ? WHERE id = ?");
    $stmt->execute([$statut, $permId]);
    
    // Journalisation
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Réponse à la demande #$permId : $statut", $_SERVER['REMOTE_ADDR']]);
    
    // Message de confirmation
    echo "<div class='alert alert-success'>Réponse enregistrée.</div>";
}

// Récupérer toutes les demandes en cours (et éventuellement les récentes)
$demandes = $pdo->query("
    SELECT p.*, e.nom, e.prenom, e.email 
    FROM permissions p 
    JOIN etudiants e ON p.etudiant_id = e.id 
    WHERE p.statut = 'en_cours' 
    ORDER BY p.date_demande DESC
")->fetchAll();

$historique = $pdo->query("
    SELECT p.*, e.nom, e.prenom 
    FROM permissions p 
    JOIN etudiants e ON p.etudiant_id = e.id 
    WHERE p.statut != 'en_cours' 
    ORDER BY p.date_demande DESC LIMIT 20
")->fetchAll();
?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-clock"></i> Demandes en attente
            </div>
            <div class="card-body">
                <?php if (empty($demandes)): ?>
                    <p class="text-muted">Aucune demande en attente.</p>
                <?php else: ?>
                    <?php foreach ($demandes as $d): ?>
                        <div class="border rounded p-3 mb-2">
                            <p><strong><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></strong> – <?= $d['email'] ?></p>
                            <p><small>Date : <?= $d['date_demande'] ?></small></p>
                            <p>Motif : <?= nl2br(htmlspecialchars($d['motif'])) ?></p>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="perm_id" value="<?= $d['id'] ?>">
                                <button type="submit" name="reponse" value="ok" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Accepter</button>
                                <button type="submit" name="reponse" value="non" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Refuser</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-history"></i> Historique des demandes traitées
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($historique)): ?>
                    <p class="text-muted">Aucune demande traitée.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($historique as $h): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?></strong><br>
                                Date : <?= $h['date_demande'] ?><br>
                                Statut : 
                                <span class="badge <?= $h['statut'] == 'ok' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $h['statut'] == 'ok' ? 'Acceptée' : 'Refusée' ?>
                                </span>
                                <br><small class="text-muted"><?= nl2br(htmlspecialchars($h['motif'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
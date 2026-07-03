<?php
// views/absences.php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

$absences = $pdo->query("SELECT a.*, 
                                e.nom, e.prenom, 
                                el.titre as element_titre,
                                u.name_fr as enseignant_nom
                         FROM absences a
                         JOIN etudiants e ON a.etudiant_id = e.id
                         JOIN elements el ON a.element_id = el.id
                         LEFT JOIN users u ON a.enseignant_id = u.id
                         ORDER BY a.date_absence DESC, a.date_saisie DESC")->fetchAll();
?>
<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-calendar-check"></i> Gestion des absences
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Étudiant</th>
                        <th>Élément</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Saisi par</th>
                        <th>Justificatif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($absences) > 0): ?>
                        <?php foreach ($absences as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></td>
                            <td><?= htmlspecialchars($a['element_titre']) ?></td>
                            <td><?= htmlspecialchars($a['date_absence']) ?></td>
                            <td><?= htmlspecialchars($a['type_absence']) ?></td>
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
                            <td><?= htmlspecialchars($a['enseignant_nom'] ?? 'Inconnu') ?></td>
                            <td>
                                <?php if ($a['justificatif_fichier']): ?>
                                    <a href="uploads/<?= $a['justificatif_fichier'] ?>" target="_blank" class="btn btn-sm btn-info">Voir</a>
                                <?php else: ?>
                                    <span class="text-muted">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['statut'] == 'non_justifiee'): ?>
                                    <a href="?page=justify_absence&id=<?= $a['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Marquer cette absence comme justifiée ?')">Justifier</a>
                                <?php elseif ($a['statut'] == 'justifiee'): ?>
                                    <a href="?page=unjustify_absence&id=<?= $a['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Retirer la justification ?')">Injustifier</a>
                                <?php endif; ?>
                                <?php if ($a['statut'] != 'annulee'): ?>
                                    <a href="?page=annuler_absence&id=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Annuler cette absence ?')">Annuler</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Aucune absence enregistrée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
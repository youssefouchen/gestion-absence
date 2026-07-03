<?php
// views/structure.php - version avec suppression
$filieres = $pdo->query("SELECT * FROM filieres")->fetchAll();
$classes = $pdo->query("SELECT c.*, f.alias as filiere_nom FROM classes c LEFT JOIN filieres f ON c.filiere_id = f.id")->fetchAll();
$modules = $pdo->query("SELECT m.*, f.alias as filiere_nom FROM modules m LEFT JOIN filieres f ON m.filiere_id = f.id")->fetchAll();
$elements = $pdo->query("SELECT e.*, m.titre as module_titre FROM elements e LEFT JOIN modules m ON e.module_id = m.id")->fetchAll();
?>

<!-- Bouton d'import global -->
<div class="mb-3 text-end">
    <a href="?page=import_structure" class="btn btn-info">
        <i class="fas fa-file-import"></i> Importer la structure (CSV/XML)
    </a>
</div>

<div class="row">
    <!-- Filières -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Filières</span>
                <div>
                    <a href="?page=add_filiere" class="btn btn-sm btn-primary">Ajouter</a>
                    <a href="?page=import_structure" class="btn btn-sm btn-info">Importer</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach($filieres as $f): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($f['alias']) ?> - <?= htmlspecialchars($f['nom']) ?>
                            <a href="?page=delete_filiere&id=<?= $f['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette filière ? Tous les modules, classes et éléments associés seront supprimés.')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Classes -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Classes</span>
                <div>
                    <a href="?page=add_classe" class="btn btn-sm btn-primary">Ajouter</a>
                    <a href="?page=import_structure" class="btn btn-sm btn-info">Importer</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach($classes as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($c['nom']) ?> (<?= htmlspecialchars($c['filiere_nom']) ?>)
                            <a href="?page=delete_classe&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette classe ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modules -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Modules</span>
                <div>
                    <a href="?page=add_module" class="btn btn-sm btn-primary">Ajouter</a>
                    <a href="?page=import_structure" class="btn btn-sm btn-info">Importer</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach($modules as $m): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['titre']) ?>
                            <a href="?page=delete_module&id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce module ? Tous les éléments associés seront supprimés.')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Éléments -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Éléments</span>
                <div>
                    <a href="?page=add_element" class="btn btn-sm btn-primary">Ajouter</a>
                    <a href="?page=import_structure" class="btn btn-sm btn-info">Importer</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach($elements as $e): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($e['titre']) ?> (<?= htmlspecialchars($e['module_titre']) ?>)
                            <a href="?page=delete_element&id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet élément ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
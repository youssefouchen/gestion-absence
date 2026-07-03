<?php
// views/import_structure.php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "<div class='alert alert-danger'>Erreur d'upload du fichier.</div>";
    } elseif (!in_array($fileType, ['csv', 'xml'])) {
        $message = "<div class='alert alert-danger'>Format non supporté. Utilisez CSV ou XML.</div>";
    } else {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $uploadFile = $uploadDir . uniqid() . '_' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $uploadFile);
        
        try {
            if ($fileType === 'csv') {
                $stats = importCSV($pdo, $uploadFile);
                $message = "<div class='alert alert-success'>Import CSV réussi : " .
                           "{$stats['filieres']} filière(s), {$stats['classes']} classe(s), " .
                           "{$stats['modules']} module(s), {$stats['elements']} élément(s) insérés.</div>";
            } elseif ($fileType === 'xml') {
                $stats = importXML($pdo, $uploadFile);
                $message = "<div class='alert alert-success'>Import XML réussi : " .
                           "{$stats['filieres']} filière(s), {$stats['classes']} classe(s), " .
                           "{$stats['modules']} module(s), {$stats['elements']} élément(s) insérés.</div>";
            }
            $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
            $log->execute([$_SESSION['user']['id'], "Import structure pédagogique via {$fileType}", $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        if (file_exists($uploadFile)) unlink($uploadFile);
    }
}

// ========== IMPORT CSV (CORRIGÉ + GESTION CLASSES) ==========
function importCSV($pdo, $filePath) {
    $handle = fopen($filePath, 'r');
    if (!$handle) throw new Exception("Impossible d'ouvrir le fichier.");
    
    // Lecture des en-têtes
    $headers = fgetcsv($handle, 0, ';');
    if ($headers === false) throw new Exception("Fichier CSV invalide : pas d'en-têtes.");
    // Nettoyer les en-têtes (supprimer BOM, espaces, normaliser)
    $headers = array_map(function($h) {
        $h = trim($h, "\xEF\xBB\xBF"); // enlever BOM
        return trim($h);
    }, $headers);
    
    $stats = ['filieres' => 0, 'classes' => 0, 'modules' => 0, 'elements' => 0];
    $filiereIds = [];
    $moduleIds = [];
    
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        // Ignorer les lignes totalement vides
        if (count($row) == 1 && trim($row[0]) === '') continue;
        
        // Ajuster le nombre de colonnes pour qu'il corresponde exactement aux en-têtes
        $headerCount = count($headers);
        $rowCount = count($row);
        if ($rowCount < $headerCount) {
            $row = array_pad($row, $headerCount, '');
        } elseif ($rowCount > $headerCount) {
            $row = array_slice($row, 0, $headerCount);
        }
        
        // Créer le tableau associatif
        $data = array_combine($headers, $row);
        if ($data === false) {
            // En cas d'échec, on ignore la ligne
            continue;
        }
        
        $type = trim($data['type']);
        if ($type === 'filiere') {
            $alias = trim($data['alias']);
            $nom = trim($data['nom']);
            $debut = trim($data['debut']) ?: date('Y');
            $fin = trim($data['fin']) ?: date('Y')+5;
            $stmt = $pdo->prepare("INSERT INTO filieres (alias, nom, accreditation_debut, accreditation_fin) 
                                   VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nom = VALUES(nom)");
            $stmt->execute([$alias, $nom, $debut, $fin]);
            $stmt = $pdo->prepare("SELECT id FROM filieres WHERE alias = ?");
            $stmt->execute([$alias]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $filiereIds[$alias] = $id;
                $stats['filieres']++;
            }
        }
        elseif ($type === 'classe') {
            $nomClasse = trim($data['nom']);
            $filiereAlias = trim($data['filiere_alias']);
            if (isset($filiereIds[$filiereAlias])) {
                $fid = $filiereIds[$filiereAlias];
                $stmt = $pdo->prepare("INSERT INTO classes (filiere_id, nom) VALUES (?, ?)");
                $stmt->execute([$fid, $nomClasse]);
                $stats['classes']++;
            }
        }
        elseif ($type === 'module') {
            $code = trim($data['code']);
            $titre = trim($data['titre']);
            $niveau = trim($data['niveau']);
            $filiereAlias = trim($data['filiere_alias']);
            if (isset($filiereIds[$filiereAlias])) {
                $fid = $filiereIds[$filiereAlias];
                $stmt = $pdo->prepare("INSERT INTO modules (filiere_id, code, titre, niveau) 
                                       VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE titre = VALUES(titre), niveau = VALUES(niveau)");
                $stmt->execute([$fid, $code, $titre, $niveau]);
                $stmt = $pdo->prepare("SELECT id FROM modules WHERE code = ?");
                $stmt->execute([$code]);
                $mid = $stmt->fetchColumn();
                if ($mid) {
                    $moduleIds[$code] = $mid;
                    $stats['modules']++;
                }
            }
        }
        elseif ($type === 'element') {
            $titre = trim($data['titre']);
            $moduleCode = trim($data['module_code']);
            if (isset($moduleIds[$moduleCode])) {
                $mid = $moduleIds[$moduleCode];
                $stmt = $pdo->prepare("INSERT INTO elements (module_id, titre) VALUES (?, ?)");
                $stmt->execute([$mid, $titre]);
                $stats['elements']++;
            }
        }
    }
    fclose($handle);
    return $stats;
}

// ========== IMPORT XML (COMPLET) ==========
function importXML($pdo, $filePath) {
    $xml = simplexml_load_file($filePath);
    if (!$xml) throw new Exception("XML invalide");
    $stats = ['filieres' => 0, 'classes' => 0, 'modules' => 0, 'elements' => 0];
    $filiereIds = [];
    $moduleIds = [];
    
    foreach ($xml->filiere as $f) {
        $alias = (string)$f->alias;
        $nom = (string)$f->nom;
        $debut = (string)$f->debut ?: date('Y');
        $fin = (string)$f->fin ?: date('Y')+5;
        $stmt = $pdo->prepare("INSERT INTO filieres (alias, nom, accreditation_debut, accreditation_fin) 
                               VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nom = VALUES(nom)");
        $stmt->execute([$alias, $nom, $debut, $fin]);
        $stmt = $pdo->prepare("SELECT id FROM filieres WHERE alias = ?");
        $stmt->execute([$alias]);
        $fid = $stmt->fetchColumn();
        if ($fid) {
            $filiereIds[$alias] = $fid;
            $stats['filieres']++;
        } else continue;
        
        // Classes
        if (isset($f->classes)) {
            foreach ($f->classes->classe as $classe) {
                $stmt = $pdo->prepare("INSERT INTO classes (filiere_id, nom) VALUES (?, ?)");
                $stmt->execute([$fid, (string)$classe]);
                $stats['classes']++;
            }
        }
        
        // Modules
        if (isset($f->modules)) {
            foreach ($f->modules->module as $m) {
                $code = (string)$m->code;
                $titre = (string)$m->titre;
                $niveau = (string)$m->niveau;
                $stmt = $pdo->prepare("INSERT INTO modules (filiere_id, code, titre, niveau) 
                                       VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE titre = VALUES(titre), niveau = VALUES(niveau)");
                $stmt->execute([$fid, $code, $titre, $niveau]);
                $stmt = $pdo->prepare("SELECT id FROM modules WHERE code = ?");
                $stmt->execute([$code]);
                $mid = $stmt->fetchColumn();
                if ($mid) {
                    $moduleIds[$code] = $mid;
                    $stats['modules']++;
                }
                if (isset($m->elements)) {
                    foreach ($m->elements->element as $e) {
                        $stmt = $pdo->prepare("INSERT INTO elements (module_id, titre) VALUES (?, ?)");
                        $stmt->execute([$mid, (string)$e]);
                        $stats['elements']++;
                    }
                }
            }
        }
    }
    return $stats;
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-file-import"></i> Importer la structure pédagogique (CSV / XML)
    </div>
    <div class="card-body">
        <?= $message ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Fichier (CSV ou XML)</label>
                <input type="file" name="import_file" class="form-control" accept=".csv,.xml" required>
                <small class="text-muted">
                    CSV : séparateur point-virgule (;) – voir exemple ci-dessous.<br>
                    XML : balises &lt;structure&gt;&lt;filiere&gt;...&lt;/filiere&gt;&lt;/structure&gt;
                </small>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Importer</button>
            <a href="?page=structure" class="btn btn-secondary">Annuler</a>
        </form>
        <hr>
        <h6>📄 Exemple de fichier CSV valide (copiez-collez) :</h6>
        <pre>type;alias;nom;debut;fin;filiere_alias;code;titre;niveau;module_code
filiere;GC;Génie Civil;2024;2029;;;;;
classe;;GC1;;;;GC;;;
classe;;GC2;;;;GC;;;
module;;;;;;GC101;Matériaux de construction;S1;GC
module;;;;;;GC102;Mécanique des sols;S2;GC
element;;;;;;;Béton armé;;GC101
element;;;;;;;Acier;;GC101
element;;;;;;;Fondations;;GC102
element;;;;;;;Stabilité des pentes;;GC102</pre>
        <h6>📄 Exemple de fichier XML :</h6>
        <pre>&lt;structure&gt;
    &lt;filiere&gt;
        &lt;alias&gt;GC&lt;/alias&gt;
        &lt;nom&gt;Génie Civil&lt;/nom&gt;
        &lt;debut&gt;2024&lt;/debut&gt;
        &lt;fin&gt;2029&lt;/fin&gt;
        &lt;classes&gt;
            &lt;classe&gt;GC1&lt;/classe&gt;
            &lt;classe&gt;GC2&lt;/classe&gt;
        &lt;/classes&gt;
        &lt;modules&gt;
            &lt;module&gt;
                &lt;code&gt;GC101&lt;/code&gt;
                &lt;titre&gt;Matériaux de construction&lt;/titre&gt;
                &lt;niveau&gt;S1&lt;/niveau&gt;
                &lt;elements&gt;
                    &lt;element&gt;Béton armé&lt;/element&gt;
                    &lt;element&gt;Acier&lt;/element&gt;
                &lt;/elements&gt;
            &lt;/module&gt;
            &lt;module&gt;
                &lt;code&gt;GC102&lt;/code&gt;
                &lt;titre&gt;Mécanique des sols&lt;/titre&gt;
                &lt;niveau&gt;S2&lt;/niveau&gt;
                &lt;elements&gt;
                    &lt;element&gt;Fondations&lt;/element&gt;
                    &lt;element&gt;Stabilité des pentes&lt;/element&gt;
                &lt;/elements&gt;
            &lt;/module&gt;
        &lt;/modules&gt;
    &lt;/filiere&gt;
&lt;/structure&gt;</pre>
    </div>
</div>
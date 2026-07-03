<?php
// config/db.php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'gestion_absences'; // correspond à MYSQL_DATABASE dans docker-compose

try {
    // Tentative de connexion à la base spécifiée
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la base n'existe pas, on se connecte sans base puis on la crée
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $pdo->exec("USE `$dbname`");
    } catch (Exception $e2) {
        die("Erreur DB: " . $e2->getMessage());
    }
}

// Création des tables (inchangée, mais on peut la garder telle quelle)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        role ENUM('admin','enseignant','etudiant'),
        name_fr VARCHAR(100),
        enabled TINYINT DEFAULT 1,
        locked TINYINT DEFAULT 0,
        failed_attempts INT DEFAULT 0,
        last_login DATETIME DEFAULT NULL,
        telephone VARCHAR(20),
        photo VARCHAR(255)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255),
        ip VARCHAR(45),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etudiants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        massar VARCHAR(20) UNIQUE,
        cin VARCHAR(20) UNIQUE,
        nom VARCHAR(50),
        prenom VARCHAR(50),
        nom_ar VARCHAR(50),
        prenom_ar VARCHAR(50),
        email VARCHAR(100),
        telephone VARCHAR(20),
        date_naissance DATE,
        niveau VARCHAR(10),
        deleted_at DATETIME DEFAULT NULL 
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS filieres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alias VARCHAR(10),
        nom VARCHAR(100),
        accreditation_debut YEAR,
        accreditation_fin YEAR,
        coordonnateur_id INT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filiere_id INT,
        nom VARCHAR(50),
        FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filiere_id INT,
        code VARCHAR(20),
        titre VARCHAR(100),
        niveau VARCHAR(5),
        FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS elements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_id INT,
        titre VARCHAR(100),
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS absences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        etudiant_id INT,
        element_id INT,
        date_absence DATE,
        type_absence ENUM('Cours','TD','TP','Visite'),
        statut ENUM('non_justifiee','justifiee','annulee') DEFAULT 'non_justifiee',
        justificatif_fichier VARCHAR(255),
        enseignant_id INT,
        date_saisie DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
        FOREIGN KEY (element_id) REFERENCES elements(id) ON DELETE CASCADE,
        FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reclamations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        absence_id INT,
        etudiant_id INT,
        message TEXT,
        statut ENUM('en_cours','traitee','refusee') DEFAULT 'en_cours',
        reponse TEXT,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (absence_id) REFERENCES absences(id) ON DELETE CASCADE,
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        etudiant_id INT,
        date_demande DATE,
        motif TEXT,
        statut ENUM('en_cours','ok','non') DEFAULT 'en_cours',
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
    )");

    // ========== PEUPLEMENT AUTOMATIQUE ==========
    // 1. Comptes admin, enseignant, et un étudiant de démo (si vide)
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUsers == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $profPass = password_hash('prof123', PASSWORD_DEFAULT);
        $etudiantPass = password_hash('etudiant123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (login, password, role, name_fr) VALUES
            ('admin', '$adminPass', 'admin', 'Administrateur'),
            ('prof1', '$profPass', 'enseignant', 'Professeur Anouar'),
            ('etudiant1', '$etudiantPass', 'etudiant', 'Étudiant Test')");
    }

    // 2. Structure pédagogique (filière, classes, modules, éléments)
    $checkFilieres = $pdo->query("SELECT COUNT(*) FROM filieres")->fetchColumn();
    if ($checkFilieres == 0) {
        $profId = $pdo->query("SELECT id FROM users WHERE login='prof1'")->fetchColumn();
        $pdo->exec("INSERT INTO filieres (alias, nom, accreditation_debut, accreditation_fin, coordonnateur_id)
                    VALUES ('GI', 'Génie Informatique', 2024, 2029, $profId)");
        $filiereId = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO classes (filiere_id, nom) VALUES ($filiereId, 'GI1-A'), ($filiereId, 'GI2-A')");
        $pdo->exec("INSERT INTO modules (filiere_id, code, titre, niveau) VALUES
            ($filiereId, 'INF101', 'Algorithmique & Programmation', 'S1'),
            ($filiereId, 'WEB201', 'Développement Web Avancé', 'S3')");
        $mod1 = $pdo->query("SELECT id FROM modules WHERE code='INF101'")->fetchColumn();
        $mod2 = $pdo->query("SELECT id FROM modules WHERE code='WEB201'")->fetchColumn();
        $pdo->exec("INSERT INTO elements (module_id, titre) VALUES
            ($mod1, 'C/C++'), ($mod1, 'Java'),
            ($mod2, 'HTML/CSS'), ($mod2, 'JavaScript')");
    }

    // 3. Création de 30 étudiants et de leurs comptes utilisateurs (login = email)
    $checkEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
    if ($checkEtudiants < 10) {
        $noms = ['Benali', 'El Mansouri', 'Idrissi', 'Chraibi', 'Fadel', 'Ziani', 'Rami', 'Tazi', 'Mernissi', 'Berrada', 'Kabbaj', 'Sefrioui', 'Ouazzani', 'Filali', 'Benjelloun', 'Lahlou', 'Belkacem', 'Ait El Cadi', 'Bahassou', 'Ghandour', 'Haddad', 'Khalil', 'Naciri', 'Benchekroun', 'Zaoui', 'Fikri', 'Bouchta', 'El Fassi', 'Mekki', 'Lamrani'];
        $prenoms = ['Mohamed', 'Ahmed', 'Yasmine', 'Sofia', 'Yassine', 'Karim', 'Nadia', 'Omar', 'Leila', 'Hamza', 'Imane', 'Mehdi', 'Salma', 'Amine', 'Hind', 'Rachid', 'Najwa', 'Said', 'Meryem', 'Fouad', 'Kenza', 'Adil', 'Soukaina', 'Anas', 'Asmae', 'Hicham', 'Ghita', 'Reda', 'Mona', 'Taha'];
        $niveaux = ['GI1', 'GI2'];
        $defaultPassword = password_hash('etudiant123', PASSWORD_DEFAULT);
        
        $insertEtudiant = $pdo->prepare("INSERT INTO etudiants (massar, cin, nom, prenom, email, telephone, date_naissance, niveau) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertUser = $pdo->prepare("INSERT IGNORE INTO users (login, password, role, name_fr, enabled) VALUES (?, ?, 'etudiant', ?, 1)");
        
        for ($i = 0; $i < 30; $i++) {
            $nom = $noms[array_rand($noms)];
            $prenom = $prenoms[array_rand($prenoms)];
            $massar = 'N' . rand(100000000, 999999999);
            $cin = strtoupper(substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 2)) . rand(100000, 999999);
            $email = strtolower($prenom . '.' . $nom . '@ensah.ma');
            $tel = '06' . rand(10000000, 99999999);
            $dateNaiss = date('Y-m-d', mktime(0,0,0, rand(1,12), rand(1,28), rand(1995, 2005)));
            $niveau = $niveaux[array_rand($niveaux)];
            
            $insertEtudiant->execute([$massar, $cin, $nom, $prenom, $email, $tel, $dateNaiss, $niveau]);
            $login = $email;   // login = email complet
            $name = $prenom . ' ' . $nom;
            $insertUser->execute([$login, $defaultPassword, $name]);
        }
    }

    // 4. Création d'absences de test (si aucune absence)
    $checkAbsences = $pdo->query("SELECT COUNT(*) FROM absences")->fetchColumn();
    if ($checkAbsences == 0) {
        $profId = $pdo->query("SELECT id FROM users WHERE login='prof1'")->fetchColumn();
        $etudiants = $pdo->query("SELECT id FROM etudiants")->fetchAll(PDO::FETCH_COLUMN);
        $elements = $pdo->query("SELECT id FROM elements")->fetchAll(PDO::FETCH_COLUMN);
        $types = ['Cours', 'TD', 'TP', 'Visite'];
        $insertAbs = $pdo->prepare("INSERT INTO absences (etudiant_id, element_id, date_absence, type_absence, statut, enseignant_id) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($etudiants as $eId) {
            if (rand(1, 100) <= 30) {
                $nb = rand(1, 2);
                for ($i = 0; $i < $nb; $i++) {
                    $elemId = $elements[array_rand($elements)];
                    $date = date('Y-m-d', strtotime('-' . rand(1, 60) . ' days'));
                    $type = $types[array_rand($types)];
                    $statut = (rand(1, 10) <= 7) ? 'non_justifiee' : 'justifiee';
                    $insertAbs->execute([$eId, $elemId, $date, $type, $statut, $profId]);
                }
            }
        }
    }

} catch (PDOException $e) {
    die("Erreur lors de la création ou du peuplement des tables : " . $e->getMessage());
}
?>
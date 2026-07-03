<?php
if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");

$error = '';
$success = '';
$person = null;
$generatedLogin = '';
$generatedPassword = '';

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) die("Utilisateur introuvable.");
} else {
    $user = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = trim($_POST['search_term']);
        $searchType = $_POST['search_type'];
        if ($searchType === 'cin') {
            $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE cin = ?");
            $stmt->execute([$searchTerm]);
            $person = $stmt->fetch();
            if (!$person) $error = "Aucun étudiant trouvé avec ce CIN.";
        } elseif ($searchType === 'massar') {
            $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE massar = ?");
            $stmt->execute([$searchTerm]);
            $person = $stmt->fetch();
            if (!$person) $error = "Aucun étudiant trouvé avec ce code Massar.";
        }
        if ($person) {
            $baseLogin = strtolower($person['prenom'] . '.' . $person['nom']);
            $login = $baseLogin;
            $counter = 1;
            $check = $pdo->prepare("SELECT id FROM users WHERE login = ?");
            $check->execute([$login]);
            while ($check->fetch()) {
                $login = $baseLogin . $counter;
                $counter++;
                $check->execute([$login]);
            }
            $generatedLogin = $login;
            $generatedPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (login, password, role, name_fr, enabled) VALUES (?, ?, 'etudiant', ?, 1)");
            $stmt->execute([$login, $hashedPassword, $person['prenom'] . ' ' . $person['nom']]);
            $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
            $log->execute([$_SESSION['user']['id'], "Création compte étudiant $login", $_SERVER['REMOTE_ADDR']]);
            $success = "Compte créé avec succès. Login : $login, Mot de passe : $generatedPassword";
        }
    } elseif (isset($_POST['create_manual'])) {
        $login = trim($_POST['login']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $name = trim($_POST['name']);
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password, role, name_fr, enabled) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$login, $hashed, $role, $name]);
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user']['id'], "Création manuelle compte $login", $_SERVER['REMOTE_ADDR']]);
        $success = "Compte créé avec succès. Login : $login, Mot de passe : $password";
    }
}
?>
<div class="card">
    <div class="card-header bg-primary text-white">Créer un nouveau compte</div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= nl2br(htmlspecialchars($success)) ?></div>
        <?php endif; ?>

        <div class="mb-4 p-3 border rounded">
            <h5><i class="fas fa-user-graduate"></i> Créer un compte étudiant à partir de la base étudiants</h5>
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-3">
                        <select name="search_type" class="form-select">
                            <option value="cin">Recherche par CIN</option>
                            <option value="massar">Recherche par Massar</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="search_term" class="form-control" placeholder="Saisir la valeur" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="search" class="btn btn-primary w-100">Rechercher et créer</button>
                    </div>
                </div>
            </form>
            <?php if ($generatedLogin): ?>
                <div class="alert alert-info mt-3">
                    <strong>Compte généré automatiquement :</strong><br>
                    Login : <?= $generatedLogin ?><br>
                    Mot de passe : <?= $generatedPassword ?>
                </div>
            <?php endif; ?>
        </div>

        <hr>

        <div class="p-3 border rounded">
            <h5><i class="fas fa-user-plus"></i> Création manuelle (Admin, Enseignant)</h5>
            <form method="POST">
                <div class="mb-3">
                    <label>Login</label>
                    <input type="text" name="login" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Nom complet</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Rôle</label>
                    <select name="role" class="form-select">
                        <option value="admin">Administrateur</option>
                        <option value="enseignant">Enseignant</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Mot de passe</label>
                    <input type="text" name="password" class="form-control" required>
                </div>
                <button type="submit" name="create_manual" class="btn btn-success">Créer manuellement</button>
                <a href="?page=accounts" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
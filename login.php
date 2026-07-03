<?php
// login.php
session_start(); // Démarrer la session avant tout
require_once __DIR__ . '/config/db.php'; // Pour avoir $pdo

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['locked']) {
            $error = "Compte verrouillé. Contactez l'administrateur.";
        } elseif (!$user['enabled']) {
            $error = "Compte désactivé.";
        } elseif (password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user'] = $user;

            $update = $pdo->prepare("UPDATE users SET failed_attempts = 0, last_login = NOW() WHERE id = ?");
            $update->execute([$user['id']]);

            $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, 'Connexion réussie', ?)");
            $log->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

            // Redirection AVANT d'inclure le header
            header('Location: index.php');
            exit;
        } else {
            $newAttempts = $user['failed_attempts'] + 1;
            $locked = ($newAttempts >= 3) ? 1 : 0;
            $update = $pdo->prepare("UPDATE users SET failed_attempts = ?, locked = ? WHERE id = ?");
            $update->execute([$newAttempts, $locked, $user['id']]);
            $error = "Identifiants incorrects. Tentative $newAttempts/3.";
        }
    } else {
        $error = "Utilisateur inconnu.";
    }
}

// *** MAINTENANT, on inclut le header (après tout le traitement) ***
$pageTitle = "Connexion";
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-unlock-alt"></i> Authentification ENSAH
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label>Login</label>
                        <input type="text" name="login" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label>Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
                <div class="mt-3 text-muted small">
                    <strong>Compte démo :</strong> <br>
                    <h6>admin/admin123</h6><br>
                    <h6>prof1/prof123</h6><br>
                    <h6>Etudiant: amine.el mansouri@ensah.ma/etudiant123</h6><br>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
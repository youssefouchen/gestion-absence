<?php
// index.php
$page = $_GET['page'] ?? 'dashboard';
$pageTitle = "Tableau de Bord";

require_once 'includes/header.php';

// Protection des routes selon le rôle
if ($page === 'comptes' && $_SESSION['user']['role'] !== 'admin') {
    die("Accès interdit.");
}

?>

<h2 class="mb-4"><?= htmlspecialchars(ucfirst($page)) ?></h2>

<?php
switch ($page) {
    case 'dashboard':
        echo "<div class='alert alert-info'>Bienvenue sur l'application de gestion des absences.</div>";
        // Ici: Afficher les stats (nombre d'étudiants, absences du jour, etc.)
        include 'views/dashboard.php';
        break;

    case 'students':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        include 'views/students_list.php'; // Créer ce fichier plus tard
        break;

    case 'saisie':
        if ($_SESSION['user']['role'] !== 'enseignant') die("Accès interdit");
        include 'views/saisie_absence.php'; // Créer ce fichier plus tard
        break;
    case 'student_form':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/student_form.php';
    break;
    case 'accounts':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/accounts.php';
    break;
case 'structure':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/structure.php';
    break;
case 'logs':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/logs.php';
    break;
    case 'absences':
    // Autoriser admin, enseignant et étudiant (chacun verra les absences selon ses droits dans la vue)
    if (!in_array($_SESSION['user']['role'], ['admin', 'enseignant', 'etudiant'])) {
        die("Accès interdit");
    }
    include 'views/absences.php';
    break;
    case 'add_filiere':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/add_filiere.php';
    break;
    case 'add_classe':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        include 'views/add_classe.php';
        break;
    case 'add_module':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        include 'views/add_module.php';
        break;
    case 'add_element':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        include 'views/add_element.php';
    break;
    case 'requests':      include 'views/requests.php'; break;
    case 'profile':       include 'views/profile.php'; break; 
    case 'my_absences':
    // Autoriser enseignant (ses saisies) et étudiant (ses absences)
    if (!in_array($_SESSION['user']['role'], ['enseignant', 'etudiant'])) {
        die("Accès interdit");
    }
    include 'views/my_absences.php';
    break; 
    case 'ask_permission':
    if ($_SESSION['user']['role'] !== 'etudiant') die("Accès interdit");
    include 'views/ask_permission.php';
    break; 
    case 'my_requests':
    if ($_SESSION['user']['role'] !== 'etudiant') die("Accès interdit");
    include 'views/my_requests.php';
    break;
    case 'restore_students':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/restore_students.php';
    break;
    case 'import_structure':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/import_structure.php';
    break;
    case 'user_actions':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/user_actions.php';
    break;
    case 'account_form':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    include 'views/account_form.php';
    break;
    case 'justify_absence':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE absences SET statut = 'justifiee' WHERE id = ?");
    $stmt->execute([$id]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Justification de l'absence ID $id", $_SERVER['REMOTE_ADDR']]);
    header("Location: ?page=absences");
    exit;
    break;

case 'unjustify_absence':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE absences SET statut = 'non_justifiee' WHERE id = ?");
    $stmt->execute([$id]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Retrait de justification de l'absence ID $id", $_SERVER['REMOTE_ADDR']]);
    header("Location: ?page=absences");
    exit;
    break;

case 'annuler_absence':
    if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE absences SET statut = 'annulee' WHERE id = ?");
    $stmt->execute([$id]);
    $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user']['id'], "Annulation de l'absence ID $id", $_SERVER['REMOTE_ADDR']]);
    header("Location: ?page=absences");
    exit;
    break;
        case 'delete_filiere':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        $id = intval($_GET['id']);
        $pdo->prepare("DELETE FROM filieres WHERE id = ?")->execute([$id]);
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user']['id'], "Suppression filière ID $id", $_SERVER['REMOTE_ADDR']]);
        header("Location: ?page=structure");
        exit;
        break;

    case 'delete_classe':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        $id = intval($_GET['id']);
        $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user']['id'], "Suppression classe ID $id", $_SERVER['REMOTE_ADDR']]);
        header("Location: ?page=structure");
        exit;
        break;

    case 'delete_module':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        $id = intval($_GET['id']);
        $pdo->prepare("DELETE FROM modules WHERE id = ?")->execute([$id]);
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user']['id'], "Suppression module ID $id", $_SERVER['REMOTE_ADDR']]);
        header("Location: ?page=structure");
        exit;
        break;

    case 'delete_element':
        if ($_SESSION['user']['role'] !== 'admin') die("Accès interdit");
        $id = intval($_GET['id']);
        $pdo->prepare("DELETE FROM elements WHERE id = ?")->execute([$id]);
        $log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
        $log->execute([$_SESSION['user']['id'], "Suppression élément ID $id", $_SERVER['REMOTE_ADDR']]);
        header("Location: ?page=structure");
        exit;
        break;
    default:
        echo "<p>Page en construction ou accès non autorisé.</p>";
}
?>

<?php require_once 'includes/footer.php'; ?>
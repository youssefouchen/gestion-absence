<?php
session_start();
require_once 'config/db.php';
if ($_SESSION['user']['role'] !== 'enseignant') die;

$element_id = $_POST['element_id'];
$type = $_POST['type'];
$date = $_POST['date'];
$absents = explode(',', $_POST['absents']);
$enseignant_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("INSERT INTO absences (etudiant_id, element_id, date_absence, type_absence, enseignant_id) VALUES (?, ?, ?, ?, ?)");
foreach ($absents as $etudiant_id) {
    $stmt->execute([$etudiant_id, $element_id, $date, $type, $enseignant_id]);
}
// Log
$log = $pdo->prepare("INSERT INTO logs (user_id, action, ip) VALUES (?, ?, ?)");
$log->execute([$enseignant_id, "Saisie de " . count($absents) . " absence(s)", $_SERVER['REMOTE_ADDR']]);

header('Location: index.php?page=saisie&success=1');


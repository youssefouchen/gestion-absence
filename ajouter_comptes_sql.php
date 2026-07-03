<?php
require_once 'config/db.php';

$etudiants = [
    ['ahmed.aitelcadi@ensah.ma', 'Ahmed Ait El Cadi'],
    ['reda.berrada@ensah.ma', 'Reda Berrada'],
    ['hind.idrissi@ensah.ma', 'Hind Idrissi'],
    ['kenza.sefrioui@ensah.ma', 'Kenza Sefrioui'],
    ['asmae.idrissi@ensah.ma', 'Asmae Idrissi'],
    ['said.bouchta@ensah.ma', 'Said Bouchta'],
    ['said.fadel@ensah.ma', 'Said Fadel'],
    ['karim.filali@ensah.ma', 'Karim Filali'],
    ['yassine.tazi@ensah.ma', 'Yassine Tazi'],
    ['najwa.fikri@ensah.ma', 'Najwa Fikri'],
    ['mohamed.ghandour@ensah.ma', 'Mohamed Ghandour'],
    ['rachid.fikri@ensah.ma', 'Rachid Fikri'],
    ['anas.naciri@ensah.ma', 'Anas Naciri'],
    ['soukaina.benali@ensah.ma', 'Soukaina Benali'],
    ['yasmine.elmansouri@ensah.ma', 'Yasmine El Mansouri'],
    ['sofia.chraibi@ensah.ma', 'Sofia Chraibi'],
    ['yassine.ziani@ensah.ma', 'Yassine Ziani'],
    ['karim.rami@ensah.ma', 'Karim Rami'],
    ['nadia.tazi@ensah.ma', 'Nadia Tazi'],
    ['omar.mernissi@ensah.ma', 'Omar Mernissi'],
    ['leila.berrada@ensah.ma', 'Leila Berrada'],
    ['hamza.kabbaj@ensah.ma', 'Hamza Kabbaj'],
    ['imane.sefrioui@ensah.ma', 'Imane Sefrioui'],
    ['mehdi.ouazzani@ensah.ma', 'Mehdi Ouazzani'],
    ['salma.filali@ensah.ma', 'Salma Filali'],
    ['amine.benjelloun@ensah.ma', 'Amine Benjelloun'],
    ['hind.lahlou@ensah.ma', 'Hind Lahlou'],
    ['rachid.belkacem@ensah.ma', 'Rachid Belkacem'],
    ['najwa.bahassou@ensah.ma', 'Najwa Bahassou'],
    ['meryem.haddad@ensah.ma', 'Meryem Haddad']
];

$hash = password_hash('etudiant123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (login, password, role, name_fr, enabled) VALUES (?, ?, 'etudiant', ?, 1)");

foreach ($etudiants as $e) {
    $stmt->execute([$e[0], $hash, $e[1]]);
    echo "Ajouté : " . $e[0] . "\n";
}
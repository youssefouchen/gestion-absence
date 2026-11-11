<?php
// 1. Indique au client (navigateur, Docker, Kubernetes) que la réponse est du JSON
header('Content-Type: application/json; charset=utf-8');

// 2. Renvoie le code HTTP 200 (OK), qui signifie que tout fonctionne normalement
http_response_code(200);

// 3. Construit le message de statut avec l'heure exacte du serveur (format ISO 8601)
$response = [
    'status' => 'ok',
    'service' => 'gestion-absence',
    'timestamp' => date('c')
];

// 4. Convertit le tableau PHP en texte JSON et l'affiche
echo json_encode($response);
exit;
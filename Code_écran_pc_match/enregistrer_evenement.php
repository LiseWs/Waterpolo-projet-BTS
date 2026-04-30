<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$temps = isset($data['temps_chrono']) && $data['temps_chrono'] !== '' ? $data['temps_chrono'] : null;

$stmt = $pdo->prepare('INSERT INTO evenement (id_matchs, id_joueur, id_equipe, type, details, temps) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $data['id_match'],
    $data['id_joueur'] ?? null,
    $data['id_equipe'] ?? null,
    $data['type_evenement'],
    isset($data['details']) ? $data['details'] : null,
    $temps
]);

echo json_encode(['success' => true]);

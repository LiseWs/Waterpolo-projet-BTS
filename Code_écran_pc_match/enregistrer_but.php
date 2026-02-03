<?php
require_once __DIR__ . '/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'root', '');
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

// On met id_periode à 1 par défaut si non fourni
$stmt = $pdo->prepare('INSERT INTO but (temps, id_joueur, id_matchs, id_periode, id_equipe) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([
    $data['temps_chrono'] ?? null,
    $data['id_joueur'],
    $data['id_match'],
    $data['id_periode'] ?? 1,
    $data['id_equipe']
]);

echo json_encode(['success' => true]); 
/**
 * ENREGISTREMENT D'ÉVÉNEMENTS DE WATERPOLO
 * Ce script gère l'enregistrement des événements de match dans la base de données
 * Types d'événements supportés :
 * - Buts
 * - Exclusions
 * - Cartons (jaunes/rouges)
 * - Accidents
 * - Réclamations
 * - Temps morts
 * - Possession
 */

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
// valider $data['type'], id_joueur, id_matchs, etc. puis INSERT dans evenement

// Gestion du temps de chronomètre (NULL si vide)
$temps_chrono = isset($data['temps_chrono']) && $data['temps_chrono'] !== '' ? $data['temps_chrono'] : null;

// Préparation et exécution de la requête d'insertion
$stmt = $pdo->prepare('INSERT INTO evenement (id_matchs, id_joueur, id_equipe, type_evenement, details, temps_chrono, horodatage) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $data['id_match'],
    $data['id_joueur'],
    $data['id_equipe'],
    $data['type_evenement'],
    $data['details'] ?? null,
    $temps_chrono
]);

// Confirmation du succès de l'opération
echo json_encode(['success' => true]); 
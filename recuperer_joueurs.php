<?php
require_once __DIR__ . '/db.php';
header("Content-Type: application/json; charset=utf-8");
$pdo = getPDO();

session_start();
$matchId = $_SESSION['current_match_id'] ?? null;

if ($matchId) {
    // Récupère les équipes du match
    $stmt = $pdo->prepare("SELECT equipe1_id, equipe2_id FROM matchs WHERE id_match = ? LIMIT 1");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();
    if ($match) {
        $e1 = (int)$match['equipe1_id'];
        $e2 = (int)$match['equipe2_id'];
    } else {
        $e1 = 1; $e2 = 2;
    }
} else {
    $e1 = isset($_GET['equipe1']) ? (int)$_GET['equipe1'] : 1;
    $e2 = isset($_GET['equipe2']) ? (int)$_GET['equipe2'] : 2;
}

$req1 = $pdo->prepare("SELECT id_joueur, prenom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req1->execute([$e1]);
$joueurs1 = $req1->fetchAll();

$req2 = $pdo->prepare("SELECT id_joueur, prenom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req2->execute([$e2]);
$joueurs2 = $req2->fetchAll();

echo json_encode(['equipe1'=>$joueurs1,'equipe2'=>$joueurs2]);
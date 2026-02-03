<?php
require_once __DIR__ . '/db.php';
header("Content-Type: application/json; charset=utf-8");
$pdo = getPDO();

session_start();
// Allow explicit matchId in GET to override session (useful after redirect from setup)
$matchId = isset($_GET['matchId']) && is_numeric($_GET['matchId']) ? (int)$_GET['matchId'] : ($_SESSION['current_match_id'] ?? null);

if ($matchId) {
    // Récupère les équipes du match
    $stmt = $pdo->prepare("SELECT equipe1_id, equipe2_id FROM matchs WHERE id_match = ? LIMIT 1");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();
    if ($match) {
        $e1 = (int)$match['equipe1_id'];
        $e2 = (int)$match['equipe2_id'];
    } else {
        // invalid match, return empty sets and explicit matchId null
        echo json_encode(['equipe1'=>[], 'equipe2'=>[], 'error'=>'match_not_found','matchId'=>null]);
        exit;
    }
} else {
    // No matchId provided - do not invent teams, return empty
    echo json_encode(['equipe1'=>[], 'equipe2'=>[], 'matchId'=>null]);
    exit;
}

$req1 = $pdo->prepare("SELECT id_joueur, prenom_joueur, nom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req1->execute([$e1]);
$joueurs1 = $req1->fetchAll();

$req2 = $pdo->prepare("SELECT id_joueur, prenom_joueur, nom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req2->execute([$e2]);
$joueurs2 = $req2->fetchAll();

echo json_encode(['matchId' => $matchId, 'equipe1'=>$joueurs1,'equipe2'=>$joueurs2]);
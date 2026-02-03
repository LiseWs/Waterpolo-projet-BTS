<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Payload JSON manquant']);
    exit;
}

// validation minimale
$team1Name = trim($input['team1Name'] ?? '');
$team2Name = trim($input['team2Name'] ?? '');
$rules = $input['rules'] ?? [];
$team1Players = $input['team1Players'] ?? [];
$team2Players = $input['team2Players'] ?? [];

if ($team1Name === '' || $team2Name === '') {
    http_response_code(422);
    echo json_encode(['success'=>false,'error'=>'Noms des équipes requis']);
    exit;
}
if (!is_array($team1Players) || !is_array($team2Players) || count($team1Players) === 0 || count($team2Players) === 0) {
    http_response_code(422);
    echo json_encode(['success'=>false,'error'=>'Au moins un joueur par équipe requis']);
    exit;
}

// start transaction pour créer match et joueurs atomiquement
try {
    $pdo->beginTransaction();

    // 1) Créer / récupérer équipes (si existe, sinon insert)
    $stmt = $pdo->prepare("SELECT id_equipe FROM equipe WHERE nom = ? LIMIT 1");
    $stmt->execute([$team1Name]);
    $row = $stmt->fetch();
    if ($row) {
        $team1Id = (int)$row['id_equipe'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO equipe (nom, short_code) VALUES (?, ?)");
        $stmt->execute([$team1Name, null]);
        $team1Id = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT id_equipe FROM equipe WHERE nom = ? LIMIT 1");
    $stmt->execute([$team2Name]);
    $row = $stmt->fetch();
    if ($row) {
        $team2Id = (int)$row['id_equipe'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO equipe (nom, short_code) VALUES (?, ?)");
        $stmt->execute([$team2Name, null]);
        $team2Id = (int)$pdo->lastInsertId();
    }

    // 2) Créer l'entrée match
    $periodTime = (int)($rules['periodTime'] ?? 480);
    $possessionTime = (int)($rules['possessionTime'] ?? 30);
    $exclusionTime = (int)($rules['exclusionTime'] ?? 20);
    $maxTimeouts = (int)($rules['maxTimeouts'] ?? 2);

    $stmt = $pdo->prepare("INSERT INTO matchs (date_match, equipe1_id, equipe2_id, score_team1, score_team2, created_at) VALUES (NOW(), ?, ?, 0, 0, NOW())");
    $stmt->execute([$team1Id, $team2Id]);
    $matchId = (int)$pdo->lastInsertId();

    // 3) Insérer joueurs (liaison équipe)
    $insertPlayer = $pdo->prepare("INSERT INTO joueur (prenom_joueur, numero_bonnet, id_equipe, created_at) VALUES (?, ?, ?, NOW())");
    foreach ($team1Players as $p) {
        $name = trim($p['name'] ?? '');
        $number = isset($p['number']) ? (int)$p['number'] : null;
        if ($name === '') continue;
        $insertPlayer->execute([$name, $number, $team1Id]);
    }
    foreach ($team2Players as $p) {
        $name = trim($p['name'] ?? '');
        $number = isset($p['number']) ? (int)$p['number'] : null;
        if ($name === '') continue;
        $insertPlayer->execute([$name, $number, $team2Id]);
    }

    // 4) Optionnel : sauver les règles dans une table logs ou settings du match (ici on peut utiliser evenement/details JSON ou logs)
    // Exemple : stocker dans logs
    $stmt = $pdo->prepare("INSERT INTO logs (action, meta, created_at) VALUES (?, ?, NOW())");
    $meta = json_encode(['match_id'=>$matchId,'rules'=>['periodTime'=>$periodTime,'possessionTime'=>$possessionTime,'exclusionTime'=>$exclusionTime,'maxTimeouts'=>$maxTimeouts]]);
    $stmt->execute(['match_created',$meta]);

    $pdo->commit();

    // Tu peux stocker l'id du match en session pour que index.php charge ce match automatiquement
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['current_match_id'] = $matchId;
    $_SESSION['current_rules'] = ['periodTime'=>$periodTime,'possessionTime'=>$possessionTime,'exclusionTime'=>$exclusionTime,'maxTimeouts'=>$maxTimeouts];

    echo json_encode(['success'=>true,'match_id'=>$matchId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('save_match.php error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Erreur serveur lors de la création du match']);
}
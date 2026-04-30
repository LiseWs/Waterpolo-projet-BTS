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
    // Normaliser les règles reçues depuis match_setup.js
    $numberPeriods = (int)($rules['numberPeriods'] ?? 4);
    $periodMinutes = (int)($rules['periodMinutes'] ?? 8);
    $breakShortMinutes = (int)($rules['breakShortMinutes'] ?? 2);
    $halftimeMinutes = (int)($rules['halftimeMinutes'] ?? 5);
    $shotClock = (int)($rules['shotClock'] ?? 30);
    $recoveryClock = (int)($rules['recoveryClock'] ?? 20);
    $maxTimeouts = (int)($rules['maxTimeouts'] ?? 2);

    // Convertir en secondes pour les timers utilisés côté client
    $periodTime = $periodMinutes * 60;
    $possessionTime = $shotClock;            // shot clock en secondes
    $specialTime = $recoveryClock;           // chronomètre de reprise / récupération
    $exclusionTime = (int)($rules['exclusionTime'] ?? 20); // garder valeur par défaut si non fournie

    $stmt = $pdo->prepare("INSERT INTO matchs (date_match, equipe1_id, equipe2_id, score_team1, score_team2, created_at) VALUES (NOW(), ?, ?, 0, 0, NOW())");
    $stmt->execute([$team1Id, $team2Id]);
    $matchId = (int)$pdo->lastInsertId();

    // 3) Insérer joueurs (liaison équipe)
    // Split full name into prenom and nom when saving players so index.php can display prénom + nom
    $insertPlayer = $pdo->prepare("INSERT INTO joueur (prenom_joueur, nom_joueur, numero_bonnet, id_equipe, created_at) VALUES (?, ?, ?, ?, NOW())");
    foreach ($team1Players as $p) {
        $name = trim($p['name'] ?? '');
        $number = isset($p['number']) ? (int)$p['number'] : null;
        if ($name === '') continue;
        $parts = preg_split('/\s+/', $name);
        $prenom = array_shift($parts);
        $nom = count($parts) ? implode(' ', $parts) : null;
        $insertPlayer->execute([$prenom, $nom, $number, $team1Id]);
    }
    foreach ($team2Players as $p) {
        $name = trim($p['name'] ?? '');
        $number = isset($p['number']) ? (int)$p['number'] : null;
        if ($name === '') continue;
        $parts = preg_split('/\s+/', $name);
        $prenom = array_shift($parts);
        $nom = count($parts) ? implode(' ', $parts) : null;
        $insertPlayer->execute([$prenom, $nom, $number, $team2Id]);
    }

    // 4) Optionnel : sauver les règles dans une table logs ou settings du match (ici on peut utiliser evenement/details JSON ou logs)
    // Exemple : stocker dans logs
    // Sauvegarder les règles en métadonnées et en session pour que l'interface principale les utilise
    $stmt = $pdo->prepare("INSERT INTO logs (action, meta, created_at) VALUES (?, ?, NOW())");
    $meta = json_encode([
        'match_id' => $matchId,
        'rules' => [
            'numberPeriods' => $numberPeriods,
            'periodMinutes' => $periodMinutes,
            'periodTime_seconds' => $periodTime,
            'breakShortMinutes' => $breakShortMinutes,
            'halftimeMinutes' => $halftimeMinutes,
            'shotClock' => $shotClock,
            'recoveryClock' => $recoveryClock,
            'possessionTime' => $possessionTime,
            'specialTime' => $specialTime,
            'exclusionTime' => $exclusionTime,
            'maxTimeouts' => $maxTimeouts
        ]
    ]);
    $stmt->execute(['match_created',$meta]);

    $pdo->commit();

    // Stocker l'id du match et les règles en session pour que index.php charge ce match automatiquement
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['current_match_id'] = $matchId;
    $_SESSION['current_rules'] = [
        'PERIOD_TIME' => $periodTime,
        'POSSESSION_TIME' => $possessionTime,
        'SPECIAL_TIME' => $specialTime,
        'EXCLUSION_TIME' => $exclusionTime,
        'MAX_PERIODS' => $numberPeriods,
        'BREAK_SHORT_MINUTES' => $breakShortMinutes,
        'HALFTIME_MINUTES' => $halftimeMinutes,
        'MAX_TIMEOUTS' => $maxTimeouts
    ];
    // Message flash pour l'utilisateur (sera consommé par match_setup.php ou index.php après redirection)
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Match créé avec succès — vous pouvez accéder à l\'interface d\'arbitrage.'];
    // Initialise un fichier d'état pour la synchronisation en temps réel
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
    $state = [
        'updated_at' => time(),
        'state' => [
            'scoreTeam1' => 0,
            'scoreTeam2' => 0,
            'mainTimer' => $periodTime,
            'possessionTimer' => $possessionTime,
            'period' => 1,
            'possessionMode' => 'normal',
            'timeoutsTeam1' => 0,
            'timeoutsTeam2' => 0,
            'team1Name' => $pdo->quote($team1Name),
            'team2Name' => $pdo->quote($team2Name)
        ]
    ];
    // Remove PHP quoting wrappers for JSON (we used PDO->quote for safety) - normalize names
    $state['state']['team1Name'] = trim($team1Name);
    $state['state']['team2Name'] = trim($team2Name);
    file_put_contents($dataDir . '/match_' . $matchId . '.json', json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['success'=>true,'match_id'=>$matchId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('save_match.php error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Erreur serveur lors de la création du match']);
}
<?php
// API endpoint: get_game_state.php
// GET param: matchId (optional). If absent, use session current match.
header('Content-Type: application/json; charset=utf-8');
session_start();
$matchId = isset($_GET['matchId']) ? (int)$_GET['matchId'] : (int)($_SESSION['current_match_id'] ?? 0);
$dir = __DIR__ . '/../data';
$file = $dir . '/match_' . $matchId . '.json';
$dbPart = ['team1Name' => null, 'team2Name' => null];
if ($matchId) {
    // si base disponible, essayer de récupérer les noms d'équipe
    try {
        require_once __DIR__ . '/../db.php';
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT equipe1_id, equipe2_id FROM matchs WHERE id_match = ? LIMIT 1');
        $stmt->execute([$matchId]);
        $m = $stmt->fetch();
        if ($m) {
            $e1 = (int)$m['equipe1_id'];
            $e2 = (int)$m['equipe2_id'];
            $s = $pdo->prepare('SELECT id_equipe, nom FROM equipe WHERE id_equipe IN (?,?)');
            $s->execute([$e1,$e2]);
            $rows = $s->fetchAll();
            foreach ($rows as $r) {
                if ((int)$r['id_equipe'] === $e1) $dbPart['team1Name'] = $r['nom'];
                if ((int)$r['id_equipe'] === $e2) $dbPart['team2Name'] = $r['nom'];
            }
        }
    } catch (Exception $e) { /* ignore db errors */ }
}

if ($matchId && file_exists($file)) {
    $raw = file_get_contents($file);
    $data = json_decode($raw, true) ?: [];
    // injecter les noms si non présents
    if (!isset($data['state']['team1Name']) && $dbPart['team1Name']) $data['state']['team1Name'] = $dbPart['team1Name'];
    if (!isset($data['state']['team2Name']) && $dbPart['team2Name']) $data['state']['team2Name'] = $dbPart['team2Name'];

    // Indiquer explicitement le match présent
    $data['matchId'] = $matchId;

    echo json_encode($data);
    exit;
}
// Retourner un default minimal si introuvable
$defaults = [
    'updated_at' => time(),
    'state' => [
        'scoreTeam1' => 0,
        'scoreTeam2' => 0,
        'mainTimer' => 480,
        'possessionTimer' => 30,
        'period' => 1,
        'possessionMode' => 'normal',
        'timeoutsTeam1' => 0,
        'timeoutsTeam2' => 0,
        'team1Name' => $dbPart['team1Name'],
        'team2Name' => $dbPart['team2Name']
    ],
    'matchId' => null
];
echo json_encode($defaults);

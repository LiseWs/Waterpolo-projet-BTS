<?php
// API endpoint: update_game_state.php
// Reçoit POST JSON { matchId: int, state: {...} } et écrit dans data/match_{id}.json
header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input || !isset($input['matchId']) || !isset($input['state'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payload manquant']);
    exit;
}
$matchId = (int)$input['matchId'];
$state = $input['state'];
$dir = __DIR__ . '/../data';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$file = $dir . '/match_' . $matchId . '.json';

// Read existing state (if any) to prevent overwriting configuration fields
$existing = null;
if (file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true) ?: null;
}
$existingState = $existing['state'] ?? [];

// Disallowed keys that should only be set via match_setup.php
$blockedKeys = ['team1Name','team2Name','team1Logo','team2Logo','equipe1','equipe2','players'];

// Merge states: incoming runtime fields override existing ones, but blocked keys keep existing values
$finalState = array_merge($existingState, $state);
foreach ($blockedKeys as $k) {
    if (array_key_exists($k, $existingState)) {
        $finalState[$k] = $existingState[$k];
    } else {
        unset($finalState[$k]);
    }
}

$payload = [
    'updated_at' => time(),
    'state' => $finalState
];
if (file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible d\'écrire le fichier']);
    exit;
}
echo json_encode(['success' => true]);

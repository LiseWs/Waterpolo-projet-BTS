<?php
require_once __DIR__ . '/db.php';
header("Content-Type: application/json");
$host = 'localhost';
$dbname = 'site_waterpolo';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Détection des équipes via GET
$id_equipe1 = isset($_GET['equipe1']) ? (int)$_GET['equipe1'] : 1;
$id_equipe2 = isset($_GET['equipe2']) ? (int)$_GET['equipe2'] : 2;

    // Récupération des joueurs de l'équipe 1
    $req1 = $pdo->prepare("SELECT id_joueur, prenom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req1->execute([$id_equipe1]);
    $joueurs1 = $req1->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des joueurs de l'équipe 2
    $req2 = $pdo->prepare("SELECT id_joueur, prenom_joueur, numero_bonnet FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet");
$req2->execute([$id_equipe2]);
    $joueurs2 = $req2->fetchAll(PDO::FETCH_ASSOC);

    // Envoi des données au format JSON
echo json_encode([
    'equipe1' => $joueurs1,
    'equipe2' => $joueurs2
]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => "Erreur de connexion : " . $e->getMessage()
    ]);
}
?>
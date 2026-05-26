<?php
// Connexion à la base de données
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'testeeee';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Vérification de l'upload du fichier
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    // Lire les données générales du match
    $header = fgetcsv($handle, 1000, ';');

    // Insérer les informations générales dans la table feuille_de_match
    $sql = "INSERT INTO feuille_de_match (competition, lieu, date_match, heure, equipe_1_nom, equipe_2_nom)
            VALUES (:competition, :lieu, :date_match, :heure, :equipe_1_nom, :equipe_2_nom)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':competition' => 'Championnat de France N3 Occitanie',
        ':lieu' => 'ALBI',
        ':date_match' => '2025-05-12',
        ':heure' => '15:00',
        ':equipe_1_nom' => 'SEA SUN POLO',
        ':equipe_2_nom' => 'STADE TOULOUSAIN'
    ]);

    $match_id = $pdo->lastInsertId();

    // Lire et insérer les joueurs (Équipe 1 et Équipe 2)
    while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
        if (is_numeric($data[0])) { // Identifier les lignes joueurs
            $sql = "INSERT INTO joueurs_equipe_1 (match_id, iuf, nom_prenom, naissance, numero, buts)
                    VALUES (:match_id, :iuf, :nom, :naissance, :numero, :buts)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':match_id' => $match_id,
                ':iuf' => $data[0],
                ':nom' => $data[1],
                ':naissance' => $data[12],
                ':numero' => $data[14],
                ':buts' => $data[15]
            ]);
        }
    }

    fclose($handle);
    echo "Données importées avec succès.";
} else {
    echo "Erreur lors de l'upload du fichier CSV.";
}
?>

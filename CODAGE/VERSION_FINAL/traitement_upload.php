<?php

require 'vendor/autoload.php'; // PHPSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user'])) {
    die('Accès refusé. Veuillez vous connecter.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['matchFile'])) {
    if ($_FILES['matchFile']['error'] === 0 && pathinfo($_FILES['matchFile']['name'], PATHINFO_EXTENSION) === 'xlsx') {
        $fileTmpPath = $_FILES['matchFile']['tmp_name'];
        $spreadsheet = IOFactory::load($fileTmpPath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Connexion à la base de données
        $pdo = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'odd', 'odd');

        // Lecture des données
        $equipe_domicile = trim($data[28][2]); // Aigle royal
        $score_domicile = (int)$data[2][30];
        $equipe_visiteur = trim($data[2][2]);  // Lion
        $score_visiteur = (int)$data[4][30];
        $date_match = date('Y-m-d', strtotime($data[4][34]));
        $heure_match = $data[4][39];
        $nom_arbitre = trim($data[42][36]);

        // Récupérer les IDs d'équipe
        $stmtID = $pdo->prepare("SELECT id_equipe FROM Equipe WHERE nom_equipe = ?");
        $stmtID->execute([$equipe_domicile]);
        $id_dom = $stmtID->fetchColumn();

        $stmtID->execute([$equipe_visiteur]);
        $id_vis = $stmtID->fetchColumn();

        if (!$id_dom || !$id_vis) {
            die("Erreur : l'une des équipes n'existe pas dans la base.");
        }

        $id_championnat = 1;
        $id_structure = 1;
        
        //$id_arbitre = 1; pas obligatoire car mode dynamique 

        //Vérification dans la BDD pour le championnat
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM championnat WHERE id_championnat = ?");
        $stmtCheck->execute([$id_championnat]);
        if ($stmtCheck->fetchColumn() == 0) {
            die("Erreur : ce championnat n'existe pas dans la BDD.");
        }


        $stmtArbitre = $pdo->prepare("SELECT id_arbitre FROM arbitre WHERE nom_arbitre = ?");
        $stmtArbitre->execute([$nom_arbitre]);
        $id_arbitre = $stmtArbitre->fetchColumn();

        if (!$id_arbitre) {
        die("Erreur : l'arbitre '$nom_arbitre' n'existe pas dans la base.");
        }


        // Insertion du match
        $sql = "INSERT INTO Matchs (
            id_equipe_domicile, id_equipe_visiteur, date_matchs, heure_matchs, id_championnat, id_structure, id_arbitre)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_dom,
            $id_vis,
            $date_match,
            $heure_match,
            $id_championnat,
            $id_structure,
            $id_arbitre
        ]);

        $match_id = $pdo->lastInsertId();

        // Insertion des buts
        $stmtBut = $pdo->prepare("INSERT INTO But (id_equipe, id_matchs) VALUES (?, ?)");
        for ($i = 0; $i < $score_domicile; $i++) {
            $stmtBut->execute([$id_dom, $match_id]);
        }
        for ($i = 0; $i < $score_visiteur; $i++) {
            $stmtBut->execute([$id_vis, $match_id]);
        }
        
        echo "<div class='message-success'>Importation réussie ✅</div>";
    } else {
        echo "Fichier invalide. Veuillez fournir un fichier .xlsx.";
    }
} else {
    echo "Méthode invalide ou fichier manquant.";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Importation réussi</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="js/bootstrap.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
</head>
<body>
<section>
    <div class="wrapper">
    <nav>
      <ul>
        <li class="bouton"><a href="index1.php">Résultat</a></li> <!-- Lien vers la page des résultats -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li> <!-- Lien vers la page du affichage feuille de match -->
        <li class="bouton"><a href="upload_match.php">Upload feuille de match dans la BDD</a></li> <!-- Upload de la feuille de match sur la BDD -->
      </ul>
    </nav> 
<style>
body {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

</style>
</section>




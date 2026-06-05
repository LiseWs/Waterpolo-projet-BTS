<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Upload feuille de match</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="js/bootstrap.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
</head>
<body>

    <nav>
      <ul>
        
       <li class="bouton"><a href="index1.php">Résultats </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="gestion_championnats.php">Gestion Championnat</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="classement.php">Classement Championnat</a></li> <!-- Upload de la feuille de match sur la BDD -->       
        <li class="bouton"><a href="affichage_feuille_match_bdd.php">Afficher la feuille</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <?php if (isset($_SESSION['user'])): ?>
            <li class="bouton"><a href="logout.php">Se déconnecter</a></li>
        <?php else: ?>
            <li><a href="login.php">Se connecter</a></li>
        <?php endif; ?>
      </ul>
      <form action="import_feuille_match.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="xlsx_file" accept=".xlsx">
    <button type="submit">Importer</button>
  </form>

    </nav> 
  
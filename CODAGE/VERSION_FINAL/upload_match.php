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
        
        <li class="bouton"><a href="index1.php">Résultat</a></li> <!-- Lien vers la page des résultats -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li> <!-- Lien vers la page du affichage feuille de match -->

        <?php if (isset($_SESSION['user'])): ?>
            <li class="bouton"><a href="logout.php">Se déconnecter</a></li>
        <?php else: ?>
            <li><a href="login.php">Se connecter</a></li>
        <?php endif; ?>



      </ul>
    </nav> 
    <form action="traitement_upload.php" method="post" enctype="multipart/form-data">
  <label for="matchFile">Importer une feuille de match Excel :</label>
  <input type="file" name="matchFile" id="matchFile" accept=".xlsx">
  <input type="submit" value="Envoyer">
    </form>
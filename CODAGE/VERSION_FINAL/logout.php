<?php
session_start();
session_unset(); // Supprime toutes les variables de session
session_destroy(); // Détruit la session

// Redirection vers la page de connexion ou d'accueil
header('Location: login.php'); // Change vers index.php si besoin
exit;
?>
<a href="logout.php">Se déconnecter</a>
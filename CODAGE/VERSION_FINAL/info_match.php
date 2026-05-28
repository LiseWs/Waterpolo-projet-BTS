<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Championnat waterpolo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/bootstrap.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/bootstrap.js"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
</head>
<body>
<?php
try {
    $mysqlClient = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'odd', 'odd');
    // Essaie de se connecter à une base de données MySQL avec PDO
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
    // En cas d'erreur de connexion, affiche un message d'erreur et arrête le script
}

if (isset($_GET['id_match'])) {
    $id_match = $_GET['id_match'];
    // Vérifie si l'ID du match est passé en paramètre GET

    $sqlQuery = "SELECT 
        matchs.id_equipe_domicile,
        matchs.id_equipe_visiteur,
        date_matchs,
        heure_matchs,
        visiteur.nom_equipe  AS equipe_visiteur,
        domicile.nom_equipe  AS equipe_domicile,
        domicile.logo_equipe AS logo_domicile,
        visiteur.logo_equipe AS logo_visiteur,
        matchs.score_domicile AS buts_domicile,
        matchs.score_visiteur AS buts_visiteur,
        structure.nom_structure,
        structure.lieu_structure
    FROM matchs
    INNER JOIN equipe    AS domicile ON matchs.id_equipe_domicile = domicile.id_equipe
    INNER JOIN equipe    AS visiteur ON matchs.id_equipe_visiteur = visiteur.id_equipe
    INNER JOIN structure             ON matchs.id_structure       = structure.id_structure
    WHERE matchs.id_matchs = :id_match";

    // Requête SQL pour récupérer les détails d'un match spécifique et les statistiques associées

    $requete_match = $mysqlClient->prepare($sqlQuery);
    // Prépare la requête SQL
    $requete_match->execute(['id_match' => $id_match]);
    // Exécute la requête en passant l'ID du match comme paramètre
    $match = $requete_match->fetch();
    // Récupère le résultat de la requête

    if ($match) {
        $date = new DateTime($match['date_matchs']);
        // Crée un objet DateTime pour la date du match
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN, 'EEEE d MMMM');
        // Formate la date en français
        $date_formatee = $formatter->format($date);
        // Formate la date pour l'affichage
        $heure_formatee = date('H:i', strtotime($match['heure_matchs']));
        // Formate l'heure pour l'affichage

        $result_domicile = '';
        $result_visiteur = '';
        if ($match['buts_domicile'] > $match['buts_visiteur']) {
            $result_domicile = '<div class="col" style="color: green;">Victoire</div>';
            $result_visiteur = '<div class="col" style="color: red;">Défaite</div>';
        } elseif ($match['buts_domicile'] < $match['buts_visiteur']) {
            $result_domicile = '<div class="col" style="color: red;">Défaite</div>';
            $result_visiteur = '<div class="col" style="color: green;">Victoire</div>';
        } else {
            $result_domicile = '<div class="col" style="color: blue;">Égalité</div>';
            $result_visiteur = '<div class="col" style="color: blue;">Égalité</div>';
        }
        // Détermine le résultat du match pour chaque équipe

        // Requête pour obtenir les informations des joueurs de l'équipe domicile
       $sqlJoueursDomicile = "SELECT 
        j.iuf,
        j.nom_joueur,
        j.annee_naissance,
        p.numero_bonnet,
        p.buts   AS buts_marques,
        p.exclu
    FROM participation p
    INNER JOIN joueur j ON p.id_joueur = j.id_joueur
    WHERE p.id_matchs = :id_match
      AND j.id_equipe = :id_equipe_domicile
    ORDER BY p.numero_bonnet";

        $requete_joueurs_domicile = $mysqlClient->prepare($sqlJoueursDomicile);
        $requete_joueurs_domicile->execute(['id_match' => $id_match, 'id_equipe_domicile' => $match['id_equipe_domicile']]);
        $joueurs_domicile = $requete_joueurs_domicile->fetchAll();
        // Exécute la requête et récupère les informations des joueurs de l'équipe domicile

        // Requête pour obtenir les informations des joueurs de l'équipe visiteur
       $sqlJoueursVisiteur = "SELECT 
        j.iuf,
        j.nom_joueur,
        j.annee_naissance,
        p.numero_bonnet,
        p.buts   AS buts_marques,
        p.exclu
    FROM participation p
    INNER JOIN joueur j ON p.id_joueur = j.id_joueur
    WHERE p.id_matchs = :id_match
      AND j.id_equipe = :id_equipe_visiteur
    ORDER BY p.numero_bonnet";

        $requete_joueurs_visiteur = $mysqlClient->prepare($sqlJoueursVisiteur);
        $requete_joueurs_visiteur->execute(['id_match' => $id_match, 'id_equipe_visiteur' => $match['id_equipe_visiteur']]);
        $joueurs_visiteur = $requete_joueurs_visiteur->fetchAll();
        // Exécute la requête et récupère les informations des joueurs de l'équipe visiteur
  ?>
  <header>
    <img class="logo" src="images/logo.svg" alt="logo">
    <!-- Image du logo -->
  </header>
  <section>
    <nav>
      <ul>
        <li class="bouton"><a href="index1.php">Résultat</a></li>
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs</a></li>
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li>
                <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li>
      </ul>
    </nav> 
    <article>
      <h1 class="titre_ddm">Détails du Match</h1>
      <!-- Titre de la section des détails du match -->
      <div>
        <h1 class="date_match">Le <?php echo ucfirst($date_formatee); ?> à <?php echo $heure_formatee; ?></h1>
        <!-- Affiche la date et l'heure du match -->
        <div class="images_equipe">
          <div class="row">
            <div class="col-3">
              <img class="logo_eq" src="<?=$match['logo_domicile']?>" alt="logo">
              <!-- Logo de l'équipe domicile -->
              <p class="style_equipe"><?=$match['equipe_domicile']?></p>
              <!-- Nom de l'équipe domicile -->
              <ul class="player-list">
                <!-- Liste des joueurs de l'équipe domicile -->
                <?php foreach ($joueurs_domicile as $joueur) { ?>
                  <li class="ddm_equipe">
    <span>J<?=$joueur['numero_bonnet']?></span>
    <span><?=$joueur['nom_joueur']?></span>
    <span><?=$joueur['annee_naissance']?></span>
    <span>: <?=$joueur['buts_marques']?> but(s)</span>
    <?php if ($joueur['exclu']) echo '<span style="color:red"> ⛔ Exclu</span>'; ?>
</li>
                <?php } ?>
              </ul>
            </div>
            <div class="col-2 vd">
              <?= $result_domicile ?>
              <!-- Affiche le résultat pour l'équipe domicile (Victoire, Défaite ou Égalité) -->
            </div>
            <div class="col-2">
              <p class="style_resultat_e"><?=$match['buts_domicile'] . ' - ' . $match['buts_visiteur'] ?></p>
              <!-- Affiche le score du match -->
            </div>
            <div class="col-2 vd">
              <?= $result_visiteur ?>
              <!-- Affiche le résultat pour l'équipe visiteur (Victoire, Défaite ou Égalité) -->
            </div>
            <div class="col-3">
              <img class="logo_eq" src="<?=$match['logo_visiteur']?>" alt="logo">
              <!-- Logo de l'équipe visiteur -->
              <p class="style_equipe"><?=$match['equipe_visiteur']?></p>
              <!-- Nom de l'équipe visiteur -->
              <ul class="player-list">
                <!-- Liste des joueurs de l'équipe visiteur -->
                <?php foreach ($joueurs_visiteur as $joueur) { ?>
                 <li class="ddm_equipe">
    <span>J<?=$joueur['numero_bonnet']?></span>
    <span><?=$joueur['nom_joueur']?></span>
    <span><?=$joueur['annee_naissance']?></span>
    <span>: <?=$joueur['buts_marques']?> but(s)</span>
    <?php if ($joueur['exclu']) echo '<span style="color:red"> ⛔ Exclu</span>'; ?>
</li>
                <?php } ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
      
<?php
$sqlArb = "SELECT o.nom_prenom FROM officiel o
           INNER JOIN match_officiel mo ON o.id_officiel = mo.id_officiel
           WHERE mo.id_matchs = :id_match AND o.role = 'ARBITRE'";
$reqArb = $mysqlClient->prepare($sqlArb);
$reqArb->execute([':id_match' => $id_match]);
$arbitres = $reqArb->fetchAll(PDO::FETCH_COLUMN);
if ($arbitres) {
    echo '<h2 class="arbitre">Arbitré par : ' . implode(' &amp; ', $arbitres) . '</h2>';
}
?>
      <h2 class="structure">Structure: <?php echo $match['lieu_structure'] . ', ' . $match['nom_structure']; ?></h2>
      <!-- Affiche les informations de la structure où le match a eu lieu -->
    <?php
    } else {
      echo "<p>Match non trouvé</p>";
      // Message si aucun match n'est trouvé
    }
  } else {
    echo "<p>Aucun match sélectionné</p>";
    // Message si aucun match n'est sélectionné
  }
  ?>
    </article>
  </section>

  <footer>
    <div class="style_footer">
      <img class="imgfooter" src="images/footer_wave.svg" alt="imgfooter">
      <!-- Image de décoration du pied de page -->
    </div>
  </footer>
</body>
</html>
<!-- Fin du document HTML -->

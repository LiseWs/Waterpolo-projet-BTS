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
    // On se connecte à MySQL en utilisant PDO (PHP Data Objects)
    // Les paramètres de connexion sont : 
    // - hôte : localhost (le serveur de base de données est sur la même machine)
    // - nom de la base de données : site_waterpolo
    // - encodage des caractères : utf8
    // - nom d'utilisateur : odd
    // - mot de passe : odd
    $mysqlClient = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'odd', 'odd');
  } catch (Exception $e) {
    // En cas d'erreur, on affiche un message et on arrête tout
    die('Erreur : ' . $e->getMessage());
  }
  // Si tout va bien, on peut continuer
  ?>
  <header>
    <img class="logo" src="images/logo.svg" alt="logo">
  </header>
  <section>
    <nav>
      <ul>
        
        <li class="bouton"><a href="index1.php">Résultat</a></li> <!-- Lien vers la page des résultats -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li> <!-- Lien vers la page du affichage feuille de match -->
        <li class="bouton"><a href="upload_match.php">Upload feuille de match dans la BDD</a></li> <!-- Upload de la feuille de match sur la BDD -->
        
      </ul>
    </nav> 
    <article>
      <?php
            // Requête SQL pour récupérer les informations 
            $sqlQuery = "SELECT 
            Matchs.id_matchs,
            date_matchs,
            heure_matchs,
            visiteur.nom_equipe AS 'equipe_visiteur',
            domicile.nom_equipe AS 'equipe_domicile',
            domicile.logo_equipe AS 'logo_domicile',
            visiteur.logo_equipe AS 'logo_visiteur',
            (SELECT COUNT(*) 
             FROM But 
             WHERE But.id_equipe = Matchs.id_equipe_domicile 
               AND But.id_matchs = Matchs.id_matchs) AS buts_domicile,
            (SELECT COUNT(*) 
             FROM But 
             WHERE But.id_equipe = Matchs.id_equipe_visiteur 
               AND But.id_matchs = Matchs.id_matchs) AS buts_visiteur
        FROM 
            Matchs 
        INNER JOIN 
            Equipe AS domicile ON Matchs.id_equipe_domicile = domicile.id_equipe
        INNER JOIN 
            Equipe AS visiteur ON Matchs.id_equipe_visiteur = visiteur.id_equipe
        ORDER BY 
            date_matchs DESC, 
            heure_matchs DESC;";
            // Préparation de la requête
            $requete_matchs = $mysqlClient->prepare($sqlQuery);
            // Exécution de la requête
            $requete_matchs->execute();
            // Récupération de tous les résultats dans un tableau associatif
            $matchsp = $requete_matchs->fetchAll();
      ?>
      <div>
      <div class="match"> 
        <?php
            foreach ($matchsp as $Matchs) {
                $result_domicile = '';
                $result_visiteur = '';
                if ($Matchs['buts_domicile'] > $Matchs['buts_visiteur']) {
                    $result_domicile = '<div class="col" style="color: green;">Victoire</div>';
                    $result_visiteur = '<div class="col" style="color: red;">Défaite</div>';
                } elseif ($Matchs['buts_domicile'] < $Matchs['buts_visiteur']) {
                    $result_domicile = '<div class="col" style="color: red;">Défaite</div>';
                    $result_visiteur = '<div class="col" style="color: green;">Victoire</div>';
                } else {
                    $result_domicile = '<div class="col" style="color: blue;">Égalité</div>';
                    $result_visiteur = '<div class="col" style="color: blue;">Égalité</div>';
                }
        ?>
        <a class="lien_cli" href="info_match.php?id_match=<?=$Matchs['id_matchs']?>">
          <div class="bordure_match">
              <div class="Date_m"> 
              
                <?php
                
                // Conversion de l'heure en un format lisible HH:MM
                $heure_formatee = date('H:i', strtotime($Matchs['heure_matchs']));
                
                // Conversion de la date en un format lisible "lundi 18 mai"
                $date = new DateTime($Matchs['date_matchs']);
                $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Paris', IntlDateFormatter::GREGORIAN, 'EEEE d MMMM');
                $date_formatee = $formatter->format($date);
                ?>
                  <h1 class="date_match">Le <?php echo ucfirst($date_formatee); ?> à <?php echo $heure_formatee; ?></h1> <!-- Affichage de la date et de l'heure du match -->

              </div>
                
              <div class="images_equipe">
                  <div class="row">
                    <div class="col">
                      <img class="logo_eq" src="<?=$Matchs['logo_domicile']?>" alt="logo">
                      <p class="style_equipe"><?=$Matchs['equipe_domicile'] ?></p>
                    </div>
                    <div class="col vd">
                      <?= $result_domicile ?>
                    </div>
                    <div class="col">
                      <p class="style_resultat_e"><?=$Matchs['buts_domicile'] . ' - ' . $Matchs['buts_visiteur'] ?></p>
                    </div>
                    <div class="col vd">
                      <?= $result_visiteur ?>
                    </div>
                    <div class="col">
                      <img class="logo_eq" src="<?=$Matchs['logo_visiteur']?>" alt="logo">
                      <p class="style_equipe"><?=$Matchs['equipe_visiteur'] ?></p>
                    </div>
                  </div>
              </div>
          </div>
        </a>
        <?php
          }
        ?>
      </div>
      </div>
    </article>
  </section>
  <footer>
    <div class="style_footer">
      <img class="imgfooter" src="images/footer_wave.svg" alt="imgfooter"> <!-- Image de pied de page -->
    </div>
  </footer>
</body>
</html>

<!DOCTYPE html>
<!-- Déclare le document comme étant un document HTML5 -->

<html>
<head>
<meta charset="UTF-8">
<!-- Définit l'encodage des caractères à UTF-8 -->
<title>Championnat waterpolo</title>
<!-- Définit le titre de la page qui apparaîtra dans l'onglet du navigateur -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Assure une bonne mise en page sur les appareils mobiles -->
<link href="css/bootstrap.css" rel="stylesheet">
<!-- Lie le fichier CSS de Bootstrap pour le style et la mise en page -->
<link rel="stylesheet" href="css/style.css">
<!-- Lie un fichier CSS personnalisé pour des styles additionnels -->
<script src="js/bootstrap.js"></script>
<!-- Inclut le fichier JavaScript de Bootstrap pour les fonctionnalités interactives -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
<!-- Lie une police de caractères Google appelée Trirong -->
</head>

<body>
  <?php
  try {
    $mysqlClient = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'odd', 'odd');
    /* Essaie de se connecter à une base de données MySQL avec PDO */
  } catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
    /* En cas d'erreur de connexion, affiche un message d'erreur et arrête le script */
  }

  $sql = "SELECT j.id_joueur, j.nom_joueur, j.prenom_joueur, e.nom_equipe, COUNT(b.id_but) as total_buts
          FROM but b
          JOIN joueur j ON b.id_joueur = j.id_joueur
          JOIN equipe e ON j.id_equipe = e.id_equipe
          GROUP BY j.id_joueur, j.nom_joueur, j.prenom_joueur, e.nom_equipe
          ORDER BY total_buts DESC";
  /* Requête SQL pour sélectionner les joueurs et le nombre de buts marqués, groupés par joueur et équipe, triés par nombre de buts décroissant */
  $stmt = $mysqlClient->prepare($sql);
  /* Prépare la requête SQL */
  $stmt->execute();
  /* Exécute la requête */
  $joueurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  /* Récupère tous les résultats sous forme de tableau associatif */
  ?>
  
  <header>
    <!-- Début de l'en-tête -->
    <img class="logo" src="images/logo.svg" alt="logo">
    <!-- Inclut une image avec la classe logo -->
  </header>
  
  <section>
    <!-- Début de la section principale du document -->
    <nav>
      <!-- Début de la barre de navigation -->
      <ul>
        <li class="bouton"><a href="index1.php">Résultat</a></li>
        <!-- Lien vers la page des résultats -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs</a></li>
        <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li>
        <!-- Lien vers la page des règlements -->
                 <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li>
      </ul>
    </nav>
    
    <article>
      <!-- Début de l'article principal -->
      <h1 class="titre_ddm">Classement des buteurs</h1>
      <!-- Titre principal de l'article -->
      <table class="table">
        <!-- Début du tableau avec la classe table -->
        <thead>
          <!-- Début de l'en-tête du tableau -->
          <tr>
            <th class="tt">Rang</th>
            <!-- Colonne pour le rang -->
            <th class="tt">Nom</th>
            <!-- Colonne pour le nom -->
            <th class="tt">Prénom</th>
            <!-- Colonne pour le prénom -->
            <th class="tt">Équipe</th>
            <!-- Colonne pour l'équipe -->
            <th class="tt">Total de buts</th>
            <!-- Colonne pour le total des buts -->
          </tr>
        </thead>
        <tbody>
          <!-- Début du corps du tableau -->
          <?php
          $rank = 1;
          $previous_goals = null;
          $duplicate_count = 0;

          foreach ($joueurs as $index => $joueur) {
            if ($previous_goals === null || $joueur['total_buts'] < $previous_goals) {
              $rank = $rank + $duplicate_count;
              $duplicate_count = 1;
            } else {
              $duplicate_count++;
            }
            $previous_goals = $joueur['total_buts'];
            /* Gestion du classement des joueurs avec traitement des égalités */
            
            echo "<tr class='table-content'>";
            /* Début de la ligne du tableau pour chaque joueur */
            echo "<td>{$rank}</td>";
            /* Affiche le rang */
            echo "<td>{$joueur['nom_joueur']}</td>";
            /* Affiche le nom du joueur */
            echo "<td>{$joueur['prenom_joueur']}</td>";
            /* Affiche le prénom du joueur */
            echo "<td>{$joueur['nom_equipe']}</td>";
            /* Affiche le nom de l'équipe */
            echo "<td>{$joueur['total_buts']}</td>";
            /* Affiche le total des buts */
            echo "</tr>";
            /* Fin de la ligne du tableau pour chaque joueur */
          }
          ?>
        </tbody>
      </table>
      <!-- Fin du tableau -->
    </article>
    <!-- Fin de l'article principal -->
  </section>
  <!-- Fin de la section principale -->
  
  <footer>
    <!-- Début du pied de page -->
    <div class="style_footer">
      <img class="imgfooter" src="images/footer_wave.svg" alt="imgfooter">
      <!-- Image de décoration du pied de page -->
    </div>
  </footer>
  <!-- Fin du pied de page -->
</body>
</html>
<!-- Fin du document HTML -->

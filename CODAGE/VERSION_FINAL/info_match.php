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

        // ── Scores par période ──
        $reqPeriodes = $mysqlClient->prepare(
            "SELECT num_periode, score_B, score_N FROM periode WHERE id_matchs = :id_match ORDER BY num_periode"
        );
        $reqPeriodes->execute([':id_match' => $id_match]);
        $periodes = $reqPeriodes->fetchAll();

        // ── Officiels du match (groupés par rôle) ──
        $reqOff = $mysqlClient->prepare(
            "SELECT o.nom_prenom, o.iuf, o.role
             FROM officiel o
             INNER JOIN match_officiel mo ON mo.id_officiel = o.id_officiel
             WHERE mo.id_matchs = :id_match
             ORDER BY FIELD(o.role,'ARBITRE','SECRETAIRE','CHRONO','JUGE_BUT','DELEGUE_FFN')"
        );
        $reqOff->execute([':id_match' => $id_match]);
        $officiels = [];
        foreach ($reqOff->fetchAll() as $o) {
            $officiels[$o['role']][] = $o;
        }
        $libelleRole = [
            'ARBITRE'     => 'Arbitre',
            'SECRETAIRE'  => 'Secrétaire',
            'CHRONO'      => 'Chronométreur',
            'JUGE_BUT'    => 'Juge de but',
            'DELEGUE_FFN' => 'Délégué F.F.N.',
        ];

        // ── Staff par équipe ──
        $reqStaff = $mysqlClient->prepare(
            "SELECT id_equipe, nom_prenom, role FROM staff_match WHERE id_matchs = :id_match
             ORDER BY FIELD(role,'ENTRAINEUR','ADJOINT','SUPPLEANT')"
        );
        $reqStaff->execute([':id_match' => $id_match]);
        $staff = ['dom' => [], 'vis' => []];
        foreach ($reqStaff->fetchAll() as $s) {
            $cle = ($s['id_equipe'] == $match['id_equipe_domicile']) ? 'dom' : 'vis';
            $staff[$cle][] = $s;
        }
        $libelleStaff = ['ENTRAINEUR' => 'Entraîneur', 'ADJOINT' => 'Entraîneur adjoint', 'SUPPLEANT' => 'Suppléant'];

        // ── Délégués de club ──
        $reqDel = $mysqlClient->prepare(
            "SELECT nom_prenom, couleur FROM delegue_club WHERE id_matchs = :id_match"
        );
        $reqDel->execute([':id_match' => $id_match]);
        $delegues = ['B' => null, 'N' => null];
        foreach ($reqDel->fetchAll() as $d) {
            $delegues[$d['couleur']] = $d['nom_prenom'];
        }

        // ── Temps morts ──
        $reqTM = $mysqlClient->prepare(
            "SELECT id_equipe, num_periode, nb FROM temps_mort WHERE id_matchs = :id_match ORDER BY num_periode"
        );
        $reqTM->execute([':id_match' => $id_match]);
        $tempsMorts = $reqTM->fetchAll();
  ?>
  <style>
    .bloc_match { max-width: 1000px; margin: 25px auto; }
    .bloc_match h2 { color: #00557f; border-bottom: 2px solid #ff9a03; padding-bottom: 6px; }
    .tbl_match { width: 100%; border-collapse: collapse; margin-bottom: 30px; background:#fff; }
    .tbl_match th, .tbl_match td { border: 1px solid #ddd; padding: 8px 12px; text-align: center; }
    .tbl_match th { background:#00557f; color:#fff; }
    .tbl_match tr:nth-child(even) { background:#f2f6f9; }
    .grille_2col { display:flex; flex-wrap:wrap; gap:30px; justify-content:center; }
    .grille_2col > div { flex:1 1 320px; }
  </style>
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
      
      <!-- ── Score par période ── -->
      <?php if ($periodes) { ?>
      <div class="bloc_match">
        <h2>Score par période</h2>
        <table class="tbl_match">
          <tr>
            <th>Équipe</th>
            <?php foreach ($periodes as $p) { echo '<th>' . $p['num_periode'] . 'ᵉ période</th>'; } ?>
            <th>Total</th>
          </tr>
          <tr>
            <td><?= htmlspecialchars($match['equipe_domicile']) ?></td>
            <?php foreach ($periodes as $p) { echo '<td>' . (int)$p['score_B'] . '</td>'; } ?>
            <td><strong><?= (int)$match['buts_domicile'] ?></strong></td>
          </tr>
          <tr>
            <td><?= htmlspecialchars($match['equipe_visiteur']) ?></td>
            <?php foreach ($periodes as $p) { echo '<td>' . (int)$p['score_N'] . '</td>'; } ?>
            <td><strong><?= (int)$match['buts_visiteur'] ?></strong></td>
          </tr>
        </table>
      </div>
      <?php } ?>

      <!-- ── Staff des équipes ── -->
      <?php if ($staff['dom'] || $staff['vis']) { ?>
      <div class="bloc_match">
        <h2>Encadrement</h2>
        <div class="grille_2col">
          <div>
            <table class="tbl_match">
              <tr><th colspan="2"><?= htmlspecialchars($match['equipe_domicile']) ?></th></tr>
              <?php foreach ($staff['dom'] as $s) { ?>
                <tr><td><?= $libelleStaff[$s['role']] ?? $s['role'] ?></td><td><?= htmlspecialchars($s['nom_prenom']) ?></td></tr>
              <?php } if (!$staff['dom']) echo '<tr><td colspan="2">—</td></tr>'; ?>
            </table>
          </div>
          <div>
            <table class="tbl_match">
              <tr><th colspan="2"><?= htmlspecialchars($match['equipe_visiteur']) ?></th></tr>
              <?php foreach ($staff['vis'] as $s) { ?>
                <tr><td><?= $libelleStaff[$s['role']] ?? $s['role'] ?></td><td><?= htmlspecialchars($s['nom_prenom']) ?></td></tr>
              <?php } if (!$staff['vis']) echo '<tr><td colspan="2">—</td></tr>'; ?>
            </table>
          </div>
        </div>
      </div>
      <?php } ?>

      <!-- ── Officiels ── -->
      <?php if ($officiels) { ?>
      <div class="bloc_match">
        <h2>Officiels du match</h2>
        <table class="tbl_match">
          <tr><th>Fonction</th><th>Nom - Prénom</th><th>IUF</th></tr>
          <?php foreach ($officiels as $role => $liste) {
              foreach ($liste as $o) { ?>
            <tr>
              <td><?= $libelleRole[$role] ?? $role ?></td>
              <td><?= htmlspecialchars($o['nom_prenom']) ?></td>
              <td><?= htmlspecialchars($o['iuf'] ?? '—') ?></td>
            </tr>
          <?php } } ?>
        </table>
      </div>
      <?php } ?>

      <!-- ── Délégués de club ── -->
      <?php if ($delegues['B'] || $delegues['N']) { ?>
      <div class="bloc_match">
        <h2>Délégués de club</h2>
        <table class="tbl_match">
          <tr><th><?= htmlspecialchars($match['equipe_domicile']) ?> (Blanc)</th><th><?= htmlspecialchars($match['equipe_visiteur']) ?> (Noir)</th></tr>
          <tr><td><?= htmlspecialchars($delegues['B'] ?? '—') ?></td><td><?= htmlspecialchars($delegues['N'] ?? '—') ?></td></tr>
        </table>
      </div>
      <?php } ?>

      <!-- ── Temps morts ── -->
      <?php if ($tempsMorts) { ?>
      <div class="bloc_match">
        <h2>Temps morts</h2>
        <table class="tbl_match">
          <tr><th>Équipe</th><th>Période</th><th>Nombre</th></tr>
          <?php foreach ($tempsMorts as $tm) { ?>
            <tr>
              <td><?= $tm['id_equipe'] == $match['id_equipe_domicile'] ? htmlspecialchars($match['equipe_domicile']) : htmlspecialchars($match['equipe_visiteur']) ?></td>
              <td><?= (int)$tm['num_periode'] ?></td>
              <td><?= (int)$tm['nb'] ?></td>
            </tr>
          <?php } ?>
        </table>
      </div>
      <?php } ?>

      <h2 class="structure">Structure: <?php echo htmlspecialchars($match['lieu_structure'] . ', ' . $match['nom_structure']); ?></h2>
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

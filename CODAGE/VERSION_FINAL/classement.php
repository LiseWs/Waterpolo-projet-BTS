<?php
/**
 * classement.php
 * Classement des équipes par championnat (CDC Id 2 « Affichage du classement »
 * + p.9 1a « suivre le classement des différents championnats »).
 * Le championnat est sélectionnable (point 5).
 * Barème : victoire = 3 pts, nul = 1 pt, défaite = 0 pt.
 */
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8mb4', 'odd', 'odd',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Liste des championnats (pour le sélecteur)
$championnats = $pdo->query(
    "SELECT c.id_championnat, c.nom_championnat, s.saison, n.niveau
     FROM championnat c
     JOIN saison s ON s.id_saison = c.id_saison
     JOIN niveau n ON n.id_niveau = c.id_niveau
     ORDER BY s.saison DESC, c.nom_championnat"
)->fetchAll(PDO::FETCH_ASSOC);

// Championnat sélectionné (par défaut le premier)
$idChamp = isset($_GET['id_championnat']) ? (int)$_GET['id_championnat']
    : (int)($championnats[0]['id_championnat'] ?? 0);

// Matchs du championnat sélectionné
$reqM = $pdo->prepare(
    "SELECT id_equipe_domicile, id_equipe_visiteur, score_domicile, score_visiteur
     FROM matchs WHERE id_championnat = :c"
);
$reqM->execute([':c' => $idChamp]);
$matchs = $reqM->fetchAll(PDO::FETCH_ASSOC);

// Calcul du classement
$stats = [];   // id_equipe => [J,G,N,P,bp,bc,pts]
$init = fn() => ['J' => 0, 'G' => 0, 'N' => 0, 'P' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0];
foreach ($matchs as $m) {
    $d = (int)$m['id_equipe_domicile'];
    $v = (int)$m['id_equipe_visiteur'];
    $sd = (int)$m['score_domicile'];
    $sv = (int)$m['score_visiteur'];
    if (!isset($stats[$d])) $stats[$d] = $init();
    if (!isset($stats[$v])) $stats[$v] = $init();
    $stats[$d]['J']++; $stats[$v]['J']++;
    $stats[$d]['bp'] += $sd; $stats[$d]['bc'] += $sv;
    $stats[$v]['bp'] += $sv; $stats[$v]['bc'] += $sd;
    if ($sd > $sv) {
        $stats[$d]['G']++; $stats[$d]['pts'] += 3; $stats[$v]['P']++;
    } elseif ($sd < $sv) {
        $stats[$v]['G']++; $stats[$v]['pts'] += 3; $stats[$d]['P']++;
    } else {
        $stats[$d]['N']++; $stats[$v]['N']++; $stats[$d]['pts']++; $stats[$v]['pts']++;
    }
}

// Noms / logos des équipes
$equipes = [];
if ($stats) {
    $ids = implode(',', array_map('intval', array_keys($stats)));
    foreach ($pdo->query("SELECT id_equipe, nom_equipe, logo_equipe FROM equipe WHERE id_equipe IN ($ids)") as $e) {
        $equipes[$e['id_equipe']] = $e;
    }
}

// Tri : points, puis différence de buts, puis buts pour
$lignes = [];
foreach ($stats as $id => $s) {
    $s['id'] = $id;
    $s['diff'] = $s['bp'] - $s['bc'];
    $s['nom'] = $equipes[$id]['nom_equipe'] ?? ('Équipe ' . $id);
    $s['logo'] = $equipes[$id]['logo_equipe'] ?? '';
    $lignes[] = $s;
}
usort($lignes, fn($a, $b) =>
    [$b['pts'], $b['diff'], $b['bp']] <=> [$a['pts'], $a['diff'], $a['bp']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Classement - Championnat waterpolo</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
  .wrap { max-width: 1000px; margin: 20px auto; padding: 0 15px; }
  .selecteur { margin: 20px 0; }
  .selecteur select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; min-width: 320px; }
  table.clt { width: 100%; border-collapse: collapse; background: #fff; }
  table.clt th, table.clt td { border: 1px solid #ddd; padding: 8px 10px; text-align: center; }
  table.clt th { background: #00557f; color: #fff; }
  table.clt td.equipe { text-align: left; }
  table.clt tr:nth-child(even) { background: #f2f6f9; }
  table.clt td.pts { font-weight: bold; color: #00557f; }
  .logo_min { height: 24px; vertical-align: middle; margin-right: 8px; }
  h1.titre_ddm { text-align:center; }
</style>
</head>
<body>
<header><img class="logo" src="images/logo.svg" alt="logo"></header>
<section>
  <nav>
    <ul>
        <li class="bouton"><a href="index1.php">Résultats</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="upload_match.php">Importer une feuille</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="gestion_championnats.php">Gestion Championnat</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="affichage_feuille_match_bdd.php">Afficher la feuille</a></li> <!-- Upload de la feuille de match sur la BDD -->       
  </nav>
  <article>
    <div class="wrap">
      <h1 class="titre_ddm">Classement</h1>

      <?php if (!$championnats) { ?>
        <p>Aucun championnat n'est encore enregistré. Importez une feuille de match ou créez un championnat.</p>
      <?php } else { ?>
        <form method="get" class="selecteur">
          <label for="id_championnat"><strong>Championnat :</strong></label>
          <select name="id_championnat" id="id_championnat" onchange="this.form.submit()">
            <?php foreach ($championnats as $c) {
                $sel = ($c['id_championnat'] == $idChamp) ? 'selected' : ''; ?>
              <option value="<?= (int)$c['id_championnat'] ?>" <?= $sel ?>>
                <?= htmlspecialchars($c['nom_championnat']) ?> - <?= htmlspecialchars($c['niveau']) ?> (<?= (int)$c['saison'] ?>)
              </option>
            <?php } ?>
          </select>
          <noscript><button type="submit">Afficher</button></noscript>
        </form>

        <?php if (!$lignes) { ?>
          <p>Aucun match enregistré pour ce championnat.</p>
        <?php } else { ?>
          <table class="clt">
            <tr>
              <th>Rang</th><th>Équipe</th><th>J</th><th>G</th><th>N</th><th>P</th>
              <th>Buts +</th><th>Buts -</th><th>Diff.</th><th>Pts</th>
            </tr>
            <?php foreach ($lignes as $i => $l) { ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td class="equipe">
                  <?php if (!empty($l['logo'])) { ?><img class="logo_min" src="<?= htmlspecialchars($l['logo']) ?>" alt=""><?php } ?>
                  <?= htmlspecialchars($l['nom']) ?>
                </td>
                <td><?= $l['J'] ?></td>
                <td><?= $l['G'] ?></td>
                <td><?= $l['N'] ?></td>
                <td><?= $l['P'] ?></td>
                <td><?= $l['bp'] ?></td>
                <td><?= $l['bc'] ?></td>
                <td><?= ($l['diff'] > 0 ? '+' : '') . $l['diff'] ?></td>
                <td class="pts"><?= $l['pts'] ?></td>
              </tr>
            <?php } ?>
          </table>
          <p style="margin-top:10px;color:#666;font-size:.9em;">
            Barème : victoire = 3 pts, nul = 1 pt, défaite = 0 pt. J = joués, G/N/P = gagnés/nuls/perdus.
          </p>
        <?php } ?>
      <?php } ?>
    </div>
  </article>
</section>
<footer><div class="style_footer"><img class="imgfooter" src="images/footer_wave.svg" alt="imgfooter"></div></footer>
</body>
</html>

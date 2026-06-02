<?php
/**
 * gestion_championnats.php
 * Configuration des championnats par la ligue (CDC p.9 1a « les championnats
 * doivent être configurables par la ligue »). Réservé aux utilisateurs connectés.
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
try {
    $pdo = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8mb4', 'odd', 'odd',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

$msg = '';
$err = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_saison') {
            $an = (int)($_POST['saison'] ?? 0);
            if ($an < 1900 || $an > 2100) throw new Exception('Année de saison invalide.');
            $st = $pdo->prepare("INSERT IGNORE INTO saison (saison) VALUES (?)");
            $st->execute([$an]);
            $msg = "Saison $an ajoutée.";
        } elseif ($action === 'add_niveau') {
            $niv = trim($_POST['niveau'] ?? '');
            if ($niv === '') throw new Exception('Niveau vide.');
            $pdo->prepare("INSERT INTO niveau (niveau) VALUES (?)")->execute([$niv]);
            $msg = "Niveau « $niv » ajouté.";
        } elseif ($action === 'add_championnat') {
            $nom = trim($_POST['nom_championnat'] ?? '');
            $ids = (int)($_POST['id_saison'] ?? 0);
            $idn = (int)($_POST['id_niveau'] ?? 0);
            if ($nom === '' || !$ids || !$idn) throw new Exception('Champs du championnat incomplets.');
            $pdo->prepare("INSERT INTO championnat (nom_championnat, id_saison, id_niveau) VALUES (?,?,?)")
                ->execute([$nom, $ids, $idn]);
            $msg = "Championnat « $nom » créé.";
        } elseif ($action === 'del_championnat') {
            $idc = (int)($_POST['id_championnat'] ?? 0);
            $pdo->prepare("DELETE FROM championnat WHERE id_championnat = ?")->execute([$idc]);
            $msg = "Championnat supprimé.";
        }
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$saisons = $pdo->query("SELECT * FROM saison ORDER BY saison DESC")->fetchAll(PDO::FETCH_ASSOC);
$niveaux = $pdo->query("SELECT * FROM niveau ORDER BY niveau")->fetchAll(PDO::FETCH_ASSOC);
$champs = $pdo->query(
    "SELECT c.id_championnat, c.nom_championnat, s.saison, n.niveau,
            (SELECT COUNT(*) FROM matchs m WHERE m.id_championnat = c.id_championnat) AS nb_matchs
     FROM championnat c
     JOIN saison s ON s.id_saison = c.id_saison
     JOIN niveau n ON n.id_niveau = c.id_niveau
     ORDER BY s.saison DESC, c.nom_championnat"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des championnats</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
  .wrap { max-width: 1000px; margin: 20px auto; padding: 0 15px; }
  .card { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:18px; margin-bottom:22px; }
  .card h2 { color:#00557f; margin-top:0; }
  .row-form { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
  .row-form label { display:block; font-size:.85em; color:#555; }
  .row-form input, .row-form select { padding:8px; border:1px solid #ccc; border-radius:6px; }
  button { background:#00557f; color:#fff; border:none; border-radius:6px; padding:9px 16px; cursor:pointer; }
  button.danger { background:#d9534f; }
  table { width:100%; border-collapse:collapse; background:#fff; }
  th,td { border:1px solid #ddd; padding:8px 10px; text-align:center; }
  th { background:#00557f; color:#fff; }
  .ok { background:#dff0d8; border:1px solid #c3e6cb; padding:10px; border-radius:6px; }
  .ko { background:#f2dede; border:1px solid #ebccd1; padding:10px; border-radius:6px; }
</style>
</head>
<body>
<header><img class="logo" src="images/logo.svg" alt="logo"></header>
<section>
  <nav>
    <ul>
      <li class="bouton"><a href="index1.php">Résultat</a></li>
      <li class="bouton"><a href="classement.php">Classement</a></li>
      <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs</a></li>
      
      <li class="bouton"><a href="gestion_championnats.php">Gestion championnats</a></li>
      
      <li class="bouton"><a href="upload_match.php">Importer une feuille</a></li>
      <li class="bouton"><a href="logout.php">Se déconnecter</a></li>
    </ul>
  </nav>
  <article>
    <div class="wrap">
      <h1 class="titre_ddm">Gestion des championnats</h1>
      <?php if ($msg) echo '<p class="ok">' . htmlspecialchars($msg) . '</p>'; ?>
      <?php if ($err) echo '<p class="ko">' . htmlspecialchars($err) . '</p>'; ?>

      <div class="card">
        <h2>Créer un championnat</h2>
        <form method="post" class="row-form">
          <input type="hidden" name="action" value="add_championnat">
          <div><label>Nom du championnat</label><input type="text" name="nom_championnat" required></div>
          <div><label>Saison</label>
            <select name="id_saison" required>
              <?php foreach ($saisons as $s) echo '<option value="'.$s['id_saison'].'">'.(int)$s['saison'].'</option>'; ?>
            </select>
          </div>
          <div><label>Niveau</label>
            <select name="id_niveau" required>
              <?php foreach ($niveaux as $n) echo '<option value="'.$n['id_niveau'].'">'.htmlspecialchars($n['niveau']).'</option>'; ?>
            </select>
          </div>
          <button type="submit">Créer</button>
        </form>
        <p style="color:#666;font-size:.85em;">Astuce : créez d'abord une saison et un niveau si les listes sont vides.</p>
      </div>

      <div class="card">
        <h2>Ajouter une saison / un niveau</h2>
        <div class="row-form">
          <form method="post" class="row-form" style="margin-right:30px;">
            <input type="hidden" name="action" value="add_saison">
            <div><label>Nouvelle saison (année)</label><input type="number" name="saison" min="1900" max="2100" placeholder="2026" required></div>
            <button type="submit">Ajouter saison</button>
          </form>
          <form method="post" class="row-form">
            <input type="hidden" name="action" value="add_niveau">
            <div><label>Nouveau niveau</label><input type="text" name="niveau" placeholder="Nationale 3" required></div>
            <button type="submit">Ajouter niveau</button>
          </form>
        </div>
      </div>

      <div class="card">
        <h2>Championnats existants</h2>
        <?php if (!$champs) { ?>
          <p>Aucun championnat.</p>
        <?php } else { ?>
          <table>
            <tr><th>Nom</th><th>Niveau</th><th>Saison</th><th>Matchs</th><th>Action</th></tr>
            <?php foreach ($champs as $c) { ?>
              <tr>
                <td><?= htmlspecialchars($c['nom_championnat']) ?></td>
                <td><?= htmlspecialchars($c['niveau']) ?></td>
                <td><?= (int)$c['saison'] ?></td>
                <td><?= (int)$c['nb_matchs'] ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Supprimer ce championnat ? Les matchs liés seront aussi supprimés.');">
                    <input type="hidden" name="action" value="del_championnat">
                    <input type="hidden" name="id_championnat" value="<?= (int)$c['id_championnat'] ?>">
                    <button type="submit" class="danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </table>
        <?php } ?>
      </div>
    </div>
  </article>
</section>
<footer><div class="style_footer"><img class="imgfooter" src="images/footer_wave.svg" alt="imgfooter"></div></footer>
</body>
</html>

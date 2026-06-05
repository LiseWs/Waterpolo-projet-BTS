<?php
/**
 * affichage_feuille_match_bdd.php
 * Affiche la feuille de match complète depuis la base de données site_waterpolo.
 * Les données doivent avoir été importées au préalable via import_feuille_match.php.
 */

// ─── Connexion PDO ────────────────────────────────────────
$host   = 'localhost';
$user   = 'odd';
$passwd = 'odd';
$dbname = 'site_waterpolo';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $passwd,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<p style="color:red">Connexion BDD impossible : ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ─── Récupération du match sélectionné ───────────────────
$idMatch = isset($_GET['id_matchs']) ? (int)$_GET['id_matchs'] : 0;

// ─── Liste de tous les matchs disponibles ────────────────
$listeMatchs = $pdo->query("
    SELECT m.id_matchs,
           e1.nom_equipe  AS equipe_B,
           e2.nom_equipe  AS equipe_N,
           m.date_matchs,
           m.score_domicile,
           m.score_visiteur,
           c.nom_championnat
    FROM matchs m
    JOIN equipe e1 ON e1.id_equipe = m.id_equipe_domicile
    JOIN equipe e2 ON e2.id_equipe = m.id_equipe_visiteur
    JOIN championnat c ON c.id_championnat = m.id_championnat
    ORDER BY m.date_matchs DESC
")->fetchAll();

// ─── Si aucun match sélectionné, prendre le dernier ──────
if ($idMatch === 0 && !empty($listeMatchs)) {
    $idMatch = (int)$listeMatchs[0]['id_matchs'];
}

// ─── Données du match sélectionné ────────────────────────
$match = null;
$joueursB = $joueursN = $periodes = $evenements = $staff = $officiels = [];

if ($idMatch > 0) {

    // Infos générales
    $match = $pdo->prepare("
        SELECT m.*,
               e1.nom_equipe AS equipe_B,
               e2.nom_equipe AS equipe_N,
               c.nom_championnat,
               s.nom_structure AS lieu
        FROM matchs m
        JOIN equipe e1      ON e1.id_equipe      = m.id_equipe_domicile
        JOIN equipe e2      ON e2.id_equipe      = m.id_equipe_visiteur
        JOIN championnat c  ON c.id_championnat  = m.id_championnat
        JOIN structure s    ON s.id_structure    = m.id_structure
        WHERE m.id_matchs = ?
    ");
    $match->execute([$idMatch]);
    $match = $match->fetch();

    // Joueurs domicile (Blancs)
    $stmtJ = $pdo->prepare("
        SELECT j.nom_joueur, j.iuf, j.annee_naissance,
               p.numero_bonnet, p.exclu, p.buts
        FROM participation p
        JOIN joueur j ON j.id_joueur = p.id_joueur
        WHERE p.id_matchs = ? AND j.id_equipe = ?
        ORDER BY p.numero_bonnet
    ");
    $stmtJ->execute([$idMatch, $match['id_equipe_domicile']]);
    $joueursB = $stmtJ->fetchAll();

    // Joueurs visiteur (Noirs)
    $stmtJ->execute([$idMatch, $match['id_equipe_visiteur']]);
    $joueursN = $stmtJ->fetchAll();

    // Périodes
    $periodes = $pdo->prepare("
        SELECT num_periode, score_B, score_N
        FROM periode
        WHERE id_matchs = ?
        ORDER BY num_periode
    ");
    $periodes->execute([$idMatch]);
    $periodes = $periodes->fetchAll();

    // Événements du chrono
    $evenements = $pdo->prepare("
        SELECT e.temps, e.couleur, e.numero_bonnet, e.code_action, e.score,
               p.num_periode
        FROM evenement e
        JOIN periode p ON p.id_periode = e.id_periode
        WHERE e.id_matchs = ?
        ORDER BY p.num_periode, e.id_evenement
    ");
    $evenements->execute([$idMatch]);
    $evenements = $evenements->fetchAll();

    // Staff
    $staff = $pdo->prepare("
        SELECT sm.role, sm.nom_prenom, e.nom_equipe
        FROM staff_match sm
        JOIN equipe e ON e.id_equipe = sm.id_equipe
        WHERE sm.id_matchs = ?
        ORDER BY e.id_equipe, sm.role
    ");
    $staff->execute([$idMatch]);
    $staff = $staff->fetchAll();

    // Officiels
    $officiels = $pdo->prepare("
        SELECT o.nom_prenom, o.role, o.iuf
        FROM match_officiel mo
        JOIN officiel o ON o.id_officiel = mo.id_officiel
        WHERE mo.id_matchs = ?
        ORDER BY o.role
    ");
    $officiels->execute([$idMatch]);
    $officiels = $officiels->fetchAll();
}

// ─── Labels lisibles ──────────────────────────────────────
$roleLabels = [
    'ENTRAINEUR' => 'Entraîneur',
    'ADJOINT'    => 'Entraîneur adjoint',
    'SUPPLEANT'  => 'Suppléant',
];
$officielLabels = [
    'ARBITRE'     => 'Arbitre',
    'SECRETAIRE'  => 'Secrétaire',
    'CHRONO'      => 'Chronométreur',
    'JUGE_BUT'    => 'Juge de but',
    'DELEGUE_FFN' => 'Délégué FFN',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feuille de Match — Water-polo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Variables ───────────────────────────────────── */
        :root {
            --orange:   #ff9a03;
            --orange-d: #e68a00;
            --vert:     #4CAF50;
            --vert-d:   #388E3C;
            --gris-bg:  #f4f4f9;
            --gris-brd: #ddd;
            --texte:    #333;
            --blanc:    #ffffff;
        }

        /* ── Reset & base ────────────────────────────────── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: var(--gris-bg);
            color: var(--texte);
            display: flex;
            min-height: 100vh;
        }

        /* ── Navigation latérale (identique au site) ─────── */
        nav {
            flex: 0 0 220px;
            background: var(--blanc);
            padding: 20px;
            box-shadow: 2px 0 8px rgba(0,0,0,.08);
            min-height: 100vh;
        }
        nav ul { list-style: none; padding: 10px 0; }
        nav ul li { margin-bottom: 12px; }
        nav ul li a {
            display: block;
            background: var(--orange);
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            transition: background .2s;
        }
        nav ul li a:hover { background: var(--orange-d); }
        nav ul li a.actif  { background: var(--orange-d); }

        /* ── Contenu principal ───────────────────────────── */
        main {
            flex: 1;
            padding: 30px 24px;
            max-width: 960px;
        }

        /* ── Sélecteur de match ───────────────────────────── */
        .selecteur {
            background: var(--blanc);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .selecteur label { font-weight: bold; font-size: 15px; white-space: nowrap; }
        .selecteur select {
            flex: 1;
            min-width: 220px;
            padding: 9px 12px;
            border: 1px solid var(--gris-brd);
            border-radius: 6px;
            font-size: 14px;
            background: #fafafa;
        }
        .selecteur button {
            background: var(--vert);
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s;
        }
        .selecteur button:hover { background: var(--vert-d); }

        /* ── Carte générique ─────────────────────────────── */
        .carte {
            background: var(--blanc);
            border-radius: 10px;
            padding: 20px 22px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .carte h2 {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--orange-d);
            border-bottom: 2px solid var(--orange);
            padding-bottom: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Bandeau score ───────────────────────────────── */
        .bandeau-score {
            background: var(--texte);
            color: var(--blanc);
            border-radius: 10px;
            padding: 22px 28px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,.18);
        }
        .bandeau-equipe {
            flex: 1;
            text-align: center;
        }
        .bandeau-equipe .badge-couleur {
            display: inline-block;
            width: 14px; height: 14px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }
        .badge-blanc { background: #fff; border: 2px solid #aaa; }
        .badge-noir  { background: #222; }
        .bandeau-equipe .nom-equipe {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: .03em;
        }
        .bandeau-equipe .role-equipe {
            font-size: 12px;
            opacity: .6;
            margin-top: 4px;
        }
        .bandeau-centre {
            text-align: center;
            min-width: 140px;
        }
        .score-affiche {
            font-size: 46px;
            font-weight: 900;
            letter-spacing: .04em;
            line-height: 1;
            color: var(--orange);
        }
        .score-meta {
            font-size: 12px;
            opacity: .6;
            margin-top: 6px;
        }

        /* ── Infos générales ─────────────────────────────── */
        .infos-grille {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
        }
        .info-item { display: flex; align-items: flex-start; gap: 10px; }
        .info-item .icone { color: var(--orange); font-size: 16px; margin-top: 2px; }
        .info-item .label { font-size: 11px; text-transform: uppercase; color: #888; }
        .info-item .valeur { font-size: 14px; font-weight: bold; }

        /* ── Périodes ────────────────────────────────────── */
        .periodes-grille {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .periode-bloc {
            background: var(--gris-bg);
            border-radius: 8px;
            padding: 14px 10px;
            text-align: center;
            border: 1px solid var(--gris-brd);
        }
        .periode-bloc .p-num {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .periode-bloc .p-score {
            font-size: 22px;
            font-weight: 900;
            color: var(--texte);
        }
        .periode-bloc .p-score span {
            color: #bbb;
            font-weight: 400;
            font-size: 18px;
        }

        /* ── Tableaux joueurs ────────────────────────────── */
        table.joueurs {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table.joueurs thead tr th {
            background: var(--vert);
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        table.joueurs thead tr th.centre { text-align: center; }
        table.joueurs tbody tr:nth-child(even) { background: #f9f9f9; }
        table.joueurs tbody tr:hover { background: #fff3e0; }
        table.joueurs tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }
        table.joueurs tbody td.centre { text-align: center; }
        .bonnet {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px; height: 26px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 13px;
        }
        .bonnet-b { background: #e8e8e8; color: #333; border: 1px solid #aaa; }
        .bonnet-n { background: #333;    color: #fff; }
        .exclu-oui {
            color: #e53935;
            font-weight: bold;
            font-size: 16px;
        }
        .buts-badge {
            background: var(--orange);
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        .buts-zero { color: #bbb; }

        /* ── Tableau équipes côte à côte ─────────────────── */
        .deux-equipes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .equipe-bloc h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 7px 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .equipe-bloc.blancs h3 { background: #f0f0f0; color: #333; }
        .equipe-bloc.noirs  h3 { background: #333;    color: #fff; }

        /* ── Chrono ──────────────────────────────────────── */
        .chrono-periode { margin-bottom: 16px; }
        .chrono-periode h4 {
            font-size: 13px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: .06em;
            margin-bottom: 8px;
            border-left: 3px solid var(--orange);
            padding-left: 10px;
        }
        table.chrono {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table.chrono th {
            background: #f0f0f0;
            padding: 7px 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        table.chrono td {
            padding: 7px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        table.chrono tr:hover td { background: #fffde7; }
        .code-action {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .code-BUT      { background: #e8f5e9; color: #2e7d32; }
        .code-EXCLUSION{ background: #fce4ec; color: #c62828; }
        .code-PENALTY  { background: #fff3e0; color: #e65100; }
        .code-TEMPS_MORT { background: #e3f2fd; color: #1565c0; }
        .code-AUTRE    { background: #f5f5f5; color: #555; }

        /* ── Staff & officiels ───────────────────────────── */
        .staff-grille {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .staff-item {
            background: var(--gris-bg);
            border: 1px solid var(--gris-brd);
            border-radius: 6px;
            padding: 10px 14px;
        }
        .staff-item .s-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
        }
        .staff-item .s-equipe {
            font-size: 11px;
            color: var(--orange-d);
            font-weight: bold;
        }
        .staff-item .s-nom {
            font-size: 14px;
            font-weight: bold;
            margin-top: 2px;
        }

        /* ── Vide / alerte ───────────────────────────────── */
        .vide {
            text-align: center;
            color: #aaa;
            font-style: italic;
            padding: 20px 0;
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 700px) {
            body { flex-direction: column; }
            nav { min-height: auto; flex: 0 0 auto; }
            .deux-equipes, .periodes-grille, .staff-grille { grid-template-columns: 1fr; }
            .bandeau-score { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- ── Navigation ──────────────────────────────────────── -->
<nav>
    <ul>
        <li class="bouton"><a href="index1.php">Résultats</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs </a></li> <!-- Lien vers la page des meilleurs buteurs -->
        <li class="bouton"><a href="règle_water-polo.php">Réglement</a></li> <!-- Lien vers la page du règlement -->
        <li class="bouton"><a href="upload_match.php">Importer une feuille</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="gestion_championnats.php">Gestion Championnat</a></li> <!-- Upload de la feuille de match sur la BDD -->
        <li class="bouton"><a href="classement.php">Classement Championnat</a></li> <!-- Upload de la feuille de match sur la BDD -->       
    </ul>
</nav>

<!-- ── Contenu ─────────────────────────────────────────── -->
<main>

    <!-- Sélecteur de match -->
    <form method="get" action="" class="selecteur">
        <label for="id_matchs"><i class="fas fa-water" style="color:var(--orange)"></i> Match :</label>
        <select name="id_matchs" id="id_matchs">
            <?php foreach ($listeMatchs as $m): ?>
                <option value="<?= $m['id_matchs'] ?>"
                    <?= $m['id_matchs'] == $idMatch ? 'selected' : '' ?>>
                    <?= htmlspecialchars(
                        date('d/m/Y', strtotime($m['date_matchs'])) . ' — ' .
                        $m['equipe_B'] . ' vs ' . $m['equipe_N'] .
                        ' (' . $m['score_domicile'] . '-' . $m['score_visiteur'] . ')'
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit"><i class="fas fa-eye"></i> Afficher</button>
    </form>

    <?php if (empty($listeMatchs)): ?>
        <div class="carte">
            <p class="vide">
                <i class="fas fa-inbox" style="font-size:32px;display:block;margin-bottom:10px"></i>
                Aucune feuille de match importée.<br>
                Utilisez <strong>import_feuille_match.php</strong> pour importer un fichier .xlsx.
            </p>
        </div>
    <?php elseif ($match): ?>

    <!-- ── Bandeau score ──────────────────────────────────── -->
    <div class="bandeau-score">
        <div class="bandeau-equipe">
            <div>
                <span class="badge-couleur badge-blanc"></span>
                <span class="nom-equipe"><?= htmlspecialchars($match['equipe_B']) ?></span>
            </div>
            <div class="role-equipe">Domicile · Bonnets blancs</div>
        </div>
        <div class="bandeau-centre">
            <div class="score-affiche">
                <?= $match['score_domicile'] ?> <span style="color:#666;font-size:32px">-</span> <?= $match['score_visiteur'] ?>
            </div>
            <div class="score-meta">Score final</div>
        </div>
        <div class="bandeau-equipe">
            <div>
                <span class="badge-couleur badge-noir"></span>
                <span class="nom-equipe"><?= htmlspecialchars($match['equipe_N']) ?></span>
            </div>
            <div class="role-equipe">Visiteur · Bonnets noirs</div>
        </div>
    </div>

    <!-- ── Informations générales ─────────────────────────── -->
    <div class="carte">
        <h2><i class="fas fa-info-circle"></i> Informations générales</h2>
        <div class="infos-grille">
            <div class="info-item">
                <div class="icone"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="label">Date</div>
                    <div class="valeur"><?= date('d/m/Y', strtotime($match['date_matchs'])) ?></div>
                </div>
            </div>
            <div class="info-item">
                <div class="icone"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="label">Heure</div>
                    <div class="valeur"><?= substr($match['heure_matchs'], 0, 5) ?></div>
                </div>
            </div>
            <div class="info-item">
                <div class="icone"><i class="fas fa-trophy"></i></div>
                <div>
                    <div class="label">Compétition</div>
                    <div class="valeur"><?= htmlspecialchars($match['nom_championnat']) ?></div>
                </div>
            </div>
            <div class="info-item">
                <div class="icone"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <div class="label">Lieu</div>
                    <div class="valeur"><?= htmlspecialchars($match['lieu']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Scores par période ──────────────────────────────── -->
    <?php if (!empty($periodes)): ?>
    <div class="carte">
        <h2><i class="fas fa-chart-bar"></i> Scores par période</h2>
        <div class="periodes-grille">
            <?php foreach ($periodes as $p): ?>
            <div class="periode-bloc">
                <div class="p-num">Période <?= $p['num_periode'] ?></div>
                <div class="p-score">
                    <?= $p['score_B'] ?><span> – </span><?= $p['score_N'] ?>
                </div>
                <div style="font-size:11px;color:#aaa;margin-top:4px">B &nbsp;·&nbsp; N</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Joueurs des deux équipes ───────────────────────── -->
    <div class="carte">
        <h2><i class="fas fa-users"></i> Joueurs</h2>
        <div class="deux-equipes">

            <!-- Blancs (domicile) -->
            <div class="equipe-bloc blancs">
                <h3><span class="badge-couleur badge-blanc"></span><?= htmlspecialchars($match['equipe_B']) ?></h3>
                <?php if (empty($joueursB)): ?>
                    <p class="vide">Aucun joueur enregistré.</p>
                <?php else: ?>
                <table class="joueurs">
                    <thead>
                        <tr>
                            <th class="centre">N°</th>
                            <th>Nom</th>
                            <th class="centre">Buts</th>
                            <th class="centre">X</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($joueursB as $j): ?>
                        <tr>
                            <td class="centre"><span class="bonnet bonnet-b"><?= $j['numero_bonnet'] ?></span></td>
                            <td><?= htmlspecialchars($j['nom_joueur']) ?></td>
                            <td class="centre">
                                <?php if ($j['buts'] > 0): ?>
                                    <span class="buts-badge"><?= $j['buts'] ?></span>
                                <?php else: ?>
                                    <span class="buts-zero">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="centre">
                                <?= $j['exclu'] ? '<span class="exclu-oui" title="Exclu définitif">✕</span>' : '' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Noirs (visiteur) -->
            <div class="equipe-bloc noirs">
                <h3><span class="badge-couleur badge-noir"></span><?= htmlspecialchars($match['equipe_N']) ?></h3>
                <?php if (empty($joueursN)): ?>
                    <p class="vide" style="color:#888">Aucun joueur enregistré.</p>
                <?php else: ?>
                <table class="joueurs">
                    <thead>
                        <tr>
                            <th class="centre">N°</th>
                            <th>Nom</th>
                            <th class="centre">Buts</th>
                            <th class="centre">X</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($joueursN as $j): ?>
                        <tr>
                            <td class="centre"><span class="bonnet bonnet-n"><?= $j['numero_bonnet'] ?></span></td>
                            <td><?= htmlspecialchars($j['nom_joueur']) ?></td>
                            <td class="centre">
                                <?php if ($j['buts'] > 0): ?>
                                    <span class="buts-badge"><?= $j['buts'] ?></span>
                                <?php else: ?>
                                    <span class="buts-zero">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="centre">
                                <?= $j['exclu'] ? '<span class="exclu-oui" title="Exclu définitif">✕</span>' : '' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ── Chrono général ─────────────────────────────────── -->
    <?php if (!empty($evenements)): ?>
    <div class="carte">
        <h2><i class="fas fa-stopwatch"></i> Chrono du match</h2>
        <?php
        // Regrouper par période
        $evParPeriode = [];
        foreach ($evenements as $ev) {
            $evParPeriode[$ev['num_periode']][] = $ev;
        }
        ksort($evParPeriode);
        ?>
        <?php foreach ($evParPeriode as $numP => $evs): ?>
        <div class="chrono-periode">
            <h4>Période <?= $numP ?></h4>
            <table class="chrono">
                <thead>
                    <tr>
                        <th>Temps</th>
                        <th>Équipe</th>
                        <th>Bonnet</th>
                        <th>Action</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evs as $ev):
                        // Détermine la classe de la pastille action
                        $codeUp = strtoupper((string)$ev['code_action']);
                        if (str_contains($codeUp, 'BUT'))        $cls = 'code-BUT';
                        elseif (str_contains($codeUp, 'EXCL'))   $cls = 'code-EXCLUSION';
                        elseif (str_contains($codeUp, 'PENALTY')) $cls = 'code-PENALTY';
                        elseif (str_contains($codeUp, 'TM') || str_contains($codeUp, 'TEMPS')) $cls = 'code-TEMPS_MORT';
                        else $cls = 'code-AUTRE';

                        $equipeLabel = '';
                        if ($ev['couleur'] === 'B') $equipeLabel = '<span style="background:#eee;padding:2px 7px;border-radius:4px;font-size:12px">Blanc</span>';
                        if ($ev['couleur'] === 'N') $equipeLabel = '<span style="background:#333;color:#fff;padding:2px 7px;border-radius:4px;font-size:12px">Noir</span>';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ev['temps']) ?></strong></td>
                        <td><?= $equipeLabel ?></td>
                        <td class="centre">
                            <?php if ($ev['numero_bonnet']): ?>
                                <span class="bonnet <?= $ev['couleur'] === 'N' ? 'bonnet-n' : 'bonnet-b' ?>">
                                    <?= $ev['numero_bonnet'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="code-action <?= $cls ?>"><?= htmlspecialchars($ev['code_action']) ?></span></td>
                        <td><?= htmlspecialchars($ev['score'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Staff ─────────────────────────────────────────── -->
    <?php if (!empty($staff)): ?>
    <div class="carte">
        <h2><i class="fas fa-id-badge"></i> Staff technique</h2>
        <div class="staff-grille">
            <?php foreach ($staff as $s): ?>
            <div class="staff-item">
                <div class="s-equipe"><?= htmlspecialchars($s['nom_equipe']) ?></div>
                <div class="s-label"><?= $roleLabels[$s['role']] ?? $s['role'] ?></div>
                <div class="s-nom"><?= htmlspecialchars($s['nom_prenom']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Officiels ─────────────────────────────────────── -->
    <?php if (!empty($officiels)): ?>
    <div class="carte">
        <h2><i class="fas fa-user-tie"></i> Officiels</h2>
        <div class="staff-grille">
            <?php foreach ($officiels as $o): ?>
            <div class="staff-item">
                <div class="s-label"><?= $officielLabels[$o['role']] ?? $o['role'] ?></div>
                <div class="s-nom"><?= htmlspecialchars($o['nom_prenom']) ?></div>
                <?php if ($o['iuf']): ?>
                <div style="font-size:11px;color:#aaa;margin-top:2px">IUF : <?= htmlspecialchars($o['iuf']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; /* fin if($match) */ ?>
</main>

</body>
</html>

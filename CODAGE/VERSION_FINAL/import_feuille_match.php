<?php
/**
 * import_feuille_match.php
 * Import d'une feuille de match waterpolo (.xlsx officiel) dans site_waterpolo.
 * Dépendance : composer require phpoffice/phpspreadsheet
 *
 * Layout réel de l'onglet « Feuile de Match » (vérifié) :
 *   Équipe domicile (Blancs/B) : C3        Équipe visiteur (Noirs/N) : C29
 *   Compétition : AK3   Lieu : AI4   Date : AI5   Heure : AN5
 *   Score final : AE3 (B)  /  AE5 (N)
 *   En-têtes joueurs « IUF » : ligne 8 (domicile) et ligne 34 (visiteur)
 *     -> 15 joueurs sous chaque en-tête (header+1 .. header+15)
 *     -> colonnes : B=IUF  C=NOM  P=NAISS  Q=X(exclu)  R=N°bonnet  S→X=BUTS (zone fusionnée 19-24)
 *     -> 3 paires exclusion Code/Période : Z/AA, AB/AC, AD/AE
 *   Grille « RESULTATS » par période : libellés col Z (lignes 26,27,29,30),
 *                                       scores col AD (B) / AE (N)
 *   Staff : libellé col Q, nom col C  (domicile 24-26, visiteur 50-52)
 *   Officiels (haut droite) : secrétaire/chrono ligne 8, juges ligne 10
 *   Arbitres / délégués : lignes 41-45 (libellé col AH, nom col AK, IUF col AT)
 *   Chrono général : 3 colonnes (AH.. / AN.. / AT..), lignes 13->39
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// ─── Connexion PDO ────────────────────────────────────────
$host   = 'localhost';
$user   = 'odd';
$passwd = 'odd';
$dbname = 'site_waterpolo';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $passwd,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die(json_encode(['erreur' => 'Connexion BDD : ' . $e->getMessage()]));
}

// ─── Upload ───────────────────────────────────────────────
if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== 0) {
    die(json_encode(['erreur' => 'Fichier manquant ou erreur d\'upload.']));
}

// ─── Chargement xlsx ──────────────────────────────────────
try {
    $spreadsheet = IOFactory::load($_FILES['xlsx_file']['tmp_name']);
} catch (\Exception $e) {
    die(json_encode(['erreur' => 'Lecture xlsx : ' . $e->getMessage()]));
}

$ws = $spreadsheet->getSheetByName('Feuile de Match');
if (!$ws) {
    die(json_encode(['erreur' => "Onglet 'Feuile de Match' introuvable."]));
}

// ─── Helpers de lecture ───────────────────────────────────
// Lire une cellule par référence ex: 'C3'
$g = fn(string $ref): ?string => (
    ($v = $ws->getCell($ref)->getCalculatedValue()) !== null && $v !== ''
        ? trim((string)$v) : null
);
// Lire une cellule par numéro colonne + ligne
$gc = fn(int $col, int $row): ?string => (
    ($v = $ws->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getCalculatedValue()) !== null && $v !== ''
        ? trim((string)$v) : null
);
// Parse date DD/MM/YYYY ou valeur numérique Excel
$parseDate = function (?string $raw): ?string {
    if ($raw === null) return null;
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $raw, $m))
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    if (is_numeric($raw))
        return XlsDate::excelToDateTimeObject((float)$raw)->format('Y-m-d');
    return null;
};
// Parse heure HH:MM (texte) ou fraction de jour Excel (0.375 = 09:00)
$parseTime = function (?string $raw): string {
    if ($raw === null || $raw === '') return '00:00';
    if (preg_match('/^(\d{1,2}):(\d{2})/', $raw, $m))
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    if (is_numeric($raw)) {
        $sec = (int)round(((float)$raw - floor((float)$raw)) * 86400);
        return sprintf('%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60));
    }
    return '00:00';
};
// Extraire la partie après "LABEL : valeur"
$extractNom = fn(?string $raw): string =>
    $raw === null ? '' : (str_contains($raw, ':') ? trim(explode(':', $raw, 2)[1]) : trim($raw));
// Extraire un numéro depuis "IUF N° : 12345"
$extractIUF = function (?string $raw): ?string {
    if ($raw === null) return null;
    if (preg_match('/\d+/', $raw, $m)) return $m[0];
    return null;
};

// ─── Lecture des infos générales ─────────────────────────
$equipe1Nom  = $g('C3');
$competition = $g('AK3');
$lieu        = $g('AI4');
$dateMatch   = $parseDate($g('AI5'));
$heureMatch  = $parseTime($g('AN5'));

// Équipe 2 (Noirs) : chercher "Équipe :" dans col B après la ligne 24
$equipe2Nom = null;
for ($r = 24; $r <= 40; $r++) {
    $label = (string)($ws->getCell('B' . $r)->getCalculatedValue() ?? '');
    $val   = (string)($ws->getCell('C' . $r)->getCalculatedValue() ?? '');
    if (!empty(trim($val)) && str_contains($label, 'quipe')) {
        $equipe2Nom = trim($val);
        break;
    }
}
$equipe2Nom = $equipe2Nom ?? 'Équipe visiteur';

// ─── Détection des blocs « joueurs » via les en-têtes « IUF » ──
// L'en-tête de chaque équipe est la cellule de la colonne B valant "IUF".
// Les 15 joueurs se trouvent sur les 15 lignes suivantes.
$headerRows = [];
for ($r = 1; $r <= 60; $r++) {
    $v = trim((string)($ws->getCell('B' . $r)->getCalculatedValue() ?? ''));
    if (mb_strtoupper($v) === 'IUF') $headerRows[] = $r;
}
$rowsEquipe1 = isset($headerRows[0]) ? range($headerRows[0] + 1, $headerRows[0] + 15) : range(9, 23);
$rowsEquipe2 = isset($headerRows[1]) ? range($headerRows[1] + 1, $headerRows[1] + 15) : range(35, 49);

// ─── UPSERT helper ───────────────────────────────────────
function upsertGet(PDO $pdo, string $table, array $where, array $extra = []): int
{
    $whereSQL = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
    $sel = $pdo->prepare("SELECT id_{$table} FROM `{$table}` WHERE $whereSQL LIMIT 1");
    $sel->execute(array_values($where));
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row["id_{$table}"];
    $all  = array_merge($where, $extra);
    $ph   = implode(',', array_fill(0, count($all), '?'));
    $cols = '`' . implode('`,`', array_keys($all)) . '`';
    $pdo->prepare("INSERT INTO `{$table}` ($cols) VALUES ($ph)")->execute(array_values($all));
    return (int)$pdo->lastInsertId();
}

// ─── Saison / Niveau / Championnat / Structure / Équipes ─
$annee    = $dateMatch ? (int)substr($dateMatch, 0, 4) : (int)date('Y');
$idSaison = upsertGet($pdo, 'saison',  ['saison'  => $annee]);
$idNiveau = upsertGet($pdo, 'niveau',  ['niveau'  => 'FF Natation']);
$idChamp  = upsertGet($pdo, 'championnat', [
    'nom_championnat' => $competition ?? 'Non renseigné',
    'id_saison'       => $idSaison,
    'id_niveau'       => $idNiveau,
]);
$idStruct  = upsertGet($pdo, 'structure', [
    'nom_structure'  => $lieu ?? 'Non renseigné',
    'lieu_structure' => $lieu ?? 'Non renseigné',
]);
$idEquipe1 = upsertGet($pdo, 'equipe', ['nom_equipe' => $equipe1Nom ?? 'Équipe domicile']);
$idEquipe2 = upsertGet($pdo, 'equipe', ['nom_equipe' => $equipe2Nom]);

// ─── Score final (lu directement sur la feuille) ─────────
$scoreBraw = $ws->getCell('AE3')->getCalculatedValue();
$scoreNraw = $ws->getCell('AE5')->getCalculatedValue();
$scoreB    = is_numeric($scoreBraw) ? (int)$scoreBraw : null;
$scoreN    = is_numeric($scoreNraw) ? (int)$scoreNraw : null;

// ─── Idempotence : supprimer un éventuel import précédent du même match ──
// (les FK ON DELETE CASCADE nettoient participation/periode/evenement/etc.)
$del = $pdo->prepare(
    "DELETE FROM matchs WHERE date_matchs = ? AND id_equipe_domicile = ? AND id_equipe_visiteur = ?"
);
$del->execute([$dateMatch, $idEquipe1, $idEquipe2]);

// ─── Match ────────────────────────────────────────────────
$pdo->prepare("
    INSERT INTO `matchs`
        (date_matchs, heure_matchs, id_equipe_domicile, id_equipe_visiteur,
         id_championnat, id_structure, score_domicile, score_visiteur)
    VALUES (:d, :h, :e1, :e2, :ch, :st, :sb, :sn)
")->execute([
    ':d'  => $dateMatch,
    ':h'  => $heureMatch,
    ':e1' => $idEquipe1,
    ':e2' => $idEquipe2,
    ':ch' => $idChamp,
    ':st' => $idStruct,
    ':sb' => $scoreB ?? 0,
    ':sn' => $scoreN ?? 0,
]);
$idMatch = (int)$pdo->lastInsertId();

// ─── Périodes ─────────────────────────────────────────────
// Grille « RESULTATS » : libellés en col Z (26), scores en col AD (30) / AE (31).
// Les lignes ne sont PAS contiguës (ex. 26, 27, 29, 30) -> on les détecte
// par le libellé contenant "PERIODE" et un chiffre 1-4.
$periodScores = [];                       // [num => [B, N]]
for ($r = 24; $r <= 33; $r++) {
    $label = (string)($ws->getCell('Z' . $r)->getCalculatedValue() ?? '');
    $labelU = mb_strtoupper($label);
    if (str_contains($labelU, 'PERIODE') && preg_match('/[1-4]/', $label, $m)) {
        $num = (int)$m[0];
        $b   = $gc(30, $r);
        $n   = $gc(31, $r);
        $periodScores[$num] = [
            is_numeric($b) ? (int)$b : null,
            is_numeric($n) ? (int)$n : null,
        ];
    }
}

$idPeriodes = [];
$stmtPer = $pdo->prepare("
    INSERT INTO `periode` (num_periode, id_matchs, score_B, score_N)
    VALUES (:n, :m, :b, :nv)
");
for ($p = 1; $p <= 4; $p++) {
    [$b, $n] = $periodScores[$p] ?? [null, null];
    $stmtPer->execute([
        ':n'  => $p,
        ':m'  => $idMatch,
        ':b'  => $b ?? 0,
        ':nv' => $n ?? 0,
    ]);
    $idPeriodes[$p] = (int)$pdo->lastInsertId();
}

// Scores cumulés de fin de période (pour rattacher les événements du chrono).
// Si une période est vide, on retombe sur le score final.
$endB = [];
$endN = [];
for ($p = 1; $p <= 4; $p++) {
    $endB[$p] = $periodScores[$p][0] ?? ($scoreB ?? 99);
    $endN[$p] = $periodScores[$p][1] ?? ($scoreN ?? 99);
}
$endB[4] = $scoreB ?? $endB[4];
$endN[4] = $scoreN ?? $endN[4];

// ─── Joueurs + Participations + Exclusions ────────────────
$stmtJ = $pdo->prepare("
    INSERT INTO `joueur` (iuf, nom_joueur, annee_naissance, id_equipe)
    VALUES (:iuf, :nom, :naiss, :eq)
    ON DUPLICATE KEY UPDATE nom_joueur=VALUES(nom_joueur), annee_naissance=VALUES(annee_naissance), id_equipe=VALUES(id_equipe)
");
$stmtP = $pdo->prepare("
    INSERT INTO `participation` (id_matchs, id_joueur, numero_bonnet, exclu, buts)
    VALUES (:m, :j, :b, :x, :buts)
    ON DUPLICATE KEY UPDATE numero_bonnet=VALUES(numero_bonnet), exclu=VALUES(exclu), buts=VALUES(buts)
");
$stmtEx = $pdo->prepare("
    INSERT INTO `exclusion_periode` (id_matchs, id_joueur, num_occurrence, code, num_periode)
    VALUES (:m, :j, :occ, :code, :per)
");

$importJoueurs = function (array $rows, int $idEquipe)
    use ($pdo, $ws, $idMatch, $stmtJ, $stmtP, $stmtEx): void
{
    foreach ($rows as $row) {
        $iuf = trim((string)($ws->getCell('B' . $row)->getCalculatedValue() ?? ''));
        $nom = trim((string)($ws->getCell('C' . $row)->getCalculatedValue() ?? ''));
        $bonnet = $ws->getCell('R' . $row)->getCalculatedValue();

        // Un joueur valide a un nom ET un numéro de bonnet.
        if ($nom === '' || !is_numeric($bonnet)) continue;
        // Ignorer une éventuelle ligne d'en-tête tombée dans la plage.
        if (mb_strtoupper($iuf) === 'IUF' || mb_strtoupper($nom) === 'NOMS - PRÉNOMS') continue;
        // L'IUF est la clé unique : si absente, en synthétiser une stable.
        if ($iuf === '') $iuf = 'AUTO-' . $idEquipe . '-' . (int)$bonnet;

        $naiss  = $ws->getCell('P' . $row)->getCalculatedValue();
        $exclu  = $ws->getCell('Q' . $row)->getCalculatedValue();

        // « BUTS » est une zone fusionnée S→X (colonnes 19 à 24) : les buts
        // d'un joueur peuvent être répartis dans plusieurs sous-cellules.
        // Le total = somme de S à X (rétro-compatible : si tout est en S, = S).
        $buts = 0;
        for ($cb = 19; $cb <= 24; $cb++) {
            $bv = $ws->getCell(Coordinate::stringFromColumnIndex($cb) . $row)->getCalculatedValue();
            if (is_numeric($bv)) $buts += (int)$bv;
        }

        $stmtJ->execute([
            ':iuf'   => $iuf,
            ':nom'   => $nom,
            ':naiss' => is_numeric($naiss) ? (int)$naiss : null,
            ':eq'    => $idEquipe,
        ]);
        $idJoueur = (int)$pdo->query(
            "SELECT id_joueur FROM joueur WHERE iuf=" . $pdo->quote($iuf) . " LIMIT 1"
        )->fetchColumn();

        $stmtP->execute([
            ':m'    => $idMatch,
            ':j'    => $idJoueur,
            ':b'    => (int)$bonnet,
            ':x'    => (!empty($exclu) && strtoupper(trim((string)$exclu)) === 'X') ? 1 : 0,
            ':buts' => $buts,
        ]);

        // 3 paires Code/Période : Z(26)/AA(27), AB(28)/AC(29), AD(30)/AE(31)
        foreach ([[26, 27], [28, 29], [30, 31]] as $occ => [$cCode, $cPer]) {
            $code = $ws->getCell(Coordinate::stringFromColumnIndex($cCode) . $row)->getCalculatedValue();
            $per  = $ws->getCell(Coordinate::stringFromColumnIndex($cPer) . $row)->getCalculatedValue();
            if (!empty($code)) {
                $stmtEx->execute([
                    ':m'    => $idMatch,
                    ':j'    => $idJoueur,
                    ':occ'  => $occ + 1,
                    ':code' => trim((string)$code),
                    ':per'  => is_numeric($per) ? (int)$per : null,
                ]);
            }
        }
    }
};

$importJoueurs($rowsEquipe1, $idEquipe1);
$importJoueurs($rowsEquipe2, $idEquipe2);

// ─── Staff ────────────────────────────────────────────────
// Libellé du rôle en col Q, nom en col C (sinon B). Domicile 24-26, visiteur 50-52.
$stmtStaff = $pdo->prepare("
    INSERT INTO `staff_match` (id_matchs, id_equipe, nom_prenom, role) VALUES (:m, :e, :n, :r)
");
$roleMap = ['ENTRAÎNEUR' => 'ENTRAINEUR', 'ENTRAÎNEUR ADJOINT' => 'ADJOINT', 'SUPPLÉANT' => 'SUPPLEANT'];
$staffZones = [
    [$idEquipe1, [24, 25, 26]],
    [$idEquipe2, [50, 51, 52]],
];
foreach ($staffZones as [$idEq, $staffRows]) {
    foreach ($staffRows as $r) {
        $roleRaw = trim((string)($ws->getCell('Q' . $r)->getCalculatedValue() ?? ''));
        $nomS = trim((string)($ws->getCell('C' . $r)->getCalculatedValue() ?? ''));
        if ($nomS === '') {
            $nomS = trim((string)($ws->getCell('B' . $r)->getCalculatedValue() ?? ''));
        }
        if ($roleRaw === '' || $nomS === '') continue;
        $roleNorm = null;
        foreach ($roleMap as $k => $v) {
            if (mb_strtoupper($k) === mb_strtoupper($roleRaw)) { $roleNorm = $v; break; }
        }
        if (!$roleNorm) continue;
        $stmtStaff->execute([':m' => $idMatch, ':e' => $idEq, ':n' => $nomS, ':r' => $roleNorm]);
    }
}

// ─── Officiels ────────────────────────────────────────────
$stmtOff = $pdo->prepare("INSERT INTO `officiel` (nom_prenom, iuf, role) VALUES (:n, :i, :r)");
$stmtMO  = $pdo->prepare("INSERT INTO `match_officiel` (id_matchs, id_officiel) VALUES (:m, :o)");

// Première valeur non vide à droite du label (cols 35→43) — utilisé pour arbitres/délégués.
$getValeurLigne = function (int $row) use ($gc): string {
    foreach (range(35, 43) as $col) {
        $v = $gc($col, $row);
        if ($v !== null && trim($v) !== '') return trim($v);
    }
    return '';
};

$addOfficiel = function (string $nomRaw, ?string $iufRaw, string $role)
    use ($pdo, $stmtOff, $stmtMO, $idMatch, $extractNom, $extractIUF): void
{
    $nom = $extractNom($nomRaw);
    if (empty($nom)) return;
    $iuf = $extractIUF($iufRaw ?? '');
    $stmtOff->execute([':n' => $nom, ':i' => $iuf, ':r' => $role]);
    $idOff = (int)$pdo->lastInsertId();
    $stmtMO->execute([':m' => $idMatch, ':o' => $idOff]);
};

// Secrétaire / chrono (ligne 8) et juges de but (ligne 10) : label + nom dans une seule cellule.
$addOfficiel($gc(34, 8) ?? '', $gc(40, 8), 'SECRETAIRE');
$addOfficiel($gc(43, 8) ?? '', $gc(49, 8), 'CHRONO');
$addOfficiel($gc(34, 10) ?? '', $gc(40, 10), 'JUGE_BUT');
$addOfficiel($gc(43, 10) ?? '', $gc(49, 10), 'JUGE_BUT');

// Arbitres et délégué FFN : libellé col AH, nom à droite, IUF col AT(46)/AU(47).
foreach ([
    [43, 'ARBITRE'],
    [44, 'ARBITRE'],
    [45, 'DELEGUE_FFN'],
] as [$rowNum, $role]) {
    $nom = $getValeurLigne($rowNum);
    $iuf = $gc(46, $rowNum) ?? $gc(47, $rowNum);
    if ($nom !== '') $addOfficiel($nom, $iuf, $role);
}

// ─── Délégués de club ─────────────────────────────────────
// Lignes fixes : 41 = DÉLÉGUÉ BLANC, 42 = DÉLÉGUÉ NOIR. Nom à droite du label.
$stmtDel = $pdo->prepare("
    INSERT INTO `delegue_club` (id_matchs, nom_prenom, couleur) VALUES (:m, :n, :c)
");
$delB = $getValeurLigne(41);
$delN = $getValeurLigne(42);
if ($delB !== '') $stmtDel->execute([':m' => $idMatch, ':n' => $delB, ':c' => 'B']);
if ($delN !== '') $stmtDel->execute([':m' => $idMatch, ':n' => $delN, ':c' => 'N']);

// ─── Chrono général (événements) ─────────────────────────
// 3 colonnes chrono lues l'une après l'autre (et non en mélangeant les lignes) :
//   Col 1 : AH(34)=temps AI(35)=B AJ(36)=N AK(37)=code AL(38)=score
//   Col 2 : AN(40)=temps AO(41)=B AP(42)=N AQ(43)=code AR(44)=score
//   Col 3 : AT(46)=temps AU(47)=B AV(48)=N AW(49)=code AX(50)=score
$stmtEv = $pdo->prepare("
    INSERT INTO `evenement`
        (id_matchs, id_periode, temps, couleur, numero_bonnet, code_action, score)
    VALUES (:m, :p, :t, :c, :n, :a, :s)
");

$chronoCols = [
    [34, 35, 36, 37, 38],
    [40, 41, 42, 43, 44],
    [46, 47, 48, 49, 50],
];

$parseTemps = function (?string $raw): ?string {
    if ($raw === null) return null;
    if (preg_match('/^\d{1,2}:\d{2}$/', $raw)) return $raw;
    if (is_numeric($raw) && (float)$raw < 1) {
        $sec = (int)round((float)$raw * 86400);
        return sprintf('%02d:%02d', intdiv($sec, 60), $sec % 60);
    }
    return null;
};

// Détermine la période d'un événement à partir de son score cumulé.
$periodeFromScore = function (?string $score, int &$pIdx) use ($endB, $endN): int {
    if ($score !== null && preg_match('/^(\d+)\s*-\s*(\d+)$/', $score, $m)) {
        $b = (int)$m[1]; $n = (int)$m[2];
        while ($pIdx < 4 && ($b > $endB[$pIdx] || $n > $endN[$pIdx])) $pIdx++;
    }
    return $pIdx;
};

$tempsMort = [];   // [ "equipe-periode" => nb ]
$pIdx = 1;

foreach ($chronoCols as [$cT, $cB, $cN, $cC, $cS]) {
    for ($row = 13; $row <= 39; $row++) {
        $code = $gc($cC, $row);
        if (empty($code) || in_array(mb_strtoupper((string)$code), ['CODE', 'TEMPS'])) continue;

        $temps = $parseTemps($gc($cT, $row));
        if ($temps === null) continue;

        $valB  = $gc($cB, $row);
        $valN  = $gc($cN, $row);
        $score = $gc($cS, $row);

        $couleur = null; $numBonnet = null;
        if ($valB !== null && is_numeric($valB)) {
            $couleur = 'B'; $numBonnet = (int)$valB;
        } elseif ($valN !== null && is_numeric($valN)) {
            $couleur = 'N'; $numBonnet = (int)$valN;
        }

        $periode = $periodeFromScore($score, $pIdx);
        $codeU   = mb_strtoupper((string)$code);

        $stmtEv->execute([
            ':m' => $idMatch,
            ':p' => $idPeriodes[$periode] ?? $idPeriodes[1],
            ':t' => $temps,
            ':c' => $couleur,
            ':n' => $numBonnet,
            ':a' => (string)$code,
            ':s' => $score,
        ]);

        // Temps mort détecté dans le chrono
        if (str_contains($codeU, 'TM') || str_contains($codeU, 'TEMPS MORT')) {
            $idEq = ($couleur === 'N') ? $idEquipe2 : $idEquipe1;
            $key  = $idEq . '-' . $periode;
            $tempsMort[$key] = ($tempsMort[$key] ?? 0) + 1;
        }
    }
}

// ─── Temps morts ──────────────────────────────────────────
if ($tempsMort) {
    $stmtTM = $pdo->prepare("
        INSERT INTO `temps_mort` (id_matchs, id_equipe, num_periode, nb) VALUES (:m, :e, :p, :nb)
    ");
    foreach ($tempsMort as $key => $nb) {
        [$idEq, $per] = explode('-', $key);
        $stmtTM->execute([':m' => $idMatch, ':e' => (int)$idEq, ':p' => (int)$per, ':nb' => $nb]);
    }
}

// ─── Score final : compléter si AE3/AE5 absents (fallback dernier événement) ──
if ($scoreB === null || $scoreN === null) {
    $sf = $pdo->query(
        "SELECT score FROM evenement WHERE id_matchs={$idMatch} AND score IS NOT NULL ORDER BY id_evenement DESC LIMIT 1"
    )->fetchColumn();
    if ($sf && preg_match('/^(\d+)-(\d+)$/', $sf, $m)) {
        $scoreB = (int)$m[1]; $scoreN = (int)$m[2];
        $pdo->prepare("UPDATE matchs SET score_domicile=?, score_visiteur=? WHERE id_matchs=?")
            ->execute([$scoreB, $scoreN, $idMatch]);
    }
}
$scoreFinal = ($scoreB ?? '?') . '-' . ($scoreN ?? '?');

// ─── Réponse ─────────────────────────────────────────────
echo json_encode([
    'success'     => true,
    'id_matchs'   => $idMatch,
    'equipe_B'    => $equipe1Nom,
    'equipe_N'    => $equipe2Nom,
    'date'        => $dateMatch,
    'heure'       => $heureMatch,
    'competition' => $competition,
    'lieu'        => $lieu,
    'score_final' => $scoreFinal,
    'message'     => 'Feuille de match importée avec succès.',
], JSON_UNESCAPED_UNICODE);

?>
<div style="text-align:center; margin-top:20px;">
    <a href="index1.php" style="padding:10px 20px; background:#2196F3; color:white; text-decoration:none; border-radius:5px;">Retour aux résultats</a>
</div>
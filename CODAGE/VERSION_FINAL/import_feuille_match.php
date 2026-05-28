<?php
/**
 * import_feuille_match.php
 * Import d'une feuille de match waterpolo (.xlsx officiel) dans site_waterpolo.
 * Dépendance : composer require phpoffice/phpspreadsheet
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

// ─── Helpers ──────────────────────────────────────────────
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
// Coordonnées vérifiées d'après analyse du fichier :
//   Équipe 1 (Blancs) : C3       Compétition : AK3
//   Lieu              : AI4      Date        : AI5
//   Heure             : AN5
$equipe1Nom  = $g('C3');
$competition = $g('AK3');
$lieu        = $g('AI4');
$dateMatch   = $parseDate($g('AI5'));
$heureMatch  = $g('AN5') ?? '00:00';

// Équipe 2 (Noirs) : chercher "Équipe :" dans col B après la ligne 24
$equipe2Nom = null;
$equipe2Row = null;
for ($r = 24; $r <= 40; $r++) {
    $label = (string)($ws->getCell('B' . $r)->getCalculatedValue() ?? '');
    $val   = (string)($ws->getCell('C' . $r)->getCalculatedValue() ?? '');
    if (!empty($val) && str_contains($label, 'quipe')) {
        $equipe2Nom = trim($val);
        $equipe2Row = $r;
        break;
    }
}
$equipe2Nom = $equipe2Nom ?? 'Équipe visiteur';

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

// ─── Match ────────────────────────────────────────────────
$pdo->prepare("
    INSERT INTO `matchs`
        (date_matchs, heure_matchs, id_equipe_domicile, id_equipe_visiteur,
         id_championnat, id_structure, score_domicile, score_visiteur)
    VALUES (:d, :h, :e1, :e2, :ch, :st, 0, 0)
")->execute([
    ':d'  => $dateMatch,
    ':h'  => $heureMatch,
    ':e1' => $idEquipe1,
    ':e2' => $idEquipe2,
    ':ch' => $idChamp,
    ':st' => $idStruct,
]);
$idMatch = (int)$pdo->lastInsertId();

// ─── Périodes ─────────────────────────────────────────────
// Scores par période : colonnes AD(30)=B et AE(31)=N, lignes 25→28
$idPeriodes = [];
for ($p = 1; $p <= 4; $p++) {
    $rowP = 24 + $p;
    $sB   = $gc(30, $rowP);
    $sN   = $gc(31, $rowP);
    $pdo->prepare("
        INSERT INTO `periode` (num_periode, id_matchs, score_B, score_N)
        VALUES (:n, :m, :b, :nv)
    ")->execute([
        ':n'  => $p,
        ':m'  => $idMatch,
        ':b'  => is_numeric($sB) ? (int)$sB : 0,
        ':nv' => is_numeric($sN) ? (int)$sN : 0,
    ]);
    $idPeriodes[$p] = (int)$pdo->lastInsertId();
}

// ─── Joueurs + Participations + Exclusions ────────────────
// Équipe 1 : lignes 9 → 22  |  Équipe 2 : equipe2Row+3 → equipe2Row+17
$stmtJ = $pdo->prepare("
    INSERT INTO `joueur` (iuf, nom_joueur, annee_naissance, id_equipe)
    VALUES (:iuf, :nom, :naiss, :eq)
    ON DUPLICATE KEY UPDATE nom_joueur=VALUES(nom_joueur), annee_naissance=VALUES(annee_naissance)
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
        $iuf    = (string)($ws->getCell('B' . $row)->getCalculatedValue() ?? '');
        $nom    = (string)($ws->getCell('C' . $row)->getCalculatedValue() ?? '');
        if (empty(trim($iuf)) || empty(trim($nom))) continue;
        $iuf = trim($iuf);
        $nom = trim($nom);
        if (!preg_match('/^\d{2}-\d{4}$/', $iuf)) continue;

        $naiss  = $ws->getCell('P' . $row)->getCalculatedValue();
        $exclu  = $ws->getCell('Q' . $row)->getCalculatedValue();
        $bonnet = $ws->getCell('R' . $row)->getCalculatedValue();
        $buts   = $ws->getCell('S' . $row)->getCalculatedValue();

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
            ':b'    => is_numeric($bonnet) ? (int)$bonnet : 0,
            ':x'    => (!empty($exclu) && strtoupper(trim((string)$exclu)) === 'X') ? 1 : 0,
            ':buts' => is_numeric($buts) ? (int)$buts : 0,
        ]);

        // 3 paires Code/Période : Z(26)/AA(27), AB(28)/AC(29), AD(30)/AE(31)
        foreach ([[26,27],[28,29],[30,31]] as $occ => [$cCode, $cPer]) {
            $colCode = Coordinate::stringFromColumnIndex($cCode);
            $colPer  = Coordinate::stringFromColumnIndex($cPer);
            $code    = $ws->getCell($colCode . $row)->getCalculatedValue();
            $per     = $ws->getCell($colPer  . $row)->getCalculatedValue();
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

$importJoueurs(range(9, 22), $idEquipe1);
$importJoueurs(
    $equipe2Row ? range($equipe2Row + 3, $equipe2Row + 17) : range(32, 48),
    $idEquipe2
);

// ─── Staff ────────────────────────────────────────────────
// Lignes avec ENTRAÎNEUR / ENTRAÎNEUR ADJOINT / SUPPLÉANT dans col Q(17)
$stmtStaff = $pdo->prepare("
    INSERT INTO `staff_match` (id_matchs, id_equipe, nom_prenom, role) VALUES (:m, :e, :n, :r)
");
$roleMap = ['ENTRAÎNEUR' => 'ENTRAINEUR', 'ENTRAÎNEUR ADJOINT' => 'ADJOINT', 'SUPPLÉANT' => 'SUPPLEANT'];
foreach ([[$idEquipe1, 9, 30], [$idEquipe2, 30, 56]] as [$idEq, $debut, $fin]) {
    for ($r = $debut; $r <= $fin; $r++) {
        $roleRaw = trim((string)($ws->getCell('Q' . $r)->getCalculatedValue() ?? ''));
        $nomS    = trim((string)($ws->getCell('C' . $r)->getCalculatedValue() ?? ''));
        if (empty($roleRaw) || empty($nomS)) continue;
        $roleNorm = null;
        foreach ($roleMap as $k => $v) {
            if (mb_strtoupper($k) === mb_strtoupper($roleRaw)) { $roleNorm = $v; break; }
        }
        if (!$roleNorm) continue;
        $stmtStaff->execute([':m' => $idMatch, ':e' => $idEq, ':n' => $nomS, ':r' => $roleNorm]);
    }
}

// ─── Officiels ────────────────────────────────────────────
// Ligne 8 : secrétaire col 34(AH) + IUF col 40(AN) | chrono col 43(AQ) + IUF col 49(AW)
// Ligne 10 : juge de but B col 34 + IUF col 40     | juge de but N col 43 + IUF col 49
$stmtOff = $pdo->prepare("INSERT INTO `officiel` (nom_prenom, iuf, role) VALUES (:n, :i, :r)");
$stmtMO  = $pdo->prepare("INSERT INTO `match_officiel` (id_matchs, id_officiel) VALUES (:m, :o)");

$addOfficiel = function (string $nomRaw, ?string $iufRaw, string $role)
    use ($pdo, $stmtOff, $stmtMO, $idMatch, $extractNom, $extractIUF): void
{
    $nom = $extractNom($nomRaw);
    if (empty($nom)) return;
    $iuf = $extractIUF($iufRaw ?? '');
    $r = $pdo->prepare("SELECT id_officiel FROM `officiel` WHERE nom_prenom=? AND role=? LIMIT 1");
    $r->execute([$nom, $role]);
    $existing = $r->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $idOff = (int)$existing['id_officiel'];
    } else {
        $stmtOff->execute([':n' => $nom, ':i' => $iuf, ':r' => $role]);
        $idOff = (int)$pdo->lastInsertId();
    }
    $stmtMO->execute([':m' => $idMatch, ':o' => $idOff]);
};

$addOfficiel($gc(34, 8) ?? '', $gc(40, 8), 'SECRETAIRE');
$addOfficiel($gc(43, 8) ?? '', $gc(49, 8), 'CHRONO');
$addOfficiel($gc(34, 10) ?? '', $gc(40, 10), 'JUGE_BUT');
$addOfficiel($gc(43, 10) ?? '', $gc(49, 10), 'JUGE_BUT');

// Arbitres et délégués : scan des lignes basses
for ($r = 35; $r <= 56; $r++) {
    $label = mb_strtoupper(trim((string)($gc(34, $r) ?? '')));
    $val   = $gc(37, $r) ?? '';
    $iuf   = $gc(47, $r);
    if (empty($val)) continue;
    if (str_starts_with($label, 'ARBITRE')) {
        $addOfficiel($val, $iuf, 'ARBITRE');
    } elseif (str_starts_with($label, 'DÉLÉGUÉ F') || str_starts_with($label, 'DELEGUE F')) {
        $addOfficiel($val, $iuf, 'DELEGUE_FFN');
    }
}

// ─── Délégués de club ─────────────────────────────────────
$stmtDel = $pdo->prepare("
    INSERT INTO `delegue_club` (id_matchs, nom_prenom, couleur) VALUES (:m, :n, :c)
");
for ($r = 35; $r <= 50; $r++) {
    $label = mb_strtoupper(trim((string)($gc(34, $r) ?? '')));
    $val   = $gc(37, $r) ?? '';
    if (empty($val)) continue;
    if (str_contains($label, 'BLANC')) {
        $stmtDel->execute([':m' => $idMatch, ':n' => $val, ':c' => 'B']);
    } elseif (str_contains($label, 'NOIR')) {
        $stmtDel->execute([':m' => $idMatch, ':n' => $val, ':c' => 'N']);
    }
}

// ─── Chrono général (événements) ─────────────────────────
// Colonnes : AH(34)=temps  AI(35)=bonnetB  AJ(36)=bonnetN  AK(37)=code  AL(38)=score
$stmtEv = $pdo->prepare("
    INSERT INTO `evenement`
        (id_matchs, id_periode, temps, couleur, numero_bonnet, code_action, score)
    VALUES (:m, :p, :t, :c, :n, :a, :s)
");

$currentPeriode = 1;
$lastSec        = null;

for ($row = 12; $row <= 36; $row++) {
    $tempsRaw = $gc(34, $row);
    $valB     = $gc(35, $row);
    $valN     = $gc(36, $row);
    $code     = $gc(37, $row);
    $score    = $gc(38, $row);
    if (empty($code)) continue;

    // Parser le temps mm:ss
    $temps = null;
    if ($tempsRaw !== null && preg_match('/^\d{1,2}:\d{2}$/', $tempsRaw)) {
        $temps = $tempsRaw;
    } elseif (is_numeric($tempsRaw) && (float)$tempsRaw < 1) {
        $sec   = (int)round((float)$tempsRaw * 86400);
        $temps = sprintf('%02d:%02d', (int)floor($sec / 60), $sec % 60);
    }
    if ($temps === null) continue;

    // Détection changement de période (temps repart à ~08:00)
    [$mm, $ss] = array_map('intval', explode(':', $temps));
    $sec = $mm * 60 + $ss;
    if ($lastSec !== null && $sec > $lastSec + 30 && $currentPeriode < 4) {
        $currentPeriode++;
    }
    $lastSec = $sec;

    $couleur   = null;
    $numBonnet = null;
    if ($valB !== null && is_numeric($valB)) {
        $couleur = 'B'; $numBonnet = (int)$valB;
    } elseif ($valN !== null && is_numeric($valN)) {
        $couleur = 'N'; $numBonnet = (int)$valN;
    }

    $stmtEv->execute([
        ':m' => $idMatch,
        ':p' => $idPeriodes[$currentPeriode] ?? $idPeriodes[1],
        ':t' => $temps,
        ':c' => $couleur,
        ':n' => $numBonnet,
        ':a' => $code,
        ':s' => $score,
    ]);
}

// ─── Score final ─────────────────────────────────────────
$sfStmt = $pdo->prepare(
    "SELECT score FROM evenement WHERE id_matchs=? AND score IS NOT NULL ORDER BY id_evenement DESC LIMIT 1"
);
$sfStmt->execute([$idMatch]);
$sf = $sfStmt->fetchColumn();
if ($sf && preg_match('/^(\d+)-(\d+)$/', $sf, $m)) {
    $pdo->prepare("UPDATE matchs SET score_domicile=?, score_visiteur=? WHERE id_matchs=?")
        ->execute([(int)$m[1], (int)$m[2], $idMatch]);
}

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
    'score_final' => $sf ?? '?',
    'message'     => 'Feuille de match importée avec succès.',
], JSON_UNESCAPED_UNICODE);
?>
<div style="text-align:center; margin-top:20px;">
    <a href="index1.php" style="padding:10px 20px; background:#2196F3; color:white; text-decoration:none; border-radius:5px;">Retour aux résultats</a>
</div>
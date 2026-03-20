<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 1. Configuration de la base de données (basée sur votre fichier SQL)
$host = 'localhost';
$db   = 'site_waterpolo';
$user = 'odd';
$pass = 'odd';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Récupération de l'ID du match (ID 3 par défaut selon vos données)
    $id_match = isset($_GET['id']) ? (int)$_GET['id'] : 3;

    // 3. Requête principale pour les infos du match
    $sqlMatch = "SELECT m.*, 
                 ed.nom_equipe AS dom_nom, ev.nom_equipe AS vis_nom,
                 a.nom_arbitre, a.prenom_arbitre, s.nom_structure
                 FROM matchs m
                 JOIN equipe ed ON m.id_equipe_domicile = ed.id_equipe
                 JOIN equipe ev ON m.id_equipe_visiteur = ev.id_equipe
                 JOIN arbitre a ON m.id_arbitre = a.id_arbitre
                 JOIN structure s ON m.id_structure = s.id_structure
                 WHERE m.id_matchs = ?";
    
    $stmt = $pdo->prepare($sqlMatch);
    $stmt->execute([$id_match]);
    $matchData = $stmt->fetch();

    if (!$matchData) {
        die("Erreur : Match non trouvé dans la base de données.");
    }

    // 4. Initialisation du tableur (Charger un template si vous en avez un)
    $spreadsheet = IOFactory::load("Feuille_de_match_Excel 44.xlsx");
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Feuille de Match");

    // 5. Remplissage des En-têtes (Positions basées sur votre script de lecture)
    $sheet->setCellValue('C3', $matchData['dom_nom']);      // Équipe Lion
    $sheet->setCellValue('C29', $matchData['vis_nom']);     // Équipe Aigle Royal
    $sheet->setCellValue('AI5', $matchData['date_matchs']); // Date (Cellule AH5/AI5)
    $sheet->setCellValue('AM5', $matchData['heure_matchs']);// Heure
    $sheet->setCellValue('AK43', $matchData['nom_arbitre']);// Arbitre

    // 6. Fonction pour remplir les joueurs et compter leurs buts
    function insererJoueurs($pdo, $sheet, $id_match, $id_equipe, $ligneDebut) {
        $sqlJ = "SELECT id_joueur, nom_joueur, prenom_joueur, numero_licence, annee_naissance 
                 FROM joueur 
                 WHERE id_equipe = ?";
        $stmtJ = $pdo->prepare($sqlJ);
        $stmtJ->execute([$id_equipe]);
        $joueurs = $stmtJ->fetchAll();

        $ligne = $ligneDebut;
        $scoreTotal = 0;

        foreach ($joueurs as $j) {
            // Comptage des buts pour ce match précis
            $stmtB = $pdo->prepare("SELECT COUNT(*) FROM but WHERE id_joueur = ? AND id_matchs = ?");
            $stmtB->execute([$j['id_joueur'], $id_match]);
            $nbButs = $stmtB->fetchColumn();

            // Injection dans les colonnes correspondantes
            $sheet->setCellValue('B' . $ligne, $j['numero_licence']); // IUF (B)
            $sheet->setCellValue('C' . $ligne, $j['nom_joueur'] . " " . $j['prenom_joueur']); // Nom (C)
            $sheet->setCellValue('N' . $ligne, $j['annee_naissance']); // Année (N)
            $sheet->setCellValue('S' . $ligne, $nbButs > 0 ? $nbButs : 0); // Buts (S)

            $scoreTotal += $nbButs;
            $ligne++;
            
            // Limite de la feuille (15 joueurs max par équipe)
            if ($ligne > ($ligneDebut + 14)) break;
        }
        return $scoreTotal;
    }

    // 7. Exécution pour les deux équipes
    // Zone Domicile : Lignes 9 à 23
    $totalDom = insererJoueurs($pdo, $sheet, $id_match, $matchData['id_equipe_domicile'], 9);
    $sheet->setCellValue('AE3', $totalDom); // Score total domicile

    // Zone Visiteur : Lignes 35 à 49
    $totalVis = insererJoueurs($pdo, $sheet, $id_match, $matchData['id_equipe_visiteur'], 35);
    $sheet->setCellValue('AE5', $totalVis); // Score total visiteur

    // 8. Envoi du fichier pour téléchargement
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Match_' . $id_match . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur générale : " . $e->getMessage());
}
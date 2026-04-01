<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// 1. Configuration de la connexion
$host = 'localhost';
$db   = 'site_waterpolo';
$user = 'odd';
$pass = 'odd';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $id_match = isset($_GET['id']) ? (int)$_GET['id'] : die("ID Match non spécifié.");

    // 2. Requête ultra-complète pour ne laisser aucune case vide
    $sqlMatch = "SELECT m.*, 
                 ed.nom_equipe AS dom_nom, ev.nom_equipe AS vis_nom,
                 ed.id_equipe AS id_dom, ev.id_equipe AS id_vis,
                 a.nom_arbitre, a.prenom_arbitre, 
                 s.nom_structure, s.lieu_structure,
                 c.nom_championnat, sn.saison
                 FROM matchs m
                 JOIN equipe ed ON m.id_equipe_domicile = ed.id_equipe
                 JOIN equipe ev ON m.id_equipe_visiteur = ev.id_equipe
                 JOIN arbitre a ON m.id_arbitre = a.id_arbitre
                 JOIN structure s ON m.id_structure = s.id_structure
                 JOIN championnat c ON m.id_championnat = c.id_championnat
                 JOIN saison sn ON c.id_saison = sn.id_saison
                 WHERE m.id_matchs = ?";
    
    $stmt = $pdo->prepare($sqlMatch);
    $stmt->execute([$id_match]);
    $matchData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$matchData) die("Match introuvable.");

    // 3. Chargement du template Excel
    $spreadsheet = IOFactory::load("Feuille_de_match_Excel 44.xlsx");
    $sheet = $spreadsheet->getActiveSheet();

    // 4. Remplissage des En-têtes (Identité du match)
    $sheet->setCellValue('C3', $matchData['dom_nom']);          // Équipe Domicile
    $sheet->setCellValue('C29', $matchData['vis_nom']);         // Équipe Visiteur
    $sheet->setCellValue('C5', $matchData['nom_championnat']);  // Compétition
    $sheet->setCellValue('N5', $matchData['lieu_structure']);   // Lieu (Piscine)
    $sheet->setCellValue('AI5', $matchData['date_matchs']);     // Date
    $sheet->setCellValue('AM5', $matchData['heure_matchs']);    // Heure
    $sheet->setCellValue('AK43', $matchData['nom_arbitre'] . " " . $matchData['prenom_arbitre']); // Arbitre principal

    // 5. Fonction pour remplir les listes de joueurs (Bonnets, Licences, Noms, Buts)
    function remplirListeJoueurs($pdo, $sheet, $id_match, $id_equipe, $ligneDebut) {
        $sqlJ = "SELECT * FROM joueur WHERE id_equipe = ? ORDER BY numero_bonnet ASC";
        $stmtJ = $pdo->prepare($sqlJ);
        $stmtJ->execute([$id_equipe]);
        $joueurs = $stmtJ->fetchAll(PDO::FETCH_ASSOC);

        $ligne = $ligneDebut;
        $scoreTotal = 0;

        foreach ($joueurs as $j) {
            // On compte les buts marqués par ce joueur dans CE match
            $stmtB = $pdo->prepare("SELECT COUNT(*) FROM but WHERE id_joueur = ? AND id_matchs = ?");
            $stmtB->execute([$j['id_joueur'], $id_match]);
            $nbButs = (int)$stmtB->fetchColumn();

            // Injection dans les colonnes du template
            $sheet->setCellValue('A' . $ligne, $j['numero_bonnet']);   // Bonnet
            $sheet->setCellValue('B' . $ligne, $j['numero_licence']);  // Licence
            $sheet->setCellValue('C' . $ligne, strtoupper($j['nom_joueur']) . " " . $j['prenom_joueur']);
            $sheet->setCellValue('N' . $ligne, $j['annee_naissance']); // Année naissance
            $sheet->setCellValue('S' . $ligne, $nbButs);               // Buts

            $scoreTotal += $nbButs;
            $ligne++;
            if ($ligne > ($ligneDebut + 14)) break; // Limite de 15 joueurs par équipe
        }
        return $scoreTotal;
    }

    // 6. Remplissage effectif
    $totalDom = remplirListeJoueurs($pdo, $sheet, $id_match, $matchData['id_dom'], 9);  // Domicile commence ligne 9
    $totalVis = remplirListeJoueurs($pdo, $sheet, $id_match, $matchData['id_vis'], 35); // Visiteur commence ligne 35

    // Scores finaux en haut de page
    $sheet->setCellValue('AE3', $totalDom);
    $sheet->setCellValue('AE5', $totalVis);

    // 7. Sortie du fichier
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Feuille_Match_' . $id_match . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Erreur technique : " . $e->getMessage());
}
<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 1. Connexion à la base de données SQLite (db.sqlite3)
try {
    $db = new PDO('sqlite:db.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 2. Récupération des données du match (Exemple pour le match ID 1)
// Les tables identifiées sont 'gestion_match', 'gestion_equipe' et 'gestion_joueur' 
$matchId = 1; 
$sqlMatch = "SELECT m.*, e.nom as equipe_nom 
             FROM gestion_match m 
             JOIN gestion_equipe e ON m.equipe_id = e.id 
             WHERE m.id = :id";
$stmt = $db->prepare($sqlMatch);
$stmt->execute(['id' => $matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match non trouvé.");
}

// 3. Récupération des joueurs de l'équipe
$sqlJoueurs = "SELECT nom, prenom, licence, naissance FROM gestion_joueur WHERE equipe_id = :equipe_id";
$stmtJ = $db->prepare($sqlJoueurs);
$stmtJ->execute(['equipe_id' => $match['equipe_id']]);
$joueurs = $stmtJ->fetchAll(PDO::FETCH_ASSOC);

// 4. Chargement de votre modèle Excel
$inputFileName = 'Feuille_de_match_Excel 44.xlsx';
$spreadsheet = IOFactory::load($inputFileName);
$sheet = $spreadsheet->getActiveSheet();

// 5. Remplissage des informations d'en-tête 
$sheet->setCellValue('C3', $match['equipe_nom']);       // Nom de l'équipe
$sheet->setCellValue('AD3', $match['competition']);    // Nom de la compétition
$sheet->setCellValue('AL4', $match['lieu']);           // Lieu du match
$sheet->setCellValue('AF5', $match['date']);           // Date (ex: 2026-02-17)

// 6. Remplissage de la liste des joueurs (Début à la ligne 10 selon le modèle) 
$row = 10;
foreach ($joueurs as $joueur) {
    $sheet->setCellValue('B' . $row, $joueur['licence']);                   // Colonne IUF 
    $sheet->setCellValue('C' . $row, $joueur['nom'] . ' ' . $joueur['prenom']); // Nom - Prénom 
    $sheet->setCellValue('P' . $row, $joueur['naissance']);                // Année de naissance 
    $row++;
    
    // Limitation à 15 joueurs par feuille selon le format standard
    if ($row > 25) break; 
}

// 7. Exportation du fichier rempli
$outputFileName = 'Feuille_Match_' . $match['equipe_nom'] . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
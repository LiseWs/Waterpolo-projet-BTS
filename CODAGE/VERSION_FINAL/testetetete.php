<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load("Feuille_de_match_Excel 44.xlsx");
$worksheet = $spreadsheet->getActiveSheet();
$data = $worksheet->toArray(null, true, true, false);

// Affiche toute la structure du tableau
echo "<pre>";
var_dump($data);
echo "</pre>";
?>
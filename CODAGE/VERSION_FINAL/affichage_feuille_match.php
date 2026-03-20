<?php
// Charger la bibliothèque PhpSpreadsheet avec l'autoloader
require 'vendor/autoload.php'; 

// Utiliser la classe IOFactory pour lire les fichiers Excel
use PhpOffice\PhpSpreadsheet\IOFactory;

// Vérifier si un fichier a été envoyé via un formulaire en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Vérifier l'absence d'erreur lors du téléchargement du fichier
    if ($_FILES['file']['error'] == 0) {
        // Récupérer le chemin temporaire du fichier téléchargé
        $filePath = $_FILES['file']['tmp_name'];
        
        // Charger la feuille Excel depuis le fichier temporaire
        $spreadsheet = IOFactory::load($filePath);
        
        // Obtenir la feuille active (par défaut, la première feuille)
        $sheet = $spreadsheet->getActiveSheet();
        
        // Convertir la feuille en un tableau PHP
        $data = $sheet->toArray();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feuille de Match</title>
    
    <!-- Lien vers la bibliothèque de styles Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles CSS intégrés pour la mise en page et l'apparence -->
    <style>
        /* Style global de la page */
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 30px auto; padding: 20px; background: #ffffff; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); }
        
        /* Style des titres et tableaux */
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }

        /* Style des boutons et formulaires */
        .upload-btn { display: flex; justify-content: center; align-items: center; gap: 10px; }
        input[type="file"] { padding: 8px; }
        button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }

        /* Style des liens et du menu de navigation */
        .bouton { margin-bottom: 15px; padding-right: 1250px; }
        .bouton a { background-color: #ff9a03; color: #fff; padding: 15px 25px; text-align: center; text-decoration: none; display: inline-block; font-size: 20px; border-radius: 10px; width: 100%; box-sizing: border-box; transition: background-color 0.3s ease; }
        .bouton a:hover { background-color: #e68a00; }
        .bouton a:active { background-color: #cc7a00; }

        /* Style pour la mise en page globale */
        * { box-sizing: border-box; }
        nav ul { list-style-type: none; padding: 30px; }
        nav { flex: 0 0 20%; background: #ffffff; padding: 20px; }
    </style>
</head>

<!-- Menu de navigation pour accéder aux différentes pages -->
<nav>
    <ul>
        <li class="bouton"><a href="index1.php">Résultat</a></li>
        <li class="bouton"><a href="Meilleur_buteurs.php">Meilleurs buteurs</a></li>
        <li class="bouton"><a href="règle_water-polo.php">Règlement</a></li>
        <li class="bouton"><a href="affichage_feuille_match.php">Feuille de Match</a></li>
    </ul>
</nav>

<!-- Corps de la page -->
<body>
<div class="container">
    <h2><i class="fas fa-volleyball-ball"></i> Lecture de la Feuille de Match</h2>
    
    <!-- Formulaire pour télécharger un fichier Excel -->
    <form action="" method="post" enctype="multipart/form-data" class="upload-btn">
        <input type="file" name="file" accept=".xlsx, .xls, .csv" required>
        <button type="submit">Afficher la feuille de match</button>
    </form>

    <!-- Affichage des données extraites du fichier Excel -->
    <?php if (isset($data)) { ?>
        <h3>Feuille de Match</h3>
        <table>
            <!-- Affichage des en-têtes de colonnes -->
            <tr>
                <?php foreach ($data[0] as $header) { echo "<th>$header</th>"; } ?>
            </tr>
            <!-- Affichage des lignes de données -->
            <?php for ($i = 1; $i < count($data); $i++) { ?>
                <tr>
                    <?php foreach ($data[$i] as $cell) { echo "<td>$cell</td>"; } ?>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</div>
</body>
</html>

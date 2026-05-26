<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

// Vérification de sécurité standard
if (!isset($_SESSION['user'])) {
    die('Accès refusé. Veuillez vous connecter.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['matchFile'])) {
    if ($_FILES['matchFile']['error'] === 0 && pathinfo($_FILES['matchFile']['name'], PATHINFO_EXTENSION) === 'xlsx') {
        
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=site_waterpolo;charset=utf8', 'odd', 'odd');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $fileTmpPath = $_FILES['matchFile']['tmp_name'];
            $spreadsheet = IOFactory::load($fileTmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // --- MODIFICATION : Extraction des infos générales selon le nouveau fichier ---
            // Le nom des équipes se trouve en colonne C (index 2) aux lignes 3 et 29 
            $equipe_dom_nom = trim($data[2][2]);   // Lion 
            $equipe_vis_nom = trim($data[28][2]);  // Aigle royal 
            
            // La date est en AH5 (index 34) et l'heure en AM5 (index 39) 
            $date_match = date('Y-m-d', strtotime($data[4][34])); 
            $heure_match = $data[4][39]; 
            
            // L'arbitre est en AK43 (index 36) 
            $nom_arbitre = trim($data[42][36]); 

            // --- COMPLÉTÉ : Gestion de l'Arbitre ---
            $stmtArb = $pdo->prepare("SELECT id_arbitre FROM arbitre WHERE nom_arbitre = ?");
            $stmtArb->execute([$nom_arbitre]);
            $id_arbitre = $stmtArb->fetchColumn();
            if (!$id_arbitre) {
                $insArb = $pdo->prepare("INSERT INTO arbitre (nom_arbitre, prenom_arbitre) VALUES (?, 'Prénom')");
                $insArb->execute([$nom_arbitre]);
                $id_arbitre = $pdo->lastInsertId();
            }

            // --- COMPLÉTÉ : Gestion des Équipes (Vérification et création auto) ---
            $ids_equipes = [];
            foreach (['dom' => $equipe_dom_nom, 'vis' => $equipe_vis_nom] as $key => $nom) {
                $stmtEq = $pdo->prepare("SELECT id_equipe FROM equipe WHERE nom_equipe = ?");
                $stmtEq->execute([$nom]);
                $id = $stmtEq->fetchColumn();
                if (!$id) {
                    $insEq = $pdo->prepare("INSERT INTO equipe (nom_equipe, logo_equipe) VALUES (?, 'images/default.png')");
                    $insEq->execute([$nom, 'images/default.png']);
                    $id = $pdo->lastInsertId();
                }
                $ids_equipes[$key] = $id;
            }

            // --- INSERTION DU MATCH ---
            $stmtM = $pdo->prepare("INSERT INTO matchs (date_matchs, heure_matchs, id_equipe_domicile, id_equipe_visiteur, id_championnat, id_structure, id_arbitre) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtM->execute([$date_match, $heure_match, $ids_equipes['dom'], $ids_equipes['vis'], 1, 1, $id_arbitre]);
            $match_id = $pdo->lastInsertId();

            // --- MODIFICATION MAJEURE : Boucle par zones pour Joueurs + Buts ---
            // Zone Blancs (Lion) : Lignes 9 à 23 | Zone Noirs (Aigle Royal) : Lignes 35 à 49 
            $zones = [
                ['start' => 8, 'end' => 22, 'id_eq' => $ids_equipes['dom']],
                ['start' => 34, 'end' => 48, 'id_eq' => $ids_equipes['vis']]
            ];

            $nb_buts_total = 0;

            foreach ($zones as $zone) {
                for ($i = $zone['start']; $i <= $zone['end']; $i++) {
                    $nom_prenom = isset($data[$i][2]) ? trim($data[$i][2]) : ''; // Colonne C 
                    $licence = isset($data[$i][1]) ? trim($data[$i][1]) : '';    // Colonne B (IUF) 
                    $nb_buts = isset($data[$i][18]) ? (int)$data[$i][18] : 0;    // Colonne S (BUTS) 

                    if (!empty($nom_prenom) && !empty($licence)) {
                        // On vérifie si le joueur existe déjà via sa licence
                        $stmtJ = $pdo->prepare("SELECT id_joueur FROM joueur WHERE numero_licence = ?");
                        $stmtJ->execute([$licence]);
                        $id_joueur = $stmtJ->fetchColumn();

                        // S'il n'existe pas, on le crée
                        if (!$id_joueur) {
                            $annee = isset($data[$i][13]) ? $data[$i][13] : 2000; // Colonne N 
                            $insJ = $pdo->prepare("INSERT INTO joueur (nom_joueur, prenom_joueur, annee_naissance, numero_bonnet, numero_licence, id_equipe) VALUES (?, 'Joueur', ?, ?, ?, ?)");
                            $insJ->execute([$nom_prenom, $annee, 0, $licence, $zone['id_eq']]);
                            $id_joueur = $pdo->lastInsertId();
                        }

                        // MODIFICATION : On insère autant de lignes de buts que le chiffre dans la colonne "BUTS" 
                        if ($nb_buts > 0) {
                            $insB = $pdo->prepare("INSERT INTO but (temps, id_joueur, id_matchs, id_periode, id_equipe) VALUES (?, ?, ?, ?, ?)");
                            for ($b = 0; $b < $nb_buts; $b++) {
                                // On utilise 00:00:00 car l'heure individuelle n'est pas dans ce tableau 
                                $insB->execute(['00:00:00', $id_joueur, $match_id, 1, $zone['id_eq']]);
                                $nb_buts_total++;
                            }
                        }
                    }
                }
            }

            // --- COMPLÉTÉ : Affichage final avec le score total extrait ---
            $score_dom = (int)$data[2][30]; // Cellule AE3 
            $score_vis = (int)$data[4][30]; // Cellule AE5 

            echo "<div style='background:#f8f9fa; border:2px solid #4CAF50; padding:20px; text-align:center; font-family:sans-serif;'>";
            echo "<h2>Importation réussie !</h2>";
            echo "<p>Match : <strong>$equipe_dom_nom</strong> ($score_dom) vs <strong>$equipe_vis_nom</strong> ($score_vis)</p>";
            echo "<p>Nombre de buts détaillés insérés : <strong>$nb_buts_total</strong></p>";
            echo "</div>";

        } catch (Exception $e) {
            die("Erreur : " . $e->getMessage());
        }
    } else {
        echo "Format de fichier non supporté.";
    }
}
?>
<div style="text-align:center; margin-top:20px;">
    <a href="index1.php" style="padding:10px 20px; background:#2196F3; color:white; text-decoration:none; border-radius:5px;">Retour aux résultats</a>
</div>
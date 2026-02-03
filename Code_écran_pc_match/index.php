<?php
require_once __DIR__ . '/db.php'; // db.php à la racine du projet
$pdo = getPDO(); // ou utiliser directement $pdo si db.php expose la variable globale

// Exemple : charger la liste des joueurs pour pré-remplir l'HTML côté serveur
try {
    $stmt = $pdo->query("SELECT id_joueur, prenom_joueur, numero_bonnet, id_equipe FROM joueur ORDER BY id_equipe, numero_bonnet");
    $joueurs = $stmt->fetchAll();
} catch (Exception $e) {
    // Log et continuer proprement
    error_log('Erreur chargement joueurs : ' . $e->getMessage());
    $joueurs = [];
}
?>


<html lang="fr">
 <head>
  <link rel="stylesheet" href="styles.css" />
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Scoreboard
  </title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&amp;family=Roboto&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>


body {
    font-family: 'Oswald', sans-serif;
}

.blink {
    animation: blink 1s infinite;
}

.blink-red {
    animation: blinkRed 1s infinite;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0; }
    100% { opacity: 1; }
}

@keyframes blinkRed {
    0% { 
        opacity: 1;
        color: #7AC142; 
    }
    50% { 
        opacity: 1;
        color: #ff0000; 
    }
    100% { 
        opacity: 1;
        color: #7AC142; 
    }
}

.timeout-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    margin-left: 4px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
}

.team-header {
    display: flex;
    align-items: center;
}

.player-name {
    font-size: 0.875rem;
    font-weight: 500;
}

.player-badges {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-left: 4px;
}

.exclusion-counter {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #fff;
    border: 1px solid #000;
    color: #000;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eda-indicator {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #000;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.edap-indicator {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #991B1B;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

#timer, #possessionTimer {
    font-weight: 700;
    letter-spacing: 1px;
}

button {
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

button:hover {
    transform: scale(1.05);
}

.period-label {
    font-weight: 600;
    text-transform: uppercase;
}

.player-badge {
    display: inline-block;
    margin-left: 2px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    animation: fadeInScale 0.3s ease-out;
    cursor: help;
}

.player-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.player-badges {
    margin-left: 4px;
    margin-right: 4px;
}

@keyframes fadeInScale {
    0% {
        opacity: 0;
        transform: scale(0.8);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.last-five-seconds {
    color: #ff0000;
    font-weight: bold;
}

.technical-timeout {
    background-color: #ff0000;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    position: fixed;
    top: 40px;
    right: 40px;
    z-index: 50;
    font-size: 14px;
}

.excluded-player {
    color: #888888 !important;
    text-decoration: line-through !important;
}

.players-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-left: 8px;
    max-height: 200px;
    overflow-y: auto;
}


.player {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 8px;
    background-color: #f3f4f6;
    border-radius: 4px;
}

.player-number {
    font-weight: bold;
    min-width: 24px;
    text-align: center;
}

.player-name {
    font-size: 0.875rem;
}

/* Styles pour la gestion des remplacements */
.player-container {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    padding: 4px;
    border-radius: 4px;
    background-color: #f9fafb;
}

.player-name {
    flex: 1;
    padding: 4px 8px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    font-size: 14px;
}

.replace-button {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    background-color: #f3f4f6;
    cursor: pointer;
    transition: background-color 0.2s;
}

.replace-button:hover {
    background-color: #e5e7eb;
}

.in-water-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.in-water-indicator[title="Dans l'eau"] {
    background-color: #10B981;
}

.in-water-indicator[title="Hors de l'eau"] {
    background-color: #EF4444;
}
</style>
 </head>
 <body class="bg-white font-sans select-none">
  <div class="flex flex-col min-h-screen max-w-[1400px] mx-auto">
   <header class="flex justify-between items-center px-6 py-4 border-b border-gray-300">
    <div class="flex items-center space-x-3">
     <img alt="Logo Tarbes Nautic Club" class="team-logo" height="48" id="team1Logo" src="images/TNC.png" width="48"/>
     <input autocomplete="off" class="team-name-input team1-color" id="team1Name" maxlength="15" placeholder="NOM EQUIPE 1" spellcheck="false" type="text" value="TNC"/>
    </div>
    <div class="flex items-center space-x-3">
     <img alt="Logo équipe 2" class="team-logo" height="48" id="team2Logo" src="images/logo_Royal.png" width="48"/>
     <select id="team2Select" class="team-select">
      <option value="">Sélectionner une équipe</option>
     </select>
    </div>
   </header>
   <main class="flex flex-1 px-6 py-4 overflow-x-auto space-x-4">
    <!-- Left action buttons -->
    <div class="flex flex-col space-y-3 flex-shrink-0 lateral-buttons" data-team="1">
     <button aria-label="Bouton A équipe 1" title="Accident - Arrêt du jeu pour incident" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-a" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'accident', details: 'Accident - Arrêt du jeu pour incident', temps_chrono: document.getElementById('timer').textContent})">A</button>
     <button aria-label="Bouton R équipe 1" title="Réclamation - Enregistrer une réclamation" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-r" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'reclamation', details: 'Réclamation - Enregistrer une réclamation', temps_chrono: document.getElementById('timer').textContent})">R</button>
     <button aria-label="Bouton CJ équipe 1" title="Carton Jaune - Avertissement" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-cj" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'carton_jaune', details: 'Carton Jaune - Avertissement', temps_chrono: document.getElementById('timer').textContent})">CJ</button>
     <button aria-label="Bouton CR équipe 1" title="Carton Rouge - Exclusion définitive" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-cr" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'carton_rouge', details: 'Carton Rouge - Exclusion définitive', temps_chrono: document.getElementById('timer').textContent})">CR</button>
     <button aria-label="Bouton EDA équipe 1" title="EDA - Exclusion Définitive avec Autorisation de remplacement" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-eda" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'eda', details: 'Exclusion Définitive avec Autorisation de remplacement', temps_chrono: document.getElementById('timer').textContent})">EDA</button>
     <button aria-label="Bouton EDAP équipe 1" title="EDAP - Exclusion Définitive avec Autorisation de remplacement après 4 minutes" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-edap" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 1, type_evenement: 'edap', details: 'Exclusion Définitive avec Autorisation de remplacement après 4 minutes', temps_chrono: document.getElementById('timer').textContent})">EDAP</button>
    </div>
    <!-- Left team players -->
    <section aria-label="Liste des joueurs équipe 1" class="flex flex-col flex-grow max-w-[380px] min-w-[380px] border border-gray-300 rounded-md p-3">
     <div class="text-center mb-3 select-text" id="scoreEquipe1">
      0
     </div>
     <div class="flex flex-col space-y-2 overflow-y-auto players-scroll max-h-[calc(100vh-220px)]" id="leftPlayersList">
      <!-- 15 players left team -->
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        1.
       </div>
       <div class="flex space-x-1 player-controls" data-player="1" data-team="1">
        <button aria-label="Bouton B joueur 1 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold but-button" type="button">B</button>
        <button aria-label="Bouton E joueur 1 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button">E</button>
        <button aria-label="Bouton P joueur 1 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 1 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        2.
       </div>
       <div class="flex space-x-1 player-controls" data-player="2" data-team="1">
        <button aria-label="Bouton B joueur 2 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold but-button" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 2 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 2 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">
         P
        </button>
       </div>
       <input aria-label="Nom du joueur 2 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        3.
       </div>
       <div class="flex space-x-1 player-controls" data-player="3" data-team="1">
        <button aria-label="Bouton B joueur 3 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold but-button" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 3 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 3 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">
         P
        </button>
       </div>
       <input aria-label="Nom du joueur 3 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        4.
       </div>
       <div class="flex space-x-1 player-controls" data-player="4" data-team="1">
        <button aria-label="Bouton B joueur 4 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 4 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 4 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 4 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        5.
       </div>
       <div class="flex space-x-1 player-controls" data-player="5" data-team="1">
        <button aria-label="Bouton B joueur 5 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 5 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 5 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 5 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        6.
       </div>
       <div class="flex space-x-1 player-controls" data-player="6" data-team="1">
        <button aria-label="Bouton B joueur 6 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 6 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 6 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 6 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        7.
       </div>
       <div class="flex space-x-1 player-controls" data-player="7" data-team="1">
        <button aria-label="Bouton B joueur 7 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 7 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 7 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 7 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        8.
       </div>
       <div class="flex space-x-1 player-controls" data-player="8" data-team="1">
        <button aria-label="Bouton B joueur 8 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 8 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 8 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 8 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        9.
       </div>
       <div class="flex space-x-1 player-controls" data-player="9" data-team="1">
        <button aria-label="Bouton B joueur 9 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 9 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 9 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 9 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        10.
       </div>
       <div class="flex space-x-1 player-controls" data-player="10" data-team="1">
        <button aria-label="Bouton B joueur 10 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 10 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 10 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 10 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        11.
       </div>
       <div class="flex space-x-1 player-controls" data-player="11" data-team="1">
        <button aria-label="Bouton B joueur 11 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 11 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 11 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 11 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        12.
       </div>
       <div class="flex space-x-1 player-controls" data-player="12" data-team="1">
        <button aria-label="Bouton B joueur 12 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 12 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 12 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 12 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        13.
       </div>
       <div class="flex space-x-1 player-controls" data-player="13" data-team="1">
        <button aria-label="Bouton B joueur 13 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 13 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 13 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 13 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        14.
       </div>
       <div class="flex space-x-1 player-controls" data-player="14" data-team="1">
        <button aria-label="Bouton B joueur 14 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 14 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 14 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 14 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold">
       <div class="w-5 text-right select-text">
        15.
       </div>
       <div class="flex space-x-1 player-controls" data-player="15" data-team="1">
        <button aria-label="Bouton B joueur 15 équipe 1" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B" type="button" data-team="1">
         B
        </button>
        <button aria-label="Bouton E joueur 15 équipe 1" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="1">
         E
        </button>
        <button aria-label="Bouton P joueur 15 équipe 1" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="1">P</button>
       </div>
       <input aria-label="Nom du joueur 15 équipe 1" autocomplete="off" class="player-name" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
      </div>
     </div>
     <button id="btnTempsMortEquipe1" title="Temps mort - Arrêt du jeu pour 1 minute" class="bg-black text-white text-xs font-semibold rounded-md mt-3 py-1 w-32 self-center" data-team="1">
  Temps morts
</button>
     <div id="badgesEquipe1" class="flex space-x-1 mt-1 justify-center"></div>

    </section>
         <!-- Center panel -->
    <section aria-label="Panneau central du tableau de score" class="flex flex-col items-center justify-start flex-grow px-6 min-w-[180px] max-h-[100vh]">
     <div class="border border-gray-300 rounded-md text-center text-[10px] font-bold py-1 mb-1 px-2 period-label select-text" style="min-width: 50px">
      Période :
     </div>
     <div class="text-[#7AC142] font-extrabold text-2xl mb-4 select-text" id="periodDisplay">
      1
     </div>
     <div class="flex flex-col items-center justify-center gap-4">
        <!-- Chronomètre principal -->
        <div aria-atomic="true" aria-live="polite" class="text-[#7AC142] font-extrabold text-5xl select-text leading-none" id="timer" style="line-height: 1">
            08:00
        </div>

        <!-- Chronomètre de possession -->
        <div class="mt-4 flex justify-center">
            <div class="flex flex-col items-center">
                <div class="text-[10px] font-bold mb-1" id="possessionLabel">Possession (30s)</div>
                <div aria-atomic="true" aria-live="polite" class="text-orange-500 font-extrabold text-3xl select-text" id="possessionTimer" style="line-height: 1">
                    30
                </div>
                <div class="flex flex-col space-y-2 mt-2">
                    <button id="possessionBtn" title="Changer la possession - Réinitialise le chronomètre à 30 secondes" class="bg-black text-white text-xs font-semibold rounded-md py-2 px-8" type="button">Possession</button>
                    <button id="special20Btn" title="Démarrer un décompte de 20 secondes" class="bg-black text-white text-xs font-semibold rounded-md py-1 px-3 w-24 mx-auto">
                        20 secondes
                    </button>
                </div>
            </div>
        </div>

        <!-- Journal d'événements -->
        <div id="eventLog" class="mt-2 text-sm text-gray-600 max-h-40 overflow-y-auto w-full max-w-md px-4"></div>
    </div>

     <div aria-atomic="true" aria-live="polite" id="goalInfo" role="log" tabindex="0">
</div>

     <div class="flex flex-col items-center w-full mt-auto space-y-2">
      <button id="nextPeriodBtn" title="Passer à la période suivante - Remet le chrono à 8 minutes et réinitialise les temps morts" class="bg-orange-600 text-white text-xs font-semibold rounded-md py-1 px-6" type="button">Période suivante</button>
      <button id="startStopBtn" title="Lancer ou arrêter le chronomètre principal (8 minutes)" aria-label="Lancer ou arrêter le chronomètre" aria-pressed="false" class="bg-black text-white text-xs font-semibold rounded-md py-2 px-8" type="button">Lancer/Arrêt</button>
     </div>
    </section>
    <!-- Right team players -->
    <section aria-label="Liste des joueurs équipe 2" class="flex flex-col flex-grow max-w-[380px] min-w-[380px] border border-gray-300 rounded-md p-3">
     <div class="text-center font-bold py-2 mb-3 select-text" id="scoreEquipe2" style="min-width: 160px">
      0
     </div>
     <div class="flex flex-col space-y-2 overflow-y-auto players-scroll max-h-[calc(100vh-220px)]" id="rightPlayersList">
      <!-- 15 players right team -->
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        1.
       </div>
       <input aria-label="Nom du joueur 1 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="1" data-team="2">
        <button aria-label="Bouton B joueur 1 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold but-button" type="button">
         B
        </button>
        <button aria-label="Bouton E joueur 1 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button">
         E
        </button>
        <button aria-label="Bouton P joueur 1 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        2.
       </div>
       <input aria-label="Nom du joueur 2 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
               <div class="flex space-x-1 player-controls" data-player="2" data-team="2">
         <button aria-label="Bouton B joueur 2 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold but-button" type="button">
         B
        </button>
        <button aria-label="Bouton E joueur 2 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button">
         E
        </button>
        <button aria-label="Bouton P joueur 2 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        3.
       </div>
       <input aria-label="Nom du joueur 3 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="3" data-team="2">
        <button aria-label="Bouton B joueur 3 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 3 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 3 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        4.
       </div>
       <input aria-label="Nom du joueur 4 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="4" data-team="2">
        <button aria-label="Bouton B joueur 4 équipe 2" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
                 <button aria-label="Bouton E joueur 4 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 4 équipe 2" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        5.
       </div>
       <input aria-label="Nom du joueur 5 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="5" data-team="2">
        <button aria-label="Bouton B joueur 5 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
                 <button aria-label="Bouton E joueur 5 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 5 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        6.
       </div>
       <input aria-label="Nom du joueur 6 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="6" data-team="2">
        <button aria-label="Bouton B joueur 6 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 6 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 6 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        7.
       </div>
       <input aria-label="Nom du joueur 7 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="7" data-team="2">
        <button aria-label="Bouton B joueur 7 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 7 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 7 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        8.
       </div>
       <input aria-label="Nom du joueur 8 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="8" data-team="2">
        <button aria-label="Bouton B joueur 8 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 8 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 8 équipe 2" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        9.
       </div>
       <input aria-label="Nom du joueur 9 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="9" data-team="2">
        <button aria-label="Bouton B joueur 9 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 9 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 9 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        10.
       </div>
       <input aria-label="Nom du joueur 10 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="10" data-team="2">
        <button aria-label="Bouton B joueur 10 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 10 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 10 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        11.
       </div>
       <input aria-label="Nom du joueur 11 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="11" data-team="2">
        <button aria-label="Bouton B joueur 11 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 11 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 11 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        12.
       </div>
       <input aria-label="Nom du joueur 12 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="12" data-team="2">
        <button aria-label="Bouton B joueur 12 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
                 <button aria-label="Bouton E joueur 12 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 12 équipe 2" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        13.
       </div>
       <input aria-label="Nom du joueur 13 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="13" data-team="2">
        <button aria-label="Bouton B joueur 13 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 13 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 13 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        14.
       </div>
       <input aria-label="Nom du joueur 14 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="14" data-team="2">
        <button aria-label="Bouton B joueur 14 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 14 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 14 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
      <div class="flex items-center space-x-2 text-xs font-semibold justify-end">
       <div class="w-5 text-left select-text">
        15.
       </div>
       <input aria-label="Nom du joueur 15 équipe 2" autocomplete="off" class="player-name text-right" maxlength="15" placeholder="Prénom" spellcheck="false" type="text" value=""/>
       <div class="flex space-x-1 player-controls" data-player="15" data-team="2">
        <button aria-label="Bouton B joueur 15 équipe 2" title="But - Marquer un but pour ce joueur" class="bg-black text-white rounded-md px-2 py-0.5 font-bold player-B-right" type="button" data-team="2">
         B
        </button>
        <button aria-label="Bouton E joueur 15 équipe 2" title="Exclusion temporaire - 20 secondes hors du jeu" class="bg-black text-white rounded-md px-2 py-0.5 font-bold exclusion-button" type="button" data-team="2">
         E
        </button>
        <button aria-label="Bouton P joueur 15 équipe 2" title="Penalty - Tir de pénalité" class="bg-black text-white rounded-md px-2 py-0.5 font-bold btn-p" type="button" data-team="2">P</button>
       </div>
      </div>
     </div>
     <button id="btnTempsMortEquipe2" class="bg-black text-white text-xs font-semibold rounded-md mt-3 py-1 w-32 self-center">
  Temps morts
</button>

     <div id="badgesEquipe2" class="flex space-x-1 mt-1 justify-center"></div>

    </section>
    <!-- Right action buttons -->
    <div class="flex flex-col space-y-3 flex-shrink-0 lateral-buttons" data-team="2">
     <button aria-label="Bouton A équipe 2" title="Accident - Arrêt du jeu pour incident" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-a" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'accident', details: 'Accident - Arrêt du jeu pour incident', temps_chrono: document.getElementById('timer').textContent})">A</button>
     <button aria-label="Bouton R équipe 2" title="Réclamation - Enregistrer une réclamation" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-r" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'reclamation', details: 'Réclamation - Enregistrer une réclamation', temps_chrono: document.getElementById('timer').textContent})">R</button>
     <button aria-label="Bouton CJ équipe 2" title="Carton Jaune - Avertissement" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-cj" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'carton_jaune', details: 'Carton Jaune - Avertissement', temps_chrono: document.getElementById('timer').textContent})">CJ</button>
     <button aria-label="Bouton CR équipe 2" title="Carton Rouge - Exclusion définitive" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-cr" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'carton_rouge', details: 'Carton Rouge - Exclusion définitive', temps_chrono: document.getElementById('timer').textContent})">CR</button>
     <button aria-label="Bouton EDA équipe 2" title="EDA - Exclusion Définitive avec Autorisation de remplacement" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-eda" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'eda', details: 'Exclusion Définitive avec Autorisation de remplacement', temps_chrono: document.getElementById('timer').textContent})">EDA</button>
     <button aria-label="Bouton EDAP équipe 2" title="EDAP - Exclusion Définitive avec Autorisation de remplacement après 4 minutes" class="bg-black text-white text-sm font-semibold rounded-md w-12 h-12 flex items-center justify-center btn-edap" type="button" onclick="enregistrerEvenement({id_match: window.idMatchActuel || 1, id_equipe: 2, type_evenement: 'edap', details: 'Exclusion Définitive avec Autorisation de remplacement après 4 minutes', temps_chrono: document.getElementById('timer').textContent})">EDAP</button>
    </div>
   </main>
   <footer aria-label="Panneau publicitaire" class="border-t border-gray-300 text-center text-xs text-gray-500 py-2 select-text" style="min-height: 28px">
    Panneau publicitaire
   </footer>
  </div>
  <script>
   /**
    * CONFIGURATION GLOBALE DU JEU
    * Toutes les constantes de temps et limites du jeu de waterpolo
    */
   const CONFIG = {
       PERIOD_TIME: 480,                 // Durée d'une période : 8 minutes (8 * 60 = 480 secondes)
       POSSESSION_TIME: 30,              // Temps de possession (shot clock) : 30 secondes exactement
       SPECIAL_TIME: 20,                 // Temps spécial pour situations particulières : 20 secondes
       MAX_TIMEOUTS: 2,                  // Nombre maximum de temps morts par équipe par MATCH (4 périodes)
       TIMEOUT_TIME: 60,                 // Durée d'un temps mort : 60 secondes (1 minute)
       EXCLUSION_TIME: 20,               // Durée d'une exclusion temporaire : 20 secondes
       EDAP_TIME: 240,                   // Durée EDAP (Exclusion Définitive avec Autorisation de remplacement après) : 4 minutes
       MAX_EXCLUSIONS: 3,                // Nombre maximum d'exclusions temporaires avant exclusion définitive
       TECHNICAL_TIMEOUT_TIME: 240,      // Temps mort technique automatique à 4 minutes restantes
       LAST_SECONDS_THRESHOLD: 5,        // Seuil pour déclencher l'alerte des dernières secondes
       MAX_PERIODS: 4                    // Nombre total de périodes en waterpolo (4 x 8 minutes)
   };

   /**
    * ÉTAT GLOBAL DU JEU
    * Contient toutes les variables qui définissent l'état actuel de la partie
    */
   const gameState = {
       // === CHRONOMÉTRAGE ===
       mainTimer: CONFIG.PERIOD_TIME,         // Timer principal (8 minutes décomptées)
       possessionTimer: CONFIG.POSSESSION_TIME, // Timer de possession (30s ou 20s selon le mode)
       isMainTimerRunning: false,             // État du chronomètre principal (en marche/arrêté)
       isPossessionRunning: false,            // État du chronomètre de possession
       
       // === SCORES ET STATISTIQUES ===
       scoreTeam1: 0,                         // Score de l'équipe 1
       scoreTeam2: 0,                         // Score de l'équipe 2
       possessionCount: 0,                    // Compteur des changements de possession
       period: 1,                             // Période actuelle (1, 2, 3 ou 4 en waterpolo)
       
       // === TEMPS MORTS ===
       timeoutsTeam1: 0,                      // Nombre de temps morts utilisés par l'équipe 1
       timeoutsTeam2: 0,                      // Nombre de temps morts utilisés par l'équipe 2
       
       // === MODES DE JEU ===
       possessionMode: 'normal',              // Mode de possession : 'normal' (30s), 'special20' (20s), 'penalty'
       
       // === EXCLUSIONS ET SANCTIONS ===
       exclusions: new Map(),                 // Map des exclusions actives (ancienne version)
       exclusionTimers: {                     // Timers des exclusions temporaires par équipe
           team1: {},                         // Format: {playerNumber: {startTime, endTime, count, badge}}
           team2: {}
       },
       playerExclusions: {                    // Compteur total d'exclusions par joueur
           team1: {},                         // Format: {playerNumber: count}
           team2: {}
       },
       playerYellowCards: {                   // Compteur de cartons jaunes par joueur
           team1: {},                         // Format: {playerNumber: count}
           team2: {}
       },
       playerPenalties: {},                   // Compteur de penalties par joueur
       
       // === TIMERS SPÉCIAUX ===
       edapTimers: new Map(),                 // Map des timers EDAP (Exclusion Définitive avec Autorisation de remplacement)
       
       // === VARIABLES TECHNIQUES ===
       chronoActif: false,                    // État technique du chronomètre (doublon de isMainTimerRunning)
       chronoInterval: null,                  // Référence de l'intervalle du chronomètre
       chronoSecondes: 0,                     // Compteur technique de secondes
       wasRunningPossession: false,           // État de possession avant interruption
       savedPossessionTimer: null,            // Sauvegarde de l'état du chrono de possession lors d'interruptions
       afterCorner: null,                     // Variable pour gérer l'après-corner
       technicalTimeoutShown: false          // Flag pour éviter de montrer plusieurs fois le temps mort technique
   };

   /**
    * RÉFÉRENCES DES ÉLÉMENTS DOM
    * Structure pour organiser l'accès aux éléments HTML de l'interface
    */
   let DOM = {
       // === AFFICHAGES TEMPORELS ===
       timers: {
           main: null,                        // Élément d'affichage du chronomètre principal (8 minutes)
           possession: null                   // Élément d'affichage du chronomètre de possession (30s/20s)
       },
       
       // === LABELS ET TEXTES ===
       labels: {
           possession: null                   // Label du chronomètre de possession (change selon le mode)
       },
       
       // === AFFICHAGES DES SCORES ===
       scores: {
           team1: null,                       // Élément d'affichage du score équipe 1
           team2: null                        // Élément d'affichage du score équipe 2
       },
       
       // === BOUTONS DE CONTRÔLE ===
       buttons: {
           startStop: null,                   // Bouton principal Lancer/Arrêt du chronomètre
           possession: null,                  // Bouton de changement de possession
           corner: null,                      // Bouton corner (si existe)
           penalty: null,                     // Bouton penalty (si existe)
           timeout1: null,                    // Bouton temps mort équipe 1
           timeout2: null,                    // Bouton temps mort équipe 2
           
           // Boutons latéraux pour actions spéciales par équipe
           lateral: {
               team1: {
                   accident: null,            // Bouton A - Accident équipe 1
                   reclamation: null,         // Bouton R - Réclamation équipe 1
                   cartonJaune: null,         // Bouton CJ - Carton Jaune équipe 1
                   cartonRouge: null,         // Bouton CR - Carton Rouge équipe 1
                   eda: null,                 // Bouton EDA - Exclusion Définitive avec Autorisation équipe 1
                   edap: null                 // Bouton EDAP - Exclusion Définitive avec Autorisation après Penalty équipe 1
               },
               team2: {
                   accident: null,            // Bouton A - Accident équipe 2
                   reclamation: null,         // Bouton R - Réclamation équipe 2
                   cartonJaune: null,         // Bouton CJ - Carton Jaune équipe 2
                   cartonRouge: null,         // Bouton CR - Carton Rouge équipe 2
                   eda: null,                 // Bouton EDA - Exclusion Définitive avec Autorisation équipe 2
                   edap: null                 // Bouton EDAP - Exclusion Définitive avec Autorisation après Penalty équipe 2
               }
           }
       },
       
       // === ZONES D'AFFICHAGE DES BADGES ===
       badges: {
           team1: null,                       // Zone d'affichage des badges équipe 1 (exclusions, temps morts, etc.)
           team2: null                        // Zone d'affichage des badges équipe 2 (exclusions, temps morts, etc.)
       },
       
       // === ÉLÉMENTS UTILITAIRES ===
       log: null,                             // Zone de journal des événements
       sound: null                            // Objet Audio pour les signaux sonores
   };

   /**
    * VARIABLES GLOBALES DES TIMERS
    * Références des intervalles pour pouvoir les arrêter/démarrer
    */
   let mainInterval = null;              // Interval du chronomètre principal (8 minutes)
   let possessionInterval = null;        // Interval du chronomètre de possession (30s/20s) - non utilisé actuellement
   let timeoutInterval = null;           // Interval du chronomètre de temps mort (60s)

   /**
    * INITIALISATION DES RÉFÉRENCES DOM
    * Récupère tous les éléments HTML nécessaires et les stocke dans l'objet DOM
    * Cette fonction doit être appelée au chargement de la page
    */
   function initializeDOMElements() {
       DOM.timers.main = document.getElementById("timer");
       DOM.timers.possession = document.getElementById("possessionTimer");
       DOM.labels.possession = document.getElementById("possessionLabel");
       DOM.scores.team1 = document.getElementById("scoreEquipe1");
       DOM.scores.team2 = document.getElementById("scoreEquipe2");
       DOM.buttons.startStop = document.getElementById("startStopBtn");
       DOM.buttons.possession = document.getElementById("possessionBtn");
       DOM.buttons.special20 = document.getElementById("special20Btn");
       DOM.buttons.timeout1 = document.getElementById("btnTempsMortEquipe1");
       DOM.buttons.timeout2 = document.getElementById("btnTempsMortEquipe2");
       
       // Initialisation des boutons latéraux pour les deux équipes
       DOM.buttons.lateral = {
           team1: {
               accident: document.querySelector('button[aria-label="Bouton A équipe 1"]'),
               reclamation: document.querySelector('button[aria-label="Bouton R équipe 1"]'),
               cartonJaune: document.querySelector('button[aria-label="Bouton CJ équipe 1"]'),
               cartonRouge: document.querySelector('button[aria-label="Bouton CR équipe 1"]'),
               eda: document.querySelector('button[aria-label="Bouton EDA équipe 1"]'),
               edap: document.querySelector('button[aria-label="Bouton EDAP équipe 1"]')
           },
           team2: {
               accident: document.querySelector('button[aria-label="Bouton A équipe 2"]'),
               reclamation: document.querySelector('button[aria-label="Bouton R équipe 2"]'),
               cartonJaune: document.querySelector('button[aria-label="Bouton CJ équipe 2"]'),
               cartonRouge: document.querySelector('button[aria-label="Bouton CR équipe 2"]'),
               eda: document.querySelector('button[aria-label="Bouton EDA équipe 2"]'),
               edap: document.querySelector('button[aria-label="Bouton EDAP équipe 2"]')
           }
       };

       // Ajouter les gestionnaires d'événements pour les boutons latéraux
       // Équipe 1
       DOM.buttons.lateral.team1.accident.addEventListener('click', () => handleLateralButton(1, 'accident'));
       DOM.buttons.lateral.team1.reclamation.addEventListener('click', () => handleLateralButton(1, 'reclamation'));
       DOM.buttons.lateral.team1.cartonJaune.addEventListener('click', () => handleLateralButton(1, 'carton_jaune'));
       DOM.buttons.lateral.team1.cartonRouge.addEventListener('click', () => handleLateralButton(1, 'carton_rouge'));
       DOM.buttons.lateral.team1.eda.addEventListener('click', () => handleLateralButton(1, 'eda'));
       DOM.buttons.lateral.team1.edap.addEventListener('click', () => handleLateralButton(1, 'edap'));

       // Équipe 2
       DOM.buttons.lateral.team2.accident.addEventListener('click', () => handleLateralButton(2, 'accident'));
       DOM.buttons.lateral.team2.reclamation.addEventListener('click', () => handleLateralButton(2, 'reclamation'));
       DOM.buttons.lateral.team2.cartonJaune.addEventListener('click', () => handleLateralButton(2, 'carton_jaune'));
       DOM.buttons.lateral.team2.cartonRouge.addEventListener('click', () => handleLateralButton(2, 'carton_rouge'));
       DOM.buttons.lateral.team2.eda.addEventListener('click', () => handleLateralButton(2, 'eda'));
       DOM.buttons.lateral.team2.edap.addEventListener('click', () => handleLateralButton(2, 'edap'));

       DOM.badges = {
           team1: document.getElementById("badgesEquipe1"),
           team2: document.getElementById("badgesEquipe2")
       };
       DOM.log = document.getElementById("goalInfo");
       DOM.sound = new Audio('beep.mp3');
   }

   /**
    * FORMATAGE DU TEMPS
    * Convertit un nombre de secondes en format MM:SS pour l'affichage
    * @param {number} seconds - Nombre de secondes à formater
    * @returns {string} - Temps formaté au format "M:SS" ou "MM:SS"
    */
   function formatTime(seconds) {
       const mins = Math.floor(seconds / 60);        // Calcul des minutes entières
       const secs = seconds % 60;                    // Calcul des secondes restantes
       return `${mins}:${secs.toString().padStart(2, '0')}`; // Format "M:SS" avec zéro initial si nécessaire
   }

   /**
    * RÉCUPÉRATION DU NOM D'UNE ÉQUIPE
    * Gère uniformément les noms d'équipes (input pour équipe 1, select pour équipe 2)
    * @param {number} teamNumber - Numéro de l'équipe (1 ou 2)
    * @returns {string} - Nom de l'équipe ou nom par défaut
    */
   function getTeamName(equipe) {
       if (equipe === 1) {
           return 'TNC';
       } else {
           // Récupérer le nom de l'équipe sélectionnée dans le menu déroulant
           const team2Input = document.querySelector('.team-name-input.team2-color');
           const team2Select = document.getElementById('team2Select');
           
           // Si l'input existe et a une valeur, l'utiliser
           if (team2Input && team2Input.value) {
               return team2Input.value;
           }
           // Sinon, si une équipe est sélectionnée dans le menu déroulant, utiliser son nom
           else if (team2Select && team2Select.selectedIndex > 0) {
               return team2Select.options[team2Select.selectedIndex].textContent;
           }
           // Par défaut
           return 'Équipe 2';
       }
   }

   /**
    * MISE À JOUR DE L'AFFICHAGE
    * Met à jour tous les éléments visuels de l'interface :
    * - Chronomètres (principal et possession)
    * - Scores des équipes
    * - Labels selon le mode de jeu
    * - Gestion des alertes visuelles et sonores
    * Cette fonction est appelée régulièrement par les timers
    */
   function updateDisplay() {
       // === MISE À JOUR DES CHRONOMÈTRES ===
       DOM.timers.main.textContent = formatTime(gameState.mainTimer);           // Affichage du timer principal (8 minutes)
       DOM.timers.possession.textContent = gameState.possessionTimer;           // Affichage du timer de possession (secondes)
       
       // === MISE À JOUR DU LABEL DE POSSESSION ===
       // Change le texte et la couleur selon le mode de jeu actuel
       if (gameState.possessionMode === 'special20') {
           DOM.labels.possession.textContent = '20 secondes';                   // Mode 20 secondes (corner/penalty)
           DOM.labels.possession.style.color = '#3B82F6';                      // Couleur bleue pour distinguer du mode normal
       } else {
           DOM.labels.possession.textContent = 'Possession (30s)';              // Mode normal 30 secondes
           DOM.labels.possession.style.color = '#000';                         // Couleur noire par défaut
       }
       
       // === MISE À JOUR DES EXCLUSIONS TEMPORAIRES ===
       updateExclusionTimers();                                                 // Met à jour tous les timers d'exclusions actifs
       
       // === ALERTE DES DERNIÈRES SECONDES DE POSSESSION ===
       // Déclenche l'alerte visuelle et sonore dans les 5 dernières secondes
       if (gameState.possessionTimer <= CONFIG.LAST_SECONDS_THRESHOLD && gameState.possessionTimer > 0) {
           DOM.timers.possession.classList.add('last-five-seconds');            // Ajoute la classe CSS pour le style rouge
           playBeep();                                                          // Signal sonore d'alerte
       } else {
           DOM.timers.possession.classList.remove('last-five-seconds');        // Retire le style d'alerte
       }
       
       // === TEMPS MORT TECHNIQUE AUTOMATIQUE ===
       // Déclenche automatiquement le temps mort technique à 4 minutes restantes
       if (gameState.mainTimer === CONFIG.TECHNICAL_TIMEOUT_TIME && !gameState.technicalTimeoutShown) {
           showTechnicalTimeout();                                              // Affiche la notification de temps mort technique
           gameState.technicalTimeoutShown = true;                             // Empêche l'affichage multiple
       }
       
       // === MISE À JOUR DES SCORES ===
       DOM.scores.team1.textContent = gameState.scoreTeam1;                    // Affichage du score équipe 1
       DOM.scores.team2.textContent = gameState.scoreTeam2;                    // Affichage du score équipe 2
       
       // === MISE À JOUR DE LA PÉRIODE ===
       document.getElementById('periodDisplay').textContent = gameState.period; // Affichage de la période actuelle
   }

   /**
    * PASSAGE À LA PÉRIODE SUIVANTE
    * Gère la transition entre les périodes en waterpolo (4 périodes de 8 minutes)
    * - Incrémente la période
    * - Remet le chrono à 8 minutes
    * - Réinitialise les temps morts
    * - Vérifie la fin de match
    */
   function nextPeriod() {
       // === VÉRIFICATION DE FIN DE MATCH ===
       if (gameState.period >= CONFIG.MAX_PERIODS) {
           addLogEntry(`🏁 FIN DU MATCH ! Score final : ${getTeamName(1)}: ${gameState.scoreTeam1} - ${getTeamName(2)}: ${gameState.scoreTeam2}`, 'text-red-600 font-bold text-lg');
           stopAllTimers();                                                     // Arrêter tous les chronomètres
           document.getElementById('nextPeriodBtn').disabled = true;            // Désactiver le bouton
           document.getElementById('nextPeriodBtn').textContent = 'Match terminé';
           return;                                                              // Sortir de la fonction
       }

       // === ARRÊT DES CHRONOMÈTRES ACTUELS ===
       stopAllTimers();                                                         // Arrêter tous les timers

       // === PASSAGE À LA PÉRIODE SUIVANTE ===
       gameState.period++;                                                      // Incrémenter la période

       // === RÉINITIALISATION DU TEMPS ===
       gameState.mainTimer = CONFIG.PERIOD_TIME;                               // Remettre à 8 minutes
       gameState.possessionTimer = CONFIG.POSSESSION_TIME;                     // Remettre possession à 30s
       gameState.possessionMode = 'normal';                                    // Mode possession normal
       gameState.isPossessionRunning = false;                                  // Possession arrêtée

       // === TEMPS MORTS EN WATERPOLO ===
       // Les temps morts ne se réinitialisent PAS entre les périodes
       // Chaque équipe a droit à 2 temps morts pour tout le match (4 périodes)
       // Les boutons restent dans leur état actuel (activés/désactivés selon quota utilisé)

       // === NETTOYAGE DES ÉTATS TEMPORAIRES ===
       gameState.technicalTimeoutShown = false;                                // Permettre nouveau temps mort technique
       gameState.savedPossessionTimer = null;                                  // Nettoyer les sauvegardes
       gameState.wasRunningPossession = null;

       // === CONSERVATION DES BADGES DE TEMPS MORTS ===
       // En waterpolo, les badges de temps morts sont CONSERVÉS entre les périodes
       // car les temps morts sont comptés pour tout le match (4 périodes)

       // === NOTIFICATION DANS LE JOURNAL ===
       if (gameState.period <= CONFIG.MAX_PERIODS) {
           addLogEntry(`🔄 DÉBUT DE LA PÉRIODE ${gameState.period} - Temps remis à 8:00`, 'text-blue-600 font-bold');
           
           // Si c'est la dernière période
           if (gameState.period === CONFIG.MAX_PERIODS) {
               addLogEntry(`⚠️ DERNIÈRE PÉRIODE !`, 'text-red-600 font-bold');
           }
       }

       // === MISE À JOUR DE L'AFFICHAGE ===
       updateDisplay();                                                         // Actualiser tous les éléments visuels
       
       // === MISE À JOUR DU BOUTON ===
       if (gameState.period >= CONFIG.MAX_PERIODS) {
           document.getElementById('nextPeriodBtn').textContent = 'Fin de match';
       }
   }

   function updateExclusionTimers() {
       // Mettre à jour tous les badges d'exclusion actifs
       for (const [teamKey, teamExclusions] of Object.entries(gameState.exclusionTimers)) {
           for (const [playerNumber, exclusion] of Object.entries(teamExclusions)) {
               if (exclusion.endTime > Date.now()) {
                   const timeRemaining = Math.ceil((exclusion.endTime - Date.now()) / 1000);
                   const badge = exclusion.badge;
                   if (badge && badge.isConnected) {
                       badge.textContent = `${playerNumber} (${exclusion.count}): ${timeRemaining}s`;
                   }
               } else {
                   // L'exclusion est terminée
                   const badge = exclusion.badge;
                   if (badge && badge.isConnected) {
                       badge.remove();
                   }
                   delete teamExclusions[playerNumber];
                   
                   // Ajouter une entrée dans le journal
                   const team = teamKey === 'team1' ? 'Équipe 1' : 'Équipe 2';
                   const playerInput = document.querySelector(`#${teamKey === 'team1' ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
                   const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
                   
                   if (exclusion.count < CONFIG.MAX_EXCLUSIONS) {
                       addLogEntry(`✅ Fin d'exclusion - ${playerName} (${playerNumber})`, 'text-green-600');
                   }
               }
           }
       }
   }

   /**
    * DÉMARRAGE DU CHRONOMÈTRE PRINCIPAL
    * Lance le timer principal de 8 minutes avec toute la logique de jeu :
    * - Gestion du chronomètre de possession
    * - Changements automatiques de possession
    * - Fin de match automatique
    * - Restauration d'états sauvegardés après interruptions
    */
   function startMainTimer() {
       // Vérifier qu'un timer n'est pas déjà en cours
       if (!mainInterval) {
           gameState.isMainTimerRunning = true;                                 // Marquer le timer comme actif
           
           // Créer l'intervalle qui s'exécute toutes les 1000ms (1 seconde)
           mainInterval = setInterval(() => {
               // === VÉRIFICATION DE FIN DE MATCH ===
               if (gameState.mainTimer <= 0) {
                   stopAllTimers();                                             // Arrêter tous les chronomètres
                   DOM.sound.play();                                            // Signal sonore de fin
                   return;                                                      // Sortir de la fonction
               }

               // === RESTAURATION D'ÉTAT APRÈS INTERRUPTION ===
               // Si un état de possession avait été sauvegardé (pendant temps mort, réclamation, etc.)
               if (gameState.savedPossessionTimer !== null) {
                   gameState.possessionTimer = gameState.savedPossessionTimer;  // Restaurer le temps de possession exact
                   gameState.isPossessionRunning = gameState.wasRunningPossession; // Restaurer l'état de fonctionnement
                   gameState.savedPossessionTimer = null;                       // Nettoyer la sauvegarde
                   gameState.wasRunningPossession = null;                       // Nettoyer l'état sauvegardé
               }
               // === DÉMARRAGE AUTOMATIQUE DE LA POSSESSION ===
               // Si aucun chronomètre de possession n'est actif, en démarrer un nouveau
               else if (!gameState.isPossessionRunning) {
                   // Ne pas réinitialiser si on est en mode spécial (20s, penalty)
                   if (gameState.possessionMode !== 'special20' && gameState.possessionMode !== 'penalty') {
                       gameState.possessionTimer = CONFIG.POSSESSION_TIME;      // Réinitialiser à 30 secondes
                       gameState.possessionMode = 'normal';                     // Mode normal
                   }
                   gameState.isPossessionRunning = true;                        // Activer le chronomètre de possession
               }

               // === GESTION DU CHRONOMÈTRE DE POSSESSION ===
               if (gameState.isPossessionRunning) {
                   if (gameState.possessionMode === 'normal') {                 // Mode normal (30 secondes)
                       if (gameState.possessionTimer === 0) {                  // Fin du temps de possession
                           gameState.possessionCount++;                        // Incrémenter le compteur de changements
                           addLogEntry(`⏱️ Changement de possession - ${formatTime(gameState.mainTimer)}`, 'text-gray-600');
                           DOM.sound.play();                                    // Signal sonore
                           gameState.possessionTimer = CONFIG.POSSESSION_TIME; // Redémarrer à 30s
                       }
                       gameState.possessionTimer--;                            // Décrémenter le temps de possession
                   } else if (gameState.possessionMode === 'special20') {      // Mode 20 secondes
                       if (gameState.possessionTimer === 0) {                  // Fin des 20 secondes
                           addLogEntry(`⏱️ Fin des 20 secondes - ${formatTime(gameState.mainTimer)}`, 'text-gray-600');
                           DOM.sound.play();                                    // Signal sonore
                           // Redémarrer en mode normal avec changement de possession
                           gameState.possessionCount++;                        // Changement de possession
                           gameState.possessionTimer = CONFIG.POSSESSION_TIME; // Redémarrer à 30s
                           gameState.possessionMode = 'normal';                 // Retour au mode normal
                       }
                       gameState.possessionTimer--;                            // Décrémenter le temps spécial
                   }
               }

               // === DÉCRÉMENTATION DU TIMER PRINCIPAL ===
               gameState.mainTimer--;                                           // Décrémenter le temps principal (8 minutes)
               updateDisplay();                                                 // Mettre à jour l'affichage
           }, 1000);                                                            // Exécuter toutes les 1000ms

           // === MISE À JOUR DE L'INTERFACE ===
           DOM.buttons.startStop.setAttribute("aria-pressed", "true");          // Marquer le bouton comme activé
           DOM.buttons.lateral.team1.accident.classList.remove('blink-red');   // Retirer les alertes d'accident
           DOM.buttons.lateral.team2.accident.classList.remove('blink-red');   // Retirer les alertes d'accident
       }
   }

   /**
    * ARRÊT DU CHRONOMÈTRE PRINCIPAL
    * Stoppe uniquement le timer principal tout en conservant l'état du jeu
    * Utilisé pour les pauses temporaires (réclamations, accidents, etc.)
    */
   function stopMainTimer() {
       if (mainInterval) {
           clearInterval(mainInterval);                                         // Annuler l'intervalle
           mainInterval = null;                                                 // Réinitialiser la référence
           gameState.isMainTimerRunning = false;                               // Marquer comme arrêté
           DOM.buttons.startStop.setAttribute("aria-pressed", "false");        // Mettre à jour le bouton
       }
   }

   /**
    * ARRÊT COMPLET DE TOUS LES TIMERS
    * Stoppe tous les chronomètres et remet les modes par défaut
    * Utilisé pour la fin de match ou les arrêts définitifs
    */
   function stopAllTimers() {
       if (mainInterval) {
           clearInterval(mainInterval);                                         // Arrêter le timer principal
           mainInterval = null;                                                 // Réinitialiser la référence
       }
       gameState.isMainTimerRunning = false;                                   // Timer principal arrêté
       gameState.isPossessionRunning = false;                                  // Timer de possession arrêté
       gameState.possessionMode = 'normal';                                    // Retour au mode normal
       DOM.buttons.startStop.setAttribute("aria-pressed", "false");            // Bouton non pressé
       updateDisplay();                                                         // Mettre à jour l'affichage
   }

   function stopPossessionTimer() {
       gameState.isPossessionRunning = false;
   }

   function resetPossession() {
       gameState.possessionTimer = CONFIG.POSSESSION_TIME;
       gameState.possessionMode = 'normal';
       updateDisplay();
   }

   function startTeam1Possession() {
       if (gameState.lastPossession === 'team1') return;
       
       if (!gameState.isMainTimerRunning) {
           startMainTimer();
       }
       
       stopPossessionTimer();
       resetPossession();
       
       DOM.buttons.possession1.classList.remove('bg-red-600');
       DOM.buttons.possession1.classList.add('bg-gray-400');
       
       gameState.lastPossession = 'team1';
       // Ne démarrer le timer 30s que si on n'est pas en mode corner ou penalty
       if (!gameState.possessionMode || gameState.possessionMode === 'normal') {
           startPossessionTimer(CONFIG.POSSESSION_TIME, 'normal');
       }
       
       addLogEntry(`🏐 Possession TNC - ${formatTime(gameState.mainTimer)}`, 'text-red-600');
   }

   function startTeam2Possession() {
       if (gameState.lastPossession === 'team2') return;
       
       if (!gameState.isMainTimerRunning) {
           startMainTimer();
       }
       
       stopPossessionTimer();
       resetPossession();
       
       DOM.buttons.possession2.classList.remove('bg-blue-600');
       DOM.buttons.possession2.classList.add('bg-gray-400');
       
       gameState.lastPossession = 'team2';
       // Ne démarrer le timer 30s que si on n'est pas en mode corner ou penalty
       if (!gameState.possessionMode || gameState.possessionMode === 'normal') {
           startPossessionTimer(CONFIG.POSSESSION_TIME, 'normal');
       }
       
       addLogEntry(`🏐 Possession Équipe 2 - ${formatTime(gameState.mainTimer)}`, 'text-blue-600');
   }

   function handleCorner() {
       stopPossessionTimer();
       startPossessionTimer(CONFIG.SPECIAL_TIME, 'corner');
       addLogEntry(`🔄 Corner - ${formatTime(gameState.mainTimer)}<br>20 secondes de possession`, 'text-gray-600');
   }

   /**
    * GESTION D'UN TEMPS MORT
    * Lance un temps mort de 60 secondes avec suspension des exclusions en cours
    * @param {number} team - Numéro de l'équipe demandant le temps mort (1 ou 2)
    */
   function handleTimeout(team) {
       // === VÉRIFICATION DU QUOTA DE TEMPS MORTS ===
       // Chaque équipe a droit à 2 temps morts par MATCH complet (4 périodes) en waterpolo
       if ((team === 1 && gameState.timeoutsTeam1 >= CONFIG.MAX_TIMEOUTS) ||
           (team === 2 && gameState.timeoutsTeam2 >= CONFIG.MAX_TIMEOUTS)) {
           return;                                                              // Sortir si quota épuisé
       }

       // === ARRÊT DE TOUS LES CHRONOMÈTRES ===
       stopAllTimers();                                                         // Arrêter le jeu complètement
       
       // === SAUVEGARDE DES EXCLUSIONS EN COURS ===
       // Les exclusions sont suspendues pendant le temps mort et reprennent après
       const savedExclusions = {};                                              // Objet pour sauvegarder l'état des exclusions
       const currentTime = Date.now();                                          // Timestamp actuel
       
       ['team1', 'team2'].forEach(teamKey => {
           savedExclusions[teamKey] = {};                                       // Initialiser pour chaque équipe
           for (const playerNumber in gameState.exclusionTimers[teamKey]) {
               const exclusion = gameState.exclusionTimers[teamKey][playerNumber];
               if (exclusion && exclusion.endTime > currentTime) {             // Si l'exclusion est encore active
                   // Calculer le temps restant à la suspension
                   const timeRemaining = exclusion.endTime - currentTime;
                   savedExclusions[teamKey][playerNumber] = {
                       timeRemaining: timeRemaining,                            // Temps restant à reprendre après temps mort
                       count: exclusion.count,                                  // Numéro de l'exclusion
                       badge: exclusion.badge                                   // Référence du badge visuel
                   };
                   
                   // === INDICATION VISUELLE DE LA SUSPENSION ===
                   // Modifier l'apparence du badge pour montrer la suspension
                   if (exclusion.badge) {
                       exclusion.badge.style.opacity = '0.5';                  // Rendre semi-transparent
                       exclusion.badge.style.border = '2px solid #FCD34D';     // Bordure jaune pour indication
                   }
               }
           }
       });
       
       // === INITIALISATION DU TEMPS MORT ===
       let timeoutRemaining = CONFIG.TIMEOUT_TIME;                              // 60 secondes de temps mort
       
       // === CRÉATION DU BADGE VISUEL DE TEMPS MORT ===
       const badge = document.createElement('span');
       badge.className = `timeout-badge ${team === 1 ? 'bg-red-600' : 'bg-blue-600'}`;
       badge.textContent = `TM${team === 1 ? gameState.timeoutsTeam1 + 1 : gameState.timeoutsTeam2 + 1}`;
       
       // Ajouter le badge à côté du nom de l'équipe concernée
       const teamElement = document.getElementById(team === 1 ? 'team1Name' : 'team2Select');
       const teamHeader = teamElement.parentElement;
       teamHeader.classList.add('team-header');
       
       // Positionnement du badge selon l'équipe
       if (team === 1) {
           teamHeader.appendChild(badge);                                       // Équipe 1 : badge à droite
       } else {
           teamHeader.insertBefore(badge, teamElement);                        // Équipe 2 : badge à gauche
       }

       // === MISE À JOUR DU COMPTEUR DE TEMPS MORTS ===
       if (team === 1) {
           gameState.timeoutsTeam1++;                                           // Incrémenter compteur équipe 1
           if (gameState.timeoutsTeam1 >= CONFIG.MAX_TIMEOUTS) {
               DOM.buttons.timeout1.disabled = true;                           // Désactiver bouton si quota atteint
           }
       } else {
           gameState.timeoutsTeam2++;                                           // Incrémenter compteur équipe 2
           if (gameState.timeoutsTeam2 >= CONFIG.MAX_TIMEOUTS) {
               DOM.buttons.timeout2.disabled = true;                           // Désactiver bouton si quota atteint
           }
       }

       // === ENREGISTREMENT DANS LE JOURNAL ===
       addLogEntry(`⏸️ Temps mort ${getTeamName(team)} - ${formatTime(gameState.mainTimer)}`, 
                   team === 1 ? 'text-red-600' : 'text-blue-600');

       // === SAUVEGARDE DU TEMPS PRINCIPAL ===
       const oldMainTimer = gameState.mainTimer;                               // Sauvegarder pour restaurer après le temps mort
       
       // === DÉCOMPTE DU TEMPS MORT ===
       // Utiliser le chronomètre principal pour afficher le décompte du temps mort
       gameState.mainTimer = timeoutRemaining;                                  // Remplacer temporairement le temps principal
       updateDisplay();                                                         // Mettre à jour l'affichage

       timeoutInterval = setInterval(() => {
           timeoutRemaining--;                                                  // Décrémenter le temps mort
           gameState.mainTimer = timeoutRemaining;                              // Mettre à jour l'affichage
           
           // === ALERTE VISUELLE DES DERNIÈRES SECONDES ===
           if (timeoutRemaining <= 10) {
               DOM.timers.main.classList.remove('blink');                      // Retirer le clignotement normal
               DOM.timers.main.classList.add('blink-red');                     // Ajouter le clignotement rouge d'alerte
           }
           
           updateDisplay();                                                     // Actualiser l'affichage

           // === FIN DU TEMPS MORT ===
           if (timeoutRemaining <= 0) {
               clearInterval(timeoutInterval);                                  // Arrêter le décompte
               DOM.sound.play();                                                // Signal sonore de fin de temps mort
               addLogEntry(`▶️ Fin du temps mort - ${formatTime(oldMainTimer)}`);
               
               // === RESTAURATION DU TEMPS PRINCIPAL ===
               gameState.mainTimer = oldMainTimer;                              // Remettre le temps de jeu original
               DOM.timers.main.classList.remove('blink-red');                  // Retirer l'alerte visuelle
               updateDisplay();                                                 // Mettre à jour l'affichage
               
               // === RESTAURATION DES EXCLUSIONS SUSPENDUES ===
               const resumeTime = Date.now();                                   // Timestamp de reprise
               ['team1', 'team2'].forEach(teamKey => {
                   for (const playerNumber in savedExclusions[teamKey]) {
                       const saved = savedExclusions[teamKey][playerNumber];
                       
                       // Rétablir l'exclusion avec le temps restant exact
                       gameState.exclusionTimers[teamKey][playerNumber] = {
                           startTime: resumeTime,                               // Nouveau début
                           endTime: resumeTime + saved.timeRemaining,          // Fin = maintenant + temps restant
                           count: saved.count,                                  // Conserver le numéro d'exclusion
                           badge: saved.badge                                   // Conserver la référence du badge
                       };
                       
                       // === RESTAURATION VISUELLE DES BADGES ===
                       if (saved.badge) {
                           saved.badge.style.opacity = '1';                    // Remettre opacité normale
                           saved.badge.style.border = 'none';                  // Retirer la bordure de suspension
                       }
                   }
               });
               
               // === NOTIFICATION DE REPRISE DES EXCLUSIONS ===
               if (Object.keys(savedExclusions.team1).length > 0 || Object.keys(savedExclusions.team2).length > 0) {
                   addLogEntry(`🔄 Exclusions reprises après le temps mort`, 'text-orange-600');
               }
               
               // === ATTENTE DE REPRISE MANUELLE ===
               // Le jeu ne redémarre pas automatiquement, l'arbitre doit relancer
               addLogEntry(`⚠️ En attente de la reprise du jeu...`, 'text-yellow-600');
           }
       }, 1000);                                                                // Exécuter toutes les 1000ms
   }

   /**
    * GESTION D'UN BUT MARQUÉ
    * Incrémente le score de l'équipe et enregistre l'événement dans le journal
    * @param {number} equipe - Numéro de l'équipe (1 ou 2)
    * @param {number} playerNumber - Numéro du joueur qui a marqué
    */
   function handleBut(equipe, playerNumber) {
       const team = getTeamName(equipe);                     // Nom de l'équipe pour l'affichage
       
       // === INCRÉMENTATION DU SCORE ===
       if (equipe === 1) {
           gameState.scoreTeam1++;                                              // Incrémenter score équipe 1
           DOM.scores.team1.textContent = gameState.scoreTeam1;                 // Mettre à jour l'affichage
       } else {
           gameState.scoreTeam2++;                                              // Incrémenter score équipe 2
           DOM.scores.team2.textContent = gameState.scoreTeam2;                 // Mettre à jour l'affichage
       }
       
       // === RÉCUPÉRATION DU NOM DU JOUEUR ===
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       // === ENREGISTREMENT DANS LE JOURNAL ===
       addLogEntry(`⚽ BUT ! ${team} - ${playerName} (${playerNumber}) - ${formatTime(gameState.mainTimer)}`, 
           equipe === 1 ? 'text-red-600 font-bold' : 'text-blue-600 font-bold');
       
       // === MISE À JOUR DE L'AFFICHAGE ===
       updateDisplay();                                                         // Actualiser tous les éléments visuels
       
       // === ENREGISTREMENT DU BUT EN BASE ===
       const joueurs = equipe === 1 ? window.lastJoueursEquipe1 : window.lastJoueursEquipe2;
       const idx = playerNumber - 1;
       const chronoTemps = document.getElementById('timer').textContent;
       enregistrerBut({
           id_match: 3, // Valeur existante pour test
           id_joueur: joueurs && joueurs[idx] ? joueurs[idx].id_joueur : null,
           id_equipe: equipe,
           temps_chrono: chronoTemps,
           id_periode: 1
       });
   }

   function updateExclusionBadges(equipe, playerNumber, exclusionCount) {
       // Utiliser le nouveau système consolidé
       updatePlayerIndicators(equipe, playerNumber, 'exclusion', exclusionCount);

       // Si c'est la 3ème exclusion, appliquer le style d'exclusion définitive
       if (exclusionCount >= CONFIG.MAX_EXCLUSIONS) {
           const playerControls = document.querySelector(`[data-player="${playerNumber}"][data-team="${equipe}"]`);
           if (playerControls) {
               const playerRow = playerControls.parentElement;
               if (playerRow) {
                   playerRow.classList.add('player-excluded');
               }
           }
       }
   }

   /**
    * GESTION D'UNE EXCLUSION TEMPORAIRE
    * Traite une exclusion de 20 secondes avec gestion des exclusions multiples
    * @param {number} equipe - Numéro de l'équipe (1 ou 2)
    * @param {number} playerNumber - Numéro du joueur exclu
    * @param {boolean} fromYellowCard - True si l'exclusion provient d'un carton jaune (évite la duplication de logs)
    */
   function handleExclusion(equipe, playerNumber, fromYellowCard = false) {
       const team = getTeamName(equipe);                     // Nom de l'équipe pour l'affichage
       const teamKey = equipe === 1 ? 'team1' : 'team2';                        // Clé pour les objets d'état
       
       // === RÉCUPÉRATION DU NOM DU JOUEUR ===
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       // === GESTION DU COMPTEUR D'EXCLUSIONS ===
       // Initialiser le compteur d'exclusions pour ce joueur s'il n'existe pas
       if (!gameState.playerExclusions[teamKey][playerNumber]) {
           gameState.playerExclusions[teamKey][playerNumber] = 0;
       }
       
       // Incrémenter le compteur d'exclusions
       gameState.playerExclusions[teamKey][playerNumber]++;
       const exclusionCount = gameState.playerExclusions[teamKey][playerNumber];
       
       // === MISE À JOUR DES BADGES VISUELS ===
       updateExclusionBadges(equipe, playerNumber, exclusionCount);
       
       // === VÉRIFICATION DE L'EXCLUSION DÉFINITIVE ===
       // À la 3ème exclusion, le joueur est exclu définitivement du match
       if (exclusionCount >= CONFIG.MAX_EXCLUSIONS) {
           addLogEntry(`🔴 ${playerName} a reçu sa 3ème exclusion - Exclusion définitive`, 'text-red-600 font-bold');
           playLongBeep();                                                      // Signal sonore prolongé pour exclusion définitive
       }

       // === VÉRIFICATION D'EXCLUSION EN COURS ===
       // Un joueur ne peut pas être exclu plusieurs fois simultanément
       if (gameState.exclusionTimers[teamKey][playerNumber]) {
           addLogEntry(`⚠️ ${playerName} est déjà exclu !`, 'text-yellow-600 font-bold');
           return;                                                              // Sortir de la fonction
       }

       // === CRÉATION DU BADGE D'EXCLUSION TEMPORAIRE ===
       const badge = document.createElement('div');
       badge.className = `exclusion-badge ${equipe === 1 ? 'bg-red-600' : 'bg-blue-600'} text-white px-2 py-1 rounded-md text-sm mb-1`;
       
       DOM.badges[equipe === 1 ? 'team1' : 'team2'].appendChild(badge);         // Ajouter le badge à la zone d'affichage

       // === ENREGISTREMENT DE L'EXCLUSION TEMPORAIRE ===
       // Créer un timer de 20 secondes pour cette exclusion
       gameState.exclusionTimers[teamKey][playerNumber] = {
           startTime: Date.now(),                                               // Heure de début de l'exclusion
           endTime: Date.now() + (CONFIG.EXCLUSION_TIME * 1000),               // Heure de fin (20 secondes plus tard)
           count: exclusionCount,                                               // Numéro de cette exclusion pour ce joueur
           badge: badge                                                         // Référence du badge pour mise à jour
       };

       // === ENREGISTREMENT DANS LE JOURNAL ===
       // Ajouter le log seulement si ce n'est pas causé par un carton jaune (évite la duplication)
       if (!fromYellowCard) {
           addLogEntry(`🔄 Exclusion ${team} - ${playerName} (${playerNumber}) - ${formatTime(gameState.mainTimer)} - ${exclusionCount}/${CONFIG.MAX_EXCLUSIONS}`, 
               equipe === 1 ? 'text-red-600' : 'text-blue-600');
           // ENREGISTREMENT EN BASE
           const joueurs = equipe === 1 ? window.lastJoueursEquipe1 : window.lastJoueursEquipe2;
           const idx = playerNumber - 1;
           const chronoTemps = document.getElementById('timer').textContent;
           enregistrerEvenement({
               id_match: 3, // à remplacer par la sélection dynamique plus tard
               id_joueur: joueurs && joueurs[idx] ? joueurs[idx].id_joueur : null,
               id_equipe: equipe,
               type_evenement: 'exclusion',
               details: `Exclusion temporaire ${exclusionCount}/${CONFIG.MAX_EXCLUSIONS}`,
               temps_chrono: chronoTemps
           });
       }
   }

   function handlePenaltyShot(equipe, playerNumber) {
       const team = getTeamName(equipe);
       const opposingTeam = getTeamName(equipe === 1 ? 2 : 1);
       const teamKey = equipe === 1 ? 'team1' : 'team2';
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       // Arrêter tous les chronos - ils restent arrêtés jusqu'à la reprise manuelle
       stopAllTimers();
       
       // Initialiser les compteurs de penalties si nécessaire
       if (!gameState.playerPenalties) {
           gameState.playerPenalties = {
               team1: {},
               team2: {}
           };
       }
       
       // S'assurer que l'objet de l'équipe existe
       if (!gameState.playerPenalties[teamKey]) {
           gameState.playerPenalties[teamKey] = {};
       }
       
       // Incrémenter le compteur de penalties pour ce joueur
       if (!gameState.playerPenalties[teamKey][playerNumber]) {
           gameState.playerPenalties[teamKey][playerNumber] = 0;
       }
       gameState.playerPenalties[teamKey][playerNumber]++;
       
       // Mettre à jour l'affichage des badges
       updatePlayerIndicators(equipe, playerNumber, 'penalty', gameState.playerPenalties[teamKey][playerNumber]);
       
       // Ajouter l'événement au journal selon les vraies règles
       addLogEntry(`🟡 PENALTY sifflé contre ${team} - ${playerName} (${playerNumber}) - ${formatTime(gameState.mainTimer)}`, 'text-yellow-600 font-bold');
       addLogEntry(`📍 ${opposingTeam} obtient un coup franc - Jeu arrêté`, 'text-blue-600 font-bold');
       addLogEntry(`⚠️ En attente de la reprise du jeu par l'arbitre...`, 'text-gray-600');
       
       // Mettre à jour l'affichage pour montrer l'arrêt
       if (DOM.labels && DOM.labels.possession) {
           DOM.labels.possession.textContent = 'PENALTY - JEU ARRÊTÉ';
           DOM.labels.possession.style.color = '#DC2626'; // Rouge pour montrer l'arrêt
       }
       
       // Ajouter une notification visuelle
       const notification = document.createElement('div');
       notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg font-bold z-50';
       notification.innerHTML = `PENALTY !<br>Jeu arrêté - Redémarrage manuel`;
       document.body.appendChild(notification);
       
       setTimeout(() => {
           if (notification.parentNode) {
               notification.remove();
           }
       }, 6000);
       
       // Enregistrement en base de données
       const joueurs = equipe === 1 ? window.lastJoueursEquipe1 : window.lastJoueursEquipe2;
       const idx = playerNumber - 1;
       const chronoTemps = document.getElementById('timer').textContent;
       enregistrerEvenement({
           id_match: window.idMatchActuel || 1,
           id_joueur: joueurs && joueurs[idx] ? joueurs[idx].id_joueur : null,
           id_equipe: equipe,
           type_evenement: 'penalty',
           details: `Penalty contre ${playerName} (${playerNumber})`,
           temps_chrono: chronoTemps
       });
   }

   function handlePenaltyStart() {
       // Démarrer la phase de penalty de 20 secondes
       stopAllTimers();
       startPossessionTimer(CONFIG.SPECIAL_TIME, 'penalty');
       
       addLogEntry(`⚠️ Phase de penalty - ${formatTime(gameState.mainTimer)}<br>20 secondes pour tirer`, 'text-yellow-600 font-bold');
   }

   function handleAccident() {
       stopAllTimers();
       addLogEntry('🚑 Accident signalé - Jeu arrêté', 'text-red-700 font-bold');
       DOM.buttons.lateral.team1.accident.classList.add('blink-red');
       DOM.buttons.lateral.team2.accident.classList.add('blink-red');
   }

   function handleReclamation() {
       const wasRunning = gameState.isMainTimerRunning;
       const wasPossessionRunning = gameState.isPossessionRunning;
       stopMainTimer();
       
       DOM.buttons.lateral.team1.reclamation.classList.add('bg-yellow-500');
       DOM.buttons.lateral.team1.reclamation.classList.add('blink');
       DOM.buttons.lateral.team2.reclamation.classList.add('bg-yellow-500');
       DOM.buttons.lateral.team2.reclamation.classList.add('blink');
       
       const currentTime = formatTime(gameState.mainTimer);
       addLogEntry(`📢 RÉCLAMATION - ${currentTime}`, 'text-yellow-600 font-bold');
       
       if (confirm('Confirmez-vous la réclamation ? Cliquez OK pour continuer.')) {
           addLogEntry('✅ Réclamation enregistrée', 'text-green-600');
           addLogEntry('⚠️ En attente de la reprise du jeu...', 'text-yellow-600');
           // Ne pas redémarrer automatiquement les chronos
           // L'arbitre devra appuyer sur Lancer/Arrêt pour reprendre
  } else {
           addLogEntry('❌ Réclamation annulée', 'text-red-600');
           addLogEntry('⚠️ En attente de la reprise du jeu...', 'text-yellow-600');
       }
       
       setTimeout(() => {
           DOM.buttons.lateral.team1.reclamation.classList.remove('bg-yellow-500');
           DOM.buttons.lateral.team1.reclamation.classList.remove('blink');
           DOM.buttons.lateral.team2.reclamation.classList.remove('bg-yellow-500');
           DOM.buttons.lateral.team2.reclamation.classList.remove('blink');
       }, 3000);
   }

   function handleCartonJaune(equipe) {
       const dialog = document.createElement('dialog');
       dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4';
       
       dialog.innerHTML = `
           <div class="bg-white rounded-lg p-6 max-w-md w-full">
               <h2 class="text-xl font-bold mb-4">Carton Jaune - Équipe ${equipe}</h2>
               <div class="mb-4">
                   <label class="block mb-2">Sélectionner le joueur :</label>
                   <select id="playerSelect" class="w-full p-2 border rounded">
                   </select>
               </div>
               <div class="flex justify-end space-x-2">
                   <button class="px-4 py-2 bg-gray-200 rounded" onclick="this.closest('dialog').close()">Annuler</button>
                   <button class="px-4 py-2 bg-yellow-500 text-white rounded" id="confirmBtn">Confirmer</button>
               </div>
           </div>
       `;

       document.body.appendChild(dialog);
       dialog.showModal();

       const playerSelect = dialog.querySelector('#playerSelect');
       const inputs = document.querySelectorAll(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} input.player-name`);
       
       inputs.forEach((input, index) => {
           if (input.value.trim()) {
               const option = document.createElement('option');
               option.value = index + 1;
               option.textContent = `${index + 1} - ${input.value}`;
               playerSelect.appendChild(option);
           }
       });

       dialog.querySelector('#confirmBtn').addEventListener('click', () => {
           const team = equipe;
           const playerNumber = parseInt(playerSelect.value);
           const playerName = playerSelect.options[playerSelect.selectedIndex].textContent;
           const teamKey = team === 1 ? 'team1' : 'team2';
           
           // Initialiser le compteur de cartons jaunes pour ce joueur s'il n'existe pas
           if (!gameState.playerYellowCards[teamKey][playerNumber]) {
               gameState.playerYellowCards[teamKey][playerNumber] = 0;
           }
           
           // Incrémenter le compteur de cartons jaunes
           gameState.playerYellowCards[teamKey][playerNumber]++;
           const yellowCardCount = gameState.playerYellowCards[teamKey][playerNumber];
           
           // Ajouter la pastille de carton jaune
           updatePlayerIndicators(team, playerNumber, 'yellowCard', yellowCardCount);
           
           // Colorer la ligne du joueur
           const playerRow = document.querySelector(`#${team === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber})`);
           if (playerRow) {
               playerRow.classList.add('bg-yellow-100');
           }
           
           // Ajouter l'événement au journal
           addLogEntry(`⚠️ Carton Jaune ${yellowCardCount}/3 - ${playerName} (Équipe ${team}) - ${formatTime(gameState.mainTimer)}`, 'text-yellow-500 font-bold');
           
           // Vérifier les conséquences selon les règles
           if (yellowCardCount === 2) {
               // 2 cartons jaunes = exclusion temporaire de 20 secondes
               addLogEntry(`🟡 2ème carton jaune - ${playerName} exclu temporairement (20s)`, 'text-orange-600 font-bold');
               handleExclusion(team, playerNumber, true); // true = exclusion causée par carton jaune
           } else if (yellowCardCount >= 3) {
               // 3 cartons jaunes = exclusion définitive
               addLogEntry(`🔴 3ème carton jaune - ${playerName} EXCLU DÉFINITIVEMENT du match`, 'text-red-600 font-bold');
               
               // Marquer le joueur comme exclu définitivement
               if (playerRow) {
                   playerRow.classList.remove('bg-yellow-100');
                   playerRow.classList.add('bg-red-200', 'opacity-50');
                   
                   // Désactiver tous les boutons du joueur
                   const buttons = playerRow.querySelectorAll('button');
                   buttons.forEach(button => {
                       button.disabled = true;
                       button.classList.add('opacity-50');
                   });
               }
               
               // Créer un badge d'exclusion définitive
               const badge = document.createElement('div');
               badge.className = 'exclusion-badge bg-red-800 text-white px-2 py-1 rounded-md text-sm mb-1';
               badge.textContent = `${playerNumber} EXCLU (3 CJ)`;
               DOM.badges[team === 1 ? 'team1' : 'team2'].appendChild(badge);
               
               playLongBeep();
           }
           
           dialog.close();
       });

       dialog.addEventListener('close', () => {
           document.body.removeChild(dialog);
       });
   }

   function addLogEntry(message, className = 'text-gray-600') {
       const log = document.createElement('div');
       log.className = `mb-1 ${className}`;
       log.innerHTML = message;
       DOM.log.appendChild(log);
       
       // Scroll automatique vers le bas
       DOM.log.scrollTop = DOM.log.scrollHeight;
       
       // Ajuster la hauteur maximale du log
       const timeoutButton = document.getElementById('btnTempsMortEquipe1');
       if (timeoutButton) {
           const buttonBottom = timeoutButton.getBoundingClientRect().bottom;
           const logTop = DOM.log.getBoundingClientRect().top;
           DOM.log.style.maxHeight = `${buttonBottom - logTop - 20}px`;
       }
   }

   // Event listeners
   document.addEventListener('DOMContentLoaded', () => {
       console.log('Initialisation...');
       initializeDOMElements();
       initializePlayerButtons();
       initializeLateralButtons();
       
       // Event listener pour le bouton de possession
       if (DOM.buttons.possession) {
           DOM.buttons.possession.addEventListener('click', handlePossessionButton);
       }
       
       // Event listener pour le bouton 20 secondes
       document.getElementById('special20Btn').addEventListener('click', () => {
           if (!gameState.isMainTimerRunning) {
               startMainTimer();
           }
           gameState.possessionTimer = CONFIG.SPECIAL_TIME;
           gameState.possessionMode = 'special20';
           gameState.isPossessionRunning = true;
           updateDisplay();
           addLogEntry(`⏱️ Phase de 20 secondes démarrée - ${formatTime(gameState.mainTimer)}`, 'text-yellow-600');
       });

       // Event listener pour la barre d'espace
       document.addEventListener('keydown', (event) => {
           // Vérifier si l'événement vient d'un champ de texte
           if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
               return;
           }
           
           // Si c'est la barre d'espace et qu'on n'est pas dans un champ de texte
           if (event.code === 'Space') {
               event.preventDefault(); // Empêcher le défilement de la page
               if (mainInterval) {
                   stopMainTimer();
  } else {
                   startMainTimer();
                   // Réactiver la possession si elle était active avant
                   if (gameState.possessionTimer < CONFIG.POSSESSION_TIME) {
                       gameState.isPossessionRunning = true;
                   }
               }
           }
       });





       // Initialisation des boutons de contrôle
       if (DOM.buttons.startStop) {
           DOM.buttons.startStop.addEventListener('click', () => {
               if (mainInterval) {
                   stopMainTimer();
               } else {
                   startMainTimer();
                   // Réactiver la possession si elle était active avant
                   if (gameState.possessionTimer < CONFIG.POSSESSION_TIME) {
                       gameState.isPossessionRunning = true;
                   }
               }
           });
       }

       // Event listeners pour les possessions
       if (DOM.buttons.possession1) {
           DOM.buttons.possession1.addEventListener('click', startTeam1Possession);
       }
       if (DOM.buttons.possession2) {
           DOM.buttons.possession2.addEventListener('click', startTeam2Possession);
       }

       // Event listeners pour corner et penalty
       if (DOM.buttons.corner) {
           DOM.buttons.corner.addEventListener('click', handleCorner);
       }
       if (DOM.buttons.penalty) {
           DOM.buttons.penalty.addEventListener('click', handlePenaltyStart);
       }

       // Event listeners pour les temps morts
       if (DOM.buttons.timeout1) {
           DOM.buttons.timeout1.addEventListener('click', () => handleTimeout(1));
       }
       if (DOM.buttons.timeout2) {
           DOM.buttons.timeout2.addEventListener('click', () => handleTimeout(2));
       }

       // Event listener pour le bouton période suivante
       document.getElementById('nextPeriodBtn').addEventListener('click', nextPeriod);

       // Initialisation de l'affichage
       updateDisplay();

       console.log('Initialisation terminée');
   });

   function startPossessionTimer(duration, mode = 'normal') {
       gameState.possessionMode = mode;
       gameState.possessionTimer = duration;
       gameState.isPossessionRunning = true;
       updateDisplay();
   }

   function handlePossessionButton() {
       if (!gameState.isMainTimerRunning) {
           startMainTimer();
       } else {
           gameState.possessionCount++;
           addLogEntry(`🏐 Changement manuel de possession - ${formatTime(gameState.mainTimer)}`, 'text-gray-600');
           
           // Redémarrer à 30 secondes
           gameState.possessionTimer = CONFIG.POSSESSION_TIME;
           gameState.isPossessionRunning = true;
           gameState.possessionMode = 'normal';
       }
   }

   // Chargement des joueurs
  fetch('recuperer_joueurs.php')
    .then(res => res.json())
    .then(data => {
           // Injecte les joueurs de l'équipe 1
      const joueurs1 = document.querySelectorAll('#leftPlayersList input.player-name');
      data.equipe1.forEach((prenom, index) => {
        if (joueurs1[index]) joueurs1[index].value = prenom;
      });

           // Injecte les joueurs de l'équipe 2
      const joueurs2 = document.querySelectorAll('#rightPlayersList input.player-name');
      data.equipe2.forEach((prenom, index) => {
        if (joueurs2[index]) joueurs2[index].value = prenom;
      });
    })
    .catch(error => console.error('Erreur chargement joueurs :', error));

   /**
    * CHARGEMENT DES ÉQUIPES POUR LE MENU DÉROULANT
    * Les équipes sont maintenant chargées directement dans le code via la fonction chargerEquipes()
    * qui est appelée au chargement de la page
    */

   function handleEDA(equipe, playerNumber) {
       const team = equipe === 1 ? 'Équipe 1' : 'Équipe 2';
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       // Ajouter l'indicateur EDA à côté du nom du joueur
       updatePlayerIndicators(equipe, playerNumber, 'eda');
       
       // Créer le badge EDA permanent
       const badge = document.createElement('div');
       badge.className = `eda-badge bg-black text-white px-2 py-1 rounded-md text-sm mb-1`;
       badge.textContent = `${playerNumber} EDA`;
       
       DOM.badges[equipe === 1 ? 'team1' : 'team2'].appendChild(badge);

       // Marquer visuellement le joueur comme exclu définitivement
       const playerRow = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber})`);
       if (playerRow) {
           playerRow.classList.add('bg-gray-200', 'opacity-50');
       }

       addLogEntry(`⛔ EDA - ${playerName} (${playerNumber}) - ${team} - ${formatTime(gameState.mainTimer)}`, 
           'text-black font-bold');
   }

   function handleEDAP(equipe, playerNumber) {
       const team = equipe === 1 ? 'Équipe 1' : 'Équipe 2';
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       // Ajouter l'indicateur EDAP à côté du nom du joueur
       updatePlayerIndicators(equipe, playerNumber, 'edap');
       
       let timeRemaining = CONFIG.EDAP_TIME;
       const edapKey = `${equipe}-${playerNumber}`;

       // Vérifier si le joueur est déjà en EDAP
       if (gameState.edapTimers.has(edapKey)) {
           addLogEntry(`⚠️ ${playerName} est déjà en EDAP !`, 'text-yellow-600 font-bold');
           return;
       }

       // Créer le badge EDAP
       const badge = document.createElement('div');
       badge.className = `edap-badge bg-red-800 text-white px-2 py-1 rounded-md text-sm mb-1`;
       badge.textContent = `${playerNumber} EDAP: ${Math.floor(timeRemaining/60)}:${(timeRemaining%60).toString().padStart(2, '0')}`;
       
       DOM.badges[equipe === 1 ? 'team1' : 'team2'].appendChild(badge);

       addLogEntry(`🔴 EDAP - ${playerName} (${playerNumber}) - ${team} - ${formatTime(gameState.mainTimer)}`, 
           'text-red-800 font-bold');

       // Démarrer le décompte de 4 minutes
       const timer = setInterval(() => {
           timeRemaining--;
           badge.textContent = `${playerNumber} EDAP: ${Math.floor(timeRemaining/60)}:${(timeRemaining%60).toString().padStart(2, '0')}`;
           
           if (timeRemaining <= 0) {
               clearInterval(timer);
               badge.remove();
               addLogEntry(`✅ Fin de l'EDAP - ${playerName} (${playerNumber}) peut être remplacé`, 'text-green-600');
               gameState.edapTimers.delete(edapKey);
           }
       }, 1000);

       gameState.edapTimers.set(edapKey, timer);
   }

   function handleCartonRouge(equipe, playerNumber) {
       const team = equipe === 1 ? 'Équipe 1' : 'Équipe 2';
       const playerInput = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber}) input.player-name`);
       const playerName = playerInput ? playerInput.value || `Joueur ${playerNumber}` : `Joueur ${playerNumber}`;
       
       if (playerInput) {
           // Ajouter la classe pour griser et barrer le nom du joueur
           playerInput.classList.add('excluded-player');
           
           // Ajouter une bordure rouge autour du nom
           playerInput.style.border = '2px solid red';
           playerInput.style.borderRadius = '4px';
           playerInput.style.padding = '2px 4px';

           // Ajouter un tooltip pour indiquer que le joueur est exclu définitivement
           playerInput.title = "Joueur exclu définitivement (Carton Rouge)";

           // Marquer visuellement la ligne du joueur comme exclue définitivement
           const playerRow = document.querySelector(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} div:nth-child(${playerNumber})`);
           if (playerRow) {
               playerRow.classList.add('bg-gray-200', 'opacity-50');
               
               // Désactiver tous les boutons du joueur
               const buttons = playerRow.querySelectorAll('button');
               buttons.forEach(button => {
                   button.disabled = true;
                   button.classList.add('opacity-50');
               });
           }
       }

       addLogEntry(`CR - Carton Rouge - ${playerName} (${playerNumber}) - ${team} - ${formatTime(gameState.mainTimer)}`, 
           'text-red-600 font-bold');
   }

   // Configuration des boutons supprimée - gérée dans initializePlayerButtons()

   // Event listener pour le bouton penalty central - élément non trouvé, commenté
   // document.getElementById('penaltyBtn').addEventListener('click', handlePenaltyStart);

   function initializePlayerButtons() {
       // Initialiser les boutons pour les deux équipes
       document.querySelectorAll('.player-controls').forEach(controls => {
           const playerNumber = parseInt(controls.getAttribute('data-player'));
           const equipe = parseInt(controls.getAttribute('data-team'));
           
           // Bouton But (B) - différentes classes selon l'équipe
           const butButton = controls.querySelector('.but-button, .player-B, .player-B-right');
           if (butButton) {
               butButton.addEventListener('click', () => handleBut(equipe, playerNumber));
           }
           
           // Bouton Exclusion (E)
           const exclusionButton = controls.querySelector('.exclusion-button');
           if (exclusionButton) {
               exclusionButton.addEventListener('click', () => handleExclusion(equipe, playerNumber));
           }
           
           // Bouton Penalty (P)
           const penaltyButton = controls.querySelector('.btn-p');
           if (penaltyButton) {
               penaltyButton.addEventListener('click', () => handlePenaltyShot(equipe, playerNumber));
           }
       });
   }

   function initializeLateralButtons() {
       // Initialiser les boutons latéraux pour les deux équipes
       document.querySelectorAll('.lateral-buttons').forEach(buttonGroup => {
           const equipe = parseInt(buttonGroup.getAttribute('data-team'));
           
           // Bouton A (Accident)
           buttonGroup.querySelector('.btn-a').addEventListener('click', handleAccident);
           
           // Bouton R (Réclamation)
           buttonGroup.querySelector('.btn-r').addEventListener('click', handleReclamation);
           
           // Bouton CJ (Carton Jaune)
           buttonGroup.querySelector('.btn-cj').addEventListener('click', () => handleCartonJaune(equipe));
           
           // Bouton CR (Carton Rouge)
           buttonGroup.querySelector('.btn-cr').addEventListener('click', () => {
               const dialog = document.createElement('dialog');
               dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4';
               
               dialog.innerHTML = `
                   <div class="bg-white rounded-lg p-6 max-w-md w-full">
                       <h2 class="text-xl font-bold mb-4">Carton Rouge - Équipe ${equipe}</h2>
                       <div class="mb-4">
                           <label class="block mb-2">Sélectionner le joueur :</label>
                           <select id="playerSelect" class="w-full p-2 border rounded">
                           </select>
                       </div>
                       <div class="flex justify-end space-x-2">
                           <button class="px-4 py-2 bg-gray-200 rounded" onclick="this.closest('dialog').close()">Annuler</button>
                           <button class="px-4 py-2 bg-red-500 text-white rounded" id="confirmBtn">Confirmer</button>
                       </div>
                   </div>
               `;

               document.body.appendChild(dialog);
               dialog.showModal();

               const playerSelect = dialog.querySelector('#playerSelect');
               const inputs = document.querySelectorAll(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} input.player-name`);
               
               inputs.forEach((input, index) => {
                   if (input.value.trim()) {
                       const option = document.createElement('option');
                       option.value = index + 1;
                       option.textContent = `${index + 1} - ${input.value}`;
                       playerSelect.appendChild(option);
                   }
               });

               dialog.querySelector('#confirmBtn').addEventListener('click', () => {
                   const playerNumber = parseInt(playerSelect.value);
                   handleCartonRouge(equipe, playerNumber);
                   dialog.close();
               });

               dialog.addEventListener('close', () => {
                   document.body.removeChild(dialog);
               });
           });
           
           // Bouton EDA
           buttonGroup.querySelector('.btn-eda').addEventListener('click', () => {
               const dialog = document.createElement('dialog');
               dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4';
               
               dialog.innerHTML = `
                   <div class="bg-white rounded-lg p-6 max-w-md w-full">
                       <h2 class="text-xl font-bold mb-4">EDA - Équipe ${equipe}</h2>
                       <div class="mb-4">
                           <label class="block mb-2">Sélectionner le joueur :</label>
                           <select id="playerSelect" class="w-full p-2 border rounded">
                           </select>
                       </div>
                       <div class="flex justify-end space-x-2">
                           <button class="px-4 py-2 bg-gray-200 rounded" onclick="this.closest('dialog').close()">Annuler</button>
                           <button class="px-4 py-2 bg-red-500 text-white rounded" id="confirmBtn">Confirmer</button>
                       </div>
                   </div>
               `;

               document.body.appendChild(dialog);
               dialog.showModal();

               const playerSelect = dialog.querySelector('#playerSelect');
               const inputs = document.querySelectorAll(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} input.player-name`);
               
               inputs.forEach((input, index) => {
                   if (input.value.trim()) {
                       const option = document.createElement('option');
                       option.value = index + 1;
                       option.textContent = `${index + 1} - ${input.value}`;
                       playerSelect.appendChild(option);
                   }
               });

               dialog.querySelector('#confirmBtn').addEventListener('click', () => {
                   const playerNumber = parseInt(playerSelect.value);
                   handleEDA(equipe, playerNumber);
                   dialog.close();
               });

               dialog.addEventListener('close', () => {
                   document.body.removeChild(dialog);
               });
           });
           
           // Bouton EDAP
           buttonGroup.querySelector('.btn-edap').addEventListener('click', () => {
               const dialog = document.createElement('dialog');
               dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4';
               
               dialog.innerHTML = `
                   <div class="bg-white rounded-lg p-6 max-w-md w-full">
                       <h2 class="text-xl font-bold mb-4">EDAP - Équipe ${equipe}</h2>
                       <div class="mb-4">
                           <label class="block mb-2">Sélectionner le joueur :</label>
                           <select id="playerSelect" class="w-full p-2 border rounded">
                           </select>
                       </div>
                       <div class="flex justify-end space-x-2">
                           <button class="px-4 py-2 bg-gray-200 rounded" onclick="this.closest('dialog').close()">Annuler</button>
                           <button class="px-4 py-2 bg-red-500 text-white rounded" id="confirmBtn">Confirmer</button>
                       </div>
                   </div>
               `;

               document.body.appendChild(dialog);
               dialog.showModal();

               const playerSelect = dialog.querySelector('#playerSelect');
               const inputs = document.querySelectorAll(`#${equipe === 1 ? 'leftPlayersList' : 'rightPlayersList'} input.player-name`);
               
               inputs.forEach((input, index) => {
                   if (input.value.trim()) {
                       const option = document.createElement('option');
                       option.value = index + 1;
                       option.textContent = `${index + 1} - ${input.value}`;
                       playerSelect.appendChild(option);
                   }
               });

               dialog.querySelector('#confirmBtn').addEventListener('click', () => {
                   const playerNumber = parseInt(playerSelect.value);
                   handleEDAP(equipe, playerNumber);
                   dialog.close();
               });

               dialog.addEventListener('close', () => {
                   document.body.removeChild(dialog);
               });
           });
       });
   }



   function updatePlayerIndicators(equipe, playerNumber, type, count = null) {
       console.log('updatePlayerIndicators called:', equipe, playerNumber, type, count);
       
       // Utiliser les attributs data pour trouver la ligne du joueur de manière fiable
       const playerControls = document.querySelector(`[data-player="${playerNumber}"][data-team="${equipe}"]`);
       if (!playerControls) {
           console.log('Player controls not found for player', playerNumber, 'team', equipe);
           return;
       }
       
       // Le parent direct des player-controls est la ligne du joueur
       const playerRow = playerControls.parentElement;
       if (!playerRow) {
           console.log('Player row not found');
           return;
       }
       
       console.log('Found playerRow for player', playerNumber, 'team', equipe, playerRow);

       // Initialiser ou récupérer les données du joueur
       const teamKey = equipe === 1 ? 'team1' : 'team2';
       if (!window.playerData) window.playerData = {};
       if (!window.playerData[teamKey]) window.playerData[teamKey] = {};
       if (!window.playerData[teamKey][playerNumber]) {
           window.playerData[teamKey][playerNumber] = {
               yellowCards: 0,
               penalties: 0,
               exclusions: 0,
               isDefinitivelyExcluded: false
           };
       }

       // Mettre à jour les données selon le type
       const playerData = window.playerData[teamKey][playerNumber];
       if (type === 'penalty') {
           playerData.penalties = count;
       } else if (type === 'yellowCard') {
           playerData.yellowCards = count;
           if (count >= 3) {
               playerData.isDefinitivelyExcluded = true;
           }
       } else if (type === 'exclusion') {
           playerData.exclusions = count;
           if (count >= 3) {
               playerData.isDefinitivelyExcluded = true;
           }
       }

       // Créer ou récupérer le conteneur de badges
       let badgesContainer = playerRow.querySelector('.player-badges');
       if (!badgesContainer) {
           badgesContainer = document.createElement('div');
           badgesContainer.className = 'player-badges flex space-x-1';
           
           if (equipe === 1) {
               // Pour l'équipe 1, insérer après le nom du joueur
               const playerName = playerRow.querySelector('.player-name');
               if (playerName) {
                   playerName.insertAdjacentElement('afterend', badgesContainer);
               }
           } else {
               // Pour l'équipe 2, insérer au tout début de la ligne (avant le numéro)
               playerRow.insertBefore(badgesContainer, playerRow.firstChild);
           }
       }

       // Vider le conteneur
       badgesContainer.innerHTML = '';

       // Créer les badges selon les données
       const badges = [];

       // Badge carton jaune
       if (playerData.yellowCards > 0) {
           const badge = document.createElement('div');
           badge.className = 'player-badge px-1.5 py-0.5 rounded text-xs font-bold';
           badge.textContent = `CJ${playerData.yellowCards}`;
           
           // Couleur selon le nombre
           if (playerData.yellowCards === 1) {
               badge.classList.add('bg-yellow-400', 'text-black');
           } else if (playerData.yellowCards === 2) {
               badge.classList.add('bg-orange-500', 'text-white');
           } else {
               badge.classList.add('bg-red-600', 'text-white');
           }
           
           badge.title = `${playerData.yellowCards} carton${playerData.yellowCards > 1 ? 's' : ''} jaune${playerData.yellowCards > 1 ? 's' : ''}`;
           badges.push(badge);
       }

       // Badge exclusion
       if (playerData.exclusions > 0) {
           const badge = document.createElement('div');
           badge.className = 'player-badge px-1.5 py-0.5 rounded text-xs font-bold';
           badge.textContent = `E${playerData.exclusions}`;
           
           // Couleur selon le nombre
           if (playerData.exclusions === 1) {
               badge.classList.add('bg-gray-500', 'text-white');
           } else if (playerData.exclusions === 2) {
               badge.classList.add('bg-orange-600', 'text-white');
           } else {
               badge.classList.add('bg-red-700', 'text-white');
           }
           
           badge.title = `${playerData.exclusions} exclusion${playerData.exclusions > 1 ? 's' : ''}`;
           badges.push(badge);
       }

       // Badge penalty
       if (playerData.penalties > 0) {
           const badge = document.createElement('div');
           badge.className = 'player-badge px-1.5 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white';
           badge.textContent = `P${playerData.penalties}`;
           badge.title = `${playerData.penalties} penalt${playerData.penalties > 1 ? 'ies' : 'y'}`;
           badges.push(badge);
       }

       // Ajouter les badges au conteneur
       badges.forEach(badge => {
           badgesContainer.appendChild(badge);
       });
       
       // Debug
       console.log(`Tentative création badges pour joueur ${playerNumber} équipe ${equipe}`);
       console.log(`PlayerData:`, playerData);
       console.log(`Badges créés:`, badges.length);
       console.log(`BadgesContainer:`, badgesContainer);
       
       if (badges.length > 0) {
           console.log(`✅ ${badges.length} badge(s) ajouté(s) pour joueur ${playerNumber} équipe ${equipe}`);
       } else {
           console.log(`❌ Aucun badge créé pour joueur ${playerNumber} équipe ${equipe}`);
       }

       // Gestion de l'exclusion définitive (nom barré)
       const playerNameInput = playerRow.querySelector('.player-name');
       if (playerData.isDefinitivelyExcluded) {
           if (playerNameInput) {
               playerNameInput.style.textDecoration = 'line-through';
               playerNameInput.style.color = '#888';
               playerNameInput.style.backgroundColor = '#f3f4f6';
               playerNameInput.title = 'Joueur exclu définitivement';
           }
           
           // Désactiver les boutons
           const buttons = playerRow.querySelectorAll('button');
           buttons.forEach(button => {
               button.disabled = true;
               button.classList.add('opacity-50');
           });
       } else {
           // Rétablir l'apparence normale si nécessaire
           if (playerNameInput) {
               playerNameInput.style.textDecoration = '';
               playerNameInput.style.color = '';
               playerNameInput.style.backgroundColor = '';
               playerNameInput.title = '';
           }
           
           // Réactiver les boutons
           const buttons = playerRow.querySelectorAll('button');
           buttons.forEach(button => {
               button.disabled = false;
               button.classList.remove('opacity-50');
           });
       }
   }



   function startPossessionTimer(duration, mode = 'normal') {
       gameState.possessionMode = mode;
       gameState.possessionTimer = duration;
       gameState.isPossessionRunning = true;
       updateDisplay();
   }



   function showTechnicalTimeout() {
       const notification = document.createElement('div');
       notification.className = 'technical-timeout fixed top-4 right-4 z-50';
       notification.textContent = 'TEMPS MORT TECHNIQUE';
       document.body.appendChild(notification);
       
       playLongBeep();
       
       // Supprimer la notification après 5 secondes
       setTimeout(() => {
           notification.remove();
       }, 5000);
   }

   function playBeep() {
       const beep = new Audio('sounds/beep-short.mp3');
       beep.play().catch(error => {
           console.log('Son non disponible - signal visuel uniquement');
           // Fallback visuel si le son n'est pas disponible
           const flash = document.createElement('div');
           flash.style.position = 'fixed';
           flash.style.top = '0';
           flash.style.left = '0';
           flash.style.right = '0';
           flash.style.height = '5px';
           flash.style.backgroundColor = '#dc2626';
           flash.style.zIndex = '9999';
           document.body.appendChild(flash);
           setTimeout(() => flash.remove(), 200);
       });
   }

   function playLongBeep() {
       const beep = new Audio('sounds/beep-long.mp3');
       beep.play().catch(error => {
           console.log('Son non disponible - signal visuel uniquement');
           // Fallback visuel si le son n'est pas disponible
           const flash = document.createElement('div');
           flash.style.position = 'fixed';
           flash.style.top = '0';
           flash.style.left = '0';
           flash.style.right = '0';
           flash.style.height = '10px';
           flash.style.backgroundColor = '#059669';
           flash.style.zIndex = '9999';
           document.body.appendChild(flash);
           setTimeout(() => flash.remove(), 1000);
       });
   }

   // Gestion des tooltips sur mobile
   document.addEventListener('DOMContentLoaded', function() {
       // Détection du support tactile
       const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
       
       if (isTouchDevice) {
           // Sur les appareils tactiles, montrer le tooltip au premier tap et exécuter l'action au second tap
           document.querySelectorAll('[title]').forEach(element => {
               let tapped = false;
               let tapTimeout;
               
               element.addEventListener('click', function(e) {
                   if (!tapped) {
                       // Premier tap : montrer le tooltip
                       e.preventDefault();
                       const tooltip = document.createElement('div');
                       tooltip.className = 'mobile-tooltip';
                       tooltip.textContent = this.getAttribute('title');
                       tooltip.style.cssText = `
                           position: fixed;
                           background: #1F2937;
                           color: white;
                           padding: 8px 12px;
                           border-radius: 4px;
                           font-size: 14px;
                           z-index: 1000;
                           max-width: 80vw;
                           text-align: center;
                       `;
                       
                       document.body.appendChild(tooltip);
                       
                       // Positionner le tooltip au-dessus du bouton
                       const rect = this.getBoundingClientRect();
                       tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
                       tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
                       
                       // Marquer comme tappé
                       tapped = true;
                       
                       // Supprimer le tooltip après 1.5 secondes
                       tapTimeout = setTimeout(() => {
                           tooltip.remove();
                           tapped = false;
                       }, 1500);
                   } else {
                       // Second tap : exécuter l'action normale du bouton
                       clearTimeout(tapTimeout);
                       tapped = false;
                       const tooltips = document.querySelectorAll('.mobile-tooltip');
                       tooltips.forEach(t => t.remove());
                   }
               });
           });
       }
   });

   // Fonction pour charger les équipes dans le menu déroulant
   function chargerEquipes() {
       const equipes = [
           { id: 1, nom: 'Lion', logo: 'images/Lion.jpg' },
           { id: 2, nom: 'Aigle royal', logo: 'images/logo_Royal.png' },
           { id: 3, nom: 'Team Panther', logo: 'images/team_panther.jpg' },
           { id: 4, nom: 'Wolf', logo: 'images/wolf.jpg' }
       ];

       const select = document.getElementById('team2Select');
       if (!select) {
           console.error('Élément select non trouvé');
           return;
       }

       select.innerHTML = '<option value="">Sélectionner une équipe</option>';
       
       equipes.forEach(equipe => {
           const option = document.createElement('option');
           option.value = equipe.id;
           option.textContent = equipe.nom;
           option.dataset.logo = equipe.logo;
           select.appendChild(option);
       });

       // Créer un conteneur pour le select et l'input
       const container = document.createElement('div');
       container.className = 'team-select-container';
       select.parentNode.insertBefore(container, select);
       container.appendChild(select);

       // Créer l'input qui sera caché initialement
       const input = document.createElement('input');
       input.type = 'text';
       input.readOnly = true;
       input.className = 'team-name-input team2-color';
       input.maxLength = 15;
       input.placeholder = 'NOM EQUIPE 2';
       input.spellcheck = false;
       input.autocomplete = 'off';
       input.style.display = 'none';
       container.appendChild(input);

       // Créer le bouton de modification qui sera caché initialement
       const editButton = document.createElement('button');
       editButton.innerHTML = '✎';
       editButton.className = 'edit-team-button';
       editButton.style.display = 'none';
       editButton.style.marginLeft = 'auto'; // Pousse le bouton à droite
       editButton.style.padding = '4px 8px';
       editButton.style.border = 'none';
       editButton.style.borderRadius = '4px';
       editButton.style.backgroundColor = '#f3f4f6';
       editButton.style.cursor = 'pointer';
       container.appendChild(editButton);

       // Modifier le style du conteneur pour utiliser flexbox
       container.style.display = 'flex';
       container.style.alignItems = 'center';
       container.style.gap = '8px';

       // Ajouter un écouteur d'événement pour le changement d'équipe
       select.addEventListener('change', function() {
           const selectedOption = this.options[this.selectedIndex];
           if (selectedOption.value) {
               // Afficher l'input et le bouton, cacher le select
               input.value = selectedOption.textContent;
               input.style.display = 'block';
               editButton.style.display = 'inline-block';
               this.style.display = 'none';
               
               // Mettre à jour le logo si nécessaire
               const logo = document.getElementById('team2Logo');
               if (logo && selectedOption.dataset.logo) {
                   logo.src = selectedOption.dataset.logo;
               }
           }
       });

       // Ajouter un écouteur d'événement pour le bouton de modification
       editButton.addEventListener('click', function() {
           // Afficher le select, cacher l'input et le bouton
           select.style.display = 'block';
           input.style.display = 'none';
           this.style.display = 'none';
       });

       console.log('✅ Menu déroulant mis à jour avec succès');
   }

   // Charger les équipes au démarrage
   document.addEventListener('DOMContentLoaded', () => {
       console.log('DOM chargé, initialisation du chargement des équipes...');
       chargerEquipes();
   });

   // Fonction pour charger les joueurs d'une équipe
   function chargerJoueurs(equipe, idEquipe) {
       console.log(`Chargement des joueurs pour l'équipe ${equipe} (ID: ${idEquipe})`);
       fetch(`recuperer_joueurs.php?equipe1=${idEquipe}&equipe2=${idEquipe}`)
           .then(response => response.json())
           .then(data => {
               console.log('Données reçues:', data);
               const joueurs = equipe === 1 ? data.equipe1 : data.equipe2;
               console.log('Joueurs sélectionnés:', joueurs);

               // Sélectionner tous les inputs de noms de joueurs pour cette équipe
               const inputs = document.querySelectorAll(`input[aria-label^="Nom du joueur"][aria-label$="équipe ${equipe}"]`);

               inputs.forEach((input, index) => {
                   if (joueurs[index]) {
                       input.value = joueurs[index]?.prenom_joueur || '';
                   } else {
                       input.value = '';
                   }

                   // Ajout de l'indicateur "dans l'eau" et du bouton de remplacement si pas déjà présent
                   let indicator = input.parentNode.querySelector('.in-water-indicator');
                   let replaceBtn = input.parentNode.querySelector('.replace-inwater-btn');
                   if (!indicator) {
                       indicator = document.createElement('div');
                       indicator.className = 'in-water-indicator';
                       indicator.style.width = '12px';
                       indicator.style.height = '12px';
                       indicator.style.borderRadius = '50%';
                       indicator.style.display = 'inline-block';
                       indicator.style.marginLeft = equipe === 1 ? '0' : '6px';
                       indicator.style.marginRight = equipe === 1 ? '6px' : '0';
                       indicator.style.backgroundColor = '#10B981'; // Vert = dans l'eau
                       indicator.title = "Dans l'eau";
                   }
                   if (!replaceBtn) {
                       console.log('Création du bouton de remplacement pour', input.value);
                       replaceBtn = document.createElement('button');
                       replaceBtn.type = 'button';
                       replaceBtn.className = 'replace-inwater-btn';
                       replaceBtn.textContent = '🔄';
                       replaceBtn.style.marginLeft = equipe === 1 ? '0' : '4px';
                       replaceBtn.style.marginRight = equipe === 1 ? '4px' : '0';
                       replaceBtn.style.background = '#f3f4f6';
                       replaceBtn.style.border = 'none';
                       replaceBtn.style.borderRadius = '4px';
                       replaceBtn.style.cursor = 'pointer';
                       replaceBtn.title = "Changer l'état dans l'eau";
                       console.log('Attachement de l\'événement click sur le bouton');
                       replaceBtn.addEventListener('click', function() {
                           const isInWater = indicator.style.backgroundColor === 'rgb(16, 185, 129)';
                           indicator.style.backgroundColor = isInWater ? '#EF4444' : '#10B981';
                           indicator.title = isInWater ? "Hors de l'eau" : "Dans l'eau";
                           // Ajout dans le journal avec le temps du chrono principal
                           const action = isInWater ? 'sorti' : 'rentré';
                           const joueurNom = input.value;
                           const equipeNom = getTeamName(equipe);
                           
                           // Récupération du temps du chrono avec une valeur par défaut
                           const mainTimer = document.getElementById('timer');
                           const chronoTemps = mainTimer ? mainTimer.textContent : '00:00';
                           
                           console.log('Ajout dans le journal:', `[${chronoTemps}] ${joueurNom} (${equipeNom}) est ${action}`);
                           addLogEntry(`[${chronoTemps}] ${joueurNom} (${equipeNom}) est ${action}`, 'text-blue-600');
                           
                           // Enregistrement en base de données avec le temps du chrono
                           enregistrerEvenement({
                               id_match: window.idMatchActuel || 1,
                               id_joueur: joueurs[index]?.id_joueur || null,
                               id_equipe: equipe,
                               type_evenement: 'remplacement',
                               details: action,
                               temps_chrono: chronoTemps
                           });
                       });
                   }
                   // Insérer à gauche pour équipe 1, à droite pour équipe 2
                   if (equipe === 1) {
                       if (input.previousSibling !== replaceBtn) {
                           input.parentNode.insertBefore(replaceBtn, input);
                       }
                       if (input.previousSibling !== indicator) {
                           input.parentNode.insertBefore(indicator, input);
                       }
                   } else {
                       if (input.nextSibling !== indicator) {
                           input.parentNode.insertBefore(indicator, input.nextSibling);
                       }
                       if (indicator.nextSibling !== replaceBtn) {
                           input.parentNode.insertBefore(replaceBtn, indicator.nextSibling);
                       }
                   }
               });

               console.log(`Joueurs de l'équipe ${equipe} chargés avec succès`);
               if (equipe === 1) {
                   window.lastJoueursEquipe1 = joueurs;
               } else {
                   window.lastJoueursEquipe2 = joueurs;
               }
           })
           .catch(error => {
               console.error('Erreur complète:', error);
           });
   }

   // Fonction pour mettre à jour le logo de l'équipe
   function updateTeamLogo(teamId, logoPath) {
       const logoImg = document.getElementById(teamId === 1 ? 'team1Logo' : 'team2Logo');
       if (logoImg) {
           logoImg.src = logoPath;
       }
   }

   // Écouter le changement d'équipe dans le menu déroulant
   document.getElementById('team2Select').addEventListener('change', function() {
       const selectedOption = this.options[this.selectedIndex];
       const selectedId = this.value;
       if (selectedId) {
           // Mettre à jour le logo
           const logoPath = selectedOption.dataset.logo;
           updateTeamLogo(2, logoPath);
           // Charger les joueurs
           chargerJoueurs(2, selectedId);
       }
   });

   // Charger les joueurs au démarrage
   document.addEventListener('DOMContentLoaded', () => {
       // Charger les joueurs de l'équipe 1 (TNC)
       chargerJoueurs(1, 1);
       // Charger les joueurs de l'équipe 2 (première équipe par défaut)
       chargerJoueurs(2, 2);
   });

   function enregistrerEvenement(evenement) {
       console.log('Envoi événement:', evenement); // Log pour débogage
       fetch('enregistrer_evenement.php', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify(evenement)
       })
       .then(res => res.json())
       .then(data => {
           console.log('Réponse enregistrer_evenement.php:', data); // Log pour débogage
           if (!data.success) {
               console.error('Erreur enregistrement événement:', data.error);
           }
       })
       .catch(err => console.error('Erreur réseau:', err));
   }

function enregistrerBut(but) {
    console.log('Envoi but:', but); // Log pour débogage
    fetch('enregistrer_but.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(but)
    })
    .then(res => res.json())
    .then(data => {
        console.log('Réponse enregistrer_but.php:', data); // Log pour débogage
        if (!data.success) {
            console.error('Erreur enregistrement but:', data.error);
        }
    })
    .catch(err => console.error('Erreur réseau:', err));
}

  </script>
</body>
</html>

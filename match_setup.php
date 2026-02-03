<?php
require_once __DIR__ . '/db.php';
$pdo = getPDO();

// Optionnel : charger valeurs par défaut si tu veux pré-remplir depuis la DB
// Exemple : récupérer les équipes par défaut
$teams = [];
try {
    $stmt = $pdo->query("SELECT id_equipe, nom FROM equipe ORDER BY id_equipe");
    $teams = $stmt->fetchAll();
} catch (Exception $e) {
    // ignore, on garde les valeurs vides si erreur
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Paramétrage du match</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    /* Styles minimal pour rendre la page lisible */
    body { font-family: Arial, Helvetica, sans-serif; padding: 20px; background:#f7fafc; color:#111827; }
    .container{max-width:900px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
    label{display:block;margin-top:10px;font-weight:600}
    input[type="text"], select { width:100%;padding:8px;margin-top:6px;border:1px solid #ddd;border-radius:4px }
    .teams { display:flex; gap:16px; }
    .team { flex:1; }
    .players { margin-top:10px; }
    .player-row { display:flex; gap:8px; margin-bottom:6px; }
    .player-row input { flex:1; }
    .btn { padding:8px 12px;border-radius:6px;border:0;background:#111827;color:white;cursor:pointer }
    .btn.secondary { background:#6b7280 }
    .inline { display:inline-block; vertical-align:middle }
  </style>
</head>
<body>
  <div class="container">
    <h1>Paramétrage du match</h1>
    <form id="matchForm">
      <div class="teams">
        <div class="team">
          <label for="team1Name">Nom équipe 1</label>
          <input id="team1Name" name="team1Name" type="text" maxlength="120" placeholder="Équipe 1" value="<?= isset($teams[0]['nom']) ? htmlspecialchars($teams[0]['nom']) : 'Équipe 1' ?>" />
          <div class="players" id="team1Players">
            <label>Joueurs équipe 1</label>
            <!-- lignes players ajoutées par JS -->
          </div>
          <button type="button" class="btn secondary inline" onclick="addPlayer(1)">+ Ajouter un joueur</button>
        </div>

        <div class="team">
          <label for="team2Name">Nom équipe 2</label>
          <input id="team2Name" name="team2Name" type="text" maxlength="120" placeholder="Équipe 2" value="<?= isset($teams[1]['nom']) ? htmlspecialchars($teams[1]['nom']) : 'Équipe 2' ?>" />
          <div class="players" id="team2Players">
            <label>Joueurs équipe 2</label>
          </div>
          <button type="button" class="btn secondary inline" onclick="addPlayer(2)">+ Ajouter un joueur</button>
        </div>
      </div>

      <hr style="margin:18px 0" />

      <label>Règles du match</label>
      <div>
        <label>Durée d'une période (secondes)</label>
        <select id="periodTime" name="periodTime">
          <option value="480">8 min (480s) — standard</option>
          <option value="360">6 min (360s)</option>
          <option value="300">5 min (300s)</option>
          <option value="custom">Personnalisé</option>
        </select>
        <div id="customPeriod" style="display:none;margin-top:8px">
          <input id="customPeriodSeconds" type="number" min="60" step="30" placeholder="Durée en secondes (ex : 480)" />
        </div>

        <label style="margin-top:10px">Durée de possession (shot clock)</label>
        <select id="possessionTime" name="possessionTime">
          <option value="30">30s — normal</option>
          <option value="20">20s — spéciale</option>
        </select>

        <label style="margin-top:10px">Durée exclusion (secondes)</label>
        <input id="exclusionTime" name="exclusionTime" type="number" value="20" min="5" step="1" />

        <label style="margin-top:10px">Nombre de temps morts par équipe</label>
        <input id="maxTimeouts" name="maxTimeouts" type="number" value="2" min="0" max="5" />

      </div>

      <hr style="margin:18px 0" />

      <div style="display:flex; gap:12px; justify-content:flex-end">
        <button type="button" class="btn secondary" onclick="location.href='index.php'">Annuler</button>
        <button type="submit" class="btn">Démarrer le match</button>
      </div>
    </form>
  </div>

  <script>
    // Code léger pour la gestion du DOM (tu pourras extraire en fichier séparé)
    const teamPlayers = {1: [], 2: []};

    // Initialise 7 joueurs par équipe par défaut (modifiable)
    for (let i=0;i<7;i++) addPlayer(1, true);
    for (let i=0;i<7;i++) addPlayer(2, true);

    function addPlayer(team, silent=false) {
      const container = document.getElementById('team'+team+'Players');
      const idx = container.querySelectorAll('.player-row').length + 1;
      const div = document.createElement('div');
      div.className = 'player-row';
      div.innerHTML = `
        <input type="text" name="team${team}_player[]" maxlength="60" placeholder="Prénom Nom (n°${idx})" />
        <input type="number" name="team${team}_number[]" min="1" max="99" placeholder="N°" style="width:80px" />
        <button type="button" onclick="removePlayer(this)" class="btn secondary">×</button>
      `;
      container.appendChild(div);
      if (!silent) {
        // scroll to new input
        div.querySelector('input').focus();
      }
    }

    function removePlayer(btn) {
      const row = btn.closest('.player-row');
      if (row) row.remove();
    }

    document.getElementById('periodTime').addEventListener('change', function(){
      document.getElementById('customPeriod').style.display = this.value === 'custom' ? 'block' : 'none';
    });

    document.getElementById('matchForm').addEventListener('submit', function(e){
      e.preventDefault();
      const form = e.currentTarget;
      // construire payload
      const payload = {
        team1Name: document.getElementById('team1Name').value.trim(),
        team2Name: document.getElementById('team2Name').value.trim(),
        rules: {
          periodTime: (document.getElementById('periodTime').value === 'custom') ? parseInt(document.getElementById('customPeriodSeconds').value || 480,10) : parseInt(document.getElementById('periodTime').value,10),
          possessionTime: parseInt(document.getElementById('possessionTime').value,10),
          exclusionTime: parseInt(document.getElementById('exclusionTime').value,10),
          maxTimeouts: parseInt(document.getElementById('maxTimeouts').value,10)
        },
        team1Players: gatherPlayers(1),
        team2Players: gatherPlayers(2)
      };

      // Validation minimale
      if (!payload.team1Name || !payload.team2Name) {
        alert('Veuillez saisir les noms des deux équipes.');
        return;
      }
      if (payload.team1Players.length === 0 || payload.team2Players.length === 0) {
        alert('Ajoutez au moins un joueur par équipe.');
        return;
      }

      // Envoi via fetch
      fetch('save_match.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }).then(async res => {
        if (!res.ok) {
          const txt = await res.text();
          throw new Error('Erreur serveur: ' + res.status + ' ' + txt);
        }
        return res.json();
      }).then(data => {
        if (data.success) {
          // redirection vers la page principale pour commencer le match
          location.href = 'index.php';
        } else {
          alert('Erreur: ' + (data.error || 'inconnue'));
        }
      }).catch(err => {
        console.error(err);
        alert('Erreur lors de la sauvegarde du match. Voir la console.');
      });
    });

    function gatherPlayers(team) {
      const names = Array.from(document.querySelectorAll(`#team${team}Players input[name="team${team}_player[]"]`)).map(i => i.value.trim()).filter(v => v);
      const numbers = Array.from(document.querySelectorAll(`#team${team}Players input[name="team${team}_number[]"]`)).map(i => parseInt(i.value,10) || null);
      const players = [];
      for (let i=0;i<names.length;i++){
        players.push({name: names[i], number: numbers[i]});
      }
      return players;
    }
  </script>
</body>
</html>
<?php
require_once __DIR__ . '/db.php';
session_start();
$matchId = (int)($_SESSION['current_match_id'] ?? 0);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Scoreboard — Waterpolo</title>
  <style>
    /* Minimal, high-contrast, full-screen scoreboard */
    html,body{height:100%;margin:0;background:#0b1220;color:#fff;font-family:Inter,Arial,Helvetica,sans-serif}
    .container{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;width:100%}
    .teams{display:flex;align-items:stretch;justify-content:space-between;gap:20px;width:100%;max-width:1600px;padding:20px}
    .team{flex:1;background:rgba(255,255,255,0.03);padding:40px;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center}
    .team-name{font-size:48px;font-weight:700;letter-spacing:1px;margin-bottom:12px;text-align:center;color:#fff}
    .score{font-size:200px;line-height:0.9;font-weight:900;color:#fff}
    .meta{display:flex;gap:24px;align-items:center;margin-top:18px;font-size:28px}
    .meta .item{display:flex;flex-direction:column;align-items:center}
    .meta .label{font-size:14px;opacity:0.8}
    .meta .value{font-size:28px;font-weight:700}
    .center{display:flex;flex-direction:column;align-items:center;justify-content:center}
    .period{font-size:40px;font-weight:800;margin-bottom:10px}
    .small-note{opacity:0.85;font-size:14px;margin-top:12px}
    @media (max-width:700px){ .score{font-size:120px} .team-name{font-size:28px} }
    /* fullscreen friendly */
    :root{height:100%}
  </style>
</head>
<body>
  <div class="container">
    <div id="noMatchMessage" style="background:#fff3cd;color:#856404;padding:12px;border-radius:6px;margin-bottom:12px;display:none;">Aucun match configuré. Veuillez passer par <a href="match_setup.php" style="font-weight:700;">match_setup.php</a></div>
    <div class="teams">
      <div class="team" id="team1Pane">
        <div class="team-name" id="team1Name">Équipe 1</div>
        <div class="score" id="team1Score">0</div>
      </div>
      <div class="team" id="centerPane">
        <div class="period" id="periodDisplay">P1</div>
        <div class="meta">
          <div class="item"><div class="label">Temps</div><div class="value" id="mainTimer">8:00</div></div>
          <div class="item"><div class="label">Shot clock</div><div class="value" id="possessionTimer">30</div></div>
        </div>
        <div class="small-note">Aucune interaction — affichage public</div>
      </div>
      <div class="team" id="team2Pane">
        <div class="team-name" id="team2Name">Équipe 2</div>
        <div class="score" id="team2Score">0</div>
      </div>
    </div>
  </div>

  <script>
    const MATCH_ID = <?= $matchId ?> || 0;

    async function fetchState() {
      try {
        const url = 'api/get_game_state.php?matchId=' + MATCH_ID;
        const res = await fetch(url, {cache:'no-store'});
        if (!res.ok) return null;
        const data = await res.json();
        return data || null;
      } catch (e) { return null; }
    }

    function formatTime(seconds) {
      const mins = Math.floor(seconds/60);
      const secs = seconds % 60;
      return `${mins}:${String(secs).padStart(2,'0')}`;
    }

    let last = null;
    async function update() {
      const s = await fetchState();
      if (!s) return;
      // When API returns no match configured explicitly, show a clear message and hide match panes
      if (s.matchId === null) {
        document.getElementById('noMatchMessage').style.display = 'block';
        document.querySelector('.teams').style.display = 'none';
        return;
      } else {
        document.getElementById('noMatchMessage').style.display = 'none';
        document.querySelector('.teams').style.display = 'flex';
      }

      const state = s.state || s;
      const key = JSON.stringify(state);
      if (key === last) return; last = key;

      document.getElementById('team1Score').textContent = state.scoreTeam1;
      document.getElementById('team2Score').textContent = state.scoreTeam2;
      document.getElementById('mainTimer').textContent = formatTime(state.mainTimer);
      document.getElementById('possessionTimer').textContent = String(state.possessionTimer);
      document.getElementById('periodDisplay').textContent = 'P' + (state.period || 1);
      // set team names when available
      if (state.team1Name) document.getElementById('team1Name').textContent = state.team1Name;
      if (state.team2Name) document.getElementById('team2Name').textContent = state.team2Name;
    }

    // Polling every 500ms for near real-time
    setInterval(update, 500);
    // initial
    update();
  </script>
</body>
</html>
<?php
// match_setup.php
// Page de paramétrage du match - amélioration UX, joueurs numérotés automatiquement
// Place : Code_écran_pc_match/match_setup.php

require_once __DIR__ . '/db.php';
$pdo = getPDO();
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Récupérer et consommer le message flash éventuel
$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}


// Charger des valeurs par défaut si disponibles (ex : équipes existantes)
$defaultTeam1 = 'Équipe A';
$defaultTeam2 = 'Équipe B';
try {
    $stmt = $pdo->query("SELECT id_equipe, nom FROM equipe ORDER BY id_equipe LIMIT 2");
    $rows = $stmt->fetchAll();
    if (isset($rows[0]['nom'])) $defaultTeam1 = $rows[0]['nom'];
    if (isset($rows[1]['nom'])) $defaultTeam2 = $rows[1]['nom'];
} catch (Exception $e) {
    // ignore, on utilisera les valeurs par défaut
}

// Valeurs par défaut pour les règles
$default = [
    'numberPeriods' => 4,
    'periodMinutes' => 8,
    'breakShortMinutes' => 2,
    'halftimeMinutes' => 5,
    'shotClock' => 30,       // 30s par défaut (compatible 28)
    'recoveryClock' => 20    // 20s par défaut (possibilité 18)
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Paramétrage du match — Waterpolo</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">


  <link rel="stylesheet" href="assets/css/match_setup.css" />
</head>
<body>
  <main class="page">
    <header class="header">
      <h1>Paramétrage du match</h1>
      <p class="subtitle">Configure les équipes, les joueurs (numéros fixes) et les règles avant de lancer le match</p>
    </header>

    <?php if ($flash): ?>
      <?php
        $bg = $flash['type'] === 'success' ? '#D1FAE5' : ($flash['type'] === 'error' ? '#FEE2E2' : '#FEF3C7');
        $color = $flash['type'] === 'success' ? '#065F46' : ($flash['type'] === 'error' ? '#991B1B' : '#92400E');
      ?>
      <div role="alert" style="background: <?= $bg ?>; color: <?= $color ?>; padding: 12px 16px; border-radius: 6px; margin: 12px 0; border: 1px solid rgba(0,0,0,0.04);">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        <?php if ($flash['type'] === 'success'): ?>
          <a href="index.php" style="margin-left:12px; padding:6px 10px; background:#065F46; color:white; text-decoration:none; border-radius:4px;">Aller à l'interface d'arbitrage</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form id="matchForm" class="form" autocomplete="off" novalidate> 
      <section class="panel teams-panel">
        <div class="team-card">
          <h2>Équipe A</h2>
          <label class="label">Nom de l'équipe</label>
          <input id="team1Name" name="team1Name" type="text" maxlength="120" class="input" value="<?= htmlspecialchars($defaultTeam1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required />
          <div class="players-section">
            <h3>Composition (numéros fixes)</h3>
            <div id="team1Players" class="players-table" aria-label="Joueurs équipe A"></div>
            <div class="players-actions">
              <button type="button" class="btn secondary" data-team="1" id="team1Add">+ Ajouter un numéro</button>
              <button type="button" class="btn secondary" data-team="1" id="team1Remove">− Retirer numéro</button>
            </div>
          </div>
        </div>

        <div class="team-card">
          <h2>Équipe B</h2>
          <label class="label">Nom de l'équipe</label>
          <input id="team2Name" name="team2Name" type="text" maxlength="120" class="input" value="<?= htmlspecialchars($defaultTeam2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required />
          <div class="players-section">
            <h3>Composition (numéros fixes)</h3>
            <div id="team2Players" class="players-table" aria-label="Joueurs équipe B"></div>
            <div class="players-actions">
              <button type="button" class="btn secondary" data-team="2" id="team2Add">+ Ajouter un numéro</button>
              <button type="button" class="btn secondary" data-team="2" id="team2Remove">− Retirer numéro</button>
            </div>
          </div>
        </div>
      </section>

      <section class="panel rules-panel">
        <h2>Règles du match</h2>
        <div class="grid">
          <div>
            <label class="label">Nombre de périodes</label>
            <input id="numberPeriods" type="number" min="1" max="10" class="input small" value="<?= $default['numberPeriods'] ?>" />
          </div>

          <div>
            <label class="label">Durée d'une période (minutes)</label>
            <input id="periodMinutes" type="number" min="1" max="30" class="input small" value="<?= $default['periodMinutes'] ?>" />
          </div>

          <div>
            <label class="label">Pause courte (entre 1/2 et 3/4) (minutes)</label>
            <input id="breakShortMinutes" type="number" min="0" max="30" class="input small" value="<?= $default['breakShortMinutes'] ?>" />
          </div>

          <div>
            <label class="label">Mi-temps (entre 2e et 3e) (minutes)</label>
            <input id="halftimeMinutes" type="number" min="0" max="60" class="input small" value="<?= $default['halftimeMinutes'] ?>" />
          </div>

          <div>
            <label class="label">Shot clock (secondes)</label>
            <select id="shotClock" class="input small">
              <option value="30" <?= $default['shotClock'] == 30 ? 'selected' : '' ?>>30s (standard)</option>
              <option value="28" <?= $default['shotClock'] == 28 ? 'selected' : '' ?>>28s</option>
              <option value="custom">Personnalisé</option>
            </select>
            <input id="shotClockCustom" type="number" min="5" max="120" class="input small hidden" placeholder="secondes (ex: 30)" />
          </div>

          <div>
            <label class="label">Chronomètre de reprise (secondes)</label>
            <select id="recoveryClock" class="input small">
              <option value="20" <?= $default['recoveryClock'] == 20 ? 'selected' : '' ?>>20s</option>
              <option value="18" <?= $default['recoveryClock'] == 18 ? 'selected' : '' ?>>18s</option>
              <option value="custom">Personnalisé</option>
            </select>
            <input id="recoveryClockCustom" type="number" min="5" max="120" class="input small hidden" placeholder="secondes (ex: 20)" />
          </div>
        </div>

        <p class="note">Les valeurs sont modifiables pour s'adapter à d'éventuelles évolutions des règles.</p>
      </section>

      <section class="panel actions-panel">
        <div class="actions-right">
          <a href="index.php" class="btn link secondary">Annuler</a>
          <button type="submit" class="btn primary" id="startMatchBtn">Démarrer le match</button>
        </div>
      </section>
    </form>
  </main>

  <script>
    // Passer des valeurs PHP à JS initiales
    const INITIAL = {
      defaultPlayers: 15,
      team1Name: <?= json_encode($defaultTeam1) ?>,
      team2Name: <?= json_encode($defaultTeam2) ?>,
      defaults: {
        numberPeriods: <?= (int)$default['numberPeriods'] ?>,
        periodMinutes: <?= (int)$default['periodMinutes'] ?>,
        breakShortMinutes: <?= (int)$default['breakShortMinutes'] ?>,
        halftimeMinutes: <?= (int)$default['halftimeMinutes'] ?>,
        shotClock: <?= (int)$default['shotClock'] ?>,
        recoveryClock: <?= (int)$default['recoveryClock'] ?>
      }
    };
  </script>
  <script src="assets/js/match_setup.js" defer></script>
</body>
</html>
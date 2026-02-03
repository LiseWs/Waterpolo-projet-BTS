// match_setup.js
// Gère la construction dynamique des tableaux de joueurs (numéros fixes),
// la logique d'ajout/suppression de numéro et la soumission du formulaire.

document.addEventListener('DOMContentLoaded', () => {
  const defaultCount = INITIAL.defaultPlayers || 15;

  // Elements
  const team1Container = document.getElementById('team1Players');
  const team2Container = document.getElementById('team2Players');
  const team1Add = document.getElementById('team1Add');
  const team2Add = document.getElementById('team2Add');
  const team1Remove = document.getElementById('team1Remove');
  const team2Remove = document.getElementById('team2Remove');
  const form = document.getElementById('matchForm');

  // State : arrays of numbers currently present
  const state = {
    team1: [],
    team2: []
  };

  // Initial population 1..defaultCount
  for (let i=1;i<=defaultCount;i++){ addPlayerRow(1,i); addPlayerRow(2,i); }

  // Event handlers
  team1Add.addEventListener('click', () => addNext(1));
  team2Add.addEventListener('click', () => addNext(2));
  team1Remove.addEventListener('click', () => removeLast(1));
  team2Remove.addEventListener('click', () => removeLast(2));

  // Shot clock custom toggle
  const shotClock = document.getElementById('shotClock');
  const shotClockCustom = document.getElementById('shotClockCustom');
  const recoveryClock = document.getElementById('recoveryClock');
  const recoveryClockCustom = document.getElementById('recoveryClockCustom');

  shotClock.addEventListener('change', () => {
    shotClockCustom.classList.toggle('hidden', shotClock.value !== 'custom');
  });
  recoveryClock.addEventListener('change', () => {
    recoveryClockCustom.classList.toggle('hidden', recoveryClock.value !== 'custom');
  });

  function addPlayerRow(team, number, prefill = {}) {
    const container = team === 1 ? team1Container : team2Container;
    // create row
    const row = document.createElement('div');
    row.className = 'player-row';
    row.dataset.number = number;

    row.innerHTML = `
      <div class="num" aria-hidden="true">${number}</div>
      <input class="input fullName" type="text" placeholder="Prénom Nom" value="${escapeHtml(prefill.name||'')}" />
      <input class="input license" type="text" placeholder="N° licence (optionnel)" value="${escapeHtml(prefill.license||'')}" />
      <button type="button" class="btn secondary remove-inline" title="Effacer" aria-label="Effacer">✕</button>
    `;
    container.appendChild(row);

    // local state
    state['team'+team].push(number);
    // remove inline button only clears the inputs (we keep the number reference)
    row.querySelector('.remove-inline').addEventListener('click', () => {
      row.querySelector('.fullName').value = '';
      row.querySelector('.license').value = '';
      row.querySelector('.fullName').focus();
    });
  }

  function addNext(team) {
    const arr = state['team'+team];
    const next = arr.length ? Math.max(...arr) + 1 : 1;
    addPlayerRow(team, next);
    // scroll into view
    const container = team === 1 ? team1Container : team2Container;
    container.lastElementChild.scrollIntoView({behavior:'smooth', block:'center'});
  }

  function removeLast(team) {
    const arr = state['team'+team];
    if (!arr.length) return;
    // remove DOM row for the highest number
    const max = Math.max(...arr);
    const container = team === 1 ? team1Container : team2Container;
    const row = container.querySelector(`.player-row[data-number="${max}"]`);
    if (row) row.remove();
    // update state
    state['team'+team] = arr.filter(n => n !== max);
  }

  function gatherPlayersFrom(container) {
    const rows = Array.from(container.querySelectorAll('.player-row'));
    const players = [];
    for (const r of rows) {
      const number = parseInt(r.dataset.number, 10);
      const fullName = (r.querySelector('.fullName').value || '').trim();
      const license = (r.querySelector('.license').value || '').trim();
      if (!fullName) {
        // skip empty names
        continue;
      }
      players.push({number, name: fullName, license: license || null});
    }
    return players;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    // validation
    const team1Name = document.getElementById('team1Name').value.trim();
    const team2Name = document.getElementById('team2Name').value.trim();
    if (!team1Name || !team2Name) {
      alert('Veuillez saisir les noms des deux équipes.');
      return;
    }

    const players1 = gatherPlayersFrom(team1Container);
    const players2 = gatherPlayersFrom(team2Container);
    if (players1.length === 0 || players2.length === 0) {
      alert('Au moins un joueur par équipe doit être renseigné.');
      return;
    }

    // Rules
    const periodMinutes = parseInt(document.getElementById('periodMinutes').value, 10) || INITIAL.defaults.periodMinutes;
    const numberPeriods = parseInt(document.getElementById('numberPeriods').value, 10) || INITIAL.defaults.numberPeriods;
    const breakShort = parseInt(document.getElementById('breakShortMinutes').value, 10) || INITIAL.defaults.breakShortMinutes;
    const halftime = parseInt(document.getElementById('halftimeMinutes').value, 10) || INITIAL.defaults.halftimeMinutes;
    const shotClockValue = document.getElementById('shotClock').value === 'custom' ? parseInt(document.getElementById('shotClockCustom').value,10) : parseInt(document.getElementById('shotClock').value,10);
    const recoveryClockValue = document.getElementById('recoveryClock').value === 'custom' ? parseInt(document.getElementById('recoveryClockCustom').value,10) : parseInt(document.getElementById('recoveryClock').value,10);

    const payload = {
      team1Name,
      team2Name,
      rules: {
        numberPeriods,
        periodMinutes,
        breakShortMinutes: breakShort,
        halftimeMinutes: halftime,
        shotClock: shotClockValue,
        recoveryClock: recoveryClockValue
      },
      team1Players: players1,
      team2Players: players2
    };

    // send to save_match.php
    try {
      const res = await fetch('save_match.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      if (!res.ok) {
        const text = await res.text();
        throw new Error('HTTP ' + res.status + ' — ' + text);
      }
      const data = await res.json();
      if (data.success) {
        // redirect to index (match started) and include match id to ensure server-side sync
        if (data.match_id) {
          window.location.href = 'index.php?matchId=' + encodeURIComponent(data.match_id);
        } else {
          window.location.href = 'index.php';
        }
      } else {
        alert('Erreur serveur : ' + (data.error || 'inconnue'));
      }
    } catch (err) {
      console.error(err);
      alert('Erreur lors de la sauvegarde du match — voir console.');
    }
  });

  // helper to escape injection into value attributes
  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
});
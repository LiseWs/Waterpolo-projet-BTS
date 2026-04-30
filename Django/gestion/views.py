import json
import os
from datetime import timedelta

from django.shortcuts import render, redirect, get_object_or_404
from django.http import JsonResponse, HttpResponse
from django.views.decorators.csrf import csrf_exempt
from django.utils import timezone

from .models import Match, Equipe, Joueur, Participation, Evenement, ScorePeriode


# ==========================================
# 1. ACCUEIL & GESTION ÉQUIPES
# ==========================================

def accueil(request):
    equipes_existantes = Equipe.objects.all()

    if request.method == 'POST':
        action = request.POST.get('action')

        if action == 'creer_equipe':
            Equipe.objects.create(nom=request.POST.get('nouveau_nom_equipe', '').strip())
            return redirect('accueil')

        elif action == 'supprimer_equipe':
            Equipe.objects.filter(id=request.POST.get('equipe_id')).delete()
            return redirect('accueil')

        elif action == 'lancer_match':
            # ── Équipes ──────────────────────────────────────────────────────
            id_dom = request.POST.get('select_domicile')
            nom_dom = request.POST.get('input_domicile', '').strip()
            eq_dom_obj = None
            if id_dom:
                eq_dom_obj = Equipe.objects.get(id=id_dom)
                nom_dom = eq_dom_obj.nom

            id_ext = request.POST.get('select_exterieur')
            nom_ext = request.POST.get('input_exterieur', '').strip()
            eq_ext_obj = None
            if id_ext:
                eq_ext_obj = Equipe.objects.get(id=id_ext)
                nom_ext = eq_ext_obj.nom

            duree_periode = int(request.POST.get('duree_periode', 8))

            match = Match.objects.create(
                nom_equipe_domicile=nom_dom,
                equipe_domicile_origine=eq_dom_obj,
                nom_equipe_exterieur=nom_ext,
                equipe_exterieur_origine=eq_ext_obj,
                lieu=request.POST.get('lieu', 'Piscine'),
                competition=request.POST.get('competition', ''),
                # Règles
                duree_periode=duree_periode,
                nb_periodes=int(request.POST.get('nb_periodes', 4)),
                temps_possession=int(request.POST.get('temps_possession', 30)),
                duree_exclusion=int(request.POST.get('duree_exclusion', 20)),
                nb_temps_morts=int(request.POST.get('nb_temps_morts', 2)),
                max_fautes_perso=int(request.POST.get('max_fautes_perso', 3)),
                nb_remplacants=int(request.POST.get('nb_remplacants', 6)),
                # Officiels
                arbitre1_nom=request.POST.get('arbitre1_nom', ''),
                arbitre1_iuf=request.POST.get('arbitre1_iuf', ''),
                arbitre2_nom=request.POST.get('arbitre2_nom', ''),
                arbitre2_iuf=request.POST.get('arbitre2_iuf', ''),
                secretaire_nom=request.POST.get('secretaire_nom', ''),
                secretaire_iuf=request.POST.get('secretaire_iuf', ''),
                chrono_nom=request.POST.get('chrono_nom', ''),
                chrono_iuf=request.POST.get('chrono_iuf', ''),
                juge_but1_nom=request.POST.get('juge_but1_nom', ''),
                juge_but1_iuf=request.POST.get('juge_but1_iuf', ''),
                juge_but2_nom=request.POST.get('juge_but2_nom', ''),
                juge_but2_iuf=request.POST.get('juge_but2_iuf', ''),
                delegue_ffn_nom=request.POST.get('delegue_ffn_nom', ''),
                delegue_ffn_iuf=request.POST.get('delegue_ffn_iuf', ''),
                delegue_dom_nom=request.POST.get('delegue_dom_nom', ''),
                delegue_ext_nom=request.POST.get('delegue_ext_nom', ''),
                # Chrono initial
                temps_restant=duree_periode * 60,
            )
            return redirect('compo_equipe', match_id=match.id)

    return render(request, 'gestion/accueil.html', {'equipes': equipes_existantes})


# ==========================================
# 2. COMPOSITION D'ÉQUIPE
# ==========================================

def _build_slots(equipe_origine):
    """
    Retourne une liste de 15 dicts (index 0 = bonnet 1, ..., 14 = bonnet 15).
    Compatible avec les templates Django natifs : pas de filtre personnalisé.
    """
    raw = {}
    if equipe_origine:
        for j in equipe_origine.effectif.all():
            if 0 < j.numero_habituel <= 15:
                raw[j.numero_habituel] = j
    slots = []
    for i in range(1, 16):
        j = raw.get(i)
        slots.append({
            'bonnet':          i,
            'nom':             j.nom             if j else '',
            'prenom':          j.prenom          if j else '',
            'numero_licence':  getattr(j, 'numero_licence', '')  if j else '',
            'annee_naissance': getattr(j, 'annee_naissance', '') if j else '',
        })
    return slots


def compo_equipe(request, match_id):
    match = get_object_or_404(Match, id=match_id)

    if request.method == 'POST':
        Participation.objects.filter(match=match).delete()

        for i in range(1, 16):
            nom_d = (request.POST.get(f'nom_dom_{i}') or '').strip()
            prenom_d = (request.POST.get(f'prenom_dom_{i}') or '').strip()
            if nom_d or prenom_d:
                Participation.objects.create(
                    match=match,
                    numero_bonnet=i,
                    equipe_concernee='DOM',
                    nom=nom_d.upper(),
                    prenom=prenom_d.capitalize(),
                    numero_licence=request.POST.get(f'licence_dom_{i}', ''),
                    annee_naissance=_parse_int(request.POST.get(f'naissance_dom_{i}')),
                )

            nom_e = (request.POST.get(f'nom_ext_{i}') or '').strip()
            prenom_e = (request.POST.get(f'prenom_ext_{i}') or '').strip()
            if nom_e or prenom_e:
                Participation.objects.create(
                    match=match,
                    numero_bonnet=i,
                    equipe_concernee='EXT',
                    nom=nom_e.upper(),
                    prenom=prenom_e.capitalize(),
                    numero_licence=request.POST.get(f'licence_ext_{i}', ''),
                    annee_naissance=_parse_int(request.POST.get(f'naissance_ext_{i}')),
                )

        # Sauvegarde staff
        match.entraineur_dom = request.POST.get('entraineur_dom', '')
        match.entraineur_adj_dom = request.POST.get('entraineur_adj_dom', '')
        match.suppleant_dom = request.POST.get('suppleant_dom', '')
        match.entraineur_ext = request.POST.get('entraineur_ext', '')
        match.entraineur_adj_ext = request.POST.get('entraineur_adj_ext', '')
        match.suppleant_ext = request.POST.get('suppleant_ext', '')

        # Mise à jour catalogue si demandé
        if request.POST.get('update_master_dom') == 'on' and match.equipe_domicile_origine:
            match.equipe_domicile_origine.effectif.all().delete()
            for i in range(1, 16):
                nom = (request.POST.get(f'nom_dom_{i}') or '').strip()
                prenom = (request.POST.get(f'prenom_dom_{i}') or '').strip()
                if nom:
                    Joueur.objects.create(
                        equipe=match.equipe_domicile_origine,
                        nom=nom, prenom=prenom, numero_habituel=i,
                        numero_licence=request.POST.get(f'licence_dom_{i}', ''),
                        annee_naissance=_parse_int(request.POST.get(f'naissance_dom_{i}')),
                    )

        if request.POST.get('update_master_ext') == 'on' and match.equipe_exterieur_origine:
            match.equipe_exterieur_origine.effectif.all().delete()
            for i in range(1, 16):
                nom = (request.POST.get(f'nom_ext_{i}') or '').strip()
                prenom = (request.POST.get(f'prenom_ext_{i}') or '').strip()
                if nom:
                    Joueur.objects.create(
                        equipe=match.equipe_exterieur_origine,
                        nom=nom, prenom=prenom, numero_habituel=i,
                        numero_licence=request.POST.get(f'licence_ext_{i}', ''),
                        annee_naissance=_parse_int(request.POST.get(f'naissance_ext_{i}')),
                    )

        match.compo_validee = True
        match.save()
        return redirect('dashboard_arbitre', match_id=match.id)

    return render(request, 'gestion/compo_equipe.html', {
        'match': match,
        'slots_dom': _build_slots(match.equipe_domicile_origine),
        'slots_ext': _build_slots(match.equipe_exterieur_origine),
    })


def _parse_int(value):
    """Retourne un entier ou None si la valeur est absente/invalide."""
    try:
        return int(value) if value and str(value).strip() else None
    except (ValueError, TypeError):
        return None


# ==========================================
# 3. DASHBOARD ARBITRE
# ==========================================

def dashboard_arbitre(request, match_id):
    match = get_object_or_404(Match, id=match_id)
    joueurs_dom = Participation.objects.filter(
        match=match, equipe_concernee='DOM').order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(
        match=match, equipe_concernee='EXT').order_by('numero_bonnet')
    events = Evenement.objects.filter(match=match).order_by('-heure_creation')
    return render(request, 'gestion/dashboard_arbitre.html', {
        'match': match,
        'joueurs_dom': joueurs_dom,
        'joueurs_ext': joueurs_ext,
        'events': events,
    })


# ==========================================
# 4. API MATCH (cerveau du chrono & actions)
# ==========================================

def _chrono_payload(match):
    """Bloc chrono renvoyé à chaque réponse API (source de vérité)."""
    now_ts = timezone.now().timestamp()
    started_at = (match.dernier_top_chrono.timestamp()
                  if match.dernier_top_chrono else now_ts)
    return {
        'running':     match.chrono_en_cours,
        'frozen_time': float(match.temps_restant),
        'started_at':  started_at,
        'server_ts':   now_ts,
    }


def _history_qs(match):
    return list(
        Evenement.objects
        .filter(match=match)
        .order_by('-heure_creation')[:20]
        .values(
            'id', 'type_action', 'chrono_match', 'equipe_attribuee', 'periode',
            'score_dom_apres', 'score_ext_apres',
            'joueur__nom', 'joueur__numero_bonnet',
        )
    )


@csrf_exempt
def api_match_action(request, match_id):
    if request.method != 'POST':
        return JsonResponse({'error': 'Method not allowed'}, status=405)

    try:
        data = json.loads(request.body)
    except json.JSONDecodeError:
        return JsonResponse({'error': 'Invalid JSON'}, status=400)

    match = get_object_or_404(Match, id=match_id)
    action = data.get('action')

    # ── Chrono ───────────────────────────────────────────────────────────────

    if action == 'start_timer':
        if not match.chrono_en_cours:
            match.chrono_en_cours = True
            match.dernier_top_chrono = timezone.now()
            match.save(update_fields=['chrono_en_cours', 'dernier_top_chrono'])
        return JsonResponse({'status': 'ok', 'chrono': _chrono_payload(match)})

    elif action == 'stop_timer':
        if match.chrono_en_cours:
            if match.dernier_top_chrono:
                delta = (timezone.now() - match.dernier_top_chrono).total_seconds()
                match.temps_restant = max(0, int(match.temps_restant - delta))
            match.chrono_en_cours = False
            match.dernier_top_chrono = None
            match.save(update_fields=['chrono_en_cours', 'temps_restant', 'dernier_top_chrono'])
        return JsonResponse({'status': 'ok', 'chrono': _chrono_payload(match)})

    elif action == 'adjust_time':
        delta = int(data.get('delta', 0))
        match.temps_restant = max(0, match.temps_restant + delta)
        match.save(update_fields=['temps_restant'])
        return JsonResponse({'status': 'ok', 'chrono': _chrono_payload(match)})

    elif action == 'reset_period':
        match.chrono_en_cours = False
        match.dernier_top_chrono = None
        match.temps_restant = match.duree_periode * 60
        match.save(update_fields=['chrono_en_cours', 'dernier_top_chrono', 'temps_restant'])
        return JsonResponse({'status': 'ok', 'chrono': _chrono_payload(match)})

    elif action == 'next_period':
        # Snapshot du score en fin de période
        ScorePeriode.objects.update_or_create(
            match=match,
            numero_periode=match.periode_actuelle,
            defaults={'score_dom': match.score_domicile,
                      'score_ext': match.score_exterieur},
        )
        match.chrono_en_cours = False
        match.dernier_top_chrono = None
        match.periode_actuelle += 1
        match.temps_restant = match.duree_periode * 60
        match.save(update_fields=[
            'chrono_en_cours', 'dernier_top_chrono',
            'periode_actuelle', 'temps_restant',
        ])
        return JsonResponse({
            'status': 'ok',
            'period': match.periode_actuelle,
            'chrono': _chrono_payload(match),
        })

    # ── Retour en jeu ─────────────────────────────────────────────────────────

    elif action == 'return_play':
        p_id = data.get('participation_id')
        joueur = get_object_or_404(Participation, id=p_id, match=match)
        joueur.est_exclu = False
        joueur.fin_exclusion = None
        joueur.save(update_fields=['est_exclu', 'fin_exclusion'])
        return JsonResponse({'status': 'ok', 'chrono': _chrono_payload(match)})

    # ── Actions de jeu ────────────────────────────────────────────────────────

    elif action in ('BUT', 'EXCL', 'EDA', 'PENALTY', 'TM', 'FAUTE'):
        p_id = data.get('participation_id')
        chrono_display = data.get('chrono_display', '00:00')

        evt_kwargs = dict(
            match=match,
            type_action=action,
            chrono_match=chrono_display,
            periode=match.periode_actuelle,
        )

        if action == 'TM':
            equipe = data.get('equipe')
            Evenement.objects.create(
                **evt_kwargs,
                equipe_attribuee=equipe,
                score_dom_apres=match.score_domicile,
                score_ext_apres=match.score_exterieur,
            )
        else:
            joueur = get_object_or_404(Participation, id=p_id, match=match)

            if action == 'BUT':
                if joueur.equipe_concernee == 'DOM':
                    match.score_domicile += 1
                else:
                    match.score_exterieur += 1

            elif action in ('FAUTE', 'PENALTY'):
                joueur.nb_fautes_personnelles += 1
                if joueur.nb_fautes_personnelles >= match.max_fautes_perso:
                    joueur.est_exclu_definitif = True
                joueur.save(update_fields=['nb_fautes_personnelles', 'est_exclu_definitif'])

            elif action == 'EXCL':
                joueur.nb_fautes_personnelles += 1
                joueur.est_exclu = True
                # Durée lue depuis le modèle (corrige le bug du hardcode 20s)
                joueur.fin_exclusion = (timezone.now()
                                        + timedelta(seconds=match.duree_exclusion))
                if joueur.nb_fautes_personnelles >= match.max_fautes_perso:
                    joueur.est_exclu_definitif = True
                    joueur.est_exclu = False  # définitif prend la priorité
                joueur.save(update_fields=[
                    'nb_fautes_personnelles', 'est_exclu',
                    'fin_exclusion', 'est_exclu_definitif',
                ])

            elif action == 'EDA':
                joueur.est_exclu_definitif = True
                joueur.est_exclu = False
                joueur.nb_fautes_personnelles += 1
                joueur.save(update_fields=[
                    'est_exclu_definitif', 'est_exclu', 'nb_fautes_personnelles'
                ])

            match.save(update_fields=['score_domicile', 'score_exterieur'])

            Evenement.objects.create(
                **evt_kwargs,
                joueur=joueur,
                equipe_attribuee=joueur.equipe_concernee,
                score_dom_apres=match.score_domicile,
                score_ext_apres=match.score_exterieur,
            )

    # ── Annulation ───────────────────────────────────────────────────────────

    elif action == 'delete_event':
        evt_id = data.get('event_id')
        evt = get_object_or_404(Evenement, id=evt_id, match=match)

        if evt.type_action == 'BUT':
            if evt.equipe_attribuee == 'DOM':
                match.score_domicile = max(0, match.score_domicile - 1)
            else:
                match.score_exterieur = max(0, match.score_exterieur - 1)
            match.save(update_fields=['score_domicile', 'score_exterieur'])

        elif evt.type_action in ('EXCL', 'EDA', 'PENALTY', 'FAUTE') and evt.joueur:
            j = evt.joueur
            j.nb_fautes_personnelles = max(0, j.nb_fautes_personnelles - 1)
            if evt.type_action == 'EXCL':
                j.est_exclu = False
                j.fin_exclusion = None
            if evt.type_action == 'EDA':
                j.est_exclu_definitif = False
            j.save()

        evt.delete()

    # ── Refresh (état courant sans action) ───────────────────────────────────
    # Géré par le fallback final (pas de clause elif nécessaire)

    return JsonResponse({
        'status': 'ok',
        'score_dom': match.score_domicile,
        'score_ext': match.score_exterieur,
        'period':    match.periode_actuelle,
        'history':   _history_qs(match),
        'chrono':    _chrono_payload(match),
    })


# ==========================================
# 5. SCOREBOARD PUBLIC
# ==========================================

def score_board(request, match_id):
    match = get_object_or_404(Match, id=match_id)
    joueurs_dom = Participation.objects.filter(
        match=match, equipe_concernee='DOM').order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(
        match=match, equipe_concernee='EXT').order_by('numero_bonnet')
    return render(request, 'gestion/score_board.html', {
        'match': match,
        'joueurs_dom': joueurs_dom,
        'joueurs_ext': joueurs_ext,
        'range_15': range(1, 16),
    })


@csrf_exempt
def api_match_state(request, match_id):
    """Endpoint de polling pour le scoreboard public."""
    match = get_object_or_404(Match, id=match_id)

    players_state = {}
    for p in Participation.objects.filter(match=match):
        if p.est_exclu_definitif:
            state, end_time = 'EDA', 0
        elif p.est_exclu and p.fin_exclusion:
            state = 'EXCL'
            end_time = p.fin_exclusion.timestamp()
        else:
            state, end_time = 'OK', 0
        players_state[p.id] = {
            'state': state,
            'end_time': end_time,
            'fautes': p.nb_fautes_personnelles,
        }

    return JsonResponse({
        'score_dom': match.score_domicile,
        'score_ext': match.score_exterieur,
        'periode':   match.periode_actuelle,
        'players':   players_state,
        'chrono':    _chrono_payload(match),
    })


# ==========================================
# 6. EXPORT EXCEL (Feuille de match FFN)
# ==========================================

def export_excel(request, match_id):
    """
    Génère et télécharge la feuille de match officielle FFN en .xlsx.
    On charge le template fourni et on remplit les cellules identifiées.

    Prérequis : installer openpyxl dans le virtualenv du projet :
        pip install openpyxl
    """
    import os

    try:
        from openpyxl import load_workbook
    except ImportError:
        return HttpResponse(
            "❌ Module manquant : openpyxl\n\n"
            "Installez-le dans votre virtualenv :\n"
            "    pip install openpyxl\n\n"
            "Puis redémarrez le serveur Django.",
            status=500,
            content_type='text/plain; charset=utf-8',
        )

    match = get_object_or_404(Match, id=match_id)

    # Chemin vers le template (à placer dans staticfiles ou MEDIA_ROOT)
    template_path = os.path.join(
        os.path.dirname(os.path.dirname(__file__)),
        'static', 'gestion', 'feuille_match_template.xlsx'
    )

    if not os.path.exists(template_path):
        return HttpResponse(
            'Template Excel introuvable. Placez le fichier dans '
            'static/gestion/feuille_match_template.xlsx',
            status=500,
            content_type='text/plain',
        )

    wb = load_workbook(template_path)
    ws = wb['Feuile de Match']   # note : typo intentionnelle du template

    def w(row, col, value):
        """
        Écrit une valeur dans une cellule du template.

        Le template FFN contient de nombreuses cellules fusionnées.
        openpyxl rend les cellules secondaires d'une fusion en lecture seule
        (MergedCell). Cette fonction remonte automatiquement à la cellule
        maître (coin haut-gauche de la plage fusionnée) si nécessaire.
        """
        if value is None or value == '':
            return
        from openpyxl.cell.cell import MergedCell
        cell = ws.cell(row=row, column=col)
        if isinstance(cell, MergedCell):
            # Trouver la plage fusionnée qui contient cette cellule
            # et écrire dans sa cellule maître (haut-gauche)
            for merged_range in ws.merged_cells.ranges:
                if (merged_range.min_row <= row <= merged_range.max_row
                        and merged_range.min_col <= col <= merged_range.max_col):
                    cell = ws.cell(
                        row=merged_range.min_row,
                        column=merged_range.min_col,
                    )
                    break
            else:
                return  # Cellule fusionnée orpheline, on ignore
        cell.value = value

    # ── Infos générales ──────────────────────────────────────────────────────
    w(2, 3,  match.nom_equipe_domicile)
    w(2, 38, match.competition)
    w(3, 35, match.lieu)
    if match.date_match:
        w(4, 35, match.date_match.strftime('%d/%m/%Y'))
    if match.heure_debut:
        w(4, 40, match.heure_debut.strftime('%H:%M'))

    # ── Secrétaire / Chrono / Juges de but ───────────────────────────────────
    # Ces infos sont intégrées dans les cellules-libellés du template (ligne 5)
    if match.secretaire_nom:
        w(5, 34, f"SECRÉTAIRE : {match.secretaire_nom}")
    if match.secretaire_iuf:
        w(5, 38, f"IUF N° : {match.secretaire_iuf}")
    if match.chrono_nom:
        w(5, 40, f"CHRONO : {match.chrono_nom}")
    if match.chrono_iuf:
        w(5, 45, f"IUF N° : {match.chrono_iuf}")

    if match.juge_but1_nom:
        w(7, 34, f"JUGE DE BUT : {match.juge_but1_nom}")
    if match.juge_but1_iuf:
        w(7, 38, f"IUF N° : {match.juge_but1_iuf}")
    if match.juge_but2_nom:
        w(7, 40, f"JUGE DE BUT : {match.juge_but2_nom}")
    if match.juge_but2_iuf:
        w(7, 45, f"IUF N° : {match.juge_but2_iuf}")

    # ── Équipe domicile – joueurs (lignes 6 à 20) ────────────────────────────
    joueurs_dom = list(
        Participation.objects.filter(match=match, equipe_concernee='DOM')
        .order_by('numero_bonnet')
    )
    _fill_players(ws, joueurs_dom, start_row=6, match=match)

    # ── Staff domicile ────────────────────────────────────────────────────────
    w(21, 18, match.entraineur_dom)
    w(22, 18, match.entraineur_adj_dom)
    w(23, 18, match.suppleant_dom)

    # ── Résultats par période ─────────────────────────────────────────────────
    scores = {sp.numero_periode: sp
               for sp in ScorePeriode.objects.filter(match=match)}
    # Résultat final si période non enregistrée
    final = {'score_dom': match.score_domicile, 'score_ext': match.score_exterieur}

    periode_rows = {1: 23, 2: 24, 3: 25, 4: 26}
    for p_num, p_row in periode_rows.items():
        sp = scores.get(p_num)
        if sp:
            w(p_row, 30, sp.score_dom)
            w(p_row, 31, sp.score_ext)

    # Score final
    w(22, 30, match.score_domicile)
    w(22, 31, match.score_exterieur)

    # ── Équipe extérieure – en-tête ────────────────────────────────────────────
    w(25, 3,  match.nom_equipe_exterieur)

    # ── Équipe extérieure – joueurs (lignes 29 à 43) ──────────────────────────
    joueurs_ext = list(
        Participation.objects.filter(match=match, equipe_concernee='EXT')
        .order_by('numero_bonnet')
    )
    _fill_players(ws, joueurs_ext, start_row=29, match=match)

    # ── Staff extérieur ────────────────────────────────────────────────────────
    w(44, 18, match.entraineur_ext)
    w(45, 18, match.entraineur_adj_ext)
    w(46, 18, match.suppleant_ext)

    # ── Délégués & arbitres ────────────────────────────────────────────────────
    if match.delegue_dom_nom:
        w(48, 34, match.delegue_dom_nom)
    if match.delegue_ext_nom:
        w(49, 34, match.delegue_ext_nom)
    if match.arbitre1_nom:
        w(50, 34, match.arbitre1_nom)
    if match.arbitre1_iuf:
        w(50, 38, match.arbitre1_iuf)
    if match.arbitre2_nom:
        w(51, 34, match.arbitre2_nom)
    if match.arbitre2_iuf:
        w(51, 38, match.arbitre2_iuf)
    if match.delegue_ffn_nom:
        w(52, 34, match.delegue_ffn_nom)
    if match.delegue_ffn_iuf:
        w(52, 38, match.delegue_ffn_iuf)

    # ── Journal des événements (zone droite, 3 colonnes de 5) ─────────────────
    events = list(
        Evenement.objects.filter(match=match).order_by('heure_creation')
    )
    # 3 groupes : (34-38), (40-44), (46-50) — chaque groupe peut tenir ~40 lignes
    # Ligne de départ des données : ligne 13 (la ligne 12 = en-têtes)
    groups = [
        (34, 13),   # cols AH-AL, à partir de la ligne 13
        (40, 13),   # cols AN-AR
        (46, 13),   # cols AT-AX
    ]
    max_per_group = 35

    for idx, evt in enumerate(events):
        grp_idx = idx // max_per_group
        if grp_idx >= len(groups):
            break  # Dépasse la capacité du template
        start_col, start_row = groups[grp_idx]
        row = start_row + (idx % max_per_group)

        bonnet = evt.joueur.numero_bonnet if evt.joueur else ''
        dom_b  = bonnet if evt.equipe_attribuee == 'DOM' else ''
        ext_b  = bonnet if evt.equipe_attribuee == 'EXT' else ''

        w(row, start_col,     evt.chrono_match)
        w(row, start_col + 1, dom_b)
        w(row, start_col + 2, ext_b)
        w(row, start_col + 3, evt.type_action)
        w(row, start_col + 4,
          f"{evt.score_dom_apres}-{evt.score_ext_apres}")

    # ── Sauvegarde en mémoire et envoi HTTP ────────────────────────────────────
    from io import BytesIO
    buf = BytesIO()
    wb.save(buf)
    buf.seek(0)

    equipes_slug = (
        f"{match.nom_equipe_domicile}_vs_{match.nom_equipe_exterieur}"
        .replace(' ', '_')[:60]
    )
    filename = f"feuille_match_{equipes_slug}.xlsx"

    response = HttpResponse(
        buf.read(),
        content_type='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    )
    response['Content-Disposition'] = f'attachment; filename="{filename}"'
    return response


def _safe_write(ws, row, col, value):
    """
    Écrit dans une cellule en gérant les cellules fusionnées (MergedCell).
    Utilisable depuis _fill_players (qui n'a pas accès à la closure w()).
    """
    from openpyxl.cell.cell import MergedCell
    if value is None or value == '':
        return
    cell = ws.cell(row=row, column=col)
    if isinstance(cell, MergedCell):
        for merged_range in ws.merged_cells.ranges:
            if (merged_range.min_row <= row <= merged_range.max_row
                    and merged_range.min_col <= col <= merged_range.max_col):
                cell = ws.cell(
                    row=merged_range.min_row,
                    column=merged_range.min_col,
                )
                break
        else:
            return
    cell.value = value


def _fill_players(ws, joueurs, start_row, match):
    """
    Remplit les lignes de joueurs dans le template.
    start_row : première ligne de données (6 pour DOM, 29 pour EXT).
    Les bonnets vont de 1 à 15 ; on place chaque joueur sur la bonne ligne.
    """
    # Calcul des buts par joueur
    buts_map = {}
    for p in joueurs:
        buts_map[p.id] = Evenement.objects.filter(
            match=match, joueur=p, type_action='BUT'
        ).count()

    # Collecte des exclusions/sanctions par joueur (max 3)
    events_map = {}
    for p in joueurs:
        evts = list(
            Evenement.objects.filter(
                match=match, joueur=p,
                type_action__in=('EXCL', 'EDA', 'PENALTY', 'FAUTE'),
            ).order_by('heure_creation')[:3]
        )
        events_map[p.id] = evts

    # Indexer les joueurs par numéro de bonnet
    by_bonnet = {p.numero_bonnet: p for p in joueurs}

    for bonnet in range(1, 16):
        row = start_row + (bonnet - 1)
        p = by_bonnet.get(bonnet)
        if not p:
            continue

        _safe_write(ws, row, 2,  p.numero_licence or '')
        _safe_write(ws, row, 3,  f"{p.nom} {p.prenom}")
        _safe_write(ws, row, 16, p.annee_naissance or '')
        _safe_write(ws, row, 18, bonnet)
        _safe_write(ws, row, 19, buts_map.get(p.id, 0) or '')

        # Événements joueur (Exclusion, EDA, Penalty…) – 3 colonnes paires
        code_cols = [(22, 23), (24, 25), (26, 27)]  # (Code, Période)
        for evt_idx, (col_code, col_per) in enumerate(code_cols):
            player_evts = events_map.get(p.id, [])
            if evt_idx < len(player_evts):
                evt = player_evts[evt_idx]
                _safe_write(ws, row, col_code, evt.type_action)
                _safe_write(ws, row, col_per,  evt.periode)


# ==========================================
# 7. TABLEAU DE BORD (Legacy / fallback)
# ==========================================

def tableau_bord(request, match_id):
    match = get_object_or_404(Match, id=match_id)
    joueurs_dom = Participation.objects.filter(
        match=match, equipe_concernee='DOM').order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(
        match=match, equipe_concernee='EXT').order_by('numero_bonnet')
    events = Evenement.objects.filter(match=match).order_by('-heure_creation')[:5]
    return render(request, 'gestion/tableau_bord.html', {
        'match': match,
        'joueurs_dom': joueurs_dom,
        'joueurs_ext': joueurs_ext,
        'events': events,
    })
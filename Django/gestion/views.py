from django.shortcuts import render, redirect, get_object_or_404
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.utils import timezone
import json
from datetime import timedelta
from .models import Match, Equipe, Joueur, Participation, Evenement

# ==========================================
# 1. ACCUEIL & GESTION ÉQUIPES
# ==========================================
def accueil(request):
    """ Page Dashboard : Liste des équipes + Création Match """
    equipes_existantes = Equipe.objects.all()

    if request.method == "POST":
        action = request.POST.get('action')

        # Créer une équipe
        if action == "creer_equipe":
            Equipe.objects.create(nom=request.POST.get('nouveau_nom_equipe'))
            return redirect('accueil')

        # Supprimer une équipe
        elif action == "supprimer_equipe":
            Equipe.objects.filter(id=request.POST.get('equipe_id')).delete()
            return redirect('accueil')

        # Lancer un match
        elif action == "lancer_match":
            id_dom = request.POST.get('select_domicile')
            nom_dom = request.POST.get('input_domicile')
            eq_dom_obj = None
            if id_dom:
                eq_dom_obj = Equipe.objects.get(id=id_dom)
                nom_dom = eq_dom_obj.nom

            id_ext = request.POST.get('select_exterieur')
            nom_ext = request.POST.get('input_exterieur')
            eq_ext_obj = None
            if id_ext:
                eq_ext_obj = Equipe.objects.get(id=id_ext)
                nom_ext = eq_ext_obj.nom

            match = Match.objects.create(
                nom_equipe_domicile=nom_dom,
                equipe_domicile_origine=eq_dom_obj,
                nom_equipe_exterieur=nom_ext,
                equipe_exterieur_origine=eq_ext_obj,
                lieu=request.POST.get('lieu'),
                temps_possession=request.POST.get('temps_possession'),
                temps_exclusion=request.POST.get('temps_exclusion'),
                duree_periode=request.POST.get('duree_periode'),
            )
            return redirect('compo_equipe', match_id=match.id)

    return render(request, 'gestion/accueil.html', {'equipes': equipes_existantes})

# ==========================================
# 2. COMPOSITION D'ÉQUIPE
# ==========================================
def compo_equipe(request, match_id):
    match = get_object_or_404(Match, id=match_id)
    
    prefill_dom = {}
    prefill_ext = {}

    if match.equipe_domicile_origine:
        for j in match.equipe_domicile_origine.effectif.all():
            if 0 < j.numero_habituel <= 15:
                prefill_dom[j.numero_habituel] = j

    if match.equipe_exterieur_origine:
        for j in match.equipe_exterieur_origine.effectif.all():
            if 0 < j.numero_habituel <= 15:
                prefill_ext[j.numero_habituel] = j

    if request.method == "POST":
        Participation.objects.filter(match=match).delete()
        
        for i in range(1, 16):
            # Domicile
            nom_d = request.POST.get(f'nom_dom_{i}')
            prenom_d = request.POST.get(f'prenom_dom_{i}')
            if nom_d or prenom_d:
                Participation.objects.create(
                    match=match, numero_bonnet=i, equipe_concernee='DOM',
                    nom=nom_d.upper(), prenom=prenom_d.capitalize()
                )

            # Extérieur
            nom_e = request.POST.get(f'nom_ext_{i}')
            prenom_e = request.POST.get(f'prenom_ext_{i}')
            if nom_e or prenom_e:
                Participation.objects.create(
                    match=match, numero_bonnet=i, equipe_concernee='EXT',
                    nom=nom_e.upper(), prenom=prenom_e.capitalize()
                )

        # Update Master Data
        if request.POST.get('update_master_dom') == 'on' and match.equipe_domicile_origine:
            match.equipe_domicile_origine.effectif.all().delete()
            for i in range(1, 16):
                nom = request.POST.get(f'nom_dom_{i}')
                prenom = request.POST.get(f'prenom_dom_{i}')
                if nom:
                    Joueur.objects.create(equipe=match.equipe_domicile_origine, nom=nom, prenom=prenom, numero_habituel=i)

        if request.POST.get('update_master_ext') == 'on' and match.equipe_exterieur_origine:
            match.equipe_exterieur_origine.effectif.all().delete()
            for i in range(1, 16):
                nom = request.POST.get(f'nom_ext_{i}')
                prenom = request.POST.get(f'prenom_ext_{i}')
                if nom:
                    Joueur.objects.create(equipe=match.equipe_exterieur_origine, nom=nom, prenom=prenom, numero_habituel=i)

        match.compo_validee = True
        match.save()
        return redirect('tableau_bord', match_id=match.id)

    return render(request, 'gestion/compo_equipe.html', {
        'match': match,
        'range_15': range(1, 16),
        'prefill_dom': prefill_dom,
        'prefill_ext': prefill_ext
    })

# ==========================================
# 3. TABLEAU DE BORD (Lecture seule)
# ==========================================
def tableau_bord(request, match_id):
    match = get_object_or_404(Match, id=match_id)
    joueurs_dom = Participation.objects.filter(match=match, equipe_concernee='DOM').order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(match=match, equipe_concernee='EXT').order_by('numero_bonnet')
    events = Evenement.objects.filter(match=match).order_by('-id')[:5]
    
    return render(request, 'gestion/tableau_bord.html', {
        'match': match, 'joueurs_dom': joueurs_dom, 'joueurs_ext': joueurs_ext, 'events': events
    })

# ==========================================
# 4. ACTIONS (Legacy - pour compatibilité)
# ==========================================
def ajouter_action(request, match_id, participation_id, type_action):
    """ Ancienne vue pour les liens directs (non AJAX) """
    match = get_object_or_404(Match, id=match_id)
    participation = get_object_or_404(Participation, id=participation_id)
    
    Evenement.objects.create(match=match, joueur=participation, type_action=type_action, equipe_attribuee=participation.equipe_concernee)
    
    if type_action == 'BUT':
        if participation.equipe_concernee == 'DOM': match.score_domicile += 1
        else: match.score_exterieur += 1
        match.save()
    return redirect('tableau_bord', match_id=match.id)

# ==========================================
# 5. DASHBOARD ARBITRE (PRO)
# ==========================================
def dashboard_arbitre(request, match_id):
    """ Affiche l'interface HTML de l'arbitre """
    match = get_object_or_404(Match, id=match_id)
    joueurs_dom = Participation.objects.filter(match=match, equipe_concernee='DOM').order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(match=match, equipe_concernee='EXT').order_by('numero_bonnet')
    events = Evenement.objects.filter(match=match).order_by('-heure_creation')
    
    return render(request, 'gestion/dashboard_arbitre.html', {
        'match': match, 'joueurs_dom': joueurs_dom, 'joueurs_ext': joueurs_ext, 'events': events
    })

# ==========================================
# 6. API MATCH ACTION (LE CERVEAU)
# ==========================================
@csrf_exempt
def api_match_action(request, match_id):
    if request.method == "POST":
        data = json.loads(request.body)
        match = Match.objects.get(id=match_id)
        action = data.get('action')
        
        # --- 1. GESTION DU TEMPS (GLOBAL) ---
        if action == 'start_timer':
            match.chrono_en_cours = True
            match.save()
            return JsonResponse({'status': 'ok'})
            
        elif action == 'stop_timer':
            match.chrono_en_cours = False
            match.temps_restant = int(data.get('temps_restant', match.temps_restant))
            match.save()
            return JsonResponse({'status': 'ok'})

        elif action == 'adjust_time':
            delta = int(data.get('delta', 0))
            match.temps_restant = max(0, match.temps_restant + delta)
            match.save()
            return JsonResponse({'new_time': match.temps_restant})

        elif action == 'next_period':
            match.chrono_en_cours = False
            match.periode_actuelle += 1
            match.temps_restant = match.duree_periode * 60 # Reset au temps par défaut
            match.save()
            return JsonResponse({'period': match.periode_actuelle, 'time': match.temps_restant})

        # --- 2. ACTIONS DE JEU ---
        elif action in ['BUT', 'EXCL', 'EDA', 'PENALTY', 'TM']:
            p_id = data.get('participation_id')
            chrono_display = data.get('chrono_display', '00:00')
            
            # Gestion Temps Mort (TM)
            if action == 'TM':
                equipe = data.get('equipe') # 'DOM' ou 'EXT'
                joueur = None
                Evenement.objects.create(
                    match=match, type_action='TM', equipe_attribuee=equipe, 
                    chrono_match=chrono_display
                )
            else:
                joueur = Participation.objects.get(id=p_id)
                Evenement.objects.create(
                    match=match, joueur=joueur, type_action=action, 
                    equipe_attribuee=joueur.equipe_concernee, chrono_match=chrono_display
                )

                if action == 'BUT':
                    if joueur.equipe_concernee == 'DOM': match.score_domicile += 1
                    else: match.score_exterieur += 1
                
                elif action == 'PENALTY':
                    joueur.nb_fautes_personnelles += 1
                    
                elif action == 'EXCL':
                    joueur.est_exclu = True
                    joueur.nb_fautes_personnelles += 1
                    joueur.fin_exclusion = timezone.now() + timedelta(seconds=match.temps_exclusion)
                    joueur.save()

                elif action == 'EDA':
                    joueur.est_exclu_definitif = True
                    joueur.nb_fautes_personnelles += 1
                    joueur.save()
            
            match.save()

        # --- 3. UNDO CIBLÉ ---
        elif action == 'delete_event':
            evt_id = data.get('event_id')
            evt = Evenement.objects.get(id=evt_id)
            
            # Annuler l'effet
            if evt.type_action == 'BUT':
                if evt.equipe_attribuee == 'DOM': match.score_domicile -= 1
                else: match.score_exterieur -= 1
            
            elif evt.type_action in ['EXCL', 'EDA', 'PENALTY']:
                if evt.joueur:
                    if evt.type_action == 'EXCL': evt.joueur.est_exclu = False
                    if evt.type_action == 'EDA': evt.joueur.est_exclu_definitif = False
                    evt.joueur.nb_fautes_personnelles -= 1
                    evt.joueur.save()
            
            evt.delete()
            match.save()

        # --- 4. RÉPONSE STANDARD (ETAT COMPLET) ---
        history = list(Evenement.objects.filter(match=match).order_by('-heure_creation')[:20].values(
            'id', 'type_action', 'chrono_match', 'equipe_attribuee', 
            'joueur__nom', 'joueur__numero_bonnet'
        ))

        return JsonResponse({
            'score_dom': match.score_domicile, 
            'score_ext': match.score_exterieur,
            'history': history,
            'period': match.periode_actuelle,
            'status': 'ok'
        })

    return JsonResponse({'error': 'Bad Request'}, status=400)
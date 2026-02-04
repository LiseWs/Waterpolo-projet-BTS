from django.shortcuts import render, redirect, get_object_or_404
from .models import Match, Equipe, Configuration, Participation, Joueur, Evenement

def accueil(request):
    """ ÉTAPE 1 : Création du match (Choix équipes + Règles) """
    if request.method == "POST":
        match = Match.objects.create(
            equipe_domicile_id=request.POST.get('equipe_domicile'),
            equipe_exterieur_id=request.POST.get('equipe_exterieur'),
            config_id=request.POST.get('config'),
            lieu=request.POST.get('lieu', 'Piscine'),
            competition=request.POST.get('competition', 'Championnat')
        )
        # On redirige vers la page de composition d'équipe
        return redirect('compo_equipe', match_id=match.id)

    return render(request, 'gestion/accueil.html', {
        'equipes': Equipe.objects.all(),
        'configs': Configuration.objects.all()
    })

def compo_equipe(request, match_id):
    """ ÉTAPE 2 : On remplit la feuille de match (15 joueurs max par côté) """
    match = get_object_or_404(Match, id=match_id)
    
    # Si on valide le formulaire
    if request.method == "POST":
        # On nettoie les anciennes participations si on refait la compo
        Participation.objects.filter(match=match).delete()

        # On boucle de 1 à 15 (les numéros de bonnet possibles)
        for i in range(1, 16):
            # Récupération joueur Domicile pour le bonnet i
            joueur_dom_id = request.POST.get(f'joueur_dom_{i}')
            if joueur_dom_id:
                Participation.objects.create(match=match, joueur_id=joueur_dom_id, numero_bonnet=i)
            
            # Récupération joueur Extérieur pour le bonnet i
            joueur_ext_id = request.POST.get(f'joueur_ext_{i}')
            if joueur_ext_id:
                Participation.objects.create(match=match, joueur_id=joueur_ext_id, numero_bonnet=i)
        
        match.compo_validee = True
        match.save()
        return redirect('tableau_bord', match_id=match.id)

    # Pour l'affichage, on envoie la liste complète des joueurs de chaque club
    return render(request, 'gestion/compo_equipe.html', {
        'match': match,
        'effectif_dom': match.equipe_domicile.effectif.all(), # Tous les licenciés du club A
        'effectif_ext': match.equipe_exterieur.effectif.all(), # Tous les licenciés du club B
        'range_15': range(1, 16) # Pour faire la boucle de 1 à 15 dans le HTML
    })

# ... (Garde tableau_bord et ajouter_action comme avant) ...

def tableau_bord(request, match_id):
    """ Interface principale du match """
    match = get_object_or_404(Match, id=match_id)
    
    # Récupérer les joueurs (triés par bonnet)
    joueurs_dom = Participation.objects.filter(match=match, joueur__equipe=match.equipe_domicile).order_by('numero_bonnet')
    joueurs_ext = Participation.objects.filter(match=match, joueur__equipe=match.equipe_exterieur).order_by('numero_bonnet')
    
    # Récupérer les 5 derniers événements pour l'historique
    derniers_events = Evenement.objects.filter(match=match).order_by('-heure_creation')[:5]

    context = {
        'match': match,
        'joueurs_dom': joueurs_dom,
        'joueurs_ext': joueurs_ext,
        'events': derniers_events,
    }
    return render(request, 'gestion/tableau_bord.html', context)

def ajouter_action(request, match_id, participation_id, type_action):
    """ Fonction qui traite le clic sur un bouton """
    match = get_object_or_404(Match, id=match_id)
    participation = get_object_or_404(Participation, id=participation_id)
    
    # 1. Créer l'événement
    # Note: Pour l'instant on met un chrono fictif, on gérera le vrai chrono plus tard
    Evenement.objects.create(
        match=match,
        joueur=participation,
        equipe=participation.joueur.equipe,
        type_action=type_action,
        temps_jeu_chrono="00:00", 
        periode=match.periode_actuelle
    )
    
    # 2. Si c'est un BUT, on met à jour le score du match
    if type_action == 'BUT':
        if participation.joueur.equipe == match.equipe_domicile:
            match.score_domicile += 1
        else:
            match.score_exterieur += 1
        match.save()
    
    # 3. On recharge la page
    return redirect('tableau_bord', match_id=match_id)
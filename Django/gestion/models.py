from django.db import models
from django.utils import timezone
from datetime import timedelta

# ==========================================
# 1. LE CATALOGUE (Données persistantes)
# ==========================================
class Equipe(models.Model):
    nom = models.CharField(max_length=100)
    entraineur = models.CharField(max_length=100, blank=True)
    
    def __str__(self):
        return self.nom

class Joueur(models.Model):
    equipe = models.ForeignKey(Equipe, on_delete=models.CASCADE, related_name="effectif")
    nom = models.CharField(max_length=50)
    prenom = models.CharField(max_length=50)
    numero_habituel = models.IntegerField(default=0)
    
    def __str__(self):
        return f"{self.nom} {self.prenom}"

# ==========================================
# 2. LE MATCH (Instance spécifique)
# ==========================================
class Match(models.Model):
    # Infos générales
    date_match = models.DateTimeField(auto_now_add=True)
    lieu = models.CharField(max_length=100, default="Piscine")
    
    # Équipes (Noms + Lien optionnel vers catalogue)
    nom_equipe_domicile = models.CharField(max_length=100)
    equipe_domicile_origine = models.ForeignKey(Equipe, on_delete=models.SET_NULL, null=True, blank=True, related_name='matchs_dom')
    
    nom_equipe_exterieur = models.CharField(max_length=100)
    equipe_exterieur_origine = models.ForeignKey(Equipe, on_delete=models.SET_NULL, null=True, blank=True, related_name='matchs_ext')

    # RÈGLES
    duree_periode = models.IntegerField(default=8)
    temps_possession = models.IntegerField(default=28)
    temps_exclusion = models.IntegerField(default=20)
    nb_temps_mort = models.IntegerField(default=2)
    
    # ÉTAT DU MATCH
    score_domicile = models.IntegerField(default=0)
    score_exterieur = models.IntegerField(default=0)
    periode_actuelle = models.IntegerField(default=1)
    compo_validee = models.BooleanField(default=False)
    
    # GESTION DU TEMPS (Pour l'arbitre)
    chrono_en_cours = models.BooleanField(default=False)
    temps_restant = models.IntegerField(default=480) # En secondes (8 min * 60)
    dernier_top_chrono = models.DateTimeField(null=True, blank=True)

class Participation(models.Model):
    match = models.ForeignKey(Match, on_delete=models.CASCADE, related_name="participants")
    numero_bonnet = models.IntegerField()
    nom = models.CharField(max_length=50)
    prenom = models.CharField(max_length=50)
    equipe_concernee = models.CharField(max_length=10) # 'DOM' ou 'EXT'
    
    # ÉTAT DU JOUEUR (Fautes et exclusions)
    est_exclu = models.BooleanField(default=False)
    fin_exclusion = models.DateTimeField(null=True, blank=True)
    nb_fautes_personnelles = models.IntegerField(default=0)
    est_exclu_definitif = models.BooleanField(default=False)

class Evenement(models.Model):
    TYPES = [
        ('BUT', 'But'), 
        ('EXCL', 'Exclusion Temporaire'), 
        ('EDA', 'Exclusion Définitive'),
        ('TM', 'Temps Mort')
    ]
    match = models.ForeignKey(Match, on_delete=models.CASCADE)
    joueur = models.ForeignKey(Participation, on_delete=models.SET_NULL, null=True, blank=True)
    type_action = models.CharField(max_length=10, choices=TYPES)
    equipe_attribuee = models.CharField(max_length=10, null=True)
    
    heure_creation = models.DateTimeField(auto_now_add=True)
    chrono_match = models.CharField(max_length=10, default="00:00")
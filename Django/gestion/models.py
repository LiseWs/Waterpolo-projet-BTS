from django.db import models
from django.utils import timezone

class Configuration(models.Model):
    nom = models.CharField(max_length=50, default="Règlement 2026")
    
    # --- LES CHRONOS ---
    duree_periodes = models.IntegerField(default=8, verbose_name="Durée Période (min)")
    temps_possession = models.IntegerField(default=28, verbose_name="Possession (sec)")
    temps_exclusion = models.IntegerField(default=18, verbose_name="Exclusion (sec)")
    
    # --- LES PAUSES ---
    duree_pause_courte = models.IntegerField(default=2, verbose_name="Pause 1-2 et 3-4 (min)")
    duree_mi_temps = models.IntegerField(default=5, verbose_name="Mi-temps (min)")
    
    # --- RÈGLES SPÉCIFIQUES ---
    nb_temps_mort = models.IntegerField(default=2, verbose_name="Temps morts par équipe")
    avec_prolongation = models.BooleanField(default=False, verbose_name="Prolongations possibles ?")
    
    def __str__(self):
        return self.nom
# ---------------------------------------------------------
# 2. ACTEURS (Basé sur le fichier "BD Joueurs Licence.csv")
# ---------------------------------------------------------
class Equipe(models.Model):
    nom = models.CharField(max_length=100, verbose_name="Nom du club")
    ville = models.CharField(max_length=100, blank=True)
    couleur_bonnet = models.CharField(max_length=20, choices=[('BLANC', 'Blanc'), ('BLEU', 'Bleu/Noir')], default='BLANC')
    
    # Staff par défaut (pour pré-remplir la feuille de match)
    entraineur_principal = models.CharField(max_length=100, blank=True)
    entraineur_adjoint = models.CharField(max_length=100, blank=True)
    
    def __str__(self):
        return self.nom

class Joueur(models.Model):
    equipe = models.ForeignKey(Equipe, on_delete=models.CASCADE, related_name="effectif")
    nom = models.CharField(max_length=50)
    prenom = models.CharField(max_length=50)
    licence_iuf = models.CharField(max_length=20, verbose_name="Numéro de Licence (IUF)", unique=True)
    annee_naissance = models.IntegerField(verbose_name="Année de naissance")
    
    # Le bonnet par défaut (peut changer selon le match, mais utile d'avoir une base)
    numero_habituel = models.IntegerField(default=0)

    def __str__(self):
        return f"{self.nom} {self.prenom} ({self.licence_iuf})"


class Match(models.Model):
    # ... (Garde les champs existants) ...
    # Ajoute ce champ pour savoir si la compo a été faite
    compo_validee = models.BooleanField(default=False)


# ---------------------------------------------------------
# 3. LE MATCH (Basé sur l'en-tête du fichier "Feuille de match Excel")
# ---------------------------------------------------------
class Match(models.Model):
    # Infos générales
    config = models.ForeignKey(Configuration, on_delete=models.SET_NULL, null=True)
    date_match = models.DateField(default=timezone.now)
    heure_match = models.TimeField(default=timezone.now)
    lieu = models.CharField(max_length=100, default="Piscine Paul Boyrie")
    competition = models.CharField(max_length=100, default="Championnat N3 Occitanie")
    
    # Les équipes
    equipe_domicile = models.ForeignKey(Equipe, related_name='matchs_domicile', on_delete=models.PROTECT)
    equipe_exterieur = models.ForeignKey(Equipe, related_name='matchs_exterieur', on_delete=models.PROTECT)
    
    # Les scores (mis à jour automatiquement ou manuellement)
    score_domicile = models.IntegerField(default=0)
    score_exterieur = models.IntegerField(default=0)
    
    # État du match
    est_termine = models.BooleanField(default=False)
    periode_actuelle = models.IntegerField(default=1) # 1, 2, 3, 4
    
    # Les Officiels (CRUCIAL pour l'export Excel - voir bas de page Excel)
    arbitre_1 = models.CharField(max_length=100, blank=True)
    arbitre_2 = models.CharField(max_length=100, blank=True)
    delegue_ffn = models.CharField(max_length=100, blank=True)
    secretaire = models.CharField(max_length=100, default="Étudiant 1")
    chronometreur = models.CharField(max_length=100, default="Étudiant 2")

    def __str__(self):
        return f"{self.equipe_domicile} vs {self.equipe_exterieur} ({self.date_match})"

# ---------------------------------------------------------
# 4. FEUILLE DE MATCH (Qui joue CE match là ?)
# ---------------------------------------------------------
class Participation(models.Model):
    """
    Table de liaison : permet de dire quel joueur joue quel match 
    et avec quel bonnet (1 à 13). C'est ça qui remplit les lignes de l'Excel.
    """
    match = models.ForeignKey(Match, on_delete=models.CASCADE, related_name="feuille_de_match")
    joueur = models.ForeignKey(Joueur, on_delete=models.CASCADE)
    numero_bonnet = models.IntegerField(help_text="Numéro 1 à 13 pour ce match")
    est_capitaine = models.BooleanField(default=False)
    
    class Meta:
        ordering = ['numero_bonnet'] # Pour que l'export Excel soit dans l'ordre 1-13
        unique_together = ('match', 'numero_bonnet', 'joueur') # Pas deux n°10 dans le même match

    def __str__(self):
        return f"Bonnet {self.numero_bonnet} - {self.joueur.nom}"

# ---------------------------------------------------------
# 5. ÉVÉNEMENTS (Le cœur du logiciel)
# ---------------------------------------------------------
class Evenement(models.Model):
    TYPES = [
        ('BUT', 'But'),
        ('EXCL', 'Exclusion (E)'),      # Faute grave 20s (ou 18s mnt)
        ('PENALTY', 'Penalty (P)'),     # Faute de Penalty
        ('EDA', 'Exclusion Définitive (EDA)'), # Faute grave avec remplacement
        ('TM', 'Temps Mort'),
        ('CARTON_J', 'Carton Jaune'),
        ('CARTON_R', 'Carton Rouge'),
    ]

    match = models.ForeignKey(Match, on_delete=models.CASCADE, related_name="evenements")
    # On lie à "Participation" pour savoir quel bonnet a fait la faute
    joueur = models.ForeignKey(Participation, on_delete=models.SET_NULL, null=True, blank=True)
    equipe = models.ForeignKey(Equipe, on_delete=models.CASCADE, null=True, blank=True) # Pour les temps morts
    
    type_action = models.CharField(max_length=10, choices=TYPES)
    heure_creation = models.DateTimeField(auto_now_add=True)
    temps_jeu_chrono = models.CharField(max_length=8, help_text="Ex: 06:24")
    periode = models.IntegerField(default=1) # 1, 2, 3, 4

    def __str__(self):
        return f"P{self.periode} - {self.temps_jeu_chrono} : {self.type_action}"
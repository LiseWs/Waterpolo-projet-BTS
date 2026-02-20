from django.db import models
from django.utils import timezone
from datetime import timedelta


# ==========================================
# 1. LE CATALOGUE
# ==========================================

class Equipe(models.Model):
    nom = models.CharField(max_length=100)
    entraineur = models.CharField(max_length=100, blank=True)

    def __str__(self):
        return self.nom


class Joueur(models.Model):
    equipe = models.ForeignKey(Equipe, on_delete=models.CASCADE, related_name='effectif')
    nom = models.CharField(max_length=50)
    prenom = models.CharField(max_length=50)
    numero_habituel = models.IntegerField(default=0)
    numero_licence = models.CharField(max_length=50, blank=True)
    annee_naissance = models.IntegerField(null=True, blank=True)

    def __str__(self):
        return f"{self.nom} {self.prenom}"


# ==========================================
# 2. LE MATCH
# ==========================================

class Match(models.Model):
    # ── Infos générales ─────────────────────────────────────────────────────
    date_match = models.DateTimeField(auto_now_add=True)
    heure_debut = models.TimeField(null=True, blank=True,
                                   help_text="Heure de début du match (HH:MM)")
    lieu = models.CharField(max_length=100, default="Piscine")
    competition = models.CharField(max_length=200, blank=True,
                                   help_text="Intitulé de la compétition (ex: Championnat de France N3)")

    # ── Équipes ──────────────────────────────────────────────────────────────
    nom_equipe_domicile = models.CharField(max_length=100)
    equipe_domicile_origine = models.ForeignKey(
        Equipe, on_delete=models.SET_NULL, null=True, blank=True,
        related_name='matchs_dom')
    couleur_bonnet_dom = models.CharField(max_length=20, default='BLANC')

    nom_equipe_exterieur = models.CharField(max_length=100)
    equipe_exterieur_origine = models.ForeignKey(
        Equipe, on_delete=models.SET_NULL, null=True, blank=True,
        related_name='matchs_ext')
    couleur_bonnet_ext = models.CharField(max_length=20, default='BLEU')

    # ── Staff équipe domicile ────────────────────────────────────────────────
    entraineur_dom = models.CharField(max_length=100, blank=True)
    entraineur_adj_dom = models.CharField(max_length=100, blank=True)
    suppleant_dom = models.CharField(max_length=100, blank=True)

    # ── Staff équipe extérieur ───────────────────────────────────────────────
    entraineur_ext = models.CharField(max_length=100, blank=True)
    entraineur_adj_ext = models.CharField(max_length=100, blank=True)
    suppleant_ext = models.CharField(max_length=100, blank=True)

    # ── Officiels ────────────────────────────────────────────────────────────
    arbitre1_nom = models.CharField(max_length=100, blank=True)
    arbitre1_iuf = models.CharField(max_length=50, blank=True)
    arbitre2_nom = models.CharField(max_length=100, blank=True)
    arbitre2_iuf = models.CharField(max_length=50, blank=True)
    secretaire_nom = models.CharField(max_length=100, blank=True)
    secretaire_iuf = models.CharField(max_length=50, blank=True)
    chrono_nom = models.CharField(max_length=100, blank=True)
    chrono_iuf = models.CharField(max_length=50, blank=True)
    juge_but1_nom = models.CharField(max_length=100, blank=True)
    juge_but1_iuf = models.CharField(max_length=50, blank=True)
    juge_but2_nom = models.CharField(max_length=100, blank=True)
    juge_but2_iuf = models.CharField(max_length=50, blank=True)
    delegue_ffn_nom = models.CharField(max_length=100, blank=True)
    delegue_ffn_iuf = models.CharField(max_length=50, blank=True)
    delegue_dom_nom = models.CharField(max_length=100, blank=True)
    delegue_ext_nom = models.CharField(max_length=100, blank=True)

    # ── Règles FFN ───────────────────────────────────────────────────────────
    duree_periode = models.IntegerField(default=8,
                                        help_text="Durée d'une période en minutes")
    nb_periodes = models.IntegerField(default=4,
                                      help_text="Nombre de périodes")
    temps_possession = models.IntegerField(default=30,
                                           help_text="Temps d'attaque en secondes")
    duree_exclusion = models.IntegerField(default=20,
                                          help_text="Durée d'exclusion temporaire en secondes")
    nb_temps_morts = models.IntegerField(default=2,
                                         help_text="Nombre de temps morts par équipe")
    max_fautes_perso = models.IntegerField(default=3,
                                           help_text="Nombre de fautes avant exclusion définitive")
    nb_remplacants = models.IntegerField(default=6,
                                         help_text="Nombre de remplaçants max")

    # ── État du match ────────────────────────────────────────────────────────
    score_domicile = models.IntegerField(default=0)
    score_exterieur = models.IntegerField(default=0)
    periode_actuelle = models.IntegerField(default=1)
    compo_validee = models.BooleanField(default=False)

    # ── Chrono (source de vérité serveur) ────────────────────────────────────
    chrono_en_cours = models.BooleanField(default=False)
    temps_restant = models.IntegerField(default=480)  # secondes
    dernier_top_chrono = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return f"{self.nom_equipe_domicile} vs {self.nom_equipe_exterieur}"


# ==========================================
# 3. PARTICIPATION (joueur dans un match)
# ==========================================

class Participation(models.Model):
    EQUIPE_CHOICES = [('DOM', 'Domicile'), ('EXT', 'Extérieur')]

    match = models.ForeignKey(Match, on_delete=models.CASCADE,
                               related_name='participants')
    equipe_concernee = models.CharField(max_length=3, choices=EQUIPE_CHOICES)
    numero_bonnet = models.IntegerField()
    nom = models.CharField(max_length=50)
    prenom = models.CharField(max_length=50)

    # Données pour feuille de match officielle
    numero_licence = models.CharField(max_length=50, blank=True)
    annee_naissance = models.IntegerField(null=True, blank=True)

    # État (fautes & exclusions)
    nb_fautes_personnelles = models.IntegerField(default=0)
    est_exclu = models.BooleanField(default=False)
    fin_exclusion = models.DateTimeField(null=True, blank=True)
    est_exclu_definitif = models.BooleanField(default=False)

    class Meta:
        ordering = ['equipe_concernee', 'numero_bonnet']
        unique_together = [('match', 'equipe_concernee', 'numero_bonnet')]

    def __str__(self):
        return f"#{self.numero_bonnet} {self.nom} ({self.equipe_concernee})"


# ==========================================
# 4. ÉVÉNEMENT (log du match)
# ==========================================

class Evenement(models.Model):
    TYPE_CHOICES = [
        ('BUT',     'But'),
        ('FAUTE',   'Faute ordinaire'),
        ('EXCL',    'Exclusion temporaire'),
        ('EDA',     'Exclusion définitive'),
        ('PENALTY', 'Penalty'),
        ('TM',      'Temps mort'),
    ]

    match = models.ForeignKey(Match, on_delete=models.CASCADE,
                               related_name='evenements')
    joueur = models.ForeignKey(Participation, on_delete=models.SET_NULL,
                                null=True, blank=True,
                                related_name='evenements')
    type_action = models.CharField(max_length=10, choices=TYPE_CHOICES)
    equipe_attribuee = models.CharField(max_length=3, null=True, blank=True)

    # Horodatage
    heure_creation = models.DateTimeField(auto_now_add=True)
    chrono_match = models.CharField(max_length=10, default='00:00',
                                    help_text="Temps affiché au moment de l'action")
    periode = models.IntegerField(default=1,
                                  help_text="Période du match lors de l'action")

    # Score snapshot (utile pour reconstituer la chronologie)
    score_dom_apres = models.IntegerField(default=0)
    score_ext_apres = models.IntegerField(default=0)

    class Meta:
        ordering = ['heure_creation']

    def __str__(self):
        joueur_str = str(self.joueur) if self.joueur else 'Équipe'
        return f"P{self.periode} {self.chrono_match} – {self.type_action} ({joueur_str})"


# ==========================================
# 5. SCORE PAR PÉRIODE
# ==========================================

class ScorePeriode(models.Model):
    """Snapshot du score à la fin de chaque période."""
    match = models.ForeignKey(Match, on_delete=models.CASCADE,
                               related_name='scores_periodes')
    numero_periode = models.IntegerField()
    score_dom = models.IntegerField(default=0)
    score_ext = models.IntegerField(default=0)

    class Meta:
        ordering = ['numero_periode']
        unique_together = [('match', 'numero_periode')]

    def __str__(self):
        return (f"Période {self.numero_periode}: "
                f"{self.score_dom} – {self.score_ext}")
from django.contrib import admin
# On importe TOUTES les classes qu'on a créées dans models.py
from .models import Configuration, Equipe, Joueur, Match, Evenement, Participation

# On les enregistre pour qu'elles apparaissent dans l'interface
admin.site.register(Configuration)
admin.site.register(Equipe)
admin.site.register(Joueur)
admin.site.register(Match)
admin.site.register(Participation)
admin.site.register(Evenement)
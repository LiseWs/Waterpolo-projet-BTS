from django.contrib import admin
from .models import Match, Equipe, Joueur, Participation, Evenement

admin.site.register(Match)
admin.site.register(Equipe)
admin.site.register(Joueur)
admin.site.register(Participation)
admin.site.register(Evenement)
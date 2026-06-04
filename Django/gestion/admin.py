from django.contrib import admin
from .models import Match, Equipe, Joueur, Participation, Evenement, SponsorGlobal


@admin.register(Match)
class MatchAdmin(admin.ModelAdmin):
    list_display = ('__str__', 'date_match', 'periode_actuelle',
                    'score_domicile', 'score_exterieur', 'chrono_en_cours')
    list_filter  = ('chrono_en_cours', 'compo_validee')
    search_fields = ('nom_equipe_domicile', 'nom_equipe_exterieur', 'lieu', 'competition')
    readonly_fields = ('date_match',)


@admin.register(Equipe)
class EquipeAdmin(admin.ModelAdmin):
    list_display  = ('nom', 'entraineur', 'logo')
    search_fields = ('nom',)


@admin.register(SponsorGlobal)
class SponsorGlobalAdmin(admin.ModelAdmin):
    list_display  = ('__str__', 'nom', 'image', 'ordre')
    list_editable = ('ordre',)
    search_fields = ('nom',)


@admin.register(Joueur)
class JoueurAdmin(admin.ModelAdmin):
    list_display  = ('nom', 'prenom', 'numero_habituel', 'equipe')
    list_filter   = ('equipe',)
    search_fields = ('nom', 'prenom', 'numero_licence')


@admin.register(Participation)
class ParticipationAdmin(admin.ModelAdmin):
    list_display  = ('match', 'equipe_concernee', 'numero_bonnet', 'nom', 'prenom',
                     'nb_fautes_personnelles', 'est_exclu', 'est_exclu_definitif')
    list_filter   = ('equipe_concernee', 'est_exclu', 'est_exclu_definitif')
    search_fields = ('nom', 'prenom')


@admin.register(Evenement)
class EvenementAdmin(admin.ModelAdmin):
    list_display  = ('match', 'type_action', 'periode', 'chrono_match',
                     'joueur', 'equipe_attribuee', 'score_dom_apres', 'score_ext_apres')
    list_filter   = ('type_action', 'periode', 'equipe_attribuee')
    search_fields = ('match__nom_equipe_domicile', 'match__nom_equipe_exterieur')

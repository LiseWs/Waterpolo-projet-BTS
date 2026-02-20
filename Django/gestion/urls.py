"""
urls.py — Ajouter la route export_excel aux URL existantes.
Exemple de fichier urls.py complet pour l'application 'gestion' :
"""
from django.urls import path
from . import views

urlpatterns = [
    path('',                                 views.accueil,           name='accueil'),
    path('match/<int:match_id>/compo/',      views.compo_equipe,      name='compo_equipe'),
    path('match/<int:match_id>/dashboard/',  views.dashboard_arbitre, name='dashboard_arbitre'),
    path('match/<int:match_id>/scoreboard/', views.score_board,       name='score_board'),
    path('match/<int:match_id>/legacy/',     views.tableau_bord,      name='tableau_bord'),

    # API JSON
    path('api/match/<int:match_id>/',        views.api_match_action,  name='api_match_action'),
    path('api/match/<int:match_id>/state/',  views.api_match_state,   name='api_match_state'),

    # Export Excel (feuille de match officielle FFN)
    path('match/<int:match_id>/export/',     views.export_excel,      name='export_excel'),
]
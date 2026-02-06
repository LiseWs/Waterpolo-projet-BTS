from django.urls import path
from . import views  # C'est cette ligne qui manquait dans l'autre fichier !

urlpatterns = [
    # Page 1 : Accueil (Création match)
    path('', views.accueil, name='accueil'),
    
    # Page 2 : Composition d'équipe (Nouveau !)
    path('match/<int:match_id>/compo/', views.compo_equipe, name='compo_equipe'),
    
    # Page 3 : Tableau de bord (Match en cours)
    path('match/<int:match_id>/', views.tableau_bord, name='tableau_bord'),
    
    # Action (Clic bouton)
    path('match/<int:match_id>/action/<int:participation_id>/<str:type_action>/', views.ajouter_action, name='ajouter_action'),

    path('match/<int:match_id>/arbitre/', views.dashboard_arbitre, name='dashboard_arbitre'),
    
    path('api/match/<int:match_id>/', views.api_match_action, name='api_match_action'),

    path('match/<int:match_id>/scoreboard/', views.score_board, name='score_board'),
    path('api/match/<int:match_id>/state/', views.api_match_state, name='api_match_state'),
]
from django.urls import path
from . import views

urlpatterns = [
    path('', views.accueil, name='accueil'),
    path('match/<int:match_id>/', views.tableau_bord, name='tableau_bord'),
    
    # Nouvelle URL pour gérer les clics (But, Faute, etc.)
    path('match/<int:match_id>/action/<int:participation_id>/<str:type_action>/', views.ajouter_action, name='ajouter_action'),
]
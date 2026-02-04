from django.contrib import admin
from django.urls import path, include  # <--- IL FAUT AJOUTER ", include" ICI

urlpatterns = [
    path('', views.accueil, name='accueil'),
    path('match/<int:match_id>/compo/', views.compo_equipe, name='compo_equipe'), # <--- NOUVELLE LIGNE
    path('match/<int:match_id>/', views.tableau_bord, name='tableau_bord'),
    path('match/<int:match_id>/action/<int:participation_id>/<str:type_action>/', views.ajouter_action, name='ajouter_action'),
]
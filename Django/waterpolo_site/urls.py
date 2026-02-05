from django.contrib import admin
from django.urls import path, include  # N'oublie pas l'import de 'include'

urlpatterns = [
    path('admin/', admin.site.urls),
    # Ici, on utilise include() pour rediriger vers le fichier de l'application
    path('', include('gestion.urls')), 
]
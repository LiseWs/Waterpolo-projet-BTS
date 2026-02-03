# Manuel d'Installation et d'Utilisation - Tableau de Score Water-Polo

## Prérequis
- Serveur web (Apache recommandé)
- PHP 7.0 ou supérieur
- MySQL 5.7 ou supérieur
- XAMPP (recommandé pour une installation facile)

## Installation

1. **Installation de XAMPP**
   - Téléchargez et installez XAMPP depuis [le site officiel](https://www.apachefriends.org/)
   - Assurez-vous que les services Apache et MySQL sont démarrés

2. **Configuration de la base de données**
   - Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
   - Créez une nouvelle base de données nommée `site_waterpolo`
   - Importez le fichier `site_waterpolo.sql` dans la base de données

3. **Installation des fichiers**
   - Placez tous les fichiers du projet dans le dossier `htdocs` de votre installation XAMPP
   - Le chemin devrait être : `C:\xampp\htdocs\scoreboard\`

## Utilisation

### Accès à l'application
1. Ouvrez votre navigateur web
2. Accédez à l'URL : `http://localhost/scoreboard/index.php`

### Fonctionnalités principales
- Affichage du tableau de score en temps réel
- Gestion des événements du match
- Enregistrement des buts
- Interface responsive adaptée aux différents écrans

### Structure des fichiers
- `index.php` : Page principale de l'application
- `styles.css` : Styles de l'interface
- `enregistrer_evenement.php` : Gestion des événements
- `enregistrer_but.php` : Gestion des buts
- `recuperer_joueurs.php` : Récupération des données des joueurs
- `images/` : Dossier contenant les images
- `sounds/` : Dossier contenant les sons

## Support
Pour toute question ou problème, veuillez contacter l'administrateur du système.

## Mise à jour
Les mises à jour seront disponibles sur le dépôt du projet. Assurez-vous de toujours avoir la dernière version pour bénéficier des dernières fonctionnalités et corrections de bugs. 
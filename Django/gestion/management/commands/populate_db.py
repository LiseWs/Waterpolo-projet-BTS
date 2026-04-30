from django.core.management.base import BaseCommand
from gestion.models import Equipe, Joueur


EQUIPES = [
    # ── Hautes-Pyrénées (65) ─────────────────────────────────────────────────
    {
        'nom': 'Tarbes Natation Waterpolo',
        'entraineur': 'Marc Lasserre',
        'joueurs': [
            ('Dupont',     'Thomas',   1, '65-0001', 2000),
            ('Bertrand',   'Lucas',    2, '65-0002', 1998),
            ('Moreau',     'Hugo',     3, '65-0003', 2001),
            ('Lefebvre',   'Antoine',  4, '65-0004', 1999),
            ('Martin',     'Clément',  5, '65-0005', 2002),
            ('Blanc',      'Paul',     6, '65-0006', 2000),
            ('Girard',     'Baptiste', 7, '65-0007', 1997),
            ('Robert',     'Victor',   8, '65-0008', 2001),
            ('Simon',      'Mathis',   9, '65-0009', 2003),
            ('Bonnet',     'Axel',    10, '65-0010', 1999),
            ('Faure',      'Théo',    11, '65-0011', 2002),
            ('Lacombe',    'Nathan',  12, '65-0012', 2000),
            ('Laborde',    'Romain',  13, '65-0013', 1998),
        ],
    },
    {
        'nom': 'CN Lourdes Waterpolo',
        'entraineur': 'Pierre Ducasse',
        'joueurs': [
            ('Souquet',    'Maxime',   1, '65-0101', 1999),
            ('Laffitte',   'Benjamin', 2, '65-0102', 2001),
            ('Dufour',     'Robin',    3, '65-0103', 2000),
            ('Cazenave',   'Arnaud',   4, '65-0104', 1997),
            ('Dumont',     'Loïc',     5, '65-0105', 2002),
            ('Estève',     'Gaël',     6, '65-0106', 1998),
            ('Fourcade',   'Tom',      7, '65-0107', 2003),
            ('Bouscatel',  'Kevin',    8, '65-0108', 2000),
            ('Artigau',    'Rémi',     9, '65-0109', 2001),
            ('Lacoste',    'Xavier',  10, '65-0110', 1999),
            ('Peyré',      'Samuel',  11, '65-0111', 2002),
            ('Bernadou',   'Quentin', 12, '65-0112', 1998),
            ('Miramont',   'Julien',  13, '65-0113', 2000),
        ],
    },
    {
        'nom': 'Bagnères Aquatique Club',
        'entraineur': 'Didier Cazaux',
        'joueurs': [
            ('Sarrat',     'Nicolas',  1, '65-0201', 2001),
            ('Ferrières',  'Clément',  2, '65-0202', 1999),
            ('Gaillard',   'Étienne',  3, '65-0203', 2000),
            ('Duffau',     'Romain',   4, '65-0204', 2002),
            ('Planel',     'Baptiste', 5, '65-0205', 1998),
            ('Soulès',     'Théo',     6, '65-0206', 2003),
            ('Lagarde',    'Hugo',     7, '65-0207', 2001),
            ('Cassagne',   'Damien',   8, '65-0208', 2000),
            ('Bordes',     'Florian',  9, '65-0209', 1997),
            ('Auriol',     'Maxime',  10, '65-0210', 2002),
            ('Vivier',     'Antoine', 11, '65-0211', 1999),
            ('Montané',    'Lucas',   12, '65-0212', 2001),
            ('Hourquet',   'Pierre',  13, '65-0213', 2000),
        ],
    },

    # ── Pyrénées-Atlantiques (64) ────────────────────────────────────────────
    {
        'nom': 'Pau Béarn Natation Waterpolo',
        'entraineur': 'Jean-Pierre Ortega',
        'joueurs': [
            ('Larrouy',    'Florian',  1, '64-0001', 2000),
            ('Barthas',    'Nicolas',  2, '64-0002', 2001),
            ('Loustalet',  'Cyril',    3, '64-0003', 1998),
            ('Castagnet',  'Damien',   4, '64-0004', 2002),
            ('Moles',      'Anthony',  5, '64-0005', 1999),
            ('Lalanne',    'Ethan',    6, '64-0006', 2003),
            ('Sarran',     'Hugo',     7, '64-0007', 2000),
            ('Etchart',    'Baptiste', 8, '64-0008', 2001),
            ('Maumus',     'Tom',      9, '64-0009', 1997),
            ('Cambot',     'Alexis',  10, '64-0010', 2002),
            ('Darricau',   'Kevin',   11, '64-0011', 2000),
            ('Laborde',    'Jérémy',  12, '64-0012', 1999),
            ('Forcade',    'Simon',   13, '64-0013', 2001),
        ],
    },
    {
        'nom': 'Olympique Bayonnais Waterpolo',
        'entraineur': 'Xabi Etxebarria',
        'joueurs': [
            ('Etcheberry',   'Pierre',    1, '64-0101', 1999),
            ('Larralde',     'Maxime',    2, '64-0102', 2001),
            ('Harambillet',  'Thomas',    3, '64-0103', 2000),
            ('Iriberry',     'Nicolas',   4, '64-0104', 1998),
            ('Harismendy',   'Romain',    5, '64-0105', 2002),
            ('Maitia',       'Victor',    6, '64-0106', 2000),
            ('Olçomendy',    'Baptiste',  7, '64-0107', 1997),
            ('Irazoqui',     'Samuel',    8, '64-0108', 2003),
            ('Harriet',      'Kevin',     9, '64-0109', 2001),
            ('Aguerre',      'Florian',  10, '64-0110', 1999),
            ('Hiriart',      'Alexandre',11, '64-0111', 2002),
            ('Etchegoin',    'Bastien',  12, '64-0112', 2000),
            ('Laxague',      'Julien',   13, '64-0113', 1998),
        ],
    },
    {
        'nom': 'Biarritz Olympique Waterpolo',
        'entraineur': 'Iker Larrea',
        'joueurs': [
            ('Durruty',      'Antoine',  1, '64-0201', 2000),
            ('Lacoste',      'Morgan',   2, '64-0202', 2002),
            ('Izard',        'Corentin', 3, '64-0203', 1999),
            ('Darrieutort',  'Théo',     4, '64-0204', 2001),
            ('Lafitte',      'Paul',     5, '64-0205', 1998),
            ('Mendibil',     'Alexis',   6, '64-0206', 2003),
            ('Castaings',    'Loïc',     7, '64-0207', 2000),
            ('Etchevers',    'Romain',   8, '64-0208', 2001),
            ('Harriague',    'Xavier',   9, '64-0209', 1997),
            ('Lauqué',       'Nathan',  10, '64-0210', 2002),
            ('Saint-Faust',  'Robin',   11, '64-0211', 2000),
            ('Cazaubon',     'Théodore',12, '64-0212', 1999),
            ('Detcheverry',  'Marc',    13, '64-0213', 2001),
        ],
    },
    {
        'nom': 'Anglet Natation Waterpolo',
        'entraineur': 'Laurent Mendiondo',
        'joueurs': [
            ('Bergouignan',  'Adrien',   1, '64-0301', 2001),
            ('Elissalde',    'Mateo',    2, '64-0302', 1999),
            ('Hegoburu',     'Lucas',    3, '64-0303', 2002),
            ('Jauréguiberry','Baptiste', 4, '64-0304', 2000),
            ('Labégorre',    'Clément',  5, '64-0305', 1998),
            ('Minvielle',    'Hugo',     6, '64-0306', 2003),
            ('Ospital',      'Simon',    7, '64-0307', 2001),
            ('Pochelu',      'Antoine',  8, '64-0308', 2000),
            ('Recalt',       'Thomas',   9, '64-0309', 1997),
            ('Sallaberry',   'Florian', 10, '64-0310', 2002),
            ('Tambourin',    'Alexis',  11, '64-0311', 1999),
            ('Urruti',       'Nicolas', 12, '64-0312', 2001),
            ('Zubiarrain',   'Kevin',   13, '64-0313', 2000),
        ],
    },
]


class Command(BaseCommand):
    help = 'Peuple la base avec les équipes de waterpolo du 65 et 64'

    def add_arguments(self, parser):
        parser.add_argument(
            '--reset',
            action='store_true',
            help='Supprime toutes les équipes et joueurs existants avant de recréer',
        )

    def handle(self, *args, **options):
        if options['reset']:
            Joueur.objects.all().delete()
            Equipe.objects.all().delete()
            self.stdout.write(self.style.WARNING('Base vidée.'))

        nb_equipes = 0
        nb_joueurs = 0

        for data in EQUIPES:
            equipe, created = Equipe.objects.get_or_create(
                nom=data['nom'],
                defaults={'entraineur': data['entraineur']},
            )
            if not created:
                self.stdout.write(f"  ⚠  Équipe déjà existante : {equipe.nom}")
            else:
                nb_equipes += 1
                self.stdout.write(f"  ✔  Équipe créée : {equipe.nom} (coach : {data['entraineur']})")

            for (nom, prenom, numero, licence, naissance) in data['joueurs']:
                _, j_created = Joueur.objects.get_or_create(
                    equipe=equipe,
                    numero_habituel=numero,
                    defaults={
                        'nom': nom,
                        'prenom': prenom,
                        'numero_licence': licence,
                        'annee_naissance': naissance,
                    },
                )
                if j_created:
                    nb_joueurs += 1

        self.stdout.write('')
        self.stdout.write(self.style.SUCCESS(
            f'Terminé — {nb_equipes} équipe(s) et {nb_joueurs} joueur(s) créés.'
        ))

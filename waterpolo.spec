# -*- mode: python ; coding: utf-8 -*-
"""
Spec PyInstaller — Water-Polo BTS
Commande : pyinstaller waterpolo.spec
"""
import os
from pathlib import Path

ROOT      = Path(os.path.abspath('.'))
DJANGO    = ROOT / 'Django'

a = Analysis(
    [str(ROOT / 'launcher.py')],
    pathex=[str(ROOT), str(DJANGO)],
    binaries=[],
    datas=[
        # Templates Django
        (str(DJANGO / 'gestion' / 'templates'), 'Django/gestion/templates'),
        # Fichiers statiques
        (str(DJANGO / 'gestion' / 'static'),    'Django/gestion/static'),
        # Base de données SQLite
        (str(DJANGO / 'db.sqlite3'),             'Django'),
        # Fichiers de configuration
        (str(DJANGO / 'waterpolo_site'),         'Django/waterpolo_site'),
        (str(DJANGO / 'manage.py'),              'Django'),
    ],
    hiddenimports=[
        'django',
        'django.template.backends.django',
        'django.contrib.staticfiles',
        'django.contrib.admin',
        'django.contrib.auth',
        'django.contrib.contenttypes',
        'django.contrib.sessions',
        'django.contrib.messages',
        'openpyxl',
        'gestion',
        'gestion.models',
        'gestion.views',
        'gestion.urls',
        'gestion.admin',
    ],
    hookspath=[],
    runtime_hooks=[],
    excludes=['tkinter', 'matplotlib', 'numpy'],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data)

exe = EXE(
    pyz, a.scripts, a.binaries, a.zipfiles, a.datas,
    name='WaterPolo-BTS',
    debug=False,
    strip=False,
    upx=True,
    console=False,          # Pas de console noire
    icon=None,              # Remplacer par 'icon.ico' si disponible
)

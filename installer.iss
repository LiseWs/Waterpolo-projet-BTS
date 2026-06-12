; ============================================================
;  WaterPolo BTS — Inno Setup Script
;  Prérequis : avoir exécuté build.ps1 d'abord (une seule fois)
;  Compiler  : ouvrir dans Inno Setup Compiler puis F9
; ============================================================

[Setup]
AppName=WaterPolo BTS
AppVersion=1.0
AppPublisher=BTS CIEL
DefaultDirName={localappdata}\WaterPolo-BTS
DefaultGroupName=WaterPolo BTS
OutputDir=Output
OutputBaseFilename=WaterPolo-BTS-setup
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=lowest
DisableWelcomePage=no
DisableDirPage=no
SetupIconFile=waterpolo.ico
UninstallDisplayIcon={app}\waterpolo.ico

[Languages]
Name: "french"; MessagesFile: "compiler:Languages\French.isl"

[Tasks]
Name: "desktopicon"; Description: "Créer un raccourci sur le Bureau"; GroupDescription: "Raccourcis :"

[Files]
; Application Django — sans venv (~50 Mo inutile) ni __pycache__
Source: "Django\*"; DestDir: "{app}\Django"; Excludes: "venv\*,__pycache__\*,*.pyc,*.pyo"; Flags: ignoreversion recursesubdirs createallsubdirs

; Python portable + toutes les dépendances (installées par build.ps1)
Source: "python-runtime\*"; DestDir: "{app}\python-runtime"; Flags: ignoreversion recursesubdirs createallsubdirs

; Scripts de lancement et installation
Source: "start.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "start.vbs"; DestDir: "{app}"; Flags: ignoreversion
Source: "migrate.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "install_deps.bat"; DestDir: "{app}"; Flags: ignoreversion

; Icône de l'application (générée par build.ps1)
Source: "waterpolo.ico"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
; Menu Démarrer
Name: "{group}\WaterPolo BTS"; Filename: "wscript.exe"; Parameters: """{app}\start.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\waterpolo.ico"; Comment: "Lancer l'application WaterPolo BTS"
Name: "{group}\Désinstaller"; Filename: "{uninstallexe}"

; Bureau (coché par défaut)
Name: "{autodesktop}\WaterPolo BTS"; Filename: "wscript.exe"; Parameters: """{app}\start.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\waterpolo.ico"; Comment: "Lancer WaterPolo BTS"; Tasks: desktopicon

[Run]
; 1. Migration de la base de données (Django, création du SQLite)
Filename: "{app}\migrate.bat"; StatusMsg: "Initialisation de la base de données..."; Flags: runhidden waituntilterminated

; 2. Proposer de lancer l'appli tout de suite après installation
Filename: "wscript.exe"; Parameters: """{app}\start.vbs"""; WorkingDir: "{app}"; Description: "Lancer WaterPolo BTS maintenant"; Flags: postinstall nowait skipifsilent

[UninstallDelete]
Type: filesandordirs; Name: "{app}\Django\db.sqlite3"
Type: filesandordirs; Name: "{app}\Django\media"
Type: filesandordirs; Name: "{app}\Django\__pycache__"

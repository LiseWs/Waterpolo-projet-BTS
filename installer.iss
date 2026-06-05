; ============================================================
;  WaterPolo BTS — Inno Setup Script
;  Prérequis : avoir exécuté build.ps1 d'abord
;  Compiler  : F9 dans Inno Setup Compiler
; ============================================================

[Setup]
AppName=WaterPolo BTS
AppVersion=1.0
AppPublisher=BTS SN
DefaultDirName={autopf}\WaterPolo-BTS
DefaultGroupName=WaterPolo BTS
OutputDir=Output
OutputBaseFilename=WaterPolo-BTS-setup
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
DisableWelcomePage=no
DisableDirPage=no

[Languages]
Name: "french"; MessagesFile: "compiler:Languages\French.isl"

[Tasks]
Name: "desktopicon"; Description: "Créer un raccourci sur le Bureau"; GroupDescription: "Raccourcis :"

[Files]
; Application Django
Source: "Django\*"; DestDir: "{app}\Django"; Flags: ignoreversion recursesubdirs createallsubdirs

; Python portable + dépendances (pip install déjà fait)
Source: "python-runtime\*"; DestDir: "{app}\python-runtime"; Flags: ignoreversion recursesubdirs createallsubdirs

; Lanceur principal
Source: "start.bat"; DestDir: "{app}"; Flags: ignoreversion

; Code Arduino (documentation / référence)
Source: "CODAGE\*"; DestDir: "{app}\CODAGE"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\WaterPolo BTS";       Filename: "{app}\start.bat"; WorkingDir: "{app}"; Comment: "Lancer l'application WaterPolo BTS"
Name: "{group}\Désinstaller";        Filename: "{uninstallexe}"
Name: "{autodesktop}\WaterPolo BTS"; Filename: "{app}\start.bat"; WorkingDir: "{app}"; Tasks: desktopicon; Comment: "Lancer WaterPolo BTS"

[Run]
Filename: "{app}\start.bat"; Description: "Lancer WaterPolo BTS maintenant"; Flags: postinstall nowait runminimized skipifsilent

[UninstallDelete]
Type: filesandordirs; Name: "{app}\Django\db.sqlite3"
Type: filesandordirs; Name: "{app}\Django\media"
Type: filesandordirs; Name: "{app}\Django\__pycache__"

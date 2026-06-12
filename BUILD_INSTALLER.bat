@echo off
cd /d "%~dp0"
echo =====================================================
echo  WaterPolo BTS - Creation de l'installeur complet
echo =====================================================
echo  Ce script :
echo   1. Installe Python portable + Django + waitress...
echo   2. Compile l'installeur .exe (tout est embarque)
echo.
echo  Connexion internet requise pour cette etape UNIQUEMENT.
echo  Le .exe final n'aura besoin de rien.
echo =====================================================
echo.

:: ── Etape 1 : preparer le runtime Python avec tous les packages ──────────────
echo [1/2] Preparation du runtime Python...
powershell -ExecutionPolicy Bypass -File "%~dp0build.ps1"
if errorlevel 1 (
    echo.
    echo ERREUR lors de la preparation du runtime Python.
    pause
    exit /b 1
)

:: ── Etape 2 : compiler l'installeur avec Inno Setup ─────────────────────────
echo.
echo [2/2] Compilation de l'installeur avec Inno Setup...

set ISCC=
if exist "%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe"    set ISCC="%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe"
if exist "%PROGRAMFILES(X86)%\Inno Setup 6\ISCC.exe"        set ISCC="%PROGRAMFILES(X86)%\Inno Setup 6\ISCC.exe"
if exist "%PROGRAMFILES%\Inno Setup 6\ISCC.exe"             set ISCC="%PROGRAMFILES%\Inno Setup 6\ISCC.exe"

if "%ISCC%"=="" (
    echo.
    echo Inno Setup introuvable automatiquement.
    echo Ouvrez installer.iss dans Inno Setup Compiler et appuyez sur F9.
    echo Le runtime Python est pret - juste la compilation manquante.
    pause
    exit /b 0
)

%ISCC% "%~dp0installer.iss"
if errorlevel 1 (
    echo.
    echo ERREUR lors de la compilation de l'installeur.
    pause
    exit /b 1
)

echo.
echo =====================================================
echo  SUCCES !
echo  Installeur : Output\WaterPolo-BTS-setup.exe
echo  Ce fichier fonctionne sur n'importe quel PC Windows
echo  sans installation supplementaire.
echo =====================================================
pause

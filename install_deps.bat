@echo off
cd /d "%~dp0"
echo =====================================================
echo  WaterPolo BTS - Installation des dependances Python
echo =====================================================
echo.

:: Etape 1 : activer "import site" dans le .pth (necessaire pour que pip fonctionne)
for %%F in (python-runtime\python3*._pth) do (
    powershell -NoProfile -Command ^
        "(Get-Content '%%F') -replace '^#import site','import site' | Set-Content '%%F' -Encoding ASCII" ^
        2>nul
    echo [1/2] Runtime Python configure : %%F
)

:: Etape 2 : installer les dependances via pip
echo [2/2] Installation de Django, waitress, openpyxl, pillow, pyserial...
python-runtime\python.exe -m pip install ^
    django==6.0.2 ^
    openpyxl==3.1.5 ^
    waitress ^
    pillow ^
    pyserial ^
    --no-warn-script-location ^
    --quiet

if errorlevel 1 (
    echo.
    echo ERREUR : l'installation a echoue.
    echo Verifiez votre connexion internet et relancez ce fichier.
    pause
    exit /b 1
)

echo.
echo OK - Toutes les dependances sont installees.
echo Vous pouvez fermer cette fenetre.
pause

@echo off
cd /d "%~dp0"

:: ── Lancer Waitress (serveur multi-thread) ───────────────────────────────────
set PYTHONPATH=%~dp0Django
start "" /B python-runtime\python.exe -m waitress ^
    --host=127.0.0.1 ^
    --port=8000 ^
    --threads=8 ^
    waterpolo_site.wsgi:application

:: Attendre 2 secondes que le serveur démarre
timeout /t 2 /nobreak >nul

:: ── Ouvrir en mode application (sans barre d'adresse) ────────────────────────
set URL=http://127.0.0.1:8000
set FLAGS=--app=%URL% --window-size=1400,900 --no-default-browser-check --disable-extensions --disable-infobars --disable-pinch --noerrdialogs

set C1="%PROGRAMFILES%\Google\Chrome\Application\chrome.exe"
set C2="%PROGRAMFILES(X86)%\Google\Chrome\Application\chrome.exe"
set E1="%PROGRAMFILES(X86)%\Microsoft\Edge\Application\msedge.exe"
set E2="%PROGRAMFILES%\Microsoft\Edge\Application\msedge.exe"

if exist %C1% ( start "" %C1% %FLAGS% & goto :fin )
if exist %C2% ( start "" %C2% %FLAGS% & goto :fin )
if exist %E1% ( start "" %E1% %FLAGS% & goto :fin )
if exist %E2% ( start "" %E2% %FLAGS% & goto :fin )
start %URL%

:fin

@echo off
cd /d "%~dp0"

set ARDUINO_PORT=

set PYTHONPATH=%~dp0Django

:: Lancer Waitress en arriere-plan, log des erreurs
start "" /B python-runtime\python.exe serve.py

timeout /t 4 /nobreak >nul

if not "%ARDUINO_PORT%"=="" (
    start "" /B python-runtime\python.exe serial_bridge.py --port %ARDUINO_PORT%
)

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

"""
Lanceur autonome — Water-Polo BTS
Lance Django en arrière-plan puis ouvre le navigateur sur localhost.
"""
import sys
import os
import subprocess
import threading
import time
import webbrowser
import signal

# ── Chemins ──────────────────────────────────────────────────────────────────
BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
DJANGO_DIR = os.path.join(BASE_DIR, 'Django')
MANAGE     = os.path.join(DJANGO_DIR, 'manage.py')
PORT       = 8000
URL        = f'http://127.0.0.1:{PORT}/'

# ── Lancer Django ─────────────────────────────────────────────────────────────
def start_django():
    env = os.environ.copy()
    env['PYTHONUNBUFFERED'] = '1'
    global django_proc
    django_proc = subprocess.Popen(
        [sys.executable, MANAGE, 'runserver', f'127.0.0.1:{PORT}', '--noreload'],
        cwd=DJANGO_DIR,
        env=env,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

django_proc = None

def stop_all(sig=None, frame=None):
    if django_proc:
        django_proc.terminate()
    sys.exit(0)

signal.signal(signal.SIGINT,  stop_all)
signal.signal(signal.SIGTERM, stop_all)

if __name__ == '__main__':
    print('Démarrage du serveur Water-Polo…')
    t = threading.Thread(target=start_django, daemon=True)
    t.start()

    # Attendre que Django soit prêt
    time.sleep(2)

    # Web Serial API nécessite Chrome ou Edge
    # Essayer Chrome d'abord, Edge ensuite, navigateur par défaut en dernier recours
    chrome_paths = [
        r'C:\Program Files\Google\Chrome\Application\chrome.exe',
        r'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        r'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
    ]
    launched = False
    for path in chrome_paths:
        if os.path.exists(path):
            subprocess.Popen([path, '--app=' + URL, '--window-size=1400,900'])
            launched = True
            break
    if not launched:
        webbrowser.open(URL)  # fallback
    print(f'Application ouverte sur {URL}')
    print('Fermez cette fenêtre pour arrêter le serveur.')

    try:
        django_proc.wait()
    except KeyboardInterrupt:
        stop_all()

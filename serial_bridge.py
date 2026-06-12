"""
serial_bridge.py — Pont automatique Django → Arduino → Afficheurs RS485

Démarre avec start.bat, tourne en tâche de fond.
Suit AUTOMATIQUEMENT le dernier match créé, sans intervention.

Protocole Arduino (4 octets) : [0xFF][H_chrono][L_chrono][shot]
"""

import argparse
import json
import sys
import time
import urllib.request

REFRESH_HZ  = 2     # 2 fois/seconde — suffit pour des afficheurs en secondes
RETRY_DELAY = 3     # secondes entre tentatives si le serveur est absent


def get_json(url):
    with urllib.request.urlopen(url, timeout=2) as r:
        return json.loads(r.read())


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--port',     default='COM3',                  help='Port série Arduino')
    ap.add_argument('--baudrate', type=int, default=115200,        help='Vitesse série')
    ap.add_argument('--url',      default='http://127.0.0.1:8000', help='URL serveur Django')
    args = ap.parse_args()

    try:
        import serial
    except ImportError:
        print("[ERREUR] Module pyserial manquant — pip install pyserial")
        sys.exit(1)

    # Ouvrir le port série
    ser = None
    while ser is None:
        try:
            ser = serial.Serial(args.port, args.baudrate, timeout=1)
            print(f"[OK] Arduino détecté sur {args.port}")
        except Exception as e:
            print(f"[ATTENTE] Port {args.port} indisponible ({e}) — réessai dans {RETRY_DELAY}s")
            time.sleep(RETRY_DELAY)

    interval      = 1.0 / REFRESH_HZ
    current_match = None
    last_chrono   = -1
    last_shot     = -1

    print(f"[OK] En attente du serveur sur {args.url} ...")

    while True:
        t0 = time.time()
        try:
            # 1. Détecter le match actif (le plus récent)
            info = get_json(f"{args.url}/api/latest-match/")
            mid  = info.get('match_id')

            if mid is None:
                time.sleep(RETRY_DELAY)
                continue

            if mid != current_match:
                current_match = mid
                last_chrono   = -1
                last_shot     = -1
                print(f"\n[Match #{mid} détecté — afficheurs actifs]")

            # 2. Lire les valeurs temps-réel
            data   = get_json(f"{args.url}/api/match/{current_match}/timer-serial/")
            chrono = int(data['chrono'])
            shot   = int(data['shot'])

            # 3. Envoyer à l'Arduino seulement si les valeurs ont changé
            if chrono != last_chrono or shot != last_shot:
                pkt = bytes([0xFF,
                             (chrono >> 8) & 0xFF,
                              chrono       & 0xFF,
                              shot         & 0xFF])
                ser.write(pkt)
                last_chrono = chrono
                last_shot   = shot

            mm, ss = chrono // 60, chrono % 60
            sys.stdout.write(f"\r  Match #{current_match} | Chrono {mm:02d}:{ss:02d}  Shot {shot:3d}s  ")
            sys.stdout.flush()

        except KeyboardInterrupt:
            print("\n[Arrêt]")
            ser.close()
            sys.exit(0)
        except Exception:
            pass  # Serveur pas encore prêt ou match non démarré — on réessaie silencieusement

        time.sleep(max(0.0, interval - (time.time() - t0)))


if __name__ == '__main__':
    main()

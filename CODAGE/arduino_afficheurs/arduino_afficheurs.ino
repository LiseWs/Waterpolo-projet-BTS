// ============================================================
//  WATERPOLO – Contrôleur RS485 double afficheur
//
//  PROTOCOLE USB (115200 bauds) – 3 octets par trame :
//    Octet 0 : valeur shot clock   (0‑30 s)    → afficheur 6 chiffres
//    Octet 1 : temps match high byte            → afficheur 4 chiffres
//    Octet 2 : temps match low byte             (valeur 0‑480 reconstruite)
//
//  Câblage RS485 :
//    Serial1 TX → DI du module MAX485
//    Serial1 RX → RO du module MAX485
//    Broche 3   → DE + RE du module MAX485 (pont les deux)
// ============================================================

#define MAX485_RE_DE 3

void setup() {
    pinMode(MAX485_RE_DE, OUTPUT);
    digitalWrite(MAX485_RE_DE, LOW);   // Mode réception par défaut

    Serial.begin(115200);              // Liaison USB ↔ navigateur web
    Serial1.begin(19200);              // RS485 (baud changé dynamiquement dans loop)
}

void loop() {
    // Attendre les 3 octets du protocole (envoyés en une seule écriture USB)
    if (Serial.available() >= 3) {

        byte octets[3];
        for (int i = 0; i < 3; i++) {
            octets[i] = Serial.read();
        }

        int valeurShotClock = octets[0];                          // 0‑30 s
        int valeurTimer     = ((int)octets[1] << 8) | octets[2]; // 0‑480 s

        // ── 1. Afficheur 6 chiffres (Shot Clock) ──────────────────
        //    19 200 bauds | adresse 0x01 | registre 0x0007
        Serial1.begin(19200);
        delayMicroseconds(100);
        envoyerTrameModbus(0x01, 0x00, 0x07, valeurShotClock);
        delay(15);

        // ── 2. Afficheur 4 chiffres (Temps match 8 min) ───────────
        //    9 600 bauds | adresse 0x01 | registre 0x0000
        Serial1.begin(9600);
        delayMicroseconds(100);
        envoyerTrameModbus(0x01, 0x00, 0x00, valeurTimer);
        delay(15);
    }
}

// Construit et envoie une trame Modbus RTU (fonction 06 – écriture registre)
void envoyerTrameModbus(byte adresse, byte regHigh, byte regLow, int valeur) {
    byte trame[8] = {
        adresse,
        0x06,
        regHigh,
        regLow,
        highByte(valeur),
        lowByte(valeur),
        0, 0            // CRC calculé ci-dessous
    };

    // Calcul CRC16 Modbus
    unsigned int crc = 0xFFFF;
    for (int pos = 0; pos < 6; pos++) {
        crc ^= (unsigned int)trame[pos];
        for (int i = 8; i != 0; i--) {
            if (crc & 0x0001) { crc >>= 1; crc ^= 0xA001; }
            else               { crc >>= 1; }
        }
    }
    trame[6] = lowByte(crc);
    trame[7] = highByte(crc);

    // Transmission physique
    digitalWrite(MAX485_RE_DE, HIGH);   // Bascule en émission
    delayMicroseconds(100);
    Serial1.write(trame, 8);
    Serial1.flush();                    // Attend la fin de l'envoi
    delayMicroseconds(100);
    digitalWrite(MAX485_RE_DE, LOW);    // Repasse en réception
}

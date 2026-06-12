/*
   === WATERPOLO MEGA – Afficheurs RS485 pilotés par le PC ===

   Basé sur le code multi-écrans existant.
   Le PC envoie 3 octets toutes les 500ms :
     [H_chrono]  octet fort du chrono en secondes (ex: 1 pour 300s)
     [L_chrono]  octet faible du chrono en secondes (ex: 44 pour 300+44=344s)
     [shot]      shot clock en secondes (0-255)

   Écran 1 (4 chiffres MM:SS) : adresse 0x01, Fonction 0x06, Registre 0x0000
   Écran 2 (Shot Clock)       : adresse 0x02, Fonction 0x10, Registre 0x0006
   Broche 8 → DE + RE du MAX485 commun
*/

const int controlPin = 8;  // Broche DE et RE du MAX485 commun

void setup() {
  pinMode(controlPin, OUTPUT);
  digitalWrite(controlPin, LOW);  // Mode RÉCEPTION par défaut

  Serial.begin(9600);   // USB ↔ PC (même vitesse que le code de référence)
  Serial1.begin(9600);  // RS485 Modbus commun aux 2 écrans

  // Initialisation des 2 écrans à zéro
  envoyerEcran4Chiffres(0);
  delay(100);
  envoyerEcranShotClock(0);

  Serial.println("=================================================");
  Serial.println("WaterPolo – En attente des valeurs du PC...");
  Serial.println("=================================================");
}

void loop() {
  // Attendre exactement 3 octets : [H_chrono][L_chrono][shot]
  if (Serial.available() >= 3) {
    byte hi   = Serial.read();
    byte lo   = Serial.read();
    byte shot = Serial.read();

    // Vider le tampon si des octets en retard sont présents
    while (Serial.available()) Serial.read();

    unsigned int chronoSec = ((unsigned int)hi << 8) | lo;

    // ── Écran 1 : chrono en format MMSS ───────────────────────────────
    int minutes  = chronoSec / 60;
    int secondes = chronoSec % 60;
    int formatMMSS = (minutes * 100) + secondes;

    Serial.print("-> Ecran 4Ch : ");
    if (minutes < 10) Serial.print("0");
    Serial.print(minutes); Serial.print(":");
    if (secondes < 10) Serial.print("0");
    Serial.print(secondes);
    Serial.print("  -> Shot : "); Serial.println(shot);

    envoyerEcran4Chiffres(formatMMSS);

    // Pause bus : laisse l'écran 1 traiter son paquet (même logique que l'original)
    delay(100);

    // ── Écran 2 : shot clock ───────────────────────────────────────────
    envoyerEcranShotClock((unsigned long)shot);
  }
}

// ── CRC16 Modbus (identique au code original) ────────────────────────────────

unsigned int calculerCRC(byte *trame, int longueur) {
  unsigned int crc = 0xFFFF;
  for (int pos = 0; pos < longueur; pos++) {
    crc ^= (unsigned int)trame[pos];
    for (int i = 8; i != 0; i--) {
      if ((crc & 0x0001) != 0) { crc >>= 1; crc ^= 0xA001; }
      else                     { crc >>= 1; }
    }
  }
  return crc;
}

// ── Écran 1 : 4 chiffres MM:SS (identique au code original) ─────────────────
// Adresse 0x01 | Fonction 0x06 | Registre 0x0000

void envoyerEcran4Chiffres(int valeurMMSS) {
  byte trame[8];

  trame[0] = 0x01;
  trame[1] = 0x06;
  trame[2] = 0x00;
  trame[3] = 0x00;
  trame[4] = (valeurMMSS >> 8) & 0xFF;
  trame[5] =  valeurMMSS       & 0xFF;

  unsigned int crc = calculerCRC(trame, 6);
  trame[6] = lowByte(crc);
  trame[7] = highByte(crc);

  digitalWrite(controlPin, HIGH);
  delayMicroseconds(50);
  Serial1.write(trame, 8);
  Serial1.flush();
  delayMicroseconds(50);
  digitalWrite(controlPin, LOW);
}

// ── Écran 2 : Shot Clock (identique au code original) ───────────────────────
// Adresse 0x02 | Fonction 0x10 | Registre 0x0006 | 2 registres (4 octets)

void envoyerEcranShotClock(unsigned long valeur) {
  byte trame[13];

  trame[0]  = 0x02;
  trame[1]  = 0x10;
  trame[2]  = 0x00;
  trame[3]  = 0x06;
  trame[4]  = 0x00;
  trame[5]  = 0x02;
  trame[6]  = 0x04;
  trame[7]  = (valeur >> 24) & 0xFF;
  trame[8]  = (valeur >> 16) & 0xFF;
  trame[9]  = (valeur >>  8) & 0xFF;
  trame[10] =  valeur        & 0xFF;

  unsigned int crc = calculerCRC(trame, 11);
  trame[11] = lowByte(crc);
  trame[12] = highByte(crc);

  digitalWrite(controlPin, HIGH);
  delayMicroseconds(50);
  Serial1.write(trame, 13);
  Serial1.flush();
  delayMicroseconds(50);
  digitalWrite(controlPin, LOW);
}

// ============================================================
//  WATERPOLO – Passerelle PC → Afficheur 6 chiffres
//  Protocole : 1 octet depuis le PC (valeur shot clock 0-30)
//  Brochage  : Serial=USB(PC) | Serial1 TX → afficheur 9600 bauds
// ============================================================

int valeurAffichee = -1;

void setup() {
  Serial.begin(115200);
  Serial1.begin(9600);
  // Affiche 0 au démarrage pour vérifier que l'écran répond
  afficherNombre6Chiffres(0, 0);
}

void loop() {
  if (Serial.available() >= 1) {
    int shotClock = Serial.read();

    // Vider le buffer si plusieurs octets en attente
    while (Serial.available()) Serial.read();

    // N'envoyer à l'afficheur que si la valeur a changé
    if (shotClock != valeurAffichee) {
      valeurAffichee = shotClock;
      afficherNombre6Chiffres((unsigned long)shotClock, 0);
    }
  }
}

void afficherNombre6Chiffres(unsigned long valeur, byte decimales) {
  byte trame[13];

  trame[0] = 0x01;
  trame[1] = 0x10;
  trame[2] = 0x00;
  trame[3] = 0x06;
  trame[4] = 0x00;
  trame[5] = 0x02;
  trame[6] = 0x04;

  trame[7]  = decimales & 0x0F;
  trame[8]  = (byte)((valeur >> 16) & 0xFF);
  trame[9]  = (byte)((valeur >> 8)  & 0xFF);
  trame[10] = (byte)(valeur         & 0xFF);

  unsigned int crc = 0xFFFF;
  for (int pos = 0; pos < 11; pos++) {
    crc ^= (unsigned int)trame[pos];
    for (int i = 8; i != 0; i--) {
      if ((crc & 0x0001) != 0) { crc >>= 1; crc ^= 0xA001; }
      else                      { crc >>= 1; }
    }
  }
  trame[11] = lowByte(crc);
  trame[12] = highByte(crc);

  Serial1.write(trame, 13);
  Serial1.flush();
}

#define MAX485_RE_DE 3

void setup() {
    pinMode(MAX485_RE_DE, OUTPUT);
    digitalWrite(MAX485_RE_DE, LOW);   // Mode réception par défaut
    Serial.begin(115200);              // USB ↔ navigateur web
    Serial1.begin(19200);             // RS485 (baud changé dynamiquement)
}

void loop() {
    // Attendre les 3 octets envoyés par le navigateur
    if (Serial.available() >= 3) {

        int shotClock  = Serial.read();                          // octet 0 : shot clock (0-30 s)
        int timerHigh  = Serial.read();                          // octet 1 : timer high byte
        int timerLow   = Serial.read();                          // octet 2 : timer low byte
        int tempsMatch = (timerHigh << 8) | timerLow;           // 0-480 s reconstitué

        // --- Afficheur 6 chiffres : Shot Clock ---
        // 19200 bauds | registre 0x0007
        Serial1.begin(19200);
        delayMicroseconds(50);
        envoyerTrameModbus(0x01, 0x00, 0x07, shotClock);
        delay(15);

        // --- Afficheur 4 chiffres : Temps de match (8 min) ---
        // 9600 bauds | registre 0x0000
        Serial1.begin(9600);
        delayMicroseconds(50);
        envoyerTrameModbus(0x01, 0x00, 0x00, tempsMatch);
        delay(15);
    }
}

void envoyerTrameModbus(byte adresse, byte regHigh, byte regLow, int valeur) {
    byte trame[8] = {adresse, 0x06, regHigh, regLow, highByte(valeur), lowByte(valeur), 0, 0};

    unsigned int crc = 0xFFFF;
    for (int pos = 0; pos < 6; pos++) {
        crc ^= (unsigned int)trame[pos];
        for (int i = 8; i != 0; i--) {
            if ((crc & 0x0001) != 0) { crc >>= 1; crc ^= 0xA001; }
            else                     { crc >>= 1; }
        }
    }
    trame[6] = lowByte(crc);
    trame[7] = highByte(crc);

    digitalWrite(MAX485_RE_DE, HIGH);
    delayMicroseconds(100);
    Serial1.write(trame, 8);
    Serial1.flush();
    delayMicroseconds(100);
    digitalWrite(MAX485_RE_DE, LOW);
}

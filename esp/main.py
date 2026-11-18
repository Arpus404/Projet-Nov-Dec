# main.py - à copier sur l'ESP32 ou l'ESP8266 (MicroPython)
# Il démarre tout seul à chaque mise sous tension, pas besoin de Thonny.
# Lit le capteur HW-611 (BMP280 : température + pression) en I2C
# et envoie chaque mesure sur le câble USB sous forme d'une ligne JSON.
#
# Branchement HW-611 :  VCC -> 3V3   GND -> GND
#   ESP32   : SDA -> GPIO21   SCL -> GPIO22
#   ESP8266 : SDA -> D2       SCL -> D1

import sys
import time
import json
import struct
from machine import Pin, SoftI2C

# Broches I2C selon la carte
if sys.platform == "esp32":
    SCL, SDA = 23, 22
else:  # esp8266 : D1 = GPIO5 (SCL), D2 = GPIO4 (SDA)
    SCL, SDA = 5, 4

NUM_CAPTEUR = 1
NOM_CAPTEUR = "Capteur BMP280"
INTERVALLE = 5  # secondes entre deux mesures


class BMP280:
    def __init__(self, i2c, addr):
        self.i2c = i2c
        self.addr = addr

        puce = self.lire_reg(0xD0, 1)[0]
        if puce not in (0x56, 0x57, 0x58, 0x60):  # 0x58 = BMP280, 0x60 = BME280
            raise OSError("puce inconnue : " + hex(puce))

        # Coefficients de calibration propres à chaque capteur
        (self.T1, self.T2, self.T3,
         self.P1, self.P2, self.P3, self.P4, self.P5,
         self.P6, self.P7, self.P8, self.P9) = struct.unpack("<HhhHhhhhhhhh", self.lire_reg(0x88, 24))

        self.i2c.writeto_mem(addr, 0xF5, bytes([0xA0]))  # pause 1 s entre mesures, sans filtre
        self.i2c.writeto_mem(addr, 0xF4, bytes([0x57]))  # mode normal, suréchantillonnage T x2 / P x16

    def lire_reg(self, reg, n):
        return self.i2c.readfrom_mem(self.addr, reg, n)

    def lire(self):
        d = self.lire_reg(0xF7, 6)
        adc_p = (d[0] << 12) | (d[1] << 4) | (d[2] >> 4)
        adc_t = (d[3] << 12) | (d[4] << 4) | (d[5] >> 4)

        # Formules de compensation de la datasheet Bosch
        var1 = (adc_t / 16384.0 - self.T1 / 1024.0) * self.T2
        var2 = ((adc_t / 131072.0 - self.T1 / 8192.0) ** 2) * self.T3
        t_fine = var1 + var2
        temperature = t_fine / 5120.0

        var1 = t_fine / 2.0 - 64000.0
        var2 = var1 * var1 * self.P6 / 32768.0
        var2 = var2 + var1 * self.P5 * 2.0
        var2 = var2 / 4.0 + self.P4 * 65536.0
        var1 = (self.P3 * var1 * var1 / 524288.0 + self.P2 * var1) / 524288.0
        var1 = (1.0 + var1 / 32768.0) * self.P1
        if var1 == 0:
            return temperature, 0

        p = 1048576.0 - adc_p
        p = (p - var2 / 4096.0) * 6250.0 / var1
        var1 = self.P9 * p * p / 2147483648.0
        var2 = p * self.P8 / 32768.0
        p = p + (var1 + var2 + self.P7) / 16.0

        return temperature, p / 100  # pression en hPa


def trouver_capteur(i2c):
    adresses = i2c.scan()
    print("Peripheriques I2C trouves :", [hex(a) for a in adresses])
    for addr in (0x76, 0x77):
        if addr in adresses:
            return BMP280(i2c, addr)
    raise OSError("BMP280 introuvable : verifie le branchement SDA/SCL")


i2c = SoftI2C(scl=Pin(SCL), sda=Pin(SDA), freq=100000)
print("Carte :", sys.platform)

capteur = None

while True:
    try:
        if capteur is None:
            capteur = trouver_capteur(i2c)
            time.sleep(1)  # laisse le temps à la première mesure

        temperature, pression = capteur.lire()

        print(json.dumps({
            "num_Capteur": NUM_CAPTEUR,
            "nom_Capteur": NOM_CAPTEUR,
            "gpio": SDA,
            "mesures": [
                {"lib_Mesure": "Temperature", "val_Mesure": round(temperature, 2), "unite_Mesurandes": "°C"},
                {"lib_Mesure": "Pression", "val_Mesure": round(pression, 2), "unite_Mesurandes": "hPa"}
            ]
        }))
    except Exception as e:
        print("Erreur capteur :", e)
        capteur = None  # on réessaiera de le trouver au prochain tour

    time.sleep(INTERVALLE)

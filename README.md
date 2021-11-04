# Knockaut MaxFlex Codetastur
Benötigt das Knockaut Alarmanlagemodul als übergeordnete Instanz.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Einrichten der Instanz](#3-einrichten-der-instanz)
4. [Statusvariablen und Profile](#4-statusvariablen-und-profile)

### 1. Funktionsumfang

* Liesst selbstständig der PIN vom Alarmanlagemodul aus
* Einstellbarkeit der ID
* Einstellbarkeit vom Zeitinterval für die Code Eingabe
* Sortierung im Alarmanlage Modul definiert die Tastenbelegung. Beispiel: Sortierung 0 = Taste 1, Sortierung 5 = Taste 6 (Taste 7 + 8 reserviert).

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0

### 3. Einrichten der Instanz

__Konfigurationsseite__:

Name                                 | Beschreibung
------------------------------------ | ---------------------------------
ID                                   | Auswahl der eingerichteten ID (Speicherpunkt im eGate)
TimerInterval                        | Zeitdauer für die Codeeingabe

### 4. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen. Profile werden keine benötigt.

##### Statusvariablen

Es werden automatisch folgende Statusvariablen angelegt.

Bezeichnung          | Typ     | Beschreibung
-------------------- | ------- | -----------
Code                 | Integer | Hier wird wärend der Eingabe der Code geschriebn.
Ist Code Ok?         | Boolean | Wird auf true gesetzt falls die Code eingabe erfolgreich war.
Aktueller Modus      | Integer | Stellt provisorisch den gewählten Modus ein.

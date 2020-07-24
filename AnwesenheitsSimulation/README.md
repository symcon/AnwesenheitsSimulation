# Anwesenheits-Simulation
Simuliert die Anwesenheit von Personen im Haushalt.
Das Modul bezieht dafür zufällig die Tagesdaten von einem der letzten 4 identischen Wochentagen. Sind an keinem dieser 4 Tage genug Schaltvorgänge geloggt, wird zufällig einer der letzten 30 Tage gewählt.  
Ist auch innerhalb dieser 30 Tage kein gültiger Tagesdatensatz vorhanden, ist keine Simulation möglich. Sollte keine Simulation möglich sein, wird dies als Nachricht in der Stringvariable "Simulationsquelle (Tag)" angezeigt.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Schalten von ausgewählten Aktoren/Variablen über geloggte Werte.
* Einstellbarkeit der benötigen durchschnittlichen Schaltvorgänge, bevor ein Tag als Quelle zur Simulation zugelassen wird.
* Ein-/Ausschaltbarkeit via WebFront-Button oder Skript-Funktion.
* Anzeige welcher Tag zur Simulation genutzt wird.
* Automatische Aktualisierung bei Tageswechsel.

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0

### 3. Software-Installation

* Über den Module Store das Modul Anwesenheits-Simulation installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
`https://github.com/symcon/AnwesenheitsSimulation`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'AnwesenheitsSimulation'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)
- Alle zu schaltenden Variablen müssen der Variablen Liste in der Instanzkonfiguration hinzugefügt werden.

__Konfigurationsseite__:

Name          | Beschreibung
------------- | ---------------------------------
Variablen     | Eine Liste der Variablen, welche für die Simulation der Anwesenheit genuzt werden sollen. Die hinzugefügten Variablen benötigen eine Aktion und müssen geloggt sein.
Mindestanzahl | Dies beschreibt die durchschnittliche Mindestanzahl von Variablenschaltungen aller ausgewählten Variablen, die vorhanden sein müssen.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                    | Typ       | Beschreibung
----------------------- | --------- | ----------------
Simulation aktiv        | Boolean   | Zeigt an ob ob die Simulation aktiviert ist oder nicht. True = Aktiviert; False = Deaktiviert;
Simulationsquelle (Tag) | String    | Der String beinhaltet das Datum, nach dem die Simulationsdaten ausgewählt wurden.
Simulationsvorschau     | String    | Zeigt eine Tabelle welche eine Übersicht über die zukünftigen Schaltvorgänge gibt.

Es werden keine zusätzlichen Profile benötigt.

### 6. WebFront

Über das WebFront kann die Simulation de-/aktiviert werden.  
Es wird zusätzlich die Information angezeigt, welcher Tag zur Simulation genutzt wird.  
Falls nicht genügend oder ungültige Daten vorhanden sind, wird dieses ebenfalls hier angezeigt.
Es wird eine Liste mit allen ausgewählten Variablen angezeigt, welche den aktuellen und nächsten Wert, sowie die Uhrzeit der Schaltung beinhaltet. 

### 7. PHP-Befehlsreferenz

`boolean AS_SetSimulation(integer $InstanzID, boolean $SetActive);`  
$SetActive aktiviert (true) oder deaktiviert (false) die Anwesenheits-Simulation mit der InstanzID $InstanzID.  
Die Funktion liefert keinerlei Rückgabewert.  

Beispiel:  
`AS_SetSimulation(12345, true);`

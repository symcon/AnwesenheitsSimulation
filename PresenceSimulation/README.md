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

[Link zur deutschen Dokumentation](https://www.symcon.de/de/service/dokumentation/modulreferenz/anwesenheitssimulation/)

[Link to the english documentation](https://www.symcon.de/en/service/documentation/module-reference/presence-simulation/)

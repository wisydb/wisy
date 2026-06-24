#!/bin/bash
# Immer ins Verzeichnis dieses Skripts wechseln. Wichtig fuer den Cron-Betrieb:
# alle relativen Pfade (main.py, Lockfile, Log, JSON, config.py, ./libs) beziehen
# sich auf das Arbeitsverzeichnis. Nur so benutzen alle Aufrufe dasselbe Lockfile
# -> die flock-Sperre gegen Parallel-Laeufe greift zuverlaessig.
cd "$(dirname "$0")" || exit 1

# Gesamte Ausgabe (stdout + stderr) ans Ende von run.log haengen. So muss im
# Cron-Feld nur "kursduplikate/run.sh" stehen (manche Hoster, z.B. Domainfactory,
# verbieten ">> ... 2>&1" im Cron-Befehl). "2>&1" leitet stderr dorthin, wohin
# stdout zeigt -> auch Fehler/Tracebacks landen im Log. Bewusst NICHT
# kursduplikate.log, da main.py dort bereits selbst hineinschreibt (sonst doppelt).
exec >> "run.log" 2>&1

# "-u" = ungepuffert: sonst sammelt Python die Ausgabe (Block-Pufferung bei
# Umleitung in eine Datei) und run.log bliebe waehrend des Laufs leer. Mit -u
# erscheinen prints/Fehler sofort. (Das strukturierte Live-Log steht ohnehin in
# kursduplikate.log, das main.py selbst zeilenweise schreibt.)
PYTHONPATH=./libs python3 -u main.py


<?php
//
// This file is created and parsed by sysloc.php!
// - Place exactly one statement per line, no additional linewraps.
// - Do not use any operators nor any function other than define().
// - Do not use other remarks than // at the beginning of a line.
//
define('_EXP_ADDBELONGINGTABLES',        "zugehörige Tabellen mitexportieren");
define('_EXP_ALLOWLINEBREAKSINFIELDS',   "Zeilenumbruch in Feldern erlauben");
define('_EXP_ATTR',                      "Attribute");
define('_EXP_ATTRASIDS',                 "als IDs exportieren, zus. Attribut-Tabellen schreiben");
define('_EXP_ATTRASTEXT',                "als Text exportieren");
define('_EXP_CSV',                       "CSV");
define('_EXP_DONTENCLFIELDS',            "Felder nicht einschließen");
define('_EXP_DONTESCFIELDS',             "Felder nicht escapen");
define('_EXP_DOWNLOAD',                  "Download");
define('_EXP_ERREXPORT',                 "Fehler beim Export: \$1");
define('_EXP_ERRSELECTTABLE',            "Bitte wählen Sie mindestens eine Tabelle aus.");
define('_EXP_EXPORTDONEMSG',             "Der Export wurde in \$1 Minuten erfolgreich abgeschlossen. Sie finden die Dateien in der Liste unten; dort stehen sie Ihnen noch \$2 Tage Verfügung.");
define('_EXP_EXPORTINPROGRESS',          "Export in das Format &quot;\$1&quot; in Arbeit. Dieser Vorgang kann mehrere Minuten dauern, bitte haben Sie Geduld.");
define('_EXP_FIELDENCL',                 "Felder einschliessen mit");
define('_EXP_FIELDESC',                  "Felder escapen mit");
define('_EXP_FIELDSEP',                  "Felder trennen mit");
define('_EXP_FILES',                     "Gespeicherte Exporte");
define('_EXP_FILESAREDELETEDAFTERNDAYS', "Die Dateien werden nach \$1 Tagen automatisch gelöscht - \$2Jetzt löschen\$3");
// $1 will be replaced by the number of days until the files get deleted, $2/$3 expand to start/end of the 'delete all' link
define('_EXP_FILESDELETE',               "Datei löschen");
define('_EXP_FILESDELETEALL',            "Alle Dateien löschen");
define('_EXP_FILESDELETEALLASK',         "Wirklich alle \$1 Dateien löschen?");
define('_EXP_FILESDELETEASK',            "Soll die Datei &quot;\$1&quot; wirklich gelöscht werden?");
define('_EXP_FILESDOWNLOAD',             "Datei herunterladen");
define('_EXP_FILESNOFILES',              "Keine Dateien");
define('_EXP_FINALIZE___',               "Export finalisieren, bitte warten...");
define('_EXP_FLATTABLESTRUCTURE',        "flache Tabellenstruktur");
define('_EXP_LINEBREAKS',                "Zeilenumbruch");
define('_EXP_MIX',                       "Mix-Datei");
define('_EXP_NRECORDSDONE___',           "\$1 - \$2 Datensätze bearbeitet...");
define('_EXP_PLEASEWAIT___',             "Bitte warten...");
define('_EXP_RECORDSQREMARK',            "z.B. <i>modified(today)</i> - wenn Sie die Anfrage leer lassen, werden alle Datenätze der Tabelle exportiert."
    . "<br><br>Weitere Beispiele:<br><br><code>anbieter(12345) AND freigeschaltet(2) AND notizen(TEXT(*01.01.18: Import (xy)*))</code>"
    . "<br>Alle Angebote des Anbieters 12345, die gesperrt sind und im Journal den Text '01.01.18: Import (xy)' enthalten."
    . "<br><br><code>anbieter(2322) AND freigeschaltet(1) AND durchfuehrung.beginn(timewindow(-45m-33m))</code>"
    . "<br>Alle Angebote des Anbieters 12345, die freigegeben sind und die zwischen 45 und 33 Monate alt sind.<br><br><br>"
    . "Aus der EQL-Dokumentation:<br><br>"
    . "Operatoren:<br>"
    . "and, or, xor, not, =, <>, >, <, >=, <= <br><br>"
    . "Vordefinierte Funktionen (andernfalls sind die 'Funktionen' = die Namen der Spalten der betroffenen Tabelle, die ein Einschänkungskriterium darstellen):<br>"
    . "not(), id(), date(), timewindow(), oneof(), allof(), exact(), fuzzy(), lazy(), job(), active(), created(), createdby(), modified(), modifiedby(), group(), rights() <br><br>"
    . "Funktionen können mit Klammern oder Operatoren genutzt werden, z.B.: <br>"
    . "title(important document)	[...]	id(12) <br>"
    . "title=important document		[...]   id>=10 and id <=20 <br><br>"
    . "Funktionen können auch geschachtelt werden: <br>"
    . "date.timewindow=today			date(timewindow(today)) <br><br>"
    );
define('_EXP_RECORDSQUERY',              "Anfrage");
define('_EXP_SIZE',                      "Größe");
define('_EXP_SOURCE',                    "Quellcode");
define('_EXP_SOURCEREMARK',              "Mit dem Quellcode-Export werden alle zu diesem Programm gehörigen Dateien in eine ZIP-Datei geschrieben. Hierzu gehören alle PHP- und JavaScript-Dateien, alle HTML- und CSS-Dateien, Bilder, SQL-Anweisungen und vieles mehr.<br /><br />Der Export kann dazu dienen, das Redaktionssystem zusammen mit den Portalen und der API auf einem neuen Server zu installieren.");
define('_EXP_STARTEXPORT',               "\$1-Export starten");
define('_EXP_TABLESTOEXPORT',            "Zu exportierende Tabellen");
define('_EXP_TABLETOEXPORT',             "Zu exportierende Tabelle");
define('_EXP_TITLESETTINGS',             "\$1-Export Einstellungen");
define('_EXP_WORDS',                     "Wortliste");
define('_EXP_WORDSREMARK',               "Es werden alle in den ausgewählten Tabellen und Datensäze auftauchenden Wörter gesammelt und jedes Wort einmal in eine Textdatei geschrieben. Das Resultat ist sozusagen der verwendetet Wortschatz der Datenbank.<br /><br />\n\nDie für die fehlertolerante Suche notwendigen Wortlisten werden bei dieser Gelegenheit ebenfalls auf den neuesten Stand gebracht.");
define('_IMP_CLICKTOPREPARE',            "Klicke, um die Datei für den Import vorzubereiten");
define('_IMP_UPLOADFILE',            "Datei hochladen (max. \$1):");
define('_IMP_UPLOADEMPTY',            "Die hochgeladene Datei ist leer (Dateigröße ist 0).");
define('_IMP_UPLOADNOMIXFILE',            "Die hochzuladende Datei muß die Dateierweiterung *.mix haben. Sie erzeugen solche Dateien z.B. unter &quot;Etc. &gt; Export&quot;.");
define('_IMP_UPLOADFILETOOBIG',		"Die hochgelandene Datei ist zu groß; die max. Dateigröße für Uploads beträgt \$1.");
define('_IMP_UPLOADNOFILE',            "Keine Datei ausgewählt.");
define('_IMP_UPLOADERRORCODE',            "Upload-Fehler \$1");
define('_IMP_DATEUPLOADED',            "Hochgeladen");
define('_IMP_UPLOADCANNOTCOPY',            "Kann \$1 nicht nach \$2 kopieren.");
define('_IMP_UPLOADOK',            "Die Datei \$1 wurde erfolgreich hochgeladen und steht nun zur Bearbeitung und/oder zum Import \$2 Tage zur Verfügung.");
define('_IMP_UPLOADFILEEXISTS',            "Die Datei wurde nicht hochgeladen, da bereits eine Datei unter dem Namen \$1 existiert. Bitte laden Sie die Datei unter einem anderen Namen hoch oder löschen Sie die bestehende Datei.");
define('_IMP_CONTENT',            "Inhalt");
define('_IMP_IMPORTINPROGRESS',            "Import der Mix-Datei \$1 in Arbeit. Dieser Vorgang kann mehrere Minuten dauern, bitte haben Sie Geduld.");
define('_IMP_IMPORTDONEMSG',            "Der Import wurde in \$1 Minuten erfolgreich abgeschlossen. Sie finden die importierten Datensätze nun ganz normal im Bestand. Weitere Informationen finden Sie im \$2Protokoll\$3.");
define('_IMP_OVERWRITE', 			"Datensätze im Bestand überschreiben");
define('_IMP_OVERWRITEOLDER', 			"wenn älter als in Mix-Datei");
define('_IMP_OVERWRITEALWAYS', 			"immer");
define('_IMP_OVERWRITENEVER', 			"nie");
define('_IMP_DELETE', 			"Datensätze im Bestand löschen");
define('_IMP_DELETEDELETED', 			"wenn in Mix-Datei nicht vorhanden");
define('_IMP_DELETENEVER', 			"nie");
define('_IMP_FURTHEROPTIONS', 			"Weitere Optionen");
define('_IMP_NEQUALERCORDS', 			"\$1 identische Datensätze");
define('_SYNC_JOBEDIT', 			"Synchronisierungsaufgabe bearbeiten");
define('_SYNC_JOBDELETE', 			"Synchronisierungsaufgabe löschen");
define('_SYNC_JOBDELETEASK', 			"Soll die Synchronisierungsaufgabe &quot;\$1&quot; wirklich gelöscht werden?");


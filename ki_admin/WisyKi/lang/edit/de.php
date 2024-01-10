<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
// This file contains the strings needed for the edit module.
//
define('_EDIT_ADDVALUESHINT',        "Geben Sie hier die hinzuzuf&uuml;genden Werte getrennt durch &quot;$1&quot; ein.");
// $1 is replaced by the seperator to use.
define('_EDIT_ADDVALUESHINTSINGLE',  "Geben Sie hier den (neuen) Wert ein.");
define('_EDIT_BY',                   "von");
define('_EDIT_CHANGETO',             "&Auml;ndern in:");
define('_EDIT_CALL_PHONE',           "&quot;$1&quot; jetzt anrufen");
define('_EDIT_COPY',                 "Duplizieren");
define('_EDIT_INSERTFROM',           "Einf&uuml;gen aus...");
define('_EDIT_CREATED',              "Erstellt");
define('_EDIT_DONOTCHANGE',          "Nicht &auml;ndern");
define('_EDIT_EDITORDER',            "Reihenfolge &auml;ndern");
define('_EDIT_EDITORDERTITLE',       "$1 Reihenfolge &auml;ndern");
// $1 will be replaced by the table name.
define('_EDIT_ERRCANNOTREADRECORD',  "Kann Datensatz nicht lesen.");
define('_EDIT_ERREMPTYATTRFIELD',    "Erforderliche Angabe fehlt.");
define('_EDIT_ERREMPTYFILEFIELD',    "Erforderliche Datei fehlt.");
define('_EDIT_ERREMPTYTEXTFIELD',    "Erforderliche Angabe fehlt.");
define('_EDIT_ERRENTERANUMBER',      "Bitte eine Zahl eingeben.");
define('_EDIT_ERRFIELDNOTUNIQUE',    "Eindeutige Angabe erforderlich, der Wert wird bereits vom Datensatz $1 verwendet.");
// $1 will be replaced by the record ID currently using the value.
define('_EDIT_ERRLINKSNOTSUPPORTED', "Verweise auf Tabelle &quot;$1&quot; sind nicht vorgesehen.");
// $1 will be replaced by the name of the table the user tried to link to.
define('_EDIT_ERRNOTREFERENCABLE',   "Das ausgew&auml;hlte Attribut (ID $1) ist nicht referenzierbar. &Auml;ndern Sie die Rechte f&uuml;r das Attribut oder w&auml;hlen Sie ein referenzierbares Attribut.");
define('_EDIT_ERRPASSWORDQUOTED',    "Das Passwort darf keine Anf&uuml;hrungszeichen enthalten.");
define('_EDIT_ERRRECORDNOTCOPIED',   "Datensatz wurde nicht kopiert.");
define('_EDIT_ERRRECORDNOTDELETED',  "Datensatz wurde nicht gel&ouml;scht.");
define('_EDIT_ERRRECORDNOTSAVED',    "Datensatz wurde nicht gespeichert.");
define('_EDIT_ERRREFERENCED',        "Kann den Datensatz nicht l&ouml;schen, da er von anderen referenziert wird.");
define('_EDIT_ERRUNKNOWNVALUE',      "Unbekannter oder nicht eindeutiger Wert &quot;$1&quot;.");
// Error for "unknown or not unique value". $1 will be replaced by the value.
define('_EDIT_ERRVALUENOTINMASK',    "Der Wert mu&szlig; dem Format $1 entsprechen.");
// $1 will be replaced by the mask, eg. dd.mm.yyyy or hh:mm:ss
define('_EDIT_ERRVALUENOTINRANGE',   "Der Wert mu&szlig; im Bereich $1..$2 liegen.");
// $1 / $2 will be replaced by the minimal / maximal value.
define('_EDIT_GRANTREF',             "Referenzieren");
define('_EDIT_GRANTSGROUP',          "Benutzergruppe");
define('_EDIT_GRANTSOTHER',          "Andere");
define('_EDIT_GRANTSOWNER',          "Eigent&uuml;mer/Ersteller");
define('_EDIT_REF_OPENINNEW',       "Referenzen in neuem Fenster anzeigen");
define('_EDIT_REF_OPENINTHIS',      "Referenzen in diesem Fenster anzeigen");
define('_EDIT_REF_1REFERENCE',       "$1 Referenz");
define('_EDIT_REF_NREFERENCES',      "$1 Referenzen");
define('_EDIT_MODIFIED',             "Ge&auml;ndert");
define('_EDIT_ONLYSECONDARYDATA',    "Dieser Datensatz enth&auml;lt lediglich Sekund&auml;rdaten. $1Die zugeh&ouml;rigen Prim&auml;rdaten bearbeiten$2.");
// $1 / $2 will be replaced by the start / end of a link to the primary data record.
define('_EDIT_REALLYDELETERECORD',   "Soll dieser Datensatz wirklich gel&ouml;scht werden?");
define('_EDIT_RECORDDELETED',        "Datensatz wurde gel&ouml;scht.");
define('_EDIT_RECORDNOTSAVED',       "Datensatz wurde nicht gespeichert.");
define('_EDIT_RECORDSAVED',          "Datensatz wurde gespeichert.");
define('_EDIT_RECORDUPTODATE',       "Datensatz aktuell, Speichern nicht notwendig.");
define('_EDIT_SHOWREFERENCES___',    "Referenzen anzeigen...");
define('_EDIT_SORTBOTTOM',           "Ganz nach unten");
define('_EDIT_SORTDOWN',             "Runter");
define('_EDIT_SORTTOP',              "Ganz nach oben");
define('_EDIT_SORTUP',               "Hoch");
define('_EDIT_STRUCTCREATE',         "$1 anlegen");
// Entry in the Structure Menu. $1 will be replaced by the name of the table to create.
define('_EDIT_STRUCTCREATE___',      "Neuer Bereich...");
define('_EDIT_STRUCTDELETE',         "Bereich l&ouml;schen");
define('_EDIT_STRUCTDELETEASK',      "Bereich l&ouml;schen?");
define('_EDIT_STRUCTDOWN',           "Bereich runter");
define('_EDIT_STRUCTUP',             "Bereich hoch");
define('_EDIT_USERRIGHTSTITLE',      "Rechte f&uuml;r $1 auf $2, ID $3, $4");
// $1 / $2 / $3 /$4 will be replaced by the user name / table name / record ID / record summary.
define('_EDIT_USERRIGHTSTITLE2',     "Rechte f&uuml;r $1 auf $2");
// $1 / $2 will be replaced by the user name / table name.
define('_EDIT_WARNEMPTYTEXTFIELD',   "Angabe empfohlen.");
define('_EDIT_WARNFIELDNOTUNIQUE',   "Eindeutige Angabe empfohlen, der Wert wird bereits vom Datensatz $1 verwendet.");
// $1 will be replaced by the record ID currently using the value.
define('_EDIT_WARNVALUENOTINMASK',   "Der Wert sollte dem Format $1 entsprechen.");
// $1 will be replaced by the mask, eg. dd.mm.yyyy or hh:mm:ss
define('_EDIT_YOURRIGHTS',           "Ihre Rechte");
define('_EDIT_INTERNALNAME',           "Interner Name");
define('_WAIT_ESCOCOM', "Dieser Vorgang kann laenger als 1 Minute dauern.");

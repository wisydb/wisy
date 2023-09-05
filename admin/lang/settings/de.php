<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
define('_SETTINGS_ALLOWMINUSPLUSWORD',      "<i>-Wort</i> und <i>+Wort</i> als Shortcut f&uuml;r <i>not(Wort)</i> und <i>exact(Wort)</i> erlauben");
define('_SETTINGS_BINACTIVEBINHINT',        "Zur Standard-Jobliste k&ouml;nnen Eintr&auml;ge durch einfachen Klick auf die B&uuml;roklammer im Hauptfenster hizugef&uuml;gt/entfernt werden.");
define('_SETTINGS_BINADDRECORDSFROM',       "Datens&auml;tze hinzuf&uuml;gen aus Jobliste:");
define('_SETTINGS_BINALLOW',                "Freigabe: andere Benutzer d&uuml;rfen diese Jobliste \$1 (wenn andere Benutzer diese Jobliste mitbenutzen sollen, m&uuml;ssen Sie ihnen den Namen &quot;\$2&quot; mitteilen; der Name kann von anderen Benutzern dann unter &quot;Neue Jobliste...&quot; eingegeben werden)");
define('_SETTINGS_BINALLOWE',               "bearbeiten");
define('_SETTINGS_BINALLOWN',               "nicht lesen");
define('_SETTINGS_BINALLOWR',               "lesen");
define('_SETTINGS_BINALLOWSHORT',           "(Freigabe: \$1)");
define('_SETTINGS_BINDELETE',               "Jobliste l&ouml;schen");
define('_SETTINGS_BINDELETEASK',            "Jobliste &quot;\$1&quot; mit insg. \$2 Eintr&auml;gen l&ouml;schen?");
define('_SETTINGS_BINEMPTY',                "Jobliste leeren");
define('_SETTINGS_BINEMPTYASK',             "Jobliste &quot;\$1&quot; mit insg. \$2 Eintr&auml;gen leeren?");
define('_SETTINGS_BINERRLISTNOTFOUND',      "Die Jobliste &quot;\$1&quot; wurde nicht gefunden, geben Sie den Namen genauso ein, wie Sie ihn vom entspr. Benutzer erhalten haben und achten Sie auch auf die Gro&szlig;- und Kleinschreibung.");
define('_SETTINGS_BINERRNAMEEXISTS',        "Konnte keine neue Jobliste unter dem Namen &quot;\$1&quot; anlegen, da eine Liste diesen Namens bereits existiert.");
define('_SETTINGS_BINISACTIVEBIN',          "(Standard-Jobliste)");
define('_SETTINGS_BINLISTBYOTHER',          "diese Jobliste wird vom Benutzer \$1 verwaltet und von Ihnen mitbenutzt; daher stehen hier nicht mehr Optionen zur Verf&uuml;gung");
// $1 will be replaced by the name of the other list owner
define('_SETTINGS_BINMSGACCESSCHANGED',     "Neue Zugriffsrechte f&uuml;r die Jobliste &quot;\$1&quot;: Andere Benutzer d&uuml;rfen diese Jobliste nun \$2.");
// $1: job list name -- $2 new access
define('_SETTINGS_BINMSGDELETED',           "Die Jobliste &quot;\$1&quot; wurde gel&ouml;scht.");
define('_SETTINGS_BINMSGEMPTYED',           "Die Jobliste &quot;\$1&quot; wurde geleert.");
define('_SETTINGS_BINMSGNEWLISTCREATED',    "Neue Jobliste mit dem Namen &quot;\$1&quot; erstellt.");
define('_SETTINGS_BINMSGRECORDSADDED',      "\$1 Datens&auml;tze aus der Jobliste &quot;\$2&quot; zur Jobliste &quot;\$3&quot; hinzugef&uuml;gt.");
// $1: the number of records added -- $2: the source job list -- $3: the destination job list
define('_SETTINGS_BINNAMEOFNEWLIST',        "Name der neuen Jobliste: \$1 (Sie k&ouml;nnen hier auch den Namen der Jobliste eines anderen Benutzers eingeben; diesen Namen erhalten Sie vom entsprechendem Benutzer in der Form &quot;Jobliste@Benutzer&quot;)");
define('_SETTINGS_BINNEW___',               "neue Jobliste...");
define('_SETTINGS_BINNORECORDS',            "keine Datens&auml;tze in dieser Jobliste");
define('_SETTINGS_BINNRECORDIN',            "\$1 Datensatz in \$2");
// $1 is replaces by the record count (singular), $2 is replaces by the table name
define('_SETTINGS_BINNRECORDSIN',           "\$1 Datens&auml;tze in \$2");
// $1 is replaces by the record count (plural), $2 is replaces by the table name
define('_SETTINGS_BINOPTIONS___',           "Optionen...");
define('_SETTINGS_BINREADONLY',             "(nur lesen)");
define('_SETTINGS_BINRECORDDOESNOTEXIST',   "ID \$1 - der Datensatz existiert nicht oder nicht mehr");
define('_SETTINGS_BINREMEMBERIN___',        "\$1 merken in Jobliste...");
// $1 is replaced by "1 record" or by "N records"
define('_SETTINGS_BINCLICKEDRECORD',        "angeklickten Datensatz");
define('_SETTINGS_BINRECORDSINVIEW',        "sichtbare Datens&auml;tze");
define('_SETTINGS_BINREMOVENONEXISTANT',    "nicht existierende Datens&auml;tze entfernen");
define('_SETTINGS_BINREMOVENONEXISTANTASK', "Sollen wiklich alle nicht existierenden Datens&auml;tze der Tabelle &quot;\$2&quot; aus der Jobliste &quot;\$1&quot; entfernt werden?");
// $1 the job list name -- $2 the table name
define('_SETTINGS_BINUSEASDEFAULTLIST',     "Jobliste als Standard-Jobliste verwenden");
define('_SETTINGS_BINUSENOLONGER',          "Jobliste nicht mehr mitbenutzen");
define('_SETTINGS_BINUSENOLONGERASK',       "M&ouml;chten Sie die Jop-Liste &quot;\$1&quot; wirklich nicht mehr mitbenutzen?");
define('_SETTINGS_COLUMNS',                 "Spalten");
define('_SETTINGS_DATE',                    "Datum");
define('_SETTINGS_DATESHOWCENTURY',         "Jahrhundert anzeigen");
define('_SETTINGS_DATESHOWRELDATE',         "relatives Datum anzeigen (Heute, Gestern usw.)");
define('_SETTINGS_DATESHOWSECONDS',         "Zeit inkl. Sekunden anzeigen");
define('_SETTINGS_DATESHOWTIME',            "Zeit anzeigen, wenn m&ouml;glich");
define('_SETTINGS_DATESHOWWEEKDAYS',        "Wochentage anzeigen");
define('_SETTINGS_FILTER',                  "Filter");
define('_SETTINGS_FILTERGRPHINT',           "Nur Datens&auml;tze der markierten Benutzergruppen werden angezeigt");
define('_SETTINGS_FONT',                    "Schrift");
define('_SETTINGS_FUNCTIONADDVALUES',       "Direkteingabe");
define('_SETTINGS_FUNCTIONEDITOR',          "Editor Popup");
define('_SETTINGS_GRANTSOTHER',             "Andere");
define('_SETTINGS_HTMLEDITOR',              "HTML Editor");
define('_SETTINGS_HTMLEDITORNONE',          "Keiner");
define('_SETTINGS_INPUTFIELDS',             "Eingabefelder");
define('_SETTINGS_INPUTFIELDSSIZEHINT',     "Gr&ouml;&szlig;e der Eingabefelder");
define('_SETTINGS_MONOSPACED',              "Nicht Proportional");
define('_SETTINGS_MULTIPLELINEINPUT',       "Mehrzeilige Eingabefelder");
define('_SETTINGS_ONNOACCESS',              "Bei fehlenden Rechten");
define('_SETTINGS_ONNOACCESSHIDE',          "Datens&auml;tze ausblenden");
define('_SETTINGS_ONNOACCESSSHOWHINT',      "Hinweis anzeigen");
define('_SETTINGS_PROPORTIONAL',            "Proportional");
define('_SETTINGS_PWCURRPASSWORD',          "Momentanes Passwort");
define('_SETTINGS_PWERRINVALID',            "Das momentane Passwort ist nicht korrekt. Bitte versuchen Sie es erneut.");
define('_SETTINGS_PWERRLENGTH',             "Das neue Passwort mu&szlig; mindestens \$1 Zeichen haben. Bitte versuchen Sie es erneut.");
// $1 is replaced by the min. number of characters.
define('_SETTINGS_PWERRQUOTES',             "Das neue Passwort darf keine Anf&uuml;hrungszeichen enthalten. Bitte versuchen Sie es erneut.");
define('_SETTINGS_PWERRSPACES',             "Das neue Passwort darf keine Leerzeichen am Anfang oder am Ende haben. Bitte versuchen Sie es erneut.");
define('_SETTINGS_PWERRUNIQUE',             "Das neue Passwort und die Wiederholung sind nicht identisch. Bitte versuchen Sie es erneut.");
define('_SETTINGS_PWNEWPASSWORD',           "Neues Passwort");
define('_SETTINGS_PWREPEATPASSWORD',        "Neues Passwort wiederholen");
define('_SETTINGS_PWSUGGESTION',            "Vorschlag");
define('_SETTINGS_PWTITLE',                 "Passwort &auml;ndern");
define('_SETTINGS_REMARK1',                 "Einstellungen nicht permanent");
define('_SETTINGS_REMARK2',                 "Die Einstellungen gelten nur f&uuml;r diese Sitzung. Wenden Sie sich an den Supervisor um einen permanenten Zugriff zu erhalten.");
define('_SETTINGS_ROWCURSOR',               "Datensatz unter Maus hervorheben");
define('_SETTINGS_USETABS',                	"Datens&auml;tze mit Tabs unterteilen");
define('_SETTINGS_SEARCH',                  "Suche");
define('_SETTINGS_SEARCHEDITCOLUMNS___',    "Spalten bearbeiten...");
define('_SETTINGS_SEARCHEDITFIELDS___',     "Felder bearbeiten...");
define('_SETTINGS_SEARCHFIELDS',            "Felder");
define('_SETTINGS_SEARCHINPUT',             "Eingabe");
define('_SETTINGS_SEARCHSETTINGSFOR',       "Einstellungen zur Suche in \$1");
define('_SETTINGS_SEARCHSHOWFUZZYINFO',     "alternative W&ouml;rter bei fehlertoleranter Suche anzeigen");
define('_SETTINGS_SEARCHSHOWPCODE',         "Interpretation der Suchanfrage anzeigen, falls keine Datens&auml;tze gefunden wurden");
define('_SETTINGS_SEPERATOR',               "Trennzeichen");
define('_SETTINGS_SHOWCHANGES',             "Zeige DF-&Auml;nderungen in Kursmaske");
define('_SETTINGS_SHOWEMPTY',               "Zeige leere DF-Felder in &Uuml;bersichten als 'k.A.'<br>('0' bleibt aber '0'. Suche: nicht nach 'k.A.' sondern nach '') ");
define('_SETTINGS_SHOWLOGO',                "Logo anzeigen");
define('_SETTINGS_SKIN',                    "Skin");
define('_SETTINGS_DIALOGTITLE',             "Ihre pers&ouml;nlichen Einstellungen");
define('_SETTINGS_TIPSNTRICKS',             "Tips und Tricks nach Login anzeigen");
define('_SETTINGS_TOOLBAR',                 "Funktionen");
define('_SETTINGS_USEJOBLISTS',             "Joblisten verwenden");
define('_SETTINGS_VIEW',                    "Ansicht");
?>

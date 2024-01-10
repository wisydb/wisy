<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
// This file contains strings for the module "System Localize".
//
define('_SYSLOC_DELENTRY',         "Eintrag l&ouml;schen");
define('_SYSLOC_DELENTRYHINT',     "Wollen Sie den Eintrag &quot;$1&quot; wirklich in ALLEN Sprachen l&ouml;schen? Normalerweise ist das L&ouml;schen von Eintr&auml;gen allenfalls vom Systementwickler notwendig.");
define('_SYSLOC_DELLANG',          "Sprache l&ouml;schen");
define('_SYSLOC_DELLANGHINT',      "Wollen Sie wirklich die Sprache &quot;$1&quot; inkl. ALLER Eintr&auml;ge l&ouml;schen?");
define('_SYSLOC_EDITFROMVIEW',     "Aus Ansicht bearbeiten");
define('_SYSLOC_EDITENTRY',        "Eintrag bearbeiten");
define('_SYSLOC_ERRIDINUSE',       "Die angegebene ID wird bereits von einem anderen Datensatz verwendet.");
define('_SYSLOC_ERRIDINVALID',     "Die angegebene ID ist ung&uuml;ltig.");
define('_SYSLOC_ERRIDMISSING',     "Bitte geben Sie die ID an.");
define('_SYSLOC_ERRW',             "Kein Schreibzugriff f&uuml;r die Sprache &quot;$1&quot; dieses Moduls. &Auml;ndern Sie die Zugriffsrechte der Datei &quot;$2&quot; um die Sprache zu editieren.");
// $1 will be replaced by the language ID ("de", "en", "fr", "gr" etc.), $2 will be replaced by the name and the path of the language file.
define('_SYSLOC_ERRWDIR',          "Kein Schreibzugriff. &Auml;ndern Sie die Zugriffsrechte des Verzeichnisses &quot;$1&quot; um die Sprache anzulegen.");
// The placeholder $1 will be replaced by the path the file should be created in.
define('_SYSLOC_FURTHERLANGUAGES', "Weitere Sprachen");
define('_SYSLOC_INITNEWLANG',      "Eintr&auml;ge initialisieren mit");
define('_SYSLOC_MODULE',           "Modul");
define('_SYSLOC_NEWENTRY',         "Neuer Eintrag");
define('_SYSLOC_NEWENTRYHINT',     "Geben Sie hier die ID des neuen Eintrages in der Form &quot;_OK&quot;, &quot;_CANCEL&quot; etc. an. Der neue Eintrag wird f&uuml;r <b>alle</b> Sprachen des Moduls angelegt. <b>Normalerweise ist das Anlegen neuer Eintr&auml;ge allenfalls vom Systementwickler notwendig.</b> Im folgenden Feld k&ouml;nnen Sie ausßerdem Hinweise zur Verwendung des Eintrages angeben. Diese sollten in englischer Sprache angegeben werden.");
define('_SYSLOC_NEWLANG',          "Neue Sprache");
define('_SYSLOC_NEWLANGHINT',      "Geben Sie hier die ID der neuen Sprache gem&auml;&szlig; $1 und $2 in der Form &quot;de&quot;, &quot;en&quot; etc. an. Die neue Sprache wird <b>alle</b> Eintr&auml;ge des Moduls enthalten; wenn Sie m&ouml;chten, k&ouml;nnen Sie diese mit dem folgenden Feld initialisieren.");
// The placeholders  will be replaces by a link to RFC ($1) and ISO ($2).
define('_SYSLOC_NUMREC',           "Das Sprachmodul enth&auml;lt insgesamt $1 Eintr&auml;ge in $2 Sprachen.");
// The placeholders will be replaced by the number of records ($1) and the number of languages ($2).
define('_SYSLOC_REMARK',           "Anmerkung");
define('_SYSLOC_RENAMEENTRY',      "Eintrag umbenennen");
define('_SYSLOC_RENAMELANG',       "Sprache umbenennen");
define('_SYSLOC_TRANSLMISSING',    "Fehlende &Uuml;bersetzung");
?>
<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
// This file contains strings for the module "System Localize".
//
define('_SYSLOC_DELENTRY',         "Delete Entry");
define('_SYSLOC_DELENTRYHINT',     "Do you really want to delete the entry &quot;$1&quot; in ALL languages? Normally only system devolpers should delete entries.");
define('_SYSLOC_DELLANG',          "Delete Language");
define('_SYSLOC_DELLANGHINT',      "Do you really want to delete the language &quot;$1&quot; including ALL Entries belonging to this language?");
define('_SYSLOC_EDITENTRY',        "Edit Entry");
define('_SYSLOC_EDITFROMVIEW',     "Edit from view");
define('_SYSLOC_ERRIDINUSE',       "The ID provided is already in use for another record.");
define('_SYSLOC_ERRIDINVALID',     "The ID provided is invalid.");
define('_SYSLOC_ERRIDMISSING',     "Please enter the ID.");
define('_SYSLOC_ERRW',             "No write access for the language &quot;$1&quot; of this module. Change the rights of the file &quot;$2&quot; to edit the language");
// $1 will be replaced by the language ID ("de", "en", "fr", "gr" etc.), $2 will be replaced by the name and the path of the language file.
define('_SYSLOC_ERRWDIR',          "No write access. Change the rights for the directory &quot;$1&quot; to create the language.");
// The placeholder $1 will be replaced by the path the file should be created in.
define('_SYSLOC_FURTHERLANGUAGES', "Further Languages");
define('_SYSLOC_INITNEWLANG',      "Init entries with");
define('_SYSLOC_MODULE',           "Module");
define('_SYSLOC_NEWENTRY',         "New Entry");
define('_SYSLOC_NEWENTRYHINT',     "Enter the ID for the new entry here. Use the form &quot;_OK&quot;, &quot;_CANCEL&quot; etc. The new entry will be created in <b>all</b> languages of the module. <b>Normally only system devolpers should created new entries.</b>");
define('_SYSLOC_NEWLANG',          "New Language");
define('_SYSLOC_NEWLANGHINT',      "Enter the ID of the new language using $1 and $2 - eg.&quot;en&quot; or &quot;de&quot;. The new language will contain <b>all</b> entries of the module. You can predefine the entries using the next control.");
// The placeholders  will be replaces by a link to RFC ($1) and ISO ($2).
define('_SYSLOC_NUMREC',           "The language module contains $1 entries in $2 languages.");
// The placeholders will be replaced by the number of records ($1) and the number of languages ($2).
define('_SYSLOC_REMARK',           "Note");
define('_SYSLOC_RENAMEENTRY',      "Re-name entry");
define('_SYSLOC_RENAMELANG',       "Re-name language");
define('_SYSLOC_TRANSLMISSING',    "Translation missing");
?>
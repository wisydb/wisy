<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
define('_SETTINGS_ALLOWMINUSPLUSWORD',      "allow <i>-Word</i> and <i>+Word</i> as shortcuts for <i>not(Word)</i> and <i>exact(Word)</i>");
define('_SETTINGS_BINACTIVEBINHINT',        "Data can be entered in/removed from default job list by simply clicking on the paper-clip in the main window");
define('_SETTINGS_BINADDRECORDSFROM',       "add records from job list:");
define('_SETTINGS_BINALLOW',                "sharing: other users may \$1 this job-list (if other users should access this job list, you have to tell them the name &quot;\$2&quot;; other users can access the list using &quot;new job list...&quot; then)");
define('_SETTINGS_BINALLOWE',               "edit");
define('_SETTINGS_BINALLOWN',               "not read");
define('_SETTINGS_BINALLOWR',               "read");
define('_SETTINGS_BINALLOWSHORT',           "(sharing: \$1)");
define('_SETTINGS_BINDELETE',               "delete job list");
define('_SETTINGS_BINDELETEASK',            "Delete job list &quot;\$1&quot; with \$2 entries?");
define('_SETTINGS_BINEMPTY',                "empty job list");
define('_SETTINGS_BINEMPTYASK',             "Empty job list &quot;\$1&quot; with \$2 entries?");
define('_SETTINGS_BINERRLISTNOTFOUND',      "Cannot find the job list &quot;\$1&quot;. Enter the name exactly as you got it from the user, also regard the case!");
define('_SETTINGS_BINERRNAMEEXISTS',        "Could not create a new job list with tne name &quot;\$1&quot;. The name is already in use.");
define('_SETTINGS_BINISACTIVEBIN',          "(default job list)");
define('_SETTINGS_BINLISTBYOTHER',          "this job list is owned by \$1, you're only using it; for this reason there are no more options here");
// $1 will be replaced by the name of the other list owner
define('_SETTINGS_BINMSGACCESSCHANGED',     "New access rights for the job list &quot;\$1&quot;: Other users may \$2 this job list now.");
// $1: job list name -- $2 new access
define('_SETTINGS_BINMSGDELETED',           "Job list &quot;\$1&quot; deleted.");
define('_SETTINGS_BINMSGEMPTYED',           "All records removed from &quot;\$1&quot;.");
define('_SETTINGS_BINMSGNEWLISTCREATED',    "New job list with the name &quot;\$1&quot; created.");
define('_SETTINGS_BINMSGRECORDSADDED',      "Added \$1 records from job list &quot;\$2&quot; to job list &quot;\$3&quot;.");
// $1: the number of records added -- $2: the source job list -- $3: the destination job list
define('_SETTINGS_BINNAMEOFNEWLIST',        "name of new list: \$1 (you can also enter the name of a job list from another user here; you get this name from the user in the form &quot;job list@user&quot;)");
define('_SETTINGS_BINNEW___',               "new job list...");
define('_SETTINGS_BINNORECORDS',            "no records in this job list");
define('_SETTINGS_BINNRECORDIN',            "\$1 record in \$2");
// $1 is replaced by the record count (singular), $2 is replaces by the table name
define('_SETTINGS_BINNRECORDSIN',           "\$1 records in \$2");
// $1 is replaced by the record count (plural), $2 is replaces by the table name
define('_SETTINGS_BINOPTIONS___',           "Options...");
define('_SETTINGS_BINREADONLY',             "(read-only)");
define('_SETTINGS_BINRECORDDOESNOTEXIST',   "ID \$1 - the record does not exist");
define('_SETTINGS_BINREMEMBERIN___',        "Save \$1 in the job list...");
// $1 is replaced by "1 record" or by "N records"
define('_SETTINGS_BINCLICKEDRECORD',        "clicked record");
define('_SETTINGS_BINRECORDSINVIEW',        "all records in view");
define('_SETTINGS_BINREMOVENONEXISTANT',    "remove non-existant records");
define('_SETTINGS_BINREMOVENONEXISTANTASK', "Really remove all non-existant records in the table &quot;\$2&quot; from the job list &quot;\$1&quot;?");
// $1 the job list name -- $2 the table name
define('_SETTINGS_BINUSEASDEFAULTLIST',     "use job list as default job list");
define('_SETTINGS_BINUSENOLONGER',          "don't use Job-List any longer");
define('_SETTINGS_BINUSENOLONGERASK',       "Really stop using the job list &quot;\$1&quot;?&quot;\$1&quot;");
define('_SETTINGS_COLUMNS',                 "Columns");
define('_SETTINGS_DATE',                    "Date");
define('_SETTINGS_DATESHOWCENTURY',         "show century");
define('_SETTINGS_DATESHOWRELDATE',         "show relative date (today, yesterday etc.)");
define('_SETTINGS_DATESHOWSECONDS',         "show time incl. seconds");
define('_SETTINGS_DATESHOWTIME',            "if possible, show time");
define('_SETTINGS_DATESHOWWEEKDAYS',        "show weekdays");
define('_SETTINGS_FILTER',                  "Filter");
define('_SETTINGS_FILTERGRPHINT',           "Only records of groups marked below are shown");
define('_SETTINGS_FONT',                    "Font");
define('_SETTINGS_FUNCTIONADDVALUES',       "Direct input");
define('_SETTINGS_FUNCTIONEDITOR',          "Editor Pop-up");
define('_SETTINGS_GRANTSOTHER',             "Other");
define('_SETTINGS_HTMLEDITOR',              "HTML Editor");
define('_SETTINGS_HTMLEDITORNONE',          "None");
define('_SETTINGS_INPUTFIELDS',             "Input Fields");
define('_SETTINGS_INPUTFIELDSSIZEHINT',     "Input Fields Size");
define('_SETTINGS_MONOSPACED',              "Monospaced");
define('_SETTINGS_MULTIPLELINEINPUT',       "Multiple Line Input");
define('_SETTINGS_ONNOACCESS',              "If rights are missing");
define('_SETTINGS_ONNOACCESSHIDE',          "Hide Records");
define('_SETTINGS_ONNOACCESSSHOWHINT',      "Show hint");
define('_SETTINGS_PROPORTIONAL',            "Proportional");
define('_SETTINGS_PWCURRPASSWORD',          "Current Password");
define('_SETTINGS_PWERRINVALID',            "The current password is not correct. Please try again.");
define('_SETTINGS_PWERRLENGTH',             "The new password must have at least \$1 characters. Please try again.");
// $1 is replaced by the min. number of characters.
define('_SETTINGS_PWERRQUOTES',             "The new password must not contain quotes. Please try again.");
define('_SETTINGS_PWERRSPACES',             "The new password must not have spaces at the beginning/the end. Please try again.");
define('_SETTINGS_PWERRUNIQUE',             "The new password and the repeated password are not identical. Please try again.");
define('_SETTINGS_PWNEWPASSWORD',           "New Password");
define('_SETTINGS_PWREPEATPASSWORD',        "Repeat New Password");
define('_SETTINGS_PWSUGGESTION',            "Suggestion");
define('_SETTINGS_PWTITLE',                 "Change Password");
define('_SETTINGS_REMARK1',                 "Settings not persistent");
define('_SETTINGS_REMARK2',                 "The settings are only valid for this session. Contact the supervisor to get a persistent account.");
define('_SETTINGS_ROWCURSOR',               "Record-cursor");
define('_SETTINGS_USETABS',                 "Divide records using tabs");
define('_SETTINGS_SEARCH',                  "Search");
define('_SETTINGS_SEARCHEDITCOLUMNS___',    "edit columns...");
define('_SETTINGS_SEARCHEDITFIELDS___',     "Edit Fields...");
define('_SETTINGS_SEARCHFIELDS',            "Fields");
define('_SETTINGS_SEARCHINPUT',             "Input");
define('_SETTINGS_SEARCHSETTINGSFOR',       "Search settings for \$1");
define('_SETTINGS_SEARCHSHOWFUZZYINFO',     "show alternative words on fuzzy search");
define('_SETTINGS_SEARCHSHOWPCODE',         "show interpretation of the query if no records were found");
define('_SETTINGS_SEPERATOR',               "Seperator");
define('_SETTINGS_SHOWCHANGES',             "Show DF changes in mask");
define('_SETTINGS_SHOWLOGO',                "show logo");
define('_SETTINGS_SKIN',                    "Skin");
define('_SETTINGS_DIALOGTITLE',             "Your personal Settings");
define('_SETTINGS_TIPSNTRICKS',             "Show Tips'n'Tricks after login");
define('_SETTINGS_TOOLBAR',                 "Functions");
define('_SETTINGS_USEJOBLISTS',             "Use job lists");
define('_SETTINGS_VIEW',                    "View");
?>

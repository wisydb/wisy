<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
// This file contains the strings needed for the edit module.
//
define('_EDIT_ADDVALUESHINT',        "Enter the values to add here. Please use &quot;$1&quot; as seperator.");
// $1 is replaced by the seperator to use.
define('_EDIT_ADDVALUESHINTSINGLE',  "Enter the (new) value here.");
define('_EDIT_BY',                   "by");
define('_EDIT_CALL_PHONE',           "call &quot;$1&quot; now");
define('_EDIT_CHANGETO',             "Change to:");
define('_EDIT_COPY',                 "Duplicate");
define('_EDIT_INSERTFROM',           "Insert from...");
define('_EDIT_CREATED',              "Created");
define('_EDIT_DONOTCHANGE',          "Do not alter");
define('_EDIT_EDITORDER',            "Change Order");
define('_EDIT_EDITORDERTITLE',       "Change $1 Order");
// $1 will be replaced by the table name.
define('_EDIT_ERRCANNOTREADRECORD',  "Cannot read record.");
define('_EDIT_ERREMPTYATTRFIELD',    "Essential attribute missing.");
define('_EDIT_ERREMPTYFILEFIELD',    "Essential file missing.");
define('_EDIT_ERREMPTYTEXTFIELD',    "Essential attribute missing.");
define('_EDIT_ERRENTERANUMBER',      "Please enter a number.");
define('_EDIT_ERRFIELDNOTUNIQUE',    "Attribute is not unique, this value is already in use in Record $1.");
// $1 will be replaced by the record ID currently using the value.
define('_EDIT_ERRLINKSNOTSUPPORTED', "Links not possible to &quot;$1&quot;.");
// $1 will be replaced by the name of the table the user tried to link to.
define('_EDIT_ERRNOTREFERENCABLE',   "The selected attribute (ID $1) is not referencable. Please change the rights for the attribute or select another.");
define('_EDIT_ERRPASSWORDQUOTED',    "Password may not contain quotation marks.");
define('_EDIT_ERRRECORDNOTCOPIED',   "Record not copied.");
define('_EDIT_ERRRECORDNOTDELETED',  "Record not deleted.");
define('_EDIT_ERRRECORDNOTSAVED',    "Record not saved.");
define('_EDIT_ERRREFERENCED',        "Cannot delete record as it has been referenced by other users.");
define('_EDIT_ERRUNKNOWNVALUE',      "Unknown or indefinite value &quot;$1&quot;.");
// Error for "unknown or not unique value". $1 will be replaced by the value.
define('_EDIT_ERRVALUENOTINMASK',    "The value must match the mask $1.");
// $1 will be replaced by the mask, eg. dd.mm.yyyy or hh:mm:ss
define('_EDIT_ERRVALUENOTINRANGE',   "Value must be within the range $1..$2.");
// $1 / $2 will be replaced by the minimal / maximal value.
define('_EDIT_GRANTREF',             "Reference");
define('_EDIT_GRANTSGROUP',          "Group");
define('_EDIT_GRANTSOTHER',          "Other");
define('_EDIT_GRANTSOWNER',          "Owner/Creator");
define('_EDIT_REF_OPENINNEW',       "Show references in new window");
define('_EDIT_REF_OPENINTHIS',      "Show references in this window");
define('_EDIT_REF_1REFERENCE',       "$1 reference");
define('_EDIT_REF_NREFERENCES',      "$1 references");
define('_EDIT_MODIFIED',             "Modified");
define('_EDIT_ONLYSECONDARYDATA',    "This record contains only secondary data. $1Edit the related primary record$2.");
// $1 / $2 will be replaced by the start / end of a link to the primary data record.
define('_EDIT_REALLYDELETERECORD',   "Do you really want to delete this record?");
define('_EDIT_RECORDDELETED',        "Record deleted.");
define('_EDIT_RECORDNOTSAVED',       "Record not saved");
define('_EDIT_RECORDSAVED',          "Record saved.");
define('_EDIT_SHOWREFERENCES___',    "Show References...");
define('_EDIT_SORTBOTTOM',           "At foot of page");
define('_EDIT_SORTDOWN',             "Down");
define('_EDIT_SORTTOP',              "At the top");
define('_EDIT_SORTUP',               "Up");
define('_EDIT_STRUCTCREATE',         "Create $1");
// Entry in the Structure Menu. $1 will be replaced by the name of the table to create.
define('_EDIT_STRUCTCREATE___',      "New area...");
define('_EDIT_STRUCTDELETE',         "Delete area");
define('_EDIT_STRUCTDELETEASK',      "Delete area?");
define('_EDIT_STRUCTDOWN',           "Area below");
define('_EDIT_STRUCTUP',             "Area up");
define('_EDIT_USERRIGHTSTITLE',      "Rights for $1 to $2, ID $3, $4");
// $1 / $2 / $3 /$4 will be replaced by the user name / table name / record ID / record summary.
define('_EDIT_USERRIGHTSTITLE2',     "Rights for $1 to $2");
// $1 / $2 will be replaced by the user name / table name.
define('_EDIT_WARNEMPTYTEXTFIELD',   "Attribute recommented.");
define('_EDIT_WARNFIELDNOTUNIQUE',   "Unique attribute recommented, this value is already in use in Record $1.");
// $1 will be replaced by the record ID currently using the value.
define('_EDIT_WARNVALUENOTINMASK',   "The value should match the mask $1.");
// $1 will be replaced by the mask, eg. dd.mm.yyyy or hh:mm:ss
define('_EDIT_YOURRIGHTS',           "Your rights");
define('_EDIT_INTERNALNAME',           "Internal name");
define('_WAIT_ESCOCOM', "This process can take more than 1 minute.");
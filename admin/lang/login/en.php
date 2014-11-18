<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
define('_LOGIN_ERR',                "The log-in name and password combination has not been recognised. Please try again. Remember, the log-in name and the password are case-sensitive. Check the \"shift-lock\" key as well.");
define('_LOGIN_HELP',               "To log into the system enter your name or the abbreviation given to you by the supervisor into the  <b>Login Name</b>field.\n\nThen enter your password into the  <b>Password</b>field. If this is the first time you log in, the supervisor will have allocated the password;  afterwards you can choose your own password.\n\nNote: both are case senistive, &quot;Login Name&quot; and &quot;Password&quot;.\n\nUse the field <b>Language</b> to select the language to be used by the programme\'s user interface. The default language is the language setting for your browser.\n\nYou can store the log-in data in your computer by means of a cookie, using the option <b>memorise data</b>. But remember that unauthorised people can then also log in. <b>Saving log-in data is always insecure!</b>\n\nFinally, click on <b>OK</b>. The programme then will check your log-in name and your password. If you are successful, you will gain access to the ystem - if not, you will get an error message and then you can try again.\n\nIf you still have problems, contact the Subervisor who gave you the log-in name and your password.");
define('_LOGIN_FEATUREMISSING',  	"To access this system, please enable the following features: $1");
// $1: missing feature
define('_LOGIN_PASSWORDCHANGED',    "The password has been changed. Please login using the new password.");
define('_LOGIN_SECURE',             "Secure");
define('_LOGIN_TITLE',              "Log in");
define('_LOGIN_INSECURE',           "Not secure");
define('_LOGIN_WARNING',            "One of the following situations were detected:\n\n - You forgot to log out after your last session\n - You are already logged in\n - Someone tried and failed to log in using your name ($1)\n\nNever forget to use the log-out function when you end a session - you can access it by using the symbol in the top right-hand corner of the menu.");
define('_LOGIN_WELCOME',            "Welcome $1! Your last login was $2.");
// $1: user name, $2: last login date
?>
<?php



require('config/view_tools.inc.php');


// ist der Kurs gesperrt o.ä.? Dann - und nur dann - den Parameter showinactive=1 anhaengen
// (wird der Parameter immer angehaengt, wird die Weitergabe der URL unnoetig erschwert)
// (ein erster versuch, war, statt des Parameters den REFERRER zu verwenden - dieser wird aber i.d.R. nicht über Domaingrenzen hinweg übertragen)
$addparam = '';
$db = new DB_Admin;
$db->query("SELECT freigeschaltet FROM kurse WHERE id=".intval($_REQUEST['id']));
$db->next_record();
if( intval($db->f('freigeschaltet'))==2 ) 
	$addparam = '?showinactive=1';



?>
<html>
<head>
<meta http-equiv="refresh" content="0; URL=<?php echo $url ?>k<?php echo intval($_REQUEST['id']) . $addparam ?>" />
</head>
<body>
</body>
</html>
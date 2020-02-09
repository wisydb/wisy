<?php


require_once('functions.inc.php');
require_once('eql.inc.php');


if( !isset($_SESSION['g_session_index_sql']['anbieter']) )
{
	echo "Keine Anbieter ausgew�hlt?";
	exit();
}


// get BCC list
$eql2sql = new EQL2SQL_CLASS('anbieter');
$eql = $_SESSION['g_session_index_eql']['anbieter']==''? '*' : $_SESSION['g_session_index_eql']['anbieter'];

$sql = $eql2sql->eql2sql($eql, 'pflege_email', acl_get_sql(ACL_READ, 0, 1, 'anbieter'), 'id');


$allEmails = array();
$db = new DB_Admin;
$db->query($sql);
while( $db->next_record() )
{
	$email = trim($db->fs('pflege_email'));
	if( $email )
	{
		$allEmails[$email] = 1;
	}
}

$bcc = "";
foreach(array_keys($allEmails) as $email)
{
	$bcc .= $bcc==""? "" : ", ";
	$bcc .= $email;
}


// get TO

$to = "";
$db->query("SELECT email FROM user WHERE id=".$_SESSION['g_session_userid']);
if( $db->next_record() )
{
	$to = $db->fs('email');
}


// render page

$site->pageStart(array('popfit'=>1));

	$site->skin->submenuStart();
		echo 'Rundschreiben an alle ausgew�hlten Anbieter';
	$site->skin->submenuBreak();
		echo "&nbsp;";
	$site->skin->submenuEnd();
	
	$site->skin->workspaceStart();

		echo "Um Ihr Email-Programm zu starten und an alle<br />ausgew�hlten Anbieter eine Email zu senden, klicken Sie bitte ";
		echo '<a href="mailto:' .$to. '?bcc=' .$bcc /*urlencode is not understood by outlook*/. '"><b>hier</b></a>.<br /><br />';
		echo "(Einige Versionen von Outlook unterst�tzen nicht die �bergabe beliebig vieler Email-Adressen; sollte Outlook nicht starten, versuchen Sie eine Auswahl mit weniger Anbietern)";
	
	$site->skin->workspaceEnd();
	
	$site->skin->buttonsStart();
		form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
	$site->skin->buttonsEnd();
		
$site->pageEnd();





?>

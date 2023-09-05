<?php


require_once('functions.inc.php');
require_once('eql.inc.php');


if( !isset($_SESSION['g_session_index_sql']['anbieter']) )
{
	echo "Keine Anbieter ausgew&auml;hlt?";
	exit();
}

$to = "";           // contact email address
$batch_limit = 249; // max. emails to be sent per batch
$trenner = ";";


// get BCC list
$eql2sql = new EQL2SQL_CLASS('anbieter');
$eql = !isset($_SESSION['g_session_index_eql']['anbieter']) || $_SESSION['g_session_index_eql']['anbieter'] == '' ? '*' : $_SESSION['g_session_index_eql']['anbieter'];

$sql = $eql2sql->eql2sql($eql, 'anspr_email', acl_get_sql(ACL_READ, 0, 1, 'anbieter'), 'id');

$allBatches = array();
$allEmails = array();
$db = new DB_Admin;
$db->query($sql);

while( $db->next_record() ) {
	$email = trim($db->fs('anspr_email'));
	if(strlen($email) > 5) {
		array_push($allEmails, $email);
	}
}

$allEmailsFull = $allEmails;
$allEmails = array_unique($allEmailsFull); // elimnates duplicate email addresses, but leaves empty values in those cases
$allEmails = array_values(array_filter($allEmails)); // remove empty elements and re-index filtered array
$batch_cnt = ceil(count($allEmails) / $batch_limit);

// all batches
for($x = 0; $x < $batch_cnt; $x++) {
	$bcc = "";
	for( $i = 0; $i < $batch_limit; $i++) {
		$index = ($x*$batch_limit)+$i;
		if($index+1 <= count($allEmails) && $allEmails[$index]) {
		    $bcc .= $bcc==""? "" : $trenner." ";
			$bcc .= $allEmails[$index];
		}
	}
	array_push($allBatches, $bcc);
}


// render page

$site->pageStart(array('popfit'=>1));

	$site->skin->submenuStart();
		echo 'Rundschreiben an alle ausgew&auml;hlten Anbieter - Kunden-EMails';
	$site->skin->submenuBreak();
		echo "&nbsp;";
	$site->skin->submenuEnd();
	
	$site->skin->workspaceStart();
	
	echo "<br><b>Die \"AN:\" - Mailadresse muss noch im Mailprogramm definiert werden!</b><br>Bitte die blauen Links/&Uuml;berschriften verwenden - nicht Copy&Paste der Liste. So landen die E-Mailadressen automatisch im BCC-Feld.<br><br>Statistik:<br><br>"
					."E-Mail-Adressen insgesamt: ".count($allEmailsFull).", nach Duplikat-Entfernung: ".count($allEmails)."<br>"
					."Max. Anzahl an E-mails, die auf einmal gesendet werden d&uuml;rfen: ".$batch_limit."<br>";
					if(count($allEmails) % $batch_limit == 0)
						echo "=> ".$batch_cnt." Tranche(n) (".count($allEmails). " / ".$batch_limit.")<br>";
					else
						echo "=> ".$batch_cnt." Tranche(n) (".count($allEmails). " / ".$batch_limit." = ".($batch_cnt-1)." Rest ".(count($allEmails) % $batch_limit).")<br>";
					echo "<br><br>";
	
	for($i = 0; $i < count($allBatches); $i++) {
	    echo	($i+1).") ".'<a href="mailto:' .$to. '?bcc=' .$allBatches[$i] /*urlencode is not understood by outlook*/. '"><b>E-Mail erstellen mit '.(substr_count($allBatches[$i], $trenner)+1).' E-Mail-Adressen</b></a><br>'
						.'<textarea style="width: 100%; height: 30%;">'.$allBatches[$i].'</textarea><br><br>';
	}
	
	echo "<small>(Einige Versionen von Outlook unterst&uuml;tzen nicht die &Uuml;bergabe beliebig vieler Email-Adressen; sollte Outlook nicht starten, versuchen Sie eine Auswahl mit weniger Anbietern)</small>";
	
	echo "<hr>SQL-Abfrage:<br>".$sql."</hr><br><br>";
	
	$site->skin->workspaceEnd();
	
	$site->skin->buttonsStart();
		form_button('cancel', htmlconstant('_CANCEL'), 'window.close();return false;');
	$site->skin->buttonsEnd();
		
$site->pageEnd();


?>
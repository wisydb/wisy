<?php

require('config/view_tools.inc.php');

$db = new DB_Admin;

$db->query("SELECT stichwort FROM stichwoerter WHERE id=" . (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null) );
$db->next_record();
$stichwort = $db->fs('stichwort');



?>
<html>
	<head>
		<meta http-equiv="refresh" content="0; URL=<?php echo $url ?>search?q=<?php echo urlencode($stichwort) ?>"/>
	</head>
	<body>
	</body>
</html>
<?php



require('config/view_tools.inc.php');

$db = new DB_Admin;

$db->query("SELECT thema FROM themen WHERE id=".intval($_REQUEST['id']));
$db->next_record();
$thema = $db->fs('thema');

$thema = str_replace(',', ' ', $thema);
$thema = str_replace('  ', ' ', $thema);



?>
<html>
<head>
<meta http-equiv="refresh" content="0; URL=<?php echo $url ?>search?q=<?php echo urlencode($thema) ?>"/>
</head>
<body>
</body>
</html>

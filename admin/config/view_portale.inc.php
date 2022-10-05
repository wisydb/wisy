<?php

require('config/view_tools.inc.php');

$db = new DB_Admin;

$db->query("SELECT domains FROM portale WHERE id=" . (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null) );
$db->next_record();

$domain = strtr($db->fs('domains'), ';,', '  ');
$domain = explode(' ', $domain);

$url = add_sandbox_prefix('http://' . $domain[0]);



?>
<html>
	<head>
		<meta http-equiv="refresh" content="0; URL=<?php echo $url ?>"/>
	</head>
	<body>
	</body>
</html>
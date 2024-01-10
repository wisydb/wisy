<?php

require('config/view_tools.inc.php');

?>
<html>
	<head>
		<meta http-equiv="refresh" content="0; URL=<?php echo $url ?>a<?php echo (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null); ?>"/>
	</head>
	<body>
	</body>
</html>
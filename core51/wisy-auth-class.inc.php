<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 5.0
 ******************************************************************************
 Einfacher Passwortschutz für ein Portal, z.B. für Testzwecke.
 
 s.a. http://php.net/manual/de/features.http-auth.php ... was in der Praxis
 nicht klappt, wenn PHP als CGI wie unter domainfactory läuft (was durchaus
 performant ist - und flexibel!)
 ******************************************************************************
 Achtung: Geringe Sicherheit!
 
 Das Passwort wird im Klartext in der Datenbank gespeichert und im Cookie
 nur durch ein einfaches md5() ohne salt etc. geschützt!
 ******************************************************************************/



class WISY_AUTH_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function check()
	{
		if( isset($_REQUEST['wisyauth1']) || isset($_REQUEST['wisyauth2']) )
		{
			$enteredHash = md5($_REQUEST['wisyauth1'] . $_REQUEST['wisyauth2']);
			setcookie('wisyauth', $enteredHash); // only use a session cookie to allow the user to remove the cookie by closing the window.
		}
		else
		{
			$enteredHash = $_COOKIE['wisyauth'];
		}
		
		$correctHash = md5($this->framework->iniRead('auth.user', '') . $this->framework->iniRead('auth.password', ''));
		if( $enteredHash != $correctHash )
		{
			?>
				<html>
					<head>
						<title>Passwort erforderlich!</title>
					</head>
					<body>
						<form action="" method="post" style="text-align: center; margin: 3em;">
							Um fortzufahren, geben Sie bitte einen g&uuml;tigen Benutzernamen und ein g&uuml;ltiges Passwort ein: <br />
							Benutzername: <input name="wisyauth1" type="text" value="<?php echo htmlspecialchars($_REQUEST['wisyauth1']) ?>" /><br />
							Passwort: <input name="wisyauth2" type="password" value="" /><br />
							<input type="submit" value=" OK " />
						</form>
					</body>
				</html>
			<?php
			exit();
		}
	}

};

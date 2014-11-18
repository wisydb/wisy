<?php

class SYNC_LOGNABORT_CLASS
{
	function __construct()
	{
	}
	
	function abort_on_bad_apikey()
	{
		if( !isset($_REQUEST['apikey']) )
		{
			$this->_abort('Kein Passwort für Cron/Export/Synchronisierung; bitte übergeben Sie das Passwort als Parameter &apikey='); // this should not happen on valid implementations
		}
		
		if( regGet('export.apikey', '<no-password>' /*do not allow empty password by setting a non-empty default*/, 'template')!=$_REQUEST['apikey'] )
		{
			$this->_abort('Falsches Passwort für Cron/Export/Synchronisierung; bitte überprüfen Sie die Einstellungen zu Etc./User/template/Einstellungen/export.apikey auf den beteiligten Servern.');
		}
	}

	private function _abort($msg)
	{
		$logwriter = new LOG_WRITER_CLASS; // only log the error if there is no user-interface involved - otherwise the error is printed and everything is fine.
		$logwriter->addData('msg', $msg);
		$logwriter->addData('url', ($_SERVER['SERVER_PORT']==443?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); 
		$logwriter->addData('ip', $_SERVER['REMOTE_ADDR']);
		$logwriter->addData('browser', $_SERVER['HTTP_USER_AGENT']);
		
		$logwriter->log('user', '', 0, 'loginfailed');
		die($msg);
	}
}


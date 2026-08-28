<?php

/*=============================================================================
confirming (new) role texts, can be used eg. for "AGB"
===============================================================================

file:	
	roleconfirm.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none

=============================================================================*/


function roleconfirm_after_login($user_about_to_log_in)
{
	if( !isset($GLOBALS['role_just_confirmed']) ) {
		return;
	}

	$db = new DB_Admin;
	$db->query("SELECT r.id, r.text_to_confirm, r.email_notify, u.name FROM user u LEFT JOIN user_roles r ON r.id=u.attr_role WHERE u.id=".$user_about_to_log_in);
	if( !$db->next_record() ) {
		return;
	}
	$role_id         = $db->fs('id');
	$text_to_confirm = $db->fs('text_to_confirm');
	$email_notify    = strval($db->fs('email_notify'));
	$user_name       = strval($db->fs('name'));
	$md5_confirmed = md5($text_to_confirm);

	// save state in registry
	regSet('role.confirmed', $md5_confirmed, '');
	regSave();
	
	// send a mail, if needed - ueber SMTP, siehe roleconfirm_send_notify__() am Dateiende
	$logwriter = new LOG_WRITER_CLASS;
	if( $email_notify != '' )
	{
		$email_subject = "Rollentext akzeptiert von ".$user_name;
		$email_body    = "Der folgende Rollentext wurde akzeptiert von ".$user_name.":\n\n".$text_to_confirm;
		$email_error   = '';

		if( roleconfirm_send_notify__($email_notify, $email_subject, $email_body, $email_error) ) {
			$logwriter->addData('notify', $email_notify);
		}
		else {
			$logwriter->addData('notify_error', $email_notify);
			$logwriter->addData('notify_error_reason', $email_error);
			error_log('[WISY roleconfirm] Benachrichtigung an "'.$email_notify.'" nicht versendet: '.$email_error);
		}
	}
	
	// log
	$logwriter->log('user_roles', $role_id,              $user_about_to_log_in, 'confirmed');
	$logwriter->log('user',       $user_about_to_log_in, $user_about_to_log_in, 'confirmed');
}


function roleconfirm_check($user_about_to_log_in)
{
	global $site;
	
	$db = new DB_Admin;
	$db->query("SELECT r.text_to_confirm FROM user u LEFT JOIN user_roles r ON r.id=u.attr_role WHERE u.id=".$user_about_to_log_in);
	if( !$db->next_record() ) {
		return;
	}
	$text_to_confirm = $db->fs('text_to_confirm');
	
	//
	// has the user already confirmed the text?
	//
	if( isset($_REQUEST['role_confirm_ok']) )
	{
		// we'll save the data and do more stuff in roleconfirm_after_login() which is called when the session is completely working
		$GLOBALS['role_just_confirmed'] = 1;
		return;
	}
	
	//
	// show page with text to confirm
	//

	$site->pageStart();
	$site->menuHelpScope = '.';
	$site->menuOut();

		form_tag('form_enter', 'index.php');
		form_hidden('enter_subsequent', 1);
		form_hidden('enter_skip_env_tests', 1);
		form_hidden('enter_loginname', isset($_REQUEST['enter_loginname']) ? $_REQUEST['enter_loginname'] : '');
		
		// For security reasons, do not write the password to an HTML-file.  Instead, read it from the $_SESSION['g_role_confirm_login_credential_pw'] on submit.
		$_SESSION['g_role_confirm_login_credential_pw'] = isset($_REQUEST['enter_password']) ? $_REQUEST['enter_password'] : ''; 
	
		echo '<div style="padding: 1em;">';
			echo nl2br($text_to_confirm);
		echo '</div>';

		$site->skin->buttonsStart();
			form_button('role_confirm_ok', "OK, Einverstanden");
			form_button('role_confirm_cancel', htmlconstant('_CANCEL'));
		$site->skin->buttonsEnd();
		
		echo '</form>';
			
	$site->pageEnd();
	exit();
}


/*=============================================================================
Mailversand der Rollen-Benachrichtigung
===============================================================================

Versendet wird ueber das externe SMTP-Postfach aus den Portaleinstellungen
(mail.extern.*) mit PHPMailer. Der fruehere Weg ueber PHP mail() ist bewusst
entfallen und es gibt auch keinen Rueckfall darauf:

=============================================================================*/


/**
 * Einstellungszeilen "key=value" einlesen, inklusive Aufloesung von
 * include-Direktiven (eine Ebene tief, wie im uebrigen WISY-Code).
 */
function roleconfirm_parse_settings__($in, &$out, $follow_includes)
{
	$in = strtr(strval($in), "\r\t", "\n ");
	$in = explode("\n", $in);
	foreach( $in as $line )
	{
		$eq = strpos($line, '=');
		if( $eq )
		{
			$key = trim(substr($line, 0, $eq));
			if( $key != '' )
			{
				$val = trim(substr($line, $eq+1));
				if( $key == 'include' ) {
					if( $follow_includes && @file_exists($val) ) {
						roleconfirm_parse_settings__(@file_get_contents($val), $out, false);
					}
				}
				else {
					$out[$key] = $val;
				}
			}
		}
	}
}


/**
 * Eine Portaleinstellung lesen. Quellen in dieser Reihenfolge:
 * 1. $wisyPortalEinstellungen, falls doch ein Frontend-Bootstrap gelaufen ist,
 * 2. das Portal, dessen "domains" zum aufgerufenen Host passt,
 * 3. das erste aktive Portal, das ueberhaupt mail.extern gesetzt hat.
 *
 * Schritt 3 greift, wenn das Redaktionssystem unter einem Host laeuft, der in
 * keinem Portal als Domain eingetragen ist. Die mail.extern.*-Werte beschreiben
 * ein einzelnes Versandpostfach und sind portaluebergreifend dieselben, deshalb
 * ist das hier vertretbar - ohne diesen Schritt bliebe die Benachrichtigung
 * sonst ganz aus.
 */
function roleconfirm_portal_setting__($key, $default = '')
{
	static $settings = null;

	if( $settings === null )
	{
		$settings = array();

		if( isset($GLOBALS['wisyPortalEinstellungen']) && is_array($GLOBALS['wisyPortalEinstellungen']) && $GLOBALS['wisyPortalEinstellungen'] )
		{
			$settings = $GLOBALS['wisyPortalEinstellungen'];
		}
		else if( class_exists('DB_Admin') )
		{
			$db = new DB_Admin;	// eigene Verbindung, damit kein laufendes Result-Set einer anderen Abfrage gestoert wird

			$host = isset($_SERVER['SERVER_NAME']) ? strtolower(str_replace('www.', '', $_SERVER['SERVER_NAME'])) : '';
			if( $host != '' )
			{
				$db->query("SELECT einstellungen FROM portale WHERE status=1 AND domains LIKE ".$db->quote('%'.$host.'%'));
				if( $db->next_record() ) {
					roleconfirm_parse_settings__($db->fs('einstellungen'), $settings, true);
				}
			}

			if( !isset($settings['mail.extern']) )
			{
				$db->query("SELECT einstellungen FROM portale WHERE status=1 AND einstellungen LIKE '%mail.extern%' ORDER BY id");
				while( $db->next_record() )
				{
					$candidate = array();
					roleconfirm_parse_settings__($db->fs('einstellungen'), $candidate, true);
					if( isset($candidate['mail.extern']) ) {
						$settings = $candidate;
						break;
					}
				}
			}
		}
	}

	return isset($settings[$key]) ? $settings[$key] : $default;
}


/**
 * Eine Portaleinstellung als Ja/Nein auswerten. Die Werte stehen als Text in
 * der Datenbank, ein einfacher Cast waere irrefuehrend: (bool)"false" ist true.
 */
function roleconfirm_setting_is_on__($value, $default_when_empty)
{
	$value = strtolower(trim(strval($value)));
	if( $value == '' ) {
		return $default_when_empty;
	}
	return in_array($value, array('1', 'true', 'yes', 'on', 'ja'), true);
}


/**
 * Benachrichtigung ueber das externe SMTP-Postfach versenden.
 *
 * Der gesamte Rumpf liegt in einem try/catch: Die Funktion laeuft mitten im
 * Login, unmittelbar vor dem Redirect. Ein Problem beim Mailversand darf die
 * Anmeldung unter keinen Umstaenden abbrechen - das frueher genutzte @mail()
 * konnte das ebenfalls nicht.
 *
 * @param  string $to       eine oder mehrere Adressen, durch Komma oder Semikolon getrennt
 * @param  string $subject  Betreff in Windows-1252 (so, wie aus der Datenbank gelesen)
 * @param  string $body     Text in Windows-1252
 * @param  string $error    Rueckgabe: Grund, falls nicht versendet wurde
 * @return bool             true = versendet (oder Entwicklungsumgebung)
 */
function roleconfirm_send_notify__($to, $subject, $body, &$error)
{
	$error = '';

	try
	{
		// Empfaenger pruefen. user_roles.email_notify wird von Hand gepflegt und
		// kann mehrere Adressen enthalten. Ungueltige Eintraege werden verworfen -
		// so wirkt auch ein Zeilenumbruch im Feld nicht als zusaetzlicher Header.
		$recipients = array();
		foreach( preg_split('/[,;]/', strval($to)) as $addr )
		{
			$addr = trim($addr);
			// Auch die Langform "Name <adresse>" annehmen: mail() hat sie frueher
			// akzeptiert, addAddress() erwartet dagegen die nackte Adresse.
			if( preg_match('/<([^<>]+)>\s*$/', $addr, $matches) ) {
				$addr = trim($matches[1]);
			}
			if( $addr != '' && filter_var($addr, FILTER_VALIDATE_EMAIL) ) {
				$recipients[] = $addr;
			}
		}
		if( !$recipients ) {
			$error = 'keine gueltige Empfaengeradresse in "'.$to.'"';
			return false;
		}

		// Entwicklungsumgebung: nicht wirklich versenden. Eine Ausgabe ist hier
		// nicht moeglich, der Login endet unmittelbar danach in einem Redirect.
		$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$http_host = preg_replace('/:\d+$/', '', $http_host);	// evtl. Port abschneiden
		if( substr($http_host, -6) == '.local' || PHP_SAPI == 'cli' ) {
			error_log('[WISY roleconfirm] (DEV) Mail an '.implode(', ', $recipients).' nicht versendet - Betreff: '.$subject);
			return true;
		}

		if( !roleconfirm_setting_is_on__(roleconfirm_portal_setting__('mail.extern', ''), false) ) {
			$error = 'Portaleinstellung "mail.extern" ist nicht gesetzt';
			return false;
		}
		if( !roleconfirm_setting_is_on__(roleconfirm_portal_setting__('mail.extern.useSMTP', ''), true) ) {
			$error = 'Portaleinstellung "mail.extern.useSMTP" ist abgeschaltet - ohne SMTP wird nicht versendet';
			return false;
		}
		if( !defined('PHPMAILER_PATH') ) {
			$error = 'PHPMAILER_PATH ist nicht definiert (admin/config/config.inc.php)';
			return false;
		}
		if( !file_exists(PHPMAILER_PATH) ) {
			$error = 'PHPMAILER_PATH zeigt auf eine nicht vorhandene Datei';
			return false;
		}

		$from = trim(strval(roleconfirm_portal_setting__('mail.extern.from', '')));
		if( $from == '' || !filter_var($from, FILTER_VALIDATE_EMAIL) ) {
			$error = 'Portaleinstellung "mail.extern.from" fehlt oder ist keine gueltige Adresse';
			return false;
		}

		require_once(PHPMAILER_PATH);

		$mail = new PHPMailer\PHPMailer\PHPMailer(true);	// true = Fehler als Exception
		$mail->isSMTP();
		$mail->CharSet = 'UTF-8';

		// Keine Ausgabe erzeugen: eine Debug-Ausgabe wuerde den Redirect am Ende
		// des Logins zerstoeren. Beides ist Standard, hier zur Sicherheit explizit.
		$mail->SMTPDebug   = 0;
		$mail->Debugoutput = 'error_log';

		// Kurzer Timeout statt der voreingestellten 300 Sekunden: Ist das
		// Postfach nicht erreichbar, wartet sonst der Anmeldevorgang darauf.
		$mail->Timeout = 15;

		$mail->Host = roleconfirm_portal_setting__('mail.extern.host', '');

		$port = intval(roleconfirm_portal_setting__('mail.extern.port', 0));
		if( $port > 0 ) {
			$mail->Port = $port;
		}

		$smtpSecure = trim(strval(roleconfirm_portal_setting__('mail.extern.smtpSecure', '')));
		if( $smtpSecure != '' ) {
			$mail->SMTPSecure = $smtpSecure;
		}

		if( roleconfirm_setting_is_on__(roleconfirm_portal_setting__('mail.extern.smtpAuth', ''), false) ) {
			$mail->SMTPAuth = true;
			$mail->Username = roleconfirm_portal_setting__('mail.extern.username', '');
			$mail->Password = roleconfirm_portal_setting__('mail.extern.password', '');
		}
		else {
			$mail->SMTPAuth = false;
		}

		// Absender immer aus mail.extern.from. 
		// Ein Reply-To wird nicht gesetzt. Antworten gehen damit an dieselbe
		// Adresse.
		$fromName = roleconfirm_to_utf8__(roleconfirm_portal_setting__('mail.extern.fromName', ''));
		$mail->setFrom($from, $fromName);
		$mail->Sender = $from;	

		foreach( $recipients as $addr ) {
			$mail->addAddress($addr);
		}

		$mail->Subject = roleconfirm_to_utf8__($subject);
		$mail->Body    = roleconfirm_to_utf8__($body);

		$mail->send();
		return true;
	}
	catch( Throwable $e )
	{
		$error = 'SMTP-Versand fehlgeschlagen: '.$e->getMessage();
		return false;
	}
}


/**
 * Text aus der Datenbank nach UTF-8 umsetzen (die Mail wird als UTF-8
 * verschickt, die WISY-Datenbank liefert Einzelbytes).
 *
 * Quellkodierung ist Windows-1252, nicht ISO-8859-1: MySQLs "latin1" ist
 * tatsaechlich cp1252, und auch der Browser sendet zu einer als iso-8859-1
 * deklarierten Seite cp1252-Bytes zurueck. Im Bereich 0x80-0x9F liegen dort
 * typografische Anfuehrungszeichen, Gedankenstrich, Euro-Zeichen und Auslassungs-
 * punkte - Zeichen, die beim Lesen als echtes ISO-8859-1 zu unsichtbaren
 * Steuerzeichen wuerden und in der Mail spurlos verschwinden. Fuer alle
 * Umlaute sind beide Kodierungen identisch, ein Unterschied entsteht also nur
 * dort, wo ISO-8859-1 ohnehin nichts Darstellbares liefern wuerde.
 */
function roleconfirm_to_utf8__($s)
{
	return mb_convert_encoding(strval($s), 'UTF-8', 'Windows-1252');
}
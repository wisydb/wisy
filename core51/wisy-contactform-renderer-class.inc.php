<?php


class WISY_CONTACTFORM_RENDERER_CLASS
{
	var $framework;
	var $contactform_enabled;
	var $contactorm_email;
	var $contactform_dsgvolink;
	var $contactform_thankyoutext;

	function __construct(&$framework)
	{		
		// constructor
		$this->framework =& $framework;
		$this->contactform_enabled = $this->framework->iniRead('contactform', '');
		$this->contactform_email = $this->framework->iniRead('contactform.email', '');
		$this->contactform_dsgvolink = $this->framework->iniRead('contactform.dsgvolink', '');
		$this->contactform_thankyoutitle = $this->framework->iniRead('contactform.thankyoutitle', 'Danke für Ihre Nachricht');
		$this->contactform_thankyoutext = $this->framework->iniRead('contactform.thankyoutext', 'Wir haben Ihre Nachricht erhalten und werden Sie baldmöglichst bearbeiten.');
	}	
	
	function render()
	{
		if($this->contactform_enabled != 1 || $this->contactform_email == '') {
			$this->framework->error404();
		}
		
		echo $this->framework->getPrologue(array(
			'title'		=>	'Kontaktformular',
			'bodyClass'	=>	'wisyp_contactform',
		));
		echo '<div id="wisy_resultarea" class="wisy_contactform" role="main">';
		
		if($_POST['kontaktformular'] == 'true') {
			$errors = $this->validateContactform();
			if(count($errors) == 0) {
				if($this->sendMail() == true) {
					echo $this->thankyouText();
				} else {
					echo $this->sendMailError();
				}
			} else {
				echo $this->returnContactform($errors);
			}
		} else {
			echo $this->returnContactform();
		}
		
		echo '</div><!-- /#wisy_resultarea -->';
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		echo $this->framework->getEpilogue();
	}
	
	function returnContactform($errors=array())
	{
		$ret = '<form class="wisy-form" action="/kontakt/" method="post">';
		$ret .= '<input type="hidden" name="kontaktformular" value="true" />';
		$ret .= '<h2>Kontaktformular</h2>';

		// Kontaktdaten
		$ret .= '<fieldset class="wisy-form--kontaktdaten">';
		$ret .= '<legend>Ihre Kontaktdaten</legend>';
		$ret .= '<label for="name">Ihr Name *</label><input id="name" name="name" type="text" value="'. $_POST['name'] .'" required />';
		if(array_key_exists('name', $errors)) { $ret .= '<p class="wisy-form--error">'. $errors['name'] .'</p>'; }
		$ret .= '<label for="email">Ihre Email-Adresse *</label><input id="email" name="email" type="email" value="'. $_POST['email'] .'" required />';
		if(array_key_exists('email', $errors)) { $ret .= '<p class="wisy-form--error">'. $errors['email'] .'</p>'; }
		$ret .= '</fieldset>';
		
		// Nachricht
		$ret .= '<fieldset class="wisy-form--nachricht">';
		$ret .= '<legend>Ihre Nachricht</legend>';
		$ret .= '<label for="nachricht">Ihre Nachricht *</label><textarea id="nachricht" name="nachricht" required>'. $_POST['nachricht'] .'</textarea>';
		if(array_key_exists('nachricht', $errors)) { $ret .= '<p class="wisy-form--error">'. $errors['nachricht'] .'</p>'; }
		$ret .= '</fieldset>';
		
		// DSGVO
		if($this->contactform_dsgvolink != '') {
			$ret .= '<fieldset class="wisy-form--dsgvo">';
			$ret .= '<legend>Datenschutz</legend>';
			$ret .= '<input id="dsgvo" name="dsgvo" type="checkbox" value="checked" '. $_POST['dsgvo'] .' required /><label for="dsgvo">Wir erheben und verwenden die von Ihnen eingegebenen Daten entsprechend unserer <a href="'. $this->contactform_dsgvolink .'">Datenschutzerklärung</a> *</label>';
			if(array_key_exists('dsgvo', $errors)) { $ret .= '<p class="wisy-form--error">'. $errors['dsgvo'] .'</p>'; }
			$ret .= '</fieldset>';
		}
		
		$ret .= '<button type="submit">Abschicken</button>';
		$ret .= '<p class="wisy-form--pflichtfelder">* Pflichtfelder</p>';
		$ret .= '</form>';
		
		return $ret;
	}
	
	function validateContactform()
	{
		$errors = array();
		$required_fields = array('name', 'email', 'nachricht');
		if($this->contactform_dsgvolink != '') {
			$required_fields[] = 'dsgvo';
		}
		foreach($required_fields as $required_field) {
			if(trim($_POST[$required_field]) == '') {
				$errors[$required_field] = 'Bitte füllen Sie dieses Feld aus.';
			}
		}
		if(trim($_POST['email']) != '' && !filter_var(idn_to_ascii($_POST['email']), FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Bitte geben sie eine valide Email-Adresse an.';
		}
		
		return $errors;
	}
	
	function sendMail()
	{
		$to = $this->contactform_email;
		$subject = $this->getMailSubject();
		$body = $this->getMailBody();
		if( substr($_SERVER['HTTP_HOST'], -6)=='.local' )
		{
			echo '<pre>';
				echo 'To: '. htmlspecialchars($to) ."\n";
				echo 'Subject: '. htmlspecialchars($subject) ."\n\n";
				echo htmlspecialchars($body);
			echo '</pre>';
			return true;
		}
		else
		{
			return mail($to, $subject, $body, "From: $this->contactform_email");
		}
	}
	
	function getMailSubject()
	{
		return 'WISY Kontaktformular '. $GLOBALS['wisyPortalKurzname'];
	}
	
	function getMailBody()
	{
		$ret = "Kontaktformular-Anfrage von ". $GLOBALS['wisyPortalKurzname'] ."\n\n";
		$ret .= "Name: ". $_POST['name'] ."\n";
		$ret .= "Email-Adresse: ". $_POST['email'] ."\n\n";
		$ret .= "Nachricht:\n";
		$ret .= $_POST['nachricht'];
		return $ret;
	}
	
	function sendMailError()
	{
		require_once('admin/lang.inc.php');			// needed for the following includes
		require_once('admin/table_def.inc.php');	// needed for db.inc.php
		require_once('admin/config/db.inc.php');	// needed for LOG_WRITER_CLASS
		
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('error', 'Kann E-Mail nicht senden.');
		$logwriter->addData('name', $_POST['name']);
		$logwriter->addData('email', $_POST['email']);
		$logwriter->log('Kontaktformular', 0, 0, 'sendMail');
		
		return "<strong>Fehler: kann den Inhalt des Kontaktformulars nicht versenden. Bitte wenden Sie sich direkt an uns.</strong>";
	}
	
	function thankyouText()
	{
		echo '<h2>'. $this->contactform_thankyoutitle .'</h2>';
		echo '<p>'. $this->contactform_thankyoutext .'</p>';
	}
}

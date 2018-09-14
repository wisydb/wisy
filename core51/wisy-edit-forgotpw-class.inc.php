<?php if( !defined('IN_WISY') ) die('!IN_WISY');


class WISY_EDIT_FORGOTPW_CLASS
{
	var $framework;

	/**************************************************************************
	 * Tools / Misc.
	 *************************************************************************/
	
	function __construct(&$framework, $param)
	{
		// constructor
		require_once('admin/genpassword.inc.php');
		
		$this->framework	=& $framework;
		$this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_confirm', 'itemLifetimeSeconds'=>24*60*60));
		$this->adminAnbieterUserId = $param['adminAnbieterUserId'];
	}

	function renderForgotPwScreen()
	{
		$anbieterSuchname	= trim($_REQUEST['as']);
		$anbieterSuchname_utf8dec = utf8_decode($anbieterSuchname);
		$msg = '';
		$showForm = true;
		
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addData('ip', $_SERVER['REMOTE_ADDR']);
		$logwriter->addData('browser', $_SERVER['HTTP_USER_AGENT']);
		$logwriter->addData('portal', $GLOBALS['wisyPortalId']);
		
		if( $_REQUEST['pwsubseq'] == 1 && isset($_REQUEST['cancel']) )
		{
			// cancel clicked
			// ================================================================
			header('Location: search');
			exit();
		}
		else if( $_REQUEST['pwsubseq'] == 1 && $anbieterSuchname!='' )
		{
			// mail or name entered - try to send the mail with the 
			// confirmation link
			// ================================================================
			
			$db	= new DB_Admin;
			$sql = "SELECT id, pflege_email FROM anbieter WHERE pflege_pweinst&1 AND (id=".intval($anbieterSuchname)." OR pflege_email=".$db->quote($anbieterSuchname_utf8dec)." OR suchname=".$db->quote($anbieterSuchname_utf8dec)." OR postname=".$db->quote($anbieterSuchname_utf8dec).");";
			$db->query($sql);
			if( $db->next_record() )
			{
				$f_id = $db->f8('id');
				$f_email = $db->f8('pflege_email');
				if( !$db->next_record() ) 
				{
					if( $f_email != '' )
					{
						$f_email = str_replace(';', ',', $f_email);
						$f_email_shortened = preg_replace('/([A-Z0-9._%+-]{1,1})[A-Z0-9._%+-]*@/i', '\1...@', $f_email);
						$f_new_pw = genpassword();
						
						$f_confirm = sprintf('%04x%04x%04x%04x%04x', mt_rand(0,0xFFFF), mt_rand(0,0xFFFF), mt_rand(0,0xFFFF), mt_rand(0,0xFFFF), mt_rand(0,0xFFFF));
						
						$this->dbCache->insert('forgotpw.'.$f_confirm, $f_id);

						$protocol = 'http';
						if( $this->framework->iniRead('useredit.secure', 1)==1 
						 && substr($_SERVER['HTTP_HOST'], -6)!='.local'
							|| $this->framework->iniRead('portal.https', '') )
							$protocol = 'https';
						
						$f_link = "{$protocol}://__HTTP_HOST__/edit?action=forgotpw&c={$f_confirm}";
						
						$f_subject  = 'Ihr neues Passwort für __HTTP_HOST__ (__NAME__)';
						$f_mailbody =
						"Hallo $f_email -
						
Sie (oder jemand der sich als Kursanbieter auf __HTTP_HOST__ bzw. \"__NAME__ \"ausgegeben hat) haben unter https://__HTTP_HOST__/edit ein neues Passwort für Ihren Account beantragt.

Wenn Sie KEIN neues Passwort beantragt haben oder wenn Ihnen Ihr altes Passwort zwischenzeitlich wieder eingefallen ist, so ignorieren und löschen Sie bitte diese E-Mail.

Nur WENN Sie ein neues Passwort beantragt haben, klicken Sie bitte auf den folgenden Link, um ein neues Passwort zu erhalten und sich damit wieder in Ihrem Account einloggen zu können: $f_link

Mit freundlichen Grüßen,
__NAME__";
						
						$f_subject  = utf8_decode($this->replaceForgotPwPlaceholders($f_subject));
						$f_mailbody = utf8_decode($this->replaceForgotPwPlaceholders($f_mailbody));
						
						$logwriter->addData('email', $f_email);
						if( $this->sendMail($f_email, $f_subject, $f_mailbody) )
						{
							$msg= 'Wir haben an die bei uns hinterlegte E-Mail-Adresse <b>erfolgreich</b> ein neues Passwort gesandt. 
								   Bitte überprüfen Sie nun Ihren E-Mail-Account ('.htmlspecialchars($f_email_shortened).') und folgen Sie den dort angegebenen Anweisungen.';
						}
						else
						{
							$msg = '<b>Fehler:</b> Kann die E-Mails mit dem neuen Passwort nicht an '.htmlspecialchars($f_email_shortened).' versenden; bitte wenden Sie sich direkt an uns.';
							$logwriter->addData('error', 'Kann E-Mail nicht senden.');
							$showForm = false;
						}

						$logwriter->log('anbieter', $f_id, $this->adminAnbieterUserId, 'requestpw');
						$showForm = false;
					}
					else
					{
						$msg = 'Zum angegebenen Anbieter ist keine E-Mail-Adresse hinterlegt; bitte wenden Sie sich direkt an uns.';
						$showForm = false;
					}
				}
				else
				{
					$msg  = '<b>Fehler:</b> Die angegebenen Daten sind nicht eindeutig. Bitte versuchen Sie es erneut oder wenden Sie sich direkt an uns.';
				}
			}
			else
			{
				$msg = '<b>Fehler:</b> Kann keinen Anbieter zu den angegebenen Daten finden. Bitte versuchen Sie es erneut oder wenden Sie sich direkt an uns.';
			}
			

		}
		else if( $_REQUEST['c'] )
		{
			// confirmation link clicked
			// ================================================================
			
			$db	= new DB_Admin;
			$this->dbCache->deleteOldEntries(); // otherwise they are only deleted if an expired entry is tried to be read - this is normally never ...
			$anbieterId = intval($this->dbCache->lookup('forgotpw.'.$_REQUEST['c']));
			$db->query("SELECT id, suchname, notizen FROM anbieter WHERE id=$anbieterId AND pflege_pweinst&1;");
			if( $db->next_record() )
			{
				$anbieterSuchname = $db->f8('suchname');
				$newpassword = genpassword();
				$notizen = strftime('%d.%m.%y') . ": Neues Passwort mit Passwort-Vergessen-Funktion generiert\n" . $db->f8('notizen');
				
				$db->query("UPDATE anbieter SET pflege_passwort=".$db->quote(crypt($newpassword)).", notizen=".$db->quote($notizen)." WHERE id=$anbieterId;");
				
				$this->dbCache->insert('forgotpw.'.$_REQUEST['c'], 0);
				
				$msg = "Ihr <b>neues Passwort</b> für den Login als Anbieter <i>".htmlspecialchars($anbieterSuchname)."</i> lautet:<br /><br /> 
					<b style=\"font-size: 14pt;\">$newpassword</b><br /><br />Bitte merken sie sich das Passwort jetzt oder notieren Sie es an einem sicheren Platz. 
					Danach können Sie sich mit Ihrem neuen Passwort <a href=\"edit?action=login&amp;as=".urlencode($anbieterSuchname)."\"><b>hier einloggen</b></a>.";
				$showForm = false;
				
				$logwriter->log('anbieter', $anbieterId, $this->adminAnbieterUserId, 'resetpw');
			}
			else
			{
				$msg = '<b>Fehler:</b> Der Verweis wurde bereits verwendet; bitte lassen Sie sich eine neue E-Mail zusenden.';
			}
		} 
		
		// render the page
		// ====================================================================

		echo $this->framework->getPrologue(array('title'=>'Passwort vergessen', 'bodyClass'=>'wisyp_edit'));
		echo $this->framework->getSearchField();
		
		echo '<h1>Passwort vergessen</h1>';
		
		if( $msg )
		{
			echo '<p class="wisy_topnote">'.$msg.'</p>';
		}
			
		if( $showForm )
		{
			echo '<p>Bitte geben Sie den Anbieternamen <i>oder</i> die Anbieter-ID <i>oder</i> die bei uns hinterlegte E-Mail-Adresse ein.
					 Nach einem Klick auf &quot;OK&quot; senden wir eine E-Mail mit einem neuen Passwort und allen weiteren Informationen.</p>';

			echo '<form action="edit" method="post">';
				echo '<table>';
					echo "<input type=\"hidden\" name=\"action\" value=\"forgotpw\" />";
					echo "<input type=\"hidden\" name=\"pwsubseq\" value=\"1\" />";
					echo '<tr>';
						echo '<td nowrap="nowrap">Anbietername oder -ID oder E-Mail-Adresse:</td>';
						echo "<td><input type=\"text\" name=\"as\" value=\"".htmlspecialchars($anbieterSuchname)."\" size=\"50\" /></td>";
					echo '</tr>';
					echo '<tr>';
						echo '<td nowrap="nowrap">';
						echo '</td>';
						echo '<td nowrap="nowrap">';
							echo '<input type="submit" value="OK - Neues Passwort zusenden" /> ';
							echo '<input type="submit" name="cancel" value="Abbruch" /> ';
						echo '</td>';
					echo '</tr>';
				echo '</table>';
			echo '</form>';

		}			

		$temp = $this->framework->iniRead('useredit.forgotpwmsg', '');
		if( $temp != '' )
		{
			echo '<p>' . $temp . '</p>';
		}
					 
		echo $this->framework->getEpilogue();
	}

	function replaceForgotPwPlaceholders($str)
	{
		return strtr($str, array(
			'__NAME__'		=>	$GLOBALS['wisyPortalKurzname'],
			'__HTTP_HOST__'	=>	$_SERVER['HTTP_HOST'],
		));
	}
	
	function sendMail($to, $subject, $text)
	{
	
		if( substr($_SERVER['HTTP_HOST'], -6)=='.local' )
		{
			echo '<pre>';
				echo 'To: ' . htmlspecialchars($to) . "\n";
				echo 'Subject: ' . htmlspecialchars($subject) . "\n\n";
				echo htmlspecialchars($text);
			echo '</pre>';
			return true;
		}
		else
		{
			return mail($to, $subject, $text, "From: noreply@".$_SERVER['HTTP_HOST']);
		}
	}
	
	
	
};

<?php if( !defined('IN_WISY') ) die('!IN_WISY');




class WISY_BILLING_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		$this->framework				=& $framework;
		$this->valid_receiver_email		=  $this->framework->iniRead('useredit.paypal.receiver', 'michaels@weiterbildung-hamburg.de');

		// read the prices for several amount of credits as "1000=12.12; 5000=23.23" etc.
		$this->allPrices = array();
		$allPrices = $this->framework->iniRead('useredit.billing.prices', '1000=29.75;');
		$allPrices = strtr($allPrices, array('='=>';', ','=>'.', ' '=>''));
		$allPrices = explode(';', $allPrices);
		for( $a = 0; $a < sizeof($allPrices); $a+=2 )
		{	
			$amount = intval($allPrices[$a]);
			$price  = floatval($allPrices[$a+1]);
			if( $amount > 0 && $price > 0.0 )
				$this->allPrices[] = array($amount, $price);
		}
	}
	
	function addBill($anbieterId, $bill_type, $credits_to_add, $price, $raw_data)
	{
		global $wisyPortalId;

		// zähler erhöhen, pausierte werbung freischalten
		if( $credits_to_add > 0 )
		{
			$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
			
			$old_credits = $promoter->getCredits($anbieterId);
			$new_credits = $old_credits + $credits_to_add;
			$promoter->setCredits($anbieterId, $new_credits);
			
			$promoter->setAllPromotionsActive($anbieterId, 1);
		}
		
		// user_created, user_grp und user_access werden aus dem Portaldatensatz übernehmen
		$db = new DB_Admin;
		$db->query("SELECT user_created, user_grp, user_access FROM portale WHERE id=$wisyPortalId;");
		$db->next_record();
		$user_created = intval($db->f('user_created'));
		$user_grp     = intval($db->f('user_grp'));
		$user_access  = intval($db->f('user_access'));
		
		// Eintrag in Log schreiben
		$todayHour     = strftime("%Y-%m-%d %H:%M:%S");
		$db->query(  "INSERT INTO anbieter_billing
					 (user_created,  user_modified, user_grp,  user_access,  date_created, date_modified, anbieter_id, portal_id,     bill_type, credits,          eur,      raw_data) VALUES
					 ($user_created, $user_created, $user_grp, $user_access, '$todayHour', '$todayHour',  $anbieterId, $wisyPortalId, $bill_type, $credits_to_add, '$price', '".addslashes($raw_data)."')");
	}
	
	/**************************************************************************
	 * PayPal Implemantation
	 **************************************************************************/
	
	function renderButton($anbieterId)
	{
		// button vorbereiten
		$amount = str_replace(',', '.', $amount);
		$return = 'http:/' . '/' . $_SERVER['HTTP_HOST'] . '/';
		$credits = $this->allPrices[0][0];
		$price   = $this->allPrices[0][1];

		// button erzeugen
		// möglichen felder:
		// https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables
		// weitere infos:
		// http://www.pdncommunity.com/pdn/board/message?board.id=de&thread.id=2108
		$button = '
		<p>
			<form name="paypalform" action="https:/'.'/www.paypal.com/cgi-bin/webscr" method="post" style="border:0px; margin:0px;">
				<input type="image" src="https://www.paypal.com/de_DE/i/btn/x-click-but01.gif" border="0" name="submit" alt="Jetzt mit PayPal kaufen" title="" />
				<input type="hidden" name="cmd" value="_xclick" />
				<input type="hidden" name="business" value="'.$this->valid_receiver_email.'" />
				<input type="hidden" name="item_name" value="'.$credits.' Einblendungen" />
				<input type="hidden" name="item_number" value="1-'.$credits.'" />
				<input type="hidden" name="amount" value="'.$price.'" />
				<input type="hidden" name="no_shipping" value="1" />
				<input type="hidden" name="no_note" value="1" />
				<input type="hidden" name="currency_code" value="EUR" />
				<input type="hidden" name="lc" value="DE" />
				<input type="hidden" name="bn" value="PP-BuyNowBF" />
				<input type="hidden" name="custom" value="'.$anbieterId.'" />
				<input type="hidden" name="return" value="'.$return.'paypalok" />
				<input type="hidden" name="cancel_return" value="'.$return.'paypalcancel" />
			</form>
		</p>
		';

		// render ....
		echo '<p>';
			echo 'Einblendungen können über PayPal gekauft werden. PayPal akzeptiert alle gängigen Kreditkarten und die Bezahlung per Überweisung. Klicken Sie einfach auf das folgende Symbol:';
		echo '</p>';
		echo $button;
	}
	
	function render()
	{
		// Paypal IPN - Instant Payment Notification, 
		// https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_admin_overview

		// zum Testen:
		// - www.sandbox.paypal.com anstelle von www.paypal.com wählen, das IPN-Testtool unter https://developer.paypal.com/
		// - eCheck complete wählen
		// - receiver_email : michaels@weiterbildung-hamburg.de
		// -  mc_currency   : EUR
		// - item_number    : 1-1000
		// - custom         : 5455

		$error = '';
		$db = new DB_Admin;

		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		$all_data = '';
		foreach ($_POST as $key => $value)
		{
			$all_data .= "$key: $value\n";
			$value = urlencode($value);
			$req .= "&$key=$value";
		}

		// kreditempfaenger ueberpruefen
		$anbieterId  = intval($_POST['custom']);
		$db->query("SELECT id FROM anbieter WHERE id=$anbieterId;");
		if( $db->next_record() )
		{
			// post back to PayPal system to validate
			$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30); 
			if( $fp ) 
			{
				$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
				fputs ($fp, $header . $req);
				while (!feof($fp))
				{
					$res = fgets ($fp, 1024);
					if (strcmp ($res, "VERIFIED") == 0)
					{
						// check the payment_status is Completed -- 
						// if not, we're done here - wait until paypal is finished
						if( $_POST['payment_status'] != 'Completed' )
						{
							fclose($fp);
							exit();
						}
						
						// geldempfaenger ueberpruefen
						if( $_POST['receiver_email'] != $this->valid_receiver_email )
						{
							$error = "Ungueltiger Geldempfaenger";
							break;						
						}
						
						// bestellnummer / anzahl gutzuschreibender Kredite ueberpruefen
						$order_no = explode('-', $_POST['item_number']); // dies ist die "Bestellnummer" als "1-1234" wobei 1234 die Anzahl der Kredite ist
						if( $order_no[0] != 1 )
						{
							$error = "Erster Teil der Bestellnummer ungueltig";
							break;
						}
	
						$soll_amount = 0;
						$credit_count = 0;
						for( $i = 0; $i < sizeof($this->allPrices); $i++ )
						{
							if( $this->allPrices[$i][0] == intval($order_no[1]) )
							{
								$credit_count = intval($order_no[1]);
								$soll_amount  = $this->allPrices[$i][1];
							}
						}
						
						if( $credit_count == 0 )
						{
							$error = "Zweiter Teil der Bestellnummer ungueltig";
							break;
						}
						
						// waehrung und betrag ueberpruefen
						if( $_POST['mc_currency'] != 'EUR' )
						{
							$error = 'Waehrung ist nicht Euro';
							break;
						}
	
						if( $_POST['mc_gross'] < $soll_amount )
						{
							$error = "Falscher Preis, erwarte $soll_amount fuer die Bestellnummer " . $_POST['item_number'];
							break;
						}
						
						// alles fein: Kredite hinzufuegen
						$this->addBill($anbieterId, 9001, $credit_count, $soll_amount, $all_data);
					}
					else if (strcmp ($res, "INVALID") == 0)
					{
						// log for manual investigation
						$error = 'Ueberpruefung der Daten fehlgeschlagen';
					}		
				}
	
				fclose ($fp);
			}
			else
			{
				$error = 'HTTP Error';
			}
		}
		else
		{
			$error = "Ungueltiger Anbieter";
		}
		
		if( $error )
		{
			$this->addBill($anbieterId, 9099, 0, '0.00', $error . "\n" . $all_data);
			echo $error;
		}
		else
		{
			echo 'OK';
		}
	}
}

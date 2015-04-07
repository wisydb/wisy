<?php



class WISY_ADVANCED_RENDERER_CLASS
{
	function __construct(&$framework)
	{
		$this->framework	=& $framework;

		$dates = $this->getDates();
		$this->presets = array();
		$this->presets['q'] = array
			(
				'type'			=> 'text',
				'descr'			=> '<strong>Suchwörter:</strong>',
				'autocomplete'	=>	'ac_keyword',
			);
		$this->presets['datum'] = array
			(
				'type'		=> 'function',
				'function'	=> 'Datum:',
				'descr'		=> '<strong>Beginndatum:</strong>',
				'options' 	=> array(
					'Alles' 				=> 'auch abgelaufene Angebote berücksichtigen',
					$dates['vorgestern']	=> 'ab vorgestern',
					$dates['gestern']		=> 'ab gestern',
					''						=> 'ab heute',
					$dates['morgen']		=> 'ab morgen',
					$dates['uebermorgen']	=> 'ab übermorgen',
					$dates['montag1']		=> 'nächste Woche &ndash; ab Montag, ' . $dates['montag1'],
					$dates['montag2']		=> 'übernächste Woche &ndash; ab Montag, ' . $dates['montag2'],
					$dates['montag3']		=> 'in 3 Wochen &ndash; ab Montag, ' . $dates['montag3'],
					$dates['montag4']		=> 'in 4 Wochen &ndash; ab Montag, ' . $dates['montag4'],
					$dates['montag5']		=> 'in 5 Wochen &ndash; ab Montag, ' . $dates['montag5'],
					$dates['montag6']		=> 'in 6 Wochen &ndash; ab Montag, ' . $dates['montag6'],
					$dates['montag7']		=> 'in 7 Wochen &ndash; ab Montag, ' . $dates['montag7'],
					$dates['montag8']		=> 'in 8 Wochen &ndash; ab Montag, ' . $dates['montag8'],
					$dates['montag9']		=> 'in 9 Wochen &ndash; ab Montag, ' . $dates['montag9'],
				),
				'decoration' => array(
					'headline_left' => '',
				),
			);
		$this->presets['dauer'] = array
			(
				'type'		=> 'function',
				'function'	=> 'Dauer:',
				'descr'		=> 'Dauer:',
				'options' 	=> array(
					''			=> '',
					'3'			=> 'max. 3 Tage',
					'7'			=> 'max. 1 Woche',
					'30'		=> 'max. 1 Monat',
					'90'		=> 'max. 3 Monate',
					'180'		=> 'max. 6 Monate',
					'365'		=> 'max. 1 Jahr',
					'2-3'		=> 'ca. 3 Tage',
					'4-7'		=> 'ca. 1 Woche',
					'20-31'		=> 'ca. 1 Monat',
					'80-100'	=> 'ca. 3 Monate',
					'160-200'	=> 'ca. 6 Monate',
					'340-380'	=> 'ca. 1 Jahr',
				),
			);
		$this->presets['tageszeit'] = array
			(
				'type'		=> 'taglist',
				'descr'		=> 'Tageszeit/Fernunterr.:',
				'options'	=> array(
					''					=> '',
					'Ganztags'			=> 'Ganztags',
					'Vormittags'		=> 'Vormittags',
					'Nachmittags'		=> 'Nachmittags',
					'Abends'			=> 'Abends',
					'Wochenende'		=> 'Wochenende',
					'Fernunterricht'	=> 'nur Fernunterricht',
					'-Fernunterricht'	=> 'ohne Fernunterricht',
					'Bildungsurlaub'	=> 'Bildungsurlaub',
				),
			);
			
			// umkreissuche
		if( !$this->framework->iniRead('radiussearch.disable', 0) )
		{
			$this->presets['bei'] = array
				(
					'type'				=>	'text',
					'function'			=>	'bei:',
					'descr'				=>	'Stra&szlig;e/Ort:',
					'autocomplete'		=>	'ac_plzort',
					'comma_to_slash'	=>	true,
					'decoration' => array(
						'headline_left' => '<strong>Umkreissuche</strong>',
						'headline_right'	=> 'suche Kurse im Umkreis der folgenden Adresse:',
					),
				);
			$this->presets['km'] = array
				(
					'type'		=> 'function',
					'function'	=> 'Km:',
					'descr'		=> 'max. Entfernung:',
					'options'	=> array(
						''			=>	'automatisch',
						'1'			=> '1 km',
						'2'			=> '2 km',
						'5'			=> '5 km',
						'7'			=> '7 km',
						'10'		=> '10 km',
						'20'		=> '20 km',
						'50'		=> '50 km',
					),
				);
		}

			// plz
		$decoration = array('headline_left' => '<strong>Weitere Optionen</strong>');
		if( !$this->framework->iniRead('plzsearch.disable', 0) )
		{
			$this->presets['plz'] = array
			(
				'type'		=>	'text',
				'function'	=>	'Plz:',
				'descr'		=>	'PLZ &ndash; 1-5 Ziffern:',
				'decoration' => $decoration,
			);
			$decoration = array();
		}
			
			// weitere Optionen
		$this->presets['preis'] = array
			(
				'type'		=> 'function',
				'function'	=> 'Preis:',
				'descr'		=> 'Preis:',
				'decoration' => $decoration,
				'options'	=> array(
					''			=>	'',
					'0'			=> 'muss kostenlos sein',
					'100'		=> 'max. 100 &euro;',
					'200'		=> 'max. 200 &euro;',
					'500'		=> 'max. 500 &euro;',
					'1000'		=> 'max. 1000 &euro;',
					'2000'		=> 'max. 2000 &euro;',
					'5000'		=> 'max. 5000 &euro;',
				),
			);
			$decoration = array();
			
		$foerderungen = $this->getSpezielleStichw(2);
		if( sizeof($foerderungen) > 1 )
		{
			$this->presets['foerderung'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Förderung:',
					'options'	=>	$foerderungen
				);
		}
		
		$zielgruppen = $this->getSpezielleStichw(8);
		if( sizeof($zielgruppen) > 1 )
		{
			$this->presets['zielgruppe'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Zielgruppe:',
					'options'	=>	$zielgruppen
				);
		}

		$qualitaetszertifikate = $this->getSpezielleStichw(4);
		if( sizeof($qualitaetszertifikate) > 1 )
		{
			$this->presets['qualitaetszertifikat'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Qualitätszertifikat:',
					'options'	=>	$qualitaetszertifikate
				);
		}

		$unterrichtsarten = $this->getSpezielleStichw(32768);
		if( sizeof($unterrichtsarten) > 1 )
		{
			$this->presets['unterrichtsart'] = array
				(
					'type'		=> 'taglist',
					'descr'		=> 'Unterrichtsart:',
					'options'	=>	$unterrichtsarten
				);
		}
				
		if( $this->framework->iniRead('search.adv.fulltext', 1)!=0 )
		{
			$this->presets['volltext'] = array
				(
					'type'		=>	'text',
					'function'	=>	'Volltext:',
					'descr'		=>	'Volltext:',
				);
		}

	}

	/**********************************************************************
	 * date routines
	 **********************************************************************/

	private function getSpezielleStichw($flag)
	{
		// nur die stichwörter zurückgeben, die im aktuellem Portal auch vervendet werden!
		$keyPrefix = "advStichw.$flag";
		$magic = strftime("%Y-%m-%d-v5-").md5($GLOBALS['wisyPortalFilter']['stdkursfilter']);
		if( $this->framework->cacheRead("adv_stichw.$flag.magic") != $magic )
		{
			$specialInfo =& createWisyObject('WISY_SPECIAL_INFO_CLASS', $this->framework);
			$specialInfo->recalcAdvStichw($magic, $flag);
		}
		$ids_str = $this->framework->cacheRead("adv_stichw.$flag.ids");
	
		// query!
		$ret = array(''=>'');
		$db = new DB_Admin;
		//$db->query("SELECT stichwort FROM stichwoerter WHERE eigenschaften=$flag ORDER BY stichwort_sorted;");
		$db->query("SELECT stichwort FROM stichwoerter WHERE id IN ($ids_str) ORDER BY stichwort_sorted;");
		while( $db->next_record() )
		{
			$stichw = isohtmlspecialchars($db->fs('stichwort'));
			$stichw = trim(strtr($stichw, array(': '=>' ', ':'=>' ', ', '=>' ', ','=>' ')));
			
			$ret[ $stichw ] = $stichw;
		}
		return $ret;
	}

	function getDates()
	{
		$onedaysec = 24*60*60;
		$heute = mktime(0, 0, 0);
		
		$ret = array();
		$ret['vorgestern'] = strftime('%d.%m.%Y', $heute-$onedaysec*2);
		$ret['gestern'] = strftime('%d.%m.%Y', $heute-$onedaysec);
		$ret['heute'] = strftime('%d.%m.%Y', $heute);
		$ret['morgen'] = strftime('%d.%m.%Y', $heute+$onedaysec);
		$ret['uebermorgen'] = strftime('%d.%m.%Y', $heute+$onedaysec*2);
		
		// nächsten Montag herausfinden
		$test = $heute + $onedaysec;
		while( 1 )
		{
			$info = getdate($test);
			if( $info['wday'] == 1 )
				{ break; }
			$test += $onedaysec;
		}
		for( $i = 1; $i <= 9 /* s.a. (1) */; $i++ )
			$ret['montag'.$i] = strftime('%d.%m.%Y', $test + $onedaysec * 7 * ($i-1));
		
		return $ret;
	}


	/**********************************************************************
	 * render, misc.
	 **********************************************************************/

	function renderForm()
	{
		
		// explode the query string to its tokens
		/////////////////////////////////////////
		
		$presets_curr = array();

		$q = $this->framework->getParam('q');
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$tokens = $searcher->tokenize($q);
		for( $i = 0; $i < sizeof($tokens['cond']); $i++ )
		{
			$do_def = true;
			
			$token_field = $tokens['cond'][$i]['field'];
			switch( $token_field )
			{
				case 'datum':
				case 'dauer':
				case 'preis':
				case 'plz':
				case 'volltext':
				case 'bei':
				case 'km':
					if( is_array($this->presets[$token_field]) ) // some presets (eg. fulltext) may be disabled at all
					{
						if( !isset($presets_curr[$token_field]) )
						{
							if( !isset($this->presets[$token_field]['options'][$tokens['cond'][$i]['value']]) )
								$this->presets[$token_field]['options'][$tokens['cond'][$i]['value']] = 'anderer Wert &ndash; '.$tokens['cond'][$i]['value'];
							$presets_curr[$token_field] = $tokens['cond'][$i]['value'];
							$do_def = false;
						}
					}
					break;
					
				case 'tag':
					reset($this->presets);
					while( list($field_name, $preset) = each($this->presets) )
					{
						if( $preset['type'] == 'taglist' && !isset($presets_curr[$field_name]) )
						{
							reset($preset['options']);
							while( list($value) = each($preset['options']) )
							{
								if( strval($tokens['cond'][$i]['value']) == strval($value) )
								{
									$presets_curr[$field_name] = $tokens['cond'][$i]['value'];
									$do_def = false;
								}
							}
						}
					}
					break;
			}
			
			if( $do_def )
			{
				$presets_curr['q'] .= $presets_curr['q']? ', ' : '';
				$presets_curr['q'] .= $tokens['cond'][$i]['field']!='tag'? ($tokens['cond'][$i]['field'].':') : '';
				$presets_curr['q'] .= $tokens['cond'][$i]['value'];
			}
		}
		
		
		// render the form
		//////////////////
		
		$glossarId = $this->framework->iniRead('advanced.help', '3241')
		?>
		<div id="adv_all">
			<div id="adv_title">
				Erweiterte Suche
				<a href="<?php echo $this->framework->getHelpUrl($glossarId); ?>" class="wisy_help">i</a>
			</div>
			<div id="adv_body">
				<form action="advanced" method="get">
					<table width="100%">
						<?php
							
							reset($this->presets);
							while( list($field_name, $preset) = each($this->presets) )
							{
								if( isset($preset['decoration']['headline_left']) )
								{
									echo 	'<tr>'
										.		'<td><small>&nbsp;</small><br /><span class="headline_left">'.$preset['decoration']['headline_left'].'</span></td>'
										.		'<td valign="bottom"><span class="headline_right">'.$preset['decoration']['headline_right'].'</span></td>'
										.	'</tr>';
								}
								
								echo '<tr>';
									echo '<td width="10%" nowrap="nowrap"><label for="adv_' . $field_name . '">' .$preset['descr']. '</label></td>';
									echo '<td>';
										if( $preset['type'] == 'text' )
										{
											$autocomplete = $preset['autocomplete']? ' class="'.$preset['autocomplete'].'" ' : '';
											echo "<input type=\"text\" name=\"adv_$field_name\" id=\"adv_$field_name\" $autocomplete value=\"" .isohtmlspecialchars($presets_curr[$field_name]). "\" />";
										}
										else
										{
											echo '<select name="adv_' .$field_name. '">';
												reset($preset['options']);
												while( list($value, $descr) = each($preset['options']) )
												{
													$selected = strval($presets_curr[$field_name])==strval($value)? ' selected="selected"' : '';
													echo "<option value=\"$value\"$selected>$descr</option>";
												}
											echo '</select>';
										}
									echo '</td>';
								echo '</tr>';
							}
							
						?></tr>

					</table>
		
					<div id="adv_buttons">
						<input type="hidden" name="adv_subseq" value="1" />
						<input type="submit" name="adv_searchkurse" id="adv_searchkurse" value="Kursangebote suchen" />
						<input type="submit" name="adv_searchanb" id="adv_searchanb" value="Anbieter suchen" />						
						<input type="submit" name="adv_cancel" id="adv_cancel" value="Abbruch" />
					</div>
				</form>
			</div>
		</div>
		
		<?php
	}

	/**********************************************************************
	 * render, main
	 **********************************************************************/

	function render()
	{

		/**********************************************************************
		 * handle submitted data
		 **********************************************************************/
	

		if( isset($_GET['adv_subseq']) )
		{
			if( isset($_GET['adv_cancel']) )
			{
				header('Location: search');
				exit();
			}
			else
			{
				$q = '';
				reset($this->presets);
				while( list($field_name, $preset) = each($this->presets) )
				{
					$item = trim($_GET['adv_' . $field_name]);
					if( $item != '' )
					{
						if( $preset['comma_to_slash'] )
						{
							$item = str_replace(', ', '/', $item);
							$item = str_replace(',', '/', $item);
						}
					
						$q .= $q==''? '' : ', ';
						if( $preset['function'] )
							$q .= $preset['function'];
						$q .= $item;
					}
				}
				
				if( isset($_GET['adv_searchanb']) )
				{
					$q .= $q==''? '' : ', ';
					$q .= 'Zeige:Anbieter';
				}
				
				header('Location: search?q=' . urlencode($q));
				exit();
			}
		}


		/**********************************************************************
		 * render
		 **********************************************************************/
		 
		
		if( intval($_GET['ajax']) )
		{
			header('Content-type: text/html; charset=ISO-8859-15');
			$this->renderForm();
		}
		else
		{
			echo $this->framework->getPrologue(array('title'=>'Erweiterte Suche', 'canonical'=>$this->framework->getUrl('advanced'), 'bodyClass'=>'wisyp_search'));
			$this->renderForm();
			echo $this->framework->getEpilogue();
		}
	}
};

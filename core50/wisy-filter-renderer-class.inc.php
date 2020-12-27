<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_ADVANCED_RENDERER_CLASS');

class WISY_FILTER_RENDERER_CLASS extends WISY_ADVANCED_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// call parent class constructor
		parent::__construct($framework);
		
		// Aus advanced-renderer geerbte Presets anpassen
		
		// Hauptsuchfeld ausblenden
		$this->presets['q']['type'] = 'hidden';
		
		// Kurszeitpunkt
		$this->presets['datum']['decoration']['headline_left'] = 'Kurszeitpunkt';
		$this->presets['datum']['descr'] = 'Beginn';
		$this->presets['datum']['classes'] = 'wisyr_c2_3 break';
		$this->presets['dauer']['classes'] = 'wisyr_c1_3';
		$this->presets['tageszeit']['descr'] = 'Tageszeit';
		$this->presets['tageszeit']['classes'] = 'wisyr_c1_3';
		
		// Umkreissuche
		$this->presets['bei']['decoration']['headline_right'] = '';
		$this->presets['bei']['descr'] = 'PLZ oder Ort';
		$this->presets['bei']['classes'] = 'wisyr_c2_3';
		$this->presets['km']['descr'] = 'Umkreis';
		$this->presets['km']['classes'] = 'wisyr_c1_3';
		
		// Weiter Optionen
		$this->presets['preis']['decoration']['headline_left'] = 'Weitere Optionen';
		$this->presets['preis']['descr'] = 'Preis';
		$this->presets['preis']['classes'] = 'wisyr_c1_3';
		$this->presets['foerderung']['descr'] = 'Förderung';
		$this->presets['foerderung']['classes'] = 'wisyr_c2_3';
		
		$this->presets['zielgruppe']['descr'] = 'Zielgruppe';
		$this->presets['zielgruppe']['classes'] = 'wisyr_c1_3';
		$this->presets['qualitaetszertifikat']['descr'] = 'Qualitätszertifikat';
		$this->presets['qualitaetszertifikat']['classes'] = 'wisyr_c2_3';
		
		$this->presets['unterrichtsart']['descr'] = 'Unterrichtsart';
		$this->presets['unterrichtsart']['classes'] = 'wisyr_c1_3';
		
		// Volltext, PLZ entfernen
		unset($this->presets['volltext']);
		unset($this->presets['plz']);
	}
	
	/**********************************************************************
	 * render, misc.
	 **********************************************************************/

	function renderForm($q, $records)
	{
		
		// explode the query string to its tokens
		/////////////////////////////////////////
		
		$presets_curr = array();

		$q = $this->framework->getParam('q');
		$q = cs8($q); // !
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$tokens = $searcher->tokenize($q);
		for( $i = 0; $i < sizeof((array) $tokens['cond']); $i++ )
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
					foreach($this->presets as $field_name => $preset)
					{
						if( $preset['type'] == 'taglist' && !isset($presets_curr[$field_name]) )
						{
							reset($preset['options']);
							foreach(array_keys($preset['options']) as $value)
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
		
		?>
		<div id="wisy_filter_all">
			<h1 id="filter_title">Ergebnisse filtern</h1>
			<h2 id="filter_description">Begrenzen Sie Ihre Suche, in dem Sie Filter- und Anzeigeeinstellungen an Ihre Wünsche anpassen.</h2>
			<div id="filter_body">
				<form action="filter" method="get">
					<div id="filter_form">
						<?php
							
							reset($this->presets);
							$fieldsets_open = 0;
							foreach($this->presets as $field_name => $preset)
							{
								if( isset($preset['decoration']['headline_left']) )
								{
									if($fieldsets_open > 0) {
										echo '</fieldset>';
										$fieldsets_open -= 1;
									}
									$fieldsets_open += 1;
									echo 	'<fieldset>';
									if(trim($preset['decoration']['headline_left'] != '')) {
										echo '<legend><span class="headline_left">'.$preset['decoration']['headline_left'].'</span>';
										echo ' <span class="headline_right">'.$preset['decoration']['headline_right'].'</span></legend>';
									}
								}
								if( $preset['type'] == 'hidden' )
								{
									echo "<input type=\"hidden\" name=\"filter_$field_name\" id=\"filter_$field_name\" value=\"" .htmlspecialchars($presets_curr[$field_name]). "\" />";
								} else {
									echo '<div class="formrow ' . $preset['classes'] . '">';
										echo '<label for="filter_' . $field_name . '">' .$preset['descr']. '</label>';
										echo '<div class="formfield">';
											if( $preset['type'] == 'text' )
											{
												$autocomplete = $preset['autocomplete']? ' class="'.$preset['autocomplete'].'" ' : '';
												echo "<input type=\"text\" name=\"filter_$field_name\" id=\"filter_$field_name\" $autocomplete value=\"" .htmlspecialchars($presets_curr[$field_name]). "\" />";
											}
											else
											{
												echo '<select name="filter_' .$field_name. '">';
													reset($preset['options']);
													foreach($preset['options'] as $value => $descr)
													{
														$selected = strval($presets_curr[$field_name])==strval($value)? ' selected="selected"' : '';
														echo "<option value=\"$value\"$selected>$descr</option>";
													}
												echo '</select>';
											}
										echo '</div>';
									echo '</div>';
								}
							}
							if($fieldsets_open > 0) {
								echo '</fieldset>';
								$fieldsets_open -= 1;
							}
							
						?>
					</div>
		
					<div id="filter_buttons">
						<input type="hidden" name="filter_subseq" value="1" />
						<input type="submit" name="filter" id="filter" value="Suche anpassen" />
						<input type="submit" name="filter_close" id="filter_close" value="Abbrechen" /> oder <input type="reset" name="filter_reset" id="filter_reset" value="alle Filter zurücksetzen" />
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
	

	    if( $this->framework->getParam('filter_subseq', false) )
	    {
	        
	        if( $this->framework->getParam('filter_close', false) )
	        {
				header('Location: search');
				exit();
			}
			else
			{
				$q = '';
				reset($this->presets);
				foreach($this->presets as $field_name => $preset)
				{
				    $item = trim( strval( $this->framework->getParam('filter_' . $field_name) ) );
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
			
				if( $this->framework->getParam('filter_searchanb', false) )
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
		 
		
		if( intval( $this->framework->getParam('ajax') ) )
		{
			header('Content-type: text/html; charset=utf-8');
			$this->renderForm(null, null);
		}
		else
		{
			echo $this->framework->getPrologue(array('title'=>'Filtern', 'canonical'=>$this->framework->getUrl('filter'), 'bodyClass'=>'wisyp_search_filter'));
			$this->renderForm(null, null);
			echo $this->framework->getEpilogue();
		}
	}
};
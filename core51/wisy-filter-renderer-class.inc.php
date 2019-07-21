<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_ADVANCED_RENDERER_CLASS');

class WISY_FILTER_RENDERER_CLASS extends WISY_ADVANCED_RENDERER_CLASS
{
	var $framework;
    var $tokens;

	function __construct(&$framework)
	{
		// call parent class constructor
		parent::__construct($framework);
			
		$this->db = new DB_Admin;
	}
	
	/**********************************************************************
	 * render, main
	 **********************************************************************/

	function render()
	{
		
		if( intval($_GET['ajax']) )
		{
			header('Content-type: text/html; charset=utf-8');
			$this->renderForm();
		}
		else
		{
			echo $this->framework->getPrologue(array('title'=>'Filtern', 'canonical'=>$this->framework->getUrl('filter'), 'bodyClass'=>'wisyp_search_filter'));
			$this->renderForm();
			echo $this->framework->getEpilogue();
		}
	}
	
	
	function renderForm($q, $records, $hlevel=1)
	{
		
        $renderformData = array();
        
		echo '<div id="wisyr_filterform" class="wisyr_filterform">';
		echo '<div class="wisyr_filterform_header"><h' . ($hlevel + 1) . '>Suchauftrag anpassen</h' . ($hlevel + 1) . '><h' . ($hlevel + 2) . '>Nutzen Sie Filter, um Ihre Suche weiter einzugrenzen:</h' . ($hlevel + 2) . '></div>';
		echo '<form action="search" method="get" name="filterform" role="search" aria-label="Suchauftrag anpassen">';
		echo '<input type="hidden" name="qs" value="' . $this->framework->QS . '" />';
		echo '<input type="hidden" name="qf" value="' . $this->framework->QF . '" />';
        
        // Workaround for "Volltext", TODO: optimize
		// Workaround for "Zeige", TODO: optimize
        if(is_array($this->framework->tokensQF)) {
            foreach($this->framework->tokensQF as $t) {
                if($t['field'] == 'volltext') {
                    echo '<input type="hidden" name="filter_volltext" value="' . $t['value'] . '" />';
                    break;
                }
                if($t['field'] == 'zeige') {
                    echo '<input type="hidden" name="filter_zeige" value="' . $t['value'] . '" />';
                    break;
                }
            }
        }
		
        if(count($records))
        {
    		$renderformData['records_simple'] = $records;
		
    		$renderformData['records_ids'] = array();
    		$renderformData['records_themen'] = array();
    		$renderformData['records_anbieter'] = array();
    		while( list($i, $record) = each($renderformData['records_simple']['records']) )
    		{
    			$renderformData['records_ids'][] = $record['id'];
    			$renderformData['records_themen'][] = $record['thema'];
    			$renderformData['records_anbieter'][] = $record['anbieter'];
    		}
    		$renderformData['records_themen'] = array_unique($renderformData['records_themen']);
    		sort($renderformData['records_themen']);
		
    		$renderformData['records_anbieter'] = array_unique($renderformData['records_anbieter']);
    		sort($renderformData['records_anbieter']);
		
    		// Anfrage anhand der Liste der $records['id']s die alle Durchführungen dafür findet um enthaltene Kursbeginne, Preise und Orte (für Umkreis) sowie Dauer zu finden.
    		$kursids = implode(',', $renderformData['records_ids']);
    		$renderformData['records_preis_min'] = 99999;
    		$renderformData['records_beginn_max'] = 0;
    		$renderformData['records_plz'] = array();
    		$renderformData['records_ort'] = array();
			$renderformData['records_dauer'] = array();
			$renderformData['records_dauer_min'] = 0;
			$renderformData['records_dauer_max'] = 0;
    		$this->db->query("SELECT preis, beginn, plz, ort, dauer FROM kurse_durchfuehrung, durchfuehrung WHERE primary_id IN($kursids) AND id=secondary_id AND (beginn>='".strftime("%Y-%m-%d 00:00:00")."' OR (beginn='0000-00-00 00:00:00' AND beginnoptionen>0))");
    		while( $this->db->next_record() )
    		{
    			$renderformData['preis'] = intval($this->db->f8('preis'));
    			if($renderformData['preis'] !== '' && $renderformData['preis'] >= 0 && $renderformData['preis'] < $renderformData['records_preis_min']) $renderformData['records_preis_min'] = $renderformData['preis'];

    			$renderformData['beginn'] = strtotime($this->db->f8('beginn'));
    			if($renderformData['beginn'] !== false && $renderformData['beginn'] > $renderformData['records_beginn_max']) $renderformData['records_beginn_max'] = $renderformData['beginn'];
			
    			$renderformData['records_beginn'][] = $this->db->f8('beginn');
    			$renderformData['records_plz'][] = $this->db->f8('plz');
    			$renderformData['records_ort'][] = $this->db->f8('ort');
				
				$renderformData['records_dauer'][] = $this->db->f8('dauer');
    		}
			$this->db->free();
    		$renderformData['records_plz'] = array_unique($renderformData['records_plz']);
    		$renderformData['records_ort'] = array_unique($renderformData['records_ort']);
			$renderformData['records_dauer'] = array_unique($renderformData['records_dauer']);
			sort($renderformData['records_dauer']);
			if(count($renderformData['records_dauer'])) {
				$renderformData['records_dauer_min'] = $renderformData['records_dauer'][0];
				$renderformData['records_dauer_max'] = $renderformData['records_dauer'][count($renderformData['records_dauer'])-1];
			}
			
			// Anfrage anhand der Liste der $records['id']s die alle Durchführungs-Tags findet um enthaltene Tageszeit, Förderungen, Zielgruppe, Zertifikate, Unterrichtsart zu finden.
			$renderformData['records_taglist'] = array();
    		$this->db->query("SELECT DISTINCT t.tag_name as tagname FROM x_kurse_tags k LEFT JOIN x_tags t ON k.tag_id=t.tag_id WHERE k.kurs_id IN($kursids)");
    		while( $this->db->next_record() )
    		{
				$renderformData['records_taglist'][] = $this->db->f8('tagname');
			}
			$this->db->free();
        }
		
		// TODO db: analoger zu filter-klasse aufbauen und nicht manuell zb. hier den q-tag ausgeben:
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$this->tokens = $searcher->tokenize($q);
		
		$renderformData['fv_bei'] = '';
		$renderformData['fv_km'] = '';
		$renderformData['km_arr'] = array('' => 'Umkreis', '10' => '10 km', '25' => '25 km', '50' => '50 km', '500' => '>50 km');
		$renderformData['fv_preis'] = '';
		$renderformData['fv_datum'] = '';
		$renderformData['fv_anbieter'] = '';
		$renderformData['fv_dauer'] = '';
		$renderformData['fv_dauer_von'] = '';
		$renderformData['fv_dauer_bis'] = '';
		$renderformData['fv_foerderung'] = '';
		$renderformData['fv_zielgruppe'] = '';
		$renderformData['fv_qualitaetszertifikat'] = '';
		$renderformData['fv_unterrichtsart'] = '';
		$renderformData['fv_tageszeit'] = '';
		
		foreach($this->framework->tokensQF as $token) {
			switch( $token['field'] ) {
				case 'bei':	
					$renderformData['fv_bei'] = $token['value']; 
					break;
					
				case 'km':	
					$renderformData['fv_km'] =  intval($token['value']);
					if( $renderformData['fv_km'] <= 0 ) $renderformData['fv_km'] = '';
					if( !$renderformData['km_arr'][$renderformData['fv_km']] ) $renderformData['km_arr'][$renderformData['fv_km']] = $renderformData['fv_km'] . " km";
					break;
				
				case 'preis':
					$renderformData['fv_preis'] = $token['value'];
					if( preg_match('/^([0-9]{1,9})$/', $renderformData['fv_preis'], $matches) )
					{	
						$renderformData['fv_preis'] = intval($matches[1]);
                        $renderformData['fv_preis_bis'] = intval($matches[1]);
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $renderformData['fv_preis'], $matches) )
					{	
						$renderformData['fv_preis_von'] = intval($matches[1]);
						$renderformData['fv_preis_bis'] = intval($matches[2]);
					}
					else
					{
						$renderformData['fv_preis'] = '';
					}
					break;
					
				case 'dauer':
					$renderformData['fv_dauer'] = $token['value'];
					if( preg_match('/^([0-9]{1,9})$/', $renderformData['fv_dauer'], $matches) )
					{	
						$renderformData['fv_dauer'] = intval($matches[1]);
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $renderformData['fv_dauer'], $matches) )
					{	
						$renderformData['fv_dauer_von'] = intval($matches[1]);
						$renderformData['fv_dauer_bis'] = intval($matches[2]);
					}
					else
					{
						$renderformData['fv_dauer'] = '';
					}
					
					break;
					
				case 'datum':
					$renderformData['fv_datum'] = $token['value'];
					break;
					
				case 'foerderung':
					$renderformData['fv_foerderung'] = $token['value'];
					break;
					
				case 'zielgruppe':
					$renderformData['fv_zielgruppe'] = $token['value'];
					break;
					
				case 'qualitaetszertifikat':
					$renderformData['fv_qualitaetszertifikat'] = $token['value'];
					break;
					
				case 'unterrichtsart':
					$renderformData['fv_unterrichtsart'] = $token['value'];
					break;
					
				case 'tageszeit':
					$renderformData['fv_tageszeit'] = $token['value'];
					break;
					
				case 'anbieter':
					$renderformData['fv_anbieter'] = $token['value'];
					break;
			}
		}
		
		$renderformData['order'] = $this->framework->order;
        
        $filtermenu = new WISY_FILTERMENU_CLASS($this->framework, $renderformData);
        echo $filtermenu->getHtml();
        
		$orders = array(
			'b'  => 'Datum: aufsteigend', 
			'p'  => 'Preis: aufsteigend',
			'pd' => 'Preis: absteigend',
			'a'  => 'Anbieter: aufsteigend',
			'ad' => 'Anbieter: absteigend',
		);
		if($renderformData['order'] == '') $renderformData['order'] = 'b';
		echo '<fieldset class="wisyr_filtergroup wisyr_filter_select filter_sortierung ui-front">';
        echo '	<legend data-filtervalue="' . $orders[$renderformData['order']] . '">Sortierung</legend>';
		echo '	<select name="filter_order" class="wisyr_selectmenu">';
		foreach($orders as $key => $value)
		{
			echo '		<option value="' . $key . '"';
			if($key == $renderformData['order']) echo ' selected="selected"';
			echo '>' . $value . '</option>';
		}
		echo '	</select>';
		echo '</fieldset>';
		
		echo '</form>';
		echo '</div>';
	}
};

class WISY_FILTERMENU_ITEM
{
    var $framework;
    var $renderer;
	var $data;
    var $renderformData;
	var $children;
	var $zindex;
	
	function __construct($framework, $data, $renderformData, $zindex)
	{
        $this->framework = $framework;
		$this->data = $data;
        $this->renderformData = $renderformData;
		$this->children = array();
		$this->zindex = $zindex;
	}
	
	function getHtml()
	{
        $filterclasses = $this->getFilterclasses($this->data, array('wisyr_filtergroup'));
		$legendvalue = $this->getLegendvalue($this->data['legendkey']);
        $title = $this->data['title'];
        
		$ret = '<fieldset class="' . $filterclasses . '" style="z-index:' . $this->zindex . '" tabindex="0">';
        
		    $ret .= '<legend data-filtervalue="' . $legendvalue . '">' . $title . '</legend>';
            $ret .= '<div class="filter_inner clearfix">';
            
            if(isset($this->data['sections']) && count($this->data['sections'])) {
                $ret .= $this->getSections($this->data['sections']);
            } else {
                $ret .= $this->getFormfields($this->data);
            }
            
            $ret .= '<input class="filter_submit" type="submit" value="Übernehmen" />';
            $ret .= '</div>';
            
		$ret .= '</fieldset>';
		
		return $ret;
	}
    
    function getSections($sections)
    {
        $ret = '';
        foreach($sections as $key => $data) {
            
            $filterclasses = $this->getFilterclasses($data);
			$legendvalue = $this->getLegendvalue($data['legendkey']);
			
            if(isset($data['resetfilter']) && strlen($data['resetfilter'])) {
                $ret .= '<a class="wisyr_filterform_reset" href="' . $this->framework->filterer->getSearchUrlWithoutFilters() . '">' . $data['resetfilter'] . '</a>';
				continue;
            }
            
            $ret .= '<fieldset class="' . $filterclasses . '">';
            $ret .= '<legend data-filtervalue="' . $legendvalue . '">' . $data['title'] . '</legend>';
        
            $ret .= $this->getFormfields($data);

            $ret .= '</fieldset>';
			if(isset($data['no_autosubmit_mobile']) && $data['no_autosubmit_mobile'] == 1) {
				$ret .= '<input class="filter_submit filter_filtersection_submit" type="submit" value="Übernehmen" />';
			}
        }
        
        return $ret;
    }
    
    function getFilterclasses($data, $fsc=array()) {
                
        $fsc[] = $data['class']; // -> filtermenu.1.class = filter_zweispaltig        
        if($data['autosubmit'] == 1) $fsc[] = 'wisyr_filter_autosubmit'; // -> filtermenu.1.autosubmit = 1
        if($data['autoclear'] == 1) $fsc[] = 'wisyr_filter_autoclear'; // -> filtermenu.1.autoclear = 1   
		if(isset($data['no_autosubmit_mobile']) && $data['no_autosubmit_mobile'] == 1) $fsc[] = 'no_autosubmit_mobile';     
        
        if(isset($data['function']) && strlen($data['function'])) {
            $fsc[] = 'filter_' . $data['function'];
        }
        
        if(isset($data['autofill']) && strlen($data['autofill'])) {
            $fsc[] = 'wisyr_filter_autofill'; // -> filtermenu.1.input.autofill = datum_von
        }
        
        return implode(' ', $fsc);
    }
    
    function getFormfields($data) {
        
        $ret = '';
        
        $function = '';
        if(isset($data['function'])) $function = $data['function'];
        
        if(isset($data['input'])) {
            foreach($data['input'] as $input) {
                
                $currentFound = false;
        
                $type = '';
                $fieldvaluename = '';
                $fieldname = '';               
                $fieldvalue = '';
                $fieldclass = '';
                $fieldplaceholder = '';
                $fieldsuffix = '';
                $autofilltarget = '';
                $clearbutton = false;
                
                if(isset($input['type'])) $type = $input['type'];
                if(isset($input['value'])) $fieldvaluename = $input['value'];
                $fieldname = $fieldvaluename;
                if(isset($input['name']) && strlen($input['name'])) $fieldname = $input['name'];
                if(isset($this->renderformData['fv_' . $fieldvaluename])) $fieldvalue = $this->renderformData['fv_' . $fieldvaluename];
                if(isset($input['class'])) $fieldclass = $input['class'];
                if(isset($input['placeholder'])) $fieldplaceholder = $input['placeholder'];
                if(isset($input['autofilltarget'])) $autofilltarget = $input['autofilltarget'];
                if(isset($input['suffix'])) $fieldsuffix = $input['suffix'];
                if(isset($input['clearbutton']) && $input['clearbutton'] == 1) $clearbutton = true;
        
                if(isset($input['options']) && count($input['options'])) {
                    $filtervalues = $input['options'];
                } else {
                    $filtervalues = $this->getFiltervalues($input);
                }
        
                switch($type) {
            
                    case 'textfield':
            
                        if($clearbutton) $ret .= '<div class="filter_clearbutton_wrapper">';
                        $ret .= '<input type="text" name="filter_' . $fieldname . '" id="filter_' . $fieldname . '" class="' . $fieldclass . '" value="' . $fieldvalue . '" placeholder="' . $fieldplaceholder . '" />';
                        if($clearbutton) $ret .= '</div>';
                        $ret .=  $fieldsuffix . '<br /><br />';
        
                    break;
            
                    case 'radiolinklist':
            
                        foreach($filtervalues as $value => $label) {
                                            
                            $ret .= '<div class="wisyr_radiowrapper">';
                            $ret .= '   <input type="radio" name="filter_' . $fieldname . '" id="filter_' . $fieldname . '_' . $value . '" value="' . ($label == 'Alle' ? '' : str_replace(',', ' ', $label)) . '"';
                
                            if(str_replace(',', ' ', $label) == $fieldvalue) {
                                $ret .= ' checked="checked"';
                                $currentFound = true;
                            }
                
                            $ret .= ' />';
                            $ret .= '	<label for="filter_' . $fieldname . '_' . $value . '">' . $label . '</label>';
            
                            $ret .= '</div>';
                        }
            
                        if(!$currentFound && trim($fieldvalue) != '') {
                            $ret .= '<input type="hidden" name="filter_' . $fieldname . '" value="' . $fieldvalue . '" />';
                        }
            
                    break;
            
                    case 'radiolist':
            
                        foreach($filtervalues as $value => $label) {
                    
							$processed_value = $this->getProcessedValue($function, $value, $label);
                            $checked = $this->getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            $disabled = $this->getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                    
                            $ret .= '<div class="wisyr_radiowrapper">';
                    
                        	$ret .= '   <input type="radio" name="filter_' . $fieldname . '" id="filter_' . $fieldname . '_' . $value . '" value="' . $processed_value . '"';
                            if(strlen($autofilltarget)) {
                                $ret .= 'data-autofilltarget="#filter_' . $autofilltarget . '" data-autofillvalue="' . $processed_value . '"';
                            }
                    
                            if($checked) $ret .= ' checked="checked"';
                            if($disabled) $ret .= ' disabled="disabled"';
                    
                        	$ret .= ' />';
                        	$ret .= '   <label for="filter_' . $fieldname . '_' . $value . '">' . $label . '</label>';
                        	$ret .= '</div>';
                        }
            
                    break;
                    
                    case 'selectmenu':
                        
                        $ret .= '<select name="filter_' . $fieldname . '" class="wisyr_selectmenu">';
                        foreach($filtervalues as $value => $label) {
							
							$processed_value = $this->getProcessedValue($function, $value, $label);
							$checked = $this->getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
							$disabled = $this->getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);

                			$ret .= '<option value="' . $processed_value . '"';
                            if($checked) $ret .= ' selected="selected"';
							if($disabled && $processed_value != '') $ret .= ' disabled="disabled"';
                            $ret .= '>' . $label . '</option>';
                        }
                        $ret .= '</select>';
                    break;
            
                }
            }
        }
        
        return $ret;
    }
    
    function getFiltervalues($data)
    {        
        switch($data['datafunction']) {
            
            case 'anbieter':
                return $this->framework->filterer->getAnbieterFilters($this->framework->tokens['cond'], $this->renderformData['records_anbieter']);
            break;
            
            case 'foerderungen':
    		    return $this->getSpezielleStichw(2, $data['datawhitelist']);
            break;
            
            case 'zielgruppen':
                return $this->getSpezielleStichw(8, $data['datawhitelist']);
            break;
            
            case 'qualitaetszertifikate':
                return $this->getSpezielleStichw(4, $data['datawhitelist']);
            break;
            
            case 'unterrichtsarten':
                return $this->getSpezielleStichw(32768, $data['datawhitelist']);
            break;
                
            default:
                return array();
        }
    }
	
	function getLegendvalue($legendkey) {
			
		$legendvalue = '';
				
        if(strlen($legendkey)) {
            $legendvalue = $this->renderformData['fv_' . $legendkey];
			
			switch($legendkey) {
				case 'preis':
					if($legendvalue > 0) {
						if(strpos($legendvalue, '-') === false) {
							$legendvalue = 'bis ' . $legendvalue;
						}
						$legendvalue = $legendvalue . ' EUR';
					} else if($legendvalue === 0) {
						$legendvalue = 'kostenlos';
					}
				break;
				case 'dauer':
					if($legendvalue != '') {
						// TODO db: das könnte hier und weiter oben noch etwas netter werden statt "1-999 Tag"
						if(preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $legendvalue, $matches)) {	
							$dauer_von = intval($matches[1]);
							$dauer_bis = intval($matches[2]);
							$legendvalue = 'min. ' . $dauer_von . ' - max. ' . $dauer_bis;
						}
						$legendvalue = $legendvalue . ' Tage';
					}
					
				break;
			}		
        }
		
		return $legendvalue;
	}
	
	function getProcessedValue($function, $value, $label) {
		
		$processed_value = $value;
		
		switch($function) {
			case 'kursbeginn_vorschlaege':
				$processed_value = date('d.m.Y', strtotime("+$value day"));
			break;
			
			default:
			if($value === 'alle' || $value === 'Alle') {
				$processed_value = '';
			}
			break;
		}
		
		return $processed_value;
	}
	
	function getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname) {

		$disabled = false;
		
		switch($function) {
			case 'kursbeginn_vorschlaege':
	            if($value !== '' && intval($value) > 0 && strtotime('+' . intval($value) . ' days') > $this->renderformData['records_beginn_max']) {
	                $disabled = true;
	            }
			break;
			
            case 'preis_vorschlaege':                
    			if($value !== 'alle' && $value !== $fieldvalue && $value < $this->renderformData['records_preis_min']) { 
    				$disabled = true;
    			}
            break;
			
			case 'dauer':
				if($fieldname == 'dauer_von') {
					if($value > $this->renderformData['records_dauer_max']) $disabled = true;
				} else if($fieldname == 'dauer_bis') {
					if($value < $this->renderformData['records_dauer_min']) $disabled = true;
				}
			break;
			
			case 'taglist':
				if(is_array($this->renderformData['records_taglist']) && !in_array($value, $this->renderformData['records_taglist'])) $disabled = true;
			break;
		}
		
		return $disabled;
	}
	
	function getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname) {
		
		$checked = '';
		
		/*
		switch($function) {
			case 'xyz':
				if(1 != 1) {
					$checked = true;
				}
			}
			break;
		}
		*/
		
		if($checked === '') { // allows checked-test to be overriden above
			if($processed_value === $fieldvalue) {
				$checked = true;
			} else {
				$checked = false;
			}
		}
		
		return $checked;
	}
    
	private function getSpezielleStichw($flag, $whitelist='')
	{
		// nur die stichwörter zurückgeben, die im aktuellem Portal auch verwendet werden!
		$keyPrefix = "advStichw.$flag";
		$magic = strftime("%Y-%m-%d-v5-").md5($GLOBALS['wisyPortalFilter']['stdkursfilter']);
		if( $this->framework->cacheRead("adv_stichw.$flag.magic") != $magic )
		{
			$specialInfo =& createWisyObject('WISY_SPECIAL_INFO_CLASS', $this->framework);
			$specialInfo->recalcAdvStichw($magic, $flag);
		}
		$ids_str = $this->framework->cacheRead("adv_stichw.$flag.ids");
	
        $ids_filtered = $ids_str;
        if(strlen(trim($whitelist))) {
            $ids_filtered = array();
            $og_ids = array_map('trim', explode(',', $ids_str));
            $wl_ids = explode(',', $whitelist);
            foreach($wl_ids as $wl_id) {
                if(in_array($wl_id, $og_ids)) {
                    $ids_filtered[] = $wl_id;
                }
            }
            $ids_filtered = implode(',', $ids_filtered);
        }
    
		// query!
		$ret = array(''=>'Alle');
        if(strlen(trim($ids_filtered))) {
    		$db = new DB_Admin;
    		//$db->query("SELECT stichwort FROM stichwoerter WHERE eigenschaften=$flag ORDER BY stichwort_sorted;");
    		$db->query("SELECT stichwort FROM stichwoerter WHERE id IN ($ids_filtered) ORDER BY stichwort_sorted;");
    		while( $db->next_record() )
    		{
    			$stichw = htmlspecialchars($db->f8('stichwort'));
    			$stichw = trim(strtr($stichw, array(': '=>' ', ':'=>' ', ', '=>' ', ','=>' ')));
			
    			$ret[ $stichw ] = $stichw;
    		}
			$db->free();
        }
		return $ret;
	}
};

class WISY_FILTERMENU_CLASS
{
	var $framework;
	var $prefix;
	var $root;

	function __construct($framework, $renderformData, $param=array())
	{
		// constructor
		$this->framework = $framework;
        $this->renderformData = $renderformData;
        $this->prefix = array_key_exists('prefix', $param) ? $param['prefix'] : 'filtermenu';
		$this->db = new DB_Admin;
		$this->start_s = $this->framework->microtime_float();
	}
	
    function parseStructure()
    {
        global $wisyPortalEinstellungen;
        reset($wisyPortalEinstellungen);
            
        $filterStructure = array();        
        
		$allPrefix = $this->prefix . '.';
		$allPrefixLen = strlen($allPrefix);
		while( list($key, $value) = each($wisyPortalEinstellungen) )
		{
            
            if( substr($key, 0, $allPrefixLen)==$allPrefix )
            {
                $levels = explode('.', substr($key, $allPrefixLen));
                
                if(!array_key_exists($levels[0], $filterStructure)) $filterStructure[$levels[0]] = array();
                
                switch(count($levels)) {
                                        
                    case 1:
                        // Title of top level element
                        // -> filtermenu.2 = Preis
                        $filterStructure[$levels[0]]['title'] = utf8_encode($value);
                    break;
                    
                    case 2:
                        if(is_numeric($levels[1])) {
                            // Title of 2nd level element
                            // -> filtermenu.2.1 = Vorschläge
                            $filterStructure[$levels[0]]['sections'][$levels[1]]['title'] = utf8_encode($value);
                        } else {
                            // Option of top level element
                            // -> filtermenu.1.class = filter_zweispaltig
                            $filterStructure[$levels[0]][$levels[1]] = utf8_encode($value);
                        }
                    break;
                    
                    case 3:
                        // Option of 2nd level element
                        // -> filtermenu.2.1.function = preis_vorschlaege
                        $filterStructure[$levels[0]]['sections'][$levels[1]][$levels[2]] = utf8_encode($value);
                    break;
                    
                    case 4:
                        // Instance of 1st level input element
                        // -> filtermenu.4.input.1.type = radiolinklist
                        $filterStructure[$levels[0]][$levels[1]][$levels[2]][$levels[3]] = utf8_encode($value);
                    break;
                    
                    case 5:
                        // Second level Input options
                        // -> filtermenu.2.2.input.1.type = textfield
                        if([$levels[4]] == 'options') {
                            $filterStructure[$levels[0]][$levels[1]][$levels[2]][$levels[3]][$levels[4]] = array();
                        } else {
                            $filterStructure[$levels[0]]['sections'][$levels[1]][$levels[2]][$levels[3]][$levels[4]] = utf8_encode($value);
                        }
                    break;
                    
                    case 6:
                        // Second level Input Option options
                        // -> filtermenu.2.1.input.1.options.0 = Kostenlos
                        $filterStructure[$levels[0]]['sections'][$levels[1]][$levels[2]][$levels[3]][$levels[4]][$levels[5]] = utf8_encode($value);
                    break;                
                }              
            }
        }
        
        ksort($filterStructure);
        return $filterStructure;
    }
    
    function getHtml()
    {
        
		global $wisyPortalModified;
        
        // TODO db: // read the menu from the cache ...
        
        
        
        $filterStructure = $this->parseStructure();        
        
		$zindex = 1111;
        foreach($filterStructure as $key => $filterItem) {
            $item = new WISY_FILTERMENU_ITEM($this->framework, $filterItem, $this->renderformData, $zindex--);
            echo $item->getHtml();
        }
        
        // TODO db: // add time
        
        // TODO db: // write the complete menu to the cache
    }
};
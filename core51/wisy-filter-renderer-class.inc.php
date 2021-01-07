<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_ADVANCED_RENDERER_CLASS');

class WISY_FILTER_RENDERER_CLASS extends WISY_ADVANCED_RENDERER_CLASS
{
	var $framework;
    var $tokens;
    var $checked_values_combStr = array();
    var $max_preis = '999999';

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
		
	    if( intval( $this->framework->getParam('ajax') ) )
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
	
	
	function prepareFormData($q, $records)
	{
	    $renderformData = array();
	    
	    if(count((array) $records))
	    {
    		$renderformData['records_simple'] = $records;
		
    		$renderformData['records_ids'] = array();
    		$renderformData['records_themen'] = array();
    		$renderformData['records_anbieter'] = array();
    		foreach($renderformData['records_simple']['records'] as $i => $record)
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
    		    $renderformData['preis'] = intval($this->db->fcs8('preis'));
    		    if($renderformData['preis'] !== '' && $renderformData['preis'] >= 0 && $renderformData['preis'] < $renderformData['records_preis_min']) $renderformData['records_preis_min'] = $renderformData['preis'];
    		    
    		    $renderformData['beginn'] = strtotime($this->db->fcs8('beginn'));
    		    if($renderformData['beginn'] !== false && $renderformData['beginn'] > $renderformData['records_beginn_max']) $renderformData['records_beginn_max'] = $renderformData['beginn'];
    		    
    		    $renderformData['records_beginn'][] = $this->db->fcs8('beginn');
    		    $renderformData['records_plz'][] = $this->db->fcs8('plz');
    		    $renderformData['records_ort'][] = $this->db->fcs8('ort');
    		    
    		    $renderformData['records_dauer'][] = $this->db->fcs8('dauer');
    		}
			$this->db->free();
    		$renderformData['records_plz'] = array_unique($renderformData['records_plz']);
    		$renderformData['records_ort'] = array_unique($renderformData['records_ort']);
			$renderformData['records_dauer'] = array_unique($renderformData['records_dauer']);
			sort($renderformData['records_dauer']);
			if(count((array) $renderformData['records_dauer'])) {
				$renderformData['records_dauer_min'] = $renderformData['records_dauer'][0];
				$renderformData['records_dauer_max'] = $renderformData['records_dauer'][count((array) $renderformData['records_dauer'])-1];
			}
			
			// Anfrage anhand der Liste der $records['id']s die alle Durchführungs-Tags findet um enthaltene Tageszeit, Förderungen, Zielgruppe, Zertifikate, Unterrichtsart zu finden.
			$renderformData['records_taglist'] = array();
    		$this->db->query("SELECT DISTINCT t.tag_name as tagname FROM x_kurse_tags k LEFT JOIN x_tags t ON k.tag_id=t.tag_id WHERE k.kurs_id IN($kursids)");
    		while( $this->db->next_record() )
    		{
    		    $renderformData['records_taglist'][] = $this->db->fcs8('tagname');
			}
			$this->db->free();
        }
		
		// TODO db: analoger zu filter-klasse aufbauen und nicht manuell zb. hier den q-tag ausgeben:
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$this->searcher = $searcher;
		$this->tokens = $searcher->tokenize($q);
		
		$renderformData['fv_bei'] = '';
		$renderformData['fv_km'] = '';
		$renderformData['fv_volltext'] = '';
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
		$renderformData['fv_zertifikat'] = '';
		$renderformData['fv_sonstigesmerkmal'] = '';
		$renderformData['fv_abschluesse'] = '';
		$renderformData['fv_abschlussart'] = '';
		$renderformData['fv_metaabschlussart'] = '';
		$renderformData['fv_unterrichtsart'] = '';
		$renderformData['fv_tageszeit'] = '';
		$renderformData['fv_stadtteil'] = '';
		$renderformData['fv_bezirk'] = '';
		$renderformData['fv_volltext'] = '';
		
		// if(!is_array($this->framework->tokensQF))
		//	return false;
		
		foreach($this->framework->tokensQF as $token) {
			switch( $token['field'] ) {
				case 'bei':	
				    $renderformData['fv_bei'] = $filterlabel = str_replace('/', ',', $token['value']);
					break;
					
				case 'km':	
					$renderformData['fv_km'] =  intval($token['value']);
					if( $renderformData['fv_km'] <= 0 ) $renderformData['fv_km'] = '';
					if( !$renderformData['km_arr'][$renderformData['fv_km']] ) $renderformData['km_arr'][$renderformData['fv_km']] = $renderformData['fv_km'] . " km";
					break;
					
				case 'volltext':
				    $renderformData['fv_volltext'] = $token['value'];
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
					    $renderformData['fv_preis_bis'] = str_replace($this->max_preis, '', $renderformData['fv_preis_bis']);
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
					
				case 'zertifikat':
				    $renderformData['fv_zertifikat'] = $token['value'];
				    break;
				    
				case 'sonstigesmerkmal':
				    $renderformData['fv_sonstigesmerkmal'] = $token['value'];
				    break;
				    
				case 'abschluesse':
				    $renderformData['fv_abschluesse'] = $token['value'];
				    break;
				    
				case 'abschlussart':
				    $renderformData['fv_abschlussart'] = $token['value'];
				    break;
				    
				case 'metaabschlussart':
				    $renderformData['fv_metaabschlussart'] = $token['value'];
				    break;
					
				case 'unterrichtsart':
					$renderformData['fv_unterrichtsart'] = $token['value'];
					break;
					
				case 'tageszeit':
					$renderformData['fv_tageszeit'] = $token['value'];
					break;
				
				case 'stadtteil':
				    $renderformData['fv_stadtteil'] = $token['value'];
				    break;
				    
				case 'bezirk':
				    $renderformData['fv_bezirk'] = $token['value'];
				    break;
				    
				case 'anbieter':
				    $renderformData['fv_anbieter'] = $token['value'];
				    break;
			}
		}
		return $renderformData;
	}
	
	function renderForm($q = null, $records = null, $hlevel=1, $number_of_results_string='')
	{
	    
	    echo '<div id="wisyr_filterform" class="wisyr_filterform">';
	    echo '<div class="wisyr_filterform_header"><h' . ($hlevel + 1) . ' class="wisyr_filterform_header_titel">Suchauftrag anpassen</h' . ($hlevel + 1) . '><h' . ($hlevel + 2) . ' class="wisyr_filterform_header_text">Nutzen Sie Filter, um Ihre Suche weiter einzugrenzen:</h' . ($hlevel + 2) . '></div>';
	    echo '<form action="search" method="get" name="filterform" role="search" aria-label="Suchauftrag anpassen">';
	    echo '<input type="hidden" name="filter_filtered" value="1" />';
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
	    
	    $renderformData = $this->prepareFormData($q, $records);
	    
	    $filtermenu = new WISY_FILTERMENU_CLASS($this->framework, $renderformData);
	    echo $filtermenu->getHtml();
	    
	    // output "number of results" string if set and order selector
	    $renderformData['order'] = $this->framework->order;
	    
	    $orders = array(
	        'b'  => 'Datum: aufsteigend',
	        'bd'  => 'Datum: absteigend',
	        'd'  => 'Dauer: aufsteigend',
	        'dd'  => 'Dauer: absteigend',
	        'p'  => 'Preis: aufsteigend',
	        'pd' => 'Preis: absteigend',
	        'a'  => 'Anbieter: aufsteigend',
	        // 'ad' => 'Anbieter: absteigend',
	        't'  => 'Angebot: Titel',
	        'o'  => 'Ort: aufsteigend',
	        't'  => 'Angebot: Titel',
	        'rand'  => 'Zuf&auml;llig sortiert'
	    );
	    
	    $portal_order_options = trim(trim($this->framework->iniRead('kurse.sortierung.optionen', false), ","));
	    if($portal_order_options && strlen($portal_order_options))
	        $portal_order_options = array_map("trim", explode(",", $portal_order_options));
	        
	    if(is_array($portal_order_options) && count($portal_order_options)) {
	        $orders_custom = array();
	        foreach($portal_order_options AS $poo) {
	          $orders_custom[$poo] = $orders[$poo];
	        }
	        $orders = $orders_custom;
	    }
	    
	    $portal_order = $this->framework->specialSortOrder ? $this->framework->specialSortOrder : $this->framework->iniRead('kurse.sortierung', false);
	    
	    if($portal_order && $renderformData['order'] == '') $renderformData['order'] = $portal_order;
	    if($renderformData['order'] == '') $renderformData['order'] = 'b';
	    
	    // display sort order filter, except if fulltext search (b/c then sorted by match category automatically + RAND as secondary criteria)
	    if( stripos($this->framework->QS, 'volltext:') === FALSE && stripos($this->framework->QF, 'volltext:') === FALSE ) {
    	    echo '<fieldset class="wisyr_filtergroup wisyr_filter_select filter_sortierung ui-front">';
    	    echo '	<legend data-filtervalue="' . str_replace('- '.$this->max_preis, '', $orders[$renderformData['order']]) . '" >Sortierung</legend>';
    	    echo '	<select name="filter_order" class="wisyr_selectmenu">';
    	    foreach($orders as $key => $value)
    	    {
    	        echo '		<option value="' . $key . '"';
    	        if($key == $renderformData['order']) echo ' selected="selected"';
    	        echo ' class="order_'.$key.'"';
    	        echo '>' . $value . '</option>';
    	    }
    	    echo '	</select>';
    	    echo '</fieldset>';
	    }
	    
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
    var $max_preis = '999999';
    
    function __construct($framework, $data, $renderformData, $zindex)
    {
        $this->framework = $framework;
        $this->data = $data;
        $this->renderformData = $renderformData;
        $this->children = array();
        $this->zindex = $zindex;
    }
    
    function getHtml($data=false, $subsection=false)
    {
        if(!$data) {
            $data = $this->data;
        }
        
        if($subsection) {
            $filterclasses = 'wisyr_filtergroup';
        } else {
            $filterclasses = $this->getFilterclasses($data, true);
        }
        $legendvalue = isset($data['legendkey']) ? $this->getLegendvalue($data['legendkey']) : '';
        $title = isset($data['title']) ? (PHP7 ? utf8_decode($data['title']) : $data['title'])  : '';
        
        $ret = '<fieldset class="' . $filterclasses . '" style="z-index:' . $this->zindex . '" tabindex="0">';
        
        if(trim($title) !== '') {
            $ret .= '<legend data-filtervalue="' . str_replace('-'.$this->max_preis, '', $legendvalue) . '" >' . $title . '</legend>'; // hoechste Ebene
        }
        $ret .= '<div class="filter_inner clearfix">';
        
        if(isset($data['sections']) && count((array) $data['sections'])) {
            $ret .= $this->getSections($data['sections']);
        } else {
            $this->getFormfields($data, false);
            $ret .= $this->getFormfields($data, true);
        }
        if(!$data['metagroup'] || $subsection || (isset($data['no_autosubmit']) && $data['no_autosubmit'] == 1) ) {
            $ret .= '<input class="filter_submit" type="submit" value="&Uuml;bernehmen" />';
        }
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
                $ret .= '<a class="wisyr_filterform_reset" href="' . $this->framework->filterer->getSearchUrlWithoutFilters() . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '') . '">' . $data['resetfilter'] . '</a>';
                continue;
            }
            
            $title = PHP7 ? $data['title'] : $data['title']; // utf8_decode(
            $ret .= '<fieldset class="' . $filterclasses . '">';
            $ret .= '<legend data-filtervalue="' . str_replace('-'.$this->max_preis, '', $legendvalue) . '" >' . $title . '</legend>';
            
            $this->getFormfields($data, false);
            $ret .= $this->getFormfields($data, true);
            
            $ret .= '</fieldset>';
            if( (isset($data['no_autosubmit_mobile']) && $data['no_autosubmit_mobile'] == 1) || (isset($data['no_autosubmit']) && $data['no_autosubmit'] == 1) ) {
                $ret .= '<input class="filter_submit filter_filtersection_submit" type="submit" value="&Uuml;bernehmen" />';
            }
        }
        
        return $ret;
    }
    
    function getFilterclasses($data, $is_filtergroup=false) {
        
        $fsc = array();
        
        if(isset($data['metagroup']) && $data['metagroup'] == 1) {
            $fsc[] = 'wisyr_filter_metagroup'; // -> filtermenu.1.metagroup = 1
        } else if($is_filtergroup) {
            $fsc[] = 'wisyr_filtergroup';
        }
        
        if(isset($data['class'])) $fsc[] = $data['class']; // -> filtermenu.1.class = filter_zweispaltig
        if(isset($data['autosubmit']) && $data['autosubmit'] == 1) $fsc[] = 'wisyr_filter_autosubmit'; // -> filtermenu.1.autosubmit = 1
        if(isset($data['autoclear']) && $data['autoclear'] == 1) $fsc[] = 'wisyr_filter_autoclear'; // -> filtermenu.1.autoclear = 1
        if(isset($data['no_autosubmit_mobile']) && $data['no_autosubmit_mobile'] == 1) $fsc[] = 'no_autosubmit_mobile';
        
        if(isset($data['function']) && strlen($data['function'])) {
            $fsc[] = 'filter_' . $data['function'];
        }
        
        if(isset($data['autofill']) && strlen($data['autofill'])) {
            $fsc[] = 'wisyr_filter_autofill'; // -> filtermenu.1.input.autofill = datum_von
        }
        
        return implode(' ', $fsc);
    }
    
    function getFormfields($data, $returnFields = true) {	
        
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
                $autocomplete = 1;
                $clearbutton = false;
                
                if(isset($input['type'])) $type = $input['type'];
                if(isset($input['value'])) $fieldvaluename = $input['value'];
                $fieldname = $fieldvaluename;
                if(isset($input['name']) && strlen($input['name'])) $fieldname = $input['name'];
                if(isset($this->renderformData['fv_' . $fieldvaluename])) $fieldvalue = $this->renderformData['fv_' . $fieldvaluename];
                if(isset($input['label']) && strlen($input['label'])) $fieldlabel = $input['label'];
                if(isset($input['class'])) $fieldclass = $input['class'];
                if(isset($input['placeholder'])) $fieldplaceholder = $input['placeholder'];
                if(isset($input['autofilltarget'])) $autofilltarget = $input['autofilltarget'];
                if(isset($input['autocomplete'])) $autocomplete = $input['autocomplete'];
                if(isset($input['suffix'])) $fieldsuffix = $input['suffix'];
                if(isset($input['clearbutton']) && $input['clearbutton'] == 1) $clearbutton = true;
        
                if(isset($input['options']) && count((array) $input['options'])) { // array must be cast(!), bc. values with '.' reduces no. of elements
                    $filtervalues = $input['options'];
                } else if(isset($input['stichwort'])) {
                    $filtervalues = $this->getStichwort($input['stichwort'], $fieldlabel);
                } else {
                    $filtervalues = $this->getFiltervalues($input);
                }
        
                switch($type) {
            
                    case 'textfield':
                        
                        if($clearbutton) $ret .= '<div class="filter_clearbutton_wrapper">';
                        $ret .= '<input type="text" name="filter_' . $fieldname . '[]" id="filter_' . $fieldname . '" class="' . $fieldclass . '" value="' . $fieldvalue . '" placeholder="' . $fieldplaceholder . '" data-autocomplete="'.$autocomplete.'" aria-label="'.$fieldname.'"/>';
                        
                        if( isset($input['deletebutton']) ) // only mobile
                            $ret .= '<div class="clear_btn hidden" aria-label="Eingabe l&ouml;schen" onclick=" jQuery(\'#filter_'.$fieldname.'\').val(\'\'); "></div>';
                            
                        if( isset($input['explicitsubmitbutton']) ) // only mobile
                            $ret .= '<input class="filter_submit hidden" type="submit" value="&Uuml;bernehmen">';
                                
                        if($clearbutton) $ret .= '</div>';
                        $ret .= $fieldsuffix . '<br /><br />';
                                
                        break;
            
                    case 'radiolinklist':
                        
                        foreach($filtervalues as $value => $label) {
                            
                            // Funktioniert auch fuer Anbieter?!
                            $processed_value = $this->getProcessedValue($function, $value, $label);
                            $disabled = $this->getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            
                            $ret .= '<div class="wisyr_radiowrapper">';
                            $ret .= '	<input type="radio" name="filter_' . $fieldname . '[]" id="filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true) . '" value="' . ($label == 'Alle' ? '' : str_replace('"', '%20', str_replace(',', ' ', $label))) . '"';
                            
                            if(str_replace(',', ' ', $label) == $fieldvalue) {
                                $ret .= ' checked="checked"';
                                $currentFound = true;
                            }
                            
                            if($disabled) $ret .= ' disabled="disabled"';
                            
                            $ret .= ' />';
                            $ret .= '	<label for="filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true) . '">' . $label . '</label>';
                            
                            $ret .= '</div>';
                        }
                        
                        if(!$currentFound && trim($fieldvalue) != '') {
                            $ret .= '<input type="hidden" name="filter_' . $fieldname . '[]" value="' . $fieldvalue . '" />';
                        }
                        
                        break;
                        
                    case 'radiolist':
                    case 'radiobuttons':
                        
                        foreach($filtervalues as $value => $label) {
                            
                            $processed_value = $this->getProcessedValue($function, $value, $label);
                            $checked = $this->getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            $disabled = $this->getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            
                            if($type == 'radiobuttons') {
                                $ret .= '<div class="wisyr_radioboxwrapper wisyr_radiobutton">';
                            } else {
                                $ret .= '<div class="wisyr_radiowrapper">';
                            }
                            
                            $ret .= '	<input type="radio" name="filter_' . $fieldname . '[]" id="filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true) . '" value="' . $processed_value . '"';
                            if(strlen($autofilltarget)) {
                                $ret .= ' data-autofilltarget="#filter_' . $autofilltarget . '" data-autofillvalue="' . $processed_value . '"';
                            }
                            
                            if($checked) $ret .= ' checked="checked"';
                            if($disabled) $ret .= ' disabled="disabled"';
                            
                            $ret .= ' />';
                            $ret .= '	<label for="filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true) . '">' . $label . '</label>';
                            $ret .= '</div>';
                        }
                        
                        break;
                        
                    case 'selectmenu':
                        
                        
                        $ret .= '<select name="filter_' . $fieldname . '[]" class="wisyr_selectmenu">';
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
                        
                    case 'checkbuttons':
                        
                        // combine all values already checked in String $this->checked_values_combStr, comma separated => checkbox!
                        foreach((array) $filtervalues as $value => $label) {
                            if($value === '') continue;
                            
                            $processed_value = $this->getProcessedValue($function, $value, $label);
                            $checked = $this->getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            
                            if($checked && !$returnFields)
                                $this->checked_values_combStr[$fieldname] .= $processed_value.",";
                        }
                        
                        foreach((array) $filtervalues as $value => $label) {  // array must be cast(!), bc. values with '.' reduces no. of elements
                            // Don't show empty value ("Alle") button:
                            if($value === '') continue;
                            
                            $processed_value = $this->getProcessedValue($function, $value, $label);
                            $checked = $this->getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            
                            
                            
                            // echo "<script>console.log('Function: ".$function.", Value: ".$value.", Label: ".$label.", Processed Value: ".$processed_value.", Fieldvalue: ".$this->framework->mysql_escape_mimic(print_r($this->renderformData, true)).", Fieldname: ".$fieldname."');</script>";
                            // echo "<script>console.log('checked: ".$checked."');</script>";
                            
                            $disabled = $this->getDisabledValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname);
                            
                            $ret .= '<div class="wisyr_checkboxwrapper wisyr_checkbutton">';
                            
                            // save all checked values other than the current boxes input to be added to current box input value
                            $other_values = trim(str_replace($processed_value.',', '', $this->checked_values_combStr[$fieldname]), ',');
                            
                            
                            $for = 'filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true);
                            $ret .= '	<input type="checkbox" name="filter_' . $fieldname . '[]" id="'.$for.'" value="' . $processed_value . '" ';
                            
                            if(strlen($autofilltarget)) {
                                $ret .= ' data-autofilltarget="#filter_' . $autofilltarget . '" data-autofillvalue="' . $processed_value . '"';
                            }
                            
                            if($checked) $ret .= ' checked="checked"';
                            if($disabled) $ret .= ' disabled="disabled"';
                            
                            $ret .= ' />';
                            
                            $ret .= '	<label for="'.$for.'" onclick="$(\'#'.$for.'\').attr(\'value\', $(\'#'.$for.'\').attr(\'value\')+\''. (strlen($other_values) ? ','.$other_values : '') .'\' );">' . $label . '</label>';
                            // $ret .= '	<label for="filter_' . $fieldname . '_' . $this->framework->cleanClassname($value, true) . '">' . $label . '</label>';
                            $ret .= '</div>';
                        }
                        
                        break;
                }
            }
        } else if(isset($data['sections']) && count((array) $data['sections'])) {
            $ret .= $this->getHtml($data, true);
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
    		    return $this->getSpezielleStichw(2, $data['datawhitelist'], $data['orderbywhitelist']);
            break;
            
            case 'zielgruppen':
                return $this->getSpezielleStichw(8, $data['datawhitelist'], $data['orderbywhitelist']);
            break;
            
            case 'qualitaetszertifikate':
                return $this->getSpezielleStichw(4, $data['datawhitelist'], $data['orderbywhitelist']);
            break;
            
            case 'zertifikate':
                return $this->getSpezielleStichw(65536, $data['datawhitelist'], $data['orderbywhitelist']);
                break;
                
            case 'sonstigemerkmale':
                return $this->getSpezielleStichw(1024, $data['datawhitelist'], $data['orderbywhitelist']);
                break;
                
            case 'abschluesse':
                return $this->getSpezielleStichw(1, $data['datawhitelist'], $data['orderbywhitelist']);
                break;
                
            case 'abschlussarten':
                return $this->getSpezielleStichw(16, $data['datawhitelist'], $data['orderbywhitelist']);
                break;
            
            case 'unterrichtsarten':
                return $this->getSpezielleStichw(32768, $data['datawhitelist'], $data['orderbywhitelist']);
                break;
                
            case 'sonstigesmerkmal':
                return $this->getSpezielleStichw(1024, $data['datawhitelist'], $data['orderbywhitelist']);
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
			    case 'volltext':
			        break;
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
			case 'checkboxes':
				if(is_array($this->renderformData['records_taglist']) && !in_array($value, $this->renderformData['records_taglist'])) $disabled = true;
			break;
			
			case 'taglist_unfiltered':
			    $disabled = false;
			break;
		}
		
		return $disabled;
	}
	
	function getCheckedValue($function, $value, $label, $processed_value, $fieldvalue, $fieldname) {
	    
	    $checked = false;
	    
	    switch($function) {
	        case 'checkboxes':
	            if(!is_array($fieldvalue)) $fieldvalue = explode($this->framework->filterValueSeparator, $fieldvalue);
	            $checked = false;
	            foreach($fieldvalue as $fieldval) {
	                if($processed_value == $fieldval) {
	                    $checked = true;
	                    break;
	                }
	            }
	            break;
	            
	        default:
	            if($processed_value === $fieldvalue) {
	                $checked = true;
	            } else {
	                $checked = false;
	            }
	            break;
	    }
	    
	    return $checked;
	}
    
	private function getSpezielleStichw($flag, $whitelist='', $orderbywhitelist=false)
	{
	    // nur die Stichwoerter zurueckgeben, die im aktuellem Portal auch verwendet werden!
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
	        if($orderbywhitelist) {
	            $db->query("SELECT stichwort FROM stichwoerter WHERE id IN ($ids_filtered) ORDER BY FIELD(id, $ids_filtered);");
	        } else {
	            $db->query("SELECT stichwort FROM stichwoerter WHERE id IN ($ids_filtered) ORDER BY stichwort_sorted;");
	        }
	        while( $db->next_record() )
	        {
	            $stichw = htmlspecialchars($db->fcs8('stichwort'));
	            $stichw = trim(strtr($stichw, array(': '=>' ', ':'=>' ', ', '=>' ', ','=>' ')));
	            
	            $ret[ $stichw ] = $stichw;
	        }
	        $db->free();
	    }
	    return $ret;
	}
	
	private function getStichwort($stichwort, $label='') {
	    $ret = array();
	    $stichwort = intval($stichwort);
	    if($stichwort) {
	        $db = new DB_Admin;
	        $db->query("SELECT stichwort FROM stichwoerter WHERE id='$stichwort' LIMIT 1");
	        while( $db->next_record() )
	        {
	            $stichw = htmlspecialchars($db->fcs8('stichwort'));
	            $stichw = trim(strtr($stichw, array(': '=>' ', ':'=>' ', ', '=>' ', ','=>' ')));
	            
	            $ret[ $stichw ] = strlen($label) ? $label : $stichw;
	        }
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
	    foreach($wisyPortalEinstellungen as $key => $value)
	    {
	        
	        if( substr($key, 0, $allPrefixLen)==$allPrefix )
	        {
	            
	            // Possible cases:
	            // √ A. is_numeric -> title (filtermenu.2 = Preis)
	            // √ B. is_numeric and "parent" is "options" -> option (filtermenu.2.1.input.1.options.0 = Kostenlos)
	            // √ C. Attribute -> (filtermenu.1.class = filter_zweispaltig)
	            // √ D. "options" -> array() (filtermenu.2.1.input.1.options)
	            // √ E. is_numeric and "parent" is_numeric -> sections (filtermenu.2.1)
	            
	            $levels = substr($key, $allPrefixLen);
	            $newStructure = false;
	            $elements = explode(".", $levels);
	            if(count((array) $elements) > 1) {
	                $last = &$newStructure[ $elements[0] ];
	                
	                // merge values that where separated falsely for containing '.'
	                if( ($optionskey = array_search ("options", $elements)) !== FALSE) {
	                    for($k = 0; $k < count((array) $elements); $k++) {
	                        if($k > ($optionskey+1)) {
	                            $elements[($optionskey+1)] .= '.'.$elements[$k]; // merge elements (values) after option, bc. = one value separated by original '.'
	                            $elements[$k] = ""; // don't unset while in loop
	                        }
	                    }
	                }
	                
	                // removing empty elements causes counting mismatch at other places
	                $elements = array_filter($elements); // remove empty elements
	                
	                $wasNumeric = false;
	                $wasOption = false;
	                foreach($elements as $k => $el) {
	                    
	                    // Add "sections" whenever two numbers follow one another -> e.g. filtermenu.2.1
	                    $isNumeric = is_numeric($el);
	                    if($isNumeric && $wasNumeric) $last = &$last['sections'];
	                    $wasNumeric = $isNumeric;
	                    
	                    // Check for "options" elements:
	                    $wasOption = $isOption;
	                    $isOption = ($el == 'options');
	                    
	                    if($k == 0) continue;
	                    $last = &$last[$el];
	                }
	                if($isNumeric && !$wasOption) {
	                    // Add title if parent was not "options"
	                    $last['title'] = cs8($value);
	                } else {
	                    $last = cs8($value);
	                }
	            } else {
	                $newStructure[$levels]['title'] = cs8($value);
	            }
	            if($newStructure) {
	                $filterStructure = array_replace_recursive($filterStructure, $newStructure);
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
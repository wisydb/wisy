<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_DURCHF_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function shy($text)
	{
		global $g_tr;
		if( !is_array($g_tr) )
		{
			$tr = array(
				"Ausstellungs-f",
				"Arbeits-such",
				"Ganz-t",
				"Nach-mittags",
			);
			$g_tr = array();
			for( $i = 0; $i < sizeof($tr); $i++ )
				$g_tr[ str_replace('-', '', $tr[$i]) ] = str_replace('-', '&shy;', $tr[$i]);
		}
				
		return strtr($text, $g_tr);
	}
	
	/*
	function formatTagescode($tagescode, $details, $addText = '') // $tagescode: <id> or 'bu'
	{
		$ret = '';
		$icons = $this->framework->iniRead('img.icons', 'skww');
		if( !@file_exists("$icons/tc1.gif") )
		{
			// use the new method (text only)
			$info = array(
				1	=>	array('Ganzt.', 'Ganzt&auml;gig'),
				2	=>	array('Vorm.',  'Vormittags'),
				3	=>	array('Nachm.', 'Nachmittags'),
				4	=>	array('Abends', 'Abends'),
				5	=>	array('WE',     'Wochenende'),
				6	=>	array('FU',     'Fernunterricht'),
				'bu'=>	array('BU',     'Bildungsurlaub'),
			);
			if( is_array($info[$tagescode]) ) {
				if( $details ) {
					$ret = '<span class="tagescode'.$tagescode.'">'.$this->shy($info[$tagescode][1]).'</span>';
					if( $addText ) $ret .= ', ' . $addText;
				}	
				else {
					$ret = '<span class="tagescode'.$tagescode.'" title="'.$info[$tagescode][1].'">'.$info[$tagescode][0].'</span>';
				}
			}
			return $ret;
		}
		else
		{
			// use the old method (icon)
			if( $tagescode )
			{
				global $codes_tagescode_array;
				if( !is_array($codes_tagescode_array) ) 
				{	
					require_once('admin/config/codes.inc.php');
					global $codes_tagescode;				
					$codes_tagescode_array = array();
					$temp = explode('###', $codes_tagescode);
					for( $i = 0; $i < sizeof($temp); $i+=2 ) {
						$codes_tagescode_array[$temp[$i]] = $temp[$i+1];
					}
					$codes_tagescode_array['bu'] = 'Bildungsurlaub';
				}
				
				$title = $codes_tagescode_array[$tagescode];
				
				if( $details ) {
					$ret = "<img src=\"{$icons}/tc{$tagescode}.gif\" width=\"15\" height=\"12\" border=\"0\" alt=\"\" title=\"\" /><small> $addText $title</small>";
				}
				else {
					$ret = "<img src=\"{$icons}/tc{$tagescode}.gif\" width=\"15\" height=\"12\" border=\"0\" alt=\"$title\" title=\"$title\" />";
				}
			}
			else
			{
				$ret = "<img src=\"core10/1x1.gif\" width=\"15\" height=\"12\" border=\"0\" alt=\"\" title=\"\" />";
			}
			return $this->shy($ret);
		}
	}
	*/	

	private function formatArtSpalte($stichwoerter_arr, $details)
	{
		// Array Stichwörter/Tags => Bilder/Text erzeugen
		// (wir verwenden hier die Informationen aus der stichworttabelle anstelle von x_tags, da diese einfacher zur Verfügung stehen und
		// nicht aktualisiert werden müssen, d.h. Änderungen in der Onlinepflege sind sofort sichtbar.
		// Nachteil ist, dass einige Stichwörter erst rekonstruiert werden müssen (aus bu_nummer, s. wisy-sync-renderer-class.inc.php))
		if( !is_array($this->imgTagArr) ) 
		{
			// init Array with defaults
			$this->imgTagArr = array(
				'tc1'	=>	array('&#9673;', 	'Ganzt&auml;gig'),
				'tc2'	=>	array('&#9680;',  	'Vormittags'),
				'tc3'	=>	array('&#9681;', 	'Nachmittags'),
				'tc4'	=>	array('&#9682;', 	'Abends'),
				'tc5'	=>	array('<i style="font-family: serif;">WE</i>',		'Wochenende'),
				1		=>	array('<b>BU</b>',	'Bildungsurlaub'),
				7721	=>	array('<big>&#9993;</big>',	'Fernunterricht'),
			);

			// deprecated (2014-11-02 17:46)
				// overwrite with the old setting, if any
				$icons = $this->framework->iniRead('img.icons', 'skww');
				if( @file_exists("{$icons}/tc1.gif") )
				{
					$this->imgTagArr['tc1']	= array("{$icons}/tc1.gif", 'Ganzt&auml;gig');
					$this->imgTagArr['tc2']	= array("{$icons}/tc2.gif", 'Vormittags');
					$this->imgTagArr['tc3']	= array("{$icons}/tc3.gif", 'Nachmittags');
					$this->imgTagArr['tc4']	= array("{$icons}/tc4.gif", 'Abends');
					$this->imgTagArr['tc5']	= array("{$icons}/tc5.gif", 'Wochenende');
					$this->imgTagArr[1]		= array("{$icons}/tcbu.gif",'Bildungsurlaub');
					$this->imgTagArr[7721]	= array("{$icons}/tc6.gif",	'Fernunterricht');
				}
			// /deprecated (2014-11-02 17:46)

			// overwrite defaults with portal settings from img.tag
			foreach( $GLOBALS['wisyPortalEinstellungen'] as $key => $value ) {
				
				if( substr($key, 0, 8) == 'img.tag.' ) 
				{
					$tag = str_replace('img.tag.', '', $key);
					if( $value == '' ) {
						unset($this->imgTagArr[ $tag ]);
					}
					else {
						$this->imgTagArr[ $tag ] = explode('|', $value);
					}
				}
			}

		}
		
		// make stichwoerter easier searchable
		$stichwoerter_hash = array();
		foreach( $stichwoerter_arr as $dummy=>$attr ) {
			$stichwoerter_hash[ $attr['id'] ] = 1;
		}
		
		// render
		$html = '';
		
		foreach( $this->imgTagArr as $id=>$img_arr )
		{
			if( $stichwoerter_hash[ $id ] )
			{
				if( $html ) {
					$html .= $details? '<br />' : ' ';
				}
				
				$img_icon = $img_arr[0];
				$img_text = $img_arr[1];
				
				$ext = substr($img_icon, -4);
				if( $ext == '.gif' || $ext == '.png' || $ext == '.jpg' || $ext == '.svg' ) {
					$alt = $details? '' : $img_text;
					$html .= '<img src="'.$img_icon.'" alt="'.$alt.'" title="'.$img_text.'" />';
				}
				else {
					$html .= '<span title="'.$img_text.'">'.$img_icon.'</span>';
				}
				
				if( $details ) {
					$html .= '<small> ' . $img_text . '</small>';
				}
			}
		}
		
		return $html;
	}

	function formatBeginnoptionen($opt)
	{
		global $codes_beginnoptionen_array;
		
		if( !is_array($codes_beginnoptionen_array) ) 
		{	
			require_once('admin/config/codes.inc.php');
			global $codes_beginnoptionen;
			$codes_beginnoptionen_array = array();
			$temp = explode('###', $codes_beginnoptionen);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_beginnoptionen_array[$temp[$i]] = $temp[$i+1];
			}
		}
	
		if( $opt <= 0 ) {
			return '';
		}
		else if( $codes_beginnoptionen_array[$opt] ) {
			return $codes_beginnoptionen_array[$opt];
		}
		else {
			return '';
		}
	}

	function formatKurstage($kurstage)
	{
		// convert the "kurstage" bitfield to an string
		global $codes_kurstage_array;
		
		if( !is_array($codes_kurstage_array) ) 
		{	
			global $codes_kurstage;
			if( !is_string($codes_kurstage) ) {
				require_once('admin/config/codes.inc.php');
			}
			
			$codes_kurstage_array = array();
			$temp = explode('###', $codes_kurstage);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_kurstage_array[intval($temp[$i])] = trim($temp[$i+1]);
			}
		}
	
		$c = 0;
		reset($codes_kurstage_array);
		while( list($value, $descr) = each($codes_kurstage_array) ) {
			if( $kurstage & $value ) {
				$c++;
			}
		}
	
		$ret = '';
		reset($codes_kurstage_array);
		while( list($value, $descr) = each($codes_kurstage_array) ) {
			if( $kurstage & $value ) {
				$ret .= $ret? ($c==1? ' und ' : ', ') : '';
				$ret .= $descr;
				$c--;
			}
		}
		
		return $ret;
	}

	function formatDauer($dauer, $stunden, $mask2 = '%1 (%2)') // return as HTML
	{
		// Dauer formatieren
		global $codes_dauer_array;
		if( !is_array($codes_dauer_array) ) 
		{	
			require_once('admin/config/codes.inc.php');
			global $codes_dauer;
			$codes_dauer_array = array();
			$temp = explode('###', $codes_dauer);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_dauer_array[$temp[$i]] = $temp[$i+1];
			}
		}
	
		if( $dauer <= 0 ) {
			$dauer = '';
		}
		else if( $codes_dauer_array[$dauer] ) {
			$dauer = $codes_dauer_array[$dauer];
		}
		else {
			$dauer = "$dauer Tage";
		}
		
		// stunden
		if( $stunden > 0 ) {
			$stunden = "$stunden&nbsp;Std.";
		}	
		else {
			$stunden = '';
		}
		
		// done
		if( $dauer != '' && $stunden != '' ) {
			$ret = str_replace('%1', $dauer, $mask2);
			$ret = str_replace('%2', $stunden, $ret);
		}
		else if( $dauer != '' ) {
			$ret = $dauer;
		}
		else if( $stunden != '' ) {
			$ret = $stunden;
		}
		else {
			$ret = 'k. A.';
		}
		return $ret;
	}

	function formatPreis($preis, $sonderpreis, $sonderpreistage, $beginn, $preishinweise_str, $html = 1, $addParam = 0)
	{
		if( !is_array($addParam) ) $addParam = array();

		// Preis formatieren
		if( $preis == -1 ) 
		{
			$ret = 'k. A.';
		}
		else if( $preis == 0 )
		{
			$ret = 'kostenlos';
		}
		else 
		{
			if( $html ) {
				$ret = "$preis&nbsp;&euro;";
			}
			else {
				$ret = "$preis EUR";
			}
			
			if( $preis>0
			 && $sonderpreis>0 
			 && $sonderpreis<$preis )
			{
				$beginn = explode(' ', str_replace('-', ' ', $beginn));
				$beginn = mktime(0, 0, 0, $beginn[1], $beginn[2], $beginn[0]) - $sonderpreistage*86400;
				if( time() >= $beginn ) {
					if( $html ) {
						$ret = "<strike>$ret</strike><br /><span class=\"red\">" . $this->formatPreis($sonderpreis, -1, 0, 0, '', $html, 0) . '</span>';
					}
					else {
						$ret = $this->formatPreis($sonderpreis, -1, 0, 0, '', $html, 0) . " (bisheriger Preis: $ret)";
					}
				}
			}
		}
	
		if( $addParam['showDetails'] )
		{
			$preishinweise_arr = array();
			if( $preishinweise_str ) $preishinweise_arr[] = $preishinweise_str;
			
			foreach( $addParam['stichwoerter'] as $stichwort ) {
				switch( $stichwort['id'] ) {
					case 3207:  $preishinweise_arr[] = 'kostenlos per Bildungsgutschein'; 		break;
					case 6013:  $preishinweise_arr[] = 'kostenlos durch Umschulung';			break;
					case 16311: $preishinweise_arr[] = 'kostenlos als Aktivierungsmaßnahme';	break;				
				}
			}
			
			if( sizeof($preishinweise_arr) )
			{	
				$preishinweise_out = implode(', ', $preishinweise_arr);
				if( $html ) {
					$ret .= '<br /><small>' . isohtmlentities($preishinweise_out) . '</small>';
				}
				else {
					$ret .= " ($preishinweise_out)";
				}
			}
		}
	
		return $ret;
	}
	
	
	
	private $plz_allow;
	private $plz_deny;
	private $plz_order;
	private function get_plz_array_($ini_key)
	{
		$ret = array();
			$temp = explode(',', $this->framework->iniRead($ini_key, ''));
			for( $i = 0; $i < sizeof($temp); $i++ ) {
				$plz = trim($temp[$i]);
				if( $plz != '' ) {
					$ret[ $plz ] = 1;
				}
			}
		return $ret;
	}
	private function is_plz_in_array_($plz, $arr)
	{
		for( $i = strlen($plz); $i >= 1; $i-- ) {
			if( $arr[ substr($plz, 0, $i) ] )
				return true;
		}
		
		return false;
	}
	private function is_valid_plz($plz)
	{
		// PLZ allow/deny liste erzeugen
		if( !is_array($this->plz_allow) )
		{
			$this->plz_allow = $this->get_plz_array_('durchf.plz.allow');
			$this->plz_deny  = $this->get_plz_array_('durchf.plz.deny');
			$this->plz_order = str_replace(' ', '', $this->framework->iniRead('durchf.plz.order', 'allow,deny')); // durchf.plz.order ist akt. (21:57 16.01.2013) nicht dokumentiert und inoffiziell!
		}
		
		// übergebene PLZ korrigieren
		$plz = trim($plz);
		if( $plz == '' ) {
			$plz = 'empty';
		}
		
		// prüfen, ob die PLZ explizit verboten oder erlaubt ist
		if( $this->plz_order == 'deny,allow' )
		{
			if( sizeof($this->plz_deny ) ) { if(  $this->is_plz_in_array_($plz, $this->plz_deny ) ) { return false; } }
			if( sizeof($this->plz_allow) ) { if( !$this->is_plz_in_array_($plz, $this->plz_allow) ) { return false; } }
		}
		else
		{
			// default behaviour
			if( sizeof($this->plz_allow) ) { if( !$this->is_plz_in_array_($plz, $this->plz_allow) ) { return false; } }
			if( sizeof($this->plz_deny ) ) { if(  $this->is_plz_in_array_($plz, $this->plz_deny ) ) { return false; } }
		}
		
		return true;
	}
	
	
	
	function getDurchfuehrungIds(&$db, $kursId, $sabg = 0 /*1=auch abgelaufene Durchfuehrungen anzeigen*/)
	{
		// "ORDER BY beginn='0000-00-00 00:00:00'" stellt Kurse ohne Datum ans Ende
		// (der erste Versuch, "STRCMP(beginn,'0000-00-00 00:00:00') DESC" klappte auf der Server (MySQL 4.1.10a) nicht)
		for( $test = ($sabg?1:0); $test <= 1; $test++ )
		{
			switch( $test )
			{
				case 0: $where = " AND (beginn>='".strftime("%Y-%m-%d 00:00:00")."' OR (beginn='0000-00-00 00:00:00' AND beginnoptionen>0))";	break;
				case 1: $where = ""; break;
			}
			
			$durchfuehrungenIds = array();
	
			$db->query("SELECT secondary_id, plz FROM kurse_durchfuehrung, durchfuehrung WHERE primary_id=$kursId AND id=secondary_id $where
						 ORDER BY beginn='0000-00-00 00:00:00', beginn, beginnoptionen, structure_pos");
			while( $db->next_record() )
			{
				if( $this->is_valid_plz($db->fs('plz')) ) {
					$durchfuehrungenIds[] = $db->fs('secondary_id');
				}
			}
			
			if( sizeof($durchfuehrungenIds) )
				break;
		}
		
		return $durchfuehrungenIds;
	}

	private function stichw_in_array(&$arr, $id)
	{
		foreach( $arr as $index=>$values ) {
			if( $values['id'] == $id ) {
				return true;
			}
		}
		return false;
	}

	function formatDurchfuehrung(&$db, $kursId, $durchfuehrungId, $details = 0, $anbieterId = 0, $showAllDurchf = 1, $addText='', $addParam = 0)
	{
		global $wisyPortalSpalten;
		
		if( !is_array($addParam) ) $addParam = array();
		
		// load data
		$db->query("SELECT nr, dauer, bemerkungen, preis, teilnehmer, kurstage, sonderpreis, sonderpreistage, plz, strasse, 
						   land, stadtteil, preishinweise, beginn, beginnoptionen, ende, ort, tagescode, stunden, zeit_von, zeit_bis, bg_nummer, bg_nummer_count
					  FROM durchfuehrung 
					 WHERE id=$durchfuehrungId");
	    if( $db->next_record() )
	    {
	    	$record  = $db->Record;
	    }
	    else
	    {
	    	$record = array('preis' => -1); // alle andere felder sind mit "leer" gut bedient
	    }
	    
		// stichwoerter um im sync-process automatisch vergebene Stichwörter ergänzen
		//
		// 2014-11-01 11:26 Anmerkung [1] (vgl. Anmerkungen [1] in wisy-sync-renderer.inc.php):
		// 		da dies ungefähr dasselbe ist, wie in wisy-sync-renderer-class.inc.php könnte man dies evtl. in eine Klasse wie "auto-stichwort" auslagern,
		// 		v.a. da im Grunde an dieser Stelle auch die AutoStichwoerter vergeben werden müssten - und wenn man
		//		ein System etabliert, Stichwörter aus Volltext zu erzeugen, noch mehr.
		//
		//		Es ist also eine Grundsätzliche Frage, wie mit automatisch vergebenen Stichwörtern verfahren werden soll,
		//		vll. ist es doch am Einfachsten, diese beim Abspeichern direkt im Redaktionssystem zu hinterlegen,
		//		auch wenn mal etwas nach core20 folgt ...
		//		dies müsste allerdings mit Jürgen und Monika besprochen werden (bp)
		if( $addParam['record']['bu_nummer'] )	{ if(!$this->stichw_in_array($addParam['stichwoerter'], 1   )) { $addParam['stichwoerter'][] = array('id'=>1   ); } }
		if( $addParam['record']['fu_knr'] )		{ if(!$this->stichw_in_array($addParam['stichwoerter'], 7721)) { $addParam['stichwoerter'][] = array('id'=>7721); } }
		if( $addParam['record']['azwv_knr'] ) 	{ if(!$this->stichw_in_array($addParam['stichwoerter'], 3207)) { $addParam['stichwoerter'][] = array('id'=>3207); } }
	    
		// termin
		$terminAttr = $details? '' : ' nowrap="nowrap"';
		$beginnsql		= $record['beginn'];
		$beginn			= $this->framework->formatDatum($beginnsql);
		$beginnoptionen = $this->formatBeginnoptionen($record['beginnoptionen']);
		$ende			= $details? $this->framework->formatDatum($record['ende']) : '';
		$zeit_von		= $details? $record['zeit_von'] : ''; if( $zeit_von=='00:00' ) $zeit_von = '';
		$zeit_bis		= $details? $record['zeit_bis'] : ''; if( $zeit_bis=='00:00' ) $zeit_bis = '';
		$bg_nummer = $db -> f('bg_nummer');
		$bg_nummer_count = $db -> f('bg_nummer_count');
		
		// termin abgelaufen?
		$termin_abgelaufen = false;
		$heute_datum = strftime("%Y-%m-%d 00:00:00");
		if( $beginnsql > '0000-00-00' && $beginnsql < $heute_datum ) {
			$termin_abgelaufen = true;	
		}
		
		if (($wisyPortalSpalten & 2) > 0)
		{
			echo "    <td$terminAttr>";
			
			if( $termin_abgelaufen ) echo '<span class="wisy_datum_abgel">';
				if( $beginn )
				{
				    echo ($ende && $beginn!=$ende)? "$beginn - $ende" : $beginn;
					if( $beginnoptionen ) { echo "<br /><small>($beginnoptionen)</small>"; }
				}
				else if( $beginnoptionen )
				{
					echo $beginnoptionen;
				}
				else
				{
					echo 'k. A.';
				}
			if( $termin_abgelaufen ) echo '</span>';
				
			if( $zeit_von && $zeit_bis ) {
				echo "<br /><small>$zeit_von - $zeit_bis Uhr</small>"; 
			}
			else if( $zeit_von ) {
				echo "<br /><small>$zeit_von Uhr</small>"; 
			}
			
			if( $addText ) // z.B. für "2 weitere Durchführungen ..."
			{
				echo '<br />' . $addText;
			}
			
			echo '</td>' . "\n";
		}
		
		if (($wisyPortalSpalten & 4) > 0)
		{
			// dauer
			echo '    <td nowrap="nowrap">';
				echo $this->formatDauer($record['dauer'], $record['stunden'], '%1<br /><small>(%2)</small>');
			echo '</td>' . "\n";
		}
		
		if (($wisyPortalSpalten & 8) > 0)
		{
			// tagescode / bildungsurlaub / teilnehmende
			$tagescodeAttr = $details? '' : ' align="center"';
			echo "    <td$tagescodeAttr>";
	
				$cell = '';
				
				// art-spalte: tagescode und img.tag 
				$dfStichw = $addParam['stichwoerter'];
				$dfStichw[] = array('id'=>'tc'.$record['tagescode']);
				
				$cell .= $this->formatArtSpalte($dfStichw, $details);

				if( $details && $this->framework->iniRead('details.kurstage', 1)==1 ) {			
					$temp = $this->formatKurstage(intval($record['kurstage']));
					if( $temp ) {
						$cell .= ($cell? '<br />' : '') . "<small>$temp</small>";
					}
				}
								
				if( $details ) {
					if( $record['teilnehmer'] ) {
						$cell .= $cell? '<br />' : '';
						$cell .= '<small>max. ' . intval($record['teilnehmer']) . ' Teiln.</small>'; // "Teilnehmende" ist etwas zu lang für die schmale Spalte (zuvor waren die Teilnehmer unter den Bemerkungen, wo die Breite egal war)
					}
				}
				
				echo $cell;
								
			echo '</td>' . "\n";
		}
		
		if (($wisyPortalSpalten & 16) > 0)
		{
			// preis
			$preisAttr = '';//($details && $record['preishinweise']!='')? ' align=\"right\"' : ' nowrap="nowrap"';
			echo "    <td$preisAttr>";
				$temp = $this->formatPreis($record['preis'],
					$record['sonderpreis'], $record['sonderpreistage'], 
					$record['beginn'], $details? $record['preishinweise'] : '',
					true, /*format as HTML*/
					array(
						'showDetails'=>$details,
						'stichwoerter'=>$addParam['stichwoerter']
						)
					);
				echo $this->shy($temp);
			echo '</td>' . "\n";
		}
		
		if (($wisyPortalSpalten & 32) > 0)
		{
			// ort / bemerkungen
			$has_bemerkungen = trim($record['bemerkungen'])? true : false;
			echo "    <td>";
			
			// get ort
			$strasse	= $record['strasse'];
			$plz		= $record['plz'];
			$ort		= $record['ort']; // hier wird noch der Stadtteil angehängt
			$stadt		= $ort;
			$stadtteil	= $record['stadtteil'];
			$land		= $record['land'];
			if( $ort && $stadtteil ) {
				if( strpos($ort, $stadtteil)===false ) {
					$ort = isohtmlentities($ort) . '-' . isohtmlentities($stadtteil);
				}
				else {
					$ort = isohtmlentities($ort);
				}
			}
			else if( $ort ) {
				$ort = isohtmlentities($ort);
			}
			else if( $stadtteil ) {
				$ort = isohtmlentities($stadtteil);
				$stadt = $stadtteil;
			}
			else {
				$ort = '';
			}
			
			if( is_object($this->framework->map) )
			{
				$this->framework->map->addPoint2($record, $durchfuehrungId);
			}
			
			
			if( $details )
			{
				$cell = '';
				
				if( $strasse ) {
					$cell = isohtmlentities($strasse);
				}
				
				if( $ort ) {
					$cell .= $cell? '<br />' : '';
					$cell .= "$plz $ort";
				}
	
				if( $land ) {
					$cell .= $cell? '<br />' : '';
					$cell .= '<i>' . isohtmlentities($land) . '</i>';
				}

				if( $has_bemerkungen ) {
					$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
					$cell .= '<div style="font-size: 11px;">' . $wiki2html->run($record['bemerkungen']) . '</div>';
				}
				
				echo $cell? $cell : 'k. A.';
			}
			else
			{
				echo $ort? $ort : 'k. A.';
			}
			
			echo '</td>' . "\n";
		}
		
		if (($wisyPortalSpalten & 64) > 0)
		{
	
			// nr
			echo "    <td>";
			$nr = $record['nr'];
			echo $nr? isohtmlentities($nr) : 'k. A.';
			echo '</td>' . "\n";
		}
	
		if (($wisyPortalSpalten & 256) > 0) 
		{
		
			echo "    <td>";
				echo '<a href="edit_nr.php?wisyedit=durchf.' . $durchfuehrungId . '&amp;uri=' . $_SERVER['SCRIPT_URI'] . '&amp;query=' . urlencode($_SERVER['QUERY_STRING']) . '" title="'. $bg_nummer . '">';
				if($bg_nummer)
				{
					echo  $bg_nummer ;
				}
				else 
				{
					echo 'k.A.';
				}
				echo '</a>';
			echo '</td>' . "\n";
		}
	}
};

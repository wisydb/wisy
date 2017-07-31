<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************

WISY_SEARCH_CLASS wird verwendet, um genau spezifizierte Suchen zu starten oder
um Informationen zu Suchen zu erhalten.

WISY_SEARCH_CLASS führt keine alternativen Suchen durch (z.B. eine Volltextsuche,
wenn die normale Suche keinen Erfolg brachte). Wenn dies gewünscht ist, ist dies 
die Aufgabe des Aufrufenden Programmteils.

Beispiele zur Verwendung:


	// Suche nach Kursen oder Anbietern
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
						$searcher->prepare($query);
	$anzahlErgebnisse = $searcher->getKurseCount();
	$ergebnisse 	  = $searcher->getKurseRecords($offset, $rows, $orderby)


	// Suche nach Anbietern
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
						$searcher->prepare($query);
	$anzahlErgebnisse = $searcher->getAnbieterCount();
	$ergebnisse 	  = $searcher->getAnbieterRecords($offset, $rows, $orderby)


	// Suchstring in Tokens zerlegen (z.B. um Teile in der erweiterten Suche speziell darzustellen):
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
	$tokens 		  = $searcher->tokenize($query);

Achtung: Leere Anfragen oder Leere Ergebnismengen sind _keine_ Fehler!

*******************************************************************************/

class WISY_SEARCH_CLASS
{
	// all private!
	var $framework;
	
	var $db;
	private $dbCache;
	
	var $error;
	var $tokens;
	
	var $last_lat;
	var $last_lng;
	
	var $rawJoin;
	var $rawWhere;
	private $rawCanCache;
	
	function __construct(&$framework, $param)
	{
		global $wisyPortalId;
	
		$this->framework	=& $framework;
		
		$this->db			=  new DB_Admin;
		$this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_search', 'itemLifetimeSeconds'=>SEARCH_CACHE_ITEM_LIFETIME_SECONDS));
		
		$this->error		= false;
		$this->secneeded	= 0;
	}
	

	/**************************************************************************
	 * performing a search
	 **************************************************************************/

	function prepare($queryString)
	{
		// first, apply the stdkursfilter
		global $wisyPortalFilter;
		global $wisyPortalId;
		if( $wisyPortalFilter['stdkursfilter'] != '' )
		{
			$queryString .= ", .portal$wisyPortalId";
		}

		$this->error 		= false;
		$this->queryString	= $queryString; // needed for the cache
		
		$this->tokens		= $this->tokenize($queryString);
		$this->rawJoinKurse = '';
		$this->rawJoin 		= '';
		$this->rawWhere		= '';
		$this->rawCanCache	= true;
		
		// pass 1: collect some values
		$this->last_lat = 0;
		$this->last_lng = 0;
		$has_bei = false;
		$max_km = 500;
		$default_km = $this->framework->iniRead('radiussearch.defaultkm', 2);
		$km = floatval($default_km);
		for( $i = 0; $i < sizeof($this->tokens['cond']); $i++ )
		{
			$value = $this->tokens['cond'][$i]['value'];
			switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'bei':
					$has_bei = true;
					break;
					
				case 'km':
					$km = floatval(str_replace(',', '.', $value));
					if( $km <= 0.0 || $km > $max_km )
						$km = 0.0; // error
					break;
			}
		}
		
				
		// pass 2: create SQL
		$abgelaufeneKurseAnzeigen = 'no';
		for( $i = 0; $i < sizeof($this->tokens['cond']); $i++ )
		{
			// build SQL statements for this part
			$value = $this->tokens['cond'][$i]['value'];
			switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'tag':
					$tagNotFound = false;
					if( strpos($value, ' ODER ') !== false )
					{
						// ODER-Suche
						$subval = explode(' ODER ', $value);
						$rawOr = '';
						for( $s = 0; $s < sizeof($subval); $s++ )
						{	
							$tag_id = $this->lookupTag(trim($subval[$s]));
							if( $tag_id == 0 )
								{ $tagNotFound = true; break; }							
							$rawOr .= $rawOr==''? '' : ' OR ';
							$rawOr .= "j$i.tag_id=$tag_id";
						}
						if( !$tagNotFound )
						{
							$this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "($rawOr)";
						}
					}
					else
					{
						// einfache UND- oder NICHT-Suche
						$op = '';
						if( $value{0} == '-' )
						{
							$value = substr($value, 1);
							$op = 'not';
						}
						
						$tag_id = $this->lookupTag($value);
						if( $tag_id == 0 )
						{
							$tagNotFound = true;
						}
						else
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							if( $op == 'not' )
							{
								$this->rawWhere .= "x_kurse.kurs_id NOT IN(SELECT kurs_id FROM x_kurse_tags WHERE tag_id=$tag_id)";
							}
							else
							{
								$this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
								$this->rawWhere .= "j$i.tag_id=$tag_id";
							}
						}
					}

					if( $tagNotFound )
					{
						$this->error = array('id'=>'tag_not_found', 'tag'=>$value, 'first_bad_tag'=>$i);
						break;
					}
					break;

				case 'schaufenster':
					$portalId = intval($value);
					$this->rawJoin  .= " LEFT JOIN anbieter_promote j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					$this->rawWhere .= "(j$i.portal_id=$portalId AND j$i.promote_active=1)";
					break;
				
				case 'preis':
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis = intval($matches[1]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						if( $preis == 0 )
							$this->rawWhere .= "x_kurse.preis=0";
						else
							$this->rawWhere .= "(x_kurse.preis!=-1 AND x_kurse.preis<=$preis)";
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis1 = intval($matches[1]);
						$preis2 = intval($matches[2]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.preis>=$preis1 AND x_kurse.preis<=$preis2)";
					}
					else
					{
						$this->error = array('id'=>'invalid_preis', 'field'=>$value) ;
					}
					break;
				
				case 'plz':
					$this->rawJoin  .= " LEFT JOIN x_kurse_plz j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( strlen($value) < 5 )
						$this->rawWhere .= "(j$i.plz LIKE '".addslashes($value)."%')";
					else
						$this->rawWhere .= "(j$i.plz='".addslashes($value)."')";
					break;
				
				case 'id';
				case 'fav':
				case 'favprint': // favprint is deprecated
					$ids = array();
					$temp = $this->tokens['cond'][$i]['field']=='fav'? $_COOKIE['fav'] : $value;
					$temp = explode(',', strtr($temp, ' /',',,'));
					for( $j = 0; $j < sizeof($temp); $j++ ) {
						$ids[] = intval($temp[$j]); // safely get the IDs - do not use the Cookie/Request-String directly!
					}
					
					$this->rawCanCache = false;
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $ids)."))";
						$abgelaufeneKurseAnzeigen = 'void';
					}
					else {
						$this->rawWhere .= '(0)';
					}
					break;
				
				case 'nr':
					// search for durchfuehrungsnummer
					$nrSearcher = createWisyObject('WISY_SEARCH_NR_CLASS', $this->framework);
					$ids = $nrSearcher->nr2id($value);
					$this->rawCanCache = false; // no caching as we have different results for login/no login
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $ids)."))";
						$abgelaufeneKurseAnzeigen = 'void'; // implicitly show expired results if a number was searched
					}
					else {
						$this->rawWhere .= '(0)';
					}							
					break;
				
				case 'bei':
					if( preg_match('/^\s*(\d+(\.\d+)?)\s*\/\s*(\d+(\.\d+)?)\s*$/', $value, $matches) ) // angabe lat/lng
					{
						$lat = floatval($matches[1]);
						$lng = floatval($matches[3]);
						$gi = array('direct_lat'=>$lat, 'direct_lng'=>$lng); // just for nicer debug view
					}
					else
					{
						$obj =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
						$gi = $obj->geocode2(array('free'=>str_replace('/', ',', $value))); // in Adressen muss der Schraegstrich anstelle des Kommas verwendet werden (das Komma trennt ja schon die verschiedenenn Suchkriterien)
						if( $gi['error'] ) {
							$this->error = array('id'=>'bad_location', 'field'=>$value, 'status'=>404);
						}
						else {
							$lat = $gi['lat'];
							$lng = $gi['lng'];
						}
					}

					if( !is_array($this->error) )
					{
						$radius_meters = $km * 1000.0;
						
						$radius_lat = $radius_meters / 111320.0; // Abstand zwischen zwei Breitengraden: 111,32 km  (weltweit)
						$radius_lng = $radius_meters /  71460.0; // Abstand zwischen zwei Längengraden :  71,46 km  (im mittel in Deutschland)
						
						$min_lat = intval( ($lat - $radius_lat)*1000000 );
						$max_lat = intval( ($lat + $radius_lat)*1000000 );
						$min_lng = intval( ($lng - $radius_lng)*1000000 );
						$max_lng = intval( ($lng + $radius_lng)*1000000 );

						$this->rawJoin  .= " LEFT JOIN x_kurse_latlng j$i ON x_kurse.kurs_id=j$i.kurs_id";
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "((j$i.lat BETWEEN $min_lat AND $max_lat) AND (j$i.lng BETWEEN $min_lng AND $max_lng))"; // lt. http://dev.mysql.com/doc/refman/4.1/en/mysql-indexes.html wird der B-Tree auch fuer groesser/kleiner oder BETWEEN abfragen verwendet.
						
						if( isset($_COOKIE['debug']) )
						{
							echo '<p style="background-color: orange;">gi: ' . isohtmlspecialchars(print_r($gi, true)) . '</p>';
						}

						// remember some stuff for the getInfo() function (needed eg. for the "distance"-column)
						$this->last_lat = $lat;
						$this->last_lng = $lng;
					}
					break;
				
				case 'km':
					if( !$has_bei )
					{
						$this->error = array('id'=>'km_without_bei');
					}
					else if( $km == 0.0 )
					{
						$this->error = array('id'=>'bad_km', 'max_km'=>$max_km, 'default_km'=>$default_km);
					}
					break;
				
				case 'datum':
					if( strtolower($value) == 'alles' )
					{
						$abgelaufeneKurseAnzeigen = 'yes';
					}
					else if( preg_match('/^heute([+-][0-9]{1,5})?$/i', $value, $matches) )
					{
						$offset = intval($matches[1]);
						$abgelaufeneKurseAnzeigen = 'void';
						$todayMidnight = strtotime(strftime("%Y-%m-%d"));
						$wantedday = strftime("%Y-%m-%d", $todayMidnight + $offset*24*60*60);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.beginn_last>='$wantedday')"; // 13:58 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
					}
					else if( preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})$/', $value, $matches) )
					{
						$day = intval($matches[1]);
						$month = intval($matches[2]);
						$year = intval($matches[3]); if( $year <= 99 ) $year += 2000;
						$timestamp = mktime(0, 0, 0, $month, $day, $year);
						if( $timestamp <= 0 )
						{
							$this->error = array('id'=>'invalid_date', 'date'=>$value) ;
						}
						else
						{
							$abgelaufeneKurseAnzeigen = 'void';
							$wantedday = strftime("%Y-%m-%d", $timestamp);
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.beginn_last>='$wantedday')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
						}
					}
					else
					{
						$this->error = array('id'=>'invalid_date', 'field'=>$value) ;
					}
					break;
				
				case 'dauer':
					$dauer_error = true;
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer = intval($matches[1]);
						if( $dauer > 0 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer!=0 AND x_kurse.dauer<=$dauer)";
							$dauer_error = false;
						}
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer1 = intval($matches[1]);
						$dauer2 = intval($matches[2]);
						if( $dauer1 > 0 && $dauer2 > 0 && $dauer1 <= $dauer2 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer>=$dauer1 AND x_kurse.dauer<=$dauer2)";
							$dauer_error = false;
						}
					}
					
					if( $dauer_error )
					{
						$this->error = array('id'=>'invalid_dauer', 'field'=>$value) ;
					}
					break;
				
				case 'volltext':
					// volltextsuche, aktuell gibt es ein Volltextindex über kurse.titel und kurse.beschreibung; dieser
					// wird vom core10 *nicht* verwendet und vom redaktionssystem wohl eher selten.
					// aktuell nehmen wird diesen Index einfach, sollten wir hier aber etwas anderes benötigen, 
					// kann der alte Volltextindex verworfen werden. ALSO:
					if( $value != '' )
					{
						$this->rawJoinKurse = " LEFT JOIN kurse ON x_kurse.kurs_id=kurse.id";	 // this join is needed only to query COUNT(*)
						
						$this->rawWhere    .= $this->rawWhere? ' AND ' : ' WHERE ';				
						$this->rawWhere    .= "MATCH(kurse.titel, kurse.beschreibung) AGAINST('".addslashes($value)."' IN BOOLEAN MODE)";
					}
					else
					{
						$this->error = array('id'=>'missing_fulltext') ;
					}
					break;
				
				default:
					$this->error = array('id'=>'field_not_found', 'field'=>$this->tokens['cond'][$i]['field']) ;
					break;
			}			
		}
		
		/* -- leere Anfragen sind für "diese kurse beginnen morgen" notwendig, leere Anfragen sind _kein_ Fehler!
		if( !is_array($this->error) && $this->rawWhere=='' )
		{
			$this->error = array('id'=>'empty_query');
		}
		*/

		// finalize SQL
		if( !is_array($this->error) )
		{
			if( $abgelaufeneKurseAnzeigen == 'no' )
			{
				$today = strftime("%Y-%m-%d");
				
				$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				$this->rawWhere .= "(x_kurse.beginn>='$today')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
			}
		}
	}
	
	function ok()
	{
		// check if there is an error or not - empty queries or results are no errors!
		return !is_array($this->error);
	}

	function getInfo()
	{
		// return an information-array
		// NOTE: "no records found" is no error if the query is fine. 
		return array(
			'show'		=>	$this->tokens['show'],
			'error'		=>	$this->error,
			'secneeded'	=>	$this->secneeded,
			'lat'		=>	$this->last_lat,
			'lng'		=>	$this->last_lng,
		);
			
	}

	function getKurseCount()
	{
		$ret = 0;
		
		if( $this->error === false )
		{
			$start = $this->framework->microtime_float();

				global $wisyPortalId;
				$do_recreate = true;
				$cacheKey = "wisysearch.$wisyPortalId.$this->queryString.count";
				if( $this->rawCanCache && ($temp=$this->dbCache->lookup($cacheKey))!='' )
				{
					$ret = unserialize($temp);
					if( $ret === false )
					{
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseCount(): bad counts for key <i>$cacheKey</i>, recreating  ...</p>";
						}
					}
					else
					{
						$do_recreate = false;
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseCount(): counts for key <i>$cacheKey</i> loaded from cache ...</p>";
						}
					}
				}
				
				if( $do_recreate )
				{
					$sql = "SELECT COUNT(DISTINCT x_kurse.kurs_id) AS cnt FROM x_kurse " . $this->rawJoinKurse . $this->rawJoin . $this->rawWhere;
					$this->db->query("SET SQL_BIG_SELECTS=1"); // optional
					$this->db->query($sql);
					if( $this->db->next_record() )
						$ret = intval($this->db->f('cnt'));
					$this->db->free();
					
					// add to cache
					$this->dbCache->insert($cacheKey, serialize($ret));

					if( isset($_COOKIE['debug']) ) {
						echo '<p style="background-color: yellow;">getKurseCount(): ' .isohtmlspecialchars($sql). '</p>';
					}
				}
					
				if( isset($_COOKIE['debug']) ) {
					echo '<p style="background-color: yellow;">getKurseCount(): ' .$ret. '</p>';
				}
							
			$this->secneeded += $this->framework->microtime_float() - $start;
		}
		
		return $ret;
	}
	
	function getKurseRecordsSql($fields)
	{
		// create complete SQL query
		$sql =  "SELECT DISTINCT $fields
				   FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . $this->rawWhere;

		return $sql;		
	}
	
	function getKurseRecords($offset, $rows, $orderBy)
	{
		$ret = array('records'=>array());
		
		if( $this->error === false )
		{
			$start = $this->framework->microtime_float();
			
				global $wisyPortalId;
				$do_recreate = true;
				$cacheKey = "wisysearch.$wisyPortalId.$this->queryString.$offset.$rows.$orderBy";
				if( $this->rawCanCache && ($temp=$this->dbCache->lookup($cacheKey))!='' )
				{	
					// result in cache :-)
					$ret = unserialize($temp);
					if( $ret === false )
					{
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseRecords(): bad result for key <i>$cacheKey</i>, recreating  ...</p>";
						}
					}
					else
					{
						$do_recreate = false;
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseRecords(): result for key <i>$cacheKey</i> loaded from cache ...</p>";
						}
					}
				}
				
				if( $do_recreate )
				{
					switch( $orderBy )
					{
						case 'a':		$orderBy = "x_kurse.anbieter_sortonly";						break;	// sortiere nach anbieter
						case 'ad':		$orderBy = "x_kurse.anbieter_sortonly DESC";				break;
						case 't':		$orderBy = 'kurse.titel_sorted';							break;	// sortiere nach titel
						case 'td':		$orderBy = 'kurse.titel_sorted DESC';						break;
						case 'b':		$orderBy = "x_kurse.beginn='0000-00-00', x_kurse.beginn";		break;	// sortiere nach beginn, spezielle Daten ans Ende der Liste verschieben
						case 'bd':		$orderBy = "x_kurse.beginn='9999-09-09', x_kurse.beginn DESC";	break;
						case 'd':		$orderBy = 'x_kurse.dauer=0, x_kurse.dauer';				break;	// sortiere nach dauer
						case 'dd':		$orderBy = 'x_kurse.dauer DESC';							break;
						case 'p':		$orderBy = 'x_kurse.preis=-1, x_kurse.preis';				break;	// sortiere nach preis
						case 'pd':		$orderBy = 'x_kurse.preis DESC';							break;
						case 'o':		$orderBy = "x_kurse.ort_sortonly='', x_kurse.ort_sortonly";	break;	// sortiere nach ort
						case 'od':		$orderBy = "x_kurse.ort_sortonly DESC";						break;
						case 'creat':	$orderBy = 'x_kurse.begmod_date';							break;	// sortiere nach beginnaenderungsdatum (hauptsächlich für die RSS-Feeds interessant)
						case 'creatd':	$orderBy = 'x_kurse.begmod_date DESC';						break;
						case 'rand':	$orderBy = 'RAND()';										break;
						default:		$orderBy = 'kurse.id';										die('invalid order!');
					}
					
					$sql = $this->getKurseRecordsSql("kurse.id, kurse.anbieter, kurse.freigeschaltet, kurse.titel, kurse.vollstaendigkeit, kurse.date_modified, kurse.bu_nummer, kurse.fu_knr, kurse.azwv_knr, x_kurse.begmod_date");
					$sql .= " ORDER BY $orderBy, vollstaendigkeit DESC, x_kurse.kurs_id ";
					$sql .= " LIMIT $offset, $rows ";
					
					$this->db->query("SET SQL_BIG_SELECTS=1"); // optional
					$this->db->query($sql);
					while( $this->db->next_record() )
						$ret['records'][] = $this->db->Record;
					$this->db->free();
					
					// add result to cache
					$this->dbCache->insert($cacheKey, serialize($ret));
					
					if( isset($_COOKIE['debug']) ) {
						echo '<p style="background-color: yellow;">getKurseRecords(): ' .isohtmlspecialchars($sql). '</p>';
					}
				}
			
			$this->secneeded += $this->framework->microtime_float() - $start;
		}
		
		return $ret;
	}

	function getAnbieterCount()
	{
		$ret = 0;
		
		if( $this->error === false )
		{
			$start = $this->framework->microtime_float();
			
				$sql = "SELECT DISTINCT kurse.anbieter FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . $this->rawWhere;
				$this->db->query($sql);
				while( $this->db->next_record() )
				{
					$this->anbieterIds .= $this->anbieterIds==''? '' :', ';
					$this->anbieterIds .= intval($this->db->f('anbieter'));
					$ret++;
				}
				$this->db->free();
			
			$this->secneeded += $this->framework->microtime_float() - $start;
		}
		
		return $ret;
	}

	function getAnbieterRecords($offset, $rows, $orderBy)
	{
		$ret = array('records'=>array());
		
		if( !isset($this->anbieterIds) )
		{
			$this->getAnbieterCount(); // this little HACK sets $this->anbieterIds ...
		}
		
		if( $this->error === false && $this->anbieterIds != '' )
		{
			// apply order
			switch( $orderBy )
			{
				// ...
				case 'a':		$orderBy = "anbieter.suchname_sorted";					break;
				case 'ad':		$orderBy = "anbieter.suchname_sorted DESC";				break;

				case 's':		$orderBy = "strasse";									break;
				case 'sd':		$orderBy = "strasse DESC";								break;

				case 'p':		$orderBy = "plz";										break;
				case 'pd':		$orderBy = "plz DESC";									break;

				case 'o':		$orderBy = "ort";										break;
				case 'od':		$orderBy = "ort DESC";									break;

				case 'h':		$orderBy = "homepage";									break;
				case 'hd':		$orderBy = "homepage DESC";								break;

				case 'e':		$orderBy = "anspr_email";								break;
				case 'ed':		$orderBy = "anspr_email DESC";							break;

				case 't':		$orderBy = "anspr_tel";									break;
				case 'td':		$orderBy = "anspr_tel DESC";							break;
				
				// sortiere nach erstellungsdatum (hauptsächlich für die RSS-Feeds interessant)
				case 'creat':	$orderBy = 'date_created';								break;
				case 'creatd':	$orderBy = 'date_created DESC';							break;

				default:		$orderBy = 'anbieter.id';								die('invalid order!');
			}
			
			// create complete SQL query
			$sql =  "SELECT id, date_created, date_modified, suchname, strasse, plz, ort, homepage, anspr_email, anspr_tel, typ FROM anbieter WHERE anbieter.id IN($this->anbieterIds)";
			$sql .= " ORDER BY $orderBy, anbieter.id ";
			$sql .= " LIMIT $offset, $rows ";
			
			if( isset($_COOKIE['debug']) )
			{
				echo '<p style="background-color: yellow;">' .isohtmlspecialchars($sql). '</p>';
			}
			
			
			$start = $this->framework->microtime_float();
			
				$this->db->query($sql);
				while( $this->db->next_record() )
					$ret['records'][] = $this->db->Record;
				$this->db->free();
			
			$this->secneeded += $this->framework->microtime_float() - $start;
		}		
		
		return $ret;
	}


	/**************************************************************************
	 * tools
	 **************************************************************************/
	
	function tokenize($queryString)
	{
		// function takes a comma-separated query and splits it into tags
		// 
		// returns an array as follows
		// array(
		//		'show' 		=> 'anbieter'
		//    	'cond' => array(
		//    		[0] array('field'=>'tag', 'value'='englisch')
		//		),
		// )
		$ret = array(
			'show'		=>	'kurse',
			'cond'		=>	array(),
		);

		$queryArr = explode(',', $queryString);
		for( $i = 0; $i < sizeof($queryArr); $i++ )
		{
			// get initial value to search tags for, remove multiple spaces
			$field = '';
			$value = trim($queryArr[$i]);
			while( strpos($value, '  ')!==false )
				$value = str_replace('  ', ' ', $value);
			
			// find out the field to search the value in (defaults to "tag:")
			if( ($p=strpos($value, ':'))!==false )
			{
				$field = strtolower(trim(substr($value, 0, $p)));
				$value = trim(substr($value, $p+1));
				
				if( $field == 'zeige' )
				{
					$ret['show'] = strtolower($value);
					continue;
				}
			}
			else if( $value != '' )
			{
				$field = 'tag';
			}

			// any token?
			if( $field!='' || $value!='' )
			{
				$ret['cond'][] = array('field'=>$field, 'value'=>$value);
			}
		}
		
		return $ret;
	}
	
	function lookupTag($tag_name)
	{
		// search a single tag
		$tag_id = 0;
		if( $tag_name != '' )
		{
			$this->db->query("SELECT tag_id, tag_type FROM x_tags WHERE tag_name='".addslashes($tag_name)."';");
			if( $this->db->next_record() )
			{
				$tag_type = $this->db->f('tag_type');
				if( $tag_type & 64 )
				{
					// synonym - ein lookup klappt nur, wenn es nur _genau_ ein synonym gibt
					$temp_id   = $this->db->f('tag_id');
					$syn_ids = array();
					$this->db->query("SELECT t.tag_id FROM x_tags t LEFT JOIN x_tags_syn s ON s.lemma_id=t.tag_id WHERE s.tag_id=$temp_id");
					while( $this->db->next_record() )
					{
						$syn_ids[] = $this->db->f('tag_id');
					}
					
					if( sizeof( $syn_ids ) == 1 )
					{
						$tag_id = $syn_ids[0]; /*directly follow 1-dest-only-synonyms*/
					}
				}
				else
				{
					// normales lemma
					$tag_id   = $this->db->f('tag_id');
				}
			}
		}
		return $tag_id;
	}


};


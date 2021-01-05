<?php

/******************************************************************************
 * WISY RSS-Feeds ausgeben
 ******************************************************************************
 * Verwendetes Format:
 *
 *	<?xml version="1.0" encoding="UTF-8"?>
 *	<rss version="2.0">
 *		<channel>
 *			<title>".html_entity_decode($wisyPortalKurzname)."</title>
 *			<description></description>
 *			<link>".html_entity_decode($wisyPortalKurzname)."</link>
 *			<lastBuildDate>".date("D, d M Y G:i:s O")."</lastBuildDate>
 *			<item>
 *				<title>Kommunikationswerkzeuge</title>
 *				<description><![CDATA[<B>Beginn</B>: 24.09.09<br /><B>Dauer</B>: 1 Tag<br /><B>Preis</B>: 92 &euro;<br /><B>Ort</B>: Kiel]]></description>
 *				<link>http://sh.kursportal.info/kurse.php?id=80348</link>
 *				<pubDate>Wed, 23 Sep 2009 11:41:52 +0200</pubDate>
 *			</item>
 *			<item>
 *				...
 *			</item>
 *		</channel>
 *	</rss>
 *****************************************************************************/


class WISY_RSS_RENDERER_CLASS
{
	var $framework;
	var $queryString;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework 	=& $framework;
		$this->queryString	= rtrim($param['q'], ', '); // remove trailing commas and spaces
		$this->domain       = $_SERVER['HTTP_HOST'];
		$protocol = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$this->absPath 		= $protocol.':/' . '/' . $this->domain . '/';
		
		$this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_rss', 'itemLifetimeSeconds'=>60*60));
	}

	function createDurchfuehrungContent(&$db, &$durchfClass, $addParam)
	{
		global $wisyPortalSpalten;

		$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $addParam['record']['id']); // $durchfuehrungenIds enthalten bereits nur die relevanten durchfuehrungen
		if( count((array) $durchfuehrungenIds) == 0 )
			return '';
		
		// collect data
		$all_beginn = array();
		$all_beginnoptionen = array();
		$dauer = '';
		$preis = '';
		$bemerkungen = '';
		for( $d = 0; $d < count((array) $durchfuehrungenIds); $d++ )
		{	
			$durchfuehrungId = $durchfuehrungenIds[$d];
			$db->query("SELECT beginn, bemerkungen, beginnoptionen, dauer, stunden, preis, sonderpreis, sonderpreistage, ort, stadtteil FROM durchfuehrung WHERE id=$durchfuehrungId");
			if( $db->next_record() )
			{
				// beginn				
				$beginn = $this->framework->formatDatum($db->fcs8('beginn'));
				if( $beginn && !in_array($beginn, $all_beginn) )
					$all_beginn[] = $beginn;
				
				$beginnoptionen = $durchfClass->formatBeginnoptionen($db->fcs8('beginnoptionen'));
				if( $beginnoptionen && !in_array($beginnoptionen, $all_beginnoptionen) )
					$all_beginnoptionen[] = $beginnoptionen;
				
				// dauer
				if( $dauer == '' )
					$dauer = $durchfClass->formatDauer($db->fcs8('dauer'), $db->fcs8('stunden'));
				
			    // bemerkungen
			    if( $bemerkungen == '' )
					$bemerkungen = $db->fcs8('bemerkungen');
				
				// preis
				if( $preis == '' )
					$preis = $durchfClass->formatPreis($db->fcs8('preis'), $db->fcs8('sonderpreis'), $db->fcs8('sonderpreistage'), $db->fcs8('beginn'), '', 0);
				
				// ort
				if( $ort == '' )
				{
					$ort            = $db->fcs8('ort'); // hier wird noch der Stadtteil angehaegt
					$stadtteil      = $db->fcs8('stadtteil');
					if( $ort!='' && $stadtteil!='' ) {
						if( strpos($ort, $stadtteil)===false ) {
							$ort = htmlentities($ort) . '-' . htmlentities($stadtteil);
						}
						else {
							$ort = htmlentities($ort);
						}
					}
					else if( $ort!='' ) {
						$ort = htmlentities($ort);
					}
					else if( $stadtteil!='' ) {
						$ort = htmlentities($stadtteil);
					}
					else {
						$ort = '';
					}
				}
			}
		}

		if( $addParam['record']['freigeschaltet'] == 4 )
		{
			$all_beginnoptionen[] = 'dauerhaftes Angebot'; 
		}
					
		// create output
		$ret = '';
		if (($wisyPortalSpalten & 2) > 0)
		{
		    $temp  = implode(', ', $all_beginn);
		    $temp .= ($temp==''||count((array) $all_beginnoptionen)==0? '' : ', ') . implode(', ', $all_beginnoptionen);
		    
		    $ret .= 'Beginn: ' . ($temp==''? 'k.A.' : $temp);
		}
		
		if (($wisyPortalSpalten & 4) > 0)
		{
			$ret .= $ret==''? '' : ' - ';
			$ret .= "Dauer: " . $dauer;
		}

		if (($wisyPortalSpalten & 16) > 0)
		{
			$ret .= $ret==''? '' : ' - ';
			$ret .= "Preis: " . $preis;
		}

		if (($wisyPortalSpalten & 32) > 0)
		{
			$ret .= $ret==''? '' : ' - ';
			$ret .= "Ort: " . ($ort? $ort : 'k. A.');
		}
		
		if (($wisyPortalSpalten & 128) > 0)
		{
		    $ret .= $ret==''? '' : ' - ';
		    $ret .= "Bemerkungen: " . ($bemerkungen? $bemerkungen : '--');
		}
						
		// done
		$ret .= $ret==''? '' : '<br />';
		return $ret;
	}
	
	function createRssContent()
	{
		global $wisyPortalName;
		global $wisyPortalKurzname;

		$db2 = new DB_Admin;

		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare($this->framework->mysql_escape_mimic($this->queryString));

		$queryHtml = htmlspecialchars($this->queryString);
		$queryHtmlLong  = $queryHtml==''? ''                  : " - Anfrage: $queryHtml";
		$queryHtmlShort = $queryHtml==''? ' - aktuelle Kurse' : " - $queryHtml";

		$ret  = "<?"."xml version=\"1.0\" encoding=\"UTF-8\" ?".">\n";
		$ret .= "<rss version=\"2.0\">\n";
		$ret .= "  <channel>\n";
		$ret .= "    <title>".htmlspecialchars($wisyPortalKurzname)."$queryHtmlShort</title>\n";
		$ret .= "    <description>".htmlspecialchars($wisyPortalName)."$queryHtmlLong</description>\n";
		$ret .= "    <link>{$this->absPath}search?q=".urlencode($this->queryString)."</link>\n";
		$ret .= "    <lastBuildDate>".date("D, d M Y G:i:s O")."</lastBuildDate>\n";

		if( $searcher->ok() )
		{
			if( $searcher->tokens['show'] == 'anbieter' )
			{
				$records = $searcher->getAnbieterRecords(0 /*offset immer 0*/, 10 /*immer 10 eintraege*/, 'creatd' /*sortierung immer nach erstellungsdatum (sonst kommt "nichts neues" bzw. das neue kommt zu spät)*/);
				foreach($records['records'] as $i => $record)
				{
					// beschreibung erstellen
					$descrHtml  = '';
					$descrHtml .= "Aufgenommen: " . $this->framework->formatDatum($record['date_created']) . ' - ';
					$descrHtml .= "Ort: " . htmlspecialchars($record['ort']? $record['ort'] : 'k. A.') . '<br />';
					$descrHtml .= '<a href="' . $this->absPath.'a'.$record['id'] . '">weitere Informationen auf '.htmlspecialchars($this->domain).' ...</a>';
					
					// itemdatum: es kommen nur neu erfasste anbieter in das RSS, daher hier einfach das erstellungsdatum
					$pubDate_str = $record['date_created'];
					
					// item erzeugen
					$ret .= "      <item>\n";
					$ret .= "        <title>".htmlspecialchars($record['suchname'])."</title>\n";
					$ret .= "        <description><![CDATA[$descrHtml]]></description>\n";
					$ret .= "        <link>{$this->absPath}a".$record['id']."</link>\n";
					$ret .= "        <pubDate>".date("D, d M Y G:i:s O", strtotime($pubDate_str))."</pubDate>\n";
					$ret .= "      </item>\n";
				}
			}
			else
			{
				$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
				
				$records = $searcher->getKurseRecords(0 /*offset immer 0*/, 10 /*immer 10 eintraege*/, 'creatd' /*sortierung immer nach "beginnaenderungsdatum" (sonst kommt "nichts neues" bzw. das neue kommt zu spät)*/);
				foreach($records['records'] as $i => $record)
				{
					// beschreibung erstellen
					$descrHtml = '';
					$descrHtml .= $this->createDurchfuehrungContent($db2, $durchfClass, array('record'=>$record));
					$descrHtml .= '<a href="' . $this->absPath.'k'.$record['id'] . '">weitere Informationen auf '.htmlspecialchars($this->domain).' ...</a>';
				
					// itemdatum: da wir neue durchfuerungen erwischen moechten, ist dies das "beginnaenderungsdatum"
					$pubDate_str = $record['begmod_date'];
					if( $pubDate_str=='0000-00-00 00:00:00' || $pubDate_str=='' )
						continue;
					
					// item erzeugen
					$ret .= "      <item>\n";
					$ret .= "        <title>".htmlspecialchars($record['titel'])."</title>\n";
					$ret .= "        <description><![CDATA[$descrHtml]]></description>\n";
					$ret .= "        <link>{$this->absPath}k".$record['id']."</link>\n";
					$ret .= "        <pubDate>".date("D, d M Y G:i:s O", strtotime($pubDate_str))."</pubDate>\n";
					$ret .= "      </item>\n";
				}
			}
		}
		
		$ret .= "  </channel>\n";
		$ret .= "</rss>\n";
		
		return $ret;
	}
	
	function render()
	{	
		global $wisyPortalId;
		$cacheKey = "rss.$wisyPortalId.$this->queryString";

		$ret = '';
		if( ($temp=$this->dbCache->lookup($cacheKey))!='' )
		{
			$ret = $temp;
		}
		else
		{
			$ret = $this->createRssContent();
			$this->dbCache->insert($cacheKey, $ret);
		}
		
		
		if( 0 )
		{
			echo nl2br(htmlspecialchars($ret));
		}
		else
		{
			header ('Content-Type: text/xml; charset=UTF-8');
			echo $ret;
		}
	}	
};

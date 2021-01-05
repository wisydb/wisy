<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_ROBOTS_RENDERER_CLASS
{
	var $framework;
	var $param;

	function __construct(&$framework, $param)
	{
		// constructor
		$this->framework =& $framework;
		$this->param = $param;

		$this->domain       = $_SERVER['HTTP_HOST'];
		$protocol = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$this->absPath 		= $protocol.':/' . '/' . $this->domain . '/';

		$this->sitemapCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_sitemap', 'storeBlobs'=>true, 'itemLifetimeSeconds'=>24*60*60));
	}

	function render()
	{
		$this->framework->log('robots', $this->absPath . $this->param['src'] . " requested by " . $_SERVER['REMOTE_ADDR'] . ", " . $_SERVER['HTTP_USER_AGENT']);
		
		switch( $this->param['src'] )
		{
			case 'robots.txt':
				$this->renderRobotsTxt();
				break;
			
			case 'sitemap.xml':
				$this->renderSitemapXml(); // der name ist eh nicht wichtig ... renderSitemapXmlGz
				break;

			case 'sitemap.xml.gz':
				$this->renderSitemapXmlGz();
				break;
			
			case 'terrapin': 
				$this->renderHoneypot();
				break;
			
			default:
				$this->framework->error404();
				break;				
		}
	}



	/* handle our honeypot, "/terrapin"
	 *************************************************************************/

	function renderHoneypot()
	{
		// "/terrapin" ist unser "Honeypot" für Bots, die sich nicht an die Regeln halten; 
		// derzeit nehmen wir keine Sanktionen vor, einfach nur mal beobachten ...
		// (log wurde schon in render() geschrieben)

		echo $this->framework->getPrologue(array('title'=>'Terrapin', 'bodyClass'=>'wisyp_search'));
		echo $this->framework->getSearchField();
			echo '<h1>The Terrapin</h1>';
			echo '<p>Access to <em>/terrapin</em> is disallowed, please start over with the <a href="/">homepage</a>. Thanx.</p>';
		echo $this->framework->getEpilogue();
	}
	

	/* handle robots.txt
	 *************************************************************************/
	
	function renderRobotsTxt()
	{
		// robots start
		header("Content-type: text/plain");
		headerDoCache();
		
		if( strpos($_SERVER['HTTP_HOST'], 'sandbox')!==false || strpos($_SERVER['HTTP_HOST'], 'backup')!==false || $this->framework->iniRead('seo.portal_blockieren', false) )
		{
			echo "User-agent: *\n";
			echo "Disallow: /\n";
		}
		else
		{
		    $block_specificlink = array_map("trim", explode(",", $this->framework->iniRead('seo.links_blockieren', "")));
		    
			// set the sitemap, 
			// dies steht in keinem zusammenhang mit User-agent, 
			// siehe https://www.sitemaps.org/protocol.php#submit_robots 
			echo "Sitemap: {$this->absPath}sitemap.xml.gz\n";
			
			// set landingpages sitemap
			echo "Sitemap: {$this->absPath}sitemap-landingpages.xml\n";
			
			// allow the adsense spider to crawl everything
			echo "User-agent: Mediapartners-Google*\n";
			echo "Disallow: /terrapin\n";
			
			echo "\n";
			
			// for all other spiders
			echo "User-agent: *\n";
			echo "Disallow: /advanced\n";
			echo "Disallow: /edit\n";
			echo "Disallow: /api\n";
			echo "Disallow: /filter\n";
			echo "Disallow: /edit\n";
			echo "Disallow: /rss\n";
			echo "Disallow: /terrapin\n";
			echo "Disallow: /search?q=volltext*\n";
			echo "Disallow: /search?q=*volltext\n";
			echo "Disallow: /search?qs=volltext*\n";
			echo "Disallow: /search?qs=*volltext\n";
			echo "Disallow: /search?qf=volltext*\n";
			echo "Disallow: /search?qf=*volltext\n";
			echo "Disallow: /search?*q=volltext*\n";
			echo "Disallow: /search?*q=*volltext\n";
			echo "Disallow: /search?*qs=volltext*\n";
			echo "Disallow: /search?*qs=*volltext\n";
			echo "Disallow: /search?*qf=volltext*\n";
			echo "Disallow: /search?*qf=*volltext\n";
			
			echo "Disallow: /g151\n"; // for historic legal reasons, better: use portal setting below.
			
			foreach($block_specificlink AS $link) {
			    if(strlen($link) >= 1)
			        echo "Disallow: ".$link."\n";
			}
		}
		
		echo "Crawl-delay: 10\n";
		
		echo "\n\n";
		echo "User-agent: ia_archiver\n";
		echo "Disallow: /\n";
		
		echo "\n\n";
		echo "User-agent: SemrushBot\n";
		echo "Disallow: /\n";
		
		echo "\n\n";
		echo "User-agent: SemrushBot-SA\n";
		echo "Disallow: /\n";
	}



	/* handle sitemap.xml
	 *************************************************************************/

	function addUrl($url, $lastmod, $changefreq)
	{
	    $block_specificlink = array_map("trim", explode(",", $this->framework->iniRead('seo.links_blockieren', "")));
	    foreach($block_specificlink AS $link) { // $link may be link fragment
	        if(strlen($link) > 1 && strpos($this->absPath.$url, $link) !== false)
	            return "";
	    }
	    
		$this->urlsAdded ++;
		return "<url><loc>{$this->absPath}$url</loc><lastmod>" .strftime("%Y-%m-%d", $lastmod). "</lastmod><changefreq>$changefreq</changefreq></url>\n";
	}

	function createSitemapXml(&$sitemap /*by reference to save some MB*/)
	{
		// sitemap start
		$sitemap =  "<" . "?xml version=\"1.0\" encoding=\"UTF-8\" ?" . ">\n";
		$sitemap .= "<urlset xmlns=\"https:/" . "/www.sitemaps.org/schemas/sitemap/0.9\">\n";
		
		if( $this->framework->iniRead('seo.portal_blockieren', false) ) {
		    $sitemap .= "</urlset>\n";
		    return;
		}
		
		$this->urlsAdded = 0;
		
		// grenzen fuer sitemaps:
		// - max. 10 MB für die unkomprimierte Sitemap
		// - max. 50000 URLs pro Sitemap		
		// wir setzen unsere grenzen etwas weiter unten an, damit der Server nicht zu arg belastet wird, für die meisten portale sollten 20000 _aktuelle_ Urls aber absolut ausreichen
		$maxUrls = 25000;

		// dump homepage
		$sitemap .= $this->addUrl('', time(), 'daily');

		// die zuletzt erzeugten Anbieter ausgeben (i.d.R. sind dies alle Anbieter)
		$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
		$searcher->prepare('Zeige:Anbieter');
		if( $searcher->ok() )
		{
			$records = $searcher->getAnbieterRecords(0 /*offset*/, intval($maxUrls/2) /*rows*/, 'creatd');
			for( $r = 0; $r < count((array) $records['records']); $r++ )
			{
				$sitemap .= $this->addUrl('a'.$records['records'][$r]['id'], strtotime($records['records'][$r]['date_modified']), 'monthly');
			}
		}

		// die zuletzt geaenderten Kurse ausgeben
		$searcher->prepare('');
		if( $searcher->ok() )
		{
			$sql = $searcher->getKurseRecordsSql('kurse.id, kurse.date_modified, kurse.freigeschaltet');
			$sql.= ' ORDER BY kurse.date_modified DESC ';
			$sql.= " LIMIT 0, $maxUrls ";
			
			$freigeschaltet404 = array_map("trim", explode(",", $this->framework->iniRead('seo.set404_kurs_freigeschaltet', "")));
			
			$searcher->db->query($sql);
			while( $searcher->db->next_record() )
			{
				$freigeschaltet = intval($searcher->db->fcs8('freigeschaltet'));
				
				if(!in_array($freigeschaltet, $freigeschaltet404))
					$sitemap .= $this->addUrl('k'.$searcher->db->fcs8('id'), strtotime($searcher->db->fcs8('date_modified')), 'monthly');
				
				if( $this->urlsAdded >= $maxUrls )
				{
					$sitemap .= "<!-- stop adding URLs, max of $maxUrls reached -->\n";
					break;
				}
			}
		}

		// sitemap end
		$sitemap .= "<!-- $this->urlsAdded URLs added -->\n";
		$sitemap .= "<!-- timestamp: ".strftime("%Y-%m-%d %H:%M:%S")." -->\n";
		$sitemap .= "</urlset>\n";
	}
	
	function renderSitemapXml()
	{
		header('Content-Type: text/xml');
		headerDoCache();

		$sitemap = '';
		$this->createSitemapXml($sitemap);

		echo $sitemap;
	}
	
	function renderSitemapXmlGz()
	{
	    header('content-type: application/x-gzip');
	    header('Content-disposition: attachment; filename="sitemap.xml.gz"');
		headerDoCache();

		$cacheKey = "sitemap.xml.gz." . $this->absPath;
		/* if( ($temp=$this->sitemapCache->lookup($cacheKey))!='' )
		{
			$sitemap_gz = $temp;
		}
		else
		{ */
			$this->createSitemapXml($temp);
			$sitemap_gz = gzencode($temp, 9);
			$temp = ''; // free *lots* of data
			
			$this->sitemapCache->insert($cacheKey, $sitemap_gz);
		/* } */

		echo $sitemap_gz;
	}
}


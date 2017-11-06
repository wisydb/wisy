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
		$this->absPath 		= 'http:/' . '/' . $this->domain . '/';

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
				$this->renderSitemapXmlGz(); // der name ist eh nicht wichtig ... renderSitemapXmlGz
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
		
		if( $_SERVER['HTTPS']=='on' 
		 || strpos($_SERVER['HTTP_HOST'], 'sandbox')!==false || strpos($_SERVER['HTTP_HOST'], 'backup')!==false )
		{
			echo "User-agent: *\n";
			echo "Disallow: /\n";
		}
		else
		{
			// set the sitemap, 
			// dies steht in keinem zusammenhang mit User-agent, 
			// siehe http://www.sitemaps.org/protocol.php#submit_robots 
			echo "Sitemap: {$this->absPath}sitemap.xml.gz\n";
			
			// allow the adsense spider to crawl everything
			echo "User-agent: Mediapartners-Google*\n";
			echo "Disallow: /terrapin\n";
			
			// for all other spiders
			echo "User-agent: *\n";
			echo "Disallow: /advanced\n";
			echo "Disallow: /filter\n";
			echo "Disallow: /edit\n";
			echo "Disallow: /rss\n";
			echo "Disallow: /terrapin\n";
		}
	}



	/* handle sitemap.xml
	 *************************************************************************/

	function addUrl($url, $lastmod, $changefreq)
	{
		$this->urlsAdded ++;
		return "<url><loc>{$this->absPath}$url</loc><lastmod>" .strftime("%Y-%m-%d", $lastmod). "</lastmod><changefreq>$changefreq</changefreq></url>\n";
	}

	function createSitemapXml(&$sitemap /*by reference to save some MB*/)
	{
		// sitemap start
		$sitemap =  "<" . "?xml version=\"1.0\" encoding=\"UTF-8\" ?" . ">\n";
		$sitemap .= "<urlset xmlns=\"http:/" . "/www.sitemaps.org/schemas/sitemap/0.9\">\n";
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
			for( $r = 0; $r < sizeof($records['records']); $r++ )
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
				$freigeschaltet = intval($searcher->db->f8('freigeschaltet'));
				
				if(!in_array($freigeschaltet, $freigeschaltet404))
					$sitemap .= $this->addUrl('k'.$searcher->db->f8('id'), strtotime($searcher->db->f8('date_modified')), 'monthly');
				
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
		header('Content-Type: application/gzip');
		header('Content-disposition: filename=sitemap.xml.gz;');
		headerDoCache();

		$cacheKey = "sitemap.xml.gz." . $this->absPath;
		if( ($temp=$this->sitemapCache->lookup($cacheKey))!='' )
		{
			$sitemap_gz = $temp;
		}
		else
		{
			$this->createSitemapXml($temp);
			$sitemap_gz = gzencode($temp);
			$temp = ''; // free *lots* of data
			
			$this->sitemapCache->insert($cacheKey, $sitemap_gz);
		}

		echo $sitemap_gz;
	}
}


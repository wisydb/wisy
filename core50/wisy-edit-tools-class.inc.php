<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_EDIT_TOOLS_CLASS
{
	var $framework;
	
	function __construct(&$framework)
	{
		$this->framework	=& $framework;
	}
	
	// function extracts all URLs from text text
	public function getUrls($text)
	{
		$url_exclude		= ' \s<>[\]\'\"\177-\277'; // EDIT 20.01.2011: () are no longer excluded from URLs, see http://www.w3.org/Addressing/rfc1738.txt , 2.2
		$url_end_exclude	= '.,;:!?';
		$pattern			= "#(https?|ftp|nntp|news|mailto):[^$url_exclude]*[^$url_exclude$url_end_exclude]#i";
		preg_match_all($pattern, $text, $matches);
		
		return $matches[0];
	}
	
	// function checks if one of the URLs is equal to the anbieter-URL
	public function isAnbieterUrl($url_arr)
	{
		$anbieter = intval($_SESSION['loggedInAnbieterId']);
		$db			= new DB_Admin;
		$db->query("SELECT homepage FROM anbieter WHERE id=$anbieter;");
		$db->next_record();
		$homepage = $this->normalizeUrl($db->fs('homepage'));
		
		foreach( $url_arr as $url )
		{
			$url = $this->normalizeUrl($url);
			if( $url == $homepage ) {
				return true;
			}
		}
			
		return false;
	}
	
	private function normalizeUrl($url)
	{
		$url = str_replace('http://', '', $url);
		$url = preg_replace('#/$#', '', $url); // remove trailing slash
		$url = strtolower($url);
		return $url;
	}
	
	public function loadStopwords($iniKey)
	{
		$ret = array();
	
		$stopwords = $this->framework->iniRead($iniKey, 'default');
		if( $stopwords == 'default' ) {
			// use default stopwords
			$stopwords = 'Pausengetränke, Verkehr, HVV, S-Bahn, U-Bahn';
		}
		
		if( $stopwords != '0' ) {
			$stopwords = explode(',', $stopwords);
			foreach( $stopwords as $stopword ) {
				$stopword = trim($stopword);
				if( $stopword != '' ) {
					$ret[] = $stopword;
				}
			}
		}

		return $ret;
	}
	
	public function containsStopword($all_text, $stopwords)
	{
		$all_text = strtolower($all_text);
		$all_text = strtr($all_text, '.,;:!?/()', '         ');
		$all_text = " $all_text ";
		foreach( $stopwords as $stopword )
		{
			$test = ' '.strtolower($stopword).' ';
			if( strpos($all_text, $test) !== false ) {
				return $stopword;
			}
			
		}
		
		return false;
	}
};
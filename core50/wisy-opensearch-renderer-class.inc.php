<?php

/*
Implementierung von OpenSearchDescription, s.
http://www.opensearch.org/
https://developer.mozilla.org/en/Creating_OpenSearch_plugins_for_Firefox
http://msdn.microsoft.com/en-us/library/bb891764.aspx
http://bueltge.de/opensearch-suchfeld-fuer-mozilla-und-internet-explorer-mit-wordpress-anbieten/410/
*/



class WISY_OPENSEARCH_RENDERER_CLASS
{
	var $framework;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}
	
	function render()
	{
		global $wisyPortalName;
		global $wisyPortalKurzname;
	
		$absPath = 'http:/' . '/' . $_SERVER['HTTP_HOST'] . '/';

		header('Content-type: application/opensearchdescription+xml');
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<OpenSearchDescription xmlns="http:/'.'/a9.com/-/spec/opensearch/1.1/" xmlns:moz="http:/'.'/www.mozilla.org/2006/browser/search/">' . "\n";
			echo '  <ShortName>' . htmlspecialchars(utf8_encode($wisyPortalKurzname)) . '</ShortName>' . "\n";
			echo '  <Description>' . htmlspecialchars(utf8_encode($wisyPortalName)) . '</Description>' . "\n";
			echo '  <InputEncoding>UTF-8</InputEncoding>' . "\n";
			echo '  <Image height="16" width="16" type="image/x-icon">' . $absPath . $this->framework->getFaviconFile(). '</Image>' . "\n";
			
			// the URL loaded on search
			echo '  <Url type="text/html" method="get" template="' .$absPath. 'search?ie=UTF-8&amp;q={searchTerms}" />' . "\n";
			
			// JSON suggestions - working for Firefox and IE8; 
			// IE7 and other browsers seem not to use any suggestion, see the Wikipedia article http://de.wikipedia.org/wiki/OpenSearch
			echo '  <Url type="application/x-suggestions+json" method="get" template="' .$absPath. 'autosuggest?ie=UTF-8&amp;format=json&amp;q={searchTerms}" />' . "\n";
			
		echo '</OpenSearchDescription>' . "\n";
	}
};



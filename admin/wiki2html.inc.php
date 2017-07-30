<?php



/*=============================================================================
WIKI to HTML transformation
===============================================================================

file:

	wiki2html.inc.php

author:

	Bjoern Petersen

parameters:

	none.

usage:

	$ob = new WIKI2HTML_CLASS();
	$htmlTxt = $ob->run($wikiTxt);

	you can derive from WIKI2HTML_CLASS and implement your own routines for:

	pageExists()
	pageUrl()
	renderA()
	renderImg()
	renderP()
	renderPre()
	renderTable()
	renderTd()
	renderH()
	renderHr()

formatting:

	paragraph\n\n				a simple paragraph using renderP() which
								returns eg. "<p>...</p>"; a single line break
								does NOT start a paragraph and is ignored

	: indented paragraph		indented paragraphs using
	:: 2x indented paragraph	<dl><dt></dt><dd>...</dd></dl>
	: indented paragraph

    right-aligned paragraph ::  aligned paragraphs using
    :: centered paragraph ::    <p align=...>...</p>

	  preformatted text			lines starting with spaces will
	 							be preformatted using renderPre() which returns
	 							eg. "<pre>...</pre>"

	''italic''					italics using <i>...</i>
	'''bold'''					bold text using <b>...</b>
	'''''bold and italic'''''	bold and italic text usinh <b><i>...</i></b>

	http://...					these external links are automatically
	https://...					recognized; external links are converted
	ftp://...					to <a hef="http://..." target="_blank">...</a>
	nntp://...					using renderA()
	news://...
	mailto:...
	user@domain.net

	[[Link]]					an internal or external link, external links
	[[Link]]ing					are starting with "http://", "https:" etc.;
								internal links are checked using pageExists()
								and pageUrl(), rendering is done using
								renderA()

	[[Link|Text]]				a link, "Text" appears instead of "Link"

	[[Link|Image]]				an internal or external link with an image;
	[[Link|Image|Text]]			the image is rendered using renderImg(), the
								link is rendered using renderA()

	[[image.gif]]				embed an image using
	[[image.gif|Text]]			<img src="image.gif" alt="Text" title="Text" />
	[[::image.gif]]				left-aligned image
	[[image.gif::]]				right-aligned image

	[1] text					a footnote

	= Headline Level 1 =		a headline using "<h1>"..."<h6>" from renderH()
	== Headline Level 2 =
	=== Headline Level 3 =
	...

	Headline Level 1			a headline using "<h1>"
	================

	Headline Level 2			a headline using "<h2>"
	----------------

	- item 1					an unordered list using, example results in:
	-- item 1.1					<ul>
	-- item 1.2					  <li>
	- item 2					    item 1
	...							    <ul>
									  <li>item 1.1</li>
									  <li>item 1.2</li>
									</ul>
								  </li>
								  <li>item 2</li>
								</ul>...

								you can also use "*" instead of "-"

	# item 1					an ordered list using <ol><li>...</li></ol>,
	# item 2
	## item 2.1
	## item 2.2
	# item 3

	----						a horizontal line using <hr />

	<allowedTag>				the given html tag itself if allowed (see
								[[allowed html tags]] for a list)

	&allowedEntity;				the given html entitiy itself, however,
								entities are used automatically if needed.

	<nowiki>...</nowiki>		suppress wiki formatting

	[[content()]]				content of the page here (up to depth 1-6), the
	[[content(maxDepth)]]		content is created using the headline
								statements, only one content allowed per page

	[[toplinks()]]				add links to top of page after each chapter

	[[stat()]]					print statistical information; <what> is one of
	[[stat(<what>)]]			time, chapters, rawsize, formattedsize,
								rawlines, formattedlines, tags

	[[joinlines()]]				whether to join single lines or not
	[[breaklines()]]

	{fieldkey|id}				Holt Adressinformationen zu einem Anbieter
								fieldkey: Feldname in der Anbietertabelle
								id: Anbieter-ID
								wird durchaus noch verwendet, s. http://goo.gl/Zi4L0

used html tags:

	a, b, dd, dl, dt, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, ul

=============================================================================*/



//
// help functions
// ----------------------------------------------------------------------------
//



define('WIKI_MAGIC', 'WIKI17583919362810184627292048327496MAGIC');



function htmlsmartentities($str, $leave = '', $smartPunctuation = 1)
{
	global $g_transentities1;
	global $g_transentities2;
	global $g_ampentities1;
	global $g_ampentities2;
	global $g_specentities1;
	global $g_specentities2;
	global $g_quotentities;

	if( !is_array($g_transentities1) )
	{
		$g_transentities1			= array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT|ENT_HTML401, 'ISO-8859-1'));
		$g_transentities1['&nbsp;'] = '/NoN-bReAkInG-sPaCe/';
		$g_transentities1['&euro;'] = '€';
		$g_transentities2			= array_flip($g_transentities1);

		$g_ampentities1 			= $g_transentities1;
		$g_ampentities2 			= $g_transentities2;
		$g_ampentities1['&amp;']	= '&amp;';
		$g_ampentities2['&']		= '&';

		$g_specentities1 			= $g_ampentities1;
		$g_specentities2 			= $g_ampentities2;
		$g_specentities1['&lt;']	= '&lt;';
		$g_specentities2['<']		= '<';
		$g_specentities1['&gt;']	= '&gt;';
		$g_specentities2['>']		= '>';
		$g_specentities1['&quot;']	= '&quot;';
		$g_specentities2['"']		= '"';

		$g_quotentities						= array();
		$g_quotentities['(/)']				= '&#092;';		// (/)	=> \
		$g_quotentities['(C)']				= '&#169;';		// (C)	=> copyright sign
		$g_quotentities['(c)']				= '&#169;';		// (c)	=> copyright sign
		$g_quotentities['(R)']				= '&#174;';		// (R)	=> registered sign
		$g_quotentities['(r)']				= '&#174;';		// (R)	=> registered sign
		$g_quotentities['(TM)']				= '&#8482;';	// (TM)	=> trademark sign
		$g_quotentities['(tm)']				= '&#8482;';	// (TM)	=> trademark sign
		$g_quotentities['---']				= '&#8212;';	// ---	=> m-dash
		$g_quotentities['--']				= '&#8211;';	// --	=> m-dash
	}

	if( $leave == '' ) {
		$str = strtr($str, $g_transentities1);
		$str = strtr($str, $g_transentities2);
	}
	else if( $leave == '&' ) {
		$str = strtr($str, $g_ampentities1);
		$str = strtr($str, $g_ampentities2);
	}
	else if( $leave == '&<>"' ) {
		$str = strtr($str, $g_specentities1);
		$str = strtr($str, $g_specentities2);
	}
	else {
		echo '<h1>Invalid parameter in htmlsmartentities.</h1>';
		exit();
	}

	if( $smartPunctuation ) {
		return strtr($str, $g_quotentities);
	}
	else {
		return $str;
	}
}



function shyurl($url)
{
	return strtr($url,
		array
		(
			'/' => '/&shy;',
			'.' => '.&shy;',
			'+' => '+&shy;',
			'#' => '#&shy;'
		)
	);
}



function shortenurl($url, $max_len = 42)
{
	if( strlen($url) > $max_len )
		$url = substr($url, 0, $max_len-2) . '..';
	return $url;
}



//
// a system-independent text-replacer class
// ----------------------------------------------------------------------------
//



//
// you should derive your own replacer classes from TXTREPLACERRULE_CLASS,
// set $pattern and implement run()
//
class TXTREPLACERRULE_CLASS
{
	var $pattern;

	function run($matches, &$context)
	{
		return $matches;
	}
}



//
// TXTREPLACER_CLASS, $context is any data that are forwarded to the
// classes derived from TXTREPLACERRULE_CLASS. use addRule() for adding rules
// and run() to start a replacement
//
class TXTREPLACER_CLASS
{
	var $rules;
	var $restRule;
	var $replacements;
	var $mark2		= '\\';	// should be non-space characters that is valid without isohtmlentities() and is not used for markup
	var $mark2repl	= '(/)';// replacement that should be used if $mark2 is in the input string

	function __construct()
	{
		$this->rules = array();
		$this->restRule = new TXTREPLACERRULE_CLASS;
	}

	// add a rule to the text replacer, the rule must be derived from
	// TXTREPLACERRULE_CLASS
	function addRule($rule)
	{
		$this->rules[]	= $rule;
	}

	function addRestRule($rule)
	{
		$this->restRule = $rule;
	}

	// run all rules on a text string and return the result.
	// the given text must not contain the letter '\'
	function run($text, &$context)
	{
		// init context
		$this->context =& $context;

		// init replacements
		$this->replacements = array();

		// run all rules
		$cRules = sizeof($this->rules);
		for( $i = 0; $i < $cRules; $i++ )
		{
		    // ...in short, the function returns the original string, but all substrings
		    // matched by the rule have been replaced with a <mark2><number><mark2>
			// sequence.
			$this->ruleIndex = $i;
			$text = preg_replace_callback($this->rules[$i]->pattern, array(&$this, '_runSingleRuleCallback'), $text);
		}

		// Expand the twice. maybe this should happen twice
		return $this->_expandTokens($text, $context);
	}

	function _runSingleRuleCallback($matches)
	{
		$this->replacements[] = $this->rules[$this->ruleIndex]->run($matches, $this->context);
		return $this->mark2 . (sizeof($this->replacements)-1) . $this->mark2;
	}

	// Make one pass expanding tokens in the line, replacing
	// <mark2><number><mark2> with the corresponding generated and
	// saved replacement text from $this->replacements[].
	function _expandTokens($in, &$context, $runRest = 1)
	{
		$in = explode($this->mark2, $in);
		$out = '';
		while( list($i, $chunk) = each($in) ) {
			if( $i % 2 == 0 ) {
				// output unmatched substring verbatim
				if( $runRest && $chunk ) {
					$out .= $this->restRule->run($chunk, $context);
				}
				else {
					$out .= $chunk;
				}
			}
			else {
				// Replace matched substring with the rule-generated text.
				// $chunk is <number> in the above-mentioned
				// <mark2><number><mark2> sequence.
				$out .= $this->_expandTokens($this->replacements[$chunk], $context, 0);
			}
		}

		return $out;
	}
}



//
// rules for the replacer class
// See http://www.c2.com/cgi/wiki?TextFormattingRegularExpressions for
// the original (Perl) Wiki text-formatting regexps
// ----------------------------------------------------------------------------
//




class TRANSL_NOWIKI_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/<nowiki>(.*?)<\/nowiki>/i';
	}

	function run($matches, &$context)
	{
		return htmlsmartentities($matches[1], '', 0);
	}
}



class TRANSL_HTMLREMARK_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/<!--.*?-->/';
	}

	function run($matches, &$context)
	{
		return $matches[0]; // just leave the html remark "as is"
	}
}



class TRANSL_HTML_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern =
			'/<\/?('

			.	 'a|applet|area'	// [[allowed html tags]] EDIT 21.05.2013: New Tags: object, video, source
			.	'|b|big|blockquote|br'
			.	'|center|cite|code'
			.	'|dd|del|div|dl|dt'
			.	'|em|embed'
			.	'|form'
			.	'|h[1-6]|hr'
			.	'|i|img|input|ins'
			.	'|li'
			.	'|map'
			.	'|nobr|noscript'
			.	'|object|ol|option'
			.	'|p|param|pre'
			.	'|span|script|select|small|style|strike|strong|sub|sup|source'
			.	'|table|td|text|textarea|th|tr|tt'
			.	'|u|ul'
			.	'|video'

			.')(\s+.*?|\/)?>/';
	}

	function run($matches, &$context)
	{
		// normal HTML tag
		if( $matches[0] == '<br>' ) {
			return '<br />';
		}
		else {
			return htmlsmartentities($matches[0], '&<>"', 0);
		}
	}
}



class TRANSL_EMPH_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = "/('{2,7})/";
	}

	function run($matches, &$context)
	{
		switch( strlen($matches[0]) )
		{
			case 2: return $this->singleOpenClose($context, 'emph');
			case 3: return $this->singleOpenClose($context, 'strong');
			case 4: return $this->singleOpenClose($context, 'wild');
			case 5: return $this->doubleOpenClose($context, 'strong', 'emph');
			case 6: return $this->doubleOpenClose($context, 'wild', 'emph');
			case 7: return $this->doubleOpenClose($context, 'wild', 'strong');
		}
	}

	function singleOpenClose(&$context, $tag1)
	{
		if( $context->isCharTagOpen__($tag1) ) {
			return $context->closeCharTag__($tag1);
		}
		else {
			return $context->openCharTag__($tag1);
		}
	}

	function doubleOpenClose(&$context, $tag1, $tag2)
	{
		if( $context->isCharTagOpen__($tag1) && $context->isCharTagOpen__($tag2) ) {
			return $context->closeCharTag__($tag2) . $context->closeCharTag__($tag1);
		}
		else if( $context->isCharTagOpen__($tag1) ) {
			return $context->closeCharTag__($tag1) . $context->openCharTag__($tag2);
		}
		else if( $context->isCharTagOpen__($tag2) ) {
			return $context->closeCharTag__($tag2) . $context->openCharTag__($tag1);
		}
		else {
			return $context->openCharTag__($tag1) . $context->openCharTag__($tag2);
		}
	}
}



class TRANSL_EXTLINK_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$url_exclude		= ' \s<>[\]\'\"\177-\277'; // EDIT 20.01.2011: () are no longer excluded from URLs, see http://www.w3.org/Addressing/rfc1738.txt , 2.2
		$url_end_exclude	= '.,;:!?';
		$this->pattern		= "#(https?|ftp|nntp|news|mailto):[^$url_exclude]*[^$url_exclude$url_end_exclude]#";
		$this->matchPattern	= "#^(https?|ftp|nntp|news|mailto):[^$url_exclude]*[^$url_exclude$url_end_exclude]$#";
	}

	function run($matches, &$context)
	{
		$str = $matches[0];
		$this->match($str);
		return $context->renderA(shyurl(htmlsmartentities(shortenurl($str))), $matches[1], htmlsmartentities($str, '&'), htmlsmartentities($str), 1);
	}

	function match(&$str)
	{
		if( preg_match_all($this->matchPattern, $str, $matches) ) {
			return $matches[1][0]; // return the protocol
		}
		else {
			return 0;
		}
	}
}



class TRANSL_EMAILLINK_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern		= '/([-\w._+]+\@[\w.-]+\.[\w.-]+)/';
		$this->matchPattern	= '/^([-\w._+]+\@[\w.-]+\.[\w.-]+)$/';
	}

	function run($matches, &$context)
	{
		return $context->renderA(htmlsmartentities($matches[1]), "mailto", "mailto:{$matches[1]}", htmlsmartentities($matches[1]), 1);
	}

	function match($str)
	{
		return preg_match($this->matchPattern, $str);
	}
}



class TRANSL_SQUAREBRACKET_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = "/\[\[(.*?)\]\](\w*)/";
	}

	function run($matches, &$context)
	{
		// get values seperated by | into $val1 ... $val3 and $valrest
		$val1	= trim($matches[1]);
		$val2	= '';
		$val3	= '';
		$valrest= $matches[2]; // string after [[
		if( !(($p=strpos($val1, '|'))===false) ) {
			$val2 = trim(substr($val1, $p+1));
			$val1 = trim(substr($val1, 0, $p));
			if( !(($p=strpos($val2, '|'))===false) ) {
				$val3 = trim(substr($val2, $p+1));
				$val2 = trim(substr($val2, 0, $p));
			}
		}

		// some needed classes
		$imgMatch = '/^.*?\.(gif|jpg|jpeg|png)(::)?$/i';

		// check pattern
		$param = array();
		if( $val3 )
		{
			// pattern [[img|link|descr]] and [[link|img|descr]]
			if( preg_match($imgMatch, $val1) ) {
				$temp = $val1; $val1 = $val2; $val2 = $temp; // swap $val1/$val2
			}

			$val2 = $this->getImgAlign($val2, $align);
			$param['text']	  = $context->renderImg(htmlsmartentities($val2, '&'), $align, htmlsmartentities($val3.$valrest));
			$param['tooltip'] = htmlsmartentities($descr);
			$this->getLink($context, $val1, $param);
		}
		else if( $val2 )
		{
			if( preg_match($imgMatch, $val1) )
			{
				// pattern [[img|descr]]
				$val1 = $this->getImgAlign($val1, $align);
				return $context->renderImg(htmlsmartentities($val1, '&'), $align, htmlsmartentities($val2.$valrest));
			}
			else if( preg_match($imgMatch, $val2) )
			{
				// pattern [[link|img]]
				$val2 = $this->getImgAlign($val2, $align);
				$param['tooltip'] = htmlsmartentities($val1.$valrest);
				$this->getLink($context, $val1, $param);
				$param['text']	  = $context->renderImg(htmlsmartentities($val2, '&'), $align, htmlsmartentities($val1 . $valrest));
			}
			else
			{
				// pattern [[link|descr]]
				$param['text']	  = htmlsmartentities($val2 . $valrest);
				$param['tooltip'] = htmlsmartentities($val1);
				$this->getLink($context, $val1, $param);
			}
		}
		else
		{
			if( substr($val1, -1) == ')'
			 && ($p=strpos($val1, '(')) )
			{
				// a function?
				$functionName 	= strtolower(trim(substr($val1, 0, $p)));
				$functionParam	= substr($val1, $p+1, (strlen($val1)-$p)-2);

				$functionState	= 0;
				$functionHtml	= $context->pageFunction($functionName, $functionParam, $functionState);
				if( !$functionState ) {
					$functionHtml	= $context->pageFunction__($functionName, $functionParam, $functionState);
				}

				if( $functionState ) {
					if( $functionState == 2 ) $context->lineSelfPara = 1;
					return $functionHtml;
				}
			}

			if( preg_match($imgMatch, $val1) )
			{
				// pattern [[img]]
				$val1 = $this->getImgAlign($val1, $align);
				return $context->renderImg(htmlsmartentities($val1, '&'), $align, htmlsmartentities($val1.$valrest));
			}
			else
			{
				// pattern [[link]]
				$param['text']		= htmlsmartentities(shortenurl($val1 . $valrest)); // shyurl() called in getLink() below
				$param['tooltip']	= htmlsmartentities($val1);
				$this->getLink($context, $val1, $param);
			}
		}

		return $context->renderA($param['text'], $param['type'], $param['href'], $param['tooltip'], $param['pageExists']);
	}

	function getLink(&$context, $dest, &$param)
	{
		// external link
		$extLinkClass = new TRANSL_EXTLINK_CLASS;
		if( ($param['type']=$extLinkClass->match($dest)) )
		{
			$param['href']		= htmlsmartentities($dest, '&');
			$param['text']		= shyurl($param['text']);
			$param['pageExists']= 1;
			return;
		}

		// email link
		$emailLinkClass = new TRANSL_EMAILLINK_CLASS;
		if( $emailLinkClass->match($dest) )
		{
			$param['type']		= 'mailto';
			$param['href']		= 'mailto:' . htmlsmartentities($dest, '&');
			$param['pageExists']= 1;
			return;
		}

		// internal link
		$param['type']	= 'internal';

		if( substr($dest, 0, 2) == '==' )
		{

			$param['text'] = str_replace('==', '', $param['text']);
			$param['tooltip'] = str_replace('==', '', $param['tooltip']);
			$param['href'] = '';
			$param['pageExists'] = 1;
			$hash = substr($dest, 2);
		}
		else
		{
			if( preg_match('/(.*[^\s])==([^\s].*)/', $dest, $matches) ) {
				$dest = $matches[1];
				$hash = $matches[2];
				$param['text'] = str_replace('==', ' - ', $param['text']);
			}

			$param['pageExists'] = $context->pageExists($dest);
			if( !is_int($param['pageExists']) || $param['pageExists']!=1 ) {
				$param['tooltip']	= $param['pageExists'];
				$param['pageExists']= 0;
			}
			else {
				$param['tooltip'] = '';
			}

			$param['href']	= $context->pageUrl($dest, $param['pageExists']);
		}

		if( $hash ) {
			$param['href'] .= '#content'.urlencode(isohtmlentities($hash));
		}
	}

	function getImgAlign($url, &$align)
	{
		$align = '';

		if( substr($url, 0, 2)=='::' ) {
			$url = substr($url, 2);
			$align = 'left';
		}

		if( substr($url, -2)=='::' ) {
			$url = substr($url, 0, strlen($url)-2);
			$align = 'right';
		}

		return $url;
	}
}



class TRANSL_FOOTNOTEPASS1_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/(\s)\[(\d{1,3})\]/';
	}

	function run($matches, &$context)
	{
		$index = strval($matches[2]);

		$ret = " ";
		if( !$context->footnotes["footref$index"] ) {
			$ret .= "<a name=\"footref$index\"></a>";
			$context->footnotes["footref$index"] = 1;
		}

		return $ret . $context->renderA(htmlsmartentities($matches[2]), "footref", "#footnote$index", '', 1);
	}
}



class TRANSL_FOOTNOTEPASS2_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/(\A)\[(\d{1,3})\]/';
	}

	function run($matches, &$context)
	{
		$index = strval($matches[2]);

		$context->lineEndsParagraph = 1;
		$context->lineStyle = 'footnote';

		if( !$context->footnotes["footnote$index"] ) {
			$ret .= "<a name=\"footnote$index\"></a>";
			$context->footnotes["footnote$index"] = 1;
		}
		return $ret . $context->renderA(htmlsmartentities($matches[2]), 'footnote', "#footref$index", '', 1);
	}
}



class TRANSL_INDENT_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/^([:\-=\s\|\*#]+)\s*(.*)$/';
	}

	function run($matches, &$context)
	{
		// preformatted text?
		if( $matches[1]{0} == ' ' )
		{
	    	$context->linePre = 1;
	    	return htmlsmartentities(substr($matches[0], 1), '', 0); // first space should not belong to the preformatted text
		}

		// get text
		$text = htmlsmartentities(rtrim($matches[2]));

		// get level
		$level = strlen(rtrim($matches[1]));
		if( $level > 6 ) $level = 6;
		if( $level < 1 ) $level = 1;

		// get result
		switch( $matches[1]{0} )
		{
			case '=':
				if( $text )
				{
					// headline
					$context->lineHeadlineLevel = $level;
					$text = preg_replace('/\s*=+$/', '', $text);
					return $text; // headline
				}
				else if( $level >= 4 )
				{
					// box
					if( $context->boxOpen ) {
						$ret = $context->renderBox($context->boxOpen, 0);
						$context->boxOpen = 0;
						$context->lineSelfPara = 1;
						return $ret;
					}
					else {
						$context->boxOpen = $level-3;
						$context->lineSelfPara = 1;
						return $context->renderBox($context->boxOpen, 1);
					}
				}
				break;

			case '-':
			case '*':
				if( $text )
				{
					// unordered list
					$context->lineIndentLevel = $level;
					$context->lineIndentType = 'ul';
					return '<li>' . $text; // closing tag written later
				}
				else if( $matches[1]{0} == '-' && $level >= 4 )
				{
					// hr
					$context->lineHasHr = 1;
					return '';
				}
				break;

			case '#':
				if( $text )
				{
					// ordered list
					$context->lineIndentLevel = $level;
					$context->lineIndentType = 'ol';
					return '<li>' . $text; // closing tag written later
				}
				break;

			case ':':
				if( $text )
				{
					if( $level == 2 && substr($text, -2) == '::' )
					{
						// centered paragraph
						$context->lineEndsParagraph = 1;
						$context->lineAlign = 'center';
						return substr($text, 0, strlen($text)-2);
					}
					else
					{
						// indented paragraph
						$context->lineIndentLevel = $level;
						$context->lineIndentType = 'dl';
						return "<dt></dt><dd>$text"; // closing tag written later
					}
				}
				break;

			case '|':
				if( $text || $context->lineTableCells )
				{
					// table
					$text = htmlsmartentities(rtrim(substr($matches[0], 1))); // reload text
					$border = 0;
					if( substr($text, -1)=='|' ) {
						$text = substr($text, 0, strlen($text)-1);
						$border = 1;
					}
					$cells = explode('|', $text);
					$context->lineTable = 1;

					// init global table settings
					if( $context->lineTableCells == 0 ) {
						$context->lineTableCells = sizeof($cells);
						$context->lineTableBorder = $border;
					}

					// add/remove cells
					if( sizeof($cells) > $context->lineTableCells ) {
						for( $i = $context->lineTableCells; $i < sizeof($cells); $i++ ) {
							$cells[$context->lineTableCells-1] .= ' ' . $cells[$i];
						}
					}
					else while( sizeof($cells) < $context->lineTableCells ) {
						$cells[] = '&nbsp;';
					}

					// add cells from string
					$ret = '';

					$colspan = 1;
					for( $i = 0; $i < $context->lineTableCells; $i++ )
					{
						if( $cells[$i] == '' && ($i!=sizeof($cells)-1) )
						{
							// cell added later
							$colspan++;
						}
						else
						{
							$cell = trim($cells[$i]);

							// get cell style
							$align = 'left';
							if( substr($cell, -2)=='::' ) {
								$cell = trim(substr($cell, 0, strlen($cell)-2));
								$align = 'right';
								if( substr($cell, 0, 2)=='::' ) {
									$cell = trim(substr($cell, 2));
									$align = 'center';
								}
							}

							// add cell
							$ret .= $context->renderTd(trim($cell)!=''? trim($cell) : '&nbsp;', $align, $colspan);
							$colspan = 1;
						}
					}

					return $context->renderTr($ret, $context->lineTableCells);
				}
				break;
		}

		return '';
	}
}



class TRANSL_PARARIGHTALIGN_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/^(.*)::\s*$/';
	}

	function run($matches, &$context)
	{
		$context->lineEndsParagraph = 1;
		$context->lineAlign = 'right';
		return htmlsmartentities(trim($matches[1]));
	}
}



class TRANSL_ENTITIES_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
	}

	function run($text, &$context)
	{
		return htmlsmartentities($text);
	}
}

class ADDRESS_PATTERN_CLASS extends TXTREPLACERRULE_CLASS
{
	function __construct()
	{
		$this->pattern = '/\{(.*\|.*)\}/U'; // {Anbieterfeld|id} - wird durchaus noch verwendet, s. http://goo.gl/Zi4L0
	}

	function run($matches, &$context)
	{
		$arguments = explode("|",$matches[1]);
		$sql = "SELECT ";
		$sql .= addslashes($arguments[0]);
		$sql .= " as ergebnis from anbieter where id = ".intval($arguments[1]).";";
		$los = @mysql_query($sql);
		if( $los ) {
			$ausgabe = @mysql_result($los, 0, "ergebnis");
			return htmlsmartentities($ausgabe, '', 0);
		}
		else {
			return '{'.htmlsmartentities($matches[1]).'}';
		}
		

	}

}


//
// the wiki2html engine itself
// ----------------------------------------------------------------------------
//



class WIKI2HTML_CLASS
{
	//
	// private: variables
	//
	var $charTags;

	var $content;
	var $hasContent;
	var $hasToplinks;

	var $indentCode;
	var $indentLevel;

	var $lineEndsParagraph;
	var $lineHasHr;
	var $lineHeadlineLevel;
	var $lineIndentLevel;
	var $lineIndentType;
	var $linePre;
	var $lineReplacer;
	var $lineStyle;
	var $lineAlign;
	var $lineTable;
	var $lineTableBorder;
	var $lineTableCells;

	//
	// public: constructor
	//
	function __construct()
	{
		// create and init line replacer class.
		// the order of the following rules can be important, since they
		// are applied to each input line in order. note that each rule
		// hides the matched text from later rules.
		$this->lineReplacer = new TXTREPLACER_CLASS;

		// first, translate 'in text'
		$this->lineReplacer->addRule(new TRANSL_NOWIKI_CLASS);
		$this->lineReplacer->addRule(new ADDRESS_PATTERN_CLASS);
		$this->lineReplacer->addRule(new TRANSL_HTMLREMARK_CLASS);
		$this->lineReplacer->addRule(new TRANSL_HTML_CLASS);
		$this->lineReplacer->addRule(new TRANSL_EMPH_CLASS); // should be before img/link so the description text may be formatted
		$this->lineReplacer->addRule(new TRANSL_SQUAREBRACKET_CLASS);
		$this->lineReplacer->addRule(new TRANSL_EXTLINK_CLASS);
		$this->lineReplacer->addRule(new TRANSL_EMAILLINK_CLASS);
		$this->lineReplacer->addRule(new TRANSL_FOOTNOTEPASS1_CLASS);



		// translate 'paragraphs'
		$this->lineReplacer->addRule(new TRANSL_INDENT_CLASS);
		$this->lineReplacer->addRule(new TRANSL_PARARIGHTALIGN_CLASS);
		$this->lineReplacer->addRule(new TRANSL_FOOTNOTEPASS2_CLASS);

		// translate rest
		$this->lineReplacer->addRestRule(new TRANSL_ENTITIES_CLASS);
	}

	//
	// public: generate and output html for all lines.
	//
	function run($in__)
	{
		// stop time
		$profile = $this->getmicrotime__();

		// set context
		$this->content			= array();
		$this->charTags			= array();
		$this->indentCode		= array();
		$this->indentLevel		= 0;
		$this->lineTableCells	= 0;
		$this->boxOpen			= 0;
		$this->joinLines		= 0;

		// get each single line of the input
		$in = explode("\n", strtr($in__,
							array(	"\r" => "",
									$this->lineReplacer->mark2 => $this->lineReplacer->mark2repl
								 ))
					 );

		// go through all lines and collect all blocks
		$blocks = array();
		$cIn = sizeof($in);
		for( $i = 0; $i < $cIn; $i++ )
		{
			// get block from line
			$this->lineHeadlineLevel	= 0;
			$this->linePre				= 0;
			$this->lineSelfPara			= 0;
			$this->lineTable			= 0;
			$this->lineHasHr			= 0;
			$this->lineEndsParagraph	= 0;
			$this->lineStyle			= '';
			$this->lineAlign			= 'left';
			$this->lineIndentLevel		= 0;
			$block = $this->lineReplacer->run($in[$i], $this) . $this->closeAllCharTags__();

			// add block...
			if( $this->lineHasHr )
			{
				// ...horizontal ruler (no else for this case!)
				if( $blocks[sizeof($blocks)-1][0] != 'hr' ) {
					$blocks[] = array('hr');
				}
			}

			if( !trim($block) )
			{
				// ...empty line
				if( $blocks[sizeof($blocks)-1][0] == 'pre' )
				{
					if( substr($blocks[sizeof($blocks)-1][1], -2) != "\n\n") {
						$blocks[sizeof($blocks)-1][1] .= "\n";
					}
				}
				else if( $blocks[sizeof($blocks)-1][0] == 'p'
					  || $blocks[sizeof($blocks)-1][0] == 'table' )
				{
					$blocks[] = array('', ''); // add an empty block to avoid block collapsing
					$this->lineTableCells = 0;
				}
			}
			else if( $this->linePre )
			{
				// ...preformatted block
				if( $blocks[sizeof($blocks)-1][0] == 'pre' ) {
					$blocks[sizeof($blocks)-1][1] .= rtrim($block) . "\n";
				}
				else {
					$blocks[] = array('pre', rtrim($block) . "\n");
				}
			}
			else if( $this->lineTable )
			{
				// ...table block
				if( $blocks[sizeof($blocks)-1][0] == 'table' ) {
					$blocks[sizeof($blocks)-1][1] .= $block;
				}
				else {
					$blocks[] = array('table', $block, $this->lineTableBorder);
				}
			}
			else if( $this->lineHeadlineLevel )
			{
				// ...headline
				if( $this->lineHeadlineLevel < 0 ) {
					$temp = sizeof($blocks)-1;
					if( $blocks[$temp][0] == 'p' ) {
						$blocks[$temp][0] = 'h';
						$blocks[$temp][2] = $this->lineHeadlineLevel * -1;
					}
				}
				else {
					$blocks[] = array('h', $block, $this->lineHeadlineLevel);
				}
			}
			else if( $this->lineIndentLevel )
			{
				// ...indent
				$blocks[] = array('li', $block, $this->lineIndentLevel, $this->lineIndentType);
			}
			else if( $this->lineSelfPara )
			{
				// ...self-formatting paragraph
				$blocks[] = array('self', $block);
			}
			else
			{
				// ...normal paragraph
				if( !$this->lineEndsParagraph
				 && $blocks[sizeof($blocks)-1][0] == 'p' )
				{
					$blocks[sizeof($blocks)-1][1] .= ($this->joinLines? ' ' : '<br />') . trim($block);
				}
				else
				{
					$blocks[] = array('p', trim($block), $this->lineAlign, $this->lineStyle);
				}
			}
		}

		// go through all blocks and collect the output
		$out = '';
		$cBlocks = sizeof($blocks);
		for( $i = 0; $i < $cBlocks; $i++ )
		{
			switch( $blocks[$i][0] )
			{
				case 'li':
					$out .= $this->indentTo__($blocks[$i][2], $blocks[$i][3]) . $blocks[$i][1] . "\n";
					break;

				case 'p':
					$out .= $this->indentTo__(0) . $this->renderP($blocks[$i][1], $blocks[$i][2], $blocks[$i][3]) . "\n";
					break;

				case 'pre':
					$out .= $this->indentTo__(0) . $this->renderPre(rtrim($blocks[$i][1])) . "\n";
					break;

				case 'h':
					$contentCounter = sizeof($this->content)+1;
					$this->content[] = array($blocks[$i][2], $blocks[$i][1], $contentCounter);
					$out .= $this->indentTo__(0);
					if( $this->hasToplinks && $contentCounter > 1 ) {
						$out .= $this->renderP($this->renderA('^', 'toplink', '#top', '', 1), 'left', 'toplink');
					}
					$out .= '<a name="content' .urlencode($blocks[$i][1]). '"></a>' . $this->renderH($blocks[$i][1], $blocks[$i][2]) . "\n";
					break;

				case 'hr':
					$out .= $this->indentTo__(0) . $this->renderHr() . "\n";
					break;

				case 'self':
					$out .= $this->indentTo__(0) . $blocks[$i][1];
					break;

				case 'table':
					$out .= $this->indentTo__(0) . $this->renderTable($blocks[$i][1], $blocks[$i][2]);
					if( $blocks[$i+1][0] == '' && $blocks[$i+2][0] == 'table' ) {
						$out .= '<br />';
					}
					break;
			}
		}

		// close any open lists
		$out .= $this->indentTo__(0);

		// close any open box
		if( $this->boxOpen ) {
			$out .= $this->renderBox($this->boxOpen, 0);
		}

		if( $this->hasToplinks ) {
			$out .= $this->renderP($this->renderA('^', 'toplink', '#top', '', 1), 'left', 'toplink');
		}


		// create content if needed
		if( $this->hasContent )
		{
			$content = '';
			for( $i = 0; $i < sizeof($this->content); $i++ )
			{
				$currDepth = $this->content[$i][0];
				if( $this->hasContent >= $currDepth )
				{
					$content .= '<table cellpadding="0" cellspacing="0" border="0"><tr><td>' . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $currDepth-1) . '</td><td>';
						$content .= $this->renderA($this->content[$i][1], 'content', "#content".urlencode($this->content[$i][1]), '', 1);
					$content .= '</td></tr></table>';
				}
			}
			$out = str_replace(WIKI_MAGIC.'CONTENT', $content, $out);
		}

		// create statistics if needed
		if( $this->hasStat )
		{
			$temp = $this->getmicrotime__() - $profile;
			$temp = sprintf("%01.2f", $temp);
			$out = str_replace(WIKI_MAGIC.'STATTIME', $temp, $out);

			$temp = $cIn;
			$out = str_replace(WIKI_MAGIC.'STATRAWLINES', $temp, $out);

			$temp = $cBlocks;
			$out = str_replace(WIKI_MAGIC.'STATBLOCKS', $temp, $out);

			$temp = sizeof($this->content);
			$out = str_replace(WIKI_MAGIC.'STATCHAPTERS', $temp, $out);

			$temp = intval(strlen($in__)/1024); if( $temp <= 0 ) $temp = 1;
			$out = str_replace(WIKI_MAGIC.'STATRAWSIZE', $temp, $out);

			$temp = substr_count($out, '<');
			$out = str_replace(WIKI_MAGIC.'STATTAGS', $temp, $out);

			$temp = substr_count($out, "\n");
			$out = str_replace(WIKI_MAGIC.'STATFORMATTEDLINES', $temp, $out); // should be last

			$temp = intval(strlen($out)/1024); if( $temp <= 0 ) $temp = 1;
			$out = str_replace(WIKI_MAGIC.'STATFORMATTEDSIZE', $temp, $out); // should be very last
		}

		// done
		return $out;
	}

	//
	// private: paragraph formatting tag handling
	//
	function indentTo__($level, $tag = '')
	{
		$str = "";

		while( $this->indentLevel > $level ) {
			$tag_old = $this->indentCode[$this->indentLevel];
			$this->indentLevel--;
			$str .= "</$tag_old>";
		}

		while( $this->indentLevel < $level ) {
			$str .= "<$tag>";
			$this->indentLevel++;
			$this->indentCode[$this->indentLevel] = $tag;
		}

		if( $this->indentCode[$level] != $tag ) {
			$tag_old = $this->indentCode[$level];
			$str .= "</$tag_old>" . "<$tag>";
			$this->indentCode[$level] = $tag;
		}

		return $str;
	}


	//
	// private: character formatting tag handling
	//
	function isCharTagOpen__($tag)
	{
		return in_array($tag, $this->charTags);
	}

	function openCharTag__($tag)
	{
		if( !in_array($tag, $this->charTags) )
		{
			$this->charTags[] = $tag;
			return $this->renderEmph($tag, 1); // done
		}
		else
		{
			return ''; // char tag already opened
		}
	}

	function closeCharTag__($tag)
	{
		if( in_array($tag, $this->charTags) )
		{
			$ret = '';
			$openAgain = array();
			while( $currTag = array_pop($this->charTags) )
			{
				$ret .= $this->renderEmph($currTag, 0);
				if( $currTag == $tag )
				{
					for( $i = sizeof($openAgain)-1; $i >= 0 ; $i-- ) {
						$ret .= $this->openCharTag__($openAgain[$i]);
					}

					return $ret; // done
				}
				else
				{
					$openAgain[] = $currTag;
				}
			}

			return '<h1>ERROR in wiki2html.inc.php</h1>'; // should not happen
		}
		else
		{
			return ''; // char tag not open
		}
	}

	function closeAllCharTags__()
	{
		$ret = '';

		while( $currTag = array_pop($this->charTags) )
		{
			$ret .= $this->renderEmph($currTag, 0);
		}

		return $ret;
	}

	//
	// private: the page function
	//
	function getmicrotime__()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	function pageFunction__($name, $param, &$state)
	{
		switch( $name )
		{
			case 'content':
				if( !$this->hasContent ) {
					$this->hasContent = intval($param);
					if( $this->hasContent<1 || $this->hasContent>6 ) {
						$this->hasContent = 6;
					}
				}
				$state = 2;
				return WIKI_MAGIC.'CONTENT';

			case 'toplinks':
				$this->hasToplinks = 1;
				$state  = 1;
				return '';

			case 'joinlines':
				$this->joinLines = 1;
				$state  = 1;
				return '';

			case 'breaklines':
				$this->joinLines = 0;
				$state  = 1;
				return '';

			case 'iframe':
				$state = 2;
				$this->iframeCnt = intval($this->iframeCnt)+1;
				$url = trim($param);
				return	'<iframe src="'.$url.'" width="100%" height="380" name="iframe'.$this->iframeCnt.'" frameborder="1">'
					.		'<a href="'.$url.'" target="_blank">'.$url.'</a>'
					.	'</iframe>';

			case 'stat':
				$this->hasStat = 1;
				$state = 1;
				$param = " $param";

				if( strpos($param, 'rawsize') )			{ return WIKI_MAGIC.'STATRAWSIZE'; }
				if( strpos($param, 'formattedsize') )	{ return WIKI_MAGIC.'STATFORMATTEDSIZE'; }
				if( strpos($param, 'rawlines') )		{ return WIKI_MAGIC.'STATRAWLINES'; }
				if( strpos($param, 'formattedlines') )	{ return WIKI_MAGIC.'STATFORMATTEDLINES'; }
				if( strpos($param, 'tags') )			{ return WIKI_MAGIC.'STATTAGS'; }
				if( strpos($param, 'blocks') )			{ return WIKI_MAGIC.'STATBLOCKS'; }
				if( strpos($param, 'chapters') )		{ return WIKI_MAGIC.'STATCHAPTERS'; }
				if( strpos($param, 'time') )			{ return WIKI_MAGIC.'STATTIME'; }

				return '<b>'
					  .WIKI_MAGIC.'STATTIME seconds</b> needed to format <b>from '
					  .WIKI_MAGIC.'STATRAWSIZE KB</b>, '
					  .WIKI_MAGIC.'STATRAWLINES lines, '
					  .WIKI_MAGIC.'STATBLOCKS blocks and '
					  .WIKI_MAGIC.'STATCHAPTERS chapters <b>to '
					  .WIKI_MAGIC.'STATFORMATTEDSIZE KB</b>, '
					  .WIKI_MAGIC.'STATFORMATTEDLINES lines and '
					  .WIKI_MAGIC.'STATTAGS tags';
		}
	}


	//
	// public user function: check if the page with the given title exists;
	// the function should return:
	// 1 if the page exists
	// an error string if the page does not exist
	//
	function pageExists($title)
	{
		return 1;
	}

	//
	// public user function: calling a function
	// the function should return any HTML code and set $state to:
	//  1	-	the HTML code is text
	//  2	-	the HTML code is a paragraph
	//  0	-	function not handled, return value is invalid
	//
	function pageFunction($name, $param, &$state)
	{
		return $this->pageFunction__($name, $param, $state);
	}


	//
	// public user function: generate the internal URL of any page;
	// the function should return sth. like "index.php?..."
	//
	function pageUrl($title, $pageExists)
	{
		return "index.php?pleaseDefineYourOwnInternalLinkHandlerFor" . urlencode($title);
	}

	//
	// public user function: render a simple emphasis tag;
	// $emph is one of "emph", "strong" or "wild";
	// $open is one of 1 (open) or 0 (close);
	// the function should return sth. like "<b>" or "</i>"
	//
	function renderEmph($emph, $open)
	{
		switch( $emph )
		{
			case 'emph':	return $open? '<i>' : '</i>';
			case 'strong':	return $open? '<b>' : '</b>';
			case 'wild':	return $open? '<strong style="color:red; font-weight:bold; font-style:normal;">' : '</strong>';
		}

		return "<b>ERROR: renderEmph(): unknown emphasis &quot;$emph&quot;.</b>";
	}


	//
	// public user function: render a complete anchor;
	// $type is one of "content", "footnote", "footref", "toplink", "internal", or any
	// TCP/IP protocol as "http", "https", "mailto", "ftp" etc.;
	// the function should return sth. like "<a href=...>...</a>"
	//
	function renderA($html, $type, $href, $tooltip, $pageExists)
	{
		$a = "<a href=\"$href\"";
				if( $tooltip ) {
					$a .= " title=\"$tooltip\"";
				}

				if( $type == 'http' || $type == 'https' ) {
					$a .= ' target="_blank"';
				}
		$a .= ">";

		if( $type == 'footnote' || $type == 'footref' ) {
			return "{$a}[{$html}]</a>";
		}
		else if( $pageExists ) {
			return "$a$html</a>";
		}
		else {
			return "$html$a???</a>";
		}
	}

	//
	// public user function: render an image;
	// $align is one of "", "left" or "right"
	// the function should return sth. like "<img src=... />"
	//
	function renderImg($src, $align, $tooltip)
	{
		$style = '';
		if( $align == 'left' ) {
			$style = ' align="left" hspace="4"';
		}
		else if( $align == 'right' ) {
			$style = ' align="right" hspace="4"';
		}
		return "<img src=\"$src\"$style border=\"0\" alt=\"$tooltip\" title=\"$tooltip\" />";
	}

	//
	// public user function: render a paragraph;
	// $style is one of "", "left", "right", "center", "footnote", "toplink";
	// the function should return sth. like "<p>...</p>"
	//
	function renderP($html, $align, $style)
	{
		if( $style == 'footnote' ) {
			return "<p><small>$html</small></p>";
		}
		else switch( $align ) {
			case 'right':	return "<p align=\"right\">$html</p>";
			case 'center':	return "<p align=\"center\">$html</p>";
			default:		return "<p>$html</p>";
		}
	}

	//
	// public user function: render preformatted paragraph;
	// the function should return sth. like "<pre>...</pre>"
	//
	function renderPre($html)
	{
		return "<pre>$html</pre>";
	}

	//
	// public user function: render a table;
	// $border is 0 or 1;
	// the function should return sth. like "<table ...>...</table>"
	//
	function renderTable($html, $border)
	{
		$cellpadding = $border? 2 : 1;
		return "<table cellpadding=\"$cellpadding\" cellspacing=\"0\" border=\"$border\">$html</table>";
	}

	//
	// public user function: render a table row;
	// $numCol is the number of logical columns inkl. column spanning;
	// the function should return sth. like "<tr>...</tr>"
	//
	function renderTr($html, $numCol)
	{
		return "<tr>$html</tr>";
	}

	//
	// public user function: render a table data cell;
	// $align is one of "left", "right", "center";
	// also regard $colspan if > 1;
	// the function should return sth. like "<td>...</td>"
	//
	function renderTd($html, $align, $colspan)
	{
		$td = "<td valign=\"top\" align=\"$align\"";
			if( $colspan > 1 ) $td .= " colspan=\"$colspan\"";
			if( strlen($html)<=20 && strpos($html, ' ')===false ) $td .= ' nowrap="nowrap"';
		$td .= '>';

		return "$td$html</td>";
	}

	//
	// public user function: render a headline;
	// the function should return sth. like "<h1>";
	//
	function renderH($html, $level)
	{
		return "<h$level>$html</h$level>";
	}

	//
	// public user function: render a horizontal ruler;
	// the function should return sth. like "<hr />";
	//
	function renderHr()
	{
		return '<hr />';
	}

	//
	// public user function: box functions
	//
	function renderBox($level, $open)
	{
		switch( $level )
		{
			case 1:		return $open? '<table style="margin-bottom:0.8em;" cellpadding="4" cellspacing="0" border="0" width="100%"><tr><td style="border:1px solid #000000;">' : '</td></tr></table>';
			default:	return $open? '<table style="margin-bottom:0.8em;" cellpadding="4" cellspacing="0" border="0" width="100%"><tr><td bgcolor="#FEFFA1">' : '</td></tr></table>';
		}
	}
}




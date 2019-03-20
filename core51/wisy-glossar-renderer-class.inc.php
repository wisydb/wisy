<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_GLOSSAR_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;
	var $db;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->db = new DB_Admin;
	}

	function getWikipediaUrl($artikel)
	{
		if( substr($artikel, 0, 4) == 'b2b:' )
		{
			$artikel = substr($artikel, 4);
			return 'https:/'.'/b2b.kursportal.info/index.php?title='.urlencode($artikel).'';
		}
		else
		{
			return 'https:/'.'/de.m.wikipedia.org/w/index.php?title='.urlencode($artikel).''; // 28.10.2013 weiterleitung auf die Mobilversion - auch auf dem Desktop, s. Mail von Jürgen vom 26.10.2013
		}
	}
	
	function render()
	{
	    $glossar_id = intval($_GET['id']);
	    
	    $glossar = $this->getGlossareintrag($glossar_id); // #landing
	    
	    // Wenn es keine Erklärung, aber eine Wikipedia-Seite gibt -> Weiterleitung auf die entspr. Wikipedia-Seite
	    if( $glossar['erklaerung'] == '' && $glossar['wikipedia'] != '' ) // #landing
	    {
	        header('Location: ' . $this->getWikipediaUrl($glossar['wikipedia'])); // #landing
	        exit();
	    }
	    
	    // #richtext
	    $richtext = (intval(trim($this->framework->iniRead('meta.richtext'))) === 1);
	    
	    // prologue
	    headerDoCache();
	    echo $this->framework->getPrologue(array(
	        'title'		=>	$glossar['begriff'],
	        'beschreibung' => $glossar['erklaerung'],	// #socialmedia, #richtext
	        'canonical'	=>	$this->framework->getUrl('g', array('id'=>$glossar_id)),
	        'bodyClass'	=>	'wisyp_glossar',
	        'richtext_pagetype' => 'itemscope itemtype="http://schema.org/QAPage"' // #richtext
	    ));
	    
	    echo $this->framework->getSearchField();
	    
	    echo $this->renderGlossareintrag($glossar_id, $glossar); // #landing
	    
	    echo $this->framework->getEpilogue(); // #landing
	}
	
	function getGlossareintrag($glossar_id) {  // #landing
	    $this->db->query("SELECT begriff, erklaerung, wikipedia, date_created, date_modified FROM glossar WHERE status=1 AND id=".$glossar_id);
	    
	    if( !$this->db->next_record() )
	        $this->framework->error404();
	        
	        $glossareintrag = array(
	            'begriff' => $this->db->f8('begriff'),
	            'erklaerung' => $this->db->f8('erklaerung'),
	            'wikipedia' => $this->db->f8('wikipedia'),
	            'date_created' => $this->db->f8('date_created'),
	            'date_modified' => $this->db->f8('date_modified'));
	        
	        $this->db->free();
	        return $glossareintrag;
	}
	
	function renderGlossareintrag($glossar_id, $glossar) // #landing
	{
	    $classes  = $this->framework->getAllowFeedbackClass();
	    $classes .= ($classes? ' ' : '') . 'wisy_glossar';
	    
	    // #richtext
	    
	    echo '<div id="wisy_resultarea" class="' .$classes. '" itemprop="mainEntity" itemscope itemtype="http://schema.org/Question">';
	    echo '<meta itemprop="name" content="'.$begriff.'">';	// the question title = Glossar title
	    echo '<meta itemprop="text" content="'.$begriff.'">';	// the actual question (not automatically retrievable)
	    echo '<meta itemprop="answerCount" content="1">';
	    
	    $db2 = new DB_Admin;
	    $db2->query("SELECT settings, glossar.user_grp FROM user_grp, glossar WHERE user_grp.id=glossar.user_grp AND glossar.id=$glossar_id");
	    if($db2->next_record() )
	        $settings = $db2->f8('settings');
	        $settings = explodeSettings($settings);
	        echo '<meta itemprop="author" content="'.$settings['glossar.autor'].'">';
	        echo '<meta itemprop="dateCreated" content="'.$date_modified.'">';
	        
	        // render head
	        echo '<a id="top"></a>'; // make [[toplinks()]] work
	        echo '<p class="noprint">';
	        echo '<a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
	        echo $this->framework->getLinkList('help.link', ' &middot; ');
	        echo '</p>';
	        
	        // #richtext
	        $about = ($richtext) ? 'itemprop="about"' : '';
	        $author = ($richtext) ? '<meta itemprop="author" content="Redaktion Landeskursportal Rheinland-Pfalz - Ministerium für Bildung, Wissenschaft, Weiterbildung & Kultur RLP">' : '';
	        echo '<h1 '.$about.' class="wisyr_glossartitel">' . htmlspecialchars($this->framework->encode_windows_chars($glossar['begriff'])) . '</h1>'; // #landing
	        flush();
	        
	        // render entry
	        echo '<section class="wisyr_glossar_infos clearfix">';
	        
	        // #richtext
	        if($richtext) {
	            echo '<div itemprop="acceptedAnswer" itemscope itemtype="http://schema.org/Answer">
											<meta itemprop="dateCreated" content="'.$date_modified.'">
											<meta itemprop="upvoteCount" content="1">
											<meta itemprop="url" content="'.$_SERVER['SCRIPT_URI'].'">
											<meta itemprop="author" content="'.$settings['glossar.autor'].'">
											<div itemprop="text">';
	        }
	        if( $glossar['erklaerung'] != '' )
	        {
	            $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework, array('selfGlossarId'=>$glossar_id));
	            echo $wiki2html->run($this->framework->encode_windows_chars($glossar['erklaerung']));
	        }
	        
	        if($richtext) {
	            echo '</div></div>';
	        } // #richtext
	        
	        /* // #richtext
	         if($richtext) {
	         echo '<div itemprop="suggestedAnswer" itemscope itemtype="http://schema.org/Answer">
	         <meta itemprop="dateCreated" content="'.$date_modified.'">
	         <meta itemprop="upvoteCount" content="1">
	         <meta itemprop="url" content="'.$_SERVER['SCRIPT_URI'].'">
	         <meta itemprop="author" content="'.$settings['glossar.autor'].'">
	         <div itemprop="text" content="'.strip_tags($wiki2html->run($this->framework->encode_windows_chars($erklaerung))).'">';
	         echo '</div></div>';
	         } // #richtext */
	        
	        // render wikipedia link
	        
	        if( $glossar['wikipedia'] != '' )
	        {
	            $isB2b = (substr($glossar['wikipedia'], 0, 4) == 'b2b:')? true : false;
	            
	            echo '<p>';
	            echo 'Weitere Informationen zu diesem Thema finden Sie <a href="'.htmlspecialchars($this->getWikipediaUrl($glossar['wikipedia'])).'" target="_blank">';
	            echo ' ' . ($isB2b? 'im Weiterbildungs-WIKI' : 'in der Wikipedia');
	            echo '</a>';
	            echo '</p>';
	        }
	        
	        echo '</section><!-- /.wisyr_glossar_infos -->';
	        
	        echo '<footer class="wisy_glossar_footer">';
	        echo '<div class="wisyr_glossar_meta">';
	        echo 'Information erstellt am ' . $this->framework->formatDatum($glossar['date_created']);
	        echo ', zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($glossar['date_modified']);
	        $copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
	        $copyrightClass->renderCopyright($this->db, 'glossar', $glossar_id);
	        echo '</div><!-- /.wisyr_glossar_meta -->';
	        echo '</footer><!-- /.wisy_glossar_footer -->';
	        
	        
	        echo "\n</div><!-- /#wisy_resultarea -->";
	        
	}
};

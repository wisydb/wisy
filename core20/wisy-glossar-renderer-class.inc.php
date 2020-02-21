<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_GLOSSAR_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

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
	        return 'http:/'.'/b2b.wisy.info/index.php?title='.urlencode($artikel).'';
	    }
	    else
	    {
	        return 'http:/'.'/de.m.wikipedia.org/w/index.php?title='.urlencode($artikel).''; // 28.10.2013 weiterleitung auf die Mobilversion - auch auf dem Desktop, s. Mail von J¸rgen vom 26.10.2013
	    }
	}
	
	function getGlossareintrag($glossar_id) {
	    // SELECT um user_grp erweitert (wegen 404-Pr¸fung)
	    $this->db->query("SELECT begriff, erklaerung, wikipedia, date_created, date_modified, user_grp FROM glossar WHERE status=1 AND id=".$glossar_id);
	    if( !$this->db->next_record() )
	        $this->framework->error404();
	        
	       $glossareintrag = array('begriff' => cs8($this->db->fs('begriff')),
	         'erklaerung' => cs8($this->db->fs('erklaerung')),
	         'wikipedia' => cs8($this->db->fs('wikipedia')),
	         'date_created' => cs8($this->db->fs('date_created')),
	         'date_modified' => cs8($this->db->fs('date_modified')),
	         'user_grp' => cs8($this->db->f('user_grp')));
	        
	        $this->db->free();
	        return $glossareintrag;
	}
	
	// Die Funktion pr¸ft ob der Glossarbeitrag einem Stichwort zugeordnet ist
	function getGlossarArt($glossar_id)
	{
	    $ret = 0;
	    $this->db->query("SELECT id FROM stichwoerter WHERE glossar = '".$glossar_id."'");
	    if( $this->db->ResultNumRows){
	        $ret = 1;
	    }
	    return $ret;
	}
	
	function render()
	{
	    // wird in der index.php gesetzt
	    global $wisyPortalUserGrp;
	    $glossar_id = intval($_GET['id']);
	    $glossarshowall = $this->framework->iniRead('glossarshowall', '');
	    $glossarshowgrps = array_map("trim", explode(",", $this->framework->iniRead('glossarshowgrps', '')));
	    $glossarshowids = array_map("trim", explode(",", $this->framework->iniRead('glossarshowids', '')));
	    $glossar = $this->getGlossareintrag($glossar_id);
	    
	    // 404 wenn Usergruppe Portal != Usergruppe Glossar und Gloasser nicht an einem Stichwort und Portalparameter glossarshowall != 1
	    if(trim($this->framework->iniRead('disable.glossar', false))
	        || ($glossarshowall != 1 && $glossar['user_grp'] != $wisyPortalUserGrp && !in_array($glossar_id, $glossarshowids) && !in_array($glossar['user_grp'], $glossarshowgrps) && $this->getGlossarArt($glossar_id) == 0)) {
	            $this->framework->error404();
	        }
	        
	        
	    $db = new DB_Admin;
	
		$db->query("SELECT begriff, erklaerung, wikipedia FROM glossar WHERE status=1 AND id=$glossar_id");
		if( !$db->next_record() )
			$this->framework->error404();

		$begriff = $db->fs('begriff');
		$erklaerung = $db->fs('erklaerung');
		$wikipedia = $db->fs('wikipedia');

		// Wenn es keine Erkl�rung, aber eine Wikipedia-Seite gibt -> Weiterleitung auf die entspr. Wikipedia-Seite
		if( $erklaerung == '' && $wikipedia != '' )
		{
			header('Location: ' . $this->getWikipediaUrl($wikipedia));
			exit();
		}

		// prologue
		headerDoCache();
		echo $this->framework->getPrologue(array(	
													'title'		=>	$begriff, 
													'canonical'	=>	$this->framework->getUrl('g', array('id'=>$glossar_id)),
													'bodyClass'	=>	'wisyp_glossar',
										   ));

		echo $this->framework->getSearchField();

		$classes  = $this->framework->getAllowFeedbackClass();
		$classes .= ($classes? ' ' : '') . 'wisy_glossar';
		echo '<div id="wisy_resultarea" class="' .$classes. '">';

			// render head
			echo '<a id="top"></a>'; // make [[toplinks()]] work
			echo '<p class="noprint">';
				echo '<a href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
				echo $this->framework->getLinkList('help.link', ' &middot; ');
			echo '</p>';
			echo '<h1>' . isohtmlspecialchars($begriff) . '</h1>';
			flush();
	
			// render entry
			
			if( $erklaerung != '' )
			{
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework, array('selfGlossarId'=>$glossar_id));
				echo $wiki2html->run($erklaerung);
			}
			
			// render wikipedia link
	
			if( $wikipedia != '' )
			{
				$isB2b = (substr($wikipedia, 0, 4) == 'b2b:')? true : false;
				
				echo '<p>';
					echo 'Weitere Informationen zu diesem Thema finden Sie <a href="'.isohtmlspecialchars($this->getWikipediaUrl($wikipedia)).'" target="_blank">';
						echo ' ' . ($isB2b? 'im Weiterbildungs-WIKI' : 'in der Wikipedia');
					echo '</a>';
				echo '</p>';
			}
	

		echo '</div>';

		// copyright
		
		$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
		$copyrightClass->renderCopyright($db, 'glossar', $glossar_id);


		// done		
		echo $this->framework->getEpilogue();
	}
};

<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_GLOSSAR_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
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
			return 'http:/'.'/de.m.wikipedia.org/w/index.php?title='.urlencode($artikel).''; // 28.10.2013 weiterleitung auf die Mobilversion - auch auf dem Desktop, s. Mail von Jürgen vom 26.10.2013
		}
	}
	
	function render()
	{
		$glossar_id = intval($_GET['id']);
	
		$db = new DB_Admin;
	
		$db->query("SELECT begriff, erklaerung, wikipedia, date_created, date_modified FROM glossar WHERE id=$glossar_id");
		if( !$db->next_record() )
			$this->framework->error404();

		$begriff 		= $db->f8('begriff');
		$erklaerung 	= $db->f8('erklaerung');
		$wikipedia 		= $db->f8('wikipedia');
		$date_created	= $db->f8('date_created');
		$date_modified	= $db->f8('date_modified');

		// Wenn es keine Erklärung, aber eine Wikipedia-Seite gibt -> Weiterleitung auf die entspr. Wikipedia-Seite
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
				echo '<a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
				echo $this->framework->getLinkList('help.link', ' &middot; ');
			echo '</p>';
			echo '<h1 class="wisyr_glossartitel">' . htmlspecialchars($this->framework->encode_windows_chars($begriff)) . '</h1>';
			flush();
	
			// render entry
			echo '<section class="wisyr_glossar_infos clearfix">';
			
				if( $erklaerung != '' )
				{
					$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework, array('selfGlossarId'=>$glossar_id));
					echo $wiki2html->run($this->framework->encode_windows_chars($erklaerung));
				}
			
				// render wikipedia link
	
				if( $wikipedia != '' )
				{
					$isB2b = (substr($wikipedia, 0, 4) == 'b2b:')? true : false;
				
					echo '<p>';
						echo 'Weitere Informationen zu diesem Thema finden Sie <a href="'.htmlspecialchars($this->getWikipediaUrl($wikipedia)).'" target="_blank">';
							echo ' ' . ($isB2b? 'im Weiterbildungs-WIKI' : 'in der Wikipedia');
						echo '</a>';
					echo '</p>';
				}
				
			echo '</section><!-- /.wisyr_glossar_infos -->';

			echo '<footer class="wisy_glossar_footer">';
				echo '<div class="wisyr_glossar_meta">';
					echo 'Information erstellt am ' . $this->framework->formatDatum($date_created);
					echo ', zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($date_modified);
					$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
					$copyrightClass->renderCopyright($db, 'glossar', $glossar_id);
				echo '</div><!-- /.wisyr_glossar_meta -->';
			echo '</footer><!-- /.wisy_glossar_footer -->';


		echo "\n</div><!-- /#wisy_resultarea -->";
		
		echo $this->framework->getEpilogue();
	}
};

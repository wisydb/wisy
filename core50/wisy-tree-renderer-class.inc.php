<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_TREE_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
	}

	function processLevel($like)
	{
		$ret = '';
	
		// create query
		$sql = "SELECT kuerzel_sorted, thema FROM themen WHERE kuerzel_sorted LIKE '$like' ORDER BY kuerzel_sorted;";
		
		// anything no this level?
		$db = new DB_Admin;
		$db->query($sql); 
		if( $db->next_record() ) 
		{
			// loop through all items of this level
			$cnt = 0;
			$db->query($sql);
			while( $db->next_record() )
			{
				$kuerzel_sorted = $db->f8('kuerzel_sorted');
				$thema = $db->f8('thema');
				
				$title = htmlspecialchars($thema);
				
				$q = g_sync_removeSpecialChars($thema);
				
				$link = '<a href="search?q='.urlencode($q).'">' .$title. '</a>';
				
				// render ...
				if( strlen($like) == 10 )
				{
					$ret .= '<h1>' . $link . '</h1>';
					$ret .= '<div style="margin-top: 1em; margin-bottom: 2em;">';
						$ret .= $this->processLevel($kuerzel_sorted.'__________');
					$ret .= '</div>';
					
					// column break ...
					if( $cnt == 5 ) $ret .= '</td><td valign="top" width="50%">';
				}
				else if( strlen($like) == 20 )
				{
					$ret .= $cnt? ' &middot; ' : '';
					$ret .= $link;

					$children = $this->processLevel($kuerzel_sorted.'__________');
					if( $children != '' )
					{
						$ret .= ' ... <div style="padding-left: 2em;">';
							$ret .= '<small> ... ' . $children . '</small>';
						$ret .= '</div>';
						$cnt = -1;
					}
				}
				else
				{
					$ret .= $cnt? ' &middot; ' : '';
					$ret .= $link;
				}
				
				// process children
				$cnt ++;
			}
		}
		
		return $ret;
	}


	function render()
	{
		// prologue
		echo $this->framework->getPrologue(array(
			'title'=>'Themen', 
			'canonical'=>$this->framework->getUrl('tree'),
			'bodyClass'=>'wisyp_search'
		));
		echo $this->framework->getSearchField();

		// tree out
		echo '<table cellpadding="0" cellspacing="0" width="100%"><tr><td valign="top" width="50%">';
		echo $this->processLevel('__________');
		echo '</td></tr></table>';

		// done		
		echo $this->framework->getEpilogue();
	}
};

<?php



/*
diese übersicht ist ohne Auftrag erstellt und 
kann ohne weiteres wieder verschwinden, wenn sie nicht mehr benötigt wird (bp)
*/


require_once('functions.inc.php');


class stichwoerter_tree
{
	var $flat;
	var $warnings;

	function markDescendents($id)
	{
	    for( $i = sizeof((array) $this->flat[$id]['unterbegriffe']) - 1; $i >= 0; $i-- )
		{
			$unterbegriff_id = $this->flat[$id]['unterbegriffe'][$i];
			if( !$this->flat[$unterbegriff_id][ 'ist_descendent' ] )
			{
				$this->flat[$unterbegriff_id][ 'ist_descendent' ] = true;
				$this->markDescendents($unterbegriff_id);
			}
		}
	}
	
	function collect()
	{
		$this->flat = array();
		$this->errors = array();
		$this->warnings = array();
	
		// get a flat array of all stichwoerter
		$db = new DB_Admin;
		$db->query("SELECT id, stichwort, eigenschaften FROM stichwoerter ORDER BY stichwort_sorted;");
		while( $db->next_record() )
		{
			$this->flat[ $db->f('id') ] = array(
				'stichwort'		=>	$db->fs('stichwort'),
				'eigenschaften'	=>	$db->f('eigenschaften'),
			);
		}
		
		$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis2;");
		while( $db->next_record() )
		{
			$primary_id = $db->f('primary_id');
			$attr_id = $db->f('attr_id');
			$this->flat[ $primary_id ]['unterbegriffe'][] = $attr_id;
			$this->flat[ $attr_id ]['ist_unterbegriff'] ++;
		}
	
		$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis;");
		while( $db->next_record() )
		{
			$primary_id = $db->f('primary_id');
			$attr_id = $db->f('attr_id');
			$this->flat[ $attr_id ]['synonyme'][] = $primary_id;
			$this->flat[ $primary_id ]['ist_synonym']  = 1;
		}
	
		// mark root-level stichwoerter
		reset($this->flat);
		foreach($this->flat as $id => $vars)
		{
			// check for some errors
			if( $vars['ist_synonym'] && !($vars['eigenschaften']&32+64) ) { 
				$this->warnings[] = $this->renderStichwortLink($id) . " wird als Synonym verwendet, hat aber keinen entsprechenden Typ."; 
			}
			if( !$vars['ist_synonym'] && ($vars['eigenschaften']&32+64) ) { 
				//$this->warnings[] = $this->renderStichwortLink($id) . " hat den Typ &quot;Synonym&quot;, wird aber nirgends als Synonym referenziert."; 
				$this->flat[ $id ]['ist_synonym'] = 2;
			}
			
			if( !$vars['ist_synonym'] && sizeof((array) $vars['unterbegriffe']) )
			{
				$this->markDescendents($id);
			}
		}
	
		
		// done collecting
		 //echo '<pre>'; print_r($this->flat);
	}
	
	function renderStichwortLink($id, $astyle='')
	{
		$stichwortHtml = $this->flat[$id]['stichwort'];
		
		if( $this->flat[$id]['eigenschaften']&32 ) $astyle .= " color: #bbb; ";
		
		if( $astyle != '' ) $astyle = " style=\"$astyle\" ";
		
		return "<a href=\"edit.php?table=stichwoerter&amp;id=$id\" onclick=\"return popdown(this);\"$astyle>$stichwortHtml</a>";
	}
	
	function renderStichwort($id, $stack)
	{
		global $site;
		
		$astyle = ''; 
		$sstyle = ' font-style:italic;  '; // font-size: 80%;
		if( sizeof((array) $this->flat[$id]['unterbegriffe']) ) { $astyle = ' font-weight: bold; '; }
		if( sizeof((array) $stack) >= 2 ) { $astyle .= ' font-size: 80%; '; $sstyle .= '  '; }
		
		$site->skin->rowStart();
		$site->skin->cellStart('style="width: 50%; padding-left: '.(sizeof((array) $stack)*20).'px;"');
		echo $this->renderStichwortLink($id, $astyle);
		$site->skin->cellEnd();
		
		$site->skin->cellStart('style="width: 50%;"');
		for( $i = 0; $i < sizeof((array) $this->flat[$id]['synonyme']); $i++ )
				{
					$synonym_id = $this->flat[$id]['synonyme'][$i];
					echo $i? ', ' : '';
					echo $this->renderStichwortLink($synonym_id, $sstyle);
				}
			$site->skin->cellEnd();
			
		$site->skin->rowEnd();
		
		if( sizeof((array) $stack) > 25 )
			{ $this->errors[] = "Zu tiefe Verschachtelung bei ".$this->renderStichwortLink($id); return; }

		$stack[] = $id;
		for( $i = 0; $i < sizeof((array) $this->flat[$id]['unterbegriffe']); $i++ )
		{
			$unterbegriff_id = $this->flat[$id]['unterbegriffe'][$i];
			$unterbegriff_html = isohtmlspecialchars($this->flat[$unterbegriff_id]['stichwort']);
			
			for( $s = 0; $s < sizeof((array) $stack)-2; $s++ )
			{
				if( $stack[$s] == $unterbegriff_id )
				{
					{ $this->errors[] = "Rekursive Verschachtelung bei ".$this->renderStichwortLink($unterbegriff_id); return; }
				}
			}
			
			$this->renderStichwort($unterbegriff_id, $stack);
		}
	}
	
	function render()
	{
		global $site;
		
		$site->pageStart(array('popfit'=>1));
		$site->skin->tableStart();
			$site->skin->headStart();
				$site->skin->cellStart();
					echo 'Deskriptor';
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo 'Synonyme';
				$site->skin->cellEnd();
			$site->skin->headEnd();
		
			reset($this->flat);
			foreach($this->flat as $id => $vars)
			{
				if( !$vars['ist_descendent'] && !$vars['ist_synonym'] )
				{
					$this->renderStichwort($id, array());
				}
			}
		$site->skin->tableEnd();
		
		if( sizeof($this->errors) )
		{
			$site->skin->msgStart('e');
			for( $i = 0; $i < sizeof((array) $this->errors); $i++ )
					echo '<b>Fehler:</b> ' .  $this->errors[$i] . '<br />';
			$site->skin->msgEnd();
		}
		if( sizeof($this->warnings) )
		{
			$site->skin->msgStart('w');
			for( $i = 0; $i < sizeof((array) $this->warnings); $i++ )
					echo '<b>Warnung:</b> ' . $this->warnings[$i] . '<br />';
			$site->skin->msgEnd();
		}
		
		$site->pageEnd();
	}
};


function main()
{
	$tree = new stichwoerter_tree();
	$tree->collect();
	$tree->render();
}

main();

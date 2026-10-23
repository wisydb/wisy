<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/*****************************************************************************
 * WISY_RATGEBERSEITEN_RENDERER_CLASS
 * Alphabetische Liste aller Ratgeberseiten (Glossareintraege), die dieses
 * Portal anzeigt, z.B. zur redaktionellen Durchsicht.
 *****************************************************************************

 Aufruf: /ratgeberseiten

 Aufgenommen werden dieselben Eintraege, die WISY_GLOSSAR_RENDERER_CLASS::render()
 ohne 404 ausliefert: status=1 und

 - Benutzergruppe des Portals oder
 - Benutzergruppe aus glossarshowgrps bzw. ID aus glossarshowids oder
 - Eintrag ist einem Stichwort zugeordnet (dann in allen Portalen sichtbar).

 Mit glossarshowall=1 zaehlen alle Eintraege mit status=1, mit disable.glossar
 gibt es keine Ratgeberseiten und damit 404. Aendert sich die Regel im
 Glossar-Renderer, muss sie hier ebenfalls angepasst werden.

 Eintraege ohne Erklaerung werden mitgelistet und gekennzeichnet: mit
 Wikipedia-Artikel leitet die Seite dorthin weiter, ohne ist sie leer.

 */

class WISY_RATGEBERSEITEN_RENDERER_CLASS
{
	var $framework;
	var $db;

	function __construct(&$framework, $param)
	{
		$this->framework =& $framework;
		$this->db = new DB_Admin;
	}

	function render()
	{
		if( trim($this->framework->iniRead('disable.glossar', false)) )
			$this->framework->error404();

		$eintraege = $this->getEintraege();

		// Buchstabengruppen A-Z, Titel mit Ziffer oder Sonderzeichen am Anfang vorab unter "0-9"
		$gruppen = array();
		foreach( $eintraege as $eintrag )
		{
			$buchstabe = preg_match('/^[a-z]/', $eintrag['key'])? strtoupper($eintrag['key'][0]) : '0-9';
			$gruppen[$buchstabe][] = $eintrag;
		}

		headerDoCache(0); // Aenderungen der Redaktion sofort sichtbar
		header('X-Robots-Tag: noindex, follow');

		echo $this->framework->getPrologue(array(
			'title'		=>	'Alle Ratgeberseiten',
			'bodyClass'	=>	'wisyp_glossar wisyp_ratgeberseiten',
		));

		echo '<div id="wisy_resultarea" class="wisy_landingpage">';
		echo '<h1 class="wisyr_landingpage_list_title">Alle Ratgeberseiten</h1>';

		if( sizeof($eintraege) )
		{
			echo '<p>' . number_format(sizeof($eintraege), 0, ',', '.') . ' Seiten, alphabetisch sortiert</p>';

			$sprungmarken = array();
			foreach( array_keys($gruppen) as $buchstabe )
				$sprungmarken[] = '<a href="#ratgeber-' . strtolower($buchstabe) . '">' . $buchstabe . '</a>';
			echo '<p class="wisyr_ratgeberseiten_abc">' . implode(' &middot; ', $sprungmarken) . '</p>';

			echo '<section class="wisyr_landingpage_list wisyr_landingpage_listratgeberseiten">';
			foreach( $gruppen as $buchstabe => $liste )
			{
				echo '<h2 id="ratgeber-' . strtolower($buchstabe) . '">' . $buchstabe . '</h2>';
				echo '<ul>';
				foreach( $liste as $eintrag )
					$this->renderEintrag($eintrag);
				echo '</ul>';
			}
			echo '</section>';
		}
		else
		{
			echo '<p>Keine Ratgeberseiten gefunden.</p>';
		}

		echo '</div><!-- /#wisy_resultarea -->';
		echo $this->framework->getEpilogue();
	}

	function renderEintrag($eintrag)
	{
		$hinweis = 'g' . $eintrag['id'];
		if( $eintrag['ohne_text'] && $eintrag['wikipedia'] != '' )
			$hinweis .= substr($eintrag['wikipedia'], 0, 4) == 'b2b:'? ', Weiterleitung zum Weiterbildungs-WIKI' : ', Weiterleitung zur Wikipedia';
		else if( $eintrag['ohne_text'] )
			$hinweis .= ', ohne Inhalt';

		echo '<li><a href="' . htmlspecialchars($this->framework->getUrl('g', array('id'=>$eintrag['id']))) . '">'
			. htmlspecialchars($eintrag['titel']) . '</a> <small>(' . $hinweis . ')</small></li>';
	}

	function getEintraege()
	{
		global $wisyPortalUserGrp;

		$where = 'status=1';
		if( $this->framework->iniRead('glossarshowall', '') != 1 )
		{
			$grps = $this->intList($this->framework->iniRead('glossarshowgrps', ''));
			$grps[] = intval($wisyPortalUserGrp);
			$ids = $this->intList($this->framework->iniRead('glossarshowids', ''));

			$oder = array('user_grp IN(' . implode(',', $grps) . ')', 'id IN(SELECT glossar FROM stichwoerter)');
			if( sizeof($ids) )
				$oder[] = 'id IN(' . implode(',', $ids) . ')';
			$where .= ' AND (' . implode(' OR ', $oder) . ')';
		}

		$eintraege = array();
		$this->db->query("SELECT id, begriff, wikipedia, erklaerung='' AS ohne_text FROM glossar WHERE $where");
		while( $this->db->next_record() )
		{
			$titel = $this->framework->encode_windows_chars($this->db->fcs8('begriff'));
			$eintraege[] = array(
				'id'		=> intval($this->db->fcs8('id')),
				'titel'		=> $titel,
				'key'		=> $this->sortKey($titel),
				'wikipedia'	=> $this->db->fcs8('wikipedia'),
				'ohne_text'	=> intval($this->db->fcs8('ohne_text')),
			);
		}
		$this->db->free();

		usort($eintraege, function($a, $b) {
			$cmp = strcmp($a['key'], $b['key']);
			return $cmp? $cmp : $a['id'] - $b['id'];
		});

		return $eintraege;
	}

	// Sortierschluessel aus dem Titel: Kleinbuchstaben, Umlaute wie Grundbuchstaben, nur a-z und 0-9
	// (begriff_sorted ist dafuer nicht verlaesslich, z.B. "Überuns").
	// Die Titel kommen aus der DB als ISO-8859-1, fuer den Schluessel wird deshalb nach UTF-8 gewandelt.
	function sortKey($titel)
	{
		if( !mb_check_encoding($titel, 'UTF-8') )
			$titel = mb_convert_encoding($titel, 'UTF-8', 'ISO-8859-1');
		$key = mb_strtolower($titel, 'UTF-8');
		$key = strtr($key, array('ä'=>'a', 'ö'=>'o', 'ü'=>'u', 'ß'=>'ss', 'é'=>'e', 'è'=>'e', 'à'=>'a', 'á'=>'a'));
		return preg_replace('/[^a-z0-9]+/', '', $key);
	}

	function intList($str)
	{
		$ret = array();
		foreach( explode(',', $str) as $val )
		{
			if( ($val = intval(trim($val))) > 0 )
				$ret[] = $val;
		}
		return $ret;
	}
}

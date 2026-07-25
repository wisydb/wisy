<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/*******************************************************************************
WISY-Thesaurus: Gesamtdarstellung aller Stichwoerter und Themen

Aufrufe (jeweils zzgl. &pwd=..., s. render()):
  /thesaurus                          - interaktive Visualisierung (SVG, Radialbaum);
                                        die Daten sind direkt in die Seite eingebettet
  /thesaurus?format=html              - kompletter Thesaurus als statische HTML-Liste
                                        (angelehnt an admin/config/index_plugin_stichwoerter_1,
                                        jedoch ohne Verlinkung ins Redaktionssystem)
  /thesaurus?show=themen              - nur der Themenbaum als interaktive Visualisierung
  /thesaurus?show=themen&format=html  - nur der Themenbaum als statische HTML-Liste

Encoding: Die HTML-Ansichten geben die DB-Bytes unveraendert aus (wie der Rest
des Frontends; je nach Host latin1 oder UTF-8) - daher isohtmlspecialchars()
statt htmlspecialchars(), letzteres liefert unter PHP<8 bei latin1-Bytes einen
Leerstring. Nur die eingebetteten Visualisierungs-Daten werden nach UTF-8
normalisiert und von json_encode() als \uXXXX escapet (Charset-unabhaengig).

Datenbasis:
  stichwoerter            - Stichwort, Typ (eigenschaften), Thema, Scope-Note
  themen                  - Themenbaum ueber kuerzel_sorted (10 Zeichen je Ebene)
  stichwoerter_verweis2   - primary_id = Oberbegriff,  attr_id = Unterbegriff
  stichwoerter_verweis    - primary_id = Synonym,      attr_id = Deskriptor
*******************************************************************************/

class WISY_THESAURUS_RENDERER_CLASS
{
	var $framework;

	// dargestellte Stichwort-Typen: nur der oeffentlich sichtbare Thesaurus
	// (eigenschaften ist je Datensatz genau einer dieser Werte).
	// Bewusst nicht dargestellt: Verstecktes Synonym (32; erscheint oeffentlich
	// nirgends, vgl. $dontdisplay im Tagsuggestor), Veranstaltungsort (128),
	// Volltext Titel/Beschreibung (256/512), Verwaltungsstichwort (2048),
	// Thema (4096), "Schlagwort nicht verwenden" (8192), Anbieterstichwort (16384).
	// Synonyme (64) sind keine eigenen Knoten; sie erscheinen lediglich in der
	// HTML-Ansicht als Zusatzinfo beim jeweiligen Deskriptor.
	var $typeNames = array(
		0		=> 'Sachstichwort',
		1		=> 'Abschluss',
		2		=> 'F&ouml;rderungsart',
		4		=> 'Qualit&auml;tszertifikat',
		8		=> 'Zielgruppe',
		16		=> 'Abschlussart',
		1024	=> 'Sonstiges Merkmal',
		32768	=> 'Unterrichtsart',
		65536	=> 'Zertifikat',
	);

	var $stichwoerter;		// id => array( name, type, thema, scope )
	var $themen;			// id => array( name, kuerzel, parent, scope )
	var $oberbegriffe;		// array of array( oberbegriff_id, unterbegriff_id )
	var $synStichwoerter;	// id => array( name, scope ) - nur Typ 64, nur fuer die HTML-Ansicht
	var $synonyme;			// array of array( synonym_id, deskriptor_id )
	var $themenCounts = null;	// id => Anzahl Stichwoerter, nur in der Themen-Ansicht gesetzt

	function __construct(&$framework)
	{
		$this->framework =& $framework;
	}

	/**************************************************************************
	 Daten laden
	 **************************************************************************/

	function typeName($type)
	{
		return isset($this->typeNames[$type]) ? $this->typeNames[$type] : 'Typ '.$type;
	}

	// robust nach UTF-8: die latin1-Spalten enthalten historisch gemischte
	// Kodierungen; schon ein einzelner echter latin1-Umlaut liesse
	// json_encode() komplett scheitern (Rueckgabe false = leere Antwort)
	function toUtf8($s)
	{
		$s = strval($s);
		return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
	}

	// interne Verweise zwischen den Ansichten; das Passwort (s. render())
	// muss dabei stets mitgegeben werden
	function selfUrl($params = array())
	{
		$params['pwd'] = strval($this->framework->getParam('pwd', ''));
		$q = array();
		foreach( $params as $key => $value )
			if( $value !== '' )
				$q[] = $key.'='.urlencode($value);
		return 'thesaurus' . ( sizeof($q) ? '?'.implode('&amp;', $q) : '' );
	}

	function loadThemen()
	{
		$db = new DB_Admin;

		// Themen inkl. Hierarchie; die Ebene ergibt sich aus kuerzel_sorted
		// (10 Zeichen je Ebene), der Elternknoten aus dem 10-Zeichen-Praefix
		$this->themen = array();
		$byKuerzelSorted = array();
		$db->query("SELECT id, thema, kuerzel, kuerzel_sorted, scope_note FROM themen ORDER BY kuerzel_sorted;");
		while( $db->next_record() )
		{
			$id = intval($db->f('id'));
			$ks = $db->fs('kuerzel_sorted');
			$this->themen[$id] = array(
				'name'		=> $db->fcs8('thema'),
				'kuerzel'	=> $db->fcs8('kuerzel'),
				'parent'	=> 0,
				'scope'		=> trim((string) $db->fcs8('scope_note')),
			);
			$byKuerzelSorted[$ks] = $id;
			if( strlen($ks) > 10 )
			{
				$parentKs = substr($ks, 0, strlen($ks)-10);
				if( isset($byKuerzelSorted[$parentKs]) )
					$this->themen[$id]['parent'] = $byKuerzelSorted[$parentKs];
			}
		}
	}

	// Anzahl direkt zugeordneter Stichwoerter (dargestellte Typen) je Thema
	function loadThemenCounts()
	{
		$db = new DB_Admin;
		$typesSql = implode(',', array_keys($this->typeNames));
		$counts = array();
		$db->query("SELECT thema, COUNT(*) AS cnt FROM stichwoerter WHERE eigenschaften IN ($typesSql) GROUP BY thema;");
		while( $db->next_record() )
			$counts[ intval($db->f('thema')) ] = intval($db->f('cnt'));
		return $counts;
	}

	function loadData($withSynonymen = false)
	{
		$db = new DB_Admin;

		$this->loadThemen();

		// Stichwoerter der dargestellten Typen
		$this->stichwoerter = array();
		$typesSql = implode(',', array_keys($this->typeNames));
		$db->query("SELECT id, stichwort, eigenschaften, thema, scope_note FROM stichwoerter WHERE eigenschaften IN ($typesSql) ORDER BY stichwort_sorted;");
		while( $db->next_record() )
		{
			$thema = intval($db->f('thema'));
			$this->stichwoerter[ intval($db->f('id')) ] = array(
				'name'	=> $db->fcs8('stichwort'),
				'type'	=> intval($db->f('eigenschaften')),
				'thema'	=> isset($this->themen[$thema]) ? $thema : 0,
				'scope'	=> trim((string) $db->fcs8('scope_note')),
			);
		}

		// Ober-/Unterbegriffe; Verweise auf nicht dargestellte Typen entfallen
		$this->oberbegriffe = array();
		$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis2 ORDER BY primary_id, structure_pos;");
		while( $db->next_record() )
		{
			$p = intval($db->f('primary_id'));
			$a = intval($db->f('attr_id'));
			if( isset($this->stichwoerter[$p]) && isset($this->stichwoerter[$a]) && $p != $a )
				$this->oberbegriffe[] = array($p, $a);
		}

		// Synonym -> Deskriptor, nur fuer die HTML-Ansicht (Zusatzinfo).
		// Nur normale Synonyme (64); versteckte Synonyme (32) erscheinen
		// oeffentlich grundsaetzlich nicht.
		$this->synStichwoerter = array();
		$this->synonyme = array();
		if( $withSynonymen )
		{
			$db->query("SELECT id, stichwort, scope_note FROM stichwoerter WHERE eigenschaften=64 ORDER BY stichwort_sorted;");
			while( $db->next_record() )
			{
				$this->synStichwoerter[ intval($db->f('id')) ] = array(
					'name'	=> $db->fcs8('stichwort'),
					'scope'	=> trim((string) $db->fcs8('scope_note')),
				);
			}

			$db->query("SELECT primary_id, attr_id FROM stichwoerter_verweis ORDER BY attr_id, structure_pos;");
			while( $db->next_record() )
			{
				$p = intval($db->f('primary_id'));
				$a = intval($db->f('attr_id'));
				if( isset($this->synStichwoerter[$p]) && isset($this->stichwoerter[$a]) )
					$this->synonyme[] = array($p, $a);
			}
		}
	}

	/**************************************************************************
	 Daten fuer die Visualisierung
	 (werden direkt in die Seite eingebettet, es gibt keinen JSON-Endpunkt;
	 bewusst ohne Scope-Notes/Notizen - nur Begriffe und Beziehungen)
	 **************************************************************************/

	// json_encode() escapet non-ASCII als \uXXXX und "/" als "\/" - die
	// Einbettung in <script> ist damit unabhaengig vom Seiten-Charset sicher
	function inlineJson($payload)
	{
		return json_encode($payload,
			defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0);
	}

	function vizPayload()
	{
		$this->loadData();

		$themen = array();
		foreach( $this->themen as $id => $t )
			$themen[] = array($id, $this->toUtf8($t['name']), $this->toUtf8($t['kuerzel']), $t['parent']);

		$stichwoerter = array();
		foreach( $this->stichwoerter as $id => $s )
			$stichwoerter[] = array($id, $this->toUtf8($s['name']), $s['type'], $s['thema']);

		$typeNames = array();
		foreach( $this->typeNames as $type => $name )
			$typeNames[$type] = html_entity_decode($name, ENT_QUOTES, 'UTF-8');

		return array(
			'typen'			=> $typeNames,
			'themen'		=> $themen,
			'stichwoerter'	=> $stichwoerter,
			'oberbegriffe'	=> $this->oberbegriffe,
		);
	}

	/**************************************************************************
	 Themenbaum (getrennt von den Stichwoertern)
	 **************************************************************************/

	function themenVizPayload()
	{
		$this->loadThemen();
		$counts = $this->loadThemenCounts();

		$themen = array();
		foreach( $this->themen as $id => $t )
			$themen[] = array($id, $this->toUtf8($t['name']), $this->toUtf8($t['kuerzel']), $t['parent'],
				isset($counts[$id]) ? $counts[$id] : 0);

		return array(
			'themen' => $themen,
		);
	}

	function renderThemenViz()
	{
		$protocol  = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$canonical = parse_url( $this->framework->getUrl('thesaurus') , PHP_URL_PATH);

		echo $this->framework->getPrologue(array(
			'title'		=> 'Themenbaum',
			'canonical'	=> $protocol."://".$_SERVER['SERVER_NAME'].$canonical,
			'bodyClass'	=> 'wisyp_thesaurus',
		));
		echo $this->framework->getSearchField();

		$jsFile = 'wisy-thesaurus.js';
		global $wisyCore;
		$jsVer = @filectime($wisyCore.'/'.$jsFile);

		echo '<h1>Themenbaum</h1>' . "\n";
		echo '<p>Alle Themen samt Hierarchie. '
			.'Zoomen mit Mausrad oder Tasten, Verschieben mit der Maus; Details erscheinen beim &Uuml;berfahren eines Themas. '
			.'<a href="'.$this->selfUrl(array('show'=>'themen', 'format'=>'html')).'">Zur HTML-Gesamtansicht</a> &middot; '
			.'<a href="'.$this->selfUrl().'">Zum Stichwort-Thesaurus</a></p>' . "\n";
		echo '<div id="wisy_thesaurus" style="width:100%;"></div>' . "\n";
		echo '<script src="'.$jsFile.'?ver='.date("Y-m-d_h-i-s", $jsVer).'"></script>' . "\n";
		echo '<script>wisyThesaurusInit(document.getElementById("wisy_thesaurus"), {mode:"themen", data:'.$this->inlineJson($this->themenVizPayload()).'});</script>' . "\n";

		echo $this->framework->getEpilogue();
	}

	function renderThemenHtml()
	{
		$this->loadThemen();
		$this->themenCounts = $this->loadThemenCounts();

		$protocol  = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$canonical = parse_url( $this->framework->getUrl('thesaurus') , PHP_URL_PATH);

		echo $this->framework->getPrologue(array(
			'title'		=> 'Themen (HTML-Gesamtansicht)',
			'canonical'	=> $protocol."://".$_SERVER['SERVER_NAME'].$canonical,
			'bodyClass'	=> 'wisyp_thesaurus',
		));
		echo $this->framework->getSearchField();

		echo '<h1>Themen &ndash; HTML-Gesamtansicht</h1>' . "\n";
		echo '<p>Der komplette Themenbaum; in Klammern die Anzahl direkt zugeordneter Stichw&ouml;rter. '
			.'Scope-Notes erscheinen, wo vorhanden, als Tooltip. '
			.'<a href="'.$this->selfUrl(array('show'=>'themen')).'">Zur interaktiven Visualisierung</a> &middot; '
			.'<a href="'.$this->selfUrl(array('format'=>'html')).'">Zum Stichwort-Thesaurus</a></p>' . "\n";

		$out = '';
		foreach( $this->themen as $id => $t )
			if( $t['parent'] == 0 )
				$this->renderHtmlThema($id, 0, $out);
		echo '<ul>'.$out.'</ul>' . "\n";

		echo $this->framework->getEpilogue();
	}

	/**************************************************************************
	 Interaktive Visualisierung
	 **************************************************************************/

	function renderViz()
	{
		$protocol  = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$canonical = parse_url( $this->framework->getUrl('thesaurus') , PHP_URL_PATH);

		echo $this->framework->getPrologue(array(
			'title'		=> 'Thesaurus',
			'canonical'	=> $protocol."://".$_SERVER['SERVER_NAME'].$canonical,
			'bodyClass'	=> 'wisyp_thesaurus',
		));
		echo $this->framework->getSearchField();

		$jsFile = 'wisy-thesaurus.js';
		global $wisyCore;
		$jsVer = @filectime($wisyCore.'/'.$jsFile);

		echo '<h1>Thesaurus &ndash; Gesamtdarstellung</h1>' . "\n";
		echo '<p>Alle Stichw&ouml;rter und Themen samt Hierarchie (Ober-/Unterbegriffe). '
			.'Zoomen mit Mausrad oder Tasten, Verschieben mit der Maus; Details erscheinen beim &Uuml;berfahren eines Begriffs. '
			.'<a href="'.$this->selfUrl(array('format'=>'html')).'">Zur HTML-Gesamtansicht</a> &middot; '
			.'<a href="'.$this->selfUrl(array('show'=>'themen')).'">Nur Themenbaum</a></p>' . "\n";
		echo '<div id="wisy_thesaurus" style="width:100%;"></div>' . "\n";
		echo '<script src="'.$jsFile.'?ver='.date("Y-m-d_h-i-s", $jsVer).'"></script>' . "\n";
		echo '<script>wisyThesaurusInit(document.getElementById("wisy_thesaurus"), {data:'.$this->inlineJson($this->vizPayload()).'});</script>' . "\n";

		echo $this->framework->getEpilogue();
	}

	/**************************************************************************
	 Statische HTML-Gesamtansicht
	 (Baumlogik wie admin/config/index_plugin_stichwoerter_1, ohne Redaktionslinks)
	 **************************************************************************/

	var $flat;			// Arbeitskopie: id => stichwort-array + beziehungen
	var $errors;

	function searchLink($name, $html, $title = '', $style = '')
	{
		$q = urlencode(g_sync_removeSpecialChars($name));
		$attr  = $title != '' ? ' title="'.isohtmlspecialchars($title).'"' : '';
		$attr .= $style != '' ? ' style="'.$style.'"' : '';
		return '<a href="search?q='.$q.'"'.$attr.'>'.$html.'</a>';
	}

	function typeChipHtml($type)
	{
		return '<span class="wisythes-chip wisythes-t'.$type.'" title="'.$this->typeName($type).'"></span>';
	}

	function renderHtmlStichwort($id, $level, &$out)
	{
		$this->flat[$id]['rendered'] = true;
		$vars = $this->flat[$id];
		$hatUnterbegriffe = isset($vars['unterbegriffe']) && sizeof((array) $vars['unterbegriffe']);

		$nameHtml = isohtmlspecialchars($vars['name']);
		if( $hatUnterbegriffe ) $nameHtml = '<b>'.$nameHtml.'</b>';

		$out .= '<tr data-level="'.($level+1).'">';
		$out .= '<td style="padding-left:'.($level*20).'px;">';
		$out .= $hatUnterbegriffe ? '<span class="wisythes-toggle" title="Ein-/Ausklappen">&#9662;</span> ' : '<span class="wisythes-toggle-placeholder"></span> ';
		$out .= $this->typeChipHtml($vars['type']) . ' ';
		$out .= $this->searchLink($vars['name'], $nameHtml, $vars['scope']);
		$out .= '</td><td>';
		if( isset($vars['synonyme']) )
		{
			$synHtml = array();
			foreach( $vars['synonyme'] as $synId )
			{
				$syn = $this->synStichwoerter[$synId];
				$synHtml[] = $this->searchLink($syn['name'], isohtmlspecialchars($syn['name']), $syn['scope'], 'font-style:italic; color:#777;');
			}
			$out .= implode(', ', $synHtml);
		}
		$out .= '</td></tr>' . "\n";

		if( $level > 25 )
			{ $this->errors[] = 'Zu tiefe Verschachtelung bei &quot;'.isohtmlspecialchars($vars['name']).'&quot;'; return; }

		if( $hatUnterbegriffe )
		{
			foreach( $vars['unterbegriffe'] as $childId )
			{
				if( isset($this->flat[$childId]['rendering']) && $this->flat[$childId]['rendering'] )
					{ $this->errors[] = 'Rekursive Verschachtelung bei &quot;'.isohtmlspecialchars($this->flat[$childId]['name']).'&quot;'; continue; }
				$this->flat[$id]['rendering'] = true;
				$this->renderHtmlStichwort($childId, $level+1, $out);
				$this->flat[$id]['rendering'] = false;
			}
		}
	}

	function renderHtmlThema($id, $level, &$out)
	{
		$t = $this->themen[$id];
		$out .= '<li>'.isohtmlspecialchars($t['kuerzel']).' ';
		$out .= $this->searchLink($t['name'], isohtmlspecialchars($t['name']), $t['scope']);
		if( is_array($this->themenCounts) )
			$out .= ' <span style="color:#777;">('.intval(isset($this->themenCounts[$id]) ? $this->themenCounts[$id] : 0).')</span>';
		$children = '';
		foreach( $this->themen as $childId => $child )
			if( $child['parent'] == $id )
				$this->renderHtmlThema($childId, $level+1, $children);
		if( $children != '' )
			$out .= '<ul>'.$children.'</ul>';
		$out .= '</li>' . "\n";
	}

	function renderHtml()
	{
		$this->loadData(true);

		// Beziehungen in die Arbeitskopie uebernehmen
		$this->flat = $this->stichwoerter;
		$this->errors = array();
		foreach( $this->oberbegriffe as $pair )
		{
			$this->flat[ $pair[0] ]['unterbegriffe'][] = $pair[1];
			$this->flat[ $pair[1] ]['ist_unterbegriff'] = 1;
		}
		foreach( $this->synonyme as $pair )
			$this->flat[ $pair[1] ]['synonyme'][] = $pair[0];

		$protocol  = $this->framework->iniRead('portal.https', '') ? "https" : "http";
		$canonical = parse_url( $this->framework->getUrl('thesaurus', array('format'=>'html')) , PHP_URL_PATH);

		echo $this->framework->getPrologue(array(
			'title'		=> 'Thesaurus (HTML-Gesamtansicht)',
			'canonical'	=> $protocol."://".$_SERVER['SERVER_NAME'].$canonical,
			'bodyClass'	=> 'wisyp_thesaurus',
		));
		echo $this->framework->getSearchField();

		// kleine Chip-Palette analog zur Visualisierung
		echo '<style>
			.wisythes-chip { display:inline-block; width:.7em; height:.7em; border-radius:50%; vertical-align:baseline; }
			.wisythes-t0     { background:#3572b0; } .wisythes-t1     { background:#2e9940; }
			.wisythes-t2     { background:#0e9aa7; } .wisythes-t4     { background:#8656c4; }
			.wisythes-t8     { background:#e07b28; } .wisythes-t16    { background:#1d6e35; }
			.wisythes-t1024  { background:#a9743c; } .wisythes-t32768 { background:#d4569a; }
			.wisythes-t65536 { background:#5b5ec7; }
			.wisythes-toggle { cursor:pointer; color:#888; display:inline-block; width:1em; }
			.wisythes-toggle-placeholder { display:inline-block; width:1em; }
			#wisythes-table td { vertical-align:top; padding:1px 6px; }
			#wisythes-table tr[data-level="1"] > td { padding-top:.6em; }
		</style>' . "\n";

		echo '<h1>Thesaurus &ndash; HTML-Gesamtansicht</h1>' . "\n";
		echo '<p>Alle Stichw&ouml;rter mit Ober-/Unterbegriffen (einger&uuml;ckt); Synonyme erscheinen kursiv als Zusatzinfo beim jeweiligen Deskriptor. '
			.'Scope-Notes erscheinen, wo vorhanden, als Tooltip. '
			.'<a href="'.$this->selfUrl().'">Zur interaktiven Visualisierung</a> &middot; '
			.'<a href="'.$this->selfUrl(array('show'=>'themen', 'format'=>'html')).'">Nur Themenbaum</a></p>' . "\n";

		// 1. Legende (Themen haben ihre eigene Ansicht, s. renderThemenHtml())
		$counts = array();
		foreach( $this->stichwoerter as $s ) {
			if( !isset($counts[$s['type']]) ) $counts[$s['type']] = 0;
			$counts[$s['type']]++;
		}
		echo '<p>';
		$legend = array();
		foreach( $this->typeNames as $type => $name )
			$legend[] = $this->typeChipHtml($type).' '.$name.' ('.intval(isset($counts[$type]) ? $counts[$type] : 0).')';
		echo implode(' &middot; ', $legend);
		echo '</p>' . "\n";

		// 2. Stichwortbaum: oberste Ebene = kein Unterbegriff
		$out = '';
		foreach( $this->flat as $id => $vars )
		{
			if( isset($vars['ist_unterbegriff']) )
				continue;
			$this->renderHtmlStichwort($id, 0, $out);
		}
		echo '<table id="wisythes-table" cellpadding="0" cellspacing="0" width="100%">' . "\n";
		echo '<tr><th align="left">Deskriptor</th><th align="left">Synonyme</th></tr>' . "\n";
		echo $out;
		echo '</table>' . "\n";

		// 2b. Auffangnetz fuer Begriffe, die wegen zyklischer Verweise von
		// keiner Wurzel erreichbar sind - eine Gesamtansicht soll alles zeigen
		$out = '';
		foreach( $this->flat as $id => $vars )
		{
			if( isset($vars['rendered']) )
				continue;
			$this->renderHtmlStichwort($id, 0, $out);
		}
		if( $out != '' )
		{
			echo '<h3>Zyklisch verkn&uuml;pfte Begriffe (bitte redaktionell pr&uuml;fen)</h3>' . "\n";
			echo '<table cellpadding="0" cellspacing="0" width="100%">'.$out.'</table>' . "\n";
		}

		if( sizeof($this->errors) )
		{
			echo '<p><b>Hinweise:</b><br>'.implode('<br>', array_unique($this->errors)).'</p>' . "\n";
		}

		// Ein-/Ausklappen der Unterbegriffe
		echo '<script>
		(function(){
			var table = document.getElementById("wisythes-table");
			if(!table) return;
			table.addEventListener("click", function(ev){
				var toggle = ev.target.closest(".wisythes-toggle");
				if(!toggle) return;
				var row = toggle.closest("tr"), level = parseInt(row.getAttribute("data-level"), 10);
				var collapse = toggle.textContent != "▸"; // aktuell aufgeklappt?
				toggle.textContent = collapse ? "▸" : "▾";
				var next = row.nextElementSibling;
				while( next && parseInt(next.getAttribute("data-level"), 10) > level )
				{
					next.style.display = collapse ? "none" : "";
					if( !collapse ) { // beim Aufklappen: eingeklappte Unteraeste zuruecksetzen
						var t = next.querySelector(".wisythes-toggle");
						if( t ) t.textContent = "▾";
					}
					next = next.nextElementSibling;
				}
			});
		})();
		</script>' . "\n";

		echo $this->framework->getEpilogue();
	}

	/**************************************************************************
	 main()
	 **************************************************************************/

	function render()
	{
		// Zugangsbeschraenkung: Die Ansicht erfordert die Portaleinstellung
		// thesaurus.frontend.pwd; deren Wert muss per &pwd=... uebergeben werden.
		// Ohne Einstellung ist die Ansicht generell nicht erreichbar (kein
		// passwortloser Betrieb); bei Misserfolg 404 mit Hinweis.
		$pwd = trim((string) $this->framework->iniRead('thesaurus.frontend.pwd', ''));
		if( $pwd == '' || strval($this->framework->getParam('pwd', '')) !== $pwd )
		{
			$this->framework->error404('', 'Hinweis: F&uuml;r diese Ansicht wird ein Passwort ben&ouml;tigt.');
			return;
		}

		$showThemen = ( $this->framework->getParam('show', '') == 'themen' );
		switch( $this->framework->getParam('format', '') )
		{
			case 'html':	$showThemen ? $this->renderThemenHtml() : $this->renderHtml();	break;
			default:		$showThemen ? $this->renderThemenViz()  : $this->renderViz();	break;
		}
	}
};

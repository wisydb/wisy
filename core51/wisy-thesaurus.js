/*******************************************************************************
WISY-Thesaurus: interaktive Gesamtdarstellung aller Stichwoerter und Themen

Radialer Baum als SVG, ohne externe Bibliotheken:
- Hierarchie: Themen (aus themen.kuerzel_sorted) und Ober-/Unterbegriffe
  (stichwoerter_verweis2); dargestellt wird nur der oeffentlich sichtbare
  Thesaurus (keine Synonyme, keine Verwaltungsstichwoerter usw. - die
  Typauswahl trifft der Server, s. wisy-thesaurus-renderer-class.inc.php)
- Mehrfach-Oberbegriffe als gestrichelte Querverweise
- Maus: ziehen = verschieben, Rad = zoomen, ueberfahren = Details

Wird von /thesaurus eingebunden; die Daten bettet der Renderer direkt in die
Seite ein und uebergibt sie ueber die Init-Optionen (siehe
wisy-thesaurus-renderer-class.inc.php):

  wisyThesaurusInit(el, {data: {...}})                  - Stichwort-Thesaurus
  wisyThesaurusInit(el, {mode:'themen', data: {...}})   - nur Themenbaum
*******************************************************************************/

'use strict';

function wisyThesaurusInit(container, initOpts)
{
	var NS = 'http://www.w3.org/2000/svg';
	var themenOnly = !!(initOpts && initOpts.mode == 'themen');

	var TYPE_COLORS = {
		0:     '#3572b0',	// Sachstichwort
		1:     '#2e9940',	// Abschluss
		2:     '#0e9aa7',	// Foerderungsart
		4:     '#8656c4',	// Qualitaetszertifikat
		8:     '#e07b28',	// Zielgruppe
		16:    '#1d6e35',	// Abschlussart
		1024:  '#a9743c',	// Sonstiges Merkmal
		32768: '#d4569a',	// Unterrichtsart
		65536: '#5b5ec7'	// Zertifikat
	};
	var THEMA_COLOR = '#333333';
	var LEAF_SPACING = 12;		// Bogenlaenge je Blatt in px (Weltkoordinaten)

	var data = null;

	// Nachschlagestrukturen (einmalig aus den JSON-Daten aufgebaut)
	var sw = {};			// id -> {name,type,thema,scope}
	var themen = {};		// id -> {name,kuerzel,parent,scope}
	var parentsSW = {};		// unterbegriff-id -> [oberbegriff-ids]
	var childrenSW = {};	// oberbegriff-id -> [unterbegriff-ids]

	// Baum der aktuellen Darstellung (abhaengig von den Schaltern)
	var nodes = {};			// key -> {key,label,kind,type,scope,swId,children:[],parent,angle,radius,x,y,leaves,depth}
	var extraEdges = [];	// Querverweise (weitere Oberbegriffe): {from,to}
	var leafCount = 0, maxDepth = 0, R = 0;

	// Ansichtszustand
	var view = { x: 0, y: 0, k: 1 }, fitK = 1;
	var W = Math.max(700, container.clientWidth || 700);
	var H = Math.max(500, window.innerHeight - container.getBoundingClientRect().top - 60);

	var opts = { showCross: true, focusType: null };

	/* ------------------------------------------------------------------ UI */

	container.innerHTML = '';
	container.className += ' wisythes';

	var style = document.createElement('style');
	style.textContent =
		'.wisythes { position:relative; border:1px solid #ccc; border-radius:4px; background:#fff; }' +
		'.wisythes svg { display:block; cursor:grab; user-select:none; -webkit-user-select:none; }' +
		'.wisythes svg.dragging { cursor:grabbing; }' +
		'.wisythes-bar { display:flex; flex-wrap:wrap; gap:.6em 1.2em; align-items:center; padding:.5em .8em; border-bottom:1px solid #ddd; font-size:13px; background:#fafafa; }' +
		'.wisythes-bar label { white-space:nowrap; cursor:pointer; }' +
		'.wisythes-bar input[type=search] { padding:.25em .5em; min-width:16em; }' +
		'.wisythes-bar button { padding:.15em .6em; cursor:pointer; }' +
		'.wisythes-legend { display:flex; flex-wrap:wrap; gap:.2em .9em; padding:.4em .8em; font-size:12px; border-bottom:1px solid #ddd; }' +
		'.wisythes-legend span { cursor:pointer; white-space:nowrap; }' +
		'.wisythes-legend span.off { opacity:.35; }' +
		'.wisythes-dot { display:inline-block; width:.7em; height:.7em; border-radius:50%; margin-right:.25em; }' +
		'.wisythes-tip { position:absolute; z-index:20; max-width:26em; background:#222; color:#eee; padding:.6em .8em; border-radius:5px; font-size:12px; line-height:1.45; pointer-events:none; display:none; }' +
		'.wisythes-tip h4 { margin:0 0 .3em 0; font-size:13px; color:#fff; }' +
		'.wisythes-hits { position:absolute; z-index:21; background:#fff; border:1px solid #bbb; border-radius:4px; max-height:20em; overflow:auto; box-shadow:0 3px 10px rgba(0,0,0,.2); font-size:13px; display:none; }' +
		'.wisythes-hits div { padding:.3em .7em; cursor:pointer; white-space:nowrap; }' +
		'.wisythes-hits div:hover { background:#eef; }' +
		'.wisythes text { font-family:sans-serif; fill:#333; paint-order:stroke; stroke:#fff; stroke-width:2.5px; }' +
		'.wisythes text.lbl-top { font-weight:bold; }' +
		'.wisythes .wtz-0 text.lbl-mid, .wisythes .wtz-0 text.lbl-leaf { display:none; }' +
		'.wisythes .wtz-1 text.lbl-leaf { display:none; }' +
		'.wisythes .ovz-0 text.lbl-ov2 { display:none; }';
	container.appendChild(style);

	var bar = document.createElement('div');
	bar.className = 'wisythes-bar';
	bar.innerHTML =
		'<input type="search" placeholder="' + ( themenOnly ? 'Thema suchen &hellip;' : 'Stichwort oder Thema suchen &hellip;' ) + '" class="js-q">' +
		( themenOnly ? '' : '<label><input type="checkbox" class="js-cross" checked> Querverweise</label>' ) +
		'<span style="margin-left:auto"></span>' +
		'<button type="button" class="js-zi" title="Vergr&ouml;&szlig;ern">+</button>' +
		'<button type="button" class="js-zo" title="Verkleinern">&minus;</button>' +
		'<button type="button" class="js-fit" title="Gesamtansicht">&#8862; Alles</button>' +
		'<span class="js-stat" style="color:#777;"></span>';
	container.appendChild(bar);

	var legend = document.createElement('div');
	legend.className = 'wisythes-legend';
	container.appendChild(legend);

	var svg = document.createElementNS(NS, 'svg');
	svg.setAttribute('width', W);
	svg.setAttribute('height', H);
	container.appendChild(svg);

	var world = document.createElementNS(NS, 'g');		// gezoomte Ebene
	svg.appendChild(world);
	var overlay = document.createElementNS(NS, 'g');	// Themen-Beschriftung in Bildschirmgroesse
	svg.appendChild(overlay);

	var tip = document.createElement('div');
	tip.className = 'wisythes-tip';
	container.appendChild(tip);

	var hits = document.createElement('div');
	hits.className = 'wisythes-hits';
	container.appendChild(hits);

	/* ------------------------------------------------- Daten (eingebettet) */

	// die Daten werden vom Renderer direkt in die Seite eingebettet und ueber
	// die Init-Optionen uebergeben (kein Nachladen, kein JSON-Endpunkt)
	data = ( initOpts && initOpts.data ) ? initOpts.data : null;
	if( !data ) {
		var errDiv = document.createElement('div');
		errDiv.style.cssText = 'position:absolute; top:45%; width:100%; text-align:center; color:#777;';
		errDiv.textContent = 'Fehler: keine Thesaurus-Daten übergeben.';
		container.appendChild(errDiv);
		return;
	}

	function indexData()
	{
		var i, r;
		data.typen = data.typen || {};
		data.themen = data.themen || [];
		data.stichwoerter = data.stichwoerter || [];	// im Themen-Modus nicht enthalten
		data.oberbegriffe = data.oberbegriffe || [];
		for( i = 0; i < data.themen.length; i++ ) {
			r = data.themen[i];
			themen[r[0]] = { name: r[1], kuerzel: r[2], parent: r[3], count: r[4] || 0 };
		}
		for( i = 0; i < data.stichwoerter.length; i++ ) {
			r = data.stichwoerter[i];
			sw[r[0]] = { name: r[1], type: r[2], thema: r[3] };
		}
		for( i = 0; i < data.oberbegriffe.length; i++ ) {
			r = data.oberbegriffe[i];
			(childrenSW[r[0]] = childrenSW[r[0]] || []).push(r[1]);
			(parentsSW[r[1]] = parentsSW[r[1]] || []).push(r[0]);
		}
	}

	/* ----------------------------------------------- Baum der Darstellung */

	function addNode(key, label, kind, type, scope, swId)
	{
		nodes[key] = { key: key, label: label, kind: kind, type: type, scope: scope || '',
			swId: swId || 0, children: [], parent: null, leaves: 0, depth: 0 };
		return nodes[key];
	}

	function attach(parentKey, childKey)
	{
		nodes[childKey].parent = parentKey;
		nodes[parentKey].children.push(childKey);
	}

	function ensureGroup(type)
	{
		var key = 'g' + type;
		if( !nodes[key] ) {
			addNode(key, data.typen[type] || 'Typ ' + type, 'group', +type, '', 0);
			attach('root', key);
		}
		return key;
	}

	// setzt ein Stichwort (rekursiv ueber seinen ersten platzierbaren
	// Oberbegriff) in den Baum; stack faengt Zyklen ab
	function placeSW(id, stack)
	{
		var key = 's' + id;
		if( nodes[key] ) return true;
		if( !sw[id] ) return false;
		if( stack[id] ) return false;
		stack[id] = true;

		var n = sw[id], parentKey = null, list = parentsSW[id] || [], i;

		for( i = 0; i < list.length && !parentKey; i++ )
			if( placeSW(list[i], stack) ) parentKey = 's' + list[i];
		if( !parentKey )
			parentKey = n.thema && nodes['t'+n.thema] ? 't' + n.thema : ensureGroup(n.type);

		delete stack[id];
		addNode(key, n.name, 'sw', n.type, '', id);
		attach(parentKey, key);
		return true;
	}

	function buildTree()
	{
		nodes = {};
		extraEdges = [];
		addNode('root', themenOnly ? 'Themen' : 'Thesaurus', 'root', -1, '', 0);

		var id, key;

		// Themenbaum
		for( id in themen ) {
			key = 't' + id;
			addNode(key, themen[id].name, 'thema', -1, '', 0);
		}
		for( id in themen )
			attach(themen[id].parent && nodes['t'+themen[id].parent] ? 't' + themen[id].parent : 'root', 't' + id);

		// Stichwoerter
		for( id in sw ) placeSW(+id, {});

		// Querverweise: weitere Oberbegriffe jenseits der im Baum gewaehlten Kante
		var i, list, primary;
		for( id in sw ) {
			key = 's' + id;
			if( !nodes[key] ) continue;
			primary = nodes[key].parent;
			list = parentsSW[id] || [];
			for( i = 0; i < list.length; i++ )
				if( 's' + list[i] != primary && nodes['s'+list[i]] )
					extraEdges.push({ from: 's' + list[i], to: key });
		}
	}

	/* ------------------------------------------------------------- Layout */

	function countLeaves(key)
	{
		var n = nodes[key], sum = 0, h = 0, i, c;
		for( i = 0; i < n.children.length; i++ ) {
			c = nodes[n.children[i]];
			c.depth = n.depth + 1;
			if( c.depth > maxDepth ) maxDepth = c.depth;
			sum += countLeaves(n.children[i]);
			if( c.height + 1 > h ) h = c.height + 1;
		}
		n.leaves = n.children.length ? sum : 1;
		n.height = h;	// Abstand zum tiefsten Blatt darunter
		return n.leaves;
	}

	// Blaetter gleichmaessig auf dem Kreis verteilen, innere Knoten auf den
	// Mittelwinkel ihrer Kinder; Blaetter aussen auf Radius R, Themen und
	// Gruppen auf festen inneren Ringen, innere Stichwoerter anhand ihrer
	// Resthoehe knapp innerhalb des Blattrings (Dendrogramm)
	function layout()
	{
		maxDepth = 1;
		leafCount = countLeaves('root');
		R = Math.max(500, leafCount * LEAF_SPACING / (2 * Math.PI));
		var stepIn = Math.max(60, R * .04);

		var nextLeaf = 0;
		(function assign(key, parentRadius) {
			var n = nodes[key], i;

			if( key == 'root' )
				n.radius = 0;
			else if( n.children.length )
				n.radius = Math.max(parentRadius + stepIn, R - n.height * stepIn);
			else
				n.radius = R;

			if( !n.children.length ) {
				n.angle = (nextLeaf++ + .5) * 2 * Math.PI / leafCount;
			}
			else {
				var sum = 0;
				for( i = 0; i < n.children.length; i++ ) {
					assign(n.children[i], n.radius);
					sum += nodes[n.children[i]].angle;
				}
				n.angle = sum / n.children.length;
			}
			if( key == 'root' ) n.angle = 0;
			n.x = n.radius * Math.cos(n.angle);
			n.y = n.radius * Math.sin(n.angle);
		})('root', 0);
	}

	/* ------------------------------------------------------------ Zeichnen */

	function radialLink(a, b)
	{
		var rm = (a.radius + b.radius) / 2;
		return 'M' + a.x.toFixed(1) + ',' + a.y.toFixed(1)
			+ 'C' + (rm * Math.cos(a.angle)).toFixed(1) + ',' + (rm * Math.sin(a.angle)).toFixed(1)
			+ ' ' + (rm * Math.cos(b.angle)).toFixed(1) + ',' + (rm * Math.sin(b.angle)).toFixed(1)
			+ ' ' + b.x.toFixed(1) + ',' + b.y.toFixed(1);
	}

	function crossLink(a, b)
	{
		// Querverweis: zur Mitte hin gebogene Kurve (angedeutetes Buendeln)
		var mx = (a.x + b.x) / 2 * .35, my = (a.y + b.y) / 2 * .35;
		return 'M' + a.x.toFixed(1) + ',' + a.y.toFixed(1)
			+ 'Q' + mx.toFixed(1) + ',' + my.toFixed(1)
			+ ' ' + b.x.toFixed(1) + ',' + b.y.toFixed(1);
	}

	function nodeColor(n)
	{
		if( n.kind == 'thema' || n.kind == 'root' ) return THEMA_COLOR;
		return TYPE_COLORS[n.type] || '#666';
	}

	function esc(s)
	{
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function draw()
	{
		var treePath = [], circles = [], labels = [], key, n;

		for( key in nodes ) {
			n = nodes[key];
			if( n.parent )
				treePath.push(radialLink(nodes[n.parent], n));
		}

		var crossOber = [], i, e;
		if( opts.showCross )
			for( i = 0; i < extraEdges.length; i++ ) {
				e = extraEdges[i];
				crossOber.push(crossLink(nodes[e.from], nodes[e.to]));
			}

		for( key in nodes ) {
			n = nodes[key];
			var col = nodeColor(n), rad, cls = 'nd';
			if( n.kind == 'root' )			rad = 8;
			else if( n.kind == 'thema' )	rad = 5;
			else if( n.kind == 'group' )	rad = 5;
			else							rad = n.children.length ? 3.5 : 2.5;

			circles.push('<circle data-k="' + key + '" class="' + cls + '" cx="' + n.x.toFixed(1) + '" cy="' + n.y.toFixed(1)
				+ '" r="' + rad + '" fill="' + col + '"/>');

			var deg = n.angle * 180 / Math.PI, flip = ( deg > 90 && deg < 270 );
			var lblCls, size, inner;
			if( n.kind == 'thema' || n.kind == 'group' || n.kind == 'root' ) { continue; }	// -> Overlay
			else if( n.children.length ) { lblCls = 'lbl-mid'; size = 12; inner = true; }	// Beschriftung zeigt nach innen
			else { lblCls = 'lbl-leaf'; size = 10; inner = false; }

			var anchorEnd = inner ? !flip : flip;
			labels.push('<text data-k="' + key + '" class="' + lblCls + '" font-size="' + size
				+ '" transform="rotate(' + deg.toFixed(2) + ') translate(' + (inner ? n.radius - 7 : n.radius + 7).toFixed(1) + ',3)'
				+ ( flip ? ' rotate(180)' : '' ) + '"'
				+ ( anchorEnd ? ' text-anchor="end"' : '' )
				+ '>' + esc(n.label) + '</text>');
		}

		world.innerHTML =
			'<path fill="none" stroke="#c9d4de" stroke-width="1" stroke-opacity=".55" d="' + treePath.join('') + '"/>' +
			( crossOber.length ? '<path fill="none" stroke="#d98f8f" stroke-width="1" stroke-dasharray="5,4" opacity=".6" d="' + crossOber.join('') + '"/>' : '' ) +
			'<path class="js-hl" fill="none" stroke="#e07b28" stroke-width="2" d=""/>' +
			circles.join('') + labels.join('') +
			'<circle class="js-pulse" r="0" fill="none" stroke="#e07b28" stroke-width="3"/>';

		// Themen/Gruppen als Overlay in konstanter Bildschirmgroesse;
		// je Ring nach Winkel sortiert und abwechselnd ober-/unterhalb des
		// Knotens versetzt, damit sich benachbarte Beschriftungen weniger ueberlappen
		var ovNodes = [];
		for( key in nodes ) {
			n = nodes[key];
			if( n.kind == 'thema' || n.kind == 'group' || n.kind == 'root' ) ovNodes.push(n);
		}
		ovNodes.sort(function(a, b) { return a.radius - b.radius || a.angle - b.angle; });
		var ov = [];
		for( i = 0; i < ovNodes.length; i++ ) {
			n = ovNodes[i];
			// strahlenfoermig entlang des eigenen Winkels beschriften
			// (Drehung uebernimmt applyView), auf der linken Kreishaelfte gespiegelt
			var odeg = n.angle * 180 / Math.PI, oflip = ( odeg > 90 && odeg < 270 );
			ov.push('<text data-k="' + n.key + '" data-x="' + n.x.toFixed(1) + '" data-y="' + n.y.toFixed(1)
				+ '" data-a="' + ( oflip ? odeg + 180 : odeg ).toFixed(2) + '"'
				+ ( n.kind == 'root' ? ' x="0" y="-10" text-anchor="middle"' : ' x="' + ( oflip ? -10 : 10 ) + '" y="4"' + ( oflip ? ' text-anchor="end"' : '' ) )
				+ ' class="lbl-top ' + ( n.depth <= 1 ? 'lbl-ov1' : 'lbl-ov2' )
				+ '" font-size="' + ( n.kind == 'root' ? 15 : ( n.depth <= 1 ? 13 : 11 ) )
				+ '"' + ( n.kind == 'group' ? ' fill="#888"' : '' ) + '>' + esc(n.label) + '</text>');
		}
		overlay.innerHTML = ov.join('');

		var stat = container.querySelector('.js-stat');
		if( themenOnly ) {
			stat.textContent = Object.keys(themen).length + ' Themen';
		}
		else {
			var cnt = 0; for( key in nodes ) if( nodes[key].kind == 'sw' ) cnt++;
			stat.textContent = cnt.toLocaleString('de-DE') + ' Stichwörter, '
				+ Object.keys(themen).length + ' Themen'
				+ ( opts.showCross ? ', ' + extraEdges.length + ' Querverweise' : '' );
		}
	}

	/* -------------------------------------------------------- Zoom und Pan */

	function applyView()
	{
		world.setAttribute('transform', 'translate(' + view.x + ',' + view.y + ') scale(' + view.k + ')');

		// Beschriftungs-Detailstufe (unterhalb sind die Schriften ohnehin unlesbar klein)
		var z = view.k >= .9 ? 2 : ( view.k >= .55 ? 1 : 0 );
		world.setAttribute('class', 'wtz-' + z);
		overlay.setAttribute('class', view.k < fitK * 2.5 ? 'ovz-0' : 'ovz-1');

		// Overlay-Labels nachfuehren (konstante Groesse, radiale Ausrichtung)
		var t = overlay.childNodes, i, el;
		for( i = 0; i < t.length; i++ ) {
			el = t[i];
			el.setAttribute('transform', 'translate('
				+ ( view.x + view.k * el.getAttribute('data-x') ) + ','
				+ ( view.y + view.k * el.getAttribute('data-y') ) + ') rotate('
				+ el.getAttribute('data-a') + ')');
		}
	}

	function fit()
	{
		var pad = 40;
		fitK = Math.min((W - pad) / (2 * (R + 200)), (H - pad) / (2 * (R + 200)));
		view.k = fitK;
		view.x = W / 2;
		view.y = H / 2;
		applyView();
	}

	function zoomAt(px, py, factor)
	{
		var k = Math.min(40, Math.max(fitK * .5, view.k * factor));
		view.x = px - (px - view.x) * (k / view.k);
		view.y = py - (py - view.y) * (k / view.k);
		view.k = k;
		applyView();
	}

	svg.addEventListener('wheel', function(ev) {
		ev.preventDefault();
		var rect = svg.getBoundingClientRect();
		zoomAt(ev.clientX - rect.left, ev.clientY - rect.top, Math.exp(-ev.deltaY * .002));
	}, { passive: false });

	var drag = null;
	svg.addEventListener('pointerdown', function(ev) {
		drag = { x: ev.clientX, y: ev.clientY, vx: view.x, vy: view.y, moved: false };
		svg.setPointerCapture(ev.pointerId);
		svg.classList.add('dragging');
	});
	svg.addEventListener('pointermove', function(ev) {
		if( !drag ) { hover(ev); return; }
		var dx = ev.clientX - drag.x, dy = ev.clientY - drag.y;
		if( Math.abs(dx) + Math.abs(dy) > 3 ) drag.moved = true;
		view.x = drag.vx + dx;
		view.y = drag.vy + dy;
		applyView();
	});
	svg.addEventListener('pointerup', function() { drag = null; svg.classList.remove('dragging'); });
	svg.addEventListener('dblclick', function(ev) {
		var rect = svg.getBoundingClientRect();
		zoomAt(ev.clientX - rect.left, ev.clientY - rect.top, 1.7);
	});

	bar.querySelector('.js-zi').addEventListener('click', function() { zoomAt(W/2, H/2, 1.5); });
	bar.querySelector('.js-zo').addEventListener('click', function() { zoomAt(W/2, H/2, 1/1.5); });
	bar.querySelector('.js-fit').addEventListener('click', fit);

	/* ------------------------------------------------- Tooltip und Hervorhebung */

	function themaPath(id)
	{
		var parts = [], guard = 0;
		while( id && themen[id] && guard++ < 10 ) {
			parts.unshift(themen[id].name);
			id = themen[id].parent;
		}
		return parts.join(' › ');
	}

	function nameList(ids, max)
	{
		var out = [], i;
		for( i = 0; i < ids.length && i < max; i++ )
			if( sw[ids[i]] ) out.push(esc(sw[ids[i]].name));
		if( ids.length > max ) out.push('… (' + ids.length + ' gesamt)');
		return out.join(', ');
	}

	function tipHtml(n)
	{
		var h = '<h4>' + esc(n.label) + '</h4>';
		if( n.kind == 'thema' ) {
			h += 'Thema';
			var tId = +n.key.substr(1);
			if( themen[tId] && themen[tId].kuerzel ) h += ' ' + esc(themen[tId].kuerzel);
			if( themenOnly && themen[tId] )
				h += '<br>' + themen[tId].count + ' direkt zugeordnete Stichwörter';
		}
		else if( n.kind == 'group' )
			h += 'Gruppe: Stichwörter ohne Thema/Oberbegriff';
		else if( n.kind == 'root' )
			h += 'Wurzel des Thesaurus';
		else {
			h += '<span class="wisythes-dot" style="background:' + nodeColor(n) + '"></span>' + esc(data.typen[n.type] || n.type);
			var id = n.swId;
			if( sw[id].thema ) h += '<br>Thema: ' + esc(themaPath(sw[id].thema));
			if( parentsSW[id] ) h += '<br>Oberbegriffe: ' + nameList(parentsSW[id], 6);
			if( childrenSW[id] ) h += '<br>Unterbegriffe (' + childrenSW[id].length + '): ' + nameList(childrenSW[id], 6);
		}
		return h;
	}

	function highlightEdges(n)
	{
		var d = [], id = n.swId, i, list;
		if( n.parent ) d.push(radialLink(nodes[n.parent], n));
		for( i = 0; i < n.children.length; i++ ) d.push(radialLink(n, nodes[n.children[i]]));
		if( id ) {
			list = parentsSW[id] || [];
			for( i = 0; i < list.length; i++ )
				if( nodes['s'+list[i]] && 's' + list[i] != n.parent ) d.push(crossLink(nodes['s'+list[i]], n));
		}
		var hl = world.querySelector('.js-hl');
		if( hl ) hl.setAttribute('d', d.join(''));
	}

	function hover(ev)
	{
		var t = ev.target, key = t.getAttribute && t.getAttribute('data-k');
		if( !key || !nodes[key] ) { hideTip(); return; }
		var n = nodes[key];
		tip.innerHTML = tipHtml(n);
		tip.style.display = 'block';
		var rect = container.getBoundingClientRect();
		var x = ev.clientX - rect.left + 14, y = ev.clientY - rect.top + 14;
		if( x + tip.offsetWidth > rect.width ) x = Math.max(0, x - tip.offsetWidth - 28);
		if( y + tip.offsetHeight > rect.height ) y = Math.max(0, y - tip.offsetHeight - 28);
		tip.style.left = x + 'px';
		tip.style.top = y + 'px';
		highlightEdges(n);
	}

	function hideTip()
	{
		tip.style.display = 'none';
		var hl = world.querySelector('.js-hl');
		if( hl ) hl.setAttribute('d', '');
	}

	svg.addEventListener('pointerleave', hideTip);

	/* --------------------------------------------------------------- Suche */

	function norm(s)
	{
		return s.toLowerCase()
			.replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss');
	}

	var qInput = bar.querySelector('.js-q');
	qInput.addEventListener('input', function() {
		var q = norm(qInput.value.trim());
		hits.innerHTML = '';
		if( q.length < 2 ) { hits.style.display = 'none'; return; }
		var found = 0, key, n;
		for( key in nodes ) {
			n = nodes[key];
			if( n.kind == 'root' || norm(n.label).indexOf(q) < 0 ) continue;
			var div = document.createElement('div');
			div.innerHTML = '<span class="wisythes-dot" style="background:' + nodeColor(n) + '"></span>'
				+ esc(n.label) + ( n.kind == 'thema' ? ' <small>(Thema)</small>' : '' );
			div.setAttribute('data-k', key);
			hits.appendChild(div);
			if( ++found >= 25 ) break;
		}
		hits.style.display = found ? 'block' : 'none';
		hits.style.left = qInput.offsetLeft + 'px';
		hits.style.top = ( qInput.offsetTop + qInput.offsetHeight + 2 ) + 'px';
	});

	hits.addEventListener('click', function(ev) {
		var el = ev.target.closest('[data-k]');
		if( !el ) return;
		hits.style.display = 'none';
		focusNode(el.getAttribute('data-k'));
	});

	qInput.addEventListener('keydown', function(ev) {
		if( ev.key == 'Enter' && hits.firstChild ) {
			hits.style.display = 'none';
			focusNode(hits.firstChild.getAttribute('data-k'));
		}
	});

	function focusNode(key)
	{
		var n = nodes[key];
		if( !n ) return;
		// innere Knoten: so zoomen, dass der Bereich bis zum Blattring passt,
		// und auf die Mitte dieses Bereichs zentrieren
		var span = n.children.length ? ( R - n.radius + 900 ) : 500;
		var rmid = n.children.length ? ( n.radius + R ) / 2 : n.radius;
		view.k = Math.min(2.5, Math.max(.55, Math.min(W, H) / span));
		view.x = W / 2 - view.k * rmid * Math.cos(n.angle);
		view.y = H / 2 - view.k * rmid * Math.sin(n.angle);
		applyView();

		var pulse = world.querySelector('.js-pulse');
		if( pulse ) {
			pulse.setAttribute('cx', n.x);
			pulse.setAttribute('cy', n.y);
			pulse.setAttribute('r', 12);
			pulse.setAttribute('opacity', 1);
			var r = 12, timer = setInterval(function() {
				r += 3;
				pulse.setAttribute('r', r);
				pulse.setAttribute('opacity', Math.max(0, 1 - (r - 12) / 40));
				if( r > 52 ) { clearInterval(timer); pulse.setAttribute('r', 0); }
			}, 30);
		}
	}

	/* ----------------------------------------------- Legende und Schalter */

	function buildLegend()
	{
		var counts = {}, i, t;
		for( i = 0; i < data.stichwoerter.length; i++ ) {
			t = data.stichwoerter[i][2];
			counts[t] = (counts[t] || 0) + 1;
		}
		var h = '<span data-t="thema"><span class="wisythes-dot" style="background:' + THEMA_COLOR + '"></span>Thema (' + data.themen.length + ')</span>';
		for( t in data.typen )
			h += '<span data-t="' + t + '"><span class="wisythes-dot" style="background:' + (TYPE_COLORS[t] || '#666')
				+ '"></span>' + esc(data.typen[t]) + ' (' + (counts[t] || 0) + ')</span>';
		legend.innerHTML = h;
	}

	// Klick auf einen Legendeneintrag: nur diesen Typ hervorheben (Rest daempfen)
	legend.addEventListener('click', function(ev) {
		var el = ev.target.closest('[data-t]');
		if( !el ) return;
		var t = el.getAttribute('data-t');
		opts.focusType = ( opts.focusType === t ) ? null : t;
		var spans = legend.querySelectorAll('[data-t]'), i;
		for( i = 0; i < spans.length; i++ )
			spans[i].className = ( opts.focusType && spans[i].getAttribute('data-t') !== opts.focusType ) ? 'off' : '';
		applyFocus();
	});

	function applyFocus()
	{
		var els = world.querySelectorAll('[data-k]'), i, key, n, on;
		for( i = 0; i < els.length; i++ ) {
			key = els[i].getAttribute('data-k');
			n = nodes[key];
			on = !opts.focusType
				|| ( opts.focusType == 'thema' && ( n.kind == 'thema' || n.kind == 'root' ) )
				|| ( n.kind == 'sw' && n.type == +opts.focusType );
			els[i].setAttribute('opacity', on ? 1 : .12);
		}
	}

	function rebuild(first)
	{
		buildTree();
		layout();
		draw();
		if( first ) fit(); else applyView();
		if( opts.focusType ) applyFocus();
	}

	var crossToggle = bar.querySelector('.js-cross');
	if( crossToggle )
		crossToggle.addEventListener('change', function(ev) { opts.showCross = ev.target.checked; rebuild(); });

	/* ------------------------------------------------------------- Start */

	indexData();
	buildLegend();
	rebuild(true);
}

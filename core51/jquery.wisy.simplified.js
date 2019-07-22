
/*****************************************************************************
 * Libs
 *****************************************************************************/

// textinputs - http://code.google.com/p/rangy/
(function(n){function o(e,g){var a=typeof e[g];return a==="function"||!!(a=="object"&&e[g])||a=="unknown"}function p(e,g,a){if(g<0)g+=e.value.length;if(typeof a=="undefined")a=g;if(a<0)a+=e.value.length;return{start:g,end:a}}function k(){return typeof document.body=="object"&&document.body?document.body:document.getElementsByTagName("body")[0]}var i,h,q,l,r,s,t,u,m;n(document).ready(function(){function e(a,b){return function(){var c=this.jquery?this[0]:this,d=c.nodeName.toLowerCase();if(c.nodeType==
1&&(d=="textarea"||d=="input"&&c.type=="text")){c=[c].concat(Array.prototype.slice.call(arguments));c=a.apply(this,c);if(!b)return c}if(b)return this}}var g=document.createElement("textarea");k().appendChild(g);if(typeof g.selectionStart!="undefined"&&typeof g.selectionEnd!="undefined"){i=function(a){return{start:a.selectionStart,end:a.selectionEnd,length:a.selectionEnd-a.selectionStart,text:a.value.slice(a.selectionStart,a.selectionEnd)}};h=function(a,b,c){b=p(a,b,c);a.selectionStart=b.start;a.selectionEnd=
b.end};m=function(a,b){if(b)a.selectionEnd=a.selectionStart;else a.selectionStart=a.selectionEnd}}else if(o(g,"createTextRange")&&typeof document.selection=="object"&&document.selection&&o(document.selection,"createRange")){i=function(a){var b=0,c=0,d,f,j;if((j=document.selection.createRange())&&j.parentElement()==a){f=a.value.length;d=a.value.replace(/\r\n/g,"\n");c=a.createTextRange();c.moveToBookmark(j.getBookmark());j=a.createTextRange();j.collapse(false);if(c.compareEndPoints("StartToEnd",j)>
-1)b=c=f;else{b=-c.moveStart("character",-f);b+=d.slice(0,b).split("\n").length-1;if(c.compareEndPoints("EndToEnd",j)>-1)c=f;else{c=-c.moveEnd("character",-f);c+=d.slice(0,c).split("\n").length-1}}}return{start:b,end:c,length:c-b,text:a.value.slice(b,c)}};h=function(a,b,c){b=p(a,b,c);c=a.createTextRange();var d=b.start-(a.value.slice(0,b.start).split("\r\n").length-1);c.collapse(true);if(b.start==b.end)c.move("character",d);else{c.moveEnd("character",b.end-(a.value.slice(0,b.end).split("\r\n").length-
1));c.moveStart("character",d)}c.select()};m=function(a,b){var c=document.selection.createRange();c.collapse(b);c.select()}}else{k().removeChild(g);window.console&&window.console.log&&window.console.log("TextInputs module for Rangy not supported in your browser. Reason: No means of finding text input caret position");return}k().removeChild(g);l=function(a,b,c,d){var f;if(b!=c){f=a.value;a.value=f.slice(0,b)+f.slice(c)}d&&h(a,b,b)};q=function(a){var b=i(a);l(a,b.start,b.end,true)};u=function(a){var b=
i(a),c;if(b.start!=b.end){c=a.value;a.value=c.slice(0,b.start)+c.slice(b.end)}h(a,b.start,b.start);return b.text};r=function(a,b,c,d){var f=a.value;a.value=f.slice(0,c)+b+f.slice(c);if(d){b=c+b.length;h(a,b,b)}};s=function(a,b){var c=i(a),d=a.value;a.value=d.slice(0,c.start)+b+d.slice(c.end);c=c.start+b.length;h(a,c,c)};t=function(a,b,c){var d=i(a),f=a.value;a.value=f.slice(0,d.start)+b+d.text+c+f.slice(d.end);b=d.start+b.length;h(a,b,b+d.length)};n.fn.extend({getSelection:e(i,false),setSelection:e(h,
true),collapseSelection:e(m,true),deleteSelectedText:e(q,true),deleteText:e(l,true),extractSelectedText:e(u,false),insertText:e(r,true),replaceSelectedText:e(s,true),surroundSelectedText:e(t,true)})})})(jQuery);

// cookie - http://archive.plugins.jquery.com/project/Cookie , http://www.electrictoolbox.com/jquery-cookies/
(function(e,t,n){function i(e){return e}function s(e){return decodeURIComponent(e.replace(r," "))}var r=/\+/g;var o=e.cookie=function(r,u,a){if(u!==n){a=e.extend({},o.defaults,a);if(u===null){a.expires=-1}
if(typeof a.expires==="number"){var f=a.expires,l=a.expires=new Date;l.setDate(l.getDate()+f)}u=o.json?JSON.stringify(u):String(u);return t.cookie=[encodeURIComponent(r),"=",o.raw?u:encodeURIComponent(u),
a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=o.raw?i:s;var h=t.cookie.split("; ");for(var p=0,d=h.length;p<d;p++)
{var v=h[p].split("=");if(c(v.shift())===r){var m=c(v.join("="));return o.json?JSON.parse(m):m}}return null};o.defaults={};e.removeCookie=function(t,n){if(e.cookie(t)!==null){e.cookie(t,null,n);return true}return false}})(jQuery,document)

/*****************************************************************************
 * DSGVO stuff
 *****************************************************************************/

/* Cookie optout wrapper for $.cookie function
 *
 * Passes cookies through to $.cookie function, but only if user has not opted out 
 * or if cookie is not blacklisted via cookiebanner.cookies.optout
 *
 */
function setCookieSafely(title, value, options) {
	if (window.cookiebanner && window.cookiebanner.optedOut && window.cookiebanner.optoutCookies && window.cookiebanner.optoutCookies.length) {
		var blacklist = window.cookiebanner.optoutCookies.split(',');
		for (var i = 0; i < blacklist.length; i++) {
			if (title === $.trim(blacklist[i])) {
				return false;
			}
		}
	}
	$.cookie(title, value, options);
}

/* Update Cookie Settings
 *
 * Remove all cookies that are set as optout cookies in portal settings
 * via cookiebanner.cookies.optout when user opts out of cookies
 * Dis- / enable Google Analytics and PIWIK
 */
function updateCookieSettings() {
	if (window.cookiebanner) {
		if (window.cookiebanner.optedOut) {
			
			// Disable Google Analytics
			// Tut das überhaupt irgendwas? -- https://developers.google.com/analytics/devguides/collection/analyticsjs/user-opt-out
			if(window.cookiebanner.uacct) window['ga-disable-' + window.cookiebanner.uacct] = true;
			
			// Remove unwanted cookies
			if (window.cookiebanner.optoutCookies && window.cookiebanner.optoutCookies.length) {
				
				// Portal blacklist
				var blacklist = window.cookiebanner.optoutCookies.split(',');
				for (var i = 0; i < blacklist.length; i++) {
					var cookieName = $.trim(blacklist[i]);
					if (cookieName !== '') $.removeCookie(cookieName, { path: '/' });
				}
				
				// FAV
				$.removeCookie('fav', { path: '/' });
				$.removeCookie('fav_init_hint', { path: '/' });
				
				// Piwik
				if(window.cookiebanner.piwik) {
					var piwikCookies = document.cookie.match(/\_pk(\_id|\_ses)\.6\..*?=/g);
					if (piwikCookies !== null) {
						for(var i=0; i < piwikCookies.length; i++) {
							$.removeCookie(piwikCookies[i].replace('=', ''), { path: '/' });
						}
					}
				}
				
				// Google Analytics
				if (window.cookiebanner.uacct) {
					$.removeCookie('_ga', { path: '/' }); 
					$.removeCookie('_gat', { path: '/' });
					$.removeCookie('_gid', { path: '/' });
				}
			}
		}
	}
}

/*****************************************************************************
 * fav stuff
 *****************************************************************************/

 
 
var g_all_fav = {};
function fav_count()
{	
	var cnt = 0;
	for( var key in g_all_fav ) {
		if( g_all_fav[ key ] )
			cnt ++;
	}
	return cnt;
}
function fav_is_favourite(id)
{
	return g_all_fav[ id ]? true : false;
}
function fav_set_favourite(id, state)
{
	g_all_fav[ id ] = state;
	fav_save_cookie();
}
function fav_save_cookie()
{
	var str = '';
	for( var key in g_all_fav ) {
		if( g_all_fav[ key ] ) {
			str += str==''? '' : ',';
			str += key;
		}
	}
	setCookieSafely('fav', str, { expires: 30 }); // expires in 30 days
}



function fav_list_functions()
{
	var cnt = fav_count();
	if( cnt > 0 )
	{
		var mailto = $('#favlistlink').attr('data-favlink');
		if( mailto != '' )
		{
			mailto += 'search?q=favprint%253A';
			for( var key in g_all_fav ) {
				if( g_all_fav[ key ] ) {
					mailto += key + '%252F';
				}
			}
		}
		
		str = '<span class="wisyr_fav_functions">';
		str += '<span class="wisyr_fav_anzahl">Ihre Merkliste enthält ' + cnt + (cnt==1? ' Eintrag ' : ' Einträge ') + '</span>';
		if( mailto != '' ) 
		{
			str += '<a class="fav_functions_mailsend" href="' + mailto + '" title="Merkliste per E-Mail versenden" class="fav_send">Merkliste per E-Mail versenden</a> ';
		}
		str += ' <a class="fav_functions_deleteall" href="javascript:fav_delete_all()" title="Gesamte Merkliste löschen">Gesamte Merkliste löschen</a>';
		str += '</span>';
		
		$('.wisyr_angebote_zum_suchauftrag').html(str);
	}
}

function fav_update_bar()
{	
	var cnt = fav_count();
	if( cnt > 0 )
	{
		str = '<a href="search?q=Fav%3A" title="Merkliste anzeigen">';
			str += '<span class="fav_item fav_selected">&#9733;</span> ';
			str += '<span class="favlistlink_title">Merkliste (' + cnt + ')</span>';
		str += '</a> ';
		
		$('#favlistlink').html(str);
		
		$('.fav_hide').hide();
	}
	else
	{
		$('#favlistlink').html('');
		$('.fav_hide').show();
	}
}


function fav_click(jsObj, id)
{
	if (window.cookiebanner && window.cookiebanner.optedOut) {
		alert(window.cookiebanner.favOptoutMessage);
		return false;
	}
	jqObj = $(jsObj);
	if( jqObj.hasClass('fav_selected') ) {
		jqObj.removeClass('fav_selected');
		fav_set_favourite(id, false);
		fav_update_bar();
	}
	else {
		jqObj.addClass('fav_selected');
		fav_set_favourite(id, true);
		fav_update_bar();
		
		if( $.cookie('fav_init_hint') != 1 ) {
			alert('Ihr Favorit wurde auf diesem Computer gespeichert. Um ihre Merkliste anzuzeigen, klicken Sie auf "Merkliste" oben rechts.');
			setCookieSafely('fav_init_hint', 1, { expires: 30 }); 
		}
	}
}
function fav_delete_all()
{
	if( !confirm('Gesamte Merkliste löschen?') )
		return false;
	
	g_all_fav = {};
	fav_save_cookie();
	window.location.reload(true);
}



function fav_init()
{
	// read favs from cookie (exp. '3501,3554')
	var temp = $.cookie('fav');
	if( typeof temp == 'string' ) {
		temp = temp.split(',');
		for( var i = 0; i < temp.length; i++ ) {
			var id = parseInt(temp[i], 10);
			if( !isNaN(id) && id > 0 ) {
				g_all_fav[ id ] = true;
			}
		}
	}
	
	// prepare the page
	var has_clickable_fav = false;
	$('.fav_add').each(function() {
		var id = $(this).attr('data-favid');
		var cls = fav_is_favourite(id)? 'fav_item fav_selected' : 'fav_item';
		$(this).parent().append(' <a href="#" class="'+cls+'" onclick="fav_click(this, '+id+');return false;" title="Angebot merken" tabindex="0">&#9733;</a>');
		has_clickable_fav = true;
	});
	
	if( has_clickable_fav ) {
		$('table.wisy_list tr th:first a.wisy_help').after('<span class="fav_hint_in_th">mit &#9733; merken</span>');
	}
	
	if( fav_count() ) {
		fav_update_bar();
		
		if( $('body').hasClass('wisyq_fav') ) {
			fav_list_functions();
		}
	}
}
 




/*****************************************************************************
 * autocomplete stuff
 *****************************************************************************/

function clickAutocompleteHelp(tag_help, tag_name_encoded)
{
	location.href = 'g' + tag_help + '?ie=UTF-8&q=' + tag_name_encoded;
	return false;
}

function clickAutocompleteMore(tag_name_encoded)
{
	location.href = 'search?ie=UTF-8&show=tags&q=' + tag_name_encoded;
}

function htmlspecialchars(text)
{
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatItem(row)
{
	var tag_name  = row[0]; // this is plain text, so take care on output! (eg. you may want to strip or escape HTML)
	var tag_descr = row[1]; // this is already HTML
	var tag_type  = row[2];
	var tag_help  = row[3];
	var tag_freq  = row[4];
	
	/* see also (***) in the PHP part */
	var row_class   = 'ac_normal';
	var row_prefix  = '';
	var row_preposition = '';
	var row_postfix = '';

	if( tag_help == 1 )
	{
		/* add the "more" link */
		row_class = 'ac_more';
		tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name) + '&#39;)">' + tag_descr + '</a>';
	}
	else
	{
		/* base type */
		     if( tag_type &   1 ) { row_class = "ac_abschluss";            row_preposition = ' zum '; row_postfix = '<b>Abschluss</b>'; }
		else if( tag_type &   2 ) { row_class = "ac_foerderung";           row_preposition = ' zur '; row_postfix = 'F&ouml;rderung'; }
		else if( tag_type &   4 ) { row_class = "ac_qualitaetszertifikat"; row_preposition = ' zum '; row_postfix = 'Qualit&auml;tszertifikat'; }
		else if( tag_type &   8 ) { row_class = "ac_zielgruppe";           row_preposition = ' zur '; row_postfix = 'Zielgruppe'; }
		else if( tag_type &  16 ) { row_class = "ac_abschlussart";         row_preposition = ' zur '; row_postfix = 'Abschlussart'; }
		else if( tag_type & 128 ) { row_class = "ac_thema";                row_preposition = ' zum '; row_postfix = 'Thema'; }
		else if( tag_type & 256 ) { row_class = "ac_anbieter";
											 if( tag_type &  0x10000 )	 { row_preposition = ' zum '; row_postfix = 'Trainer'; }
										else if( tag_type &  0x20000 )	 { row_preposition = ' zur '; row_postfix = 'Beratungsstelle'; }
										else if( tag_type & 0x400000 )	 { row_preposition = ' zum '; row_postfix = 'Anbieterverweis'; }
										else							 { row_preposition = ' zum '; row_postfix = 'Anbieter'; }
								  }
		else if( tag_type & 512 ) { row_class = "ac_ort";                  row_preposition = ' zum '; row_postfix = 'Ort'; }
		else if( tag_type & 1024 ) { row_class = "ac_sonstigesmerkmal";    row_preposition = ' zum '; row_postfix = 'sonstigen Merkmal'; }
		else if( tag_type & 32768 ) { row_class = "ac_unterrichtsart";     row_preposition = ' zur '; row_postfix = 'Unterrichtsart'; }
	
		/* frequency, end base type */
		if( tag_freq > 0 )
		{
			row_postfix = (tag_freq==1? '1 Kurs' : ('' + tag_freq + ' Kurse')) + row_preposition + row_postfix;
		}

		if( tag_descr != '' )
		{
			row_postfix = tag_descr + ', ' + row_postfix;
		}
		
		if( row_postfix != '' )
		{
			row_postfix = ' <span class="ac_tag_type">(' + row_postfix + ')</span> ';
		}

		
		/* additional flags */
		if( tag_type & 0x10000000 )
		{
			row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			row_class += " ac_indent";
		}	
		else if( tag_type & 0x20000000 )
		{
			row_prefix = 'Meinten Sie: ';
		}
		
		/* help link */
		if( tag_help != 0 )
		{
			/* note: a single semicolon disturbs the highlighter as well as a single quote! */
			row_postfix +=
			 ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)" aria-label="Ratgeber zu ' + tag_name + '">&nbsp;i&nbsp;</a>';
		}
		
		tag_name = htmlspecialchars(tag_name);
	}
	
	return '<span class="'+row_class+'">' + row_prefix + tag_name + row_postfix + '</span>';
}

function formatResult(row) {
	return row[0].replace(/(<.+?>)/gi, '');
}

function formatPlzOrtItem(row) {
	return row[0] + ' ' + row[1];
}

function formatPlzOrtResult(row) {
	return row[1];
}

function initAutocomplete()
{
	$(".ac_keyword").autocomplete('autosuggest',
	{
		width: '40%',
		multiple: true,
		matchContains: true,
		matchSubset: false, /* andernfalls wird aus den bisherigen Anfragen versucht eine Liste herzustellen; dies schlaegt dann aber bei unseren Verweisen fehl */
		formatItem: formatItem,
		formatResult: formatResult,
		max: 512,
		scrollHeight: 250,
		selectFirst: false
	});
	
	$(".ac_plzort").autocomplete('autosuggestplzort',
	{
		width: '20%',
		multiple: false,
		matchContains: true,
		matchSubset: false, /* andernfalls wird aus den bisherigen Anfragen versucht eine Liste herzustellen; dies schlaegt dann aber bei unseren Verweisen fehl */
		formatItem: formatPlzOrtItem,
		formatResult: formatPlzOrtResult,
		max: 512,
		scrollHeight: 250,
		selectFirst: false
	});
}

/*****************************************************************************
 * autocomplete stuff v2 (using jquery ui autocomplete. new in 2015)
 * activate by setting search.suggest.v2 = 1
 * Uses functions of autocomplete v1 where possible
 *****************************************************************************/


if (jQuery.ui)
{
	function formatItem_v2(row, request_term)
	{
		var tag_name  = row[0]; // this is plain text, so take care on output! (eg. you may want to strip or escape HTML)
		var tag_descr = row[1]; // this is already HTML
		var tag_type  = row[2];
		var tag_help  = row[3];
		var tag_freq  = row[4];
	
		/* see also (***) in the PHP part */
		row_class = 'ac_normal';
		row_type = 'Sachstichwort';
		row_count = '';
		row_info = '';
		row_prefix = '';
		row_postfix = '';

		if( tag_help == -3 )
		{
			/* add volltext */
			row_class = 'ac_fulltext';
			tag_name = 'Volltextsuche nach "' + request_term + '" ausführen?';
		}
		else if( tag_help == -2 )
		{
			/* add "no results" */
			row_class = 'ac_noresults';
			row_type = '';
			tag_name = '<strong>' + tag_descr + '</strong>';
		}
		else if( tag_help == -1 )
		{
			/* add the Headline */
			row_class = 'ac_headline';
			row_type = '';
			tag_name = '<strong>' + tag_descr + '</strong>';
		}
		else if( tag_help == 1 )
		{
			/* add the "more" link */
			row_class = 'ac_more';
			row_type = '';
			tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name) + '&#39;)">' + tag_descr + '</a>';
		}
		else
		{
			/* base type */
				 if( tag_type &   1 ) { row_class = "ac_abschluss";            row_type = 'Abschluss'; }
			else if( tag_type &   2 ) { row_class = "ac_foerderung";           row_type = 'F&ouml;rderung'; }
			else if( tag_type &   4 ) { row_class = "ac_qualitaetszertifikat"; row_type = 'Qualit&auml;tsmerkmal'; }
			else if( tag_type &   8 ) { row_class = "ac_zielgruppe";           row_type = 'Zielgruppe'; }
			else if( tag_type &  16 ) { row_class = "ac_abschlussart";         row_type = 'Abschlussart'; }
			else if( tag_type & 128 ) { row_class = "ac_thema";                row_type = 'Thema'; }
			else if( tag_type & 256 ) { row_class = "ac_anbieter";
										     if( tag_type &  0x20000 )	{ row_type = 'Beratungsstelle'; }
										else if( tag_type & 0x400000 )	{ row_type = 'Tr&auml;gerverweis'; }
										else							{ row_type = 'Tr&auml;ger'; }
									  }
			else if( tag_type & 512 ) { row_class = "ac_ort";                  row_type = 'Kursort'; }
			else if( tag_type & 1024) { row_class = "ac_merkmal"; 			   row_type = 'Kursmerkmal'; }
			else if( tag_type & 32768){ row_class = "ac_unterrichtsart";	   row_type = 'Unterrichtsart'; }
	
			/* frequency, end base type */
			if( tag_descr != '' ) row_postfix = ' (' + tag_descr + ')';
		
			if( tag_freq > 0)
			{
				row_count = '(' + tag_freq;
				row_count += (tag_freq == 1) ? '&nbsp;Kurs)' : '&nbsp;Kurse)';
			}
		
			/* additional flags */
			if( tag_type & 0x10000000 ) row_class += " ac_indent";
		
			else if( tag_type & 0x20000000 )
			{
				row_prefix = 'Meinten Sie ';
				tag_name = '"' + tag_name + '"?';
				row_class += " ac_suggestion";
			}
		
			/* help link */
			if( tag_help != 0 )
			{
				/* note: a single semicolon disturbs the highlighter as well as a single quote! */
				row_info = ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)" aria-label="Ratgeber zu ' + tag_name + '">&nbsp;i&nbsp;</a>';
			}
			
			tag_name = htmlspecialchars(tag_name);
		}
		
		// highlight search string
		var regex = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + request_term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		tag_name_highlighted = tag_name.replace(regex, "<em>$1</em>");

		return '<span class="row ' + row_class + '" data-value="' + tag_name + '">' + 
					'<span class="tag_name">' + row_prefix + tag_name_highlighted + '</span>' + 
					'<span class="tag_count">' + row_count + '</span>' +
				'</span>';
	}
	
	function ac_sourcecallback_anbieter(request, response_callback)
	{
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggest?q=" + encodeURIComponent(request_term) + "&type=anbieter&limit=512&timestamp=" + new Date().getTime();
		
		ac_sourcecallback_execute(request_term, url, response_callback);
	}
	
	function ac_sourcecallback_ort(request, response_callback)
	{
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggestplzort?q=" + encodeURIComponent(request_term) + "&type=ort&limit=512&timestamp=" + new Date().getTime();
		
		ac_sourcecallback_execute(request_term, url, response_callback, 'plzOrt');
	}

	function ac_sourcecallback(request, response_callback)
	{	
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggest?q=" + encodeURIComponent(request_term) + "&limit=512&timestamp=" + new Date().getTime();
		
		ac_sourcecallback_execute(request_term, url, response_callback);
	
	}
	
	function ac_sourcecallback_execute(request_term, url, response_callback, formatter)
	{
		// ask the server for suggestions
		$.get(url, function(data)
		{
		
			// Daten aufbereiten
			data = data.split("\n");
		
			var response_data = [];
			for(var i = 0; i < data.length; i++)
			{
				if(data[i] != "")
				{
					if(formatter == 'plzOrt')
					{
						var row = data[i].split("|");
						if(row[0] == 'headline')
						{
							// Zwischenüberschrift
							response_data.push({ label: '<strong class="headline">' + row[1] + '</strong>', value: '' });
						} else
						{
							response_data.push({ label: row[0], value: row[1] });
						}
					} 
					else
					{
						var row = data[i].split("|");
						response_data.push({ label: formatItem_v2(row, request_term), value: row[0] });
					}
				}
			}
			response_callback(response_data);
		});	
	}
	
	function ac_selectcallback_ort(event, ui, that) {
		
		event.preventDefault();
		
		if(that == undefined) {
			that = this;
		}
		
		if(ui.item.value != '') {
			that.value = ui.item.value;
		}
	}
	
	function ac_selectcallback_autosubmit(event, ui) {
		
		ac_selectcallback(event, ui, this, true);

	}

	function ac_selectcallback(event, ui, that, autosubmit) {
		
		event.preventDefault();
		
		if(that == undefined) {
			that = this;
		}
	
		// Standardverhalten (Value ins Eingabefeld schreiben) bei Überschrift und Mehrlink der Ergebnisliste ausschalten
		// Ebenso bei Klick auf "wisy_help"
		var $span = $(ui.item.label);
		var $to = $(event.toElement);
		if($span.hasClass('ac_noresults') || $span.hasClass('ac_headline') || $span.hasClass('ac_more') || $to.hasClass('wisy_help'))
		{
			event.preventDefault();
			if($span.hasClass('ac_more')) {
				clickAutocompleteMore(encodeURIComponent(that.value));
				return false;
			}
		} 
		else
		{
	
			// Neuen Autocomplete-Wert nach evtl. bereits vorhandenen einfügen
			var terms = split( that.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			that.value = terms.join( ", " );
			
			// remove trailing comma / comma and space
			if(that.value.substr(-1) == ' ') that.value = that.value.substring(0, that.value.length - 1);
			if(that.value.substr(-1) == ',') that.value = that.value.substring(0, that.value.length - 1);
			
		}
		// Auto-submit on select
		if(autosubmit) $('#wisy_searcharea form').submit();
	}

	function ac_focuscallback(event, ui)
	{
		event.preventDefault();
	}

	function initAutocomplete_v2() {
		var activeItemId = 'selectedOption';
		var ac_defaults = {
					html:		true
				,	focus:		ac_focuscallback
				,	appendTo: "#wisy_autocomplete_wrapper"
				, open: function(event, ui) { $(event.target).attr('aria-expanded', 'true').attr('aria-activedescendant', activeItemId); }
				,	close: function(event, ui) { $(event.target).attr('aria-expanded', 'false').attr('aria-activedescendant', ''); }
				, focus: function(event, ui) {
						$('#wisy_autocomplete_wrapper .ui-menu-item[aria-selected="true"]').attr('aria-selected', 'false').attr('id', '');
						$('#wisy_autocomplete_wrapper .ui-menu-item [data-value="' + ui.item.value + '"]').parents('.ui-menu-item').attr('aria-selected', 'true').attr('id', activeItemId);
				}
		}
		$(".ac_keyword").each(function()
		{
				var jqObj = $(this);
				var ac_options = {
						theinput:	jqObj
					, source:		ac_sourcecallback
					,	select:		ac_selectcallback_autosubmit
				};
				jqObj.autocomplete($.extend({}, ac_defaults, ac_options));
			}
		);
		$(".ac_keyword_ort").each(function()
		{
				var jqObj = $(this);
				var ac_options = {
						theinput:	jqObj
					, source:		ac_sourcecallback_ort
					,	select:		ac_selectcallback_ort
				};
				jqObj.autocomplete($.extend({}, ac_defaults, ac_options));
			}
		);
		$(".ac_keyword_anbieter").each(function()
		{
				var jqObj = $(this);
				var ac_options = {
						theinput:	jqObj
					, source:		ac_sourcecallback_anbieter
					,	select:		ac_selectcallback_autosubmit
				};
				jqObj.autocomplete($.extend({}, ac_defaults, ac_options));
			}
		);
	}
	$.widget( "custom.autocomplete", $.ui.autocomplete, {
		_renderMenu: function( ul, items )
		{
			var that = this;
			$.each( items, function( index, item )
			{
				that._renderItemData( ul, item );
			});
			
			// Streifen
			$( ul ).addClass('ac_results ac_results_v2').find( "li:odd" ).addClass( "ac_odd" );
			$( ul ).find( "li:even" ).addClass( "ac_even" );
			
			// WAI ARIA
			$( ul ).find( "li" ).attr('role', 'option').attr('aria-selected', 'false');
		},
		_resizeMenu: function()
		{
			this.menu.element.outerWidth(500);
		}
	});
	function split( val ) { return val.split( /,\s*/ ); }
	function extractLast( term ) { return split( term ).pop(); }
}
 

/******************************************************************************
jQuery UI Autocomplete HTML Extension 
Copyright 2010, Scott González (http://scottgonzalez.com)
Dual licensed under the MIT or GPL Version 2 licenses. 
http://github.com/scottgonzalez/jquery-ui-extensions
******************************************************************************/

if (jQuery.ui)
{
	(function( $ ) {

	var proto = $.ui.autocomplete.prototype,
		initSource = proto._initSource;

	function filter( array, term ) {
		var matcher = new RegExp( $.ui.autocomplete.escapeRegex(term), "i" );
		return $.grep( array, function(value) {
			return matcher.test( $( "<div>" ).html( value.label || value.value || value ).text() );
		});
	}

	$.extend( proto, {
		_initSource: function() {
			if ( this.options.html && $.isArray(this.options.source) ) {
				this.source = function( request, response ) {
					response( filter( this.options.source, request.term ) );
				};
			} else {
				initSource.call( this );
			}
		},

		_renderItem: function( ul, item) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( $( "<a></a>" )[ this.options.html ? "html" : "text" ]( item.label ) )
				.appendTo( ul );
		}
	});

	})( jQuery );
}


/*****************************************************************************
 * advanced search stuff
 *****************************************************************************/

function advEmbeddingViaAjaxDone()
{
	// Init autocomplete function
	// Use v2 if available
	if(jQuery.ui)
	{ 
		initAutocomplete_v2();
	}
	else
	{
		initAutocomplete();
	}

	// fade from normal to adv
	$("#wisy_searcharea").hide('slow'); 
	$("#advEmbedded").show('slow');
	
	// remove the loading indicatiot
	$("#wisy_searchinput").removeClass('ac_loading');
	
	// ajaxify  the "cancel" button
	$("#adv_cancel").click(function()
	{
		// fade from adv to normal
		$("#wisy_searcharea").show('slow');
		$("#advEmbedded").hide('slow', function(){ $("#advEmbedded").remove() });
		
		// done
		return false;
	});
}

function advEmbedViaAjax()
{
	// add the loading indicator
	$("#wisy_searchinput").addClass('ac_loading');
	
	// create query string
	var q   = $("#wisy_searchinput").val(); // for some reasons, q is UTF-8 encoded, so we use this charset as ie= below
	if( $("#wisy_beiinput").length ) {
		var bei = $("#wisy_beiinput").val(); if( bei != '' ) { q += ', bei:' + bei; }
		var km  = $("#wisy_kmselect").val(); if( km  != '' ) { q += ', km:'  + km;  }
	}
	
	// create and load the advanced options
	var justnow = new Date();
	var rnd = justnow.getTime();	
	$("#wisy_searcharea").after('<div id="advEmbedded" style="display: none;"></div>');
	$("#advEmbedded").load('advanced?ajax=1&ie=UTF-8&rnd='+rnd+'&q='+encodeURIComponent(q), advEmbeddingViaAjaxDone);
	return false;
}

function filterEmbeddingViaAjaxDone()
{
	// show filter form
	$("#filterEmbedded").removeClass('loading');
	$("#filterEmbedded .inner").slideDown(500);
	
	// remove the loading indicator
	$("#wisy_searchinput").removeClass('filter_loading');
	
	// ajaxify the "close" button
	$("#filter_close").click(function()
	{
		// hide filter form
		$('#wisy_contentarea').removeClass('filter_open');
		$('#wisy_filterlink').removeClass('active');
		$("#filterEmbedded").slideUp(300, function(){ $("#filterEmbedded").remove() });
		
		// done
		return false;
	});
	
	// ajaxify the "reset" button
	$('#filter_reset').click(function()
	{
		$('#filterEmbedded form').find('input')
			.filter(':text, :password, :file').val('').end()
			.filter(':checkbox, :radio').removeAttr('checked').end().end()
			.find('textarea').val('').end()
			.find('select').prop("selectedIndex", -1)
			.find('option:selected').removeAttr('selected');
		
		return false;
	});
}

function filterEmbedViaAjax()
{
	// Close Filter form if already open
	if($("#wisy_contentarea").hasClass('filter_open')) {
		$('#wisy_contentarea').removeClass('filter_open');
		$('#wisy_filterlink').removeClass('active');
		$("#filterEmbedded").slideUp(300, function(){ $("#filterEmbedded").remove() });
		return false;
	}
	
	// add the and active indicator
	$("#wisy_contentarea").addClass('filter_open');
	
	// Update Filterlink Button status
	$('#wisy_filterlink').addClass('active');
	
	// create query string
	var q = $("#wisy_searchinput").val(); // for some reasons, q is UTF-8 encoded, so we use this charset as ie= below
	if( $("#wisy_beiinput").length ) {
		var bei = $("#wisy_beiinput").val(); if( bei != '' ) { q += ', bei:' + bei; }
		var km  = $("#wisy_kmselect").val(); if( km  != '' ) { q += ', km:'  + km;  }
	}
	
	// create and load the filter options
	var justnow = new Date();
	var rnd = justnow.getTime();	
	$("#wisy_filterlink").after('<div id="filterEmbedded" class="loading"><div class="inner" style="display:none;"></div></div>');
	$("#filterEmbedded .inner").load('filter?ajax=1&ie=UTF-8&rnd='+rnd+'&q='+encodeURIComponent(q), filterEmbeddingViaAjaxDone);
	return false;
}


/*****************************************************************************
 * index pagination stuff
 *****************************************************************************/

function paginateViaAjaxDone()
{
	$("#wisy_searchinput").removeClass('ac_loading');
	initPaginateViaAjax();
}

function paginateViaAjax(theLink)
{
	$("#wisy_searchinput").addClass('ac_loading');
	$("#wisy_resultarea").load(theLink.href + '&ajax=1', paginateViaAjaxDone);
}

function initPaginateViaAjax()
{
	$("span.wisy_paginate a").click(function(){paginateViaAjax(this);return false;});
	$("a.wisy_orderby").click(function(){paginateViaAjax(this);return false;});
}

/*****************************************************************************
 * old edit stuff
 *****************************************************************************/

function ed(theAnchor)
{
	// remove focus -- do this first to avoid coming the new window into the background
	if( theAnchor.blur ) {
		theAnchor.blur();
	}

    // open new window;
    // no spaces in string, bug in function window.open()
    var w = window.open(theAnchor.href, theAnchor.target, 'width=610,height=580,resizable=yes,scrollbars=yes');
    
    // bring window to top
    if( !w.opener ) {
    	w.opener = self;
    }
    	
    if( w.focus != null ) {
    	w.focus();
    }
	
	// avoid standard hyperlink processing
    return false;
}

/*****************************************************************************
 * new edit stuff
 *****************************************************************************/

function editShowHide(jqObj, toShow, toHide)
{
	jqObj.parent().parent().find(toShow).show('fast');
	jqObj.parent().parent().find(toHide).hide();
}

function editFindDurchfRow(jqObj)
{
	var durchfRow = jqObj;
	var iterations = 0;
	while( 1 )
	{
		if( durchfRow.hasClass('editDurchfRow') )
			return durchfRow;
		durchfRow = durchfRow.parent();
		iterations++;
		if( iterations > 100 ) { alert('ERROR: editDurchfRow class not found ...'); return durchfRow; }
	}
}

function editDurchfLoeschen(jqObj)
{
	if( $('.editDurchfRow').size() == 1 )
	{
		alert("Diese Durchführung kann nicht gelöscht werden, da ein Kurs mindestens eine Durchführung haben muss.\n\nWenn Sie den Kurs komplett löschen möchten, verwenden Sie die Option \"Kurs löschen\" ganz unten auf dieser Seite.");
		return;
	}
	else if( confirm("Diese Durchführung löschen?") )
	{
		editFindDurchfRow(jqObj).remove();
	}
}
function editDurchfKopieren(jqObj)
{
	var durchfBase = editFindDurchfRow(jqObj);

	var clonedObj = durchfBase.clone();
	clonedObj.find('.hiddenId').val('0');

	durchfBase.after(clonedObj);	
}

function editKursLoeschen(jqObj)
{
	if( confirm("Wenn Sie einen Kurs löschen möchten, wird zunächst ein Sperrvermerk gesetzt; beim nächsten Index-Update wird der Kurs dann inkl. aller Durchführungen komplett gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden!\n\nDen kompletten Kurs inkl. ALLER Durchführungen löschen?") )
	{
		return true;
	}
	return false;
}

function editWeekdays(jqObj)
{
	// jqObj ist der Text; ein click hierauf soll das nebenliegende <input type=hidden> ändern
	var hiddenObj = jqObj.parent().find('input');
	if( hiddenObj.val() == '1' )
	{
		hiddenObj.val('0');
		jqObj.addClass   ('wisy_editweekdaysnorm');
		jqObj.removeClass('wisy_editweekdayssel');
	}
	else
	{
		hiddenObj.val('1');
		jqObj.addClass   ('wisy_editweekdayssel');
		jqObj.removeClass('wisy_editweekdaysnorm');
	}
}

/*****************************************************************************
 * feedback stuff
 *****************************************************************************/

function ajaxFeedback(rating, descr)
{
	var url = 'feedback?url=' + encodeURIComponent(window.location) + '&rating=' + rating + '&descr=' + encodeURIComponent(descr);
	$.get(url);
}

function describeFeedback()
{
	var descr = $('#wisy_feedback_descr').val();
	descr = $.trim(descr);
	if( descr == '' )
	{
		alert('Bitte geben Sie zuerst Ihren Kommentar ein.');
	}
	else
	{
		$('#wisy_feedback_line2').html('<p class="wisy_feedback_thanksforcomment">Vielen Dank für Ihren Kommentar!</p>');
		ajaxFeedback(0, descr); // Kommentar zur Bewertung hinzufügen; die Bewertung selbst (erster Parameter) wird an dieser Stelle ignoriert!
	}
}

function sendFeedback(rating)
{
	$('#wisy_feedback_yesno').html('<strong class="wisy_feedback_thanks">Vielen Dank für Ihr Feedback!</strong>');
	
	if( rating == 0 )
	{
		$('#wisy_feedback').append(
				'<div id="wisy_feedback_line2">'
			+		'<p>Bitte schildern Sie uns noch kurz, warum diese Information nicht hilfreich war und was wir besser machen können:</p>'
				+	'<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="2" cols="20"></textarea><br />'
				+	'Wenn Sie eine Antwort wünschen, geben Sie bitte auch Ihre E-Mail-Adresse an.<br />'
				+	'<input id="wisy_feedback_submit" type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+	'</div>'
		);
		$('#wisy_feedback_descr').focus();
	}
	else 
	{
		$('#wisy_feedback').append(
				'<div id="wisy_feedback_line2">'
			+		'<p>Bitte schildern Sie uns kurz, was hilfreich war, damit wir Bew&auml;hrtes bewahren und ausbauen:</p>'
				+	'<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="2" cols="20"></textarea><br />'
				+	'Wenn Sie eine Antwort wünschen, geben Sie bitte auch Ihre E-Mail-Adresse an.<br />'
				+	'<input id="wisy_feedback_submit" type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+	'</div>'
		);
		$('#wisy_feedback_descr').focus();
	}
	
	ajaxFeedback(rating, '');
}

function initFeedback()
{
	$('.wisy_allow_feedback').after(
			'<div id="wisy_feedback" class="noprint" role="contentinfo">'
		+		'<span class="wisy_feedback_question">War diese Information hilfreich?</span> '
		+		'<span id="wisy_feedback_yesno"><a href="javascript:sendFeedback(1)">Ja</a> <a href="javascript:sendFeedback(0)">Nein</a></span>'
		+	'</div>'
	);
}


/*****************************************************************************
 * the simple editor functions
 *****************************************************************************/


function add_chars(jqObj, chars1, chars2)
{
	var jqTextarea = jqObj.parent().parent().find('textarea'); // see (**) 
	jqTextarea.surroundSelectedText(chars1, chars2);
}


/*****************************************************************************
 * the keywords() functions in the ratgeber
 *****************************************************************************/

function wisy_glskeyexp()
{
	var jqAClick = $(this);
	var jqTrClick = jqAClick.parent().parent();
	
	var action = jqAClick.attr('data-glskeyaction');
	var indentClick = parseInt(jqTrClick.attr('data-indent'));
	
	var show = true;
	if( action == 'shrink' )
	{
		jqAClick.html('&nbsp;&#9654;');
		jqAClick.attr('data-glskeyaction', 'expand');
		show = false;

	}
	else if( action == 'expand' )
	{
		jqAClick.html('&#9660;');
		jqAClick.attr('data-glskeyaction', 'shrink');
		jqAClick.attr('aria-label', 'Unterthemen zuklappen');
		show = true;
	}
	
	jqTrCurr = jqTrClick.next();
	var indentShow = indentClick + 1;
	while( jqTrCurr.length )
	{
		var indentCurr = jqTrCurr.attr('data-indent');
		if( indentCurr <= indentClick ) {
			break; // done, this row is no child
		}
		
		if( show && indentCurr==indentShow ) 
		{ 
			jqTrCurr.show();
		}
		else 
		{ 
			jqTrCurr.hide();
		}
		
		jqACurr = jqTrCurr.find('a.wisy_glskeyexp');
		jqACurr.html('&nbsp;&#9654;');
		jqACurr.attr('data-glskeyaction', 'expand');
		jqAClick.attr('aria-label', 'Unterthemen aufklappen');	
		
		jqTrCurr = jqTrCurr.next();
	}
	
	return false; // no default link processing
}

function initRatgeber()
{
	$("a.wisy_glskeyexp").click(wisy_glskeyexp);
}

/*****************************************************************************
 * Responsive functions for core50
 *****************************************************************************/

function initResponsive()
{
	// Remove nojs class from body in case the remove function directly after the <body> tag is missing
	$('body.nojs').removeClass('nojs').addClass('yesjs');
	
	// Toggle mobile nav on __MOBILNAVLINK__ click
	$('#nav-link').on('click', function() {
		window.scrollTo(0, 0);
		$('body').toggleClass('navshowing');
	});

	// Navigation Unterpunkte öffnen und schließen mobil
	$('.nav_menu a').on('click', function() {
		$firstUl = $(this).siblings('ul').first();
		if($firstUl.length) {
			if($firstUl.hasClass('open')) {
				$firstUl.removeClass('open').stop(true,true).slideUp();
				$(this).parent().removeClass('open');
			} else {
				$firstUl.children('li').show();
				$firstUl.addClass('open').stop(true,true).hide().slideDown();
				$(this).parent().addClass('open');
			}
			return false;
		}
	});
}

/*****************************************************************************
 * Filter functions for core51
 *****************************************************************************/

function initFilters() {
	// Filter an Kursliste öffnen und schließen
	$('.wisyr_filtergroup, .wisyr_filtergroup > legend').on('click', function(e) {
		if(e.target !== e.currentTarget && !$(e.target).hasClass('ui-selectmenu-text') && !$(e.target).hasClass('ui-selectmenu-button')) return;

		// In Desktopansicht nicht auf Klicks auf ui-selectmenu-text reagieren
		var isMobile = $(window).width() < 761;
		if(!isMobile && ($(e.target).hasClass('ui-selectmenu-text') || $(e.target).hasClass('ui-selectmenu-button'))) return;

		if($(e.target).hasClass('wisyr_filtergroup') || $(e.target).hasClass('ui-selectmenu-text') || $(e.target).hasClass('ui-selectmenu-button')) {
			$group = $(this);
		} else {
			$group = $(this).parent();
		}
	
		var wasActive = $group.hasClass('active');
		$('.wisyr_filterform fieldset.active').removeClass('active');
		$('.wisyr_filterform').removeClass('subActive');
		if(wasActive) {
			$(document).off('click.filtergroup');
		} else {
			$group.addClass('active');
			$('.wisyr_filterform').addClass('subActive');
		
			// Filter an Kursliste schließen wenn außerhalb geklickt wird
			$(document).on('click.filtergroup', function(event) {
				$target = $(event.target);
			
				if($target.closest('.Zebra_DatePicker').length) return;
				if($target.hasClass('clear_btn')) return;
				if($target.closest('.wisyr_filtergroup').length) return;
				if($target.closest('.ui-autocomplete').length) return;
				if($target.closest('.ui-menu-item').length) return;
			
				$(document).off('click.filtergroup');
				$('.wisyr_filtergroup.active').removeClass('active');
				$('.wisyr_filterform').removeClass('subActive');
			});
		}
	
		// Sonderfall Sortierfeld
		if($group.hasClass('filter_sortierung')) {
			// Open selectmenus if present
			if(wasActive) {
				$group.find('.wisyr_selectmenu').selectmenu('close');
			} else {
				$group.find('.wisyr_selectmenu').selectmenu('open');
			}
		}
		e.stopPropagation();
		return false;
	});

	// Filtervorschläge befüllen automatisch Inputs
	$('.wisyr_filter_autofill input[type="radio"]').on('change', function() {
		$this = $(this);
		$target = $($this.data('autofilltarget'));
		if($target.length) {
			$target.val($this.data('autofillvalue'));
		}
	});

	// Filter automatisch abschicken
	$('.wisyr_filter_autosubmit .filter_submit').hide();
	$('.wisyr_filter_autosubmit input, .wisyr_filter_autosubmit select').on('change', function() {
	
		// Freie Eingaben zurücksetzen bei autosubmit
		$(this).parents('.wisyr_filter_autosubmit')
			.siblings('.wisyr_filter_autoclear')
			.find('input:not([type=submit]), select')
			.val('');
	
		$('.wisyr_filterform form').submit();
	});

	// Filterlink Checkboxen automatisch abschicken
	$('.filter_checkbox').on('change', function() {
		var target = $(this).siblings('a').attr('href');
		if(target != '') $(location).attr('href', target);
	});

	// Suchfeld "clear input button"
	$('#wisy_searchinput').on('input', function() {
		updateClearInput($('#wisy_searchinput'), $('.wisyr_searchinput'));
	});
	updateClearInput($('#wisy_searchinput'), $('.wisyr_searchinput'));

	// Generischer "clear input button" für Filter
	$('.filter_clearbutton_wrapper').each(function(i, el) {
		$wrapper = $(this);
		$input = $wrapper.children('input');
	
		updateClearInput($input, $wrapper);
		$input.on('input', function() {
			$input = $(this);
			$wrapper = $input.parents('.filter_clearbutton_wrapper');
			updateClearInput($input, $wrapper);
		});
	});

	function updateClearInput($el, $wrapper) {
		if($el) {
			if($el.val() && $el.val().length) {
				if($wrapper.children('.clear_btn').length == 0) {
					$wrapper.append('<div class="clear_btn" aria-label="Eingabe löschen"></div>');
					$wrapper.children('.clear_btn').one('click', function() {
						$el.val('');
						$wrapper.children('.clear_btn').remove();
						$el.focus();
					});
				}
			} else {
				$wrapper.children('.clear_btn').remove();
			}
		}
	}

	// Autosuggest Field same width as input field:
	$('#wisy_searchinput').on('autocompleteopen', function(event) {
		$('#wisy_searchinput').autocomplete("widget").width($('#wisy_searchinput').outerWidth());
	});

	// Autosuggest Field in Filter same width as input field:
	$('#filter_bei').on('autocompleteopen', function(event) {
		$('#filter_bei').autocomplete("widget").width($('#filter_bei').outerWidth());
	});

	// Ortsfilter Umkreis nur enablen wenn Ort eingegeben ist
	$('#filter_bei').on('input', function() {
		updateUmkreisFilter();
	});
	updateUmkreisFilter();

	function updateUmkreisFilter() {
		$bei = $('#filter_bei');
		if($bei && $bei.length) {
			if($bei.val() != '') {
				$('.filter_ortundumkreis_umkreis input[type="radio"]').prop('disabled', false);
			} else {
				$('.filter_ortundumkreis_umkreis input[type="radio"]').prop('disabled', true);
			}
		}
	}

	// Sortierung Selectfeld via jQuery UI stylebar machen
	$(".filter_weiterekriterien .wisyr_selectmenu").selectmenu();
	$(".filter_sortierung .wisyr_selectmenu").selectmenu({
		change: function(event, ui) { $('.wisyr_filterform form').submit() }
	});

	// Zebra Datepicker für Datum in Filtern
	$(".wisyr_datepicker").Zebra_DatePicker(
		{
			format: 'd.m.Y',
			days: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
			months: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
			lang_clear_date: 'Auswahl entfernen',
			show_icon: false,
			open_on_focus: true,
			show_select_today: false
		}
	);
}

function initFiltersMobile() {
	$("#wisy_filterlink").on('click', function(e) {
		if(e.target !== e.currentTarget) return;
		$('body').addClass('wisyr_filterform_active');
		e.stopPropagation();
		return false;
	});
	$('.wisyr_filterform_header').on('click', function(e) {
		if(e.target !== e.currentTarget) return;
		$('body').removeClass('wisyr_filterform_active');
		e.stopPropagation();
		return false;
	});

	// Zweite Filterebene wird mobil wie erste Filterebene behandelt
	$('.wisyr_filtergroup.filter_weiterekriterien > .filter_inner > fieldset, .wisyr_filtergroup.filter_weiterekriterien > .filter_inner > fieldset > legend').on('click', function(e) {
		// Workaround für "Alle".
		// Mobil in zweiter Filterebene passiert nichts wenn dieser Filter nicht gesetzt ist und "Alle" geklickt wird.
		var isMobile = $(window).width() < 761;
		if (isMobile && $(e.target).text() == 'Alle' && $(e.target).closest('fieldset').children('.wisyr_selectmenu').children('option:selected').text() == 'Alle') {
			// Close Filter
			$group = $(e.target).closest('fieldset');
			$group.removeClass('active');
			$('.wisyr_filterform').removeClass('subActive');
			// Close selectmenus if present
			$group.find('.wisyr_selectmenu').selectmenu('close');
		}
		if(e.target !== e.currentTarget) return;
	
		if($(e.target).is('fieldset')) {
			$group = $(this);
		} else {
			$group = $(this).parent();
		}
	
		if($group.hasClass('active')) {	
			$group.removeClass('active');
			$('.wisyr_filterform').removeClass('subActive');
			// Close selectmenus if present
			$group.find('.wisyr_selectmenu').selectmenu('close');
		} else {
			$group.addClass('active');
			$('.wisyr_filterform').addClass('subActive');
			// Open selectmenus if present
			$group.find('.wisyr_selectmenu').selectmenu('open');
			if ($group.hasClass('no_autosubmit_mobile')) {
				$group.find('.wisyr_selectmenu').on('selectmenuselect', function(event, ui) {
					$(event.toElement).parent().parent().find('.wisyr_selectedmobile').removeClass('wisyr_selectedmobile');
					$(event.toElement).parent().addClass('wisyr_selectedmobile');
				});
			} else {
				$group.find('.wisyr_selectmenu').one('selectmenuchange', function(event, ui) {
					$('.wisyr_filterform form').submit();
				});
			}
		}
	
		e.stopPropagation();
	});
}

/*****************************************************************************
 * Accessible Menus: simple and complex
 *****************************************************************************/

function initAccessibleMenus() {
	// Add functionality for "simple" menus, accessible via tab-navigation
	$(".wisyr_menu_simple > ul").initMenuSimple();
	
	// Add functionality for "complex" menus, accessible via WAI-ARIA assisted arrow-navigation
	$(".wisyr_menu_complex > ul").initMenuComplex();
}

$.fn.initMenuSimple = function(settings) {
	settings = jQuery.extend({ menuHoverClass: "wisyr_show_menu" }, settings);
	
	// Add tabindex 0 to top level spans because otherwise they can't be tabbed to
	$(this).find("> li > .nav_no_link").attr("tabIndex", 0);
	
	var top_level_links = $(this).find("> li > a, > li > .nav_no_link");

	// Set tabIndex to -1 so that top_level_links can't receive focus until menu is open
	$(top_level_links)
		.next("ul")
		.attr("data-test", "true")
		.attr({ "aria-hidden": "true" })
		.find("a")
		.attr("tabIndex", -1);

	// Show and hide on focus
	$(this).find('a, .nav_no_link').on('focus', function() {
		$(this)
			.closest("ul")
			.find("." + settings.menuHoverClass)
			.attr("aria-hidden", "true")
			.removeClass(settings.menuHoverClass)
			.find("a, .nav_no_link")
			.attr("tabIndex", -1);

		$(this)
			.next("ul")
			.attr("aria-hidden", "false")
			.addClass(settings.menuHoverClass)
			.find("a, .nav_no_link")
			.attr("tabIndex", 0);
	});

	// Hide menu if the user tabs out of the navigation
	$(this)
		.find("a, .nav_no_link")
		.last()
		.keydown(function(e) {
			if (e.keyCode == 9) {
				$("." + settings.menuHoverClass)
					.attr("aria-hidden", "true")
					.removeClass(settings.menuHoverClass)
					.find("a, .nav_no_link")
					.attr("tabIndex", -1);
			}
	});

	// Hide menu if click occurs outside of navigation
	$(document).on('click', function() {
		$("." + settings.menuHoverClass)
			.attr("aria-hidden", "true")
			.removeClass(settings.menuHoverClass)
			.find("a, .nav_no_link")
			.attr("tabIndex", -1);
	});

	$(this).on('click', function(e) {
		e.stopPropagation();
	});
};

$.fn.initMenuComplex = function(settings) {
	if (typeof ARIAMenuBar !== "undefined") {
		var myMB = new ARIAMenuBar({
			topMenuBarSelector: '.wisyr_menu_complex ul[role="menubar"]',
			hiddenClass: 'hidden',

			// Click handler that executes whenever an A tag that includes role="menuitem" is clicked
			handleMenuItemClick: function(ev) {
				top.location.href = this.href;
			},

			// Handle opening of dynamic submenus
			openMenu: function(subMenu, menuContainer) {
				$(subMenu).removeClass('hidden');

				// Focus callback for use when adding animation effect; must be moved into animation callback after animation finishes rendering
				if (myMB.cb && typeof myMB.cb === 'function'){
					myMB.cb();
					myMB.cb = null;
				}
			},

			// Handle closing of dynamic submenus
			closeMenu: function(subMenu) {
				$(subMenu).addClass('hidden');
			},

			// Accessible offscreen text to specify necessary keyboard directives for non-sighted users.
			dualHorizontalTxt: 'Press Enter to navigate to page, or Down to open dropdown',
			dualVerticalTxt: 'Press Enter to navigate to page, or Right to open dropdown',
			horizontalTxt: 'Press Down to open dropdown',
			verticalTxt: 'Press Right to open dropdown'
		});
	}
};

/*****************************************************************************
 * main entry point
 *****************************************************************************/

var main_Initialized = false;
$().ready(function()
{
	if(main_Initialized) return;
	main_Initialized = true;
	
	// Init autocomplete function
	// Use v2 if available
	if(jQuery.ui)
	{
		initAutocomplete_v2();
	}
	else
	{
		initAutocomplete();
	}	
	
	// handle "advanced search" via ajax
	$("#wisy_advlink").click(advEmbedViaAjax);
	
	
	// handle "paginate" via ajax
	// currently, we NO NOT do so as this fails the "back button" to work correctly. Eg. the order and the page is not set when clicking a result and going back to the list.
	// we should add the parameter behind the hash "#ordere=" or sth. like that.
	// initPaginateViaAjax();
	
	// init feedback and fav. stuff
	initFeedback();
	fav_init();
	
	// init ratgeber stuff
	initRatgeber();
	
	// init responsive stuff
	initResponsive();
	
	// init accessibility stuff
	initAccessibleMenus();
	
	// init filter stuff
	initFilters();
	initFiltersMobile();
	
});


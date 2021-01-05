
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
a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":"",a.sameSite?"; sameSite="+a.sameSite:"; sameSite=Strict"].join("")}var c=o.raw?i:s;var h=t.cookie.split("; ");for(var p=0,d=h.length;p<d;p++)
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

window.sameSiteDefault = "Strict";

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
			
			// Disable Google Analytics -- https://developers.google.com/analytics/devguides/collection/analyticsjs/user-opt-out
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
		str += '<span class="wisyr_fav_anzahl">Ihre Merkliste enth&auml;lt ' + cnt + (cnt==1? ' Eintrag ' : ' Eintr&auml;ge ') + '</span>';
		if( mailto != '' ) 
		{
			str += '<a class="fav_functions_mailsend" href="' + mailto + '" title="Merkliste per E-Mail versenden" class="fav_send">Merkliste per E-Mail versenden</a> ';
		}
		str += ' <a class="fav_functions_deleteall" href="javascript:fav_delete_all()" title="Gesamte Merkliste l&ouml;schen">Gesamte Merkliste l&ouml;schen</a>';
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
			str += '<span class="fav_item fav_selected noprint">&#9733;</span> ';
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
		window.cookieconsent.popup.open();
			return false;
		} else if($.cookie('cconsent_merkliste') != "allow") {
		  alert("Um diese Funktion nutzen zu k"+oe+"nnen, m"+ue+"ssen Sie dem Speichern von Cookies f"+ue+"r diese Funktion zustimmen (im Cookie-Hinweisfenster).");
		  hightlightCookieConsentOption('merkliste');
		  window.cookieconsent.popup.open();
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
	if( !confirm('Gesamte Merkliste l'+oe+'schen?') )
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
		var cls = fav_is_favourite(id)? 'fav_item fav_selected noprint' : 'fav_item noprint';
		$(this).parent().append(' <span class="'+cls+'" onclick="fav_click(this, '+id+');" title="Angebot merken">&#9733;</span>');
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
	location.href = 'search?show=tags&q=' + tag_name_encoded; // ie=UTF-8&
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
			row_postfix = (tag_freq==1? '1 Angebot' : ('' + tag_freq + ' Angebote')) + row_preposition + row_postfix;
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
			 ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)">&nbsp;i&nbsp;</a>';
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
		row_count_prefix = (tag_freq == 1) ? ' Angebot zum' : ' Angebote zum';
		row_info = '';
		row_prefix = '';
		row_postfix = '';

		if( tag_help == -1 )
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
			tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name).replace('/&/', '%26') + '&#39;)">' + tag_descr + '</a>';
		}
		else
		{
			/* base type */
				 if( tag_type &   1 ) { row_class = "ac_abschluss";            row_type = 'Abschluss'; }
			else if( tag_type &   2 ) { row_class = "ac_foerderung";           row_type = 'F&ouml;rderung'; row_count_prefix = (tag_freq == 1) ? ' Angebot zur' : ' Angebote zur';  }
			else if( tag_type &   4 ) { row_class = "ac_qualitaetszertifikat"; row_type = 'Qualit&auml;tsmerkmal'; }
			else if( tag_type &   8 ) { row_class = "ac_zielgruppe";           row_type = 'Zielgruppe'; row_count_prefix = (tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
			else if( tag_type &  16 ) { row_class = "ac_abschlussart";         row_type = 'Abschlussart'; row_count_prefix = (tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
			else if( tag_type & 128 ) { row_class = "ac_thema";                row_type = 'Thema'; }
			else if( tag_type & 256 ) { row_class = "ac_anbieter";
										     if( tag_type &  0x20000 )	{ row_type = 'Beratungsstelle';  row_count_prefix = (tag_freq == 1) ? ' Angebot von der' : ' Angebote von der';  }
										else if( tag_type & 0x400000 )	{ row_type = 'Tr&auml;gerverweis'; }
										else							{ row_type = 'Tr&auml;ger'; row_count_prefix = (tag_freq == 1) ? ' Angebot vom' : ' Angebote vom'; }
									  }
			else if( tag_type & 512 ) { row_class = "ac_ort";                  row_type = 'Angebotsort'; row_count_prefix = (tag_freq == 1) ? ' Angebot am' : ' Angebote am'; }
			else if( tag_type & 1024) { row_class = "ac_merkmal"; 			   row_type = 'Angebotsmerkmal'; }
			else if( tag_type & 32768){ row_class = "ac_unterrichtsart";	   row_type = 'Unterrichtsart'; row_count_prefix = (tag_freq == 1) ? ' Angebot zur' : ' Angebote zur'; }
	
			/* frequency, end base type */
			if( tag_descr != '' ) row_postfix = ' (' + tag_descr + ')';
		
			if( tag_freq > 0 )
			{
				row_count = tag_freq;
				if(row_count_prefix == '')
				{
					row_count += (tag_freq == 1) ? ' Angebot' : ' Angebote';
				} 
				else
				{
					row_count += row_count_prefix;
				}
			}
		
			/* additional flags */
			if( tag_type & 0x10000000 ) row_class += " ac_indent";
		
			else if( tag_type & 0x20000000 )
			{
				row_prefix = 'Meinten Sie: ';
				row_class += " ac_suggestion";
			}
		
			/* help link */
			if( tag_help != 0 )
			{
				/* note: a single semicolon disturbs the highlighter as well as a single quote! */
				row_info = ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)">&nbsp;i&nbsp;</a>';
			}
			
			tag_name = htmlspecialchars(tag_name);
		}
		
		// highlight search string
		var regex = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + request_term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		tag_name = tag_name.replace(regex, "<em>$1</em>").replace('&amp;', '&');
	
		return '<span class="row '+row_class+'">' + 
					'<span class="tag_name">' + row_prefix + tag_name + row_postfix + '</span>' + 
					'<span class="tag_count">' + row_count + '</span>' +
					'<span class="tag_type">' + row_type + '</span>' +
					'<span class="tag_info">' + row_info + '</span>' +
				'</span>';
	}

	function ac_sourcecallback(request, response_callback)
	{	
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "autosuggest?q=" + encodeURIComponent(request_term) + "&limit=512&timestamp=" + new Date().getTime();
	
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
					var row = data[i].split("|");
					response_data.push({ label: formatItem_v2(row, request_term), value: row[0] });
				}
			}
			response_callback(response_data);
		});
	}

	function ac_selectcallback(event, ui) {
	
		// Standardverhalten (Value ins Eingabefeld schreiben) bei Ueberschrift und Mehrlink der Ergebnisliste ausschalten
		// Ebenso bei Klick auf "wisy_help"
		var $span = $(ui.item.label);
		var $to = $(event.toElement);
		if($span.hasClass('ac_headline') || $span.hasClass('ac_more') || $to.hasClass('wisy_help'))
		{
			event.preventDefault();
			if($span.hasClass('ac_more')) $span.find('a').click();
		} 
		else
		{
	
			// Neuen Autocomplete-Wert nach evtl. bereits vorhandenen einfuegen
			var terms = split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			this.value = terms.join( ", " );
			return false;
		}
	}

	function ac_focuscallback(event, ui)
	{
		event.preventDefault();
	}

	function initAutocomplete_v2() {
		$(".ac_keyword").each(function()
		{
				var jqObj = $(this);
				jqObj.autocomplete(
				{
						source:		ac_sourcecallback
					,	theinput:	jqObj
					,	html:		true
					,	select:		ac_selectcallback
					,	focus:		ac_focuscallback 
				});
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

//prevent empty search (< 2 chars): on hompage: output message, on other page: search for all courses
function preventEmptySearch(homepage) {
 
  // only if no other submit event is attached to search submit button:
  if( Array.isArray($("#wisy_searcharea form[action=search]")) && typeof $._data( $("#wisy_searcharea form[action=search]")[0], "events" ) == 'undefined' ) {
    
   $('#wisy_searcharea form[action=search]').on('submit', function(e) {
    e.preventDefault();
    var len = $('#wisy_searchinput').val().length;
    var emptyvalue = $('#wisy_searchinput').data('onemptyvalue');
    
       if ($(location).attr('pathname') == homepage) {
            if (len > 1) {
                   this.submit(); // default: normal search
               } else {
                if( emptyvalue != '' ) {
                   $('#wisy_searchinput').val(emptyvalue);
                   this.submit();
                } else {
                  alert('Bitte geben Sie einen Suchbegriff an (mindesten 2 Buchstaben)');
                }
            }
       } else {
           if(len < 2) {
            if( emptyvalue != '' )
             $('#wisy_searchinput').val(emptyvalue);
            else
             $('#wisy_searchinput').val("zeige:kurse");
           }
           
           // default: normal search on other than homepage
           this.submit();
       }
   });
   
  }
}

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
 * dropdown stuff
 *****************************************************************************/

$.fn.dropdown = function()
{
	// needed only for IE6 dropdown menu
	$(this).hover(function(){
		$(this).addClass("hover");
		$('> .dir',this).addClass("open");
		$('ul:first',this).css('visibility', 'visible');
	},function(){
		$(this).removeClass("hover");
		$('.open',this).removeClass("open");
		$('ul:first',this).css('visibility', 'hidden');
	});

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
		alert("Diese Durchf"+ue+"hrung kann nicht gel"+oe+"scht werden, da ein Angebot mindestens eine Durchf"+ue+"hrung haben muss.\n\nWenn Sie den Angebot komplett l"+oe+"schen m"+oe+"chten, verwenden Sie die Option \"Angebot l"+oe+"schen\" ganz unten auf dieser Seite.");
		return;
	}
	else if( confirm("Diese Durchf"+ue+"hrung l"+oe+"schen?") )
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
	if( confirm("Wenn Sie einen Angebot l"+oe+"schen m"+oe+"chten, wird zun"+ae+"chst ein Sperrvermerk gesetzt; beim n"+ae+"chsten Index-Update wird der Angebot dann inkl. aller Durchf"+ue+"hrungen komplett gel"+oe+"scht. Dieser Vorgang kann nicht r"+ue+"ckg"+ae+"ngig gemacht werden!\n\nDen kompletten Angebot inkl. ALLER Durchf"+ue+"hrungen l"+oe+"schen?") )
	{
		return true;
	}
	return false;
}

function editWeekdays(jqObj)
{
	// jqObj ist der Text; ein click hierauf soll das nebenliegende <input type=hidden> aendern
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

function resetPassword(aID, pflegeEmail) {
	$.ajax({
	type: "POST",
	url: "/edit",
	data: { action: "forgotpw", pwsubseq: "1", as: aID },
	success: function(data) { alert( "Wir haben Ihnen eine E-Mail mit einem Link zur Passwortgenerierung an "+pflegeEmail+" gesandt!\n\nSollte in wenigen Minuten keine E-Mail eintreffen, pruefen Sie bitte die E-Mailadresse bzw. wenden Sie sich bitte an den Portal-Betreiber."); }
  });
}

/*****************************************************************************
 * feedback stuff
 *****************************************************************************/

function ajaxFeedback(rating, descr, name, email)
{
	var url = 'feedback?url=' + encodeURIComponent(window.location) + '&rating=' + rating + '&descr=' + encodeURIComponent(descr) + '&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email);
	$.get(url);
}

function describeFeedback()
{
	var descr = $('#wisy_feedback_descr').val();
	var name = $('#wisy_feedback_name').val();
	var email = $('#wisy_feedback_email').val();
	
	descr = $.trim(descr);
	name = $.trim(name);
	email = $.trim(email);
	
	if( descr == '' )
	{
		alert('Bitte geben Sie zuerst Ihren Kommentar ein.');
	}
	else
	{
		$('#wisy_feedback_line2').html('<strong style="color: green;">Vielen Dank f'+ue+'r Ihren Kommentar!</strong>');
		ajaxFeedback(0, descr, name, email); // Kommentar zur Bewertung hinzufuegen; die Bewertung selbst (erster Parameter) wird an dieser Stelle ignoriert!
	}
}

function sendFeedback(rating)
{
	$('#wisy_feedback_yesno').html('<strong class="wisy_feedback_thanks">Vielen Dank f'+ue+'r Ihr Feedback!</strong>');
	
	if( rating == 0 )
	{
		$('#wisy_feedback').append(
				'<div id="wisy_feedback_line2">'
			+		'<p>Bitte schildern Sie uns noch kurz, warum diese Seite nicht hilfreich war und was wir besser machen k&ouml;nnen:</p>'
				+	'<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="2" cols="20"></textarea><br />'
				+	'<br><b>Wenn Sie eine Antwort w&uuml;nschen</b>, geben Sie bitte auch Ihre E-Mail-Adresse an (optional).<br />Wir verwenden Ihre E-Mailadresse und ggf. Name nur, um Ihr Anliegen zu bearbeiten und l&ouml;schen diese personenbezogenen Daten alle 12 Monate.<br><br>'
				+	'<label for="wisy_feedback_name">Name (optional): </label><input type="text" id="wisy_feedback_name" name="wisy_feedback_name">&nbsp; <label for="wisy_feedback_email">E-Mailadresse (optional): </label><input type="text" id="wisy_feedback_email" name="wisy_feedback_email"><br><br>'
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
				+	'<br><b>Wenn Sie eine Antwort w&uuml;nschen</b>, geben Sie bitte auch Ihre E-Mail-Adresse an (optional).<br />Wir verwenden Ihre E-Mailadresse und ggf. Name nur, um Ihr Anliegen zu bearbeiten und l&ouml;schen diese personenbezogenen Daten alle 12 Monate.<br><br>'
				+	'<label for="wisy_feedback_name">Name (optional): </label><input type="text" id="wisy_feedback_name" name="wisy_feedback_name">&nbsp; <label for="wisy_feedback_email">E-Mailadresse (optional): </label><input type="text" id="wisy_feedback_email" name="wisy_feedback_email"><br><br>'
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
			'<div id="wisy_feedback" class="noprint">'
		+		'<span class="wisy_feedback_question">War diese Seite hilfreich?</span> '
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
	$('body.nojs').removeClass('nojs');
	
	// Toggle mobile nav on __MOBILNAVLINK__ click
	$('#nav-link').on('click', function() {
		window.scrollTo(0, 0);
		$('body').toggleClass('navshowing');
	});

	// Navigation Unterpunkte oeffnen und schliessen mobil
	$('#themenmenue a').on('click', function() {
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
 * SEO, alternative means for search
 *****************************************************************************/

// calculate weighted size (by tag usage within portal)
$(document).ready(function() {
 $("#sw_cloud span").each( function(){ 
  weight = $(this).attr("data-weight"); 
  fontsize = Math.floor($(this).find("a").css("font-size").replace('px', ''));
  $(this).find("a").css("font-size", parseInt(fontsize)+parseInt(weight)+'px');
 });
});

/*****************************************************************************
 * main entry point
 *****************************************************************************/

var main_Initialized = false;
$().ready(function()
{
    if(main_Initialized) return;
    main_Initialized = true;

	// check for forwarding
	var askfwd = $('body').attr('data-askfwd');
	if( typeof askfwd != 'undefined' ) {
		if( confirm('Von dieser Webseite gibt es auch eine Mobilversion unter ' + askfwd + '. M'+oe+'chten Sie jetzt dorthin wechseln?') ) {
			window.location = askfwd;
			return;
		}
	}
	
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

	// init dropdown
	if ($.browser && $.browser.msie && $.browser.version == '6.0') {
    	$("ul.dropdown li").dropdown();
	}	
	
	// handle "advanced search" via ajax
	$("#wisy_advlink").click(advEmbedViaAjax);
	
	// handle "filter search" via ajax
	$("#wisy_filterlink").click(filterEmbedViaAjax);
	
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
	
	// append parameter for searches triggered by human interaction
	if($("#wisy_searchbtn")) {
	  $("#wisy_searchbtn").click(function(event){
	   if (event.originalEvent === undefined) {
	    /* robot: console.log(event); */
	   } else {
	       event.preventDefault();
	       $(this).before("<input type=hidden id=qsrc name=qsrc value=s>");
	       $(this).before("<input type=hidden id=qtrigger name=qtrigger value=h>");
	       $(this).closest("form").submit();
	   }
	  });
	  
	  $(".wisyr_filtergroup .filter_submit").click(function(event){
		   if (event.originalEvent === undefined) {
		    /* robot: console.log(event); */
		   } else {
		       event.preventDefault();
		       $(this).before("<input type=hidden id=qsrc name=qsrc value=s>");
		       $(this).before("<input type=hidden id=qtrigger name=qtrigger value=h>");
		       $(this).closest("form").submit();
		   }
	   });
	 }
	
	// Human triggered search, propagate to filter form + pagination
	 if(window.qtrigger)
	  $("form[name='filterform']").prepend("<input type=hidden name='qtrigger' value="+window.qtrigger+">");
	 if(window.force)
	  $("form[name='filterform']").prepend("<input type=hidden name='force' value="+window.force+">");
	
});

function initializeTranslate() {
	 if($.cookie('cconsent_translate') == "allow") {
	  console.log("consented");
	  $.loadScript('//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit', function(){
	     /* console.log('Loaded Google Translate'); */
	 });
	  
	 } else {
	  /* Interaction not disirable */
	  /*
	  hightlightCookieConsentOption('translate');
	  window.cookieconsent.popup.open();
	  return false; */
	 }
};

function googleTranslateElementInit() {
 new google.translate.TranslateElement({pageLanguage: 'de', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
}

$(document).ready(function(){
 // $('#filter_datum_von').after('<a href="#filter_datum_von" onclick="alleDatenAnzeigen()" id="abgelaufeneAnzeigen">Abgelaufene Angebote anzeigen...</a>'); // noch framework
 preventEmptySearch(window.homepage); // noch framework
 consentCookieBeforePageFunction();
});


function hightlightCookieConsentOption(name) {
 $('.cc-consent-details .'+name+' .consent_option_infos').addClass('highlight');
}

// check for consent of specific cookie, if page dependant on it being given
function consentCookieBeforePageFunction() {
  // Edit page
 if($(".wisyp_edit").length) {
	   
   // only if no other submit event is attached to search submit button:
   if( typeof $._data( $(".wisyp_edit form[action=edit]"), "events" ) == 'undefined' ) {
    $('.wisyp_edit form[action=edit]').on('submit', function(e) {
     e.preventDefault();

     if($.cookie('cconsent_onlinepflege') != "allow") {
      alert("Um die Onlinepflege nutzen zu k"+oe+"nnen, m"+ue+"ssen Sie dem Speichern von Cookies für diese Funktion zustimmen (im Cookie-Hinweisfenster).");
      hightlightCookieConsentOption('onlinepflege');
      window.cookieconsent.popup.open();
      return false;
     } else {
      // default: normal search on other than homepage
      this.submit();
     }
    });
   }
 } // end: edit page
}

function toggle_cookiedetails() {
 jQuery(".cookies_techdetails").toggleClass("inactive");
 jQuery(".toggle_cookiedetails").toggleClass("inactive");
}


function openCookieSettings() {
	 window.cookieconsent.popup.open();
}

/* Called every time change Cookie consent window initialized or updated */
function callCookieDependantFunctions() {
 initializeTranslate();
}

jQuery.loadScript = function (url, callback) {
    jQuery.ajax({
        url: url,
        dataType: 'script',
        success: callback,
        async: true
    });
}


var ae = unescape("%E4");
var ue = unescape("%FC");
var oe = unescape("%F6");
var ss = unescape("%DF");

/*****************************************************************************
 * info text popup
 *****************************************************************************/

 $(window).load(function () {
    $('.hover_bkgr_fricc').show();
    $('.popupCloseButton').click(function(){
        $('.hover_bkgr_fricc').hide();

        if(window.cookieconsent.popup)
         window.cookieconsent.popup.open();
    });
    
   // if old cookie banner is active: set cookie immediately that msg has been viewed for 3 days
   jQuery(".hover_bkgr_fricc .popupCloseButton").click(function() {
     if(jQuery(".cc-consent-details li").length > 0 )
       ;
     else {
      setCookieSafely('cconsent_popuptext', "allow", { expires:3}); 
     }
   });                                                 
 });
 
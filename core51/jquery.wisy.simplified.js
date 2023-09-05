/*****************************************************************************
 * Libs
 *****************************************************************************/

// textinputs - http://code.google.com/p/rangy/
(function (n) {
	function o(e, g) {
		var a = typeof e[g];
		return a === "function" || !!(a == "object" && e[g]) || a == "unknown"
	}

	function p(e, g, a) {
		if (g < 0) g += e.value.length;
		if (typeof a == "undefined") a = g;
		if (a < 0) a += e.value.length;
		return {start: g, end: a}
	}

	function k() {
		return typeof document.body == "object" && document.body ? document.body : document.getElementsByTagName("body")[0]
	}

	var i, h, q, l, r, s, t, u, m;
	n(document).ready(function () {
		function e(a, b) {
			return function () {
				var c = this.jquery ? this[0] : this, d = c.nodeName.toLowerCase();
				if (c.nodeType ==
					1 && (d == "textarea" || d == "input" && c.type == "text")) {
					c = [c].concat(Array.prototype.slice.call(arguments));
					c = a.apply(this, c);
					if (!b) return c
				}
				if (b) return this
			}
		}

		var g = document.createElement("textarea");
		k().appendChild(g);
		if (typeof g.selectionStart != "undefined" && typeof g.selectionEnd != "undefined") {
			i = function (a) {
				return {
					start: a.selectionStart,
					end: a.selectionEnd,
					length: a.selectionEnd - a.selectionStart,
					text: a.value.slice(a.selectionStart, a.selectionEnd)
				}
			};
			h = function (a, b, c) {
				b = p(a, b, c);
				a.selectionStart = b.start;
				a.selectionEnd =
					b.end
			};
			m = function (a, b) {
				if (b) a.selectionEnd = a.selectionStart; else a.selectionStart = a.selectionEnd
			}
		} else if (o(g, "createTextRange") && typeof document.selection == "object" && document.selection && o(document.selection, "createRange")) {
			i = function (a) {
				var b = 0, c = 0, d, f, j;
				if ((j = document.selection.createRange()) && j.parentElement() == a) {
					f = a.value.length;
					d = a.value.replace(/\r\n/g, "\n");
					c = a.createTextRange();
					c.moveToBookmark(j.getBookmark());
					j = a.createTextRange();
					j.collapse(false);
					if (c.compareEndPoints("StartToEnd", j) >
						-1) b = c = f; else {
						b = -c.moveStart("character", -f);
						b += d.slice(0, b).split("\n").length - 1;
						if (c.compareEndPoints("EndToEnd", j) > -1) c = f; else {
							c = -c.moveEnd("character", -f);
							c += d.slice(0, c).split("\n").length - 1
						}
					}
				}
				return {start: b, end: c, length: c - b, text: a.value.slice(b, c)}
			};
			h = function (a, b, c) {
				b = p(a, b, c);
				c = a.createTextRange();
				var d = b.start - (a.value.slice(0, b.start).split("\r\n").length - 1);
				c.collapse(true);
				if (b.start == b.end) c.move("character", d); else {
					c.moveEnd("character", b.end - (a.value.slice(0, b.end).split("\r\n").length -
						1));
					c.moveStart("character", d)
				}
				c.select()
			};
			m = function (a, b) {
				var c = document.selection.createRange();
				c.collapse(b);
				c.select()
			}
		} else {
			k().removeChild(g);
			window.console && window.console.log && window.console.log("TextInputs module for Rangy not supported in your browser. Reason: No means of finding text input caret position");
			return
		}
		k().removeChild(g);
		l = function (a, b, c, d) {
			var f;
			if (b != c) {
				f = a.value;
				a.value = f.slice(0, b) + f.slice(c)
			}
			d && h(a, b, b)
		};
		q = function (a) {
			var b = i(a);
			l(a, b.start, b.end, true)
		};
		u = function (a) {
			var b =
				i(a), c;
			if (b.start != b.end) {
				c = a.value;
				a.value = c.slice(0, b.start) + c.slice(b.end)
			}
			h(a, b.start, b.start);
			return b.text
		};
		r = function (a, b, c, d) {
			var f = a.value;
			a.value = f.slice(0, c) + b + f.slice(c);
			if (d) {
				b = c + b.length;
				h(a, b, b)
			}
		};
		s = function (a, b) {
			var c = i(a), d = a.value;
			a.value = d.slice(0, c.start) + b + d.slice(c.end);
			c = c.start + b.length;
			h(a, c, c)
		};
		t = function (a, b, c) {
			var d = i(a), f = a.value;
			a.value = f.slice(0, d.start) + b + d.text + c + f.slice(d.end);
			b = d.start + b.length;
			h(a, b, b + d.length)
		};
		n.fn.extend({
			getSelection: e(i, false),
			setSelection: e(h,
				true),
			collapseSelection: e(m, true),
			deleteSelectedText: e(q, true),
			deleteText: e(l, true),
			extractSelectedText: e(u, false),
			insertText: e(r, true),
			replaceSelectedText: e(s, true),
			surroundSelectedText: e(t, true)
		})
	})
})(jQuery);

// cookie - http://archive.plugins.jquery.com/project/Cookie , http://www.electrictoolbox.com/jquery-cookies/
(function (e, t, n) {
	function i(e) {
		return e
	}

	function s(e) {
		return decodeURIComponent(e.replace(r, " "))
	}

	var r = /\+/g;
	var o = e.cookie = function (r, u, a) {
		if (u !== n) {
			a = e.extend({}, o.defaults, a);
			if (u === null) {
				a.expires = -1
			}
			if (typeof a.expires === "number") {
				var f = a.expires, l = a.expires = new Date;
				l.setDate(l.getDate() + f)
			}
			u = o.json ? JSON.stringify(u) : String(u);
			return t.cookie = [encodeURIComponent(r), "=", o.raw ? u : encodeURIComponent(u),
				a.expires ? "; expires=" + a.expires.toUTCString() : "", a.path ? "; path=" + a.path : "", a.domain ? "; domain=" + a.domain : "", a.secure ? "; secure" : "", a.sameSite ? "; sameSite=" + a.sameSite : "; sameSite=Strict"].join("")
		}
		var c = o.raw ? i : s;
		var h = t.cookie.split("; ");
		for (var p = 0, d = h.length; p < d; p++) {
			var v = h[p].split("=");
			if (c(v.shift()) === r) {
				var m = c(v.join("="));
				return o.json ? JSON.parse(m) : m
			}
		}
		return null
	};
	o.defaults = {};
	e.removeCookie = function (t, n) {
		if (e.cookie(t) !== null) {
			e.cookie(t, null, n);
			return true
		}
		return false
	}
})(jQuery, document)

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
	/* optout check makes no sense b/c opt in required anyway
    if (window.cookiebanner && window.cookiebanner.optedOut && window.cookiebanner.optoutCookies && window.cookiebanner.optoutCookies.length) {
        var blacklist = window.cookiebanner.optoutCookies.split(',');
        for (var i = 0; i < blacklist.length; i++) {
            if (title === $.trim(blacklist[i])) {
                return false;
            }
        }
    } */
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
			/* console.log("opted out!"); */
			// Disable Google Analytics
			// Tut das ueberhaupt irgendwas? -- https://developers.google.com/analytics/devguides/collection/analyticsjs/user-opt-out
			if (window.cookiebanner.uacct) window['ga-disable-' + window.cookiebanner.uacct] = true;

			// Remove unwanted cookies
			if (window.cookiebanner.optoutCookies && window.cookiebanner.optoutCookies.length) {

				// Portal blacklist
				var blacklist = window.cookiebanner.optoutCookies.split(',');
				for (var i = 0; i < blacklist.length; i++) {
					var cookieName = $.trim(blacklist[i]);
					if (cookieName !== '') $.removeCookie(cookieName, {path: '/'});
				}

				// FAV
				$.removeCookie('fav', {path: '/'});
				$.removeCookie('fav_init_hint', {path: '/'});

				// Piwik
				if (window.cookiebanner.piwik) {
					var piwikCookies = document.cookie.match(/\_pk(\_id|\_ses)\.6\..*?=/g);
					if (piwikCookies !== null) {
						for (var i = 0; i < piwikCookies.length; i++) {
							$.removeCookie(piwikCookies[i].replace('=', ''), {path: '/'});
						}
					}
				}

				// Google Analytics
				if (window.cookiebanner.uacct) {
					$.removeCookie('_ga', {path: '/'});
					$.removeCookie('_gat', {path: '/'});
					$.removeCookie('_gid', {path: '/'});
				}
			}
		}
	}
}

/*****************************************************************************
 * fav stuff
 *****************************************************************************/



var g_all_fav = {};

function fav_count() {
	var cnt = 0;
	for (var key in g_all_fav) {
		if (g_all_fav[key])
			cnt++;
	}
	return cnt;
}

function fav_is_favourite(id) {
	return g_all_fav[id] ? true : false;
}

function fav_set_favourite(id, state) {
	g_all_fav[id] = state;
	fav_save_cookie();
}

function fav_save_cookie() {
	var str = '';
	for (var key in g_all_fav) {
		if (g_all_fav[key]) {
			str += str == '' ? '' : ',';
			str += key;
		}
	}
	setCookieSafely('fav', str, {path: "/", sameSite: "Strict", expires: 7}); // expires in 30 days // options working?
}


function fav_list_functions() {
	var cnt = fav_count();
	if (cnt > 0) {
		var mailto = $('#favlistlink').attr('data-favlink');
		if (mailto != '') {
			mailto += 'search?q=favprint%253A';
			for (var key in g_all_fav) {
				if (g_all_fav[key]) {
					mailto += key + '%252F';
				}
			}
		}

		str = '<span class="wisyr_fav_functions">';
		str += '<span class="wisyr_fav_anzahl">Ihre Merkliste enth&auml;lt ' + cnt + (cnt == 1 ? ' Eintrag ' : ' Eintr&auml;ge ') + '</span>';
		if (mailto != '') {
			str += '<a class="fav_functions_mailsend" href="' + mailto + '" title="Merkliste per E-Mail versenden" class="fav_send">Merkliste per E-Mail versenden</a> ';
		}
		str += ' <a class="fav_functions_deleteall" href="javascript:fav_delete_all()" title="Gesamte Merkliste l&ouml;schen">Gesamte Merkliste l&ouml;schen</a>';
		str += '</span>';

		$('.wisyr_angebote_zum_suchauftrag').html(str);
	}
}

function fav_update_bar() {
	var cnt = fav_count();
	if (cnt > 0) {
		str = '<a href="search?q=Fav%3A" title="Merkliste anzeigen">';
		//str += '<span class="fav_item fav_selected noprint">&#9733;</span> ';
		str += '<span class="fav_item fav_selected noprint"><img src="/files/sh/img/merkliste_icon2.svg" alt="Angebot gemerkt"></span> ';
		str += '<span class="favlistlink_title">Merkliste (' + cnt + ')</span>';
		str += '</a> ';

		$('#favlistlink').html(str);

		$('.fav_hide').hide();
	} else {
		$('#favlistlink').html('');
		$('.fav_hide').show();
	}
}


function fav_click(jsObj, id) {
	/* if (window.cookiebanner && window.cookiebanner.optedOut) {
        alert(window.cookiebanner.favOptoutMessage);
        window.cookieconsent.popup.open();
        return false;
    } else */
	if ($.cookie('cconsent_merkliste') != "allow" && !window.cookiebanner_zustimmung_merkliste_legacy) {
		alert("Um diese Funktion nutzen zu k" + oe + "nnen, m" + ue + "ssen Sie dem Speichern von Cookies f" + ue + "r diese Funktion zustimmen (im Cookie-Hinweisfenster).");
		hightlightCookieConsentOption('merkliste');
		window.cookieconsent.popup.open();
		return false;
	}
	jqObj = $(jsObj);
	var imageElement = jqObj.find('img');
	if (jqObj.hasClass('fav_selected')) {
		jqObj.removeClass('fav_selected');
		imageElement.attr('src', '/files/sh/img/merkliste_icon1.svg');
		fav_set_favourite(id, false);
		fav_update_bar();
	} else {
		jqObj.addClass('fav_selected');
		imageElement.attr('src', '/files/sh/img/merkliste_icon2.svg');
		fav_set_favourite(id, true);
		fav_update_bar();

		if ($.cookie('fav_init_hint') != 1) {
			alert('Ihr Favorit wurde auf diesem Computer gespeichert. Um ihre Merkliste anzuzeigen, klicken Sie auf "Merkliste" oben rechts.');
			setCookieSafely('fav_init_hint', 1, {path: "/", sameSite: "Strict", expires: 7});
		}
	}
}

function fav_delete_all() {
	if (!confirm('Gesamte Merkliste l' + oe + 'schen?'))
		return false;

	g_all_fav = {};
	fav_save_cookie();
	window.location.href = '/';
}


function fav_init() {
	// read favs from cookie (exp. '3501,3554')
	var temp = $.cookie('fav');
	if (typeof temp == 'string') {
		temp = temp.split(',');
		for (var i = 0; i < temp.length; i++) {
			var id = parseInt(temp[i], 10);
			if (!isNaN(id) && id > 0) {
				g_all_fav[id] = true;
			}
		}
	}

	// prepare the page
	var has_clickable_fav = false;
	$('.fav_add').each(function () {
		var id = $(this).attr('data-favid');
		var cls = fav_is_favourite(id) ? 'fav_item fav_selected noprint' : 'fav_item noprint';
		// $(this).parent().append(' <span class="' + cls + '" onclick="fav_click(this, ' + id + ');" title="Angebot merken">&#9733;</span>');
		$(this).parent().append(' <span class="'+cls+'" onclick="fav_click(this, '+id+');" title="Angebot merken"><img src="/files/sh/img/merkliste_icon1.svg" alt="Angebot merken"></span>');
		has_clickable_fav = true;
	});

	if (has_clickable_fav) {
		$('table.wisy_list tr th:first a.wisy_help').after('<span class="fav_hint_in_th">mit &#9733; merken</span>');
	}

	if (fav_count()) {
		fav_update_bar();

		if ($('body').hasClass('wisyq_fav')) {
			fav_list_functions();
		}
	}
}



/*****************************************************************************
 * autocomplete stuff
 *****************************************************************************/

function clickAutocompleteHelp(tag_help, tag_name_encoded) {
	location.href = 'g' + tag_help + '?ie=UTF-8&q=' + tag_name_encoded;
	return false;
}

function clickAutocompleteMore(tag_name_encoded) {
	location.href = 'search?ie=UTF-8&show=tags&q=' + tag_name_encoded; // ie=UTF-8& is necessary b/c q-value being urlencoded in UTF-8
}

function htmlspecialchars(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return text.replace(/[&<>"']/g, function (m) {
		return map[m];
	});
}

function formatItem(row) {
	var tag_name = row[0]; // this is plain text, so take care on output! (eg. you may want to strip or escape HTML)
	var tag_descr = row[1]; // this is already HTML
	var tag_type = row[2];
	var tag_help = row[3];
	var tag_freq = row[4];

	/* see also (***) in the PHP part */
	var row_class = 'ac_normal';
	var row_prefix = '';
	var row_preposition = '';
	var row_postfix = '';

	if (tag_help == 1) {
		/* add the "more" link */
		row_class = 'ac_more';
		tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name) + '&#39;)">' + tag_descr + '</a>';
	} else {
		/* base type */
		if (tag_type & 1) {
			row_class = "ac_abschluss";
			row_preposition = ' zum ';
			row_postfix = '<b>Abschluss</b>';
		} else if (tag_type & 2) {
			row_class = "ac_foerderung";
			row_preposition = ' zur ';
			row_postfix = 'F&ouml;rderung';
		} else if (tag_type & 4) {
			row_class = "ac_qualitaetszertifikat";
			row_preposition = ' zum ';
			row_postfix = 'Qualit&auml;tszertifikat';
		} else if (tag_type & 8) {
			row_class = "ac_zielgruppe";
			row_preposition = ' zur ';
			row_postfix = 'Zielgruppe';
		} else if (tag_type & 16) {
			row_class = "ac_abschlussart";
			row_preposition = ' zur ';
			row_postfix = 'Abschlussart';
		} else if (tag_type & 128) {
			row_class = "ac_thema";
			row_preposition = ' zum ';
			row_postfix = 'Thema';
		} else if (tag_type & 256) {
			row_class = "ac_anbieter";
			if (tag_type & 0x10000) {
				row_preposition = ' zum ';
				row_postfix = 'Trainer';
			} else if (tag_type & 0x20000) {
				row_preposition = ' zur ';
				row_postfix = 'Beratungsstelle';
			} else if (tag_type & 0x400000) {
				row_preposition = ' zum ';
				row_postfix = 'Anbieterverweis';
			} else {
				row_preposition = ' zum ';
				row_postfix = 'Anbieter';
			}
		} else if (tag_type & 512) {
			row_class = "ac_ort";
			row_preposition = ' zum ';
			row_postfix = 'Ort';
		} else if (tag_type & 1024) {
			row_class = "ac_sonstigesmerkmal";
			row_preposition = ' zum ';
			row_postfix = 'sonstigen Merkmal';
		} else if (tag_type & 32768) {
			row_class = "ac_unterrichtsart";
			row_preposition = ' zur ';
			row_postfix = 'Unterrichtsart';
		}

		/* frequency, end base type */
		if (tag_freq > 0) {
			row_postfix = (tag_freq == 1 ? '1 Angebot' : ('' + tag_freq + ' Angebote')) + row_preposition + row_postfix;
		}

		if (tag_descr != '') {
			row_postfix = tag_descr + ', ' + row_postfix;
		}

		if (row_postfix != '') {
			row_postfix = ' <span class="ac_tag_type">(' + row_postfix + ')</span> ';
		}


		/* additional flags */
		if (tag_type & 0x10000000) {
			row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			row_class += " ac_indent";
		} else if (tag_type & 0x20000000) {
			row_prefix = 'Meinten Sie: ';
		}

		/* help link */
		if (tag_help != 0) {
			/* note: a single semicolon disturbs the highlighter as well as a single quote! */
			row_postfix +=
				' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)">&nbsp;i&nbsp;</a>';
		}

		tag_name = htmlspecialchars(tag_name);
	}
	
	return '<span class="'+row_class+'">' + row_prefix + '<span class="tag_name">' + tag_name + '</span>' + row_postfix + '</span>';
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

function initAutocomplete() {
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


if (jQuery.ui) {
	function formatItem_v2(row, request_term) {
		var tag_name = row[0]; // this is plain text, so take care on output! (eg. you may want to strip or escape HTML)
		var tag_descr = row[1]; // this is already HTML
		var tag_type = row[2];
		var tag_help = row[3];
		var tag_freq = row[4];

		/* see also (***) in the PHP part */
		row_class = 'ac_normal';
		row_type = 'Sachstichwort';
		row_count = '';
		row_info = '';
		row_prefix = '';
		row_postfix = '';

		if (tag_help == -3) {
			var minChars = (window.search_minchars ? window.search_minchars : 3);
			if (request_term.length >= minChars) {
				/* add volltext */
				row_class = 'ac_fulltext';
				tag_name = 'Volltextsuche nach "' + (jQuery(request_term).text() ? jQuery(request_term).text() : request_term) + '" ausf&uuml;hren?'; //  if a html tag is detected: text() makes sure only text value output otherwise output request_term (=normal text input)
			} else {
				row_class = 'ac_ignore';
				tag_name = ''; /* too short for fulltext */
			}
		} else if (tag_help == -2) {
			/* add "no results" */
			row_class = 'ac_noresults';
			row_type = '';
			tag_name = '<strong>' + tag_descr + '</strong>';
		} else if (tag_help == -1) {
			/* add the Headline */
			row_class = 'ac_headline';
			row_type = '';
			tag_name = '<strong>' + tag_descr + '</strong>';
		} else if (tag_help == 1) {
			/* add the "more" link */
			row_class = 'ac_more';
			row_type = '';
			tag_name = '<a href="" onclick="return clickAutocompleteMore(&#39;' + encodeURIComponent(tag_name) + '&#39;)">' + tag_descr + '</a>';
		} else {
			/* base type */
			if (tag_type & 1) {
				row_class = "ac_abschluss";
				row_type = 'Abschluss';
			} else if (tag_type & 2) {
				row_class = "ac_foerderung";
				row_type = 'F&ouml;rderung';
			} else if (tag_type & 4) {
				row_class = "ac_qualitaetszertifikat";
				row_type = 'Qualit&auml;tsmerkmal';
			} else if (tag_type & 8) {
				row_class = "ac_zielgruppe";
				row_type = 'Zielgruppe';
			} else if (tag_type & 16) {
				row_class = "ac_abschlussart";
				row_type = 'Abschlussart';
			} else if (tag_type & 128) {
				row_class = "ac_thema";
				row_type = 'Thema';
			} else if (tag_type & 256) {
				row_class = "ac_anbieter";
				if (tag_type & 0x20000) {
					row_type = 'Beratungsstelle';
				} else if (tag_type & 0x400000) {
					row_type = 'Tr&auml;gerverweis';
				} else {
					row_type = 'Tr&auml;ger';
				}
			} else if (tag_type & 512) {
				row_class = "ac_ort";
				row_type = 'Angebotsort';
			} else if (tag_type & 1024) {
				row_class = "ac_merkmal";
				row_type = 'Angebotsmerkmal';
			} else if (tag_type & 32768) {
				row_class = "ac_unterrichtsart";
				row_type = 'Unterrichtsart';
			}

			/* frequency, end base type */
			if (tag_descr != '') row_postfix = ' (' + tag_descr + ')';

			if (tag_freq > 0) {
				row_count = '(' + tag_freq;
				row_count += (tag_freq == 1) ? '&nbsp;Angebot)' : '&nbsp;Angebote)';
			}

			/* additional flags */
			if (tag_type & 0x10000000) row_class += " ac_indent";

			else if (tag_type & 0x20000000) {
				row_prefix = 'Meinten Sie ';
				tag_name = '"' + tag_name + '"?';
				row_class += " ac_suggestion";
			}

			/* help link */
			if (tag_help != 0) {
				/* note: a single semicolon disturbs the highlighter as well as a single quote! */
				row_info = ' <a class="wisy_help" href="" onclick="return clickAutocompleteHelp(' + tag_help + ', &#39;' + encodeURIComponent(tag_name) + '&#39;)">&nbsp;i&nbsp;</a>';
			}

			tag_name = htmlspecialchars(tag_name);
		}

		// highlight search string
		var regex = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + request_term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		tag_name = tag_name.replace(regex, "<em>$1</em>").replace('&amp;', '&');

		//
		if (typeof ajax_infoi != "undefined" && ajax_infoi)
			var postfix_help = (tag_help > 0) ? '<span data-tag_help="##g' + tag_help + '##"></span>' : '';
		// var postfix_help = (tag_help > 0) ? '<span class="tag_info">' + '<a href="/g'+tag_help+'" class="tag_help">'+'Infos'+'</a>' + '</span>' : '';

		var postfix_text = '<span class="row ' + row_class + '">' +
			'<span class="tag_name">' + row_prefix + tag_name + '</span>' +
			'<span class="tag_count">' + row_count + '</span></span>';

		if (typeof ajax_infoi != "undefined" && ajax_infoi)
			return postfix_text + postfix_help;

		return postfix_text;
	}

	function ac_sourcecallback_anbieter(request, response_callback) {
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggest?q=" + encodeURIComponent(request_term) + "&type=anbieter&limit=512&timestamp=" + new Date().getTime();

		ac_sourcecallback_execute(request_term, url, response_callback);
	}

	function ac_sourcecallback_ort(request, response_callback) {
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggestplzort?q=" + encodeURIComponent(request_term) + "&type=ort&limit=512&timestamp=" + new Date().getTime();

		ac_sourcecallback_execute(request_term, url, response_callback, 'plzOrt');
	}

	function ac_sourcecallback(request, response_callback) {
		// calculate the new source url
		var request_term = extractLast(request.term);
		var url = "/autosuggest?q=" + encodeURIComponent(request_term) + "&limit=512&timestamp=" + new Date().getTime();

		ac_sourcecallback_execute(request_term, url, response_callback);

	}

	function ac_sourcecallback_execute(request_term, url, response_callback, formatter) {
		// ask the server for suggestions
		$.get(url, function (data) {

			// Daten aufbereiten
			data = data.split("\n");

			var response_data = [];
			for (var i = 0; i < data.length; i++) {
				if (data[i] != "") {
					if (formatter == 'plzOrt') {
						var row = data[i].split("|");
						if (row[0] == 'headline') {
							// Zwischenueberschrift
							response_data.push({label: '<strong class="headline">' + row[1] + '</strong>', value: ''});
						} else {
							response_data.push({label: row[0], value: row[1]});
						}
					} else {
						var row = data[i].split("|");
						response_data.push({label: formatItem_v2(row, request_term), value: row[0]});
					}
				}
			}
			response_callback(response_data);
		});
	}

	function ac_selectcallback_ort(event, ui, that) {

		event.preventDefault();

		if (that == undefined) {
			that = this;
		}

		if (ui.item.value != '') {
			that.value = ui.item.value;
		}
	}

	function ac_selectcallback_autosubmit(event, ui) {

		ac_selectcallback(event, ui, this, true);

	}

	function ac_selectcallback(event, ui, that, autosubmit) {

		event.preventDefault();

		if (that == undefined) {
			that = this;
		}

		// Standardverhalten (Value ins Eingabefeld schreiben) bei Ueberschrift und Mehrlink der Ergebnisliste ausschalten
		// Ebenso bei Klick auf "wisy_help"
		var $span = $(ui.item.label);
		var $to = $(event.toElement);
		if ($span.hasClass('ac_noresults') || $span.hasClass('ac_headline') || $span.hasClass('ac_more') || $to.hasClass('wisy_help')) {
			event.preventDefault();
			if ($span.hasClass('ac_more')) {
				clickAutocompleteMore(encodeURIComponent(that.value));
				return false;
			}
		} else {

			// Neuen Autocomplete-Wert nach evtl. bereits vorhandenen einfuegen
			var terms = split(that.value);
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push(ui.item.value);
			// add placeholder to get the comma-and-space at the end
			terms.push("");
			that.value = terms.join(", ");

			// remove trailing comma / comma and space
			if (that.value.substr(-1) == ' ') that.value = that.value.substring(0, that.value.length - 1);
			if (that.value.substr(-1) == ',') that.value = that.value.substring(0, that.value.length - 1);

		}
		// Auto-submit on select
		$('#wisy_searcharea form').append('<input type="hidden" name="qtrigger" value="h">');
		if (autosubmit) $('#wisy_searcharea form').submit();
	}

	function ac_focuscallback(event, ui) {
		event.preventDefault();
	}

	function initAutocomplete_v2() {
		$(".ac_keyword").each(function () {
				var jqObj = $(this);
				jqObj.autocomplete(
					{
						source: ac_sourcecallback
						, theinput: jqObj
						, html: true
						, select: ac_selectcallback_autosubmit
						, focus: ac_focuscallback
					});
			}
		);
		$(".ac_keyword_ort").each(function () {
				if ($(".ac_keyword_ort").data('autocomplete') == 1) {
					var jqObj = $(this);
					jqObj.autocomplete(
						{
							source: ac_sourcecallback_ort
							, theinput: jqObj
							, html: true
							, select: ac_selectcallback_ort
							, focus: ac_focuscallback
						});
				}
			}
		);
		$(".ac_keyword_anbieter").each(function () {
				var jqObj = $(this);
				jqObj.autocomplete(
					{
						source: ac_sourcecallback_anbieter
						, theinput: jqObj
						, html: true
						, select: ac_selectcallback_autosubmit
						, focus: ac_focuscallback
					});
			}
		);
	}

	$.widget("custom.autocomplete", $.ui.autocomplete, {
		_renderMenu: function (ul, items) {
			var that = this;
			$.each(items, function (index, item) {
				that._renderItemData(ul, item);
			});
			// Streifen
			$(ul).addClass('ac_results ac_results_v2').find("li:odd").addClass("ac_odd");
			$(ul).find("li:even").addClass("ac_even");
		},
		_resizeMenu: function () {
			this.menu.element.outerWidth(500);
		}
	});

	function split(val) {
		return val.split(/,\s*/);
	}

	function extractLast(term) {
		return split(term).pop();
	}
}


/******************************************************************************
 jQuery UI Autocomplete HTML Extension
 Copyright 2010, Scott González (http://scottgonzalez.com)
 Dual licensed under the MIT or GPL Version 2 licenses.
 http://github.com/scottgonzalez/jquery-ui-extensions
 ******************************************************************************/

if (jQuery.ui) {
	(function ($) {

		var proto = $.ui.autocomplete.prototype,
			initSource = proto._initSource;

		function filter(array, term) {
			var matcher = new RegExp($.ui.autocomplete.escapeRegex(term), "i");
			return $.grep(array, function (value) {
				return matcher.test($("<div>").html(value.label || value.value || value).text());
			});
		}

		$.extend(proto, {
			_initSource: function () {
				if (this.options.html && $.isArray(this.options.source)) {
					this.source = function (request, response) {
						response(filter(this.options.source, request.term));
					};
				} else {
					initSource.call(this);
				}
			},

			_renderItem: function (ul, item) {
				return $("<li></li>")
					.data("item.autocomplete", item)
					.append($("<a></a>")[this.options.html ? "html" : "text"](item.label))
					.appendTo(ul);
			}
		});

	})(jQuery);
}


/*****************************************************************************
 * advanced search stuff
 *****************************************************************************/

// prevent empty search (<2 chars): on hompage: output message, on other page: search for all courses
function preventEmptySearch(homepage) {
 
  // ! $("#wisy_searcharea form[action=search]").length &&
  
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

function advEmbeddingViaAjaxDone() {
	// Init autocomplete function
	// Use v2 if available
	if (jQuery.ui) {
		initAutocomplete_v2();
	} else {
		initAutocomplete();
	}

	// fade from normal to adv
	$("#wisy_searcharea").hide('slow');
	$("#advEmbedded").show('slow');

	// remove the loading indicatiot
	$("#wisy_searchinput").removeClass('ac_loading');

	// ajaxify  the "cancel" button
	$("#adv_cancel").click(function () {
		// fade from adv to normal
		$("#wisy_searcharea").show('slow');
		$("#advEmbedded").hide('slow', function () {
			$("#advEmbedded").remove()
		});

		// done
		return false;
	});
}

function advEmbedViaAjax() {
	// add the loading indicator
	$("#wisy_searchinput").addClass('ac_loading');

	// create query string
	var q = $("#wisy_searchinput").val(); // for some reasons, q is UTF-8 encoded, so we use this charset as ie= below
	if ($("#wisy_beiinput").length) {
		var bei = $("#wisy_beiinput").val();
		if (bei != '') {
			q += ', bei:' + bei;
		}
		var km = $("#wisy_kmselect").val();
		if (km != '') {
			q += ', km:' + km;
		}
	}

	// create and load the advanced options
	var justnow = new Date();
	var rnd = justnow.getTime();
	$("#wisy_searcharea").after('<div id="advEmbedded" style="display: none;"></div>');
	$("#advEmbedded").load('advanced?ajax=1&ie=UTF-8&rnd=' + rnd + '&q=' + encodeURIComponent(q), advEmbeddingViaAjaxDone);
	return false;
}

function strike_doubleTag_filter() {
	if (typeof (double_tags) != "undefined") {
		for (i = 0; i < double_tags.length; i++) {
			label_strike = jQuery("input[value='" + double_tags[i] + "']").next("label");
			label_strike.html("<strike>" + label_strike.html() + "</strike>");
		}
	}
}

function filterEmbeddingViaAjaxDone() {
	// show filter form
	$("#filterEmbedded").removeClass('loading');
	$("#filterEmbedded .inner").slideDown(500);

	// remove the loading indicator
	$("#wisy_searchinput").removeClass('filter_loading');

	// ajaxify the "close" button
	$("#filter_close").click(function () {
		// hide filter form
		$('#wisy_contentarea').removeClass('filter_open');
		$('#wisy_filterlink').removeClass('active');
		$("#filterEmbedded").slideUp(300, function () {
			$("#filterEmbedded").remove()
		});

		// done
		return false;
	});

	// ajaxify the "reset" button
	$('#filter_reset').click(function () {
		$('#filterEmbedded form').find('input')
			.filter(':text, :password, :file').val('').end()
			.filter(':checkbox, :radio').removeAttr('checked').end().end()
			.find('textarea').val('').end()
			.find('select').prop("selectedIndex", -1)
			.find('option:selected').removeAttr('selected');

		return false;
	});
}

function filterEmbedViaAjax() {
	// Close Filter form if already open
	if ($("#wisy_contentarea").hasClass('filter_open')) {
		$('#wisy_contentarea').removeClass('filter_open');
		$('#wisy_filterlink').removeClass('active');
		$("#filterEmbedded").slideUp(300, function () {
			$("#filterEmbedded").remove()
		});
		return false;
	}

	// add the and active indicator
	$("#wisy_contentarea").addClass('filter_open');

	// Update Filterlink Button status
	$('#wisy_filterlink').addClass('active');

	// create query string
	var q = $("#wisy_searchinput").val(); // for some reasons, q is UTF-8 encoded, so we use this charset as ie= below
	if ($("#wisy_beiinput").length) {
		var bei = $("#wisy_beiinput").val();
		if (bei != '') {
			q += ', bei:' + bei;
		}
		var km = $("#wisy_kmselect").val();
		if (km != '') {
			q += ', km:' + km;
		}
	}

	// create and load the filter options
	var justnow = new Date();
	var rnd = justnow.getTime();
	$("#wisy_filterlink").after('<div id="filterEmbedded" class="loading"><div class="inner" style="display:none;"></div></div>');
	$("#filterEmbedded .inner").load('filter?ajax=1&ie=UTF-8&rnd=' + rnd + '&q=' + encodeURIComponent(q), filterEmbeddingViaAjaxDone);
	return false;
}


/*****************************************************************************
 * index pagination stuff
 *****************************************************************************/

function paginateViaAjaxDone() {
	$("#wisy_searchinput").removeClass('ac_loading');
	initPaginateViaAjax();
}

function paginateViaAjax(theLink) {
	$("#wisy_searchinput").addClass('ac_loading');
	$("#wisy_resultarea").load(theLink.href + '&ajax=1', paginateViaAjaxDone);
}

function initPaginateViaAjax() {
	$("span.wisy_paginate a").click(function () {
		paginateViaAjax(this);
		return false;
	});
	$("a.wisy_orderby").click(function () {
		paginateViaAjax(this);
		return false;
	});
}

/*****************************************************************************
 * dropdown stuff
 *****************************************************************************/

$.fn.dropdown = function () {
	// needed only for IE6 dropdown menu
	$(this).hover(function () {
		$(this).addClass("hover");
		$('> .dir', this).addClass("open");
		$('ul:first', this).css('visibility', 'visible');
	}, function () {
		$(this).removeClass("hover");
		$('.open', this).removeClass("open");
		$('ul:first', this).css('visibility', 'hidden');
	});

}


/*****************************************************************************
 * old edit stuff
 *****************************************************************************/

function ed(theAnchor) {
	// remove focus -- do this first to avoid coming the new window into the background
	if (theAnchor.blur) {
		theAnchor.blur();
	}

	// open new window;
	// no spaces in string, bug in function window.open()
	var w = window.open(theAnchor.href, theAnchor.target, 'width=610,height=580,resizable=yes,scrollbars=yes');

	// bring window to top
	if (!w.opener) {
		w.opener = self;
	}

	if (w.focus != null) {
		w.focus();
	}

	// avoid standard hyperlink processing
	return false;
}

/*****************************************************************************
 * new edit stuff
 *****************************************************************************/

function editShowHide(jqObj, toShow, toHide) {
	jqObj.parent().parent().find(toShow).show('fast');
	jqObj.parent().parent().find(toHide).hide();
}

function editFindDurchfRow(jqObj) {
	var durchfRow = jqObj;
	var iterations = 0;
	while (1) {
		if (durchfRow.hasClass('editDurchfRow'))
			return durchfRow;
		durchfRow = durchfRow.parent();
		iterations++;
		if (iterations > 100) {
			alert('ERROR: editDurchfRow class not found ...');
			return durchfRow;
		}
	}
}

function editDurchfLoeschen(jqObj) {
	if ($('.editDurchfRow').size() == 1) {
		alert("Diese Durchf" + ue + "hrung kann nicht gel" + oe + "scht werden, da ein Angebot mindestens eine Durchf" + ue + "hrung haben muss.\n\nWenn Sie das Angebot komplett l" + oe + "schen m" + oe + "chten, verwenden Sie die Option \"Angebot l" + oe + "schen\" ganz unten auf dieser Seite.");
		return;
	} else if (confirm("Diese Durchf" + ue + "hrung l" + oe + "schen?")) {
		editFindDurchfRow(jqObj).remove();
	}
}

function editDurchfKopieren(jqObj) {
	var durchfBase = editFindDurchfRow(jqObj);

	var clonedObj = durchfBase.clone();
	clonedObj.find('.hiddenId').val('0');

	durchfBase.after(clonedObj);
}

function editKursLoeschen(jqObj) {
	if (confirm("Wenn Sie ein Angebot l" + oe + "schen m" + oe + "chten, wird zun" + ae + "chst ein Sperrvermerk gesetzt; beim n" + ae + "chsten Index-Update wird das Angebot dann inkl. aller Durchf" + ue + "hrungen komplett gel" + oe + "scht. Dieser Vorgang kann nicht r" + ue + "ckg" + ae + "ngig gemacht werden!\n\nDas komplette Angebot inkl. ALLER Durchf" + ue + "hrungen l" + oe + "schen?")) {
		return true;
	}
	return false;
}

function editWeekdays(jqObj) {
	// jqObj ist der Text; ein click hierauf soll das nebenliegende <input type=hidden> aendern
	var hiddenObj = jqObj.parent().find('input');
	if (hiddenObj.val() == '1') {
		hiddenObj.val('0');
		jqObj.addClass('wisy_editweekdaysnorm');
		jqObj.removeClass('wisy_editweekdayssel');
	} else {
		hiddenObj.val('1');
		jqObj.addClass('wisy_editweekdayssel');
		jqObj.removeClass('wisy_editweekdaysnorm');
	}
}

function resetPassword(aID, pflegeEmail) {
	$.ajax({
		type: "POST",
		url: "/edit",
		data: {action: "forgotpw", pwsubseq: "1", as: aID},
		success: function (data) {
			alert("Wir haben Ihnen eine E-Mail mit einem Link zur Passwortgenerierung an " + pflegeEmail + " gesandt!\n\nSollte in wenigen Minuten keine E-Mail eintreffen, pruefen Sie bitte die E-Mailadresse bzw. wenden Sie sich bitte an den Portal-Betreiber.");
		}
	});
}

/*****************************************************************************
 * feedback stuff
 *****************************************************************************/

function ajaxFeedback(rating, descr, name, email) {
	var url = 'feedback?url=' + encodeURIComponent(window.location) + '&rating=' + rating + '&descr=' + encodeURIComponent(descr) + '&name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email);
	$.get(url);
}

function describeFeedback() {
	var descr = $('#wisy_feedback_descr').val();
	var name = $('#wisy_feedback_name').val();
	var email = $('#wisy_feedback_email').val();

	descr = $.trim(descr);
	name = $.trim(name);
	email = $.trim(email);

	if (descr == '') {
		alert('Bitte geben Sie zuerst Ihren Kommentar ein.');
	} else {
		$('#wisy_feedback_line2').html('<strong style="color: green;">Vielen Dank f' + ue + 'r Ihren Kommentar!</strong>');
		ajaxFeedback(0, descr, name, email); // Kommentar zur Bewertung hinzufuegen; die Bewertung selbst (erster Parameter) wird an dieser Stelle ignoriert!
	}
}

function sendFeedback(rating)
{
	var feedbackThxTxt = '';
	if( typeof window.feedbackThx != "undefined" )
		feedbackThxTxt = window.feedbackThx;
	else
		feedbackThxTxt = '<b style="color: green;">Vielen Dank f'+ue+'r Ihr Feedback!</b>';
		
	$('#wisy_feedback_yesno').html( '<br><br><span class="wisy_feedback_thanks">' + feedbackThxTxt + '</span><br>' );
	
	if( rating == 0 )
	{
		$('#wisy_feedback').append(
			'<div id="wisy_feedback_line2">'
			+ '<p>Bitte schildern Sie uns noch kurz, warum diese Seite nicht hilfreich war und was wir besser machen k&ouml;nnen:</p>'
			+ '<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="2" cols="20"></textarea><br />'
			+ '<br><b>Wenn Sie eine Antwort w&uuml;nschen</b>, geben Sie bitte auch Ihre E-Mail-Adresse an (optional).<br />Wir verwenden Ihre E-Mailadresse und ggf. Name nur, um Ihr Anliegen zu bearbeiten und l&ouml;schen diese personenbezogenen Daten alle 12 Monate.<br><br>'
			+ '<label for="wisy_feedback_name">Name (optional): </label><input type="text" id="wisy_feedback_name" name="wisy_feedback_name">&nbsp; <label for="wisy_feedback_email">E-Mailadresse (optional): </label><input type="text" id="wisy_feedback_email" name="wisy_feedback_email"><br><br>'
			+ '<input id="wisy_feedback_submit" type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+ '</div>'
		);
		$('#wisy_feedback_descr').focus();
	} else {
		$('#wisy_feedback').append(
			'<div id="wisy_feedback_line2">'
			+ '<p>Bitte schildern Sie uns kurz, was hilfreich war, damit wir Bew&auml;hrtes bewahren und ausbauen:</p>'
			+ '<textarea id="wisy_feedback_descr" name="wisy_feedback_descr" rows="2" cols="20"></textarea><br />'
			+ '<br><b>Wenn Sie eine Antwort w&uuml;nschen</b>, geben Sie bitte auch Ihre E-Mail-Adresse an (optional).<br />Wir verwenden Ihre E-Mailadresse und ggf. Name nur, um Ihr Anliegen zu bearbeiten und l&ouml;schen diese personenbezogenen Daten alle 12 Monate.<br><br>'
			+ '<label for="wisy_feedback_name">Name (optional): </label><input type="text" id="wisy_feedback_name" name="wisy_feedback_name">&nbsp; <label for="wisy_feedback_email">E-Mailadresse (optional): </label><input type="text" id="wisy_feedback_email" name="wisy_feedback_email"><br><br>'
			+ '<input id="wisy_feedback_submit" type="submit" onclick="describeFeedback(); return false;" value="Kommentar senden" />'
			+ '</div>'
		);
		$('#wisy_feedback_descr').focus();
	}

	ajaxFeedback(rating, '');
}

function initFeedback() {
	$('.wisy_allow_feedback').after(
		'<div id="wisy_feedback" class="noprint">'
		+ '<span class="wisy_feedback_question">War diese Seite hilfreich?</span> '
		+ '<span id="wisy_feedback_yesno"><a href="javascript:sendFeedback(1)">Ja</a> <a href="javascript:sendFeedback(0)">Nein</a></span>'
		+ '</div>'
	);
}


/*****************************************************************************
 * the simple editor functions
 *****************************************************************************/


function add_chars(jqObj, chars1, chars2) {
	var jqTextarea = jqObj.parent().parent().find('textarea'); // see (**)
	jqTextarea.surroundSelectedText(chars1, chars2);
}


/*****************************************************************************
 * the keywords() functions in the ratgeber
 *****************************************************************************/

function wisy_glskeyexp() {
	var jqAClick = $(this);
	var jqTrClick = jqAClick.parent().parent();

	var action = jqAClick.attr('data-glskeyaction');
	var indentClick = parseInt(jqTrClick.attr('data-indent'));

	var show = true;
	if (action == 'shrink') {
		jqAClick.html('&nbsp;&#9654;');
		jqAClick.attr('data-glskeyaction', 'expand');
		show = false;

	} else if (action == 'expand') {
		jqAClick.html('&#9660;');
		jqAClick.attr('data-glskeyaction', 'shrink');
		show = true;
	}

	jqTrCurr = jqTrClick.next();
	var indentShow = indentClick + 1;
	while (jqTrCurr.length) {
		var indentCurr = jqTrCurr.attr('data-indent');
		if (indentCurr <= indentClick) {
			break; // done, this row is no child
		}

		if (show && indentCurr == indentShow) {
			jqTrCurr.show();
		} else {
			jqTrCurr.hide();
		}

		jqACurr = jqTrCurr.find('a.wisy_glskeyexp');
		jqACurr.html('&nbsp;&#9654;');
		jqACurr.attr('data-glskeyaction', 'expand');

		jqTrCurr = jqTrCurr.next();
	}

	return false; // no default link processing
}

function initRatgeber() {
	$("a.wisy_glskeyexp").click(wisy_glskeyexp);
}

/*****************************************************************************
 * Responsive functions for core50
 *****************************************************************************/

function initResponsive() {
	// Remove nojs class from body in case the remove function directly after the <body> tag is missing
	$('body.nojs').removeClass('nojs').addClass('yesjs');

	// Toggle mobile nav on __MOBILNAVLINK__ click
	$('#nav-link').on('click', function () {
		window.scrollTo(0, 0);
		$('body').toggleClass('navshowing');
	});

	// Navigation Unterpunkte oeffnen und schliessen mobil
	$('#themenmenue a').on('click', function () {
		$firstUl = $(this).siblings('ul').first();
		if ($firstUl.length) {
			if ($firstUl.hasClass('open')) {
				$firstUl.removeClass('open').stop(true, true).slideUp();
				$(this).parent().removeClass('open');
			} else {
				$firstUl.children('li').show();
				$firstUl.addClass('open').stop(true, true).hide().slideDown();
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
	// Filter an Kursliste oeffnen und schliessen
	$('.wisyr_filtergroup, .wisyr_filtergroup > legend').on('click', function (e) {
		if (e.target !== e.currentTarget && !$(e.target).hasClass('ui-selectmenu-text') && !$(e.target).hasClass('ui-selectmenu-button')) return;

		// In Desktopansicht nicht auf Klicks auf ui-selectmenu-text reagieren
		var isMobile = $(window).width() < 761;
		if (!isMobile && ($(e.target).hasClass('ui-selectmenu-text') || $(e.target).hasClass('ui-selectmenu-button'))) return;

		if ($(e.target).hasClass('wisyr_filtergroup') || $(e.target).hasClass('ui-selectmenu-text') || $(e.target).hasClass('ui-selectmenu-button')) {
			$group = $(this);
		} else {
			$group = $(this).parent();
		}

		var wasActive = $group.hasClass('active');
		$('.wisyr_filterform fieldset.active').removeClass('active');
		$('.wisyr_filterform').removeClass('subActive');
		if (wasActive) {
			$(document).off('click.filtergroup');
		} else {
			$group.addClass('active');
			$('.wisyr_filterform').addClass('subActive');

			// Filter an Kursliste schliessen wenn ausserhalb geklickt wird
			$(document).on('click.filtergroup', function (event) {
				$target = $(event.target);

				if ($target.closest('.Zebra_DatePicker').length) return;
				if ($target.hasClass('clear_btn')) return;
				if ($target.closest('.wisyr_filtergroup').length) return;
				if ($target.closest('.ui-autocomplete').length) return;
				if ($target.closest('.ui-menu-item').length) return;

				$(document).off('click.filtergroup');
				$('.wisyr_filtergroup.active').removeClass('active');
				$('.wisyr_filterform').removeClass('subActive');
			});
		}

		// Sonderfall Sortierfeld
		if ($group.hasClass('filter_sortierung')) {
			// Open selectmenus if present
			if (wasActive) {
				$group.find('.wisyr_selectmenu').selectmenu('close');
			} else {
				$group.find('.wisyr_selectmenu').selectmenu('open');
			}
		}
		e.stopPropagation();
		return false;
	});

	// Filtervorschlaege befuellen automatisch Inputs
	$('.wisyr_filter_autofill input[type="radio"]').on('change', function () {
		$this = $(this);
		$target = $($this.data('autofilltarget'));
		if ($target.length) {
			$target.val($this.data('autofillvalue'));
		}
	});

	// Eventuelle weitere Instanzen dieses Filters auf gleichen Wert setzen vor dem Abschicken
	$('.wisyr_filterform input, .wisyr_filterform select, wisyr_selectmenu').on('change selectmenuchange', function () {
		var $this = $(this);
		var newVal = $this.val();
		if ($this.attr('type') == 'checkbox' && !$this.prop('checked')) newVal = '';

		// Selectmenus
		$('[name="' + $this.attr('name') + '"] [value="' + $this.val() + '"]').val(newVal);

		// Inputs etc.
		$('input:not([type="checkbox"])[name="' + $this.attr('name') + '"]').val(newVal);

		// Checkboxes
		$('input[type="checkbox"][name="' + $this.attr('name') + '"]').prop('checked', false);
		if (newVal != '') {
			$('input[type="checkbox"][name="' + $this.attr('name') + '"][value="' + $this.val() + '"]').prop('checked', true);
		}
	});

	// Filter automatisch abschicken
	$('.wisyr_filter_autosubmit .filter_submit').hide();
	$('.wisyr_filter_autosubmit input, .wisyr_filter_autosubmit select').on('change', function () {

		// Freie Eingaben zuruecksetzen bei autosubmit
		$(this).parents('.wisyr_filter_autosubmit')
			.siblings('.wisyr_filter_autoclear')
			.find('input:not([type=submit]), select')
			.val('');

		$('.wisyr_filterform form').submit();
	});

	// Filterlink Checkboxen automatisch abschicken
	$('.filter_checkbox').on('change', function () {
		var target = $(this).siblings('a').attr('href');
		if (target != '') $(location).attr('href', target);
	});

	// Suchfeld "clear input button"
	$('#wisy_searchinput').on('input', function () {
		updateClearInput($('#wisy_searchinput'), $('.wisyr_searchinput'));
	});
	updateClearInput($('#wisy_searchinput'), $('.wisyr_searchinput'));

	// Generischer "clear input button" fuer Filter
	$('.filter_clearbutton_wrapper').each(function (i, el) {
		$wrapper = $(this);
		$input = $wrapper.children('input');

		updateClearInput($input, $wrapper);
		$input.on('input', function () {
			$input = $(this);
			$wrapper = $input.parents('.filter_clearbutton_wrapper');
			updateClearInput($input, $wrapper);
		});
	});

	function updateClearInput($el, $wrapper) {
		if ($el) {
			if ($el.val() && $el.val().length) {
				if ($wrapper.children('.clear_btn').length == 0) {
					$wrapper.append('<div class="clear_btn" aria-label="Eingabe l&ouml;schen"></div>');
					$wrapper.children('.clear_btn').one('click', function () {
						// Wert von qs leeren und auch aus q entfernen
						var oldVal = $el.val();
						var oldQVal = $('#wisy_searchinput_q').val();
						$('#wisy_searchinput_q').val(oldQVal.replace(oldVal, ''));
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
	$('#wisy_searchinput').on('autocompleteopen', function (event) {
		$('#wisy_searchinput').autocomplete("widget").width($('#wisy_searchinput').outerWidth());
	});

	// Autosuggest Field in Filter same width as input field:
	$('#filter_bei').on('autocompleteopen', function (event) {
		$('#filter_bei').autocomplete("widget").width($('#filter_bei').outerWidth());
	});

	// Ortsfilter Umkreis nur enablen wenn Ort eingegeben ist
	$('#filter_bei').on('input', function () {
		updateUmkreisFilter();
	});
	updateUmkreisFilter();

	function updateUmkreisFilter() {
		$bei = $('#filter_bei');
		if ($bei && $bei.length) {
			if ($bei.val() != '') {
				$('.filter_ortundumkreis_umkreis input[type="radio"]').prop('disabled', false);
			} else {
				$('.filter_ortundumkreis_umkreis input[type="radio"]').prop('disabled', true);
			}
		}
	}

	// Sortierung Selectfeld via jQuery UI stylebar machen
	$(".filter_weiterekriterien .wisyr_selectmenu").selectmenu();
	$(".filter_sortierung .wisyr_selectmenu").selectmenu({
		change: function (event, ui) {
			$('.wisyr_filterform form').submit()
		}
	});

	if ($(".wisyr_datepicker").length) {
		// Zebra Datepicker fuer Datum in Filtern
		$(".wisyr_datepicker").Zebra_DatePicker(
			{
				format: 'd.m.Y',
				days: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
				months: ['Januar', 'Februar', 'M&auml;rz', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
				lang_clear_date: 'Auswahl entfernen',
				show_icon: false,
				open_on_focus: true,
				show_select_today: false,
				direction: 1 /* start calendar tomorrow, because start dates of DF in the past not searchable at this point */
			}
		);
	}

	/* strike tags in filters that are double tags (compared to tags already part of search) */
	/* strike_doubleTag_filter();  activate? */
}

function initFiltersMobile() {
	$("#wisy_filterlink").on('click', function (e) {
		if (e.target !== e.currentTarget) return;
		$('body').addClass('wisyr_filterform_active');
		e.stopPropagation();
		return false;
	});
	$('.wisyr_filterform_header').on('click', function (e) {
		if (e.target !== e.currentTarget) return;
		$('body').removeClass('wisyr_filterform_active');
		e.stopPropagation();
		return false;
	});

	// Zweite Filterebene wird mobil wie erste Filterebene behandelt
	$('.wisyr_filtergroup.filter_weiterekriterien > .filter_inner > fieldset, .wisyr_filterform form fieldset.filter_weiterekriterien > .wisyr_filtergroup > .filter_inner > fieldset, .wisyr_filtergroup.filter_weiterekriterien > .filter_inner > fieldset > legend, .wisyr_filterform form fieldset.filter_weiterekriterien > .wisyr_filtergroup > .filter_inner > fieldset > legend').on('click', function (e) {
		// Workaround fuer "Alle".
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
		if (e.target !== e.currentTarget) return;

		if ($(e.target).is('fieldset')) {
			$group = $(this);
		} else {
			$group = $(this).parent();
		}

		if ($group.hasClass('active')) {
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
				$group.find('.wisyr_selectmenu').on('selectmenuselect', function (event, ui) {
					$(event.toElement).parent().parent().find('.wisyr_selectedmobile').removeClass('wisyr_selectedmobile');
					$(event.toElement).parent().addClass('wisyr_selectedmobile');
				});
			} else {
				$group.find('.wisyr_selectmenu').one('selectmenuchange', function (event, ui) {
					$('.wisyr_filterform form').submit();
				});
			}
		}

		e.stopPropagation();
	});
}

/*****************************************************************************
 * main entry point
 *****************************************************************************/

var main_Initialized = false;
$().ready(function () {
	if (main_Initialized) return;
	main_Initialized = true;

	// Init autocomplete function
	// Use v2 if available
	if (jQuery.ui) {
		initAutocomplete_v2();
	} else {
		initAutocomplete();
	}

	// init dropdown
	if ($.browser && $.browser.msie && $.browser.version == '6.0') {
		$("ul.dropdown li").dropdown();
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

	// init filter stuff
	initFilters();
	initFiltersMobile();

	// append parameter for searches triggered by human interaction
	if ($("#wisy_searchbtn")) {
		$("#wisy_searchbtn").click(function (event) {
			if (event.originalEvent === undefined) {
				/* robot: console.log(event); */
			} else {
				event.preventDefault();
				$(this).before("<input type=hidden id=qsrc name=qsrc value=s>");
				$(this).before("<input type=hidden id=qtrigger name=qtrigger value=h>");
				$(this).closest("form").submit();
			}
		});
		$(".wisyr_filtergroup .filter_submit").click(function (event) {
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
	
	 // Make sure human triggere fulltext search through menu appends necessary parameters
	 $('a[data-searchtype="volltext"]').click(function(event){
	    event.preventDefault();
	    let href = $(this).attr('href');
	    href += href.includes('?') ? '&qsrc=m&qtrigger=h' : '?qsrc=m&qtrigger=h';
	    window.location.href = href;
	 });
	
	 // Add human trigger signal to fulltext links.
	 // They should only have this parameter through Javascript as simple "if human check", so fulltext search isn't indexed by search engine etc.
	 if (jQuery('.wisyp_search').length) { 
	    jQuery('a[data-volltextlink]').each(function() { 
	        let url = jQuery(this).attr('href');
	        url += url.includes('?') ? '&qtrigger=h' : '?qtrigger=h';
	        jQuery(this).attr('href', url); 
	    }); 
	 }
	
	 // Add signal to menu link clicks
	 if (jQuery('#themenmenue').length) {
	    jQuery('#themenmenue .dropdown a').click(function() { 
	        let url = jQuery(this).attr('href');
	        url += url.includes('?') ? '&qtrigger=m' : '?qtrigger=m';
	        jQuery(this).attr('href', url); 
	    });
	 }


});


function initializeTranslate() {
	if ($.cookie('cconsent_translate') == "allow") {
		/* console.log("consented"); */
		$.loadScript('//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit', function () {
			/* console.log('Loaded Google Translate'); */
			if (jQuery("#google_translate_element").length) {
				jQuery("#google_translate_element").closest("li").show().addClass("translate");
			}
		});

		// Mobile uses a different menu structure => #google_translate_element exists twice
		if (windowDims.width < 801) {
			/* console.log("Mobile width  >"+windowDims.width+"<"); */
			translateHtml = jQuery("#subnav #google_translate_element").html();
			jQuery("#subnav #google_translate_element").remove();
			jQuery("#google_translate_element").html(translateHtml).addClass("mobile");

			jQuery('.goog-te-menu-frame').contents().find('.goog-te-menu2').css(
				{
					'max-width': '100%',
					'overflow': 'scroll',
					'box-sizing': 'border-box',
					'height': 'auto'
				}
			)
		} else {
			/* console.log("Desktop width  >"+windowDims.width+"<"); */
		}

	} else {
		/* Interaction not disirable */
		/*
  hightlightCookieConsentOption('translate');
  window.cookieconsent.popup.open();
  return false; */
	}
};

function googleTranslateElementInit() {
	new google.translate.TranslateElement({
		pageLanguage: 'de',
		layout: google.translate.TranslateElement.InlineLayout.SIMPLE
	}, 'google_translate_element');
}

$(document).ready(function () {
	// $('#filter_datum_von').after('<a href="#filter_datum_von" onclick="alleDatenAnzeigen()" id="abgelaufeneAnzeigen">Abgelaufene Angebote anzeigen...</a>'); // noch framework
	preventEmptySearch(window.homepage); // noch framework
	consentCookieBeforePageFunction();
});


function hightlightCookieConsentOption(name) {
	$('.cc-consent-details .' + name + ' .consent_option_infos').addClass('highlight');
}

// check for consent of specific cookie, if page dependant on it being given
function consentCookieBeforePageFunction() {

	// Edit page
	if ($(".wisyp_edit").length) {

		// jQuery("input[type=password").length necessary b/c distinguishable from confirmation of AGB
		if ($(".wisyp_edit form[action=edit]").length && jQuery("input[type=password]").length && typeof $._data($(".wisyp_edit form[action=edit]"), "events") == 'undefined') {
			$('.wisyp_edit form[action=edit]').on('submit', function (e) {
				e.preventDefault();

				if ($.cookie('cconsent_onlinepflege') != "allow" && !window.cookiebanner_zustimmung_onlinepflege_legacy) {
					alert("Um die Onlinepflege nutzen zu k" + oe + "nnen, m" + ue + "ssen Sie dem Speichern von Cookies f" + ue + "r diese Funktion zustimmen (im Cookie-Hinweisfenster).");
					hightlightCookieConsentOption('onlinepflege');
					window.cookieconsent.popup.open();
					return false;
				}

				// default: normal search on other than homepage
				this.submit();

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
var Ue = unescape("%DC");
/*****************************************************************************
 * info text popup
 *****************************************************************************/

$(window).load(function () {
	$('.hover_bkgr_fricc').show();
	$('.popupCloseButton').click(function () {
		$('.hover_bkgr_fricc').hide();

		if (window.cookieconsent.popup)
			window.cookieconsent.popup.open();
	});

	// if old cookie banner is active: set cookie immediately that msg has been viewed for 3 days
	jQuery(".hover_bkgr_fricc .popupCloseButton").click(function () {
		if (jQuery(".cc-consent-details li").length > 0)
			;
		else {
			setCookieSafely('cconsent_popuptext', "allow", {expires: 3});
		}
	});

	// Hide load / wait message after page has actually completed loading (esp. fulltext)
	if (jQuery(".laden").length)
		jQuery(".laden").remove();

});


/*****************************************************************************
 * Anbietermaske Niveaustufen
 *****************************************************************************/

$(document).ready(function () {

	/*    $('textarea#tiny').tinymce({
            height: 250,
            menubar: false,
            skin: 'borderless',
            plugins: [
                'autolink', 'lists', 'link', 'image',
            ],
            toolbar: '|bold italic| ' +  '|bullist numlist| superscript | insert/edit link'
        });*/

	/*
        tinymce.init({
            selector: '#tiny',
           // statusbar: false,
            height: 500,
            resize: true,
            branding    : false,  // Remove the "Powered by Tiny"
            elementpath : false,  // Stop showing the selected element TAG
            menubar: false,
            plugins: ['autoresize autolink  link lists ','searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'],
            toolbar: '| bold italic | bullist numlist | superscript | link ',
            valid_elements: 'strong/b,i/em,br,p,ul,ol,li',
            //   content_css: '/www.tinymce.com/css/codepen.min.css',
        });
    */


	//Updatet die Niveaustufen zwischen Faehigkeiten und Entwicklung.
	$('.niveau-wissen').click(function () {
		$('.niveau-menu-intro').css({'display': 'block'});
		// $('.niveau-menu').css({'display': 'block'});
		$('.niveau-menu-intro2').fadeIn(1000);
		// $('.niveau-menu').css({'height': '500px'});
		$('.niveau-wissen').css({"background-color": "#0F3B7F"});
		$('.niveau-wissen p').css({"color": "#fff"});
		$('.niveau-entwicklung').css({"background-color": "white"});
		$('.niveau-entwicklung p').css({"color": "#000"});

		$('.niv-text-a').text("Die Teilnehmenden erlangen Kompetenzen zur Erf" + ue + "llung grundlegender Aufgaben nach Anleitung in einem " + ue + "berschaubaren Bereich.");
		$('.niv-text-b').text("Die Teilnehmenden erlangen Kompetenzen zur " + ue + "berwiegend selbstst" + ae + "ndigen Umsetzung erweiterter Aufgaben in einem sich teilweise ver" + ae + "ndernden Bereich.");
		$('.niv-text-c').text("Die Teilnehmenden erlangen Kompetenzen zur selbstst" + ae + "ndigen Umsetzung vertiefter spezialisierter Aufgaben in sich h" + ae + "ufig ver" + ae + "ndernden Bereichen.");
		$('.niv-text-d').text("Die Teilnehmenden erlangen Kompetenzen zur eigenverantwortlichen Umsetzung komplexer Aufgaben in " + ue + "bergreifenden Bereichen.");
	});

	$('.niveau-entwicklung').click(function () {
		$('.niveau-menu-intro').css({'display': 'none'});
		$('.niveau-menu-intro2').fadeIn(1000);
		$('.niveau-menu').css({'height': '500px'});
		$('.niveau-entwicklung').css({"background-color": "#0F3B7F"});
		$('.niveau-entwicklung p').css({"color": "#fff"});
		$('.niveau-wissen').css({"background-color": "white"});
		$('.niveau-wissen p').css({"color": "#000"});

		$('.niv-text-a').text("Die Teilnehmenden erlangen Kompetenzen um das eigende Handeln und das anderer wahrzunehmen und einzusch" + ae + "tzen.");
		$('.niv-text-b').text("Die Teilnehmenden erlangen Kompetenzen um in einer Gruppe mitzuwirken bzw. diese mitzugestalten und Unterst" + ue + "tzung anbieten zu k" + oe + "nnen.");
		$('.niv-text-c').text("Die Teilnehmenden erlangen Kompetenzen um Prozesse kooperativ zu planen und zu gestalten, sowie andere anzuleiten.");
		$('.niv-text-d').text("Die Teilnehmenden erlangen Kompetenzen um Personen, Gruppen oder Organisationen anzuleiten oder zu f" + ue + "hren");

	});

	//(Modal) Hilfe fuer die Niveaustufen
	$('.niv-infoA').click(function () {
		$('.niveauInfo-a').css("display", "block");
	});

	$('.niv-infoB').click(function () {
		$('.niveauInfo-b').css("display", "block");
	});

	$('.niv-infoC').click(function () {
		$('.niveauInfo-c').css("display", "block");
	});

	$('.niv-infoD').click(function () {
		$('.niveauInfo-d').css("display", "block");
	});


	$('.niveauInfo-close').click(function () {
		$('.niveauInfo').css("display", "none");
		$('body').css('overflow-y', 'auto');
	});

	/*    //Nur fuer Hessen
        $('.wisy-foerderungen-checkbox').on('change', function () {
            $('.wisy-foerderungen-checkbox').not(this).prop('checked', false);
        });*/

	$('.wisy-lernform-kategorie input[type="checkbox"]').on('change', function () {
		$('.wisy-lernform-kategorie input[type="checkbox"]').not(this).prop('checked', false);
	});

	var lernform = '';
	// Wenn sich der Auswahlzustand des Radiobuttons ändert
	$('input[name=learning-type]').on('change', function () {
		// Holen Sie den Text des ausgewählten Radiobuttons
		lernform = $('input[name=learning-type]:checked').parent().text().trim();
	});


	// Erstelle eine leere Liste von ausgewählten Werten
	var selectedValues = [];
	$('#add-stw').click(function () {
		var selectedword = $('#stichwortvorschlag').val();

		// Überprüfen, ob der ausgewählte Wert bereits gesucht wurde
		if (selectedValues.includes(selectedword)) {
			return false;
		} else {
// Hinzufügen des ausgewählten Werts zur Liste der bereits gesuchten Begriffe
			selectedValues.push(selectedword);
			var stichwortvorschlag = $('<span>', {
				class: 'esco-komp stichwortvorschlag-kompetenz',
				css: {
					backgroundColor: '#88ff00',
					fontSize: '14px',
					color: 'black',
					border: '1px solid black',
					borderRadius: '15px',
					padding: '5px 10px',
					position: 'relative'
				},
				text: selectedword
			});
			// Schließfunktion hinzufügen
			var closeButton = $('<button>', {
				text: 'x',
				class: 'close-button',
				css: {
					backgroundColor: 'red',
					color: 'white',
					borderRadius: '50%',
					border: 'none',
					width: '20px',
					height: '20px',
					position: 'absolute',
					top: '-10px',
					right: '-10px',
					cursor: 'pointer'
				},
				on: {
					click: function () {
						var selectedword = $(this).parent().text().trim();
						selectedword = selectedword.slice(0, -1);
						var index = selectedValues.indexOf(selectedword);
						if (index !== -1) {
							selectedValues.splice(index, 1);
						}
						$(this).parent().remove();
					},
					mouseenter: function () {
						$(this).css('background-color', 'darkred');
					},
					mouseleave: function () {
						$(this).css('background-color', 'red');
					}
				}
			});
			stichwortvorschlag.append(closeButton);
			$('.stichwort-area').append(stichwortvorschlag);
		}
	});

	//Abschluss erst nach tippen anzeigen
	/*
        $('.abschluss-select').on('input', function () {
            if ($(this).val().length >= 3) {
                $(this).attr('list', 'abschlussarten');
            } else {
                $(this).removeAttr('list');
            }
        }).on('blur', function () {
            $(this).removeAttr('list');
        });

        //Lernform erst nach tippen anzeigen.
        $('.lernform-select').on('input', function () {
            if ($(this).val().length >= 1) {
                $(this).attr('list', 'lernformart');
            } else {
                $(this).removeAttr('list');
            }
        }).on('blur', function () {
            $(this).removeAttr('list');
        });
    */


	/* ###################################################################################################################
     *
     * Ki Einbindung
     *
     */

	let lernzieltext = $('.wisy-lernziel-text');
	let kurstiteltext = $('.wisy-kurs-titel');
	let kursbeschreibung = $('.wisy-kursbeschreibung-text');

	// Event-Listener hinzufügen
	kursbeschreibung.on('blur', function () {
		// neuen Text abrufen
		let newText = $(this).val();

		// prüfen, ob der Text sich geändert hat
		if (newText !== kursbeschreibung) {
			// neuen Text als ursprünglichen Text speichern
			kursbeschreibung = newText;

			// JSON-String erstellen
			let jsonData = JSON.stringify({lernzieltext: kursbeschreibung, kurstiteltext: kurstiteltext});

			// Ajax-Request senden
			$.ajax({
				url: '/edit?action=kursniveaustufe',
				method: 'POST',
				data: jsonData,
				dataType: 'json',
				contentType: 'application/json',
				success: function (response) {
					// Erfolgs-Callback
					// $('.stichwort-area').html(JSON.stringify(response));
					//    var json = JSON.stringify(response);
					//    var level = JSON.parse(json).level;
					//   $('.stichwort-area').html(level);

					// Daten aus der Ki-Empfehlung auslesen.
					var json = JSON.stringify(response);
					var level = JSON.parse(json).level;

					switch (level) {
						case 'A':
							level = 'Grundstufe';
							break;
						case 'B':
							level = 'Aufbaustufe';
							break;
						case 'C':
							level = 'Fortgeschrittenenstufe';
							break;
						case 'D':
							level = 'Expert*innenstufe';
							break;
						default:
							level = 'Unbekannte Stufe';
					}


					if ($('#bildung-checkbox').prop('checked') === true) {
						var kiNiveaustufe = $('<span>', {
							class: 'wisy-kiNiveaustufe',
							css: {
								color: 'green',
								fontsize: '14px',
								marginTop: '10px',
								marginBottom: '10px'
							},
							text: ' Anhand Ihrer Angaben zu Kurstitel und Kursbeschreibung empfehlen wir Ihnen den Kurs als ' + level + ' einzustufen.'
						});
					}
					if ($('.niv-header span .wisy-kiNiveaustufe').length > 0) {
						$('.niv-header span .wisy-kiNiveaustufe').replaceWith(kiNiveaustufe);
					} else {
						$('.niv-header span').html(kiNiveaustufe);
					}
					$('.wisy-kiNiveaustufe').each(function () {
						var txt = $(this);
						txt.html(txt.text()
							.replace(level, '<strong>' + level + '</strong>'));
					});
					let imageNiv = $('<img>').attr({
						'src': 'files/lamp.png',
						'width': '20px',
						'height': '20px'
					});

					if ($('.wisy-kiniveaustufe img').length > 0) {
						$('.wisy-kiniveaustufe img').replaceWith(imageNiv);
					} else {
						$('.wisy-kiNiveaustufe').before(imageNiv);
					}


					//  console.log(level);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// Fehler-Callback
					console.log('Fehler beim Senden des Ajax-Requests: ' + textStatus + ', ' + errorThrown);
				}
			});
		}
	});


	//ALS JSON

	// Event-Listener hinzufügen
	lernzieltext.on('blur', function () {
		// neuen Text abrufen
		let newText = $(this).val();

		// prüfen, ob der Text sich geändert hat
		if (newText !== lernzieltext) {
			// neuen Text als ursprünglichen Text speichern
			lernzieltext = newText;

			// JSON-String erstellen
			let jsonData = JSON.stringify({lernzieltext: lernzieltext, kurstiteltext: kurstiteltext});

			// Ajax-Request senden
			$.ajax({
				url: '/edit?action=kursniveaustufe',
				method: 'POST',
				data: jsonData,
				dataType: 'json',
				contentType: 'application/json',
				success: function (response) {
					// Erfolgs-Callback
					// $('.stichwort-area').html(JSON.stringify(response));
					//    var json = JSON.stringify(response);
					//    var level = JSON.parse(json).level;
					//   $('.stichwort-area').html(level);

					// Daten aus der Ki-Empfehlung auslesen.
					var json = JSON.stringify(response);
					var level = JSON.parse(json).level;

					switch (level) {
						case 'A':
							level = 'Grundstufe';
							break;
						case 'B':
							level = 'Aufbaustufe';
							break;
						case 'C':
							level = 'Fortgeschrittenenstufe';
							break;
						case 'D':
							level = 'Expert*innenstufe';
							break;
						default:
							level = 'Unbekannte Stufe';
					}

					if ($('#bildung-checkbox').prop('checked') === true) {
						var kiNiveaustufe = $('<span>', {
							class: 'wisy-kiNiveaustufe',
							css: {
								color: 'green',
								fontsize: '14px',
								marginTop: '10px',
								marginBottom: '10px'
							},
							text: ' Anhand Ihrer Angaben zu Kurstitel und Kursbeschreibung empfehlen wir Ihnen den Kurs als ' + level + ' einzustufen.'
						});
					}
					if ($('.niv-header span .wisy-kiNiveaustufe').length > 0) {
						$('.niv-header span .wisy-kiNiveaustufe').replaceWith(kiNiveaustufe);
					} else {
						$('.niv-header span').html(kiNiveaustufe);
					}

					$('.wisy-kiNiveaustufe').each(function () {
						var txt = $(this);
						txt.html(txt.text()
							.replace(level, '<strong>' + level + '</strong>'));
					});
					let imageNiv = $('<img>').attr({
						'src': 'files/lamp.png',
						'width': '20px',
						'height': '20px'
					});
					if ($('.wisy-kiniveaustufe img').length > 0) {
						$('.wisy-kiniveaustufe img').replaceWith(imageNiv);
					} else {
						$('.wisy-kiNiveaustufe').before(imageNiv);
					}                    //  console.log(level);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// Fehler-Callback
					console.log('Fehler beim Senden des Ajax-Requests: ' + textStatus + ', ' + errorThrown);
				}
			});
		}
	});

	kurstiteltext.on('blur', function () {
		// neuen Text abrufen
		let newText = $(this).val();
		var kiNiveaustufe = '';

		if (lernzieltext === '') {
			lernzieltext = kursbeschreibung.text();
		}

		// prüfen, ob der Text sich geändert hat
		if (newText !== kurstiteltext) {
			// neuen Text als ursprünglichen Text speichern
			kurstiteltext = newText;

			// JSON-String erstellen
			let jsonData = JSON.stringify({lernzieltext: lernzieltext, kurstiteltext: kurstiteltext});
			clearTimeout(timeoutId);
			timeoutId = setTimeout(function () {
				// Ajax-Request senden
				$.ajax({
					url: '/edit?action=kursniveaustufe',
					method: 'POST',
					data: jsonData,
					dataType: 'json',
					contentType: 'application/json',
					success: function (response) {
						// Erfolgs-Callback
						//  $('.stichwort-area').html(JSON.stringify(response));
						/*       var level = JSON.parse(response).level;
                               $('.stichwort-area').html(level);
                               console.log(level);*/
						// Daten aus der Ki-Empfehlung auslesen.
						var json = JSON.stringify(response);
						var level = JSON.parse(json).level;

						switch (level) {
							case 'A':
								level = 'Grundstufe';
								break;
							case 'B':
								level = 'Aufbaustufe';
								break;
							case 'C':
								level = 'Fortgeschrittenenstufe';
								break;
							case 'D':
								level = 'Expert*innenstufe';
								break;
							default:
								level = 'Unbekannte Stufe';
						}

						if ($('#bildung-checkbox').prop('checked') === true) {
							kiNiveaustufe = $('<span>', {
								class: 'wisy-kiNiveaustufe',
								css: {
									color: 'green',
									fontsize: '14px',
									marginTop: '10px',
									marginBottom: '10px'
								},
								text: '  Anhand Ihrer Angaben zu Kurstitel und Kursbeschreibung empfehlen wir Ihnen den Kurs als ' + level + ' einzustufen.'
							});
						}
						$('.wisy-kiNiveaustufe').each(function () {
							var txt = $(this);
							txt.html(txt.text()
								.replace(level, '<strong>' + level + '</strong>'));
						});
						let imageNiv = $('<img>').attr({
							'src': 'files/lamp.png',
							'width': '20px',
							'height': '20px'
						});
						if ($('.wisy-kiniveaustufe img').length > 0) {
							$('.wisy-kiniveaustufe img').replaceWith(imageNiv);
						} else {
							$('.wisy-kiNiveaustufe').before(imageNiv);
						}                        //  $('.stichwort-area').html(level);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// Fehler-Callback
						console.log('Fehler beim Senden des Ajax-Requests: ' + textStatus + ', ' + errorThrown);
					}
				});
				if ($('.niv-header span .wisy-kiNiveaustufe').length > 0) {
					$('.niv-header span .wisy-kiNiveaustufe').replaceWith(kiNiveaustufe);
				} else {
					$('.niv-header span').html(kiNiveaustufe);
				}
				$('.niv-header p').append(kiNiveaustufe);


			}, 3500);
		}
	});

	/*    // OHNE JSON


        // Event-Listener hinzufügen
        $('.wisy-lernziel-text').on('input', function() {
            // neuen Text abrufen
            let newText = $(this).val();

            // prüfen, ob der Text sich geändert hat
            if (newText !== lernzieltext) {
                // neuen Text als ursprünglichen Text speichern
                lernzieltext = newText;

                // Ajax-Request senden
                $.ajax({
                    url: '/edit?action=kursniveaustufe',
                    method: 'POST',
                    data: { lernzieltext: lernzieltext, kurstiteltext: kurstiteltext },
                    success: function(response) {
                        // Erfolgs-Callback
                        console.log(response);
                        $('.stichwort-area').text(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Fehler-Callback
                        console.log('Fehler beim Senden des Ajax-Requests: ' + textStatus + ', ' + errorThrown);
                    }
                });
            }
        });

        $('.wisy-kurs-titel').on('change', function() {
            // neuen Text abrufen
            let newText = $(this).val();

            // prüfen, ob der Text sich geändert hat
            if (newText !== kurstiteltext) {
                // neuen Text als ursprünglichen Text speichern
                kurstiteltext = newText;

                // Ajax-Request senden
                $.ajax({
                    url: '/edit?action=kursniveaustufe',
                    method: 'POST',
                    data: { lernzieltext: lernzieltext, kurstiteltext: kurstiteltext },
                    success: function(response) {
                        // Erfolgs-Callback
                        $('.stichwort-area').text(response);
                        console.log(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Fehler-Callback
                        console.log('Fehler beim Senden des Ajax-Requests: ' + textStatus + ', ' + errorThrown);
                    }
                });
            }
        });*/
	/* ###################################################################################################################
     *
     *
     *
     */

	////HIER WAR SONST DOCUMENT ZUENDE

	$('.wisy-kategorie-check').on('change', function () {
		$('.wisy-kategorie-check').not(this).prop('checked', false);
	});

	$('select[name="beginnoptionen[]"]').on('change', function () {
		return $(this).val();
	});


	// ###########################################################################################################################################
///VORSCHAU

	$('#wisy-vorschau').on('click', function (event) {
		event.preventDefault();

		$('.wisy-vorschau-modal').css('display', 'block');
		$('body').css('overflow-y', 'hidden');
		/*
        Holt die Eingabe Felder für die Vorschau
         */
		//  var beschreibung = tinymce.get('textarea.wisy-kursbeschreibung-text').getContent();

		//  console.log(beschreibung);


		let kurstitel = $('.wisy-kurs-titel');
		let kursbeschreibung = $('.wisy-kursbeschreibung-text');
		let lernziel = $('.wisy-lernziel-text');
		let voraussetzung = $('.wisy-voraussetzung-text');
		let zielgruppe = $('.wisy-zielgruppe-text');
		let themaHauptkategorie = $('.wisy-select-hauptkategorie option:selected');
		let themaUnterkategorie = $('.wisy-select-unterkategorie option:selected');
		let niveaustufe = $('.wisy-niveaustufen-check:checked').val();
		let abschlussart = $('.abschluss-select');
		//  let lernform = $('.lernform-select');
		//  let foerderungart = $('.wisy-foerderungen-checkbox:checked');
		let hinweisRedaktion = $('.wisy-nachricht-content input');

		//  let foerderungenArray = $('input[name="useredit_stichwoerter"]');

		let vorschautablebody = $('#vorschau-table');
		// let newRow

		vorschautablebody.find('tbody').empty(); // leert vorherige Einträge


		// Geben Sie den Text zur Überprüfung auf der Konsole aus
		$('.wisy-vorschau-lernform').text('').text(lernform);

		/***
		 * Durchfuehrungsnummer des Kurses
		 */
		$('input[name="nr[]"]').each(function (index) {
			let durchf_nr = $(this).val();
			let tablerowNR = "<tr><td>" + durchf_nr + "</td>";
			vorschautablebody.find('tbody').append(tablerowNR);

			/***
			 * Dauer des Kurses (Datum)
			 * @type {*|string|jQuery}
			 */
			let durchf_terminBeginn = $('input[name="beginn[]"]').eq(index).val();
			let durchf_terminEnde = $('input[name="ende[]"]').eq(index).val();

			/***
			 * Wochentage der Durchfuehrungstage
			 * @type {string}
			 */
			let durchf_kurstageMo = $('input[name="kurstage0[]"]').eq(index).val() > 0 ? 'Mo ' : '';
			let durchf_kurstageDi = $('input[name="kurstage2[]"]').eq(index).val() > 0 ? 'Di ' : '';
			let durchf_kurstageMi = $('input[name="kurstage4[]"]').eq(index).val() > 0 ? 'Mi ' : '';
			let durchf_kurstageDo = $('input[name="kurstage6[]"]').eq(index).val() > 0 ? 'Do ' : '';
			let durchf_kurstageFr = $('input[name="kurstage8[]"]').eq(index).val() > 0 ? 'Fr ' : '';
			let durchf_kurstageSa = $('input[name="kurstage10[]"]').eq(index).val() > 0 ? 'Sa' : '';
			let durchf_kurstageSo = $('input[name="kurstage12[]"]').eq(index).val() > 0 ? 'So' : '';

			/***
			 * Terminoption Werte (Terminoptionen, Dauer, Tagescode)
			 * @type {jQuery|HTMLElement|*}
			 */
			let durchf_terminoptionErg = '';
			let durchf_terminoption = $('select[name="beginnoptionen[]"]').eq(index).val();
			if (durchf_terminoption === '1') durchf_terminoptionErg = 'Beginnt laufend';
			else if (durchf_terminoption === '2') durchf_terminoptionErg = 'Beginnt w' + oe + 'chentlich';
			else if (durchf_terminoption === '4') durchf_terminoptionErg = 'Beginnt monatlich';
			else if (durchf_terminoption === '8') durchf_terminoptionErg = 'Beginnt zweimonatlich';
			else if (durchf_terminoption === '16') durchf_terminoptionErg = 'Beginnt quartalweise';
			else if (durchf_terminoption === '32') durchf_terminoptionErg = 'Beginnt halbj' + ae + 'hrlich';
			else if (durchf_terminoption === '64') durchf_terminoptionErg = 'Beginnt j' + ae + 'hrlich';
			else if (durchf_terminoption === '256') durchf_terminoptionErg = 'Termin noch offen';
			else if (durchf_terminoption === '512') durchf_terminoptionErg = 'Startgarantie';

			let durchf_dauerErg = '';
			let durchf_dauer = $('select[name="dauer[]"]').eq(index).val();
			if (durchf_dauer === '1') durchf_dauerErg = '1 Tag';
			else if (durchf_dauer === '2') durchf_dauerErg = '2 Tage';
			else if (durchf_dauer === '3') durchf_dauerErg = '3 Tage';
			else if (durchf_dauer === '4') durchf_dauerErg = '4 Tage';
			else if (durchf_dauer === '5') durchf_dauerErg = '5 Tage';
			else if (durchf_dauer === '6') durchf_dauerErg = '6 Tage';
			else if (durchf_dauer === '7') durchf_dauerErg = '1 Woche';
			else if (durchf_dauer === '14') durchf_dauerErg = '2 Wochen';
			else if (durchf_dauer === '21') durchf_dauerErg = '3 Wochen';
			else if (durchf_dauer === '28') durchf_dauerErg = '4 Wochen';
			else if (durchf_dauer === '35') durchf_dauerErg = '5 Wochen';
			else if (durchf_dauer === '42') durchf_dauerErg = '6 Wochen';
			else if (durchf_dauer === '49') durchf_dauerErg = '7 Wochen';
			else if (durchf_dauer === '56') durchf_dauerErg = '8 Wochen';
			else if (durchf_dauer === '63') durchf_dauerErg = '9 Wochen';
			else if (durchf_dauer === '70') durchf_dauerErg = '10 Wochen';
			else if (durchf_dauer === '77') durchf_dauerErg = '11 Wochen';
			else if (durchf_dauer === '84') durchf_dauerErg = '12 Wochen';
			else if (durchf_dauer === '91') durchf_dauerErg = '13 Wochen';

			let durchf_tagescodeErg = '';
			let durchf_tagescode = $('select[name="tagescode[]"]').eq(index).val();
			if (durchf_tagescode === '1') durchf_tagescodeErg = 'Ganzt' + ae + 'gig';
			else if (durchf_tagescode === '2') durchf_tagescodeErg = 'Vormittags';
			else if (durchf_tagescode === '3') durchf_tagescodeErg = 'Nachmittags';
			else if (durchf_tagescode === '4') durchf_tagescodeErg = 'Abends';
			else if (durchf_tagescode === '5') durchf_tagescodeErg = 'Wochenende';

			/***
			 * Uhrzeit der Durchfuehrung
			 */

			let durchf_zeitvon = $('input[name="zeit_von[]"]').eq(index).val();
			let durchf_zeitbis = $('input[name="zeit_bis[]"]').eq(index).val();


			/***
			 * Stunden dauer der Durchfuehrung pro Tag
			 * @type {*|string|jQuery}
			 */
			let durchf_stunden = $('input[name="stunden[]"]').eq(index).val();

			/***
			 * Preis der Durchfuehrung
			 * @type {jQuery|HTMLElement|*}
			 */
			let durchf_preis = $('input[name="preis[]"]').eq(index).val();


			/***
			 * Teilnehmer begrenzung
			 * @type {jQuery|HTMLElement|*}
			 */
			let durchf_teilnehmer = $('input[name="teilnehmer[]"]').eq(index).val();

			/***
			 * Hinweis fuer die Durchfuehrung
			 * @type {jQuery|HTMLElement|*}
			 */
			let durchf_bemerkung = $('input[name="bemerkungen[]"]').eq(index).val();

			let durchf_url = $('input[name="url[]"]').eq(index).val();
			if (durchf_url !== '') {
				if (!/^https?:\/\//i.test(durchf_url)) {
					durchf_url = 'https://www.' + durchf_url;
				}
			} else {
				durchf_url = '';
			}

			//  let durchf_rollstuhl = $('input[name="rollstuhlgerecht[]"]').eq(index).prop('checked');
			let durchf_rollstuhl = '';
			if ($('input[name="rollstuhlgerecht[]"]').eq(index).prop('checked')) {
				durchf_rollstuhl = 'Barrierefreier Zugang';
			} else {
				durchf_rollstuhl = '';
			}

			let durchf_einstieg = '';
			if ($('input[name="einstieg[]"]').eq(index).prop('checked')) {
				durchf_einstieg = 'Einstieg jederzeit m' + oe + 'glich';
			} else {
				durchf_einstieg = '';
			}

			let durchf_sonderpreistage = $('input[name="sonderpreistage[]"]').eq(index).val();
			let durchf_sonderpreis = $('input[name="sonderpreis[]"]').eq(index).val();


			let durchf_preishinweis = $('input[name="preishinweise[]"]').eq(index).val();


			/***
			 * Strasse, PLZ, ORT Angaben
			 * @type {jQuery|HTMLElement|*}
			 */
			let durchf_strasse = $('input[name="strasse[]"]').eq(index).val();
			let durchf_plz = $('input[name="plz[]"]').eq(index).val();
			let durchf_ort = $('input[name="ort[]"]').eq(index).val();

			/***
			 * Fuegt alle eingaben in die Tabelle als Zeile
			 * @type {string}
			 */
			let tablerow = "<td>" + (durchf_terminBeginn.length > 0 ? (durchf_terminBeginn + (durchf_terminEnde.length > 0 ? ' - ' + durchf_terminEnde : '')) + "<br>" : '')
				+ durchf_kurstageMo + durchf_kurstageDi + durchf_kurstageMi + durchf_kurstageDo + durchf_kurstageFr + durchf_kurstageSa + durchf_kurstageSo
				+ (durchf_terminoptionErg.length > 0 ? "<br>" + durchf_terminoptionErg : '') + (durchf_zeitvon.length > 0 ? "<br>" + durchf_zeitvon + ' UHR' : '') + (durchf_zeitbis.length > 0 ? ' - ' + durchf_zeitbis + ' UHR' : '')
				+ "</td>" +
				"<td>" + durchf_dauerErg + (durchf_tagescodeErg.length > 0 ? "<br>" + durchf_tagescodeErg : '') + "</td>" +
				"<td>" + lernform + "</td>" +
				"<td>" + (durchf_preis.length > 0 ? durchf_preis + " EUR" + "<br>" : "") + (durchf_sonderpreis.length > 0 ? "Sonderpreis von " + durchf_sonderpreis + "EUR<br> nur noch " + durchf_sonderpreistage + " Tage <br>" : "") + (durchf_preishinweis.length > 0 ? durchf_preishinweis + "<br>" : "") + (durchf_stunden.length > 0 ? "(" + durchf_stunden + " Std.)" : "") + "</td>" +
				"<td>" + (durchf_strasse.length > 0 ? durchf_strasse + "<br>" : "") + (durchf_plz.length > 0 ? durchf_plz + " " : "") + (durchf_ort.length > 0 ? durchf_ort + "<br>" : "") + "</td>" +
				"<td>" + (durchf_teilnehmer.length > 0 ? "max. " + durchf_teilnehmer + " Teilnehmer:innen <br>" : "") + (durchf_bemerkung.length > 0 ? durchf_bemerkung + "<br>" : "") + (durchf_rollstuhl.length > 0 ? durchf_rollstuhl + "<br>" : "") + (durchf_einstieg.length > 0 ? durchf_einstieg + "<br>" : "") + (durchf_url.length > 0 ? " <a target=\"_blank\" href= " + durchf_url + ">zur Anmeldung</a>" + "<br>" : "") + "</td>";

			vorschautablebody.find('tbody tr:last').append(tablerow);
		});

		//   let durchf_terminoption = $('select[name="beginnoptionen[]"]');


		/*
        platziert die Eingaben in die Vorschau
         */
		if (kurstitel !== '') {
			$('.wisy-vorschau-kurstitel').text('').text(kurstitel.val());
			//  $(this).css('display', 'block');
		} else {
			//$(this).css('display', 'none');
			$('.wisy-vorschau-kurstitel').text('').text('Kursvorschau');
		}

		if (kursbeschreibung !== '') {
			$('.wisy-vorschau-inhalt').text('').text(kursbeschreibung.val());
			$(this).css('display', 'block');
		} else {
			$('.wisy-vorschau-inhalt').text('').text('Sie haben keine Kursbeschreibung');

			//   $('.wisy-vorschau-inhaltH').css('display', 'none');
			//   $('.wisy-vorschau-inhalt').css('display', 'none');
		}

		if (lernziel !== '') {
			$('.wisy-vorschau-lernziel').text('').text(lernziel.val());
			//  $(this).css('display', 'block');
		} else {
			$('.wisy-vorschau-lernziel').text('').text('Sie haben kein Lernziel!');
			//  $(this).css('display', 'none');
		}

		if (voraussetzung !== '') {
			$('.wisy-vorschau-voraussetzung').text('').text(voraussetzung.val());
			//  $(this).css('display', 'block');
		} else {
			$('.wisy-vorschau-voraussetzung').text('').text('Sie haben keine Voraussetzungen in Ihrem Kurs!');
			//  $(this).css('display', 'none');
		}

		if (zielgruppe !== '') {
			$('.wisy-vorschau-zielgruppe').text('').text(zielgruppe.val());
			//   $(this).css('display', 'block');
		} else {
			$('.wisy-vorschau-zielgruppe').text('').text('Sie haben keine Zielgruppe festgelegt!');
			//  $(this).css('display', 'none');
		}

		if (themaHauptkategorie !== '' && themaUnterkategorie !== '')
			//  $('.wisy-vorschau-thema').html((themaHauptkategorie.text() === 'Hauptkategorie' ? '' : themaHauptkategorie.text() + " - ") + themaUnterkategorie.text());
			$('.wisy-vorschau-thema').html(themaUnterkategorie.text());


		$('.wisy-vorschau-kursniveau').text('').text(niveaustufe !== undefined ? niveaustufe : 'Keine Angabe');


		if (abschlussart !== '') $('.wisy-vorschau-abschluss').text('').text(abschlussart.val());
		else $('.wisy-vorschau-abschluss').text('').text('Ihr Kurs hat keinen Abschluss!');

		//ALTE LERNFORM
		/*  if (lernform !== '') $('.wisy-vorschau-lernform').text('').text(lernform.val());
          else $('.wisy-vorschau-lernform').text('').text('Bitte legen Sie eine Lernform f' + ue + 'r Ihren Kurs an!');
  */
		var ausgewaehlteTexte = "";

		$('.wisy-foerderungen-checkbox').each(function () {
			var isChecked = $(this).prop('checked');
			//  var value = $(this).val();
			var labelText = $(this).siblings('label').text();


			if (isChecked) {
				ausgewaehlteTexte += (ausgewaehlteTexte.length > 0 ? ', ' + labelText : labelText);
				if (labelText === 'Aktivierungsgutschein' || labelText === 'Bildungsgutschein') {
					ausgewaehlteTexte += ($('.wisy-foerderungsnummerA').val() !== '' ? (' +AZAV NR.: ' + $('.wisy-foerderungsnummerA').val()) : '');
				}
				if (labelText === 'Bildungsurlaub') {
					ausgewaehlteTexte += ($('.wisy-foerderungsnummerB').val() !== '' ? (' +NR.: ' + $('.wisy-foerderungsnummerB').val()) : '');
				}
				// ausgewaehlteTexte += (ausgewaehlteTexte.length > 0 ? (', ' + ((labelText==='Bildungsgutschein' || labelText==='Bildungsurlaub')? (labelText+' - ' +$('input[name="'+labelText+'"]').val()) :(labelText))) : labelText);
				//  console.log('Die Checkbox mit dem Wert ' + labelText + ' ist ausgewählt.');
				//  console.log((labelText==='Bildungsgutschein' || labelText==='Bildungsurlaub')? (labelText+' - ' +$('input[name="'+labelText+'"]').val()) :(labelText));
			}
			$('.wisy-vorschau-foerderung').text(ausgewaehlteTexte);
		});

		$('.wisy-vorschau-stichwort').text('').text(selectedValues.length > 0 ? selectedValues : '');
		$('.wisy-vorschau-nachricht').text('').text(hinweisRedaktion !== '' ? hinweisRedaktion.val() : '');
	});

	/*    $('input[name="ende[]"]').each(function () {
            // $('.wisy-vorschau-durchfuehrung').html($(this).val());
            var durchf = $('<span>', {
                class: 'durchf-nr-',
                css: {
                    backgroundColor: '#eaffd5',
                    color: 'black'
                },
                text: $(this).val()
            });
            $('.wisy-vorschau-durchfuehrung').append(durchf);
        });*/


	//import initOccupationStep from "./wisyki-scout.js";

//Kompetenzen manuell einfuegen


	$('#stichwortvorschlag').autocomplete({
		source: function (request, response) {
			$.ajax({
				url: "/esco/autocomplete?",
				dataType: "json",
				data: {
					term: request.term,
					limit: 10,
					scheme: "member-skills,skills-hierarchy,sachstichwort",
					onlyrelevant: false
				},
				success: function (data) {
					var labels = [];
					for (var k in data) {
						labels.push({label: data[k].label});
					}
					response(labels);
				}
			});
		},
		select: function (event, ui) {
			// Überprüfen, ob der ausgewählte Wert bereits gesucht wurde
			if (selectedValues.includes(ui.item.value)) {
				return false;
			} else {
				// Hinzufügen des ausgewählten Werts zur Liste der bereits gesuchten Begriffe
				selectedValues.push(ui.item.value);

				var escoKomp = $('<span>', {
					text: ui.item.value,
					class: "esco-komp esco-kompetenzen-" + ui.item.value,
					css: {
						backgroundColor: '#66ccff',
						fontSize: '14px',
						color: 'black',
						border: '1px solid black',
						borderRadius: '15px',
						padding: '5px 10px',
						position: 'relative'
					},
				});
				// Schließfunktion hinzufügen
				var closeButton = $('<button>', {
					text: 'x',
					class: 'close-button',
					css: {
						backgroundColor: 'red',
						color: 'white',
						borderRadius: '50%',
						border: 'none',
						width: '20px',
						height: '20px',
						position: 'absolute',
						top: '-10px',
						right: '-10px',
						cursor: 'pointer'
					},
					on: {
						click: function () {
							var selectedword = $(this).parent().text().trim();
							selectedword = selectedword.slice(0, -1);
							var index = selectedValues.indexOf(selectedword);
							if (index !== -1) {
								selectedValues.splice(index, 1);
							}
							$(this).parent().remove();
						},
						mouseenter: function () {
							$(this).css('background-color', 'darkred');
						},
						mouseleave: function () {
							$(this).css('background-color', 'red');
						}
					}

				});
				escoKomp.append(closeButton);
				$('.stichwort-area').append(escoKomp);
				$(this).val("");
				return false;
			}
		}
	});


	/*

        $('#stichwortvorschlag').keyup(function () {
            var escovar = $(this).val();
            const escoURL = "https://ec.europa.eu/esco/api/search?"; // esco webapi url
            const escoURI = escoparams(escovar, 'de', 'skill', 'false', 'true', 5);

            const buildEscoURL = escoURL + escoURI;
            var output = [];
            $.getJSON(buildEscoURL, function (data) {
                $.each(data._embedded.results, function (key, value) {
                    output.push(value.title);
                });
            });
            $('#stichwortvorschlag').autocomplete({
                source: function (request, response) {
                    response($.map(output, function (value) {
                        return {
                            label: value,
                            value: value
                        }
                    }));
                },
                select: function (event, ui) {
                    var escoKomp = $('<span>', {
                        text: ui.item.value,
                        class: "esco-komp esco-kompetenzen-" + ui.item.value,
                        css: {
                            backgroundColor: '#66ccff',
                            fontsize: '24px',
                            color: 'black',
                            border: '1px solid black'
                        },
                    });
                    $('.stichwort-area').append(escoKomp);
                    $(this).val("");
                    return false;
                }
            });
        });

    */

	$('.wisy-select-hauptkategorie').on('change', function () {
		var url = '/edit?action=hauptthema';
		var option = $(this).val();

		//  console.log(option);
		$.ajax({
			type: 'POST',
			url: url, // Hier den Pfad zu Ihrer PHP-Datei angeben
			data: {hauptthema: option},
			success: function (response) {
				$('.wisy-select-unterkategorie').html(response);
				// $('.stichwort-area').html(response);
				//     console.log(response);
			},
			error: function () {
				$('.stichwort-area').html('fehler');
			}
		});
	});

// Überprüfen, ob eine Checkbox ausgewählt wurde, bevor ein Textfeld aktiviert wird
	var checkboxSelected = false;
	$('.wisy-kategorie-check').on('change', function () {
		if ($(this).is(':checked')) {
			checkboxSelected = true;
		} else {
			checkboxSelected = $('.wisy-kategorie-check:checked').length > 0;
		}
	});

// Überprüfen, ob ein Textfeld aktiviert wurde, bevor eine Checkbox ausgewählt wurde
	$('textarea, input[type=text], select, .wisy-foerderungen-checkbox, .abschluss-select, .lernform-select, a, small, #wisy-kurseinstieg, .wisy-lernform-kategorie input').on('focus', function () {
		// Prüfen, ob das Textfeld bereits aktiviert ist, bevor die Alert-Nachricht angezeigt wird
		if (!checkboxSelected && !$(this).hasClass('active')) {
			//alert('Bitte wählen Sie zuerst mindestens eine Checkbox aus.');
			$('.modal-kategoriebg').css({'display': 'block'});
		} else {
			$(this).addClass('active');
		}
	});

// Entfernen Sie die "active"-Klasse, wenn das Textfeld den Fokus verliert
	$('textarea, input[type=text], select, .wisy-foerderungen-checkbox, .abschluss-select, .lernform-select, a, small, .wisy-lernform-kategorie input').on('blur', function () {
		$(this).removeClass('active');
	});


	$('.wisy-kategorie-check').on('change', function (event) {
		event.preventDefault();
		var url = '/edit?action=kategorie';
		var checkboxValues = {};
		$(this).each(function () {
			checkboxValues[this.name] = this.checked ? this.value : '';
		});

		$.ajax({
			url: url,
			type: 'POST',
			data: {checkboxValues: checkboxValues},
			success: function (response) {
				$('.niveau-stufen').html(response);

				/*  if ($('#andere-checkbox').prop('checked') === true) {
                      $('.wisy-kursniveau-cell').css({'display': 'block'});
                  } else {
                      $('.wisy-kursniveau-cell').css({'display': 'none'});
                  }*/

				if ($('#bildung-checkbox').prop('checked') === true || $('#sprachkurs-checkbox').prop('checked') === true) {
					$('.wisy-kursniveau-cell').css({'display': 'block'});
				} else {
					$('.wisy-kursniveau-cell').css({'display': 'none'});
				}

				//   if (!$('.wisy-kategorie-check').prop('checked')) $('.niveau-stufen').css({'display': 'none'})

				$('.niveau-stufen').css({'display': 'block'})

				//Updatet die Niveaustufen zwischen Faehigkeiten und Entwicklung.
				$('.niveau-wissen').click(function () {
					$('.niveau-menu-intro').css({'display': 'none'});
					// $('.niveau-menu').css({'display': 'block'});
					$('.niveau-menu-intro2').fadeIn(1000);
					// $('.niveau-menu').css({'height': '500px'});
					$('.niveau-wissen').css({"background-color": "#0F3B7F"});
					$('.niveau-wissen p').css({"color": "#fff"});
					$('.niveau-entwicklung').css({"background-color": "white"});
					$('.niveau-entwicklung p').css({"color": "#000"});

					$('.niv-text-a').text("Die Teilnehmenden erlangen Kompetenzen zur Erf" + ue + "llung grundlegender Aufgaben nach Anleitung in einem " + ue + "berschaubaren Bereich.");
					$('.niv-text-b').text("Die Teilnehmenden erlangen Kompetenzen zur " + ue + "berwiegend selbstst" + ae + "ndigen Umsetzung erweiterter Aufgaben in einem sich teilweise ver" + ae + "ndernden Bereich.");
					$('.niv-text-c').text("Die Teilnehmenden erlangen Kompetenzen zur selbstst" + ae + "ndigen Umsetzung vertiefter spezialisierter Aufgaben in sich h" + ae + "ufig ver" + ae + "ndernden Bereichen.");
					$('.niv-text-d').text("Die Teilnehmenden erlangen Kompetenzen zur eigenverantwortlichen Umsetzung komplexer Aufgaben in " + ue + "bergreifenden Bereichen.");
				});

				$('.niveau-entwicklung').click(function () {
					$('.niveau-menu-intro').css({'display': 'none'});
					$('.niveau-menu-intro2').fadeIn(1000);
					//  $('.niveau-menu').css({'height': '500px'});
					$('.niveau-entwicklung').css({"background-color": "#0F3B7F"});
					$('.niveau-entwicklung p').css({"color": "#fff"});
					$('.niveau-wissen').css({"background-color": "white"});
					$('.niveau-wissen p').css({"color": "#000"});

					$('.niv-text-a').text("Die Teilnehmenden erlangen Kompetenzen, um das eigende Handeln und das anderer wahrzunehmen und einzusch" + ae + "tzen.");
					$('.niv-text-b').text("Die Teilnehmenden erlangen Kompetenzen, um in einer Gruppe mitzuwirken bzw. diese mitzugestalten und Unterst" + ue + "tzung anbieten zu k" + oe + "nnen.");
					$('.niv-text-c').text("Die Teilnehmenden erlangen Kompetenzen, um Prozesse kooperativ zu planen und zu gestalten sowie andere anzuleiten.");
					$('.niv-text-d').text("Die Teilnehmenden erlangen Kompetenzen, um Personen, Gruppen oder Organisationen anzuleiten oder zu f" + ue + "hren");

				});

				//(Modal) Hilfe fuer die Niveaustufen
				$('.niv-infoA').click(function () {
					$('.niveauInfo-a').css("display", "block");
				});

				$('.niv-infoB').click(function () {
					$('.niveauInfo-b').css("display", "block");
				});

				$('.niv-infoC').click(function () {
					$('.niveauInfo-c').css("display", "block");
				});

				$('.niv-infoD').click(function () {
					$('.niveauInfo-d').css("display", "block");
				});


				$('.niveauInfo-close').click(function () {
					$('.niveauInfo').css("display", "none");
					$('body').css('overflow-y', 'auto');
				});

				$('.wisy-niveaustufen-check').on('change', function () {
					$('.wisy-niveaustufen-check').not(this).prop('checked', false);
				});
			},
			error: function () {
				console.log('FEHLER: Beim Empfangen der Themen ist ein Problem entstanden.');
			}
		});
	});


	let kursspeichern = [];

	$('.wisy-vorschau-speichern').click(function () {
		let kurstitel = $('.wisy-kurs-titel').val();
		let kursbeschreibung = $('.wisy-kursbeschreibung-text').val();
		let lernziel = $('.wisy-lernziel-text').val();
		let voraussetzung = $('.wisy-voraussetzung-text').val();
		let zielgruppe = $('.wisy-zielgruppe-text').val();
		let themaHauptkategorie = $('.wisy-select-hauptkategorie option:selected').text();
		let themaUnterkategorie = $('.wisy-select-unterkategorie option:selected').text();
		let niveaustufe = $('.wisy-niveaustufen-check:checked').val();
		let abschlussart = $('.abschluss-select').val();
		//  let lernform = $('.lernform-select').val(); ALT
		let hinweisRedaktion = $('.wisy-nachricht-content input').val();

		let durchfArray = [];

		$('input[name="nr[]"]').each(function (index) {
			let durchf_nr = $(this).val();
			let durchf_terminBeginn = $('input[name="beginn[]"]').eq(index).val();
			let durchf_terminEnde = $('input[name="ende[]"]').eq(index).val();
			let durchf_kurstageMo = $('input[name="kurstage0[]"]').eq(index).val() > 0 ? 'Mo ' : '';
			let durchf_kurstageDi = $('input[name="kurstage2[]"]').eq(index).val() > 0 ? 'Di ' : '';
			let durchf_kurstageMi = $('input[name="kurstage4[]"]').eq(index).val() > 0 ? 'Mi ' : '';
			let durchf_kurstageDo = $('input[name="kurstage6[]"]').eq(index).val() > 0 ? 'Do ' : '';
			let durchf_kurstageFr = $('input[name="kurstage8[]"]').eq(index).val() > 0 ? 'Fr ' : '';
			let durchf_kurstageSa = $('input[name="kurstage10[]"]').eq(index).val() > 0 ? 'Sa' : '';
			let durchf_kurstageSo = $('input[name="kurstage12[]"]').eq(index).val() > 0 ? 'So' : '';

			let durchf_terminoptionErg = '';
			let durchf_terminoption = $('select[name="beginnoptionen[]"]').eq(index).val();
			if (durchf_terminoption === '1') durchf_terminoptionErg = 'Beginnt laufend';
			else if (durchf_terminoption === '2') durchf_terminoptionErg = 'Beginnt w' + oe + 'chentlich';
			else if (durchf_terminoption === '4') durchf_terminoptionErg = 'Beginnt monatlich';
			else if (durchf_terminoption === '8') durchf_terminoptionErg = 'Beginnt zweimonatlich';
			else if (durchf_terminoption === '16') durchf_terminoptionErg = 'Beginnt quartalweise';
			else if (durchf_terminoption === '32') durchf_terminoptionErg = 'Beginnt halbj' + ae + 'hrlich';
			else if (durchf_terminoption === '64') durchf_terminoptionErg = 'Beginnt j' + ae + 'hrlich';
			else if (durchf_terminoption === '256') durchf_terminoptionErg = 'Termin noch offen';
			else if (durchf_terminoption === '512') durchf_terminoptionErg = 'Startgarantie';

			let durchf_dauerErg = '';
			let durchf_dauer = $('select[name="dauer[]"]').eq(index).val();
			if (durchf_dauer === '1') durchf_dauerErg = '1 Tag';
			else if (durchf_dauer === '2') durchf_dauerErg = '2 Tage';
			else if (durchf_dauer === '3') durchf_dauerErg = '3 Tage';
			else if (durchf_dauer === '4') durchf_dauerErg = '4 Tage';
			else if (durchf_dauer === '5') durchf_dauerErg = '5 Tage';
			else if (durchf_dauer === '6') durchf_dauerErg = '6 Tage';
			else if (durchf_dauer === '7') durchf_dauerErg = '1 Woche';
			else if (durchf_dauer === '14') durchf_dauerErg = '2 Wochen';
			else if (durchf_dauer === '21') durchf_dauerErg = '3 Wochen';
			else if (durchf_dauer === '28') durchf_dauerErg = '4 Wochen';
			else if (durchf_dauer === '35') durchf_dauerErg = '5 Wochen';
			else if (durchf_dauer === '42') durchf_dauerErg = '6 Wochen';
			else if (durchf_dauer === '49') durchf_dauerErg = '7 Wochen';
			else if (durchf_dauer === '56') durchf_dauerErg = '8 Wochen';
			else if (durchf_dauer === '63') durchf_dauerErg = '9 Wochen';
			else if (durchf_dauer === '70') durchf_dauerErg = '10 Wochen';
			else if (durchf_dauer === '77') durchf_dauerErg = '11 Wochen';
			else if (durchf_dauer === '84') durchf_dauerErg = '12 Wochen';
			else if (durchf_dauer === '91') durchf_dauerErg = '13 Wochen';
			let durchf_tagescodeErg = '';
			let durchf_tagescode = $('select[name="tagescode[]"]').eq(index).val();
			if (durchf_tagescode === '1') durchf_tagescodeErg = 'Ganzt' + ae + 'gig';
			else if (durchf_tagescode === '2') durchf_tagescodeErg = 'Vormittags';
			else if (durchf_tagescode === '3') durchf_tagescodeErg = 'Nachmittags';
			else if (durchf_tagescode === '4') durchf_tagescodeErg = 'Abends';
			else if (durchf_tagescode === '5') durchf_tagescodeErg = 'Wochenende';

			let durchf_zeitvon = $('input[name="zeit_von[]"]').eq(index).val();
			let durchf_zeitbis = $('input[name="zeit_bis[]"]').eq(index).val();
			let durchf_stunden = $('input[name="stunden[]"]').eq(index).val();
			let durchf_preis = $('input[name="preis[]"]').eq(index).val();
			let durchf_teilnehmer = $('input[name="teilnehmer[]"]').eq(index).val();
			let durchf_bemerkung = $('input[name="bemerkungen[]"]').eq(index).val();
			let durchf_url = $('input[name="url[]"]').eq(index).val();
			if (durchf_url !== '') {
				if (!/^https?:\/\//i.test(durchf_url)) {
					durchf_url = 'https://www.' + durchf_url;
				}
			} else {
				durchf_url = '';
			}

			// let durchf_rollstuhl = $('input[name="rollstuhlgerecht[]"]').eq(index).val();
			let durchf_rollstuhl = '';
			if ($('input[name="rollstuhlgerecht[]"]').eq(index).prop('checked')) {
				durchf_rollstuhl = 'Barrierefreier Zugang';
			} else {
				durchf_rollstuhl = '';
			}

			let durchf_einstieg = '';
			if ($('input[name="einstieg[]"]').eq(index).prop('checked')) {
				durchf_einstieg = 'Einstieg jederzeit m' + oe + 'glich';
			} else {
				durchf_einstieg = '';
			}
			let durchf_sonderpreistage = $('input[name="sonderpreistage[]"]').eq(index).val();
			let durchf_sonderpreis = $('input[name="sonderpreis[]"]').eq(index).val();
			let durchf_preishinweis = $('input[name="preishinweise[]"]').eq(index).val();
			let durchf_strasse = $('input[name="strasse[]"]').eq(index).val();
			let durchf_plz = $('input[name="plz[]"]').eq(index).val();
			let durchf_ort = $('input[name="ort[]"]').eq(index).val();


			var ausgewaehlteTexte = "";
			$('.wisy-foerderungen-checkbox').each(function () {
				var isChecked = $(this).prop('checked');
				var labelText = $(this).siblings('label').text();
				if (isChecked) {
					ausgewaehlteTexte += (ausgewaehlteTexte.length > 0 ? ', ' + labelText : labelText);
					if (labelText === 'Aktivierungsgutschein' || labelText === 'Bildungsgutschein') {
						ausgewaehlteTexte += ($('.wisy-foerderungsnummerA').val() !== '' ? (' +AZAV NR.: ' + $('.wisy-foerderungsnummerA').val()) : '');
					}
					if (labelText === 'Bildungsurlaub') {
						ausgewaehlteTexte += ($('.wisy-foerderungsnummerB').val() !== '' ? (' +NR.: ' + $('.wisy-foerderungsnummerB').val()) : '');
					}
				}
			});

			let data = {
				'kurstitel': kurstitel,
				'kursbeschreibung': kursbeschreibung,
				'lernziel': lernziel,
				'voraussetzung': voraussetzung,
				'zielgruppe': zielgruppe,
				'themaHauptkategorie': themaHauptkategorie,
				'themaUnterkategorie': themaUnterkategorie,
				'niveaustufe': niveaustufe,
				'abschlussart': abschlussart,
				'lernform': lernform,
				'foerderungen': ausgewaehlteTexte,
				'stichwort': selectedValues,
				'hinweisRedaktion': hinweisRedaktion
			};

			let durchfTag = {
				'durchf_Nr': durchf_nr,
				'datum': durchf_terminBeginn + ' - ' + durchf_terminEnde,
				'kurstage': durchf_kurstageMo + ' ' + durchf_kurstageDi + ' ' + durchf_kurstageMi + ' ' + durchf_kurstageDo + ' ' + durchf_kurstageFr + ' ' + durchf_kurstageSa + ' ' + durchf_kurstageSo,
				'terminoption': durchf_terminoptionErg,
				'dauer': durchf_dauerErg,
				'tagescode': durchf_tagescodeErg,
				'uhrzeiten': durchf_zeitvon + ' - ' + durchf_zeitbis,
				'stunden': durchf_stunden,
				'preis': durchf_preis,
				'sonderpreis': durchf_sonderpreis,
				'sonderpreistage': durchf_sonderpreistage,
				'preishinweis': durchf_preishinweis,
				'strasse': durchf_strasse,
				'plz': durchf_plz,
				'ort': durchf_ort,
				'teilnehmer': durchf_teilnehmer,
				'bemerkung': durchf_bemerkung,
				'url': durchf_url,
				'barrierefrei': durchf_rollstuhl,
				'einstieg': durchf_einstieg
			};
			durchfArray.push(durchfTag);
			kursspeichern.push(data);
			kursspeichern.push(durchfArray);
		});
		let jsonString = JSON.stringify(kursspeichern);
		//  console.log(jsonString);

		$.ajax({
			type: 'post',
			url: '/edit?action=kursspeichern',
			data: {kurseingabe: jsonString},
			success: function (response) {
				console.log(response);
				//  $('.stichwort-area').text(response);
			},
			error: function (response) {
				console.log('fehler');
			}
		});
	});
});


/**
 * Kursansicht scroll to Titel
 */
window.onload = function() {
	var element = document.querySelector('.wisyr_kursinfos');
	element.scrollIntoView();
};

function updateAccordion(sectionId, checkboxId, imageId) {
	var section = document.getElementById(sectionId);
	var checkbox = document.getElementById(checkboxId);
	var image = document.getElementById(imageId);
	var content = section.querySelector('.wisy_foerber_con');

	if (checkbox.checked) {
		section.classList.add('active');
		content.style.display = 'block';
		image.style.transform = 'rotate(0deg)';
	} else {
		section.classList.remove('active');
		content.style.display = 'none';
		image.style.transform = 'rotate(180deg)';
	}
}

/*

function escoparams(text, language, type, full, alt, limit) {
    var str = new URLSearchParams({
        text: text,
        language: language,
        type: type,
        full: full,
        alt: alt,
        limit: limit
    });
    
   // if old cookie banner is active: set cookie immediately that msg has been viewed for 3 days
   jQuery(".hover_bkgr_fricc .popupCloseButton").click(function() {
     if(jQuery(".cc-consent-details li").length > 0 )
       ;
     else {
      setCookieSafely('cconsent_popuptext', "allow", { expires:3}); 
     }
   });                        
   
   // Hide load / wait message after page has actually completed loading (esp. fulltext)
   if(jQuery(".laden").length)
    jQuery(".laden").remove();
   
 });
 
/***********************************************
 * viewport size
 ***********************************************/
function dw_getWindowDims() {
    var doc = document, w = window;
    var docEl = (doc.compatMode && doc.compatMode === 'CSS1Compat') ? doc.documentElement : doc.body;
    
    var width = docEl.clientWidth;
    var height = docEl.clientHeight;
    
    // mobile zoomed in?
    if ( w.innerWidth && width > w.innerWidth ) {
        width = w.innerWidth;
        height = w.innerHeight;
    }
    
    return {width: width, height: height};
}

var windowDims = dw_getWindowDims();

/***********************************************
 * editor
 ***********************************************/

/* WYSIWYG */
$(function() { 
 if( $('.wisyp_edit').length && $('.format_buttons').length ) { 
  $('.format_buttons a').click(function(e) {
   switch( $(this).data('role') ) {
    case 'p':
		break;
    default:
		// If using contentEditable: execCommand() affects the currently active editable element!
		document.execCommand($(this).data('role'), false, null);
    	break;
   }
  });
 }
});

/* submit edit form after checks etc. */
function finalizeSubmit() {
	
	// write editable div content to textarea
	// b/c only textarea content can be processed as form element
	jQuery('#beschreibung').text( jQuery( '#edit_beschreibung' ).html() );
	
	// write editable divs content to next element (textarea)
	jQuery('.edit_bemerkungen').each(function(){ 
		jQuery(this).next().val( jQuery(this).html()); 
	});
	
	// Get the form element
    var form = jQuery('form[name=kurs]');

    // Check if the form is valid using the reportValidity method
	// = browser form check via pattern attribute
    if (form[0].reportValidity()) {
	
    	// If the form is valid, submit form
        form.submit();

    }

}
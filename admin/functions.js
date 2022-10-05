var ANIM_DURATION = 200;
var AJAX_INDICATOR = '<img src="skins/default/img/ajaxload-16x11.gif " width="16" height="11" alt="" />';



/******************************************************************************
Autocomplete Handling
******************************************************************************/

function ac_href(href)
{
	// open the href in a blank window
	window.open(href, "_blank");

	// stop event propagation
	event.cancelBubble = true; // ie
	event.returnValue = false;
 	if ( event.stopPropagation ) event.stopPropagation(); // firefox/chrom
	if ( event.preventDefault ) event.preventDefault();

	return false;
}

function ac_sourcecallback(request, response_callback)
{
	// get the corresponding input object
	var jqObj = this.options.theinput;
	
	// calculate the new source url
	var url = "autocomplete.php?acdata=" + jqObj.attr("data-acdata");
	var jqSelectObj = jqObj.parent().find("select.acselect"); 
	if( jqSelectObj.length == 1 ) {
		url = url + "&select=" + jqSelectObj.val();  
	}
	url += "&term=" + encodeURIComponent(request.term);
	
	// ask the server for suggestions
	$.getJSON(url, function(json_data) { response_callback(json_data); });
}

function ac_selectcallback(event, ui)
{
	var jqObj = $(this);
	
	if( typeof ui.item.nest == "object" )
	{
		// invoke the nest
		var index = -1;
		jqObj.parent().find('.acnest').each(function() 
			{
				if( jqObj.get(0) == $(this).get(0) ) {
					index = 0; // found anchor, next one is fine
				}
				else if( index >= 0 ) {
					$(this).val(ui.item.nest[index]);
					index++;
				}
			});
	}
	
	return true;
}

function ac_init()
{
	// make all inputfields with the class "acclass" an autocomplete widget; forward "data-acdata" to "accallback.php"
	$("input.acclass").each(function() {
			var jqObj = $(this);
			jqObj.autocomplete({
					source:		ac_sourcecallback
				,	theinput:	jqObj
				,	html:		true
				,	select:		ac_selectcallback
			});
		}
	);
	
	

}



/******************************************************************************
jQuery UI Autocomplete HTML Extension 
Copyright 2010, Scott González (http://scottgonzalez.com)
Dual licensed under the MIT or GPL Version 2 licenses. 
http://github.com/scottgonzalez/jquery-ui-extensions
******************************************************************************/

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



/******************************************************************************
A _small_ jQuery extension ...
******************************************************************************/


jQuery.fn.reverse = [].reverse;


/******************************************************************************
Things to do on load
******************************************************************************/

function init_tb_table()
{
	$('table.tb > tbody > tr a').click(function() {
		setClickConsumed(); // avoid rowclicks (clicktr) is a normal hyperlink is clicked
	});
	$('table.tb > tbody > tr').click(function() {
		var jqObj = $(this).find('a.clicktr');
		if( !isClickConsumed() && jqObj.length == 1 ) {
			window.location = jqObj.attr('href');
			return false;
		}
		
	});
}


var documentLoaded = 0;
$().ready(function()
{
	documentLoaded = 1;
	
	// init data table
	init_tb_table();
	ac_init();
	edit_modify_interfacedetails();
});

function edit_modify_interfacedetails() {
	if(window.location.href.match(/edit/i)) {

		// make some input fields adapt to longer text dynamically as per editors request
		jQuery("input[name='f_postname'], input[name='f_pflege_email'], input[name='f_anspr_email']").each(function(){
		 if(jQuery(this).val().length > 3)
			jQuery(this).css("width", ((jQuery(this).val().length+1)*7)+"px");
		});
		
	}
}


/******************************************************************************
Click Tracking
******************************************************************************/

var clickConsumed = 0;

function setClickConsumed()
{
	var today = new Date();
	clickConsumed = today.getTime()+1000;
}

function isClickConsumed()
{
	var today = new Date();
	var clickCurr = today.getTime();
	if( clickCurr > clickConsumed ) {
		return false;
	}
	else {
		return true;
	}
}



/******************************************************************************
String Functions
******************************************************************************/

function htmlspecialchars(str)
{
	str = str.replace(/&/ig, "&amp;");
	str = str.replace(/"/ig, "&quot;");
	str = str.replace(/</ig, "&lt;");
	str = str.replace(/>/ig, "&gt;");
	return str;
}

function htmlconstant(str)
{
	return eval('typeof ' + str + '=="undefined"?"' + str + '":' + str);
}



/******************************************************************************
Find HTML Objects in the DOM (deprecated stuff before using jQuery)
******************************************************************************/

function findForm(nameForm)
{
	var i;
	for( i = 0; i < self.document.forms.length; i++ ) {
		if( self.document.forms[i].name == nameForm )  {
			return self.document.forms[i];
		}
	}
	return 0;
}

function findFormElement(nameForm, nameElement)
{
	var oForm = findForm(nameForm), oElement;
	if( oForm ) {
		var i;
		for( i = 0; i < oForm.elements.length; i++ ) {
			if( oForm.elements[i].name == nameElement ) {
				return oForm.elements[i];
			}
		}
	}
	return 0;
}

function findDhtmlObj(n /*name*/, d /*document*/)
{
	var p, i, x;

	if( !d ) {
		d = document;
	}

	if( (p=n.indexOf("?")) >0 && parent.frames.length) {
	    d = parent.frames[n.substring(p+1)].document;
	    n = n.substring(0,p);
	}

	if( !(x=d[n]) && d.all ) {
		x = d.all[n];
	}

	for (i=0; !x && i<d.forms.length; i++) {
		x = d.forms[i][n];
	}

	for(i=0;!x&&d.layers&&i<d.layers.length;i++) {
		x = findDhtmlObj(n, d.layers[i].document);
	}

	if(!x && document.getElementById) {
		x = document.getElementById(n);
	}

	return x;
}



/******************************************************************************
Alter HTML objects in the DOM
******************************************************************************/

/*	roll an image by the image source base name, use as
	<a href="..." onmouseover="rollA(this,'file');"><img src=".../file.gif" /></a>,
	the onmouseout-handler is automatically added if missing
*/
function rollA(theAnchor, baseImgName) 
{
	var rollImgName		= baseImgName + 'roll.gif';
	var norollImgName	= baseImgName + '.gif';
	var istImgName, istImgPath, i, j, d = window.document;

	if( typeof theAnchor.onmouseout != 'function' ) {
		theAnchor.onmouseout = new Function("rollA(this,'" + baseImgName + "');");
	}

	for( i = 0; i < d.images.length; i++ ) {
		istImgName = d.images[i].src;
		j = istImgName.lastIndexOf('/');
		if( j > 0 ) {
			istImgName = istImgName.substring(j+1);
			if( istImgName == rollImgName || istImgName == norollImgName ) {
				istImgPath = d.images[i].src;
				istImgPath = istImgPath.substring(0,j+1);
				d.images[i].src = istImgPath + (istImgName==rollImgName? norollImgName : rollImgName);
				return;
			}
		}
	}
}

/*	roll an image an given image object, use as
	<img src="..." onmouseover="rollI(this);" />, 
	the onmouseout-handler is automatically added if missing
*/
function rollI(theImg) 
{
	var s = theImg.src;
	
	if( typeof theImg.onmouseout != 'function' ) {
		theImg.onmouseout = new Function("rollI(this);");
	}

	if ( s.substring(s.length-8, s.length) == 'roll.gif' ) {
		s = s.substring(0, s.length-8) + '.gif';
	}
	else {
		s = s.substring(0, s.length-4) + 'roll.gif';
	}
	
	theImg.src = s;
}



/******************************************************************************
Popup Handling
******************************************************************************/

function popup(theAnchor, window_w, window_h, href, target)
{
	// remove focus -- do this first to avoid coming the new window into the background
	if( theAnchor!==0 && theAnchor.blur ) {
		theAnchor.blur();
	}

    // open new window;
    // no spaces in string, bug in function window.open()
    var w = window.open(href===undefined? theAnchor.href : href, 
		target===undefined? theAnchor.target : target,
		'width=' + window_w + ',height=' + window_h + ',resizable=yes,scrollbars=yes');
    
    // bring window to top
    if( !w.opener ) {
    	w.opener = self;
    }
    	
    if( w.focus != null ) {
    	w.focus();
    }
	
	// store window object in the context of the current window
	if( theAnchor!==0 ) {
		eval(theAnchor.target + "=w;");
	}

	// avoid standard hyperlink processing
	setClickConsumed();
    return false;
}

function popdown(theAnchor) 
{
	if( window.opener && !window.opener.closed )
	{
		window.opener.location.href = theAnchor.href;
		return false; // avoid standard hyperlink processing
	}
	else {
		return true; // continue with standard hyperlink processing
	}
}

var popfitted = 0;
function popfit(w)
{
	var currOuterWidth, currOuterHeight, chromeWidth, chromeHeight, newInnerHeight, newOuterHeight;
	
	if( !w ) {
		w = window;
	}

	// find out the outer/chrome width/height
	if( w.outerHeight )
	{
		currOuterWidth	= w.outerWidth;
		currOuterHeight	= w.outerHeight;
		chromeWidth		= currOuterWidth - w.innerWidth;
		chromeHeight	= w.outerHeight - w.innerHeight;
	}
	else if( document.body && document.body.offsetWidth )
	{
	    var offW		= document.body.offsetWidth;
	    var offH		= document.body.offsetHeight;			
	    var fixedW		= offW;
		var fixedH		= offH;
	    w.resizeTo(fixedW, fixedH);
		var diffW		= document.body.offsetWidth  - offW;
		var diffH		= document.body.offsetHeight - offH;
		currOuterWidth	= fixedW - diffW;
		currOuterHeight	= fixedH - diffH;
		chromeWidth 	= currOuterWidth - offW;
		chromeHeight	= currOuterHeight - offH;
		w.resizeTo(currOuterWidth, currOuterHeight);
	}
	else {
		return;
	}

	// get the max. height
	var maxH = 450;
	if( screen && screen.availHeight && screen.availHeight > 480 ) {
		maxH = screen.height - 160;
		if( maxH > 800 ) {
			maxH = 800;
		}
	}

	// find out the new inner/outer height
	if( document.body && document.body.scrollHeight ) {
		newInnerHeight = document.body.scrollHeight;
	}
	else {
		return;
	}

	newOuterHeight = newInnerHeight + chromeHeight;
	if( newOuterHeight > maxH ) {
		newOuterHeight = maxH;
	}
	
	// finally, resize window
	w.resizeTo(currOuterWidth, newOuterHeight);
	
	popfitted = 1;
}

function selUpdtOpnr(id)
{
	if( window.opener && !window.opener.closed && window.opener.rcv_id_selection )
	{
		var opener = window.opener;
		window.close();
		opener.rcv_id_selection(id);
	}
	else
	{
		alert('Das zu dieser Attributauswahl geh'+oe+'rige Fenster ist bereits geschlossen.');
	}
}

/******************************************************************************
The Old Edit Dialog
******************************************************************************/

function editTgAttr(theAnchor, uncheck_other /*may be set to imgFolder*/)
{
	var i, curr_img_name, window_url, ret;
	
	// toggle image, this will also change the attribute on the server
	ret = true;
	for( i = 0; i < document.images.length; i++ )
	{
		curr_img_name = document.images[i].name;
		if( curr_img_name == theAnchor.target ) // the target is the same as the image name
		{
			// set 'img' to a random number
			if( Math.random ) {
				window_url = theAnchor.href + '&img=' + (1+Math.random());
			}
			else {
				window_url = theAnchor.href + '&img=1';
			}
			
			// change image
			document.images[i].src = window_url;
			
			if( theAnchor.blur ) {
				theAnchor.blur();
			}
			
			ret = false; 
			if( uncheck_other == 0 ) {
				setClickConsumed();
				return ret;
			}
		}
		else 
		{
			if( uncheck_other != 0 )
			{
				// uncheck other images if needed
				img_img_name_prefix = curr_img_name.substring(0,2);
				if( img_img_name_prefix == 'tg' ) 
				{
					document.images[i].src = uncheck_other + '/check0.gif';
				}
			}
		}
	}
    
    setClickConsumed();
    return ret; 
}

function setFocus(focusElement)
{
	var f, currElement;
	for( f = 0; f < self.document.forms.length; f++ ) 
	{
		currElement = findFormElement(self.document.forms[f].name, focusElement);
		if( currElement ) {
			currElement.focus();
			return;
		}
	}
}

function allNone(formName, prefixName)
{
	var form = findForm(formName), e, checkall;
	if( form ) {
		// find out if to check/uncheck all
		checkall = 0;
		for( e = 0; e < form.elements.length; e++ ) { 
			elem = form.elements[e];
			if( elem.type == 'checkbox' ) {
				if( elem.name.substring(0,prefixName.length)==prefixName ) {
					if( !elem.checked ) {
						checkall = 1;
						break;
					}
				}
			}
		}
		// check/uncheck all
		for( e = 0; e < form.elements.length; e++ ) { 
			elem = form.elements[e];
			if( elem.type == 'checkbox' ) {
				if( elem.name.substring(0,prefixName.length)==prefixName ) {
					elem.checked = checkall;
				}
			}
		}
	}
	return false;
}

function rowsPerPageSel(baseUrl)
{
	var i=prompt(htmlconstant('_RECORDSPERPAGE'), '');
	if(i) {
		window.location.href = baseUrl + i;
	}
	return false;
}

function pageSel(baseUrl, maxPage, maxOffset, rowsPerPage)
{
	var i;
	
	i = htmlconstant('_GOTOPAGE');
	
	i = i.split('$1');
	i = i[0] + '1-' + (maxPage+1) + i[1];

	i = i.split('$2');
	i = i[0] + '#0-#' + maxOffset + i[1];
	
	i=prompt(i, '');
	if( i!=null && i!='' ) 
	{
		if( i.charAt(0) == '#' ) 
		{
			// goto offset
			i = i.substring(1);
		}
		else
		{
			// goto page
			i = (i-1) * rowsPerPage;
		}
		
		window.location.href = baseUrl + i;
	}
	return false;
}



/******************************************************************************
Job Lists (Bin)
******************************************************************************/

// function changes all 'bin images' in the opener window regarding the
// given records. syntax for given records: "<table1>:<id1> <id2>...:<table2>:<id1>..."
function binUpdateOpener(selRecStr, listname, imgFolder)
{
	var sel_rec = new Object(), w, d, i, j, temp1, temp2, title;
	
	// create a hash from the given strings
	temp1 = selRecStr.split(':');
	for( i = 0; i < temp1.length; i+=2 ) {
		if( temp1[i] ) {
			temp2 = temp1[i+1].split(' ');
			for( j = 0; j < temp2.length; j++ ) {
				sel_rec['bin_' + temp1[i] + '_' + temp2[j]] = 1;
			}
		}
	}
	
	// get the new title
	title = htmlconstant('_REMEMBERRECORDINLIST').split('$1');
	title = title[0] + '"' + listname + '"' + title[1];
	
	// get opener window, go through all images / hyperlinks in this window
	w = window.opener;
	if( w && !w.closed ) {
		d = w.document;
		for( i = 0; i < d.images.length; i++ ) {
			temp1 = d.images[i].name;
			if( temp1 && temp1.substring(0,4) == "bin_" ) {
				d.images[i].src = imgFolder + (sel_rec[temp1]? '/bin1.gif' : '/bin0.gif');
				d.images[i].title = title;
			}
		}
	}
}

function binToggle(theImg, theUrl)
{
	var curr_img_name, window_url, ret;

	// close 'bin' and 'settings' window if opened
	if( typeof window.bin == 'object' && !window.bin.closed && window.bin.close ) {
		window.bin.close();
	}

	if( typeof window.settings == 'object' && !window.settings.closed && window.settings.close ) {
		window.settings.close();
	}

	// toggle image, this will also change the attribute on the server
	if( Math.random ) {
		window_url = theUrl + '&img=' + (1+Math.random()); // set 'img' to a random number
	}
	else {
		window_url = theUrl + '&img=1';
	}

	// change image
	theImg.src = window_url;
	
	// done
	setClickConsumed();
}

var binListName = htmlconstant('_REMEMBERRECORD');
var binImgFolder = '';
var binNumBinLists = 1;
function binRender(table, id, state, listName, imgFolder, numBinLists)
{
	if( listName ) {
		// store to global
		binListName = htmlconstant('_REMEMBERRECORDINLIST').split('$1');
		binListName = binListName[0] + "&quot;" + listName + "&quot;" + binListName[1];
		binImgFolder = imgFolder;
		binNumBinLists = numBinLists;
	}

	document.write
	(
			'<img name="bin_' + table + '_' + id + '"'
		+		' onclick="binToggle(this, \'bin_toggle.php?table=' + table + '&id=' + id + '\');"'
		+		' src="' + binImgFolder + '/bin' + (state?'1':'0') + '.gif"'
		+		' width="15" height="13" border="0"'
		+		' style="cursor:pointer;"'
		+		' alt="[J]" title="' + binListName + '" />'
	);
					
	/*if( binNumBinLists > 1 ) -- 17.03.2013: always show "..." as it is also used for adding all records */
	{
		document.write(	'<a href="bin.php?table=' + table + '&id=' + id + '" target="bin" onclick="return popup(this,350,200);">'
					+		'<img src="' + binImgFolder + '/binoptions.gif" width="10" height="13" border="0" alt="[...]" title="' + htmlconstant('_REMEMBERRECORDIN') + '" />'
					+	'</a>' );
	}
}



/******************************************************************************
Section Handling
******************************************************************************/

function sect(theAnchor, currSectNum, numSect)
{
	// document already loaded?
	if( !documentLoaded ) {
		return false; // document not yet loaded, no standard processing
	}

	// check for DHTML
	var ob;
	if( !(ob=findDhtmlObj('sect' + currSectNum))
	 || !ob.style
	 || (ob.style.display != 'block' && ob.style.display != 'none') ) {
		if( document.forms[0] && document.forms[0].section ) {
			document.forms[0].section.value = currSectNum;
			document.forms[0].submit();
			return false; // cannot use DHTML, but we've submitted the formular
		}
		return true; // cannot use DHTML, use standard processing
	}
	
	// hide all other
	for( var s = 0; s < numSect; s++ ) {
		if( s != currSectNum ) {
			var closeSectObj = findDhtmlObj('sect' + s);
			if( closeSectObj ) {
				closeSectObj.style.display = 'none';
			}
			
			closeSectObj = findDhtmlObj('sectmm' + s);
			if( closeSectObj ) {
				closeSectObj.className = 'mmn';
			}
		}
	}
	
	// show current 
	ob.style.display = 'block';
	
	ob = findDhtmlObj('sectmm' + currSectNum);
	if( ob ) {
		ob.className = 'mms';
	}
	
	// blur anchor
	if( theAnchor.blur ) {
		theAnchor.blur();
	}

	// store current in form
	if( document.forms[0] && document.forms[0].section ) {
		document.forms[0].section.value = currSectNum;
	}

	if( popfitted ) {
		popfit();
	}

	return false; // can use DHTML, no standard processing
}

function sectd(id, display /*0=off, 1=on, 2=toggle*/)
{
	// document already loaded?
	if( !documentLoaded ) {
		return false;
	}

	// change section
	var ob = $('#'+id);
	if( display==2 ) {
		ob.toggle();
	}
	else if( display==1 ) {
		ob.show();
	}
	else {
		ob.hide();
	}

	if( popfitted ) { popfit(); }
	
	return false;
}

/* SERER-SPECIFIC ! To be resolved !*/
/* Leitung ausblenden, wenn Benutzergruppe HH oder HA oder Fernunterricht enthaelt */
jQuery(document).ready(function() {
	jQuery(".e_cll").each(function(){
		if(jQuery(this).text().match(/Benutzergruppe:/)){ 
			if(jQuery(this).next().text().match(/HH /) || jQuery(this).next().text().match(/HA /)  || jQuery(this).next().text().match(/Fernunterricht /)) {
				jQuery("input[name=f_leitung_name]").parent().hide();
				jQuery("input[name=f_leitung_name]").parent().prev().hide();
			}
		} 
	});
});

/* Ticketing-System 
jQuery(document).ready(function() {
	if(typeof jQuery(".mms[href$=tickets]") != "undefined") {
		if(jQuery("input[name=f_date_created]")) {
			meingang = jQuery("input[name=f_date_created]").val();
			mvon_name = jQuery("input[name=f_von_name]").val();
			mantwortemail = jQuery("input[name=f_antwortan_email]").val();
			mbetreff = jQuery("input[name=f_betreff]").val();
			mnachricht = "Am "+meingang.replace(/,/, ' um')+" schrieb "+mvon_name+":%0A>%0A>"+jQuery("textarea[name=f_nachricht_txt]").text().replace(/\n/g, '%0A>');
			jQuery(".mms[href$=tickets]").closest("body").find("input[name=f_betreff]").after('<input style="border-color: lightblue;" type="button" value="Antworten &rarr;" onclick="window.location.href = \'mailto:\'+mantwortemail+\'?subject=\'+mbetreff+\'&body=\'+mnachricht;">');
		}
	}
}); */

/* - Bei Benutzung von Klammern auf Anf.Striche hinweisen */
/* - Uneindeutige Felder bei NICHT-Suche markieren! */
$(document).ready(function(){
	
if($(".msgt").text().match(/Unbekannte Funktion/i) || $(".msgt").text().match(/Keine Ihren Suchkriterien/i)) {
	$(".ui-autocomplete-input").each(function() { 
		if($(this).val().match(/\(/) && !$(this).val().match(/"/) && !$(this).val().match(/'/)) {
			alert('Hinweis:\n\nWert evtl. mit Anf'+ue+'hrungszeichen versehen (Grund: Klammern haben eine Sonderfunktion):\n"'+$(this).val()+'"');
		}
		else if($(this).val().match(/ /) && !$(this).val().match(/"/) && !$(this).val().match(/'/)) {
			alert('Hinweis:\n\nWert evtl. mit Anf'+ue+'hrungszeichen versehen (Grund: Leerzeichen):\n"'+$(this).val()+'"');
		}
	});
}

if($("form[name=dbsearch]") && ($("#fheader .mml .mms").text() == "Angebote" || $("#fheader .mml .mms").text() == "Anbieter")) {
	
	var uneindeutig = ["anbieter.verweis", "anbieter.stichwort", "anbieter.stichwort.stichwort", "anbieter.stichwort.zusatzinfo", "anbieter.stichwort.verweis", "anbieter.stichwort.verweis2", "anbieter.stichwort.eigenschaften", "anbieter.stichwort.thema", "anbieter.stichwort.glossar", "anbieter.stichwort.scopenote", "anbieter.stichwort.algorithmus", "anbieter.stichwort.notizen", "anbieter.stichwort.notizen_fix", "anbieter.thema", "anbieter.thema.thema", "anbieter.thema.kuerzel", "anbieter.thema.glossar", "anbieter.thema.scopenote", "anbieter.thema.algorithmus", "anbieter.thema.notizen", "anbieter.thema.notizen_fix", "thema", "thema.thema", "thema.kuerzel", "thema.glossar", "thema.glossar.status", "thema.glossar.freigeschaltet", "thema.glossar.begriff", "thema.glossar.erklaerung", "thema.glossar.wikipedia", "thema.glossar.notizen", "thema.glossar.notizen_fix", "thema.scopenote", "thema.algorithmus", "thema.notizen", "thema.notizen_fix", "stichwort", "stichwort.stichwort",  "stichwort.zusatzinfo", "stichwort.verweis", "stichwort.verweis2", "stichwort.eigenschaften", "stichwort.thema", "stichwort.thema.thema",  "stichwort.thema.kuerzel", "stichwort.thema.glossar", "stichwort.thema.scopenote",  "stichwort.thema.algorithmus", "stichwort.thema.notizen", "stichwort.thema.notizen_fix", "stichwort.glossar",  "stichwort.glossar.status", "stichwort.glossar.freigeschaltet",  "stichwort.glossar.begriff", "stichwort.glossar.erklaerung",  "stichwort.glossar.wikipedia", "stichwort.glossar.notizen", "stichwort.glossar.notizen_fix", "stichwort.scopenote", "stichwort.algorithmus", "stichwort.notizen", "stichwort.notizen_fix", "durchfuehrung", "durchfuehrung.nr",  "durchfuehrung.bgnummer", "durchfuehrung.budnummer", "durchfuehrung.wisydnr",  "durchfuehrung.fudnr", "durchfuehrung.foerderdnr", "durchfuehrung.azwvdnr",  "durchfuehrung.stunden", "durchfuehrung.teilnehmer", "durchfuehrung.preis",  "durchfuehrung.preishinweise", "durchfuehrung.sonderpreis",  "durchfuehrung.sonderpreistage", "durchfuehrung.beginn", "durchfuehrung.ende", "durchfuehrung.zeitvon", "durchfuehrung.zeitbis", "durchfuehrung.kurstage", 
	"durchfuehrung.tagescode", "durchfuehrung.dauer", "durchfuehrung.beginnoptionen", 
	"durchfuehrung.strasse", "durchfuehrung.plz", "durchfuehrung.ort",  "durchfuehrung.stadtteil", "durchfuehrung.land", "durchfuehrung.rollstuhlgerecht",  "durchfuehrung.bemerkungen", "rights"
	];

	$("form[name=dbsearch] select.acselect").each(function() {
		
			$(this).on('change', function() {
				
				var nr = $(this).attr('name').replace(/f/, '');
				var s_feld = this.value; 
				var s_op_select = $("form[name=dbsearch] select[name=o"+nr+"]");
				var s_op = $("form[name=dbsearch] select[name=o"+nr+"] option:selected");
	
				if(s_op.text() == "<>" && uneindeutig.includes(s_feld)) {
					$("form[name=dbsearch] select.acselect").css("color", "black");
					$(this).css("color", "darkred");
					s_op_select.css("color", "darkred");
						
					alert('Achtung: Das Feld "'+s_feld+'" f'+ue+'hrt zusammen mit der NICHT-Suche (< >) i.d.R. zu falschen Ergebnissen!\n\nSolches ist immer dann der Fall, wenn einem Kurs mehrere der gesuchten Werte werden k'+oe+'nnen - wie etwa SW oder DF-Parameter.');
					
				} else {
				 $("form[name=dbsearch] select.acselect").css("color", "black");
				 $("form[name=dbsearch] select[name^=o]").css("color", "black");
				}
			});
	});

	$("form[name=dbsearch] select[name^=o]").each(function() {
		$(this).on('change', function() {
		 var nr = $(this).attr('name').replace(/o/, '');
		var s_op = this.value; 
		var s_feld_select = $("form[name=dbsearch] select[name=f"+nr+"]");
		var s_feld = $("form[name=dbsearch] select[name=f"+nr+"] option:selected");
	 
	 if(s_op == "ne" && uneindeutig.includes(s_feld.val())) {
				 $("form[name=dbsearch] select.acselect").css("color", "black");
				 $(this).css("color", "darkred");
				 s_feld_select.css("color", "darkred");
					 
				 alert('Achtung: Das Feld "'+s_feld.val()+'" f'+ue+'hrt zusammen mit der NICHT-Suche (< >) i.d.R. zu falschen Ergebnissen!\n\nSolches ist immer dann der Fall, wenn einem Kurs mehrere der gesuchten Werte werden k'+oe+'nnen - wie etwa SW oder DF-Parameter.');
				 
			 } else {
				$("form[name=dbsearch] select.acselect").css("color", "black");
				$("form[name=dbsearch] select[name^=o]").css("color", "black");
			 }
		 });
 	});
	
	$("form[name=dbsearch] select").each(function() {
	  $(this).on('change', function() {
		var s_feld = $("form[name=dbsearch] select option:selected");
	    
	    if( s_feld.data('msg') != "" )
			  alert( unescape( s_feld.data('msg') ).replace(/\\n/g, "\r\n") );
	 });
	});
	
}

// make archived entries css-designable
$('td:contains("Archiv")').each(function(){
  var col = $(this).prevAll().length;
  var headerObj = $(this).parents('table').find('th').eq(col);
  if( headerObj.text() == "Status")
    $(this).parent().attr("class", "archiv");
});

$('td:contains("Gesperrt")').each(function(){
  var col = $(this).prevAll().length;
  var headerObj = $(this).parents('table').find('th').eq(col);
  if( headerObj.text() == "Status")
    $(this).parent().attr("class", "gesperrt");
});

$("input[name^='f_durchfuehrung_beginn'],input[name^='f_durchfuehrung_ende']").change(function(){
 /* $(".dauer_fix_label").parent().find("input.e_bitfield_item").prop( "checked", false ); */
 $(".dauer_fix_label").css("color", "darkgreen");
 if(!$(".dauer_fix_label:visible").length)
 $(".e_object .e_clr .e_defhide_more:last-child").trigger("click");
});

// Delete Portal Caches module link
if( jQuery("form[name=edit]").find("input[name=table]").val() == "portale") {
 var portal_id = jQuery("form[name=edit]").find("input[name=id]").val();
 if(portal_id > 0)
  jQuery("#fheader table.sm td.sml a:last-child").before('<a href="module.php?module=plugin_portale_0_cacheloeschen&id='+portal_id+'" target="plugin_cache_portale_0" onclick="return popup(this,750,550);"> &nbsp;Cache l&ouml;schen&nbsp; </a></td>');
}

// Show statistics plugin link
if( jQuery("form[name=edit]").find("input[name=table]").val() == "portale") {
  var portal_id = jQuery("form[name=edit]").find("input[name=id]").val();
   if(portal_id > 0)
     jQuery("#fheader table.sm td.sml a:last-child").before('<a href="module.php?module=plugin_portale_1_statistiken&id='+portal_id+'&what=Durchfuehrungen&strokeColor=tomato" target="plugin_cache_portale_0" onclick="return popup(this,750,550);"> &nbsp;Statistiken&nbsp; </a></td>');
}

/* ********************************************************** */
/* Dont allow user or user_grp to be empty when saving a view */
jQuery("input[name=submit_ok]").click(function(){ 
 return check_grp_user();
});

jQuery("input[name=submit_apply]").click(function(){ 
 return check_grp_user();
});


function check_grp_user() {
	
  /* exceptions */
 if( jQuery("form input[type=hidden][name=table][value=feedback]").length )
  return true;
	
 if( jQuery("span[data-table=user_grp]").length && jQuery("span[data-table=user_grp]").text() == "" ) {
  alert("Es wurde keine Benutzergruppe vergeben.\n\nBitte vor dem Speichern definieren!")
  return false;
 } else if( jQuery("span[data-table=user]").length && jQuery("span[data-table=user]").text() == "" ) {
  alert("Es wurde kein Eigent"+ue+"mer dieses Angebots angegeben.\n\nBitte vor dem Speichern definieren!")
  return false;
 } else {
  return true;
 }
 
}
/* ********************************************************** */

});

var ae = unescape("%E4");
var ue = unescape("%FC");
var oe = unescape("%F6");
var ss = unescape("%DF");

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
    // Normal link click: click marked as "consumed"
    $('table.tb > tbody > tr a').click(function() {
        setClickConsumed();
    });

    // Clicking a row: Only go to details view, if no button and no link is clicked
    $('table.tb > tbody > tr').click(function(e) {
	
		// if target is action-button: don't go to detail view (= go to target of button action)
		if ($(e.target).closest('button, .btn-diff, .btn-arrow-left, .btn-arrow-right, .btn-arrow-all-left, .btn-arrow-all-right, .btn-toggle-duplikat').length > 0) {
			return;
		}
        // if target is link: don't go to detail view (= go to target/href of link)
        if ($(e.target).closest('a').length > 0) {
            return;
        }
        var jqObj = $(this).find('a.clicktr');
        if (!isClickConsumed() && jqObj.length == 1) {
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
		if( theAnchor.target != "" )
			eval(theAnchor.target + "=w;");
		else
			window.myPopup = w;
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
		alert( 'Das zu dieser Attributauswahl geh'+oe+'rige Fenster ist bereits geschlossen.');
	    alert( 'Opener' + window.opener + ', geschlossen: ' + window.opener.closed + ', Auswahl: ' + window.opener.rcv_id_selection );
		alert( 'W ist ' + (typeof w) + ', myPopup: ' + (typeof window.myPopup ) );
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
     jQuery("#fheader table.sm td.sml a:last-child").before('<a href="module.php?module=plugin_portale_1_statistiken&id='+portal_id+'&what=Durchfuehrungen&strokeColor=tomato" target="plugin_cache_portale_0" onclick="return popup(this,100%,860);"> &nbsp;Statistiken&nbsp; </a></td>');
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

/* HTML preview: */
/* if beschreibung contains HTML tags => display button to create overlay to parse that HTML in the textarea field */
function removeOverlay() {
    jQuery("#overlay").remove();
}

function displayOverlay() {

    if( jQuery("#overlay").length ) {
		removeOverlay();
		return true;
	}
	
	var overlay = jQuery('textarea[name=f_beschreibung]').val();
    overlay = '<div id="overlay" onclick="removeOverlay();" tile="Klicken zum schliessen." >' + overlay + '</div>';
	jQuery('#fheader').append(overlay);
}

function containsHTMLTags(text) {
    const regex = /<[^>]*>/g;
    return regex.test(text);
}

function downloadCSV(csv, filename) {
  var csvFile;
  var downloadLink;

  // CSV file
  csvFile = new Blob([csv], {type: "text/csv"});

  // Download link
  downloadLink = document.createElement("a");

  // File name
  downloadLink.download = filename;

  // Create a link to the file
  downloadLink.href = window.URL.createObjectURL(csvFile);

  // Hide download link
  downloadLink.style.display = "none";

  // Add the link to DOM
  document.body.appendChild(downloadLink);

  // Click download link
  downloadLink.click();
}

function buildCSVrow(csv, rows, skipRowsEnd, textElementSelector) {
	
  // don't use last row (protocol etc.)
  for (var i = 0; i < rows.length-skipRowsEnd; i++) {
	
    var row = [];
	var cols = rows[i].querySelectorAll( textElementSelector );

    for (var j = 0; j < cols.length; j++) {
	  
      var cellData = cols[j].innerText;

	  // If the cell data is not a number, encapsulate it with quotes and remove any new line characters
      if (isNaN(cellData)) {
        cellData = '"' + cellData.replace(/"/g, '""').replace(/[\n]+/g, '') + '"';
      } else {
        cellData = cellData.replace(/[\n]+/g, '');
      }

      row.push(cellData);
    }

    csv.push(row.join(";"));
  }

  return csv;
}

function removeThisNestedElement( elementParent, selector) {
	
	// Iterate through the NodeList
  	elementParent.forEach(element => {
  		// Find nested tables within the current element
  		const nestedTables = element.querySelectorAll( selector );

  		// Remove each nested table = always in last column in CMS
  		nestedTables.forEach( selectedElement => {
    		selectedElement.parentNode.removeChild( selectedElement );
  		});
  	});

	return elementParent;
}

function exportTableToCSV( rootElement, headSelector, bodySelector, url = "" ) {

  if( url != "" ) {
	
	$.get(url, (data) => {
      var parser = new DOMParser();
      outputTableToCSV( parser.parseFromString(data, 'text/html'), headSelector, bodySelector );
    }).fail(function() {
		console.log('An error occurred while fetching the search table data via ajax call!');
    });

  } else {
	// console.log( "Verwende Tabelle der aktuellen Seite." );
	outputTableToCSV( rootElement, headSelector, bodySelector );
  }

}

function outputTableToCSV( rootElement, headSelector, bodySelector ) {
	
  var csv = [];

  // select only direct children b/c some cells have child table
  var rows_head = rootElement.querySelectorAll( headSelector );
  var rows_body = rootElement.querySelectorAll( bodySelector );
	
  console.log(bodySelector);
  console.log( rows_body );
	
  if( headSelector )
  	csv = buildCSVrow( csv, rows_head, 0, 'th' );

  csv = buildCSVrow( csv, rows_body, 0, 'td' );

  $.ajax({
	  url: '/admin/lib/exp/table_to_csv.php',
	  method: 'POST',
	  data: {
	    csv_data: csv.join("\n")
	  },
	  success: function(response) {
		
	    // Create a Blob with the response data
	    var blob = new Blob([response], {type: 'text/csv'});
	
	    // Create a download link and set the Blob URL as its href
	    var downloadLink = document.createElement('a');
	    downloadLink.href = window.URL.createObjectURL(blob);

 
		Number.prototype.pad = function(n) {
			if( this < 10 )
		    	return new Array(n).join('0').slice((n || 2) * -1) + this;
			else
				return this;
		}
		const date	= new Date();
		let year	= date.getFullYear(); 
		let month	= date.getMonth()+1; 
		let day		= date.getDate();
		let hour	= date.getHours();
		let minute	= date.getMinutes(); 
 
	    downloadLink.download = 'Export_' + year + '-' + month.pad(2) + '-' + day.pad(2) + '_' + hour.pad(2) + '-'+minute.pad(2) + '.csv';
	
	    // Add the download link to the DOM and trigger the click event to start the download
	    document.body.appendChild(downloadLink);
	    downloadLink.click();
	
	    // Remove the download link from the DOM
	    document.body.removeChild(downloadLink);

	  },
	  error: function(xhr, status, error) {
	    alert( "Fehler bei der Generierung der CSV-Datei:\n" + error );
	  }
	});
	
} // end: outputTableToCSV



jQuery(document).ready(function() {

	var selectTag = 'textarea[name=f_beschreibung]';
	var beschreibung = jQuery(selectTag);

	if( beschreibung.length ) {
	
		var checkHTML = document.querySelector(selectTag);
		checkHTMLContent = checkHTML.value;

		if (containsHTMLTags(checkHTMLContent)) {
			beschreibung.next().after('<br><button type="button" onclick="displayOverlay()" id="overlayButton">Portal-Ansicht</button>');
		} else {
    		/* console.log('Textarea does not contain HTML tags.'); */
		}

	}
	
	$('.swbaum.tb tbody tr[data-level="1"] .collapse-toggle').click(function (e) {
      e.stopPropagation(); // Prevent the click event from bubbling to the row
      var row = $(this).closest('tr');
      row.toggleClass('collapsed');
      $(this).toggleClass('collapsed');
      
      let level = parseInt(row.data('level'));
      let next = row.next();

      while (next.length && parseInt(next.data('level')) > level) {
        if (row.hasClass('collapsed')) {
          if (parseInt(next.data('level')) === level + 1) {
            next.show();
          }
        } else {
          next.hide();
        }
        next = next.next();
      }
    });
    
});

/* End: HTML preview */
/*********************/

var ae = unescape("%E4");
var ue = unescape("%FC");
var oe = unescape("%F6");
var ss = unescape("%DF");

var Ae = unescape("%C4");
var Ue = unescape("%DC");
var Oe = unescape("%D6");


// -------------------------
// Kurs-Duplikate-Ansichten:
// -------------------------

// verwendet: JSDIFF
// https://github.com/kpdecker/jsdiff - BSD 3 - Lizenz:
// https://github.com/kpdecker/jsdiff/blob/master/LICENSE
// Der Lizenztext selbst muss als Datei LICENSE im jsdiff-Repository enthalten sein. Gemäß BSD-3 reicht es aus, 
// irgendwo (z.B. im Impressum, in einem Lizenzverzeichnis oder via Kommentar im Header) darauf hinzuweisen, dass jsdiff unter der BSD-3 License verwendet wird.
// Es sind keine Pflicht-Lizenzen oder Modalitäten wie GPL erforderlich - BSD-3 erlaubt die Integration in's CMS inklusive Modifikation und kommerzieller Verteilung.

// globaler Window-Scope zum später fokussieren, wenn bereits offen und minimiert:
var diffWindow = null;

$(function() {

	// =====================
	    // Tabellen-Ansicht-Code
	    // =====================
	    var params = new URLSearchParams(window.location.search);
	    if (params.get('table') === 'kurse_duplikate' && $('.tb').length) {

	        var $table = $('.tb');

	        function getIndices() {
	            var ths = $table.find('thead tr th');
	            return {
	                kursId1: ths.filter(':contains("Kurs-ID 1")').index(),
	                kursId2: ths.filter(':contains("Kurs-ID 2")').index(),
	                titel1: ths.filter(':contains("Titel 1")').index(),
	                titel2: ths.filter(':contains("Titel 2")').index(),
	                anbieter1: ths.filter(':contains("Anbieter 1")').index(),
	                anbieter2: ths.filter(':contains("Anbieter 2")').index(),
	                beschreibung1: ths.filter(':contains("Beschreibung 1")').index(),
	                beschreibung2: ths.filter(':contains("Beschreibung 2")').index(),
	                duplikat: ths.filter(':contains("Ist Duplikat")').index(),
	                selberAnbieter: ths.filter(':contains("Selber Anbieter")').index(),
	                erschliessungIdentisch: ths.filter(':contains("ung identisch")').index()
	            };
	        }

	        function toGermanEntities(str) {
	            return str
	                .replace(/ä/g, '&auml;')
	                .replace(/ö/g, '&ouml;')
	                .replace(/ü/g, '&uuml;')
	                .replace(/Ä/g, '&Auml;')
	                .replace(/Ö/g, '&Ouml;')
	               .replace(/Ü/g, '&Uuml;')
	                .replace(/ß/g, '&szlig;');
	        }

	        function extractCourseIdsFromRow($row, idx) {
	            var $tds = $row.find('td');
	            var id1 = '';
	            var id2 = '';

	            // 1) Primär: beide Kurs-IDs aus den Kurs-Links in der Zeile lesen
	            var idsFromLinks = [];
	            $row.find('a[href*="table=kurse&id="]').each(function() {
	                var href = $(this).attr('href') || '';
	                var m = href.match(/table=kurse&id=(\d+)/);
	                if (m && m[1]) {
	                    idsFromLinks.push(m[1]);
	                }
	            });
	            if (idsFromLinks.length >= 2) {
	                id1 = idsFromLinks[0];
	                id2 = idsFromLinks[1];
	            }

	            // 2) Fallback: über Spaltenindizes (falls vorhanden)
	            if (!id1 && idx.kursId1 >= 0) {
	                id1 = $tds.eq(idx.kursId1).text().trim().replace(/\D/g, '');
	            }
	            if (!id2 && idx.kursId2 >= 0) {
	                id2 = $tds.eq(idx.kursId2).text().trim().replace(/\D/g, '');
	            }

	            // 3) Fallback ohne Header-Abhängigkeit:
	            //    numerische Zellen (nur Ziffern) aus der Zeile sammeln und als Kurs-IDs verwenden.
	            //    Dadurch klappt es auch, wenn der Header-Text "Kurs-ID 2" nicht exakt erkannt wird.
	            if (!id1 || !id2) {
	                var numericCellIds = [];
	                $tds.each(function() {
	                    var cellText = $(this).text().trim();
	                    if (/^\d+$/.test(cellText)) {
	                        numericCellIds.push(cellText);
	                    }
	                });

	                // Datensatz-ID (erste Spalte) ist i.d.R. kleiner/anders; Kurs-IDs stehen als erste zwei
	                // reinen ID-Zellen nach den Statusspalten. Wir nehmen daher die letzten zwei gefundenen.
	                if (numericCellIds.length >= 2) {
	                    if (!id1) {
	                        id1 = numericCellIds[numericCellIds.length - 2];
	                    }
	                    if (!id2) {
	                        id2 = numericCellIds[numericCellIds.length - 1];
	                    }
	                }
	            }

	            return { id1: id1, id2: id2 };
	        }

	        // Liefert das HTML fuer die klickbare "Ist Duplikat"-Zelle.
	        // value = 1 -> gruenes Haekchen, 0 -> rotes X. Klick toggelt per AJAX.
	        function renderDuplikatToggle(value, id1, id2) {
	            var isDup = (parseInt(value, 10) === 1);
	            var color = isDup ? '#18a058' : '#b80000';
	            var symbol = isDup ? '&#10003;' : '&#10007;';
	            var tooltip = isDup
	                ? toGermanEntities('Duplikat - Klick zum Umschalten auf "Nein"')
	                : toGermanEntities('Kein Duplikat - Klick zum Umschalten auf "Ja"');
	            return '<span class="btn-toggle-duplikat" '
	                + 'data-value="' + (isDup ? 1 : 0) + '" '
	                + 'data-id1="' + id1 + '" data-id2="' + id2 + '" '
	                + 'title="' + tooltip + '" '
	                + 'style="color:' + color + ';font-size:1.3em;vertical-align:middle;cursor:pointer;user-select:none;display:inline-block;padding:0 4px;">'
	                + symbol + '</span>';
	        }

	        // Aktions-Spalten ("Erschließung übertragen" + "Vergleich") robust nach der
	        // IMMER vorhandenen ID-Spalte einfuegen - unabhaengig davon, welche Daten-Spalten
	        // (Kurs-ID, Anbieter, ...) der Redakteur ein-/ausgeblendet hat.
	        // Die echten Kurs-IDs werden NICHT mehr aus (evtl. ausgeblendeten) Zellen gelesen,
	        // sondern serverseitig aus der kurse_duplikate-Zeilen-ID aufgeloest. Dazu traegt
	        // jede Zeile data-dupe-rowid; die Pfeile tragen nur Richtung (data-dir/data-side).

	        // ID-Spalte (Zelle mit dem kurse_duplikate-Editlink) als Anker bestimmen.
	        var idColIdx = 0;
	        var $probeRow = $table.children('tbody').children('tr').first();
	        if ($probeRow.length) {
	            $probeRow.find('td').each(function(i) {
	                if ($(this).find('a[href*="table=kurse_duplikate"]').length) { idColIdx = i; return false; }
	            });
	        }

	        // Kopfzellen: Reihenfolge ID | Erschließung übertragen | Vergleich
	        var $idTH = $table.find('thead tr th').eq(idColIdx);
	        $('<th>Vergleich</th>').insertAfter($idTH);
	        $('<th>' + toGermanEntities('Erschließung übertragen') + '</th>').insertAfter($idTH);

	        var diffBtnHtml = '<button type="button" class="btn-diff" title="' + toGermanEntities('Beschreibungen vergleichen') + '" style="background:transparent;border:none;cursor:pointer;padding:4px;">' +
	            '<svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle"><circle cx="9" cy="9" r="7" stroke="#18a058" stroke-width="2" fill="none"/><line x1="14" y1="14" x2="19" y2="19" stroke="#18a058" stroke-width="2" stroke-linecap="round"/></svg>' +
	            '</button>';

	        // Pfeile - Quelle/Ziel loest der Server aus row + dir/side auf (fuer alle Zeilen gleich).
	        var arrowAllLeft = '<button class="btn-arrow-all-left" data-side="2" title="' + toGermanEntities('Erschließung des rechten Kurses auf ALLE seine Duplikate übertragen') + '" style="background:transparent;border:none;cursor:pointer;">' +
	            '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M11 3L5 9L11 15" stroke="#0a58ca" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M16 3L10 9L16 15" stroke="#0a58ca" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
	            '</button>';
	        var arrowLeft = '<button class="btn-arrow-left" data-dir="r2l" title="' + toGermanEntities('Erschließung des rechten Kurses auf den linken übertragen') + '" style="background:transparent;border:none;cursor:pointer;">' +
	            '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M12 3L6 9L12 15" stroke="#18a058" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
	            '</button>';
	        var arrowRight = '<button class="btn-arrow-right" data-dir="l2r" title="' + toGermanEntities('Erschließung des linken Kurses auf den rechten übertragen') + '" style="background:transparent;border:none;cursor:pointer;">' +
	            '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M6 3L12 9L6 15" stroke="#18a058" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
	            '</button>';
	        var arrowAllRight = '<button class="btn-arrow-all-right" data-side="1" title="' + toGermanEntities('Erschließung des linken Kurses auf ALLE seine Duplikate übertragen') + '" style="background:transparent;border:none;cursor:pointer;">' +
	            '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M2 3L8 9L2 15" stroke="#0a58ca" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M7 3L13 9L7 15" stroke="#0a58ca" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
	            '</button>';
	        var arrowsHtml = '<div style="display:flex;gap:4px;justify-content:center;align-items:center;">' + arrowAllLeft + arrowLeft + arrowRight + arrowAllRight + '</div>';

	        $table.children('tbody').children('tr').each(function() {
	            var $tr = $(this);
	            var $tds = $tr.find('td');

	            // kurse_duplikate-Zeilen-ID aus dem ID-Link lesen (ID-Spalte ist immer sichtbar)
	            var rowId = '';
	            var href = ($tds.eq(idColIdx).find('a[href*="table=kurse_duplikate"]').first().attr('href')) || '';
	            var m = href.match(/[?&]id=(\d+)/);
	            if (m) {
	                rowId = m[1];
	            } else {
	                var t = $tds.eq(idColIdx).find('a').first().text().replace(/\D/g, '');
	                if (t) { rowId = t; }
	            }
	            $tr.attr('data-dupe-rowid', rowId);

	            // Reihenfolge nach ID: erst "Erschließung übertragen" (Pfeile), dann "Vergleich" (Lupe)
	            $tds.eq(idColIdx).after('<td class="difftd" style="text-align:center;">' + diffBtnHtml + '</td>');
	            $tds.eq(idColIdx).after('<td>' + arrowsHtml + '</td>');
	        });

	        // Spalten-Inhalte formatieren...
	        $table.children('tbody').children('tr').each(function() {
	            var idx = getIndices();
	            var $tds = $(this).find('td');

	            var kursId1 = $tds.eq(idx.kursId1).text().trim().replace(/\D/g, '');
	            $tds.eq(idx.kursId1).html(
	                $('<a>', {
	                    href: '/admin/edit.php?table=kurse&id=' + kursId1,
	                    text: kursId1,
	                    target: '_blank',
	                    title: toGermanEntities('In Editor oeffnen...')
	                })
	            );
	            var kursId2 = $tds.eq(idx.kursId2).text().trim().replace(/\D/g, '');
	            $tds.eq(idx.kursId2).html(
	                $('<a>', {
	                    href: '/admin/edit.php?table=kurse&id=' + kursId2,
	                    text: kursId2,
	                    target: '_blank',
	                    title: toGermanEntities('In Editor oeffnen...')
	                })
	            );
	            var titel1 = $tds.eq(idx.titel1).text().trim().replace(/^"+|"+$/g, '');
	            $tds.eq(idx.titel1).html('<em>&quot;' + $('<div>').text(titel1).html() + '&quot;</em>');
	            var titel2 = $tds.eq(idx.titel2).text().trim().replace(/^"+|"+$/g, '');
	            $tds.eq(idx.titel2).html('<em>&quot;' + $('<div>').text(titel2).html() + '&quot;</em>');
	            var anbieter1 = $tds.eq(idx.anbieter1).text().trim();
	            $tds.eq(idx.anbieter1).html('<b>' + $('<div>').text(anbieter1).html() + '</b>');
	            var anbieter2 = $tds.eq(idx.anbieter2).text().trim();
	            $tds.eq(idx.anbieter2).html('<b>' + $('<div>').text(anbieter2).html() + '</b>');
	            var $duplikatTD = $tds.eq(idx.duplikat);
	            var origVal = $duplikatTD.text().trim();
	            if (origVal === "Ja" || origVal === "\u2713") { // \u2713 = grünes ok Häkchen
	                $duplikatTD.html(renderDuplikatToggle(1, kursId1, kursId2));
	            } else if (origVal === "Nein" || origVal === "\u2717") {
	                $duplikatTD.html(renderDuplikatToggle(0, kursId1, kursId2));
	            }
	            var $selberAnbieterTD = $tds.eq(idx.selberAnbieter);
	            var origVal2 = $selberAnbieterTD.text().trim();
	            if (origVal2 === "Ja" || origVal2 === "\u2713") { // \u2713 = rotes x
	                $selberAnbieterTD.html('<span title="Ja" style="color:#18a058;font-size:1.3em;vertical-align:middle;">&#10003;</span>');
	            } else if (origVal2 === "Nein" || origVal2 === "\u2717") {
	                $selberAnbieterTD.html('<span title="Nein" style="color:#b80000;font-size:1.3em;vertical-align:middle;">&#10007;</span>');
	            }
	            var $erschliessungIdentischTD = $tds.eq(idx.erschliessungIdentisch);
	            var origVal3 = $erschliessungIdentischTD.text().trim();
	            if (origVal3 === "Ja" || origVal3 === "\u2713") { // \u2713 = rotes x
	                $erschliessungIdentischTD.html('<span title="Ja" style="color:#18a058;font-size:1.3em;vertical-align:middle;">&#10003;</span>');
	            } else if (origVal3 === "Nein" || origVal3 === "\u2717") {
	                $erschliessungIdentischTD.html('<span title="Nein" style="color:#b80000;font-size:1.3em;vertical-align:middle;">&#10007;</span>');
	            }            
	            
	        });

	        // Ajax fuer Pfeile / Erschliessungsuebertragung (1:1)
	        // row = kurse_duplikate-Zeilen-ID (immer vorhanden), dir = Richtung. Der Server
	        // loest daraus Quelle/Ziel auf -> unabhaengig von eingeblendeten Spalten.
	       $table.on('click', '.btn-arrow-left, .btn-arrow-right', function(e) {
	            e.preventDefault();
	            e.stopPropagation();
	            var $btn = $(this);
	            var $row = $btn.closest('tr');
	            var rowId = $row.attr('data-dupe-rowid');
	            var dir = $btn.data('dir'); // 'l2r' (links->rechts) oder 'r2l'

	            if (!rowId || (dir !== 'l2r' && dir !== 'r2l')) {
	                alert('Fehler beim ' + Ue + 'bertragen: Zeilen-/Richtungsangabe fehlt.');
	                $btn.attr('title', toGermanEntities("Fehler beim Übertragen"));
	                return;
	            }

	            $btn.prop('disabled', true);

	            $.ajax({
	                url: '/admin/kurse_duplikate_tagtransfer.php',
	                method: 'POST',
	                dataType: 'json',
	                data: { row: rowId, dir: dir },
	                success: function(response) {
	                    $btn.prop('disabled', false);
	                    var ok = response && response.success;
	                    if (!ok) {
	                        var backendMessage = (response && response.message) ? response.message : 'Unbekannter Fehler.';
	                        alert('Fehler beim Übertragen: ' + backendMessage);
	                        $btn.attr('title', toGermanEntities("Fehler beim Übertragen"));
	                        $btn.closest('td').find('span[title="Fehler!"]').remove();
	                        $btn.closest('td').append('<span style="color:#b80000;margin-left:6px;" title="Fehler!">&#9888;</span>');
	                        setTimeout(function() {
	                            $btn.closest('td').find('span[title="Fehler!"]').fadeOut(1500, function() { $(this).remove(); });
	                        }, 2000);
	                        return;
	                    }

	                    var successMsg = Ue+"bertragung erfolgreich.\n"
	                        + "Quelle: " + response.source + "\n"
	                        + "Ziel: " + response.target + "\n"
	                        + "Transferierte Themen-ID: " + response.thema_transferred + "\n"
	                        + "Erg"+ae+"nzte Stichw"+oe+"rter: " + response.keywords_added;
	                    alert(successMsg);

	                    $btn.attr('title', toGermanEntities("Übertragung erfolgreich!"));
	                    $btn.closest('td').find('span[title="Erfolgreich!"]').remove();
	                    $btn.closest('td').append('<span style="color:#18a058;margin-left:6px;" title="Erfolgreich!">&#10003;</span>');
	                    setTimeout(function() {
	                        $btn.closest('td').find('span[title="Erfolgreich!"]').fadeOut(700, function() { $(this).remove(); });
	                    }, 1500);
	                },
	                error: function(xhr) {
	                    $btn.prop('disabled', false);
	                    var errMsg = 'Unbekannter Fehler.';
	                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
	                        errMsg = xhr.responseJSON.message;
	                    } else if (xhr && xhr.responseText) {
	                        try {
	                            var parsed = JSON.parse(xhr.responseText);
	                            if (parsed && parsed.message) {
	                                errMsg = parsed.message;
	                            }
	                        } catch (ignore) {}
	                    }
	                    alert('Fehler beim Übertragen: ' + errMsg);
	                    $btn.attr('title', toGermanEntities("Fehler beim Übertragen"));
	                    $btn.closest('td').find('span[title="Fehler!"]').remove();
	                    $btn.closest('td').append('<span style="color:#b80000;margin-left:6px;" title="Fehler!">&#9888;</span>');
	                    setTimeout(function() {
	                        $btn.closest('td').find('span[title="Fehler!"]').fadeOut(1500, function() { $(this).remove(); });
	                    }, 2000);
	                }
	            });
	        });

	        // Ajax fuer Doppelpfeile: Erschliessung von einer Quelle auf ALLE ihre Duplikate
	        $table.on('click', '.btn-arrow-all-left, .btn-arrow-all-right', function(e) {
	            e.preventDefault();
	            e.stopPropagation();
	            var $btn = $(this);
	            var $row = $btn.closest('tr');
	            var rowId = $row.attr('data-dupe-rowid');
	            var side = String($btn.data('side') || ''); // '1' = linker Kurs ist Quelle, '2' = rechter Kurs

	            if (!rowId || (side !== '1' && side !== '2')) {
	                alert('Fehler: Zeilen-/Seitenangabe fehlt.');
	                return;
	            }

	            var seite = (side === '1') ? 'linken' : 'rechten';
	            if (!confirm('Erschließung des ' + seite + ' Kurses auf ALLE seine Duplikate übertragen?\n\n'
	                + 'Bei jedem Duplikat wird das Thema gesetzt und fehlende Stichwörter werden ergänzt.\n'
	                + 'Diese Aktion betrifft mehrere Kurse. Fortfahren?')) {
	                return;
	            }

	            $btn.prop('disabled', true);
	            $.ajax({
	                url: '/admin/kurse_duplikate_tagtransfer_all.php',
	                method: 'POST',
	                dataType: 'json',
	                data: { row: rowId, side: side },
	                success: function(response) {
	                    $btn.prop('disabled', false);
	                    if (!response || !response.success) {
	                        var backendMessage = (response && response.message) ? response.message : 'Unbekannter Fehler.';
	                        alert('Fehler beim ' + Ue + 'bertragen: ' + backendMessage);
	                        return;
	                    }
	                    var msg = Ue + 'bertragung auf alle Duplikate abgeschlossen.\n'
	                        + 'Quelle: ' + response.source + '\n'
	                        + 'Duplikate gesamt: ' + response.targets_total + '\n'
	                        + 'Davon ge' + ae + 'ndert: ' + response.targets_changed + '\n'
	                        + 'Erg' + ae + 'nzte Stichw' + oe + 'rter: ' + response.keywords_added;
	                    if (response.failed) {
	                        msg += '\nFehlgeschlagen: ' + response.failed;
	                        if (response.failures && response.failures.length) {
	                            response.failures.forEach(function(f) {
	                                msg += '\n  - Kurs-ID ' + f.target + ': ' + f.message;
	                            });
	                        }
	                    }
	                    alert(msg);
	                    $btn.closest('td').find('span[title="Erfolgreich!"]').remove();
	                    $btn.closest('td').append('<span style="color:#18a058;margin-left:6px;" title="Erfolgreich!">&#10003;</span>');
	                    setTimeout(function() {
	                        $btn.closest('td').find('span[title="Erfolgreich!"]').fadeOut(700, function() { $(this).remove(); });
	                    }, 1500);
	                },
	                error: function(xhr) {
	                    $btn.prop('disabled', false);
	                    var errMsg = 'Unbekannter Fehler.';
	                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
	                        errMsg = xhr.responseJSON.message;
	                    } else if (xhr && xhr.responseText) {
	                        try {
	                            var parsed = JSON.parse(xhr.responseText);
	                            if (parsed && parsed.message) { errMsg = parsed.message; }
	                        } catch (ignore) {}
	                    }
	                    alert('Fehler beim ' + Ue + 'bertragen: ' + errMsg);
	                }
	            });
	        });

	        // Ajax fuer das Umschalten des Duplikat-Status (Klick auf das Symbol)
	        $table.on('click', '.btn-toggle-duplikat', function(e) {
	            e.preventDefault();
	            e.stopPropagation();
	            var $span = $(this);
	            var current = parseInt($span.data('value'), 10);
	            var next = (current === 1) ? 0 : 1;
	            var id1 = String($span.data('id1') || '').trim();
	            var id2 = String($span.data('id2') || '').trim();
	            var $td = $span.closest('td');

	            if (!id1 || !id2) {
	                alert('Fehler beim Umschalten: Ungültige Kurs-IDs.');
	                return;
	            }

	            // visuelles Feedback: kurz "ausgrauen" waehrend der Anfrage laeuft
	            $span.css('opacity', 0.4).css('pointer-events', 'none');

	            $.ajax({
	                url: '/admin/kurse_duplikate_setduplikat.php',
	                method: 'POST',
	                dataType: 'json',
	                data: { id1: id1, id2: id2, value: next },
	                success: function(response) {
	                    if (!response || !response.success) {
	                        var msg = (response && response.message) ? response.message : 'Unbekannter Fehler.';
	                        alert('Fehler beim Umschalten: ' + msg);
	                        $span.css('opacity', 1).css('pointer-events', 'auto');
	                        return;
	                    }
	                    $td.html(renderDuplikatToggle(next, id1, id2));
	                    // Erfolgs-Haekchen kurz neben dem Symbol einblenden
	                    var $hint = $('<span style="color:#18a058;margin-left:6px;font-size:0.9em;" title="Gespeichert">&#10003;</span>');
	                    $td.append($hint);
	                    setTimeout(function() {
	                        $hint.fadeOut(700, function() { $(this).remove(); });
	                    }, 900);
	                },
	                error: function(xhr) {
	                    var msg = 'Unbekannter Fehler.';
	                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
	                        msg = xhr.responseJSON.message;
	                    } else if (xhr && xhr.responseText) {
	                        try {
	                            var parsed = JSON.parse(xhr.responseText);
	                            if (parsed && parsed.message) { msg = parsed.message; }
	                        } catch (ignore) {}
	                    }
	                    alert('Fehler beim Umschalten: ' + msg);
	                    $span.css('opacity', 1).css('pointer-events', 'auto');
	                }
	            });
	        });

	        // Diff-Library fuer Tabellenansicht
	        var diffLoadedTable = false;
	        function loadDiffJsTable(callback) {
	            if (diffLoadedTable) { callback(); return; }
	            $.getScript('/admin/lib/diff/diff.min.js', function() {
	                diffLoadedTable = true;
	                callback();
	            }).fail(function() {
	                alert('Diff-Bibliothek konnte nicht geladen werden.');
	            });
	        }

	        // Lupe: Beschreibungen der beiden Kurse LIVE laden und vergleichen.
	        // (Robust unabhaengig davon, ob die Beschreibungs-Spalten eingeblendet sind.)
	        // Klick loest dank Klasse "btn-diff" (Whitelist in init_tb_table) + stopPropagation
	        // NICHT die Zeilen-Navigation aus.
	        $table.on('click', '.btn-diff', function(e) {
	            e.preventDefault();
	            e.stopPropagation();
	            var $btn = $(this);
	            var rowId = $btn.closest('tr').attr('data-dupe-rowid');
	            if (!rowId) {
	                alert('Vergleich nicht m' + oe + 'glich: Zeilen-ID nicht gefunden.');
	                return;
	            }
	            $btn.prop('disabled', true);

	            // row-Modus: der Server loest beide Kurse aus der Duplikat-Zeile auf und
	            // liefert die Beschreibungen direkt (unabhaengig von eingeblendeten Spalten).
	            $.ajax({
	                url: '/admin/kurse_duplikate_courseinfo.php',
	                method: 'GET',
	                dataType: 'json',
	                data: { row: rowId },
	                success: function(resp) {
	                    $btn.prop('disabled', false);
	                    if (!resp || !resp.success) {
	                        alert('Die Beschreibungen konnten nicht geladen werden.');
	                        return;
	                    }
	                    var beschreibung1 = resp.beschreibung1 || '';
	                    var beschreibung2 = resp.beschreibung2 || '';

	                    loadDiffJsTable(function() {
	                        if (typeof window.Diff === "undefined" || typeof window.Diff.diffWords !== "function") {
	                            alert("Diff-Bibliothek konnte nicht korrekt geladen werden.");
	                            return;
	                        }
	                        var diff = window.Diff.diffWords(beschreibung1, beschreibung2);
	                        var html = diff.map(function(part){
	                            var color = part.added ? '#d4ffd4' : part.removed ? '#ffe3e3' : 'transparent';
	                            var tag = part.added ? 'ins' : part.removed ? 'del' : 'span';
	                            return `<${tag} style="background:${color};">${$('<div>').text(part.value).html()}</${tag}>`;
	                        }).join('');
	                        if (!diffWindow || diffWindow.closed) {
	                            diffWindow = window.open("", "Diff", "width=900,height=700");
	                        } else {
	                            diffWindow.focus();
	                        }
	                        var head1 = $('<div>').text((resp.id1 || '') + ' ' + (resp.titel1 || '')).html();
	                        var head2 = $('<div>').text((resp.id2 || '') + ' ' + (resp.titel2 || '')).html();
	                        diffWindow.document.write(
	                            "<!DOCTYPE html><html><head><title>Textvergleich</title><meta charset='utf-8'>" +
	                            "<style>body{font-family:sans-serif;font-size:1.1em;margin:24px;} ins{background:#d4ffd4;text-decoration:none;} del{background:#ffe3e3;text-decoration:none;} pre{white-space:pre-wrap;word-break:break-word;} h3{font-size:0.9em;color:#555;font-weight:normal;}</style></head><body>" +
	                            "<h2>Vergleich Beschreibung 1 &amp; 2</h2>" +
	                            "<h3>1: " + head1 + "<br>2: " + head2 + "</h3>" +
	                            "<pre>" + html + "</pre>" +
	                            "</body></html>"
	                        );
	                        diffWindow.document.close();
	                    });
	                },
	                error: function() {
	                    $btn.prop('disabled', false);
	                    alert('Fehler beim Laden der Beschreibungen f' + ue + 'r den Vergleich.');
	                }
	            });
	        });
	    }


	    // ===========================
	    // Detailansicht (Edit-Form)
	    // ===========================    
    if (/\/admin\/edit\.php$/.test(window.location.pathname) && /table=kurse_duplikate/.test(window.location.search)) {
        var $form = $('form[name="edit"]');
        if ($form.length === 0) return;
		// if ($('#btn-beschreibungsdiff').length) return; // kann nicht vorhanden sein beim Neuladen der Seite

        // Kurs-IDs verlinken
        function linkifyKursId(inputName) {
            var $input = $form.find('input[name="'+inputName+'"]');
            if ($input.length && $input.val().match(/^\d+$/)) {
                var kursId = $input.val();
                var $link = $('<a>', {
                    href: '/admin/edit.php?table=kurse&id=' + kursId,
                    text: kursId,
                    target: '_blank',
                    title: 'In Editor oeffnen...'
                });
                // Ersetze Input durch Link (Input vorher verstecken für Formular, falls noetig)
                $input.hide().after($link);
            }
        }
        linkifyKursId('f_kurse_id1');
        linkifyKursId('f_kurse_id2');

        // Titel kursiv + Anfuehrungszeichen
        function stylizeTitel(inputName) {
            var $input = $form.find('input[name="'+inputName+'"]');
            if ($input.length) {
                var val = $input.val() || '';
                var html = '<em>&quot;' + $('<div>').text(val).html() + '&quot;</em>';
                $input.hide().after(html);
            }
        }
        stylizeTitel('f_kurse_titel1');
        stylizeTitel('f_kurse_titel2');

        // Anbieter fett
        function stylizeAnbieter(inputName) {
            var $input = $form.find('input[name="'+inputName+'"]');
            if ($input.length) {
                var val = $input.val() || '';
                var html = '<b>' + $('<div>').text(val).html() + '</b>';
                $input.hide().after(html);
            }
        }
        stylizeAnbieter('f_anbieter_name1');
        stylizeAnbieter('f_anbieter_name2');

        // Button erzeugen
        var $btn = $('<button type="button" id="btn-beschreibungsdiff" title="Beschreibung vergleichen" style="background:transparent;border:none;cursor:pointer;padding:2px 10px 2px 0;vertical-align:middle;">'
            + '<svg width="22" height="22" viewBox="0 0 22 22" style="vertical-align:middle">'
            + '<circle cx="10" cy="10" r="8" stroke="#18a058" stroke-width="2" fill="none"/>'
            + '<line x1="16" y1="16" x2="21" y2="21" stroke="#18a058" stroke-width="2" stroke-linecap="round"/></svg> '
            + 'Vergleich Beschreibung 1&nbsp;/&nbsp;2'
            + '</button>');

        // Zeile fuer Button vor der Zeile mit Beschreibung 1 einfügen
        var $beschreibung1 = $form.find('textarea[name="f_kurse_beschreibung1"]');
        if ($beschreibung1.length) {
            var $beschreibungTr = $beschreibung1.closest('tr');
            var $tr = $('<tr><td></td><td colspan="2" style="padding-top: 2em; text-align:left;padding-bottom:2px;"> </td></tr>');
            $tr.find('td:last-child').append($btn);
            $beschreibungTr.before($tr);
        }

        // Diff-Library für Detailansicht
        var diffLoadedDetail = false;
        function loadDiffJsDetail(callback) {
            if (diffLoadedDetail) { callback(); return; }
            $.getScript('/admin/lib/diff/diff.min.js', function() {
                diffLoadedDetail = true;
                callback();
            }).fail(function() {
                alert('Diff-Bibliothek konnte nicht geladen werden.');
            });
        }

        // Klick-Handler
        $btn.on('click', function(e) {
            e.preventDefault();
            var text1 = $form.find('textarea[name="f_kurse_beschreibung1"]').val() || '';
            var text2 = $form.find('textarea[name="f_kurse_beschreibung2"]').val() || '';
            loadDiffJsDetail(function() {
                if (typeof window.Diff === "undefined" || typeof window.Diff.diffWords !== "function") {
                                                console.log('4'); // <--- TEST
                    alert("Diff-Bibliothek konnte nicht korrekt geladen werden.");
                    return;
                }
                var diff = window.Diff.diffWords(text1, text2);
                var html = diff.map(function(part){
                    var color = part.added ? '#d4ffd4' : part.removed ? '#ffe3e3' : 'transparent';
                    var tag = part.added ? 'ins' : part.removed ? 'del' : 'span';
                    return `<${tag} style="background:${color};">${$('<div>').text(part.value).html()}</${tag}>`;
                }).join('');
                var win = window.open("", "Diff", "width=900,height=700");
                win.document.write(
                    "<!DOCTYPE html><html><head><title>Textvergleich Beschreibung</title><meta charset='utf-8'>" +
                    "<style>body{font-family:sans-serif;font-size:1.1em;margin:24px;} ins{background:#d4ffd4;text-decoration:none;} del{background:#ffe3e3;text-decoration:none;} pre{white-space:pre-wrap;word-break:break-word;}</style></head><body>" +
                    "<h2>Vergleich Beschreibung 1 &amp; 2</h2>" +
                    "<pre>" + html + "</pre>" +
                    "</body></html>"
                );
                win.document.close();
            });
        });
    }
});


// ============================================================
// Kurs-Edit-Maske: "Duplikate"-Sektion (zwischen mein NOW und Anmerkungen)
// ============================================================
$(function() {

    var params = new URLSearchParams(window.location.search);
    if (!/\/admin\/edit\.php$/.test(window.location.pathname)) return;
    if (params.get('table') !== 'kurse') return;

    var kursId = params.get('id');
    if (!kursId || !/^\d+$/.test(kursId)) return; // nur bei bestehendem Kurs (nicht "Neu")

    var $form = $('form[action*=edit]');
    if ($form.length === 0) return;

    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    // Umlaute werden ueber die global definierten Variablen ae/ue/oe/ss/Ae/Ue/Oe
    // zusammengesetzt (unescape("%E4") usw.). So sind wir unabhaengig von der
    // Datei-/Backend-Kodierung (Backend liefert derzeit noch ISO-8859-1).
    var TITLE_MAX = 100;                      // Titel laenger als das wird abgekuerzt
    var COLLAPSE_COOKIE = 'dupe_section_collapsed';

    function dupeSetCookie(name, val) {
        var d = new Date();
        d.setTime(d.getTime() + 365 * 24 * 60 * 60 * 1000); // ~1 Jahr -> bis zur naechsten Aenderung
        document.cookie = name + '=' + encodeURIComponent(val) + ';expires=' + d.toUTCString() + ';path=/';
    }
    function dupeGetCookie(name) {
        var m = document.cookie.match('(?:^|; )' + name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)');
        return m ? decodeURIComponent(m[1]) : null;
    }

    // Liefert das Text-Eingabefeld des Attribut-Controls (Thema/Stichwort),
    // ueber das edit.js' attr_add() neue Attribute hinzufuegt.
    function getAttrInput(fieldName) {
        var $hidden = $form.find('input[type=hidden][name="f_' + fieldName + '"]');
        if (!$hidden.length) return null;
        var $attr = $hidden.closest('.e_attr');
        if (!$attr.length) return null;
        var $input = $attr.find('input[type=text]').first();
        return $input.length ? $input : null;
    }

    // IDs der aktuell im Formular gesetzten Erschliessung lesen (zum Abgleich mit
    // den Duplikaten). Ausgewaehlte Attribute rendert das attr-Control als
    // <span class="e_attritem" data-attrid="..."> innerhalb des jeweiligen .e_attr.
    function currentThemaId() {
        var $input = getAttrInput('thema');
        if (!$input) return null;
        var $item = $input.closest('.e_attr').find('.e_attritem[data-attrid]').first();
        if (!$item.length) return null;
        var v = parseInt($item.attr('data-attrid'), 10);
        return isNaN(v) ? null : v;
    }
    function currentStichwortIds() {
        var set = {};
        var $input = getAttrInput('stichwort');
        if (!$input) return set;
        $input.closest('.e_attr').find('.e_attritem[data-attrid]').each(function() {
            var v = parseInt($(this).attr('data-attrid'), 10);
            if (!isNaN(v)) set[v] = true;
        });
        return set;
    }

    function themaLabel(thema) {
        if (!thema) return '';
        var k = thema.kuerzel ? (thema.kuerzel + ' ') : '';
        return k + (thema.name || '');
    }

    // Thema oben ersetzen (SATTR: attr_add entfernt vorher den alten Wert)
    function applyThema(partner) {
        if (!partner.thema || !partner.thema.id) {
            alert('Dieses Duplikat hat kein Thema hinterlegt.');
            return false;
        }
        if (typeof attr_add !== 'function') {
            alert('Interner Fehler: ' + Ue + 'bernahme-Funktion nicht verf' + ue + 'gbar.');
            return false;
        }
        var $input = getAttrInput('thema');
        if (!$input) {
            alert('Das Themenfeld konnte in dieser Maske nicht gefunden werden.');
            return false;
        }
        attr_add($input, partner.thema.name || ('ID ' + partner.thema.id), partner.thema.id, '');
        return true;
    }

    // Stichwoerter oben ergaenzen (MATTR: nur die noch nicht vorhandenen)
    function applyStichwoerter(partner) {
        if (!partner.stichwoerter || !partner.stichwoerter.length) {
            return { added: 0, skipped: 0, ok: true, empty: true };
        }
        if (typeof attr_add !== 'function') {
            alert('Interner Fehler: ' + Ue + 'bernahme-Funktion nicht verf' + ue + 'gbar.');
            return { added: 0, skipped: 0, ok: false };
        }
        var $input = getAttrInput('stichwort');
        if (!$input) {
            alert('Das Stichwortfeld konnte in dieser Maske nicht gefunden werden.');
            return { added: 0, skipped: 0, ok: false };
        }
        var $attr = $input.closest('.e_attr');
        var added = 0, skipped = 0;
        partner.stichwoerter.forEach(function(sw) {
            if (!sw || !sw.id) return;
            if ($attr.find('.e_attritem[data-attrid="' + sw.id + '"]').length) {
                skipped++;
                return;
            }
            attr_add($input, sw.name || ('ID ' + sw.id), sw.id, sw.actype || '');
            added++;
        });
        return { added: added, skipped: skipped, ok: true };
    }

    // kurzes visuelles Feedback neben einem Element
    function flashOk($anchor) {
        var $hint = $('<span style="color:#18a058;margin-left:6px;" title="' + Ue + 'bernommen">&#10003;</span>');
        $anchor.after($hint);
        setTimeout(function() {
            $hint.fadeOut(900, function() { $(this).remove(); });
        }, 1100);
    }

    // HTML fuer die Titelzeile eines Duplikats: vorangestellte Kurs-ID + (ggf. gekuerzter) Titel.
    function titleLineHtml(p, i, expanded) {
        var full = p.titel || '';
        var truncated = full.length > TITLE_MAX;
        var shown = (truncated && !expanded) ? full.substring(0, TITLE_MAX) : full;
        var inner = '&quot;' + esc(shown);
        if (truncated && !expanded) {
            inner += '<span class="dupe-title-more" data-idx="' + i + '" style="cursor:pointer;color:#0a58ca;" title="vollst' + ae + 'ndigen Titel anzeigen">&hellip;</span>';
        }
        inner += '&quot;';
        return '<a href="/admin/edit.php?table=kurse&id=' + encodeURIComponent(p.id) + '" target="_blank" rel="noopener" title="Kurs in neuem Tab ' + oe + 'ffnen">'
            + '<span style="color:#555;">' + esc(p.id) + '</span> '
            + '<em>' + inner + '</em></a>';
    }

    function renderSection(partners) {
        // Hervorhebung fuer abweichende Erschliessung (Thema/Stichwoerter, die der
        // aktuelle Kurs noch NICHT hat). Bewusst KEIN Link-/Button-Look: fette Schrift
        // + dezent gelbe Markierung. Die Fettung ist ein zusaetzliches, farb-
        // unabhaengiges Signal (auch bei ausgegrauten/gesperrten Zeilen erkennbar).
        if (!document.getElementById('dupe-diff-style')) {
            $('<style id="dupe-diff-style">'
                + '.dupe-diff{font-weight:bold;background:#ffe9a8;border-radius:2px;padding:0 2px;'
                + '-webkit-box-decoration-break:clone;box-decoration-break:clone;}'
                + '</style>').appendTo('head');
        }

        // "Anmerkungen"-Sektion finden, davor einfuegen (= zwischen "mein NOW" und "Anmerkungen")
        var $anmerk = $form.find('.e_section').filter(function() {
            return $(this).text().replace(/\s+/g, ' ').trim() === 'Anmerkungen';
        }).first();

        var rows = partners.map(function(p, i) {
            var anbieterLink = p.anbieter_id
                ? '<a href="/admin/edit.php?table=anbieter&id=' + encodeURIComponent(p.anbieter_id) + '" target="_blank" rel="noopener" title="Anbieter in neuem Tab ' + oe + 'ffnen"><b>' + esc(p.anbieter_name) + '</b></a>'
                : '<b>' + esc(p.anbieter_name) + '</b>';

            var themaHtml = p.thema
                ? '<span class="dupe-apply-thema" data-idx="' + i + '" data-themaid="' + esc(p.thema.id) + '" style="cursor:pointer;text-decoration:underline dotted;" title="Klicken, um dieses Thema oben zu ' + ue + 'bernehmen">' + esc(themaLabel(p.thema)) + '</span>'
                : '<span style="color:#888;">(kein Thema)</span>';

            // Stichwoerter als einzelne Spans (data-swid) rendern, damit abweichende
            // (oben fehlende) Stichwoerter gezielt hervorgehoben werden koennen.
            var swInner = (p.stichwoerter && p.stichwoerter.length)
                ? p.stichwoerter.map(function(s) {
                    return '<span class="dupe-sw" data-swid="' + esc(s.id) + '">' + esc(s.name) + '</span>';
                  }).join(', ')
                : '';
            var swHtml = swInner
                ? '<span class="dupe-apply-sw" data-idx="' + i + '" style="cursor:pointer;text-decoration:underline dotted;" title="Klicken, um diese Stichw' + oe + 'rter oben zu erg' + ae + 'nzen">' + swInner + '</span>'
                : '<span style="color:#888;">(keine Stichw' + oe + 'rter)</span>';

            // Gesperrt (2) / Abgelaufen (3) leicht ausgrauen - rein visuell,
            // Links und Erschliessungs-Funktionen bleiben voll nutzbar (opacity deaktiviert nichts).
            var st = parseInt(p.status, 10);
            var muted = (st === 2 || st === 3);
            var itemStyle = 'margin:0 0 0.9em 0;padding:0 0 0.7em 0;border-bottom:1px solid #e0e0e0;'
                + (muted ? 'opacity:0.55;' : '');

            return '<div class="dupe-edit-item" style="' + itemStyle + '">'
                + '<div><span class="dupe-titleline" data-idx="' + i + '">' + titleLineHtml(p, i, false) + '</span> &ndash; Anbieter: ' + anbieterLink + '</div>'
                + '<div style="margin-top:3px;">Status: ' + esc(p.status_name) + '</div>'
                + '<div style="margin-top:3px;">Thema: ' + themaHtml + '</div>'
                + '<div style="margin-top:3px;">Stichw' + oe + 'rter: ' + swHtml + '</div>'
                + '<div style="margin-top:5px;"><button type="button" class="dupe-apply-all" data-idx="' + i + '" style="cursor:pointer;">gesamte Erschlie' + ss + 'ung ' + ue + 'bernehmen</button></div>'
                + '</div>';
        }).join('');

        var collapsed = dupeGetCookie(COLLAPSE_COOKIE) === '1';
        var indCollapsed = '&#9656;'; // > (eingeklappt)
        var indExpanded  = '&#9662;'; // v (ausgeklappt)

        var $header = $('<div class="e_section dupe-section-header" style="cursor:pointer;user-select:none;" title="Duplikate ein-/ausklappen">'
            + '<span class="dupe-collapse-ind">' + (collapsed ? indCollapsed : indExpanded) + '</span> '
            + 'Duplikate <span style="font-weight:normal;color:#888;">(' + partners.length + ')</span>'
            + '</div>');

        var $body = $('<table class="e_tb dupe-section-body"><tr>'
            + '<td class="e_cll">Erkannte Duplikate:</td>'
            + '<td class="e_clr"><div class="dupe-edit-list">' + rows + '</div></td>'
            + '</tr></table>');

        if (collapsed) { $body.hide(); }

        if ($anmerk.length) {
            $anmerk.before($header);
            $anmerk.before($body);
        } else {
            // Fallback: ans Ende des Formularinhalts haengen
            var $target = $form.find('.e_object').first();
            $target.append($header);
            $target.append($body);
        }

        // Abweichungen hervorheben: Thema, das vom aktuell gesetzten abweicht, sowie
        // Stichwoerter des Duplikats, die der aktuelle Kurs (oben) noch nicht hat.
        // Wird nach jeder Uebernahme erneut aufgerufen, damit die Markierung aktuell bleibt.
        function refreshDiff() {
            var curThema = currentThemaId();
            var curSw = currentStichwortIds();
            $body.find('.dupe-apply-thema[data-themaid]').each(function() {
                var tid = parseInt($(this).attr('data-themaid'), 10);
                $(this).toggleClass('dupe-diff', !isNaN(tid) && tid !== curThema);
            });
            $body.find('.dupe-sw[data-swid]').each(function() {
                var sid = parseInt($(this).attr('data-swid'), 10);
                var missing = !isNaN(sid) && !curSw[sid];
                $(this).toggleClass('dupe-diff', missing);
                if (missing) { $(this).attr('title', 'Dieses Stichwort fehlt im aktuellen Kurs'); }
                else { $(this).removeAttr('title'); }
            });
        }
        refreshDiff();

        // --- Ein-/Ausklappen (Zustand im Cookie merken) ---
        $header.on('click', function() {
            var nowCollapsed = $body.is(':visible');
            if (nowCollapsed) {
                $body.slideUp(150);
                $header.find('.dupe-collapse-ind').html(indCollapsed);
                dupeSetCookie(COLLAPSE_COOKIE, '1');
            } else {
                $body.slideDown(150);
                $header.find('.dupe-collapse-ind').html(indExpanded);
                dupeSetCookie(COLLAPSE_COOKIE, '0');
            }
        });

        // --- Titel ausklappen (Klick auf "...") ---
        $body.on('click', '.dupe-title-more', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var i = $(this).data('idx');
            var p = partners[i];
            if (!p) return;
            $body.find('.dupe-titleline[data-idx="' + i + '"]').html(titleLineHtml(p, i, true));
        });

        // --- Interaktionen: Erschliessung uebernehmen ---
        $body.on('click', '.dupe-apply-thema', function() {
            var p = partners[$(this).data('idx')];
            if (!p) return;
            if (!confirm('Thema "' + themaLabel(p.thema) + '" ' + ue + 'bernehmen?\nDas aktuelle Thema oben wird ersetzt.')) return;
            if (applyThema(p)) flashOk($(this));
            refreshDiff();
        });

        $body.on('click', '.dupe-apply-sw', function() {
            var p = partners[$(this).data('idx')];
            if (!p) return;
            if (!confirm('Stichw' + oe + 'rter dieses Duplikats ' + ue + 'bernehmen?\nFehlende Stichw' + oe + 'rter werden oben erg' + ae + 'nzt (vorhandene bleiben erhalten).')) return;
            var res = applyStichwoerter(p);
            if (res.ok) {
                flashOk($(this));
                if (res.added === 0) {
                    alert('Alle Stichw' + oe + 'rter waren oben bereits vorhanden - nichts erg' + ae + 'nzt.');
                }
            }
            refreshDiff();
        });

        $body.on('click', '.dupe-apply-all', function() {
            var p = partners[$(this).data('idx')];
            if (!p) return;
            if (!confirm('Gesamte Erschlie' + ss + 'ung dieses Duplikats ' + ue + 'bernehmen?\n\n- Thema oben wird ersetzt\n- fehlende Stichw' + oe + 'rter werden oben erg' + ae + 'nzt\n\nDie ' + Ae + 'nderung wird erst mit dem Speichern des Kurses wirksam.')) return;

            var themaOk = p.thema ? applyThema(p) : true;
            var res = applyStichwoerter(p);

            var msg = Ue + 'bernahme abgeschlossen:\n';
            msg += p.thema ? ('- Thema: ' + themaLabel(p.thema) + '\n') : '- Thema: (keines vorhanden)\n';
            if (res.ok) {
                msg += '- Stichw' + oe + 'rter erg' + ae + 'nzt: ' + res.added + ' (bereits vorhanden: ' + res.skipped + ')';
            }
            msg += '\n\nBitte den Kurs noch speichern, damit die ' + Ae + 'nderung erhalten bleibt.';
            alert(msg);

            if (themaOk && res.ok) flashOk($(this));
            refreshDiff();
        });
    }

    // Duplikate laden und ggf. Sektion einfuegen
    $.ajax({
        url: '/admin/kurse_duplikate_partners.php',
        method: 'GET',
        dataType: 'json',
        data: { id: kursId },
        success: function(resp) {
            if (!resp || !resp.success || !resp.partners || !resp.partners.length) return;
            renderSection(resp.partners);
        }
        // Bei Fehler: still nichts anzeigen (Sektion ist optional/erganzend)
    });
});

// ------------------------------
// Ende: Kurs-Duplikate-Ansichten
// ------------------------------
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
        if ($(e.target).closest('button, .btn-diff, .btn-arrow-left, .btn-arrow-right').length > 0) {
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
                selberAnbieter: ths.filter(':contains("Selber Anbieter")').index()
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

        // "Erschliessung uebertragen" einfuegen
        var idx = getIndices();
        var $anbieter1TH = $table.find('thead tr th').eq(idx.anbieter1);
        $('<th>' + toGermanEntities('Erschließung übertragen') + '</th>').insertAfter($anbieter1TH);

        $table.find('tbody tr').each(function() {
            var idx = getIndices();
            var $tds = $(this).find('td');
            var kursId1 = $tds.eq(idx.kursId1).text().trim().replace(/\D/g, '');
            var kursId2 = $tds.eq(idx.kursId2).text().trim().replace(/\D/g, '');

            var arrowLeft = '<button class="btn-arrow-left" title="' + toGermanEntities('Nach links übertragen') + '" style="background:transparent;border:none;cursor:pointer;" ' +
                'data-source="'+kursId2+'" data-target="'+kursId1+'">' +
                '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M12 3L6 9L12 15" stroke="#18a058" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
                '</button>';
            var arrowRight = '<button class="btn-arrow-right" title="' + toGermanEntities('Nach rechts übertragen') + '" style="background:transparent;border:none;cursor:pointer;" ' +
                'data-source="'+kursId1+'" data-target="'+kursId2+'">' +
                '<svg width="18" height="18" viewBox="0 0 18 18"><path d="M6 3L12 9L6 15" stroke="#18a058" stroke-width="2" fill="none" stroke-linecap="round"/></svg>' +
                '</button>';
            var arrows = '<div style="display:flex;gap:4px;justify-content:center;align-items:center;">'+arrowLeft+arrowRight+'</div>';

            $tds.eq(idx.anbieter1).after('<td>'+arrows+'</td>');
        });

        // Diff-Button nach "Beschreibung 2" (mit Lupe)
        // $table.find('thead tr').each(function() {
        //    if ($(this).find('th.diffth').length === 0) {
        //       $('<th class="diffth">Vergleich</th>').insertAfter($(this).find('th').eq(getIndices().beschreibung2));
        //    }
        // });
        // $table.find('tbody tr').each(function() {
        //     var $tds = $(this).find('td');
        //     if ($(this).find('td.difftd').length === 0) {
        //         $('<td class="difftd"><button type="button" class="btn-diff" title="Text vergleichen" style="background:transparent;border:none;cursor:pointer;padding:4px;">' +
        //             '<svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle"><circle cx="9" cy="9" r="7" stroke="#18a058" stroke-width="2" fill="none"/><line x1="14" y1="14" x2="19" y2="19" stroke="#18a058" stroke-width="2" stroke-linecap="round"/></svg>' +
        //             '</button></td>').insertAfter($tds.eq(getIndices().beschreibung2));
        //     }
        // });

        // Spalten-Inhalte formatieren...
        $table.find('tbody tr').each(function() {
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
                $duplikatTD.html('<span title="Ja" style="color:#18a058;font-size:1.3em;vertical-align:middle;">&#10003;</span>');
            } else if (origVal === "Nein" || origVal === "\u2717") {
                $duplikatTD.html('<span title="Nein" style="color:#b80000;font-size:1.3em;vertical-align:middle;">&#10007;</span>');
            }
            var $selberAnbieterTD = $tds.eq(idx.selberAnbieter);
            var origVal2 = $selberAnbieterTD.text().trim();
            if (origVal2 === "Ja" || origVal2 === "\u2713") { // \u2713 = rotes x
                $selberAnbieterTD.html('<span title="Ja" style="color:#18a058;font-size:1.3em;vertical-align:middle;">&#10003;</span>');
            } else if (origVal2 === "Nein" || origVal2 === "\u2717") {
                $selberAnbieterTD.html('<span title="Nein" style="color:#b80000;font-size:1.3em;vertical-align:middle;">&#10007;</span>');
            }
        });

        // Ajax fuer Pfeile / Erschliessungsuebertragung
        $table.on('click', '.btn-arrow-left, .btn-arrow-right', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var source = $(this).data('source');
            var target = $(this).data('target');
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: '/admin/tagtransfer.php',
                method: 'GET',
                data: { source: source, target: target },
                success: function(response) {
                    $btn.prop('disabled', false);
                    $btn.attr('title', toGermanEntities("Übertragung erfolgreich!"));
                    $btn.closest('td').append('<span style="color:#18a058;margin-left:6px;" title="Erfolgreich!">&#10003;</span>');
                    setTimeout(function() {
                        $btn.siblings('span[title="Erfolgreich!"]').fadeOut(700, function() { $(this).remove(); });
                    }, 1500);
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $btn.attr('title', toGermanEntities("Fehler beim Übertragen"));
                    $btn.closest('td').append('<span style="color:#b80000;margin-left:6px;" title="Fehler!">&#9888;</span>');
                    setTimeout(function() {
                        $btn.siblings('span[title="Fehler!"]').fadeOut(1500, function() { $(this).remove(); });
                    }, 2000);
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

        $table.on('click', '.btn-diff', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $row = $(this).closest('tr');
            var idx = getIndices();
            var beschreibung1 = $row.find('td').eq(idx.beschreibung1).text().trim();
            var beschreibung2 = $row.find('td').eq(idx.beschreibung2).text().trim();

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
                diffWindow.document.write(
                    "<!DOCTYPE html><html><head><title>Textvergleich</title><meta charset='utf-8'>" +
                    "<style>body{font-family:sans-serif;font-size:1.1em;margin:24px;} ins{background:#d4ffd4;text-decoration:none;} del{background:#ffe3e3;text-decoration:none;} pre{white-space:pre-wrap;word-break:break-word;}</style></head><body>" +
                    "<h2>Vergleich Beschreibung 1 &amp; 2</h2>" +
                    "<pre>" + html + "</pre>" +
                    "</body></html>"
                );
                diffWindow.document.close();
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

// ------------------------------
// Ende: Kurs-Duplikate-Ansichten
// ------------------------------

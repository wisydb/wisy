

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
		alert('Das zu dieser Attributauswahl gehörige Fenster ist bereits geschlossen.');
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




/*=============================================================================
Edit User Access, Client JavaScript Part
===============================================================================

file:	
	deprecated_edit_access.js
	
author:	
	Bjoern Petersen

=============================================================================*/



//
// variables'n'basic functions
//
var euat	= "";			// [e]ditable [u]ser [t]able'n'fields
var euar	= "";			// [e]dit [u]ser [a]ccess t[r]anslations, this is an object after euaRender()

var euaTables = new Array();
var euaFields = new Array();

function get_transl(s)
{
	if( typeof euar[s] != 'undefined' ) {
		return euar[s];
	}
	else {
		return s;
	}
}

function isCharInStr(s, c)
{
	return s.indexOf(c)<0? 0 : 1;
}

function getReadableGrants(grants)
{
	var str = "";
	var numGrants = 0;

	if( isCharInStr(grants, 'r') ) {
		if( str ) { str = str + ", "; }
		str += htmlconstant('_READ');
		numGrants++;
	}

	if( isCharInStr(grants, 'w') ) {
		if( str ) { str = str + ", "; }
		str += htmlconstant('_EDIT');
		numGrants++;
	}

	if( isCharInStr(grants, 'n') ) {
		if( str ) { str = str + ", "; }
		str += htmlconstant('_NEW');
		numGrants++;
	}

	if( isCharInStr(grants, 'd') ) {
		if( str ) { str = str + ", "; }
		str += htmlconstant('_DELETE');
		numGrants++;
	}

	if( numGrants == 4 ) {
		str = htmlconstant('_ALLRIGHTS');
	}

	if( str == "" ) {
		str = htmlconstant('_NOACCESS');
	}

	return str;
}

function getReadablePath(path)
{
	var i, ret = "";
	
	path = path.replace(/\s/g, "");
	path = path.split(".");
	for( i = 0; i < path.length; i++ ) {
		ret += i? "." : "";
		ret += get_transl(path[i]);
	}
	
	if( ret.length > 36 ) {
		ret = ret.substring(0, 36);
	}
	
	ret += ' ';

	while( ret.length < 38 ) {
		ret += '_';
	}
	
	ret += ' ';
	
	return ret;
}

function isValueInArray(a, v)
{
	var i;
	for( i = 0; i < a.length; i++ ) {
		if( a[i] == v ) {
			return 1;
		}
	}
	return 0;
}

function arrayCopy(a1)
{
	var a2 = new Array();
	var i;
	for( i = 0; i < a1.length; i++ ) {
		a2[a2.length] = a1[i];
	}
	return a2;
}

function arrayAnd(a1, a2)
{
	var a3 = new Array();
	var i;
	for( i = 0; i < a1.length; i++ ) {
		if( isValueInArray(a2, a1[i]) ) {
			a3[a3.length] = a1[i];
		}
	}
	return a3;
}

function arrayOr(a1, a2)
{
	var a3 = arrayCopy(a1);
	var i;
	for( i = 0; i < a2.length; i++ ) {
		if( !isValueInArray(a3, a2[i]) ) {
			a3[a3.length] = a2[i];
		}
	}
	return a3;
}

//
// new dialog
//
function euaNewOpen()
{
    // open new window;
    // no spaces in string, bug in function window.open()
	var w = window.open('', 'euaNew', 'width=450,height=400,resizable=yes,scrollbars=yes');
	var code, temp1, temp2, i, j;

    // bring window to top
    if( !w.opener ) {
    	w.opener = self;
    }
    	
    if( w.focus != null ) {
    	w.focus();
    }
    
    // unpack the table'n'fields
    if( euaTables.length == 0 ) {
	    temp1 = euat.split(';');
		for( i = 0; i < temp1.length; i+=2 ) {
			euaTables[i/2] = temp1[i];
			euaFields[i/2] = new Array();
			temp2 = temp1[i+1].split(',');
			for( j = 0; j < temp2.length; j++ ) {
				euaFields[i/2][j] = temp2[j];
			}
	    }
	}
    
    // create window content
    code =	'<html>\n\n'
    +			'<head>\n'
    +				'<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />\n'
    +				'<title>' + htmlconstant('_NEWGRANTTITLE') + '</title>\n'
    +			'</head>\n\n'
    +			'<body>'
    +				'<script type="text/javascript"><!--\n'
    +					'function euaNewLoadResult() {'
    +						'var s1=document.forms[0].selectTable;'
    +						'var s2=document.forms[0].selectField;'
    +						'var sr=document.forms[0].selectResult;'
    +						'var i, j, anySel1 = 0;'
    +						'sr.value = "";'
    +						'if( s1.selectedIndex != -1 && s2.selectedIndex != -1 ) {'
    +							'for(i=0; i<s1.options.length; i++) {'
    +								'if( s1.options[i].selected ) {'
    +									'for(j=0; j<s2.options.length; j++) {'
    +										'if( s2.options[j].selected ) {'
    +											'sr.value += anySel1? ", " : "";'
    +											'sr.value += s1.options[i].value + "." + s2.options[j].value;'
    +											'anySel1 = 1;'
   	+										'}'
    +									'}'
    +								'}'
    +							'}'
    +						'}'
    +					'}'
    +					'function euaNewCheckAllSel(s) {'
    +						'var i, numSel = 0;'
    +						'for( i = 0; i < s.options.length; i++ ) {'
    +							'if( s.options[i].selected ) { numSel++; }'
    +						'}'
    +						'if( numSel > 1 && numSel >= s.options.length-1 ) {'
    +							's.selectedIndex = 0;'
    +						'}'
    +					'}'
    +					'function euaNewOnList1Change(s1, s2) {'
    +						'var i, j, s2Options = new Array(), anySel = 0;'
    +						'euaNewCheckAllSel(s1);'
    +						'if( s1.selectedIndex==0 ) {'
    +							's2Options = new Array("*");'
    +							's1.selectedIndex = 0;' // make sure, only the first option is selected
    +							'for( i = 0; i < window.opener.euaFields.length; i++ ) {'
    +								's2Options = window.opener.arrayOr(s2Options, window.opener.euaFields[i]);'
    +							'}'
   	+						'} else {'
    +							'for( i = 0; i < s1.options.length; i++ ) {'
    +								'if( s1.options[i].selected ) {'
    +									'if( anySel == 0 ) {'
    +										's2Options = window.opener.euaFields[i-1];'
   	+									'} else {'
   	+										's2Options = window.opener.arrayAnd(s2Options, window.opener.euaFields[i-1]);'
    +									'}'
   	+									'anySel = 1;'
    +								'}'
    +							'}'
    +							'if( anySel ) {'
    +								's2Options = window.opener.arrayOr(new Array("*"), s2Options);'
    +							'}'
    +						'}'
    +						's2.selectedIndex = -1;'
    +						's2.options.length = 0;'
    +						'for( i = 0; i < s2Options.length; i++ ) {'
    +							's2.options[s2.length] = new Option(window.opener.get_transl(s2Options[i]), s2Options[i]);'
    +						'}'
    +						'euaNewLoadResult();'
    +					'}'
    +					'function euaNewOnList2Change(s2) {'
    +						'euaNewLoadResult();'
    +					'}'
    +				"\n/" + "/--></script>"
    +				'<div style="text-align:center;"><table border="0">'
    +					'<form name="dummy">'
    +					'<tr>'
    +						'<td>' + htmlconstant('_TABLES') + ':</td>'
    +						'<td>' + htmlconstant('_FIELDS') + ':</td>'
    +					'</tr>'
    +					'<tr>'
    +						'<td>'
    +							'<select multiple="multiple" name="selectTable" size="16" style="width:185px; height:300px; font-family:Courier New,monospace;" onchange="euaNewOnList1Change(this,window.document.forms[0].selectField);return true;">'
    +								'<option value="*">*</option>';
    
										for( i = 0; i < euaTables.length; i++ ) {
											code += '<option value="' + euaTables[i] + '">' + get_transl(euaTables[i]) + '</option>'
    									}
    
    code +=						'</select>'
    +						'</td>'
    +						'<td>'
    +							'<select multiple="multiple" name="selectField" size="16" style="width:185px; height:300px; font-family:Courier New,monospace;" onchange="euaNewOnList2Change(this);return true;">'
    +							'</select>'
    +						'</td>'
    +					'</tr>'
    +					'<tr>'
    +						'<td colspan="2" nowrap="nowrap">'
    +							'<input type="text" name="selectResult" value="" size="32" style="width:374px; font-family:Courier New,monospace;" />'
    +						'</td>'
    +					'</tr>'
    +					'<tr>'
    +						'<td colspan="2" align="center">'
    +							'<input class="button" type="submit" name="ok" value="' + htmlconstant('_ADD') + '" onclick="if(!window.opener.closed && typeof window.opener.euaAddGrant!=\'undefined\'){if(!window.opener.euaAddGrant(document.forms[0].selectResult.value)){alert(\'' +htmlconstant('_SELECTGRANTFIRST')+ '\');}} return false;" style="width:100px;" />'
    +							' <input class="button" type="submit" name="cancel" value="' + htmlconstant('_CLOSE') + '" onclick="window.close(); return false;" style="width:100px;" />'
    +						'</td>'
    +					'</tr>'
    +					'</form>'
    +				'</table></div>'
    +			'</body>\n\n'
    +		'</html>';
    
    // write window
    w.document.open();
    w.document.write(code);
    w.document.close();
}



//
// basic dialog
//
function euaSort() // sort 'euap' and 'euag'
{
	var i, j;
	
	// create temp. array
	var temp = new Array();
	for( i = 0; i < euap.length; i++ ) {
		temp[i] = getReadablePath(euap[i]) + ':' + euap[i] + ':' + euag[i];
	}
	
	// sort temp. array
	temp.sort();
	
	// re-create 'euap' and 'euag'
	euap = new Array();
	euag = new Array();
	for( i = 0; i < temp.length; i++ ) {
		j = temp[i].split(':');
		euap[i] = j[1];
		euag[i] = j[2];
	}
	
}

function euaSetHidden()
{
	var f = document.forms[0];
	var v = "";
	
	for( i = 0; i < euap.length; i++ ) {
		v = v + euap[i] + ":" + euag[i] + ";\n";
	}	
	
	f.euahidden.value = v;
}

function euaSetSelect(selOnlyTheseOpts)
{
	var f = document.forms[0];
	var i, selOpt = new Object();
	
	// find out the selected options
	if( selOnlyTheseOpts ) {
		for( i = 0; i < selOnlyTheseOpts.length; i++ ) {
			selOpt[selOnlyTheseOpts[i]] = 1;
		}
	}
	else {
		for( i = 0; i < f.euaSelect.options.length; i++ ) {
			if( f.euaSelect.options[i].selected ) {
				selOpt[f.euaSelect.options[i].value] = 1;
			}
		}
	}
	
	// clear selection listbox
	f.euaSelect.options.length = 0;
	
	// add options to selection listbox
	for( i = 0; i < euap.length; i++ ) {
		f.euaSelect.options[i] = new Option(getReadablePath(euap[i]) + getReadableGrants(euag[i]), 
			euap[i], 0, 
			(selOpt[euap[i]]?1:0));
	}
}

function euaSetCheck()
{
	var f = document.forms[0];
	var grants = "";
	var i;
	
	// OR grants
	for( i = 0; i < f.euaSelect.options.length; i++ ) {
		if( f.euaSelect.options[i].selected ) {
			grants += euag[i];
		}
	}

	// set checkboxes
	f.euaNew.checked = isCharInStr(grants, 'n');
	f.euaRead.checked = isCharInStr(grants, 'r');
	f.euaWrite.checked = isCharInStr(grants, 'w');
	f.euaDelete.checked = isCharInStr(grants, 'd');
}

function getCheckedGrants()
{
	var f = document.forms[0];
	var grants = "";
	if( f.euaNew.checked )		{ grants += "n"; }
	if( f.euaRead.checked )		{ grants += "r"; }
	if( f.euaWrite.checked )	{ grants += "w"; }
	if( f.euaDelete.checked )	{ grants += "d"; }
	return grants;
}

function euaChangeGrant(grant)
{
	var f = document.forms[0];
	var i, anySel = 0;

	// anything selected?
	if( f.euaSelect.selectedIndex == -1 ) {
		alert(htmlconstant('_SELECTGRANTFIRST'));
		return false;
	}
	
	// get grants
	if( grant == 'r' ) {
		if( !f.euaRead.checked ) {
			f.euaNew.checked = false;
			f.euaWrite.checked = false;
			f.euaDelete.checked = false;
		}
	}
	else {
		if( f.euaNew.checked || f.euaWrite.checked || f.euaDelete.checked ) {
			f.euaRead.checked = true;
		}
	}
	var grants = getCheckedGrants();
	
	// change the grants for all selected options
	for( i = 0; i < f.euaSelect.options.length; i++ ) {
		if( f.euaSelect.options[i].selected ) {
			euag[i] = grants;
			f.euaSelect.options[i].text = getReadablePath(euap[i]) + getReadableGrants(euag[i]);
			f.euaSelect.options[i].selected = true;
		}
	}
	
	// and: change
	euaSetHidden();
	return true;
}

function euaAddGrant(s)
{
	var f = document.forms[0];
	var grants = getCheckedGrants();
	var i;
	
	if( s ) 
	{
		s = s.replace(/\s/g, "");
		s = s.split(",");
		
		for( i = 0; i < s.length; i++ ) {
			if( s[i]!="" && !isValueInArray(euap, s[i]) ) {
				euap[euap.length] = s[i];
				euag[euag.length] = grants;
			}
		}
	
		euaSort();
		euaSetSelect(s /*select only these options*/);
		euaSetCheck();
		euaSetHidden();

		return true;
	}
	else 
	{
		return false;
	}
}

function euaDeleteGrant()
{
	var f = document.forms[0];
	var newEuap = new Array();
	var newEuag = new Array();
	var i, j;
	
	j = 0;
	for( i = 0; i < euap.length; i++ ) {
		if( !f.euaSelect.options[i].selected ) {
			newEuap[j] = euap[i];
			newEuag[j] = euag[i];
			j++;
		}
	}

	if( newEuap.length == euap.length ) 
	{
		alert(htmlconstant('_SELECTGRANTFIRST'));
	}
	else if( confirm(htmlconstant('_ONDELETEGRANT')) )
	{
		euap = newEuap;
		euag = newEuag;
		
		euaSetSelect();
		euaSetCheck();
		euaSetHidden();
	}
}

function euaSetEuapEuag(grants)
{
	var g, temp;

	euap = new Array();	// [e]dit [u]ser [a]ccess [p]aths - global
	euag = new Array();	// [e]dit [u]ser [a]ccess [g]rants - global
	
	grants = grants.replace(/\n/g, ";");
	grants = grants.replace(/\s/g, "");
	grants = grants.split(";");
	
	for( g = 0; g < grants.length; g++ ) {
		temp = grants[g].split(':');
		if( temp.length == 2 && temp[0] != '' ) {
			euap[euap.length] = temp[0];
			euag[euag.length] = temp[1];
		}
	}
}

function editor_callback(child_unique_id)
{
	if( child_unique_id == 'rights_editor' ) {
		euaSetEuapEuag(document.forms[0].euahidden.value);
		euaSort();
		euaSetSelect();
		euaSetHidden();
	}
}

function euaRender(grants)
{
	// create hash from the translations string
	var temp = euar, i;
	euar = new Object();
	temp = temp.split(';');
	for( i = 0; i < temp.length; i+=2 ) {
		euar[temp[i]] = temp[i+1];
	}

	// render
	document.write(
			'<input type="hidden" name="euahidden" value="" />'
		+	'<table cellpadding="0" cellspacing="0" border="0">'
		+		'<tr>'
		+			'<td valign="top" rowspan="2">'
		+				'<select multiple="multiple" size="16" name="euaSelect" style="width:400pt; font-family:Courier New,monospace;" onchange="euaSetCheck();return true;">'
		+				'</select>'
		+			'</td>'
		+			'<td rowspan="2">'
		+				'&nbsp;'
		+			'</td>'
		+			'<td nowrap="nowrap" valign="top">'
		+				'<input type="checkbox" name="euaRead" id="euaRead" onclick="return euaChangeGrant(\'r\');" /><label for="euaRead">' + htmlconstant('_READ') + '</label><br />'
		+				'<input type="checkbox" name="euaWrite" id="euaWrite" onclick="return euaChangeGrant(\'w\');" /><label for="euaWrite">' + htmlconstant('_EDIT') + '</label><br />'
		+				'<input type="checkbox" name="euaNew" id="euaNew" onclick="return euaChangeGrant(\'n\');" /><label for="euaNew">' + htmlconstant('_NEW') + '</label><br />'
		+				'<input type="checkbox" name="euaDelete" id="euaDelete" onclick="return euaChangeGrant(\'d\');" /><label for="euaDelete">' + htmlconstant('_DELETE') + '</label>'
		+			'</td>'
		+		'</tr>'
		+		'<tr>'
		+			'<td valign="bottom" nowrap="nowrap">'
		+				'&nbsp;<a href="" onclick="euaNewOpen();return false;">' + htmlconstant('_NEWGRANT___') + '</a><br />'
		+				'&nbsp;<a href="" onclick="euaDeleteGrant();return false;">' + htmlconstant('_DELETEGRANT') + '</a>'
		+			'</td>'
		+		'</tr>'
		+	'</table>'
	);
	
	euaSetEuapEuag(grants);
	euaSort();
	euaSetSelect();
	euaSetHidden();
}


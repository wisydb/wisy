

// search an element by prefix and index
function dbsearch_findelem(prefix, i)
{
	var j, dbsearch = 0, dbfield = 0;

	// find dbsearch formular
	for( j=0; j<document.forms.length; j++ ) {
		if( document.forms[j].name == 'dbsearch' ) {
			dbsearch = document.forms[j];
			break;
		}
	}
	
	if( !dbsearch ) {
		return 0;
	}

	// find field popup with index i
	for( j=0; j<dbsearch.elements.length; j++ ) {
		if( dbsearch.elements[j].name == prefix+i ) {
			dbfield = dbsearch.elements[j];
			break;
		}
	}

	if( !dbfield ) {
		alert("cannot find element dbsearch.f"+i);
		return false;
	}
	
	return dbfield;
}


// clear the value field
function dbsearch_clearv(i)
{
	var dbfield = dbsearch_findelem('v', i);
	if(dbfield) { 
		dbfield.value = ''; 
	}
}

// function selects the 1st value in a selection box
function dbsearch_select1st(prefix, i)
{
	var dbfield = dbsearch_findelem(prefix, i);
	if( dbfield ) {
		dbfield.selectedIndex = 0;
	}
}


// function selects the 2nd value of a selection box if the first was selected
function dbsearch_select2nd(prefix, i)
{
	var dbfield = dbsearch_findelem(prefix, i);
	if( dbfield && dbfield.selectedIndex == 0 ) {
		dbfield.selectedIndex = 1;
	}
}


// on change field
function dbsearch_chf(i, href)
{
	var dbfield;
	
	dbfield = dbsearch_findelem('f', i);
	if( dbfield ) {
		if( dbfield.selectedIndex == 0 ) 
		{
			// "no field" selected, init operator and value
			dbsearch_select1st('o', i);
			dbsearch_clearv(i);
		}
		else if( dbfield.options[dbfield.selectedIndex].value == 'DUMMY' ) {
			dbsearch_select1st('f', i);
		}
		else if( dbfield.options[dbfield.selectedIndex].value == 'OPTIONS' ) 
		{
			// "options..." selected, open new window for the field options
		    // no spaces in string, bug in function window.open()
		    var cWindow = window.open(href, 'dbsearch_opt',
		    			"width=260,height=500,resizable=yes,scrollbars=yes" );
		
		    // bring window to top
		    if( !cWindow.opener ) { cWindow.opener = self; }
		    if( cWindow.focus != null ) { cWindow.focus(); }
			dbsearch_select1st('f', i);
			
			return;
		}
	}
}



// on change operator
function dbsearch_cho(i)
{
	dbsearch_select2nd('f', i);
}



// open the value list
function dbsearch_list(href, index)
{
	var dbfield = dbsearch_findelem('f', index);

	if( dbfield ) 
	{
	    // open new window;
	    // no spaces in string, bug in function window.open()
	    var cWindow = window.open(href + "-" + dbfield.options[dbfield.selectedIndex].value + "-" + index, // dbfield.value does not work under netscape 4
	    			'dbsearch_list',
	    			"width=320,height=400,resizable=yes,scrollbars=yes" );
	
	    // bring window to top
	    if( !cWindow.opener ) { cWindow.opener = self; }
	    if( cWindow.focus != null ) { cWindow.focus(); }
	}
	   
    return false;
}


// add a row by submitting the search formular
function dbsearch_addrow()
{
	document.dbsearch.searchalt.value = 'addrow'; 
	document.dbsearch.submit(); 
	return false;
}

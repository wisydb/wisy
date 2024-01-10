<?php

/*=============================================================================
Some Tools to create table index and overviews
===============================================================================

file:	
	index_tools.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/




// private
function page_sel_dots($baseUrl, $currRowsPerPage, $currPageNumber, $maxPageNumber, $totalRows, $useSkin)
{
    $temp = isohtmlentities( strval( $baseUrl ) ) . intval($currPageNumber*$currRowsPerPage);
	$ahref = "<a href=\"$temp\" onclick=\"return pageSel('{$baseUrl}',$maxPageNumber,$totalRows,$currRowsPerPage);\">";

	if( $useSkin ) {
		global $site;
		$site->skin->mainmenuItem('...', $ahref, 0);
		return '';
	}
	else {
		return $ahref . '... </a>';
	}
}

function page_sel_link($baseUrl, $currRowsPerPage, $currPageNumber, $hilite, $useSkin)
{
    $ahref = '<a href="' . isohtmlentities( strval( $baseUrl ) ) . intval($currPageNumber*$currRowsPerPage) . '">';
	
	if( $useSkin )
	{
		global $site;
		$pageTitle = intval($currPageNumber+1);
		$site->skin->mainmenuItem($hilite? htmlconstant('_OVERVIEW_RESULTPAGE', $pageTitle) : $pageTitle, $ahref, $hilite);
		return '';
	}
	else
	{
		$ret = $ahref;
			if( $hilite ) { $ret.= '<b class="border">'; }
			$ret .= intval($currPageNumber+1);
			if( $hilite ) { $ret.= '</b>'; }
		$ret .= ' </a>';
		return $ret;
	}
}

function rows_per_page_sel_link($baseUrl, $currRowsPerPage, $hilite = 0)
{
    $ret = '<a href="' . isohtmlentities( strval( $baseUrl ) ) . intval($currRowsPerPage) . '">';
		if( $hilite ) { $ret.= '<b class="border">'; }
		$ret .= $currRowsPerPage;
		if( $hilite ) { $ret.= '</b>'; }
	$ret .= ' </a>';
	return $ret;
}



// public: the page selector
global $page_sel_surround;
$page_sel_surround = 3;
function page_sel($baseUrl, $currRowsPerPage, $currOffset, $totalRows, $useSkin = 0)
{
	global $page_sel_surround;
	
	$currPageNumber = 1;
	
	if( !isset($currRowsPerPage) || !$currRowsPerPage)
	    return '';
	
	// find out the current page number (the current page number is zero-based)
	$currPageNumber = intval($currOffset / $currRowsPerPage);

    // find out the max. page page number (also zero-based)
	$maxPageNumber = intval($totalRows / $currRowsPerPage);
	if( intval($totalRows / $currRowsPerPage) == $totalRows / $currRowsPerPage ) {
	  $maxPageNumber--;
	}
	
	
	// find out the first/last page number surrounding the current page (zero-based)
	$firstPageNumber = $currPageNumber-$page_sel_surround;
	if( $firstPageNumber < $page_sel_surround ) {
		$firstPageNumber = 0;
	}
	
	$lastPageNumber = $currPageNumber+$page_sel_surround;
	if( $lastPageNumber > ($maxPageNumber-$page_sel_surround) ) {
		$lastPageNumber = $maxPageNumber;
	}

	// get the options string
	$options = '';
	if( $firstPageNumber != 0 ) {
		$options .= page_sel_link($baseUrl, $currRowsPerPage, 0, 0, $useSkin) . ' ' . page_sel_dots($baseUrl, $currRowsPerPage, 1, $maxPageNumber, $totalRows-1, $useSkin);
	}
	
	for( $i = $firstPageNumber; $i<=$lastPageNumber; $i++ ) {
		$options .= page_sel_link($baseUrl, $currRowsPerPage, $i, $i==$currPageNumber? 1 : 0, $useSkin);
	}
	
	if( $lastPageNumber != $maxPageNumber ) {
		$options .= page_sel_dots($baseUrl, $currRowsPerPage, $lastPageNumber+1, $maxPageNumber, $totalRows-1, $useSkin) . page_sel_link($baseUrl, $currRowsPerPage, $maxPageNumber, 0, $useSkin);
	}

	return trim($options);
}



// public: the "rows per page" selector
global $rows_per_page_options;
$rows_per_page_options = array(10, 20, 50);
function rows_per_page_sel($baseUrl, $currRowsPerPage)
{
	global $rows_per_page_options;
	
	// get the "number of rows" options string
	$options = '';
	$hiliteDone = 0;
	for( $i = 0; $i < sizeof((array) $rows_per_page_options); $i++ ) 
	{
		if( !$hiliteDone 
		 && $currRowsPerPage<$rows_per_page_options[$i] )
		{
			$options .= rows_per_page_sel_link($baseUrl, $currRowsPerPage, 1);
			$hiliteDone = 1;
		}
		
		if( $rows_per_page_options[$i] == $currRowsPerPage ) 
		{
			$options .= rows_per_page_sel_link($baseUrl, $currRowsPerPage, 1);
			$hiliteDone = 1;
		}
		else 
		{
			$options .= rows_per_page_sel_link($baseUrl, $rows_per_page_options[$i]);
		}
	}

	if( !$hiliteDone )
	{
		$options .= rows_per_page_sel_link($baseUrl, $currRowsPerPage, 1);
	}

    // input field
	$options .= "<a href=\"\" onclick=\"return rowsPerPageSel('{$baseUrl}');\">...</a>";

	// done.
	return trim($options);
}
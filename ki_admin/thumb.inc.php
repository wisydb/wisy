<?php



/*=============================================================================
Image Thumbnail handling functions
===============================================================================

file:	
	thumb.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/



function fit_to_rect($orgw, $orgh, $maxw, $maxh, &$scaledw, &$scaledh)
{
	$scaledw = $orgw;
	$scaledh = $orgh;
	
	if( $scaledw > $maxw ) {
		$scaledh = intval(($scaledh*$maxw) / $scaledw);
		$scaledw = $maxw;
	}

	if( $scaledh > $maxh ) {
		$scaledw = intval(($scaledw*$maxh) / $scaledh);
		$scaledh = $maxh;
	}
}



function create_thumb_img_tag(	$thumbw, $thumbh,
								$url, $orgw, $orgh, $title = '', $popup = 1)
{
	$ret = '';

	fit_to_rect($orgw, $orgh, $thumbw, $thumbh, $scaledw, $scaledh);

	if( $orgw==$scaledw && $orgh==$scaledh ) 
	{
		$popup = 0;
	}

	if( $popup ) 
	{
		$windoww = $orgw + 60;
		$windowh = $orgh + 80;
		fit_to_rect($windoww, $windowh, 750, 550, $windoww, $windowh);
		$ret .= "<a href=\"$url\" target=\"thumbfullview\" onclick=\"w=window.open(this.href,this.target,'width=$windoww,height=$windowh,resizable=yes,scrollbars=yes'); if(w.focus!=null) {w.focus();} return false;\">";
	}

	$ret .=  "<img src=\"$url\" width=\"$scaledw\" height=\"$scaledh\" border=\"0\" alt=\"$title\" title=\"$title\" />";
	
	if( $popup )
	{
		$ret .= "</a>";
	}
	
	return $ret;
}



?>
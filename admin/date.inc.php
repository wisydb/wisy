<?php



/*=============================================================================
Date Functions
===============================================================================

file:	
	date.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file

=============================================================================*/



//
// functions remove/add leading zeros from/to a string
//
function lzerotrim($s) 
{
	while( $s[0]=='0' ) {
		$s = substr($s, 1);
	}
	
	return $s;
}

function lzeroadd($s, $wantedlen)
{
	while( strlen($s) < $wantedlen ) {
		$s = '0' . $s;
	}
	
	return $s;
}	



//
// the function returns the number of days of the month in the given year.
// Leap years are taken into consideration.
//
function days_in_month($m, $y)
{
	$m = lzerotrim($m); 
	$d = 31;
	$y = lzerotrim($y);

	$tries = 0;	
	while( !checkdate($m, $d, $y) ) {
		$d--;
		$tries++;
		if( $tries > 100 ) {
			return 28;
		}
	}
	
	return $d;
}



//
// function creates an SQL date from given year, month etc.
// 
function get_sql_date($year, $month, $day, $hour, $minute, $second)
{
	$year  = trim($year);
	$month = trim($month);	if( !$month ) $month = '0';
	$day   = trim($day);	if( !$day   ) $day   = '0';
	$hour  = trim($hour); 	if( !$hour  ) $hour  = '0';
	$minute= trim($minute);	if( !$minute) $minute= '0';
	$second= trim($second);	if( !$second) $second= '0';
	
	if( strlen($year)==2 ) {
		$year = lzerotrim($year) + ($year < 60 ? 2000 : 1900);
	}
	else if( strlen($year)==4 ) {
		$year = lzerotrim($year);
	}
	else {
		$year = 0;
	}
	$year = intval($year);
	
	return sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second);
}



//
// function checks an SQL date. if the date is correct, an empty string is
// returned. for errors, the function returns a HTML-ready error string.
//
function check_sql_date($d, $mayBeUnset = 0)
{
	if( $mayBeUnset && $d=='0000-00-00 00:00:00' ) {
		return '';
	}

	$d = str_replace(' ', '-', $d);
	$d = str_replace(':', '-', $d);
	$d = explode('-', $d);
	
	if( !checkdate( (isset($d[1]) ? $d[1] : null) , (isset($d[2]) ? $d[2] : null) , (isset($d[0]) ? $d[0] : null) ) ) {
		return htmlconstant('_ERRINVALIDDATE');
	}
	
	if( isset($d[3]) && $d[3]<0 || isset($d[3]) && $d[3]>23 || isset($d[4]) && $d[4]<0 || isset($d[4]) && $d[4]>59 || isset($d[5]) && $d[5]<0 || isset($d[5]) && $d[5]>59 ) {
		return htmlconstant('_ERRINVALIDTIME');
	}
	
	return '';
}


function sql_date_to_timestamp($d)
{
	$d = str_replace(' ', '-', $d);
	$d = str_replace(':', '-', $d);
	$d = explode('-', $d);
	
	
	return mktime( isset($d[3]) ? intval($d[3]) : 0, 
	               isset($d[4]) ? intval($d[4]) : 0, 
	               isset($d[5]) ? intval($d[5]) : 0, 
	               isset($d[1]) ? intval($d[1]) : 0, 
	               isset($d[2]) ? intval($d[2]) : 0, 
	               isset($d[0]) ? intval($d[0]) : 0
	              );
}


//
// function creates a human readable or editable sting from a SQL date, options are:
// date, datetime, dateopt, editable, absolute (may be combined using spaces or commas)
//
function sql_date_to_human($d, $options = 'datetime')
{
	// init options
	$optionAbsolute		= 0; // show relative date by default
	$optionWeekdays		= 1; // show weekdays by default
	$optionCentury		= 0; // don't show century by default
	$optionTime			= 0; // don't show time by default
	$optionSeconds		= 0; // don't show seconds by default
	$optionOpt			= 0; // by default we assume a full date specification

	// get options from registry
	if( @function_exists('regGet') )
	{
		$dateSetting = regGet('date.global', 'relative, weekdays');
		$dateSetting = str_replace(',', ' ', "  $dateSetting  ");
		
		$optionAbsolute = strpos($dateSetting, ' relative ')?	0 : 1;
		$optionWeekdays	= strpos($dateSetting, ' weekdays ')?	1 : 0;
		$optionCentury	= strpos($dateSetting, ' century ')?	1 : 0;
		$optionTime		= strpos($dateSetting, ' time ')?		1 : 0;
		$optionSeconds	= strpos($dateSetting, ' seconds ')?	1 : 0;
	}

	// get options from function param (overwrites the registry options)
	$options = str_replace(',', ' ', "  $options ");
	
	if( strpos($options, ' time ') ) {
		$optionTime = 1;
	}

	if( strpos($options, ' -weekdays ') ) { $optionWeekdays = 0; }
	
	if( strpos($options, ' datetime ') ) {
		;
	}
	else if( strpos($options, ' dateopt ') ) {
		$optionTime = 0;
		$optionOpt	= 1;
	}
	else {
		$optionTime = 0;
	}
	
	if( strpos($options, ' absolute ') ) {
		$optionAbsolute = 1;
	}

	$optionEditable = strpos($options, ' editable ')? 1 : 0;
	if( $optionEditable )
	{
		$optionAbsolute = 1;
		$optionWeekdays = 0;
		$optionCentury	= 1;
		$optionTime		= strpos($options, ' datetime ')? 1 : 0;
		$optionSeconds	= 1;
	}
	
	// date set?
	if( $d == '0000-00-00 00:00:00' || $d=='' ) {
		return $optionEditable? '' : htmlconstant('_NA');
	}
	
	// convert SQL-Date to array(year, month, day, hour, minute, second)
	$d = str_replace(' ', '-', $d);
	$d = str_replace(':', '-', $d);
	$d = str_replace('--', '-', $d);
	$d = explode('-', $d);

	// check if a complete date is given, otherwise return month/year only
	if( $optionOpt 
	 && $d[3] == 0 /*hour*/
	 && $d[4] == 0 /*minute*/ )
	{
		if( $d[5] == 2 /*second*/
		 && $d[1] == 1 /*month*/
		 && $d[2] == 1 /*day*/ )
		{
			return $d[0]; /*year*/
		}
		else if( $d[5] == 1 /*second*/ 
			  && $d[2] == 1 /*day*/)
		{
			return $d[1] /*month*/ . '/' .$d[0] /*year*/;
		}
	}

	// get the date string
	$format = htmlconstant('_CONST_DATEFORMAT');
	$year = $optionCentury? $d[0] : substr($d[0], 2, 2);
	if( $format == 'dd.mm.yyyy' || $format=='_CONST_DATEFORMAT' )
	{
		$ret = $d[2] /*day*/ . '.' . $d[1] /*month*/ . '.' . $year;
	}
	else
	{
		$ret = $d[1] /*month*/ . '/' . $d[2] /*day*/ . '/' . $year;
	}
	
	// add weekday or relative date
	if( !$optionEditable 
	 && checkdate($d[1], $d[2], $d[0]) 
	 && $d[0]>1970 /*cannot find out weekdays for older dates*/ ) 
	{
		$relDateSet = 0;
		
		if( !$optionAbsolute )
		{
			// make relative date
			$dateinfo = getdate();
			if( $dateinfo['mday']==$d[2] && $dateinfo['mon']==$d[1] && $dateinfo['year']==$d[0] ) {
				$ret = htmlconstant('_TODAY');
				$relDateSet = 1;
			}
			else {
				$timestamp_day = mktime(0,0,0,$dateinfo['mon'],$dateinfo['mday'],$dateinfo['year']);
				$dateinfo = getdate($timestamp_day - 86400 /*Number of seconds in a day*/);
				if( $dateinfo['mday']==$d[2] && $dateinfo['mon']==$d[1] && $dateinfo['year']==$d[0] ) {
					$ret = htmlconstant('_YESTERDAY');
					$relDateSet = 1;
				}
				else {
					$dateinfo = getdate($timestamp_day + 86400*2 /*Number of seconds in a day*/);
					if( $dateinfo['mday']==$d[2] && $dateinfo['mon']==$d[1] && $dateinfo['year']==$d[0] ) {
						$ret = htmlconstant('_TOMORROW');
						$relDateSet = 1;
					}
				}
			}
		}
		
		if( $optionWeekdays && !$relDateSet )
		{
			$dateinfo = @getdate(@mktime(0,0,0,$d[1],$d[2],$d[0]));
			$weekdays = array('_SUNDAY','_MONDAY','_TUESDAY','_WEDNESDAY','_THURSDAY','_FRIDAY','_SATURDAY',);
			$ret = htmlconstant($weekdays[$dateinfo['wday']]) . ', ' . $ret;
		}
	}

	// add time
	if( $optionTime )
	{
		$ret .= ', ' . strval($d[3]) . ':' . strval($d[4]);
		if( $optionSeconds ) {
		    $ret .= ':' . (isset($d[5]) ? strval($d[5]) : '');
		}
	}
	
	return $ret;
}



//
// help functions for sql_date_from_human(). for internal use only.
//
function sql_date_from_human_correctyear($y)
{
	if( strlen($y)==0 ) {
		$y = date('Y');
	}
	else if( strlen($y)==2 ) {
		$y = lzerotrim($y) + ($y < 60 ? 2000 : 1900);
	}
	else  {
		$y = lzerotrim($y);
	}
	
	if( $y=='' ) {
		$y = 0;
	}
	
	return intval($y);
}

function sql_date_from_human_correctmonth($m)
{
	$months = array	(	
						"ap",  4,
						"au",  8,
						"de",  12,
						"f",   2,
						"ja",  1, 
						"jul", 7,
						"jun", 6,
						"mai", 5,
						"m�r", 3,
						"mar", 3,
						"may", 5,
						"n",   11,
						"o",   10,
						"s",   9,
					);

	$m = strtolower(lzerotrim($m));
	for( $i = 0; $i < sizeof($months); $i += 2 )
	{
		if( strspn($m, $months[$i])==strlen($months[$i]) ) {
			$m = $months[$i+1];
			break;
		}
	}
	
	return $m;
}

function sql_date_from_human_1sttry($s, $type = 'date' /*or datetime or dateopt*/)
{
	// handle date
	$cutbefore = array	(	
							" den ", ",den ",
							" the ", ",the ",
							"tag,", "tag ,",
							"woch,", "woch ,",
							"abend,", "abend ,",
							"day,", "day ,",
							"mo,", "di,", "mi,", "do,", "fr,", "sa,", "so,",
							"mo ,", "di ,", "mi ,", "do ,", "fr ,", "sa ,", "so ,",
						);

	// remove unneeded whitespace	
	$s = strtolower(trim($s));
	while( strstr($s, '  ') ) {
		$s = str_replace('  ', ' ', $s);
	}
	
	// cut before?
	for( $i = 0; $i < sizeof($cutbefore); $i++ )
	{
		if( is_int($p=strpos($s, $cutbefore[$i])) ) {
			$s = substr($s, $p+strlen($cutbefore[$i]));
			$s = trim($s);
			break;
		}
	}
	
	// get time
	$h = '00:00:00';
	if( $p=strpos($s, ',') ) {
		$temp = trim(substr($s, $p+1));
		$s = substr($s, 0, $p);
		$temp = explode(':', $temp);
		
		if( $temp[0] < 0  )	$temp[0] = 0;
		if( $temp[0] > 23 )	$temp[0] = 23;
		if( $temp[1] < 0  )	$temp[1] = 0;
		if( $temp[1] > 59 )	$temp[1] = 59;
		if( $temp[2] < 0  )	$temp[2] = 0;
		if( $temp[2] > 59 )	$temp[2] = 59;
		
		$h = lzeroadd(intval($temp[0]), 2) . ':' . lzeroadd(intval($temp[1]), 2) .':'. lzeroadd(intval($temp[2]), 2);
	}
	
	// split into day, month and year
	if( strstr($s, '-') ) // SQL like
	{
		$s = str_replace('- ', '-', $s);
		$s = str_replace(' -', '-', $s);
		$s = str_replace(' ',  '-', $s);
		$s = explode('-', $s);
		if( sizeof($s)!=3 ) {
			return 0; // error
		}
		$d = $s[2];
		$m = $s[1];
		$y = $s[0];
	}
	else if( strstr($s, '.') || strstr($s, ',') ) // German
	{
		$s = str_replace(',',  '.', $s);
		$s = str_replace('. ', '.', $s);
		$s = str_replace(' .', '.', $s);
		$s = str_replace(' ',  '.', $s);
		$s = explode('.', $s);
		if( sizeof($s)!=3 ) {
			return 0; // error
		}
		$d = $s[0];
		$m = $s[1];
		$y = $s[2];
	}
	else if( strstr($s, '/') ) // English (not: American)
	{
		$s = str_replace('/ ', '/', $s);
		$s = str_replace(' /', '/', $s);
		$s = str_replace(' ',  '/', $s);
		$s = explode('/', $s);
		
		if( sizeof((array) $s)!=3 ) {
			return 0; // error
		}
		
		if( strlen($s[0])==4 ) {
			$d = $s[2];
			$m = $s[1];
			$y = $s[0];
		}
		else {
			$d = $s[0];
			$m = $s[1];
			$y = $s[2];
		}
	}
	else
	{
		return 0; // error
	}

	// correct year
	$y = intval(sql_date_from_human_correctyear($y));
	
	// correct month
	$m = intval(sql_date_from_human_correctmonth($m));
		
	// correct day
	$d = intval(lzerotrim($d));
	
	// finally, check date
	if( !checkdate($m, $d, $y) ) {
		return 0; // error
	}

	return lzeroadd($y, 4) . '-' . lzeroadd($m, 2) . '-' . lzeroadd($d, 2) . ' ' . $h;
}

function sql_date_from_human_2ndtry($s, &$ret2)
{
	// convert spacing characters to spaces
	$s = trim(strtr( strval($s),	"_.:,;/+\\", 
			  			"         "));
	// remove double spaces
	while( strpos($s, '  ') ) {
		$s = str_replace('  ', ' ', $s);
	}
	
	// explode by spaces
	$monthGiven = 0;
	$s = explode(' ', $s);
	if( sizeof((array) $s) == 2 ) {
		$month = sql_date_from_human_correctmonth($s[0]);
		if( $month < 1 || $month > 12 ) {
			return 0; // error
		}
		else {
			$monthGiven = 1;
		}
		$month = lzeroadd($month, 2);
		$year  = lzeroadd(sql_date_from_human_correctyear($s[1]), 4);
	}
	else {
		if( intval($s[0])==0 || strlen($s[0]) < 2 ) {
			return 0; // error
		}
		
		$month = lzeroadd(1, 2);
		$year  = lzeroadd(sql_date_from_human_correctyear($s[0]), 4);
	}
	
	// done
	if( $monthGiven ) {
		$ret2 = "$year-$month-" . days_in_month($month, $year) . " 00:00:00";
		return "$year-$month-01 00:00:01";
	}
	else {
		$ret2 = "$year-12-31 00:00:00";
		return "$year-01-01 00:00:02";
	}
}



//
// function sql_date_from_human() convert a human date (german or english, 
// not: american) in to the correct SQL representation. 
//
// the created string is returned.
//
// accepted formats:
// yyyy-mm-dd
// dd.mm.yyyy
// dd.mm.yy
// dd/mm/yyyy
// dd/mm/yy
// yyyy/mm/dd
//
// further features:
// - smart whitespace cutting
// - the month may be given literal in german or in english
// - prepended weekdays are cutted
//
function sql_date_from_human($s, $type = 'date' /*date, datetime, dateopt or dateoptspan*/)
{
	// create an array with the datespan as the return value?
	$span = 0;
	if( $type == 'dateoptspan' ) 
	{
		$type = 'dateopt';
		$span = 1;
		if( sizeof(explode('-', $s))==2 ) 
		{
			list($s1, $s2) = explode('-', $s);
			if( ($ret1=sql_date_from_human($s1, 'dateoptspan'))==0
			 || ($ret2=sql_date_from_human($s2, 'dateoptspan'))==0 ) {
			 	return array(); // error
			}
			
			$ret1 = array_merge($ret1, $ret2);
			sort($ret1);
			return array($ret1[0], $ret1[sizeof($ret1)-1]); // success
		}
	}
	
	// find out the date
	if( ($sqlDate1=sql_date_from_human_1sttry($s, $type)) != 0 ) {
		return $span? array($sqlDate1) : $sqlDate1; // success
	}

	if( $type=='dateopt' && ($sqlDate1=sql_date_from_human_2ndtry($s, $sqlDate2))!=0 ) {
		return $span? array($sqlDate1, $sqlDate2) : $sqlDate1; // success
	}

	return $span? array() : '0000-00-00 00:00:00'; // error
}



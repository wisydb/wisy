<?php



/*=============================================================================
EQL (Else Query Language) to SQL (Structured Query Language) plus other tools
===============================================================================

file:
	eql.inc.php

author:
	Bjoern Petersen

parameters:
	none, only function definitions in this file

usage:

	convert EQL to SQL:
				  $ob = new EQL2SQL_CLASS($table);
		[$pcode = $ob->getPCode($eql);]
		   $sql = $ob->eql2sql($eql, $addSqlFields, $addSqlCond, $sqlOrderBy);
		$table must be defined in "admin/config/db.inc.php"
	normalize a function name:
		$funcName = g_eql_normalize_func_name($funcName, $ignorePluralS)

todo:

	give AND a higher priority than OR - "a or b and c or d" should be equal
	to "(a or b) and (c or d)", currently we use "a or (b and (c or d))"


EQL rough overview:

	operators:
		and, or, xor, not, =, <>, >, <, >=, <=
	predefined functions:
		not(), id(), date(), timewindow(),
		oneof(), allof(),
		exact(), fuzzy(), lazy(),
		job(), active(),
		created(), createdby(), modified(), modifiedby()
		group(), rights()
	functions can be used with brackets or with an operator:
		title(important document)		id(12)
		title=important document		id>=10 and id <=20
	functions can also be nested:
		date.timewindow=today			date(timewindow(today))

  DATE(TIMEWINDOW(12)) MAINAREA(P) AND (TEXT(PHRASE(iff)) OR TEXT(turner) OR TEXT(PHRASE(institut für finanzdienstleistungen)) OR TEXT(reifner) OR TEXT(tiffe) OR TEXT(springeneer) OR TEXT(jaquemoth))
= timewindow(12) mainarea(p) oneof(iff turner "institut für finanzdienstleistungen" reifner tiffe springeneer jaquemoth)
=============================================================================*/


global $site;
if( !is_object($site) ) 
{	
	
	$site = new ADMIN_SITE_CLASS(); 
	if( file_exists('admin/eql.inc.php') ) {
		// create dummy site for call eg. from /core20
		$site->sitePath=''; $site->adminDir='admin';
	}
	else if( file_exists('../../admin/eql.inc.php') ) {
		// create dummy site for call eg. from /api/v1
		$site->sitePath='../../'; $site->adminDir='admin';
	}
}

require_once("{$site->sitePath}{$site->adminDir}/table_def.inc.php");
require_once("{$site->sitePath}{$site->adminDir}/config/db.inc.php");

//
// Configuration
//
global $g_eql_prefer_joins;
$g_eql_prefer_joins = 1;
if( isset($_GET['eqltoggle']) ) 
	$g_eql_prefer_joins = $g_eql_prefer_joins? 0 : 1;


//
// EQL defines
//
define('EQL_PHRASE',		0x00000001);
define('EQL_NEAR',			0x00000002);
define('EQL_PHRASE_BITS',	0x0000000F);

define('EQL_EXACT',			0x00000010);
define('EQL_LAZY',			0x00000020);
define('EQL_FUZZY',			0x00000040);
define('EQL_FUZZY_BITS',	0x000000F0);

define('EQL_ONEOF',			0x00000100);
define('EQL_ALLOF',			0x00000200);
define('EQL_ONEALL_BITS',	0x00000F00);

define('EQL_LESS',			0x00001000);
define('EQL_LESSEQUAL',		0x00002000);
define('EQL_GREATER',		0x00004000);
define('EQL_GREATEREQUAL',	0x00008000);
define('EQL_OP_BITS',		0x0000F000);

define('EQL_TIMEWINDOW',	0x00010000);

define('EQL_INUSERFUNC',	0x00100000);


//
// a global database object needed for queries
//
global $g_eql_db;
$g_eql_db = new DB_Admin();



// normalize a function name by...
function g_eql_normalize_func_name($funcName, $pluralS = 1)
{
	// ...name to upper case
	$funcName = strtolower(trim($funcName));

	// ...remove some leading stuff and the underscore '_'
	$funcName = str_replace('flag_', '', $funcName);
	$funcName = str_replace('secondary_', '', $funcName);
	$funcName = str_replace('attr_', '', $funcName);
	$funcName = str_replace('_', '', $funcName);

	// ...remove some trailing stuff
	if( $funcName != 'id' && $funcName != 'herkunftsid' && substr($funcName, -2) == 'id' ) { // 12:49 13.03.2013: special handling for "herkunftsid" as this would normalize to "herkunft" which is also a valid field
		$funcName = substr($funcName, 0, strlen($funcName)-2);
	}
	else if( $funcName!='ids' && substr($funcName, -3) == 'ids' ) {
		$funcName = substr($funcName, 0, strlen($funcName)-3);
	}

	if( substr($funcName, -1) == '_' ) {
		$funcName = substr($funcName, 0, strlen($funcName)-1);
	}

	// ...add a plural 's'
	if( $pluralS ) {
		if( substr($funcName, -1) != 's' ) {
			$funcName .= 's'; // add plural 's'
		}

		if( $funcName == 'themas' ) {
			$funcName = 'themens';
		}
	}

	// done.
	return $funcName;
}



function g_eql_normalize_words($words_, $unique = 0, $keepNumbers = 0, $keepWildcards = 0)
{
	// convert void characters to spaces
	$tr = 					"«»@^_=&´`.:,;/!'~+-#|<>()[]\{}\$%§\"\\\n\r\t";
	if(!$keepNumbers)$tr  .="0123456789";
	if(!$keepWildcards)$tr.="?*";
	$words = strtr($words_,	$tr,
				  			"                                                                ");
	// remove multiple spaces
	while( !(strpos($words, '  ')===false) ) {
		$words = str_replace('  ', ' ', $words);
	}

	// lower string
	$words = trim(strtolower($words));

	// return words in an array
	if( $words != '' ) {
		$words = explode(' ', $words);
	}
	else {
		$words = array(); // no real words found
	}

	// create unique values?
	if( $unique ) {
		$temp = array_flip($words);
		$words = array();
		reset($temp);
		while( list($word) = each($temp) ) {
			$words[] = $word;
		}
	}

	// done
	return $words;
}



function g_eql_normalize_natsort_callback($matches)
{
	// add leading zeros to a number to allow a 'natural' ordering
	if( strlen($matches[0]) < 10 ) {
		return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
	}
	else {
		return $matches[0];
	}
}
function g_eql_normalize_natsort($str)
{
	// lower string
	$str = strtolower($str);

	// convert accented characters
	$str = strtr($str,	'áàâåãæçéèêëíìîïñóòôõøúùûýÿ',
						'aaaaaaceeeeiiiinooooouuuyy');

	// convert german umlaute
	$str = strtr($str,	array('ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue', 'ß'=>'ss'));

	// convert numbers to a 'natural' sorting order
	$str = preg_replace_callback('/[0-9]+/', 'g_eql_normalize_natsort_callback', $str);

	// strip special characters
	$str = strtr($str,	'\'\\!"§$%&/(){}[]=?+*~#,;.:-_<>|@€©®£¥  ',
						'                                        ');

	// remove spaces
	$str = str_replace(' ', '', $str);

	// done
	return $str;
}



//
// creates a string from an array with table names. the string can be used in
// a SQL-FROM-clause.
//
function g_eql_array2string($tables, $sep = ' ')
{
	$ret = '';
	for( $i = 0; $i < sizeof($tables); $i++ ) {
		$ret .= ($i? $sep : '') . $tables[$i];
	}
	return $ret;
}


function g_eql_getmicrotime()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}



// relative dates may be given as [-|+]<value>[m|d|y|h]
// timewindow.durchfuehrung.beginn(+2d-3d)))
function g_rel2absdate($reldate, &$absdate1, &$absdate2, &$unit)
{
	$reldate = strtolower(trim($reldate));

	// past or future?
	$timedir = -1;
	if( $reldate{0} == '+' ) { $reldate = trim(substr($reldate, 1)); $timedir = 1; }
	if( $reldate{0} == '-' ) { $reldate = trim(substr($reldate, 1)); }

	// get the value
	$value = intval($reldate) * $timedir;

	// get the unit
	if( substr($reldate, -1) == 'h' || substr($reldate, -1) == 's' )
	{
		$unit		= 'h';
		$seconds	= $value * 3600; /*no. of seconds in an hour*/
		$absdate1	= strftime("%Y-%m-%d %H:00:00", time()+$seconds);
		$absdate2	= strftime("%Y-%m-%d %H:59:59", time()+$seconds);
	}
	else if( substr($reldate, -1) == 'd' || substr($reldate, -1) == 't' )
	{
		$unit		= 'd';
		$seconds	= $value * 86400; /*no. of seconds in a day*/
		$absdate1	= strftime("%Y-%m-%d 00:00:00", time()+$seconds);
		$absdate2	= strftime("%Y-%m-%d 23:59:59", time()+$seconds);
	}
	else if( substr($reldate, -1) == 'y' || substr($reldate, -1) == 'j' )
	{
		$unit		= 'y';
		$seconds	= $value * 31622400; /*no. of seconds in a year*/
		$absdate1	= strftime("%Y-01-01 00:00:00", time()+$seconds);
		$absdate2	= strftime("%Y-12-31 23:59:59", time()+$seconds);
	}
	else
	{
		$unit		= 'm';
		$seconds	= $value * 2678400; /* no. of seconds in a month*/
		$temp		= time() + $seconds;
		$absdate1	= strftime("%Y-%m-01 00:00:00", $temp);
		$absdate2	= strftime("%Y-%m-" .days_in_month(strftime('%m', $temp), strftime('%Y', $temp)). " 23:59:59", $temp);
	}

	// get the timestamp
	return strftime("%Y-%m-%d %H:%M:%S", time() + $seconds);
}



function g_addJoin(&$joins, $joinedTable, $joinedField, $primaryField)
{
	global $g_joinHash;

	// does the join exist? if so, re-use it.
	for( $i = 1; $i <= sizeof($joins); $i++ ) {
		if( $joins[$i-1] == "LEFT JOIN $joinedTable AS j$i ON j$i.$joinedField=$primaryField" ) {
			return "j$i";
		}
		if( $joins[$i-1] == "LEFT JOIN $joinedTable ON $joinedTable.$joinedField=$primaryField" ) {
			return $joinedTable;
		}
	}

	// add a new join
	if( $g_joinHash[$joinedTable] )
	{
		$g_joinHash[$joinedTable] = $g_joinHash[$joinedTable]=='self'? 'self' : 'alias';
		$i = sizeof($joins)+1;
		$joins[] = "LEFT JOIN $joinedTable AS j$i ON j$i.$joinedField=$primaryField";
		return "j$i";
	}
	else
	{
		$g_joinHash[$joinedTable] = 'direct';
		$joins[] = "LEFT JOIN $joinedTable ON $joinedTable.$joinedField=$primaryField";
		return $joinedTable;
	}
}


function g_addRelation(&$joins, $joinedTable, $joinedField, $primaryTable, $relation)
{
	if( $relation == TABLE_SECONDARY )
	{
		$alias = g_addJoin($joins, "{$primaryTable}_{$joinedField}", 'primary_id', "$primaryTable.id");
		return g_addJoin($joins, $joinedTable, 'id', "$alias.secondary_id");
	}
	else if( $relation == TABLE_MATTR )
	{
		$alias = g_addJoin($joins, "{$primaryTable}_{$joinedField}", 'primary_id', "$primaryTable.id");
		return g_addJoin($joins, $joinedTable, 'id', "$alias.attr_id");
	}
	else if( $relation == TABLE_SATTR )
	{
		return g_addJoin($joins, $joinedTable, 'id', "$primaryTable.{$joinedField}");
	}
}

function g_debug_out($headline, $text, $color, $leaveOpen = 0)
{
	global $debug;

	if( $debug == '1' || $debug == 'eql' ) {
		$colors = array('blue'=>'#C0FFFF', 'yellow'=>'#FFFFC0', 'green'=>'#C0FFC0', 'red'=>'#FF4040');
		echo '&nbsp;<br /><table width="100%" border="1" cellpadding="3" cellspacing="0" bgcolor="' .$colors[$color]. '"><tr><td';
		 if($leaveOpen) echo " colspan=\"$leaveOpen\"";
		echo '>';

			echo '<b>eql.inc.php: ' . $headline . '</b>';

		echo '</td></tr><tr><td';
		 if($leaveOpen) echo " colspan=\"$leaveOpen\"";
		echo '>';

			echo $text;

		echo '</td></tr>';
		if( !$leaveOpen ) {
			echo '</table>';
		}
	}
}

function g_debug_sql_out($headline, $text, $color, $leaveOpen = 0)
{
	$text = isohtmlentities($text);
	$text = str_replace(" LEFT JOIN ", " <br />LEFT JOIN ", $text);
	$text = str_replace(" WHERE ", " <br />WHERE ", $text);
	$text = str_replace(" GROUP BY ", " <br />GROUP BY ", $text);
	$text = str_replace(" ORDER BY ", " <br />ORDER BY ", $text);
	g_debug_out($headline, $text, $color, $leaveOpen);
}


//
// the EQL parser class
//
class EQL_PARSER_CLASS
{
	var $lastError;		// last error code

	var $func1;			// the (partly) expression
	var $ident1;
	var $expr1;
	var $op;
	var $expr2;



	//
	// EQL_PARSER_CLASS Constructor
	// ----------------------------
	// used grammar:
	//	expr	 = [function] "(" expr ")" | [function] ident | [function] ident op expr
	//	function = "phrase" | "near" | "exact" | "fuzzy" | "lazy" | "not" | ... | <table defined>
	//	op		 = "and" | "or"
	//	ident	 = ...
	//
	function __construct(&$symbols, &$s, $consumeOp = 1, $lastFunc = '')
	{
		// get function...
		if( $symbols[$s]{0} == '"' /* " indicates a function */ )
		{
			$this->func1 = substr($symbols[$s], 1);
			$lastFunc = $this->func1;
			$s++;
		}

		// get left part...
		if( $symbols[$s] == '(' )
		{
			$s++;
			$this->expr1 = new EQL_PARSER_CLASS($symbols, $s, 1 /* consume operator */, $lastFunc);
			if( $this->expr1->lastError ) {
				$this->lastError = $this->expr1->lastError;
				return; // error
			}

			if( $symbols[$s] == ')' ) {
				$s++;
			}
		}
		else if( $symbols[$s]{0} == '"' /* " indicates a function */ )
		{
			$this->expr1 = new EQL_PARSER_CLASS($symbols, $s, 0 /* don't consume operator */, $lastFunc);
			if( $this->expr1->lastError ) {
				$this->lastError = $this->expr1->lastError;
				return; // error
			}
		}
		else if( $symbols[$s]{0} == '$' /* $ indicates an ident */ )
		{
			$this->ident1 = substr($symbols[$s], 1);
			$s++;
		}
		else
		{
			if( $lastFunc == 'help' ) {
				$this->ident1 = '1';
				// let the error print later
			}
			else if( $lastFunc ) {
				$this->lastError = htmlconstant('_MOD_DBSEARCH_ERRFUNCARGEXP', strtolower($lastFunc) . '()');
				return; // error
			}
			else {
				$this->lastError = htmlconstant('_MOD_DBSEARCH_ERRIDENTIFIEREXP');
				return; // error
			}

		}

		// get operator (if any)...
		if( !$consumeOp ) {
			return;
		}

		if( $symbols[$s] == 'and'
		 || $symbols[$s] == 'or' )
		{
			$this->op = $symbols[$s];
			$s++;
		}
		else if( $symbols[$s] == '('
			  || $symbols[$s]{0} == '"'
			  || $symbols[$s]{0} == '$' )
		{
			$this->op = 'and';
		}

		// if we have any operator, get right part...
		if( $this->op )
		{
			$this->expr2 = new EQL_PARSER_CLASS($symbols, $s, 1 /* consume operator */, $lastFunc);
			if( $this->expr2->lastError ) {
				$this->lastError = $this->expr2->lastError;
				return; // error
			}
		}

		// optimize
		if( $this->expr1 && !$this->func1 && !$this->expr2 ) {
			$this->func1	= $this->expr1->func1;
			$this->ident1	= $this->expr1->ident1;
			$this->op		= $this->expr1->op;
			$this->expr2	= $this->expr1->expr2;
			$this->expr1	= $this->expr1->expr1; // copy expr1 at last
		}
	}



	//
	// EQL_PARSER_CLASS->htmldeentities()
	// -----------------------------------
	// removes entities from an HTML-formatted string
	//
	function htmldeentities($str)
	{
		global $transentities;

		if( !is_array($transentities) ) {
			$transentities = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT|ENT_HTML401, 'ISO-8859-1'));
		}

		return strtr($str, $transentities);
	}

	//
	// EQL_PARSER_CLASS->smartStrCompareFind()
	// ---------------------------------------
	// function makes a string easier comparable by removing some special
	// character.
	//
	// array contains the significant values at "even indices" and the strings
	// at the strings at "odd indices".
	//
	function smartStrCompareFind($str, $strArray, &$state /* either 'ok', 'notunique' or 'notfound' */)
	{
		// we try to ignore these characters
		$ignoreChars		= "+-*/\\.,:;?!\t\n\r";
		$ignoreCharsSubst	= str_repeat(' ', strlen($ignoreChars));

		// build test array
		$testArray = array();
		for( $try = 0; $try <= 2; $try++ )
		{
			for( $i = 0; $i < sizeof($strArray); $i += 2 )
			{
				$testArray[$i/2] = $strArray[$i+1];
				if( $try < 2 ) {
					$testArray[$i/2] = strtolower(trim($testArray[$i/2]));
					if( $try < 1 ) {
						$testArray[$i/2] = str_replace(' ', '', strtr($testArray[$i/2], $ignoreChars, $ignoreCharsSubst));
					}
				}
			}

			$temp = array_unique($testArray);
			if( sizeof($temp) == sizeof($testArray) )
			{
				// we can use this test array, make the same conversion for the string
				$test = $str;
				if( $try < 2 ) {
					$test = strtolower(trim($str));
					if( $try < 1 ) {
						$test = str_replace(' ', '', strtr($test, $ignoreChars, $ignoreCharsSubst));
					}
				}

				$testLen = strlen($test);

				// search for the value
				$valuesFound = 0;
				for( $i = 0; $i < sizeof($strArray); $i += 2 )
				{
					if( strcmp($test, $testArray[$i/2]) == 0 )
					{
						$state = 'ok';
						return $strArray[$i];
					}
				}

				$state = 'notfound';
				return 0;
			}
		}

		// error
		echo '<h1>ERROR: smartStrCompareFind(): The given value array is not unique!</h1>';
		print_r($strArray);
	}



	//
	// EQL_PARSER_CLASS->getArrayIdsCond()
	// -----------------------------------
	// function returns the SQL condition matching the IDs in the given array.
	//
	function getArrayIdsCond($field, &$ids)
	{
		if( sizeof($ids) == 0 ) {
			return '(0)';
		}
		else if( sizeof($ids) == 1 )
		{
			return "$field={$ids[0]}";
		}
		else
		{
			return "$field IN(" . implode(', ', $ids) . ')';
		}
	}



	//
	// EQL_PARSER_CLASS->getReadableAllowedFunc()
	// ------------------------------------------
	// function returns a string with the allowed functions for the table
	//
	function getReadableAllowedFunc($tableDefName)
	{
		$allowedFunc = 'id()';
		$tableDef = Table_Find_Def($tableDefName, 0 /* no access check */);
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) {
			$allowedFunc .= ', ' . g_eql_normalize_func_name($tableDef->rows[$r]->name, 0) . '()';
		}

		return $allowedFunc;
	}



	//
	// EQL_PARSER_CLASS->getRowInfo()
	// ------------------------------
	//
	function getRowInfo($tableDefName, $field)
	{
		$tableDef = Table_Find_Def($tableDefName, 0 /* no access check  */);
		$field = g_eql_normalize_func_name($field);
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
		{
			$row_name = g_eql_normalize_func_name($tableDef->rows[$r]->name);
			if( $row_name == $field ) {
				return $tableDef->rows[$r];
			}
		}

		return 0;
	}



	//
	// EQL_PARSER_CLASS->select2ids()
	// ------------------------------
	// invoke the given selection on the given table and return an array with
	// all the IDs. the returned array may be empty if no records were found.
	//
	function select2ids($table, $joins, $cond, $idField = 'id')
	{
		global $g_eql_db;
		global $debug;

		// ...build subselect select statement
		$currSelect = "SELECT ";
		if( sizeof($joins) ) {
			$currSelect .= "DISTINCT ";
		}
		$currSelect .= "$table.$idField FROM $table " . g_eql_array2string($joins) . " WHERE $cond";

		// ...collect all IDs
		$ids = array();

		if( $debug ) {
			$msneeded = g_eql_getmicrotime();
		}

				$g_eql_db->query($currSelect);
				while( $g_eql_db->next_record() ) {
					$currId = $g_eql_db->f($idField);
					$ids[] = $currId;
				}

		if( $debug ) {
			$msneeded = g_eql_getmicrotime() - $msneeded;
			g_debug_sql_out('EQL_PARSER_CLASS->select2ids():', $currSelect . ' => ' . (sizeof($ids)? implode(', ', $ids) : 'EMPTY ROWS') . ' (' . $msneeded . ' seconds needed)', 'yellow');
		}

		return $ids;
	}

	function ids2primaryselect($ids, &$retJoins, $tableDefName, $rowName, $rowType, $completeResolve = 1)
	{
		$rowType = $rowType & TABLE_ROW; // force masked row type

		if( sizeof($ids) == 0 )
		{
			// nothing found, add brackets around '0' to avoid a 'false' result
			return "(0)";
		}

		if( $rowType == TABLE_SATTR )
		{
			return $this->getArrayIdsCond("{$tableDefName}.{$rowName}", $ids);
		}
		else if( $completeResolve )
		{
			$cond1 = $this->getArrayIdsCond("{$tableDefName}_{$rowName}." .($rowType==TABLE_MATTR? 'attr':'secondary'). "_id", $ids);
			$ids = $this->select2ids("{$tableDefName}_{$rowName}", array(), $cond1, 'primary_id');
			return $this->getArrayIdsCond("$tableDefName.id", $ids);
		}
		else
		{
			$alias = g_addJoin($retJoins, "{$tableDefName}_{$rowName}", 'primary_id', "$tableDefName.id");
			return $this->getArrayIdsCond("$alias." .($rowType==TABLE_MATTR? 'attr':'secondary'). "_id", $ids);
		}
	}


	//
	// EQL_PARSER_CLASS->selectString()
	// --------------------------------
	// function returns the SQL condition for a string.
	//
	function selectString($tableDefName, $field, $ident, $modifiers, $addparam = 0)
	{
		if( $modifiers & EQL_OP_BITS )
		{
			$this->lastError = htmlconstant('_MOD_DBSEARCH_INVALIDSTRINGOP', g_eql_normalize_func_name($tableDefName, 0).".$field()");
			return 0;
		}

		$ident = addslashes($ident);
		if( $ident=='*' )
		{
			return '(1)';
		}
		else if( strpos($ident, '*') === false && strpos($ident, '?') === false )
		{
			if( $addparam ) {
				$rules = explode('###', $addparam);
				if( $ident != '' && sizeof($rules)>=2 ) {
					// perform given regular expressions on input
					for( $i = 3; $i < sizeof($rules); $i += 2 ) {
						$ident = preg_replace($rules[$i], $rules[$i+1], $ident);
					}
				}
			}
			
			return "$tableDefName.$field='$ident'";
		}
		else
		{
			$ident = strtr($ident, '*?', '%_');
			return "$tableDefName.$field LIKE '$ident'";
		}
	}


	//
	// EQL_PARSER_CLASS->selectInt()
	// -----------------------------
	// function returns the SQL condition for an integer.
	//
	function selectInt($tableDefName, $field, $ident, $modifiers)
	{
		// check for invalid modifiers
		if( $modifiers & (EQL_NEAR|EQL_LAZY|EQL_FUZZY)
		 || $ident{0} == '*'
		 || substr($ident, -1) == '*' )
		{
			$this->lastError = htmlconstant('_MOD_DBSEARCH_INVALIDINTOP', g_eql_normalize_func_name($tableDefName, 0).".$field()");
			return 0;
		}

		// check for range
		if( strpos($ident, '-')>=1  ) {
			list($mn, $mx) = explode('-', $ident);
			return '(' . $this->selectInt($tableDefName, $field, $mn, EQL_GREATEREQUAL) . ' AND ' . $this->selectInt($tableDefName, $field, $mx, EQL_LESSEQUAL) . ')';
		}

		// select int
		$op = '=';
			 if( $modifiers & EQL_LESS )		{ $op = '<';	}
		else if( $modifiers & EQL_LESSEQUAL )	{ $op = '<=';	}
		else if( $modifiers & EQL_GREATER )		{ $op = '>';	}
		else if( $modifiers & EQL_GREATEREQUAL ){ $op = '>=';	}

		return "$tableDefName.$field$op" . intval($ident);
	}



	//
	// EQL_PARSER_CLASS->selectFlag()
	// -----------------------------
	// function returns the SQL condition for a true/false flag.
	//
	function selectFlag($tableDefName, $field, $ident, $modifiers)
	{
		$ident = strtolower($ident);
		if( $ident=='no' || $ident=='off' || $ident=='false' || $ident=='0' ) {
			return "$tableDefName.$field=0";
		}
		else {
			return "$tableDefName.$field!=0";
		}
	}



	//
	// EQL_PARSER_CLASS->selectDate()
	// -----------------------------
	// function returns the SQL condition for a date or a timewindow.
	//
	// dates can be given as a single date or a span:
	//  	12.8.2002, 8/2002, 2003, 10.3.2003-21.04.2003, 1999-2001 etc.
	//
	// timewindows can be used as:
	//		timewindow([{-|+}]<value>[{m|d|y|h}][-[{-|+}]<value>[{m|d|y|h}]])
	//		default units are months (m), default scope is the past (-)
	// examples:
	//		0m		-	this month
	//		0m-1m	-	this month and the previous
	//		0m-+1m	-	this month and the next
	//		1m-1m	-	last month
	//		0y		-	this year
	//		1y-2y	-	two years ago
	//
	function selectDate($tableDefName, $field, $ident, $modifiers)
	{
		// load date functions
		global $site;
		require_once("{$site->sitePath}{$site->adminDir}/date.inc.php");

		// search for non-set date?
		if( $ident == '0' || $ident == '' ) {
			return "$tableDefName.$field='0000-00-00 00:00:00'";
		}

		// search for some special dates?
		$test = trim(strtolower($ident));
		if( $test=='today' || $test==strtolower(htmlconstant('_TODAY')) ) {
			$modifiers |= EQL_TIMEWINDOW; $ident = '0d';
		}
		else if( $test == 'yesterday' || $test==strtolower(htmlconstant('_YESTERDAY')) ) {
			$modifiers |= EQL_TIMEWINDOW; $ident = '1d-1d';
		}
		else if( $test == 'tomorrow' || $test==strtolower(htmlconstant('_TOMORROW')) ) {
			$modifiers |= EQL_TIMEWINDOW; $ident = '+1d-+1d';
		}

		if( $modifiers & EQL_TIMEWINDOW )
		{
			// search for a time-window
			$ident = trim($ident);

			$p = strpos(substr($ident, 1), '-');
			if( $p ) {
				$i1 = g_rel2absdate(substr($ident, 0, $p+1), $i1a, $i1b, $unit);
				$i2 = g_rel2absdate(substr($ident, $p+2), $i2a, $i2b, $unit);
			}
			else {
				$i1 = g_rel2absdate($ident, $i1a, $i1b, $unit);
				$i2 = g_rel2absdate("0$unit", $i2a, $i2b, $unit);
			}

			if( $i1 > $i2 ) {
				$dates[0] = $i2a;
				$dates[1] = $i1b;
			}
			else {
				$dates[0] = $i1a;
				$dates[1] = $i2b;
			}

			g_debug_out("EQL_PARSER_CLASS->selectDate(timewindow($ident)):", "exact time 1: $i1, slice 1: $i1a - $i1b<br />exact time 2: $i2, slice 2: $i2a - $i2b<br />slice calculated: <i>{$dates[0]} - {$dates[1]}</i>", 'yellow');

			$modifiers &= ~EQL_TIMEWINDOW;
		}
		else
		{
			// get date (span) from ident
			$dates = sql_date_from_human($ident, 'dateoptspan');
			if( sizeof($dates) == 0 ) {
				return '(0)'; // error
			}

			$dates[0] = substr($dates[0], 0, 10) . ' 00:00:00';
			$dates[1] = substr(sizeof($dates)==2? $dates[1] : $dates[0], 0, 10) . ' 23:59:59';
		}

		if( $modifiers & EQL_LESS )
		{
			return "$tableDefName.$field<'{$dates[0]}'";
		}
		else if( $modifiers & EQL_LESSEQUAL )
		{
			return "$tableDefName.$field<='{$dates[1]}'";
		}
		else if( $modifiers & EQL_GREATER )
		{
			return "$tableDefName.$field>'{$dates[1]}'";
		}
		else if( $modifiers & EQL_GREATEREQUAL )
		{
			return "$tableDefName.$field>='{$dates[0]}'";
		}
		else
		{
			return "($tableDefName.$field>='{$dates[0]}' AND $tableDefName.$field<='{$dates[1]}')";
		}
	}



	//
	// EQL_PARSER_CLASS->selectAnyDate()
	// ----------------------------------------
	// function returns the SQL condition for an enumeration field.
	//
	function selectAnyDate(&$retJoins, $tableDefName, $ident, $modifiers, $recursionDepth = 0)
	{
		global $g_eql_db;

		// recursion check
		if( $recursionDepth > 2 ) {
			return 0;
		}

		// get table definition
		$tableDef = Table_Find_Def($tableDefName, 0 /* no access check */);

		// check all fields
		$ret = '';
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
		{
			$rowflags	= $tableDef->rows[$r]->flags;
			$rowtype	= $rowflags & TABLE_ROW;
			$rowname	= $tableDef->rows[$r]->name;

			if( $rowtype == TABLE_DATE || $rowtype == TABLE_DATETIME )
			{
				$ret .= $ret? ' OR ' : '';
				$ret .= $this->selectDate($tableDefName, $rowname, $ident, $modifiers);
			}
			else if( $rowtype == TABLE_SECONDARY )
			{
				$tempTable = $tableDef->rows[$r]->addparam->name;
				$tempJoins = array();
				$tempCond = $this->selectAnyDate($tempJoins, $tempTable, $ident, $modifiers, $recursionDepth + 1);
				if( $tempCond )
				{
					global $g_joinHash;
					global $g_eql_prefer_joins;
					if( $g_eql_prefer_joins
					 && $g_joinHash[$tableDefName]  == 'self' // on root level?
					 && $g_joinHash[$tempTable] != 'alias' )
					{
						// this variation works, but is slower
						g_addRelation($retJoins, $tempTable, $rowname, $tableDefName, TABLE_SECONDARY);
						$ret .= $ret? ' OR ' : '';
						$ret .= "($tempCond)";
					}
					else
					{
						$tempIds = $this->select2ids($tempTable, $tempJoins, $tempCond);
						if( sizeof($tempIds) ) {
							$ret .= $ret? ' OR ' : '';
							$ret .= $this->ids2primaryselect($tempIds, $retJoins, $tableDefName, $rowname, $rowtype);
						}
					}
				}
			}
		}

		if( $ret == '' ) {
			$this->lastError = htmlconstant('_MOD_DBSEARCH_ERRUNKNOWNFUNCTION',
				'date()/timewindow()',
				g_eql_normalize_func_name($tableDefName, 0) . '()',
				$this->getReadableAllowedFunc($tableDefName));
		}

		return $ret;
	}


	//
	// EQL_PARSER_CLASS->selectEnumOrBitfield()
	// ----------------------------------------
	// function returns the SQL condition for an enumeration field.
	//
	function selectEnumOrBitfield($tableDefName, $field, $ident, $modifiers)
	{
		$intIdent = 'unset';

		$rowinfo = $this->getRowInfo($tableDefName, $field);

		$ident = trim($ident);
		if( $ident == '' )
		{
			$intIdent = 0;
		}
		else if( strval(intval($ident)) == strval($ident) )
		{
			$intIdent = intval($ident);
		}
		else
		{
			global $site;
			if( $rowinfo )
			{
				// remove entities from all values
				$values = explode('###', $rowinfo->addparam);
				for( $v = 0; $v < sizeof($values); $v+=2 ) {
					$values[$v+1] = $this->htmldeentities(htmlconstant(trim($values[$v+1])));
				}

				// find ident
				$intIdent = intval($this->smartStrCompareFind($ident, $values, $state));
				if( $state != 'ok' )
				{
					$allowedValues .= '0';
					for( $v = 0; $v < sizeof($values); $v+=2 ) {
						if ($values[$v+1] != '' ) { $allowedValues .= ", {$values[$v+1]}"; }
					}

					$this->lastError = htmlconstant('_MOD_DBSEARCH_ERRUNKNOWNVALUE',
						$ident,
						g_eql_normalize_func_name($tableDefName, 0) . ".{$field}()",
						isohtmlentities($allowedValues));
					return 0; // error
				}
			}
		}

		if( $intIdent === 'unset' )
		{
			return '(0)'; // nothing found, add brackets around '0' to avoid a 'false' result
		}
		else
		{
			if( ($rowinfo->flags & TABLE_ROW) == TABLE_BITFIELD )
			{
				if( $modifiers & EQL_OP_BITS )
				{
					$this->lastError = htmlconstant('_MOD_DBSEARCH_INVALIDBITFIELDOP', g_eql_normalize_func_name($tableDefName, 0).".$field()");
					return 0;
				}

				if( $intIdent == 0 ) {
					return "{$tableDefName}.{$field}=0";
				}
				else {
					return "{$tableDefName}.{$field}&" . $intIdent;
				}
			}
			else
			{
				return $this->selectInt($tableDefName, $field, $intIdent, $modifiers);
			}
		}
	}


	//
	// EQL_PARSER_CLASS->selectBlob()
	// -----------------------------
	// function returns the SQL condition for an blob.
	// we're searching in the name and in the mime.
	//
	function selectBlob($tableDefName, $field, $ident, $modifiers)
	{
		return	$this->selectString($tableDefName, $field, $ident==''? '' : ($ident.';*'), $modifiers);
	}



	//
	// EQL_PARSER_CLASS->selectFulltext()
	// ----------------------------------
	//
	function selectFulltextSingleWord(&$retJoins, $tableDefName, $words /*plus variations*/,
									  $modifiers, $recursionDepth = 0)
	{
		global $g_eql_db;
		global $g_ft_min_word_len;
		global $g_ft_boolean_syntax;

		//
		// recursion check
		//
		if( $recursionDepth > 2 ) {
			return 0;
		}

		//
		// get the fulltext fields (if any)
		//
		$fulltext_fields = '';
		$index = $g_eql_db->index_info($tableDefName);
		reset($index);
		while( list($index_name, $index_info) = each($index) )
		{
			if( $index_info['fulltext']  ) {
				for( $i = 0; $i < sizeof($index_info['fields']); $i++ ) {
					$fulltext_fields .= $fulltext_fields? ', ' : '';
					$fulltext_fields .= "$tableDefName." . $index_info['fields'][$i];
				}
				break;
			}
		}

		//
		// get the fallback fields
		//
		$like_fields = '';
		$tableDef = Table_Find_Def($tableDefName, 0 /* no access check */);
		$like_count = 0;
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
		{
			$rowflags	= $tableDef->rows[$r]->flags;
			$rowtype	= $rowflags & TABLE_ROW;
			$rowname	= $tableDef->rows[$r]->name;

			if( $rowtype == TABLE_TEXT || $rowtype == TABLE_TEXTAREA )
			{
				$like_fields .= $like_fields? ' OR ' : '';
				$like_fields .= "$tableDefName.$rowname %s";
				$like_count++;
			}
		}

		if( $like_count > 1 ) {
			$like_fields = "($like_fields)";
		}

		//
		// build the query string
		//
		$ret = '';
		$retCount = 0;
		if( $like_fields )
		{
			if( $fulltext_fields
			 && (strpos($words[0], '*')===false || strpos($words[0], '*')==(strlen($words[0])-1))
			 && strpos($words[0], '?')===false )
			{
				if( strlen(str_replace('*', '', $words[0])) >= $g_ft_min_word_len )
				{
					// query using fulltext index
					$temp = '';
					$tempBool = '';
					for( $v = 0; $v < sizeof($words); $v++ )
					{
						$temp .= $temp? ' ' : '';

						if( $modifiers&EQL_PHRASE
						 && strpos($words[$v], ' ')
						 && strpos($g_ft_boolean_syntax, '"') )
						{
							$temp .= '"' . addslashes($words[$v]) . '"';
							$tempBool = ' IN BOOLEAN MODE';
						}
						else if( substr($words[$v], -1) == '*'
							  && strpos($g_ft_boolean_syntax, '*') )
						{
							$temp .= addslashes($words[$v]);
							$tempBool = ' IN BOOLEAN MODE';
						}
						else
						{
							$temp .= addslashes($words[$v]);
						}
					}

					$ret .= "MATCH($fulltext_fields) AGAINST ('$temp'$tempBool)";
				}
			}
			else
			{
				// query using fallback
				$temp = '';
				for( $v = 0; $v < sizeof($words); $v++ )
				{
					$word = addslashes($words[$v]);

					if( !($modifiers & EQL_INUSERFUNC)
					 && $word{0}!='*'
					 && substr($word, -1)!='*' ) {
						$word = "*{$word}*";
					}

					if( strpos($word, '*')===false && strpos($word, '?')===false ) {
						$word = "='$word'";
					}
					else {
						$word = strtr($word, '*?', '%_');
						$word = "LIKE '$word'";
					}

					$temp .= $temp? ' OR ' : '';
					$temp .= str_replace('%s', $word, $like_fields);
				}

				if( sizeof($words) > 1 ) { $temp = "($temp)"; }
				$ret .= $temp;
			}

			$retCount++;
		}

		//
		// search for secondary tables
		//
		for( $r = 0; $r < sizeof($tableDef->rows); $r++ )
		{
			$rowflags	= $tableDef->rows[$r]->flags;
			$rowtype	= $rowflags & TABLE_ROW;
			$rowname	= $tableDef->rows[$r]->name;

			if( $rowtype == TABLE_SECONDARY || $rowname == 'localize_id' )
			{
				$tempTable = $tableDef->rows[$r]->addparam->name;
				$tempJoins = array();
				$tempCond = $this->selectFulltextSingleWord($tempJoins, $tempTable, $words, $modifiers, $recursionDepth + 1);
				if( $tempCond ) {
					global $g_joinHash;
					global $g_eql_prefer_joins;
					if( $g_eql_prefer_joins
					 && $g_joinHash[$tableDefName]  == 'self' // on root level?
					 && $g_joinHash[$tableDefName1] != 'alias' )
					{
						// this variation works, but is slower
						g_addRelation($retJoins, $tempTable, $rowname, $tableDefName, TABLE_SECONDARY);
						$ret .= $ret? ' OR ' : '';
						$ret .= $tempCond;
						$retCount++;
					}
					else
					{
						$tempIds = $this->select2ids($tempTable, $tempJoins, $tempCond);
						if( sizeof($tempIds) ) {
							$ret .= $ret? ' OR ' : '';
							$ret .= $this->ids2primaryselect($tempIds, $retJoins, $tableDefName, $rowname, $rowtype);
							$retCount++;
						}
					}
				}
			}
		}

		if( $retCount > 1 ) {
			$ret = "($ret)";
		}

		return $ret? $ret : 0;
	}
	function selectFulltext(&$retJoins, $tableDefName, $ident, $modifiers)
	{
		global $g_eql_db;
		global $g_fuzzyInfo;
		global $g_ft_fuzzymin;

		//
		// get fulltext param
		//
		global $g_ft_min_word_len;
		global $g_ft_boolean_syntax;
		if( !$g_ft_min_word_len )
		{
			$g_eql_db->query("SHOW VARIABLES LIKE 'ft_min_word_len'");
			$g_ft_min_word_len = $g_eql_db->next_record()? intval($g_eql_db->f('Value')) : 4;

			$g_eql_db->query("SHOW VARIABLES LIKE 'ft_boolean_syntax'");
			$g_ft_boolean_syntax = ' ' . ($g_eql_db->next_record()? $g_eql_db->f('Value') : '');
		}


		//
		// get the words
		//
		if( $modifiers&EQL_PHRASE )
		{
			$words = array(array($ident));
		}
		else
		{
			if( $modifiers&EQL_FUZZY ) {
				$use_fuzzy = $g_eql_db->table_exists('user_fuzzy');
			}
			else {
				$use_fuzzy = 0;
			}

			$words = array();
			$temp = g_eql_normalize_words($ident, 1 /*unique*/, 1 /*keepNumbers*/, 1 /*keepWildcards*/);
			if( sizeof($temp) == 0 ) $temp = array($ident);
			for( $i = 0; $i < sizeof($temp); $i++ )
			{
				$words[$i][0] = $temp[$i];
				if( $use_fuzzy
				 && strpos($temp[$i], '*')===false
				 && strpos($temp[$i], '?')===false
				 && strlen($temp[$i])>=$g_ft_min_word_len )
				{
					$g_eql_db->query("SELECT word FROM user_fuzzy WHERE soundex='".soundex($temp[$i])."' OR metaphone='".metaphone($temp[$i])."'");
					while( $g_eql_db->next_record() )
					{
						$similarword = $g_eql_db->f('word');
						if( $similarword != $temp[$i] ) {
							similar_text($similarword, $temp[$i], $percent);
							$percent = intval($percent);
							if( $percent >= $g_ft_fuzzymin ) {
								$words[$i][] = $similarword;
								$g_fuzzyInfo[$temp[$i]][str_pad($percent, 3, '0', STR_PAD_LEFT).$similarword] = array($similarword, $percent, 1);
							}
						}
					}
				}
			}
		}


		//
		// select!
		//
		$ret = '';
		$retCount = 0;
		for( $w = 0; $w < sizeof($words); $w++ )
		{
			$temp = $this->selectFulltextSingleWord($retJoins, $tableDefName, $words[$w], $modifiers);
			if( $temp ) {
				if( $ret ) {
					$ret .= $modifiers&EQL_ONEOF? ' OR ' : ' AND ';
				}
				$ret .= $temp;
				$retCount++;
			}
		}

		if( $retCount > 1 ) {
			return "($ret)";
		}
		else if( !$ret ) {
			return '(0)';
		}
		else {
			return $ret;
		}
	}


	//
	// EQL_PARSER_CLASS->selectJob()
	// -----------------------------
	//
	function selectJob($tableDefName, $ident, $modifiers)
	{
		// find job list to use
		$joblist = '@'; // init by error
		if( $ident == '1' ) {
			$joblist = $_SESSION['g_session_bin']->activeBin;
		}
		else {
			$soll = strtolower($ident);
			$bins = $_SESSION['g_session_bin']->getBins();
			for( $i = 0; $i < sizeof($bins); $i++ ) {
				if( strtolower($bins[$i]) == $soll ) {
					$joblist = $bins[$i];
					break;
				}
			}
		}

		// anything in jobist?
		$joblist = $_SESSION['g_session_bin']->getRecords($tableDefName, $joblist);
		if( sizeof($joblist) == 0 ) {
			return '(0)'; // nothing found
		}

		// get return statement
		$ret = '';
		reset($joblist);
		while( list($id, $state) = each($joblist) ) {
			$ret .= $ret? ', ' : "$tableDefName.id IN (";
			$ret .= $id;
		}
		$ret .= ')';

		return $ret; // done
	}


	//
	// EQL_PARSER_CLASS->selectSingleIdent()
	// -------------------------------------
	// returns the SQL statement or 0 for errors
	//
	function selectSingleIdent(&$retJoins, $tableDefName, $ident, $modifiers, $func)
	{
		if( $func == 'date' || ($func=='' && $modifiers&EQL_TIMEWINDOW) )
		{
			// select any date
			return $this->selectAnyDate($retJoins, $tableDefName, $ident, $modifiers);
		}
		else if( $func == 'id'
		 || ($func == '' && strval(intval($ident)) === strval($ident)) )
		{
			// select ID
			return $this->selectInt($tableDefName, 'id', $ident, $modifiers);
		}
		else if( $func == 'job' )
		{
			// select a job list
			return $this->selectJob($tableDefName, $ident, $modifiers);
		}
		else if( $func == 'rights' )
		{
			// select rights
			return $this->selectInt($tableDefName, 'user_access', $ident, $modifiers);
		}
		else if( $func == 'group' )
		{
			// select group
			if( strval(intval($ident)) == strval($ident) ) {
				$tempIds = array($ident);
			}
			else {
				$tempSql = $this->selectSingleIdent($tempJoins, 'user_grp', $ident, $modifiers, 'shortname');
				$tempIds = $this->select2ids('user_grp', $tempJoins, $tempSql);
				if( !sizeof($tempIds) ) {
					$tempSql = $this->selectSingleIdent($tempJoins, 'user_grp', $ident, $modifiers, 'name');
					$tempIds = $this->select2ids('user_grp', $tempJoins, $tempSql);
				}
			}

			return $this->ids2primaryselect($tempIds, $retJoins, $tableDefName, 'user_grp', TABLE_SATTR);
		}
		else if( $func == 'createdby' || $func == 'modifiedby' )
		{
			// select creator/modificator
			if( strval(intval($ident)) == strval($ident) ) {
				$tempIds = array($ident);
			}
			else {
				$tempSql = $this->selectSingleIdent($tempJoins, 'user', $ident, $modifiers, 'loginname');
				$tempIds = $this->select2ids('user', $tempJoins, $tempSql);
				if( !sizeof($tempIds) ) {
					$tempSql = $this->selectSingleIdent($tempJoins, 'user', $ident, $modifiers, 'name');
					$tempIds = $this->select2ids('user', $tempJoins, $tempSql);
				}
			}
			return $this->ids2primaryselect($tempIds, $retJoins, $tableDefName, $func=='createdby'? 'user_created' : 'user_modified', TABLE_SATTR);
		}
		else if( $func == 'created' || $func == 'modified' )
		{
			// select creation/modification date
			return $this->selectDate($tableDefName, $func=='created'? 'date_created' : 'date_modified', $ident, $modifiers);
		}
		else if( $func == '' )
		{
			// select fulltext
			return $this->selectFulltext($retJoins, $tableDefName, $ident, $modifiers);
		}
		else
		{
			// select other row
			$row = $this->getRowInfo($tableDefName, $func);
			if( $row ) {
				switch( $row->flags & TABLE_ROW )
				{
					case TABLE_TEXT:
					case TABLE_TEXTAREA:
						return $this->selectString($tableDefName, $row->name, $ident, $modifiers, $row->addparam);

					case TABLE_DATE:
					case TABLE_DATETIME:
						return $this->selectDate($tableDefName, $row->name, $ident, $modifiers);

					case TABLE_BLOB:
						return $this->selectBlob($tableDefName, $row->name, $ident, $modifiers);

					case TABLE_FLAG:
						return $this->selectFlag($tableDefName, $row->name, $ident, $modifiers);

					case TABLE_INT:
						return $this->selectInt($tableDefName, $row->name, $ident, $modifiers);

					case TABLE_BITFIELD:
						return $this->selectEnumOrBitfield($tableDefName, $row->name, $ident, $modifiers);

					case TABLE_ENUM:
						return $this->selectEnumOrBitfield($tableDefName, $row->name, $ident, $modifiers);
				}
			}

			// error
			$this->lastError = htmlconstant('_MOD_DBSEARCH_ERRUNKNOWNFUNCTION',
				strtolower($func).'()',
				g_eql_normalize_func_name($tableDefName, 0) . '()',
				$this->getReadableAllowedFunc($tableDefName));
			return 0;
		}
	}



	//
	// EQL_PARSER_CLASS->buildSqlString()
	// ----------------------------------
	// get condition and tables from expression.
	// recursive function.
	//
	function buildSqlString(&$retJoins, $tableDefName, $modifiers = -1, $func = '', $addparam = array())
	{
		//
		// init on root level
		// ------------------
		//
		if( $modifiers == -1 ) {
			$modifiers = 0;
		}

		//
		// LEFT PART: init param by given parameters (may be modified later)
		// -----------------------------------------------------------------
		//
		$tableDefName1	= $tableDefName;
		$modifiers1		= $modifiers;
		$func1			= $func;
		$rowType		= 0; // set if needed
		
		//
		// LEFT PART: get function modifiers and function stack 
		// ----------------------------------------------------
		//
		switch( $this->func1 )
		{
			case 'phrase':		$modifiers1 &= ~EQL_PHRASE_BITS;$modifiers1 |= EQL_PHRASE;				break;
			case 'near':		$modifiers1 &= ~EQL_PHRASE_BITS;$modifiers1 |= EQL_NEAR;				break;

			case 'exact':		$modifiers1 &= ~EQL_FUZZY_BITS;	$modifiers1 |= EQL_EXACT;				break;
			case 'lazy':		$modifiers1 &= ~EQL_FUZZY_BITS;	$modifiers1 |= EQL_LAZY;				break;
			case 'fuzzy':		$modifiers1 &= ~EQL_FUZZY_BITS;	$modifiers1 |= EQL_FUZZY;				break;

			case 'oneof':		$modifiers1 &= ~EQL_ONEALL_BITS;$modifiers1 |= EQL_ONEOF;				break;
			case 'allof':		$modifiers1 &= ~EQL_ONEALL_BITS;$modifiers1 |= EQL_ALLOF;				break;

			case 'less':		$modifiers1 &= ~EQL_OP_BITS;	$modifiers1 |= EQL_LESS;				break;
			case 'lessequal':	$modifiers1 &= ~EQL_OP_BITS;	$modifiers1 |= EQL_LESSEQUAL;			break;
			case 'greater':		$modifiers1 &= ~EQL_OP_BITS;	$modifiers1 |= EQL_GREATER;				break;
			case 'greaterequal':$modifiers1 &= ~EQL_OP_BITS;	$modifiers1 |= EQL_GREATEREQUAL;		break;
			case 'timewindow':									$modifiers1 |= EQL_TIMEWINDOW;			break;
			case 'text':																				break;

			case 'not':
				break; // handled later

			case '':
				break; // no function defined

			default:
				$row = $this->getRowInfo($tableDefName, $this->func1);
				$rowType = $row->flags & TABLE_ROW;
				if( $rowType == TABLE_SECONDARY
				 || $rowType == TABLE_MATTR
				 || $rowType == TABLE_SATTR )
				{
					$tableDefName1 = $row->addparam->name;
					$func1 = '';
					$modifiers1 |= EQL_INUSERFUNC;
					$addparam['inattr'] = array('xtable'=>$tableDefName, 'xrow'=>$row->name, 'xtype'=>$rowType);
				}
				else
				{
					$func1 = $this->func1;
				}
				break; // field-function
		}

		//
		// LEFT PART: init joins...
		// ------------------------
		//
		if( $tableDefName1 == $tableDefName
		 && $rowType != TABLE_MATTR
		 && $rowType != TABLE_SATTR )
		{
		 	// ...we're in the same table scope, use the same join array and copy it back later
			$joins1 = $retJoins;
		}
		else {
			// ...we're in a different table scope, no chance to use the same joins
			$joins1 = array();
		}

		//
		// LEFT PART: get condition...
		// ---------------------------
		//
		if( $this->expr1 )
		{
			// ...from expression (invokes recursion)
			$cond1 = $this->expr1->buildSqlString($joins1, $tableDefName1, $modifiers1, $func1, $addparam);
			if( !$cond1 ) {
				$this->lastError = $this->expr1->lastError;
				return ''; // error
			}
		}
		else
		{
			// ...from identifier
			if( $this->ident1 == '' && $modifiers1&EQL_PHRASE && $func1 == '' && is_array($addparam['inattr'])  )
			{
				$xtable = $addparam['inattr']['xtable']; 		// special handling for unset attribute tables ...
				$xrow   = $addparam['inattr']['xrow'];
				$xtype  = $addparam['inattr']['xtype'];
				if( $xtype == TABLE_SATTR ) {
					$cond1 = "({$xtable}.{$xrow}=0)";
				}
				else if( $xtype == TABLE_MATTR || $xtype == TABLE_SECONDARY ) {
					$xsql = "SELECT {$xtable}.id FROM {$xtable} LEFT JOIN {$xtable}_{$xrow} ON {$xtable}.id={$xtable}_{$xrow}.primary_id WHERE {$xtable}_{$xrow}.primary_id IS NULL";
					$cond1 = "({$xtable}.id IN ($xsql))";
				}				
			}
			else if( !($cond1=$this->selectSingleIdent($joins1, $tableDefName1, $this->ident1, $modifiers1, $func1)) )
			{
				return ''; // error, last error should be set
			}
		}

		//
		// LEFT PART: handle condition...
		// ------------------------------
		//
		if( $tableDefName1 == $tableDefName
		 && $rowType != TABLE_MATTR
		 && $rowType != TABLE_SATTR )
		{
			// ...we're in the same table scope, simply copy back the (maybe modified) joins
			$retJoins = $joins1;
		}
		else
		{
			// ...we're in a different table scope, convert secondary / attribute tables to primary table...
			global $g_joinHash;
			global $g_eql_prefer_joins;
			if( $g_eql_prefer_joins
			 && $g_joinHash[$tableDefName]  == 'self' // on root level?
			 && $g_joinHash[$tableDefName1] != 'alias'
			 && !isset($g_joinHash[$tableDefName1]) // EDIT BY ME in 2008: NO joins on AND conditions (this would require one additional join per table, see http://www.silverjuke.net/forum/post.php?p=8423#8423)
			 && sizeof($joins1)==0 // EDIT 2015: this code does not work eg. with "not(stichwort=7 and stichwort=5480)", the select2ids-code seems to work, hopefully, there are not other disadvantages
			 && count($retJoins)==0 // EDIT, jm, 2017: to allow search for courses without keywords
			 && $tableDefName1=="stichwoerter" // EDIT, jm, 2017: to allow search for courses without keywords
			 && $row->name=="stichwort" // EDIT, jm, 2017: to allow search for courses without keywords
			 && $tableDefName=="kurse") // EDIT, jm, 2017: to allow search for courses without keywords
			{
				// this variation works, but is slower
				g_addRelation($retJoins, $tableDefName1, $row->name, $tableDefName, $rowType);
			}
			else
			{
				$ids = $this->select2ids($tableDefName1, $joins1, $cond1);
				$cond1 = $this->ids2primaryselect($ids, $retJoins, $tableDefName, $row->name, $rowType);
			}
		}

		//
		// LEFT PART: not
		// --------------
		//
		if( $this->func1 == 'not' ) {
			$cond1 = "NOT($cond1)";
		}

		//
		// LEFT PART: if there is no right part, we're done with it!
		// ---------------------------------------------------------
		//
		if( !$this->expr2 ) {
			return $cond1;
		}

		//
		// RIGHT PART: get condition from expression
		// -----------------------------------------
		//
		$cond2 = $this->expr2->buildSqlString($retJoins, $tableDefName, $modifiers, $func);
		if( !$cond2 ) {
			$this->lastError = $this->expr2->lastError;
			return ''; // error
		}

		//
		// LEFT/RIGHT PART: use wanted operator
		// ------------------------------------
		//
		if( $this->op == 'or' || $modifiers & EQL_ONEOF ) {
			return "($cond1) OR ($cond2)";
		}
		else {
			return "($cond1) AND ($cond2)";
		}
	}



	//
	// EQL_PARSER_CLASS->getPCode()
	// ----------------------------
	// get the PCode for the object. the string must be empty on the first call.
	//
	function getPCode(&$pcode, $curlyBrackets = 0)
	{
		if( $curlyBrackets ) {
			$pcode .= ' { ';
		}

		if( $this->func1 ) {
			$pcode .= $this->func1;
			$pcode .= ' ( ';
		}

		if( $this->ident1 != '' ) {
			$pcode .= " [$this->ident1] ";
		}
		else if( $this->expr1 ) {
			$this->expr1->getPCode($pcode, $this->func1? 0 : 1);
		}

		if( $this->func1 ) {
			$pcode .= ' ) ';
		}

		if( $this->expr2 ) {
			$pcode .= " $this->op ";
			$this->expr2->getPCode($pcode, 1);
		}

		if( $curlyBrackets ) {
			$pcode .= ' } ';
		}
	}
}



class EQL2SQL_CLASS
{
	var $tableDefName;

	var $lastError;


	//
	// EQL2SQL_CLASS Constructor
	// -------------------------
	//
	function __construct($tableDefName, $usePlusMinusWordQualifiers = 0)
	{
		$this->tableDefName					= $tableDefName;
		$this->lastError					= '';
		$this->usePlusMinusWordQualifiers	= $usePlusMinusWordQualifiers;
	}



	//
	// EQL2SQL_CLASS->getPCode()
	// -------------------------
	// get the so called "PCode" or an EQL expression.
	//
	// The PCode is for information only and cannot be used in subsequent
	// queries.
	//
	// you can use the PCode to see how the EQL expression will be
	// interpreted.
	//
	// for errors, 0 is returned and $lastError ist set.
	//
	function getPCode($eqlExpr)
	{
		if( $eqlExpr == '' || $eqlExpr == '*' ) {
			return "[*]";
		}

		// convert tabs / linefeeds to spaces, enclose expression by spaces
		$eqlExpr = ' ' . strtr($eqlExpr, "\t\n\r", "   ") . ' ';

		// remove quoted identifiers from the string
		$this->quotedIdent = array();
		$eqlExpr = preg_replace_callback('/\"[^\"]*\"/', array(&$this, 'replaceQuotedIdent_'), $eqlExpr);
		$eqlExpr = str_replace('"', ' ', $eqlExpr);

		// replace symbols and enclose symbols by spaces
		$eqlExpr = strtr($eqlExpr, array(
			'(' 	=> ' ( ',
			')' 	=> ' ) '
		));

		$eqlExpr = strtr($eqlExpr, array(
			' AND ' => ' and ',
			' And ' => ' and ',
			' OR ' 	=> ' or ',
			' Or ' 	=> ' or ',
			' XOR ' => ' xor ',
			' XOr ' => ' xor ',
			' Xor ' => ' xor '
		));

		// match functions: single word identifier modifiers (not / exact) as -word or +word
		if( $this->usePlusMinusWordQualifiers ) {
			$eqlExpr = preg_replace('/\s+[\-]([^0-9\s][^\s]*)/', ' "not ( \1 ) ', $eqlExpr);
			$eqlExpr = preg_replace('/\s+[\+]([^\s]+)/', ' "exact ( \1 ) ', $eqlExpr);
		}

		// match functions: get fuzzymin()
		global $g_ft_fuzzymin;
		$g_ft_fuzzymin = 70;
		$temp1 = '/fuzzymin\s*=\s*([0-9]{2,3})/i';
		$temp2 = '/fuzzymin\s*\(\s*([0-9]{2,3})\s*\)/i';
		if( preg_match($temp1, $eqlExpr, $matches)
		 || preg_match($temp2, $eqlExpr, $matches) )
		{
			$g_ft_fuzzymin = intval($matches[1]);
			if( $g_ft_fuzzymin < 10  ) $g_ft_fuzzymin = 10;
			if( $g_ft_fuzzymin > 100 ) $g_ft_fuzzymin = 100;

			g_debug_out("EQL2SQL_CLASS->getPCode():",
				"fuzzymin set to $g_ft_fuzzymin", 'yellow');

			$eqlExpr = preg_replace($temp1, '', $eqlExpr);
			$eqlExpr = preg_replace($temp2, '', $eqlExpr);
		}

		// match functions: predefined / table defined functions
		$eqlExpr = preg_replace_callback('/([\.A-Za-z0-9_]{2,})\s*([<>=]{0,2})\s*(\(*)/', array(&$this, 'replaceFunc_'), $eqlExpr);

		// match functions: not
		$eqlExpr = strtr($eqlExpr, array(
			' NOT ' => ' "not ',
			' Not ' => ' "not ',
			' not ' => ' "not ',
		));

		// get the symbols
		$lastIsIdent = 0;
		$symbols = array();
		$eqlExpr = explode(' ', $eqlExpr);
		for( $e = 0; $e < sizeof($eqlExpr); $e++ )
		{
			switch( $eqlExpr[$e] )
			{
				case '':
					break; // skip empty parts

				case 'and':
				case 'or':
				case 'xor':
				case '(':
				case ')':
					$symbols[] = $eqlExpr[$e];
					$lastIsIdent = 0;
					break;

				default:
					if( $eqlExpr[$e]{0} == '"' )
					{
						// '"' indicates a function
						$symbols[] = $eqlExpr[$e];
						$lastIsIdent = 0;
					}
					else
					{
						// '$' indicates the search identifier
						if( substr($eqlExpr[$e], 0, 11) == 'QUOTEDIDENT' ) {
							$symbols[] = '"exact';
							$symbols[] = '"phrase';
							$symbols[] = '$' . $this->quotedIdent[intval(substr($eqlExpr[$e], 11))];
							$lastIsIdent = 0;
						}
						else {
							if( $lastIsIdent ) {
								$symbols[sizeof($symbols)-1] .= ' ' . $eqlExpr[$e];
							}
							else {
								$symbols[] = '$' . $eqlExpr[$e];
								$lastIsIdent = 1;
							}
						}
					}
					break;
			}
		}

		unset($this->quotedIdent);

		// create parser, parse symbols
		$s = 0;
		$this->parser = new EQL_PARSER_CLASS($symbols, $s);
		if( $this->parser->lastError ) {
			$this->lastError = $this->parser->lastError;
			return 0;
		}

		// done
		$pcode = '';
		$this->parser->getPCode($pcode);
		$pcode = preg_replace('/\s+/', ' ', trim($pcode));
		return $pcode;
	}



	//
	// EQL2SQL_CLASS->addTableToFields_()
	// ----------------------------------
	// adds missing tables to a field list,
	// eg. "id, name" becomes "main.id, main.name"
	//
	function addTableToFields_($inFields)
	{
		$outFields = '';
		$inFields = explode(',', $inFields);
		for( $i = 0; $i < sizeof($inFields); $i++ ) {
			$currInField = trim($inFields[$i]);
			if( $currInField )
			{
				if( $outFields ) {
					$outFields .= ', ';
				}

				if( strpos($currInField, '.') === false ) {
					$outFields .= "$this->tableDefName.$currInField";
				}
				else {
					$outFields .= $currInField;
				}
			}
		}
		return $outFields;
	}


	//
	// EQL2SQL_CLASS->correctOrderField_()
	// -----------------------------------
	// function tries to get a 'better' order field
	//
	function correctOrderField_($table, $field)
	{
		global $g_eql_db;
		if( $g_eql_db->column_exists($table, $field.'_sorted') ) {
			return $field.'_sorted';
		}
		else {
			return $field;
		}
	}

	//
	// EQL2SQL_CLASS->eql2sql()
	// ------------------------
	// create a SQL query string from the given expression. the SQL string
	// can be customized using $userFields, $userCond and $userOrderBy
	//
	// for errors, 0 is returned and $lastError ist set.
	//
	function eql2sql($eqlExpr, $userFields = 'id', $userCond = '', $userOrderBy = '')
	{
		g_debug_out('EQL2SQL_CLASS() and EQL2SQL_CLASS->eql2sql() arguments:',
			"tableDefName = &quot;$this->tableDefName&quot;, usePlusMinusWordQualifiers = $this->usePlusMinusWordQualifiers, eqlExpr = &quot;".isohtmlentities($eqlExpr)."&quot;, userFields = &quot;$userFields&quot;, userCond = &quot;".isohtmlentities($userCond)."&quot;, userOrderBy = &quot;$userOrderBy&quot;", 'blue');

		global $g_fuzzyInfo;
		$g_fuzzyInfo = array();

		global $g_joinHash;
		$g_joinHash = array($this->tableDefName => 'self');

		$joins = array();
		$distinct = 0;
		$addFields = '';
		if( $eqlExpr == '' || $eqlExpr == '*' || $eqlExpr == '(*)' )
		{
			// no expression given
			$cond = '';
		}
		else
		{
			// create parser from expression
			if( !($pcode=$this->getPCode($eqlExpr)) ) {
				g_debug_out(htmlconstant("EQL2SQL_CLASS->getPCode():"), $this->lastError, 'red');
				return 0;
			}

			g_debug_out(htmlconstant("EQL2SQL_CLASS->getPCode():"), "PCode: $pcode", 'yellow');

			// get SQL partly result strings
			$cond = $this->parser->buildSqlString($joins, $this->tableDefName);
			if( $this->parser->lastError || !$cond ) {
				$this->lastError = $this->parser->lastError;
				g_debug_out('EQL_PARSER_CLASS->buildSqlString():', $this->lastError, 'red');
				return 0;
			}

			// distinct selection? (may be replaced by GROUP BY later)
			if( sizeof($joins) ) {
				$distinct = 1;
			}
		}

		// apply order
		$tableDef = Table_Find_Def($this->tableDefName, 0 /*no access check*/);
		$allOrders = explode(',', $userOrderBy);
		$orderBy = '';
		$groupby = '';
		$fieldsApplied = array();
		for( $i = 0; $i < sizeof($allOrders); $i++ ) // go through all order statements
		{
			$currOrder = trim($allOrders[$i]);
			if( $currOrder )
			{
				// get order direction
				$currOrderDesc = '';
				if( strtolower(substr($currOrder, -5)) == ' desc' ) {
					$currOrder = substr($currOrder, 0, strlen($currOrder)-5);
					$currOrderDesc = ' DESC';
				}
				else if( strtolower(substr($currOrder, -4)) == ' desc' ) {
					$currOrder = substr($currOrder, 0, strlen($currOrder)-4);
				}

				if( !$fieldsApplied[$currOrder] )
				{
					$fieldsApplied[$currOrder] = 1;
					$currOrderApplied = 0;
					// is the current order statement complex?

					if( strpos($currOrder, '.')===false )
					{
						// no
						$orderBy .= $orderBy? ', ' : '';
						$orderBy .= "$this->tableDefName." . $this->correctOrderField_($this->tableDefName, $currOrder) . $currOrderDesc;
						$currOrderApplied = 1;
					}
					else
					{
						// yes: get table and field
						$temp = explode('.', $currOrder);
						if( sizeof($temp) == 2 )
						{
							// find the meaning of the linked table
							for( $r = 0; $r < sizeof($tableDef->rows); $r++ ) {
								$rowtype = $tableDef->rows[$r]->flags&TABLE_ROW;
								if( ($rowtype == TABLE_SECONDARY || $rowtype == TABLE_MATTR || $rowtype == TABLE_SATTR)
								 && $tableDef->rows[$r]->name == $temp[0] )
								{
									$alias = g_addRelation($joins, $tableDef->rows[$r]->addparam->name, $temp[0], $tableDef->name, $rowtype);

									if( $rowtype == TABLE_SATTR ) {
										$orderBy .= $orderBy? ', ' : '';
										$orderBy .= "{$alias}." . $this->correctOrderField_($tableDef->rows[$r]->addparam->name, $temp[1]) . $currOrderDesc;
									}
									else {
										$addFields .= ', ' . ($currOrderDesc? 'MAX':'MIN') . "({$alias}." .$this->correctOrderField_($tableDef->rows[$r]->addparam->name, $temp[1]). ") AS s$i";

										$groupBy = "{$this->tableDefName}.id";
										$distinct = 0;

										$orderBy .= $orderBy? ', ' : '';
										$orderBy .= "s{$i}{$currOrderDesc}";
									}

									$currOrderApplied = 1;
									break;
								}
							}
						}
					}

					if( !$currOrderApplied ) {
						g_debug_out('EQL_PARSER_CLASS->eql2sql():', "cannot apply order &quot;$currOrder&quot;", 'red');
					}
				}
			}
		}

		// finish SQL result...
		$retSql = $distinct? 'SELECT DISTINCT ' : 'SELECT ';
		$retSql .= $this->addTableToFields_($userFields) . "$addFields FROM $this->tableDefName " . g_eql_array2string($joins);

		// ...add condition
		if( $userCond )
		{
			$retSql .= " WHERE ";
			$userCond = trim($userCond);
			if( strtoupper(substr($userCond, 0, 2)) == 'OR'
			 || strtoupper(substr($userCond, 0, 3)) == 'AND' ) {
				$retSql .= $cond? "($cond) $userCond" : $userCond;
			}
			else {
				$retSql .= $cond? "($cond) AND ($userCond)" : $userCond;
			}
		}
		else if( $cond )
		{
			$retSql .= " WHERE $cond";
		}

		// ...add group/order information
		if( $groupBy ) {
			$retSql .= " GROUP BY $groupBy";
		}

		if( $orderBy ) {
			$retSql .= " ORDER BY $orderBy";
		}

		// debug
		global $debug;
		if( $debug )
		{
			g_debug_sql_out('EQL2SQL_CLASS->eql2sql() will return:', $retSql, 'green', 8);

			global $g_eql_db;
			$g_eql_db->query("EXPLAIN $retSql");
			echo '<tr>';
				echo '<td><b>table</b></td><td><b>type</b></td><td><b>possible_keys</b></td><td><b>key</b></td><td><b>key_len</b></td><td><b>ref</b></td><td><b>rows</b></td><td><b>Extra</b></td>';
			echo '</tr>';
			while( $g_eql_db->next_record() ) {
				echo '<tr>';
					echo '<td valign="top">' .$g_eql_db->f('table'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('type'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('possible_keys'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('key'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('key_len'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('ref'). '&nbsp;</td>';
					echo '<td valign="top">' .$g_eql_db->f('rows'). '&nbsp;</td>';
					echo '<td valign="top">';
						$extra = $g_eql_db->f('Extra');
						$extra = str_replace('Using filesort', '<b>Using filesort<b>', $extra);
						$extra = str_replace('Using temporary', '<b>Using temporary<b>', $extra);
						echo $extra;
					echo '&nbsp;</td>';
				echo '</tr>';
			}
			echo '<tr>';
				echo '<td colspan="8" align="right"><a href="http://dev.mysql.com/doc/refman/5.1/en/explain.html" target="_blank">give me more information about that...</a></td>';
			echo '</tr>';
			echo '</table>';
		}


		// done
		return $retSql;
	}

	function sqlCount($sql)
	{
		// modify query string: add COUNT(*)
		$select = /*substr($sql, 0, 15)=='SELECT DISTINCT'?*/ 'SELECT COUNT(DISTINCT ' /*: 'SELECT COUNT(DISTINCT '*/;
		$p = strpos($sql, ' FROM ');
		if( $p ) {
			$sql = "{$select}{$this->tableDefName}.id) AS cntall" . substr($sql, $p);
		}
		else {
			return 0;
		}

		if( preg_match('/^(.*?)((ORDER BY|GROUP BY)[a-zA-Z0-9\.,\s\(\)]*)$/', $sql, $matches) ) {
			$sql = $matches[1];
		}

		// modify query string: remove ORDER BY
		$p = $this->strrpos($sql, ' ORDER BY ');
		if( $p ) {
			$sql = substr($sql, 0, $p);
		}

		$p = $this->strrpos($sql, ' GROUP BY ');
		if( $p ) {
			$sql = substr($sql, 0, $p);
		}

		// get count
		global $g_eql_db;
		$msneeded = g_eql_getmicrotime();
		$g_eql_db->query($sql);
		$msneeded = g_eql_getmicrotime() - $msneeded;
		if( $g_eql_db->next_record() ) {
			$cntall = intval($g_eql_db->f('cntall'));
			//g_debug_sql_out('EQL2SQL_CLASS->sqlCount() will return:', $sql . " => ROW COUNT: $cntall ($msneeded seconds needed)", 'green');
			g_debug_sql_out('EQL2SQL_CLASS->sqlCount():', "$msneeded seconds needed", 'green');
			return $cntall;
		}
		else {
			g_debug_out('EQL2SQL_CLASS->sqlCount():', $sql, 'red');
			return 0;
		}
	}

	//
	// EQL2SQL_CLASS->strrpos()
	// ------------------------
	// same as strrpos() but a string is accepted as needle
	//
	function strrpos($haystack, $needle)
	{
		$p1 = false;
		while( 1 ) {
			$pnew = strpos($haystack, $needle, $p1===false? 0 : $p1+1);
			if( $pnew === false ) {
				return $p1;
			}
			else {
				$p1 = $pnew;
			}
		}
	}

	//
	// EQL2SQL_CLASS->replaceQuotedIdent_()
	// ------------------------------------
	//
	function replaceQuotedIdent_($matches)
	{
		$this->quotedIdent[] = strtr($matches[0], array('"'=>'', "''"=>'"'));
		return ' QUOTEDIDENT' . (sizeof($this->quotedIdent)-1) . ' ';
	}



	//
	// EQL2SQL_CLASS->replaceFunc_()
	// -----------------------------
	// replace functions callback, $matches contains:
	// $matches[0] - full match
	// $matches[1] - the function name
	// $matches[2] - the (optional) operator
	// $matches[3] - (optional) opening brackets
	//
	function replaceFunc_($matches)
	{
		if( ($matches[2] || $matches[3]) )
		{
			$funcName = strtolower($matches[1]);
			if( $funcName == 'and' || $funcName == 'or' || $funcName == 'xor' ) {
				return $matches[0];
			}

			$funcName = str_replace('.', ' "', $funcName);
			$funcName = '"' . $funcName;

			switch( $matches[2] )
			{
				case '>=':
				case '=>':	return " $funcName \"greaterequal {$matches[3]} ";

				case '<=':
				case '=<':	return " $funcName \"lessequal {$matches[3]} ";

				case '>':
				case '>>':	return " $funcName \"greater {$matches[3]} ";

				case '<':
				case '<<':	return " $funcName \"less {$matches[3]} ";

				case '<>':
				case '><':	return " $funcName \"not {$matches[3]} ";

				default:	return " $funcName {$matches[3]} ";
			}
		}

		return $matches[0];
	}

	function getInfo($addFuzzyInfo = 0)
	{
		$ret = '';

		// fuzzy info
		global $g_fuzzyInfo;
		if( $addFuzzyInfo && sizeof($g_fuzzyInfo) )
		{
			$ret .= '<table cellpadding="0" cellspacing="0" border="0">';
				ksort($g_fuzzyInfo);
				reset($g_fuzzyInfo);
				$c = 0;
				while( list($word, $info) = each($g_fuzzyInfo) ) {
					$ret .= '<tr>';
						$ret .= '<td align="right" valign="top" nowrap="nowrap">';
							$ret .= htmlconstant('_MOD_DBSEARCH_FUZZYINFOFOR');
							$ret .= ' &quot;' . isohtmlentities($word) . '&quot;&nbsp;';
						$ret .= '</td>';
						$ret .= '<td valign="top">';
							reset($info);
							krsort($info);
							$i = 0;
							while( list($dummy, $similarWord) = each($info) ) {
								$ret .= $i? ',' : htmlconstant('_MOD_DBSEARCH_FUZZYINFOALT');
								$ret .= ' &quot;' . isohtmlentities($similarWord[0]) . '&quot;';
								$percent = $similarWord[1] . '%';
								$ret .= ' <i>(' . ($c==0? htmlconstant('_MOD_DBSEARCH_FUZZYINFOPERCENT', $percent) : $percent) . ')</i>';
								$c++;
								$i++;
							}
						$ret .= '</td>';
					$ret .= '</tr>';
				}
			$ret .= '</table>';
		}

		return $ret;
	}
}




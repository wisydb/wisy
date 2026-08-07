<?php

/*=============================================================================
Export job lists ("Joblisten") as CSV or as printable HTML
===============================================================================

file:
	joblist_export.php

parameters:
	jobname	-	internal name of the job list to export
	all		-	if "1", all job lists of the current user are exported;
				"jobname" is ignored in this case
	format	-	"csv" (default) or "html"

remarks:
	- job lists are stored serialized in the field user.remembered,
	  see lib/g/bin.inc.php; the corresponding user interface is the section
	  "Joblisten" in settings.php
	- all strings are handled in ISO-8859-1 (as the whole CMS does) and are
	  converted to UTF-8 directly before they are written out; the CSV file
	  gets a BOM so that Excel detects UTF-8 without further questions
	- the access rights of the current user are checked on table level
	  (Table_Find_Def()) as well as on record level (Table_Find_Id(), regards
	  owner, group, sharing and the group filter); records without access are
	  exported with the state "kein Zugriff" and without any content instead of
	  being dropped silently - this way the export stays complete, which is
	  needed to discuss/migrate job lists offline
	- the CSV export also contains a line for job lists without any records,
	  otherwise empty job lists would vanish from the export completely

=============================================================================*/



// includes
require_once('functions.inc.php');
require_lang('lang/settings');

// larger job lists may need some time - as get_summary() does one query per field
@set_time_limit(600);



/*=============================================================================
Helper functions
=============================================================================*/



// convert a "html-ish" string (table descriptions, language constants, list
// names as returned by G_BIN_CLASS::getName()) to plain ISO-8859-1 text
function jle_label($str)
{
	$str = html_entity_decode(strval($str), ENT_QUOTES, 'ISO-8859-1');
	$str = strip_tags($str);

	return jle_value($str);
}



// normalize a plain ISO-8859-1 value as read from the database
function jle_value($str)
{
	$str = strtr(strval($str), array("\r\n"=>' ', "\r"=>' ', "\n"=>' ', "\t"=>' '));
	while( !(strpos($str, '  ')===false) ) { $str = str_replace('  ', ' ', $str); }

	return trim($str);
}



// ISO-8859-1 -> UTF-8 (used directly before writing out)
function jle_utf8($str)
{
	return mb_convert_encoding(strval($str), 'UTF-8', 'ISO-8859-1'); // utf8_encode() is deprecated since PHP 8.2
}



// escape a UTF-8 string for HTML output
function jle_html($str)
{
	return htmlspecialchars(jle_utf8($str), ENT_QUOTES, 'UTF-8');
}



// "ja"/"nein" for the flag columns of the CSV export
function jle_yesno($flag)
{
	return htmlconstant($flag? '_SETTINGS_BINEXPYES' : '_SETTINGS_BINEXPNO');
}



// "insgesamt n Datensaetze" (singular/plural)
function jle_total($count)
{
	$count = intval($count);

	return htmlconstant($count == 1? '_SETTINGS_BINEXPTOTAL1' : '_SETTINGS_BINEXPTOTAL', $count);
}



// format a SQL datetime for the export; "empty" dates result in an empty string
function jle_datetime($str)
{
	$str = trim(strval($str));

	if( $str == '' || substr($str, 0, 10) == '0000-00-00' ) {
		return '';
	}

	if( substr($str, -9) == ' 00:00:00' ) {
		return substr($str, 0, 10); // date only
	}

	return $str;
}



// build one CSV field; input is ISO-8859-1, output is UTF-8
function jle_csv_field($str)
{
	$str = jle_utf8($str);

	// avoid formula injection: spreadsheet applications evaluate cells starting
	// with these characters, also within quoted fields
	if( $str != '' && strpos('=+-@', substr($str, 0, 1)) !== false ) {
		$str = "'" . $str;
	}

	return '"' . str_replace('"', '""', $str) . '"';
}



// build one CSV line from an array of ISO-8859-1 values
function jle_csv_line($fields)
{
	$out = array();

	foreach( $fields as $field ) {
		$out[] = jle_csv_field($field);
	}

	return implode(';', $out) . "\r\n"; // CRLF: expected by Excel
}



// make an ISO-8859-1 string usable as part of a file name
function jle_filename_part($str)
{
	$str = jle_label($str);
	$str = strtr($str, array("\xE4"=>'ae', "\xF6"=>'oe', "\xFC"=>'ue', "\xC4"=>'Ae',
							 "\xD6"=>'Oe', "\xDC"=>'Ue', "\xDF"=>'ss'));
	$str = preg_replace('/[^A-Za-z0-9._-]+/', '_', $str);
	$str = preg_replace('/_{2,}/', '_', $str);
	$str = trim($str, '_.-');

	return $str == ''? 'Jobliste' : $str;
}



// absolute link to the edit mask of a record (the export is read offline, therefore relative links are of little use)
function jle_record_url($tableName, $id)
{
	global $site;

	if( defined('FORCE_SECURE_HOST') && FORCE_SECURE_HOST && defined('SECURE_HOST') && SECURE_HOST ) {
		$base = 'https://' . SECURE_HOST;
	}
	else if( isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ) {
		$base = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'];
	}
	else {
		$base = ''; // no host known - fall back to a relative link
	}

	$adminDir = isset($site) && isset($site->adminDir) && $site->adminDir? $site->adminDir : 'admin';

	return ($base? "$base/$adminDir/" : '') . 'edit.php?table=' . urlencode(strval($tableName)) . '&id=' . intval($id);
}



// position of a table within the global table definition (used for a stable ordering of the exported tables)
function jle_table_pos($tableName)
{
	global $Table_Def;

	for( $t = 0; $t < sizeof((array) $Table_Def); $t++ ) {
		if( $Table_Def[$t]->name == $tableName ) {
			return $t;
		}
	}

	return 9999; // unknown table - sort to the end
}



// read id, dates and last editor of the given records
// returns an array meta[<id>] - missing ids do not exist (any more)
function jle_read_meta($tableDef, $ids)
{
	$db  = new DB_Admin;
	$ret = array();

	// which of the optional columns does this table have?
	$columns = array('id');
	$exists  = array();
	$db->query('SHOW COLUMNS FROM ' . $tableDef->name);
	while( $db->next_record() ) {
		$exists[ strval($db->fs('Field')) ] = 1;
	}
	foreach( array('date_created', 'date_modified', 'user_modified') as $optional ) {
		if( isset($exists[$optional]) ) {
			$columns[] = $optional;
		}
	}

	// read the records in chunks (job lists may contain some thousand records)
	$chunk = array();
	foreach( $ids as $id ) {
		$chunk[] = intval($id);
		if( sizeof($chunk) >= 500 ) {
			jle_read_meta_chunk($db, $tableDef->name, $columns, $chunk, $ret);
			$chunk = array();
		}
	}
	if( sizeof($chunk) ) {
		jle_read_meta_chunk($db, $tableDef->name, $columns, $chunk, $ret);
	}

	return $ret;
}



function jle_read_meta_chunk(&$db, $tableName, $columns, $chunk, &$ret)
{
	$db->query('SELECT ' . implode(', ', $columns) . " FROM $tableName WHERE id IN (" . implode(',', $chunk) . ')');

	while( $db->next_record() )
	{
		$ret[ intval($db->f('id')) ] = array(
			'date_created'	=> jle_datetime($db->fs('date_created')),
			'date_modified'	=> jle_datetime($db->fs('date_modified')),
			'user_modified'	=> $db->f('user_modified') === null? '' : jle_label(user_ascii_name($db->f('user_modified')))
		);
	}
}



// name, owner and sharing state of one job list; these values are repeated in
// every CSV row and are also used for the overview of the HTML export
function jle_list_info($binName)
{
	$bin = $_SESSION['g_session_bin'];

	if( $bin->binIsExt($binName) ) {
		// list of another user, shared with the current user
		$owner  = jle_label($bin->getExtUserName($binName));
		$access = jle_label(htmlconstant('_SETTINGS_BINEXPSHARED'));
	}
	else {
		$owner  = jle_label(user_ascii_name(isset($_SESSION['g_session_userid'])? $_SESSION['g_session_userid'] : 0));
		$acc    = isset($bin->access[$binName]) && $bin->access[$binName]? $bin->access[$binName] : 'n';
		$access = jle_label(htmlconstant('_SETTINGS_BINALLOW' . strtoupper($acc)));
	}

	return array(
		'list'		=> jle_label($bin->getName($binName)),
		'listint'	=> jle_value($binName),
		'owner'		=> $owner,
		'access'	=> $access,
		'isdefault'	=> $binName == $bin->activeBin? 1 : 0
	);
}



// an "empty" row describing a job list without any records; without such a row
// empty job lists would not appear in the CSV export at all
function jle_empty_row($listInfo)
{
	return array_merge($listInfo, array(
		'table'			=> '',
		'tableint'		=> '',
		'id'			=> '',
		'name'			=> '',
		'details'		=> '',
		'state'			=> jle_label(htmlconstant('_SETTINGS_BINNORECORDS')),
		'date_created'	=> '',
		'date_modified'	=> '',
		'user_modified'	=> '',
		'url'			=> ''
	));
}



// collect all records of one job list; returns an array of rows, each row
// being an array with the keys used by the CSV/HTML renderers below
function jle_collect($binName)
{
	$bin = $_SESSION['g_session_bin'];
	$ret = array();

	$listInfo = jle_list_info($binName);

	// sort the tables of this list as defined in the table definition
	$entries = $bin->getRecords('', $binName);
	if( !is_array($entries) ) { $entries = array(); }

	$sortedTables = array();
	foreach( $entries as $tableName => $dummy ) {
		$sortedTables[ sprintf('%04d_%s', jle_table_pos($tableName), $tableName) ] = $tableName;
	}
	ksort($sortedTables);

	foreach( $sortedTables as $dummy => $tableName )
	{
		// collect and sort the ids of this table
		$ids = array();
		foreach( array_keys($entries[$tableName]) as $currId ) {
			$currId = intval($currId);
			if( $currId > 0 ) {
				$ids[$currId] = $currId;
			}
		}
		if( sizeof($ids) == 0 ) {
			continue;
		}
		ksort($ids, SORT_NUMERIC);

		// the table definition also checks the access rights of the current user
		$tableDef = Table_Find_Def($tableName);

		if( !$tableDef )
		{
			// no read access, or the table is not part of the table definition
			// (any more): list the ids nevertheless so that nothing gets lost
			// silently; the readable table name is read without the access
			// check as it is a static label only
			$plainDef   = Table_Find_Def($tableName, 0);
			$tableDescr = $plainDef? jle_label($plainDef->descr) : jle_value($tableName);
			$state      = $plainDef? htmlconstant('_SETTINGS_BINEXPSTATENOACCESS') : htmlconstant('_SETTINGS_BINEXPSTATEMISSING');

			foreach( $ids as $currId ) {
				$ret[] = array_merge($listInfo, array(
					'table'			=> $tableDescr,
					'tableint'		=> jle_value($tableName),
					'id'			=> $currId,
					'name'			=> '',
					'details'		=> '',
					'state'			=> jle_label($state),
					'date_created'	=> '',
					'date_modified'	=> '',
					'user_modified'	=> '',
					'url'			=> ''
				));
			}
			continue;
		}

		$meta = jle_read_meta($tableDef, $ids);

		foreach( $ids as $currId )
		{
			$row = array_merge($listInfo, array(
				'table'			=> jle_label($tableDef->descr),
				'tableint'		=> jle_value($tableDef->name),
				'id'			=> $currId,
				'name'			=> '',
				'details'		=> '',
				'state'			=> jle_label(htmlconstant('_SETTINGS_BINEXPSTATEMISSING')),
				'date_created'	=> '',
				'date_modified'	=> '',
				'user_modified'	=> '',
				'url'			=> ''
			));

			if( isset($meta[$currId]) )
			{
				// the record exists - check the rights on record level as well:
				// Table_Find_Id() additionally regards owner, group, sharing bits
				// and the group filter of the current user.  Job lists are kept
				// for a long time, so rights may have changed since a record was
				// remembered; without this check we would export data the user
				// is no longer allowed to see (this applies to shared job lists
				// of other users in particular)
				if( !Table_Find_Id($tableDef->name, $currId) )
				{
					$row['state'] = jle_label(htmlconstant('_SETTINGS_BINEXPSTATENOACCESS'));
				}
				else
				{
					$row['state']			= jle_label(htmlconstant('_SETTINGS_BINEXPSTATEOK'));
					$row['name']			= jle_value($tableDef->get_summary($currId, ''));				// the first summary field only, eg. the course title
					$row['details']			= jle_value($tableDef->get_summary($currId, ' | ', 1));			// all fields of the list view, eg. title, provider, dates
					$row['date_created']	= $meta[$currId]['date_created'];
					$row['date_modified']	= $meta[$currId]['date_modified'];
					$row['user_modified']	= $meta[$currId]['user_modified'];
					$row['url']				= jle_record_url($tableDef->name, $currId);
				}
			}

			$ret[] = $row;
		}
	}

	return $ret;
}



// render a minimal error page (no download has been started at this point)
function jle_error($msgHtml)
{
	header('Content-Type: text/html; charset=iso-8859-1');

	echo "<!DOCTYPE html>\n";
	echo "<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=iso-8859-1\">";
	echo '<title>' . htmlconstant('_SETTINGS_BINEXPTITLE') . '</title></head>';
	echo '<body style="font-family: sans-serif; font-size: 13px;">';
	echo '<h1 style="font-size: 15px;">' . htmlconstant('_SETTINGS_BINEXPTITLE') . '</h1>';
	echo '<p>' . $msgHtml . '</p>';
	echo '</body></html>';

	exit();
}



/*=============================================================================
Get the parameters and collect the data
=============================================================================*/



$bin = isset($_SESSION['g_session_bin']) && is_object($_SESSION['g_session_bin'])? $_SESSION['g_session_bin'] : null;

$exportAll	= isset($_REQUEST['all'])? intval($_REQUEST['all']) : 0;
$jobname	= isset($_REQUEST['jobname'])? strval($_REQUEST['jobname']) : '';
$format		= isset($_REQUEST['format'])? strtolower(strval($_REQUEST['format'])) : 'csv';

if( $format != 'html' ) {
	$format = 'csv';
}

if( !$bin ) {
	jle_error(htmlconstant('_SETTINGS_BINNORECORDS'));
}

if( $exportAll ) {
	$binNames = $bin->getBins();
	$exportName = htmlconstant('_SETTINGS_BINEXPALLLISTS');
}
else if( $jobname != '' && $bin->binExists($jobname) ) {
	$binNames = array($jobname);
	$exportName = $bin->getName($jobname);
}
else {
	jle_error(htmlconstant('_SETTINGS_BINERRLISTNOTFOUND', isohtmlentities($jobname)));
}

// collect the rows, grouped by job list
$groups = array();
$totalCount = 0;

foreach( (array) $binNames as $currName )
{
	$listInfo = jle_list_info($currName);
	$rows     = jle_collect($currName);

	$groups[] = array(
		'info'	=> $listInfo,	// list, listint, owner, access, isdefault
		'rows'	=> $rows
	);

	$totalCount += sizeof($rows);
}

// file name: "Jobliste_<name>_<date>" resp. "Joblisten_alle_<date>"
$fileName = ($exportAll? 'Joblisten_alle' : 'Jobliste_' . jle_filename_part($exportName)) . '_' . date('Y-m-d_H-i');

$exportedBy = htmlconstant('_SETTINGS_BINEXPCREATEDBY',
					isohtmlentities(user_ascii_name(isset($_SESSION['g_session_userid'])? $_SESSION['g_session_userid'] : 0)),
					date('d.m.Y H:i'));



/*=============================================================================
Output: CSV
=============================================================================*/



if( $format == 'csv' )
{
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');

	echo "\xEF\xBB\xBF"; // BOM, so that Excel detects UTF-8

	echo jle_csv_line(array(
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLLIST')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLLISTINT')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLOWNER')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLACCESS')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLDEFAULT')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLTABLE')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLTABLEINT')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLID')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLNAME')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLDETAILS')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLSTATE')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLCREATED')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLMODIFIED')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLMODIFIEDBY')),
		jle_label(htmlconstant('_SETTINGS_BINEXPCOLLINK'))
	));

	foreach( $groups as $group )
	{
		// job lists without records get a single line of their own - otherwise
		// they would be missing in the export completely (the HTML export
		// renders them as an own section anyway)
		$rows = sizeof($group['rows'])? $group['rows'] : array(jle_empty_row($group['info']));

		foreach( $rows as $row )
		{
			echo jle_csv_line(array(
				$row['list'],
				$row['listint'],
				$row['owner'],
				$row['access'],
				jle_label(jle_yesno($row['isdefault'])),
				$row['table'],
				$row['tableint'],
				$row['id'],
				$row['name'],
				$row['details'],
				$row['state'],
				$row['date_created'],
				$row['date_modified'],
				$row['user_modified'],
				$row['url']
			));
		}
	}

	exit();
}



/*=============================================================================
Output: HTML
=============================================================================*/



header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $fileName . '.html"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$title = jle_label(htmlconstant('_SETTINGS_BINEXPTITLE')) . ': ' . jle_label($exportName);

echo "<!DOCTYPE html>\n";
echo '<html lang="' . (isset($_SESSION['g_session_language'])? jle_html($_SESSION['g_session_language']) : 'de') . "\">\n";
echo "<head>\n";
	echo '<meta charset="utf-8">' . "\n";
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
	echo '<title>' . jle_html($title) . "</title>\n";
	echo "<style>\n";
		echo "body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #222; margin: 20px; }\n";
		echo "h1 { font-size: 19px; margin: 0 0 4px 0; }\n";
		echo "h2 { font-size: 16px; margin: 26px 0 2px 0; border-bottom: 2px solid #888; padding-bottom: 2px; }\n";
		echo "h3 { font-size: 14px; margin: 16px 0 2px 0; color: #444; }\n";
		echo "p.meta { color: #666; margin: 0 0 18px 0; }\n";
		echo "p.hint { color: #666; }\n";
		echo "table { border-collapse: collapse; width: 100%; margin-top: 4px; }\n";
		echo "th, td { border: 1px solid #bbb; padding: 3px 5px; text-align: left; vertical-align: top; font-size: 12px; }\n";
		echo "th { background: #eee; }\n";
		echo "tr:nth-child(even) td { background: #f8f8f8; }\n";
		echo "td.id { text-align: right; white-space: nowrap; }\n";
		echo "td.nowrap { white-space: nowrap; }\n";
		echo "td.missing { color: #a00; }\n";
		echo "a { color: #06c; }\n";
		echo "@media print {\n";
			echo "  body { margin: 0; font-size: 11px; }\n";
			echo "  h2 { page-break-after: avoid; }\n";
			echo "  h3 { page-break-after: avoid; }\n";
			echo "  tr { page-break-inside: avoid; }\n";
			echo "  th, td { font-size: 10px; }\n";
			echo "  a { color: #222; text-decoration: none; }\n";
		echo "}\n";
	echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

	echo '<h1>' . jle_html($title) . "</h1>\n";
	echo '<p class="meta">' . jle_html(jle_label($exportedBy));
	echo ' &ndash; ' . jle_html(jle_label(jle_total($totalCount)));
	echo "</p>\n";

	// overview of all exported lists
	if( sizeof($groups) > 1 )
	{
		echo '<h2>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPOVERVIEW'))) . "</h2>\n";
		echo "<table>\n";
			echo '<tr><th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLLIST'))) . '</th>';
			echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLLISTINT'))) . '</th>';
			echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLOWNER'))) . '</th>';
			echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLACCESS'))) . '</th>';
			echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLCOUNT'))) . "</th></tr>\n";

			foreach( $groups as $group )
			{
				echo '<tr><td>' . jle_html($group['info']['list']);
				if( $group['info']['isdefault'] ) {
					echo ' ' . jle_html(jle_label(htmlconstant('_SETTINGS_BINISACTIVEBIN')));
				}
				echo '</td>';
				echo '<td>' . jle_html($group['info']['listint']) . '</td>';
				echo '<td>' . jle_html($group['info']['owner']) . '</td>';
				echo '<td>' . jle_html($group['info']['access']) . '</td>';
				echo '<td class="id">' . intval(sizeof($group['rows'])) . "</td></tr>\n";
			}
		echo "</table>\n";
	}

	// the lists themselves, grouped by table
	foreach( $groups as $group )
	{
		echo '<h2>' . jle_html($group['info']['list']);
		if( $group['info']['isdefault'] ) {
			echo ' ' . jle_html(jle_label(htmlconstant('_SETTINGS_BINISACTIVEBIN')));
		}
		echo "</h2>\n";
		echo '<p class="meta">' . jle_html($group['info']['owner']) . ' &ndash; ' . jle_html($group['info']['access']);
		echo ' &ndash; ' . jle_html(jle_label(jle_total(sizeof($group['rows'])))) . "</p>\n";

		if( sizeof($group['rows']) == 0 )
		{
			echo '<p class="hint">' . jle_html(jle_label(htmlconstant('_SETTINGS_BINNORECORDS'))) . "</p>\n";
			continue;
		}

		// count the records per table beforehand (the rows are already grouped
		// by table, but a single pass keeps this cheap for large job lists)
		$tableCounts = array();
		foreach( $group['rows'] as $row ) {
			$key = $row['tableint'];
			$tableCounts[$key] = isset($tableCounts[$key])? $tableCounts[$key] + 1 : 1;
		}

		$currTable = null;
		$tableOpen = 0;

		foreach( $group['rows'] as $row )
		{
			if( $row['tableint'] !== $currTable )
			{
				if( $tableOpen ) { echo "</table>\n"; $tableOpen = 0; }
				$currTable = $row['tableint'];

				echo '<h3>' . jle_html($row['table']) . ' (' . intval($tableCounts[$currTable]) . ")</h3>\n";
				echo "<table>\n";
					echo '<tr><th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLID'))) . '</th>';
					echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLNAME'))) . '</th>';
					echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLDETAILS'))) . '</th>';
					echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLSTATE'))) . '</th>';
					echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLMODIFIED'))) . '</th>';
					echo '<th>' . jle_html(jle_label(htmlconstant('_SETTINGS_BINEXPCOLMODIFIEDBY'))) . "</th></tr>\n";
				$tableOpen = 1;
			}

			echo '<tr>';
				echo '<td class="id">';
					if( $row['url'] ) {
						echo '<a href="' . jle_html($row['url']) . '" target="_blank" rel="noopener noreferrer">' . intval($row['id']) . '</a>';
					}
					else {
						echo intval($row['id']);
					}
				echo '</td>';
				echo '<td>' . jle_html($row['name']) . '</td>';
				echo '<td>' . jle_html($row['details']) . '</td>';
				echo '<td class="nowrap' . ($row['url']? '' : ' missing') . '">' . jle_html($row['state']) . '</td>';
				echo '<td class="nowrap">' . jle_html($row['date_modified']) . '</td>';
				echo '<td>' . jle_html($row['user_modified']) . '</td>';
			echo "</tr>\n";
		}

		if( $tableOpen ) { echo "</table>\n"; }
	}

echo "</body></html>\n";

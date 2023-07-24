<?php


/*=============================================================================
table overview, also used as an attribute selector
===============================================================================

file:	
	index.php
	
author:	
	Bjoern Petersen

parameters to call from extern as a simple list:
	table			- the table to list
	justedited		- optional, the ID of the record just saved or canceled, 
					  used in addition with the hash #id1234

additional parameters when calling as attribute selector:
	selectobject	- we should select an object for the editor

internal parameters for subsequent calls:
	orderby			- set the order
	searchoffset	- the offset for result output
	rows			- the number of rows to show on result output
	...

=============================================================================*/



/*=============================================================================
Functions
=============================================================================*/

//Decide WISY or WISY@KI
function select_WisyKi()
{
	$wisyPortalEinstellungen = null;
	$db = new DB_Admin;
	$ist_domain = strtolower($_SERVER['HTTP_HOST']);
	if (substr($ist_domain, 0, 7) == 'wisyisy') {
		$ist_domain = substr($ist_domain, 7 + 1);
	}
	// find all matching domains with status = "1" - in this case 404 on purpose (mainly for SEO)
	$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '%" . addslashes(str_replace('www.', '', $ist_domain)) . "%';";
	$db->query($sql);
	if ($db->next_record()) {
		$wisyPortalEinstellungen = explodeSettings($db->fs('einstellungen'));
	} else {
		error404();
	}
	if (strval($wisyPortalEinstellungen['wisyki'] != '')) {
		$GLOBALS['WisyKi'] = true;
	}
}

function createColumnsHash($table, $prefix = '')
{
	global $columnsToShow;
	global $columnsHash;
	global $show_summary;
	global $show_secondary;
	global $sqlFields;

	$columnsCount		= 0;
	$hasSecondary		= 0;
	$secondaryIsDefault	= 0;

	if ($prefix == '') {
		// ID/bin/view column
		$columnsCount++;
		$columnsHash[] = 1;
	}

	// "direct" columns
	$tableDef = Table_Find_Def($table);
	for ($r = 0; $r < sizeof((array) $tableDef->rows); $r++) {
		$rowflags		= intval($tableDef->rows[$r]->flags);
		$rowtype		= $rowflags & TABLE_ROW;
		$defaultColumn	= $rowflags & TABLE_LIST ? 1 : 0;

		if ($rowtype == TABLE_SECONDARY) {
			$hasSecondary		= 1;
			$secondaryIsDefault	= $secondaryIsDefault ? 1 : $defaultColumn;

			$secondaryColumnsCount = createColumnsHash(
				$tableDef->rows[$r]->addparam->name,
				$tableDef->rows[$r]->addparam->name . '.'
			);
			if ($secondaryColumnsCount) {
				$columnsCount += $secondaryColumnsCount;
				$show_secondary[$tableDef->rows[$r]->addparam->name] = 1;
			}
		} else if ((!$columnsToShow && $defaultColumn && $prefix == '')
			|| ($columnsToShow && in_array($prefix . $tableDef->rows[$r]->name, $columnsToShow))
		) {
			$columnsCount++;
			$columnsHash[] = 1;

			if ($prefix == '') {
				if ($rowtype != TABLE_MATTR) {
					$sqlFields .= ', ' . $tableDef->rows[$r]->name;
				}
			}
		} else {
			$columnsHash[] = 0;
		}
	}

	if ($prefix == '') {
		// secondary summary column
		$columnsHash[] = 0;
		if ($hasSecondary) {
			if ((!$columnsToShow && $secondaryIsDefault)
				|| ($columnsToShow && in_array('SUMMARY', $columnsToShow))
			) {
				$columnsCount++;
				$columnsHash[sizeof((array) $columnsHash) - 1] = 1;
				$show_summary = 1;
			}
		}

		// "rights" columns		
		$test = array(
			'date_created', 'date_created',
			'user_created', 'user_created',
			'date_modified', 'date_modified',
			'user_modified', 'user_modified',
			'user_grp',		'user_grp',
			'user_access', 	'user_access',
			'REFERENCES',	''
		);

		for ($r = 0; $r < sizeof($test); $r += 2) {
			if ($columnsToShow && in_array($test[$r], $columnsToShow)) {
				$columnsCount++;
				$columnsHash[] = 1;
				if ($test[$r + 1]) {
					$sqlFields .= ', ' . $test[$r + 1];
				}
			} else {
				$columnsHash[] = 0;
			}
		}
	}

	// done
	return $columnsCount;
}

function renderTableHeadCell($curr_field, $descr, $def_desc = 0, $sum_field = 0)
{

	global $site;
	global $columnsCount;
	global $tableHeadCellsRendered;
	global $tableDef;
	global $orderby;
	global $baseurl;
	global $Table_Shortnames;

	if (!isset($tableHeadCellsRendered))
		$tableHeadCellsRendered = 0;

	// start head cell
	$site->skin->cellStart();

	// prepare for columns settings
	$settingsOut = 0;
	if ($tableHeadCellsRendered == $columnsCount - 1 && !isset($_REQUEST['object'])) {
		echo '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="left" nowrap="nowrap" class="hdnested">';
		$settingsOut = 1;
	}

	// get order by information
	$orderby_sort = ''; // == ASC
	if (strpos($orderby, 'DESC')) {
		$orderby_sort = 'DESC';
	}

	$orderby_field = $orderby;
	$orderby_field = str_replace('ASC', '', $orderby_field);
	$orderby_field = str_replace('DESC', '', $orderby_field);
	$orderby_field = trim($orderby_field);

	// get arrowImage'n'action
	$img = '';
	$action = '';
	if ($curr_field) {
		$action = $baseurl;
		if ($curr_field == $orderby_field) {
			if ($orderby_sort == 'DESC') {
				$img = 'sortdesc';
				$action .= '&orderby=' . $curr_field;
			} else {
				$img = 'sortasc';
				$action .= '&orderby=' . $curr_field . '+DESC';
			}
			$title = htmlconstant('_OVERVIEW_SORTTOGGLE');
		} else {
			if ($def_desc) {
				$action .= '&orderby=' . $curr_field . '+DESC';
			} else {
				$action .= '&orderby=' . $curr_field;
			}
			$title = htmlconstant('_OVERVIEW_SORT');
		}
	}

	// render action'n'description
	if ($action) {
		echo '<a href="' . isohtmlentities(strval($action)) . "\" title=\"$title\">";
	}

	$descr = trim($descr);
	if (isset($Table_Shortnames[$descr]) && $Table_Shortnames[$descr]) {
		echo $Table_Shortnames[$descr];
	} else {
		echo $descr;
	}

	if ($action) {
		if ($img) {
			echo $img == 'sortdesc' ? " {$site->skin->ti_sortdesc}" : " {$site->skin->ti_sortasc}";
		}
		echo '</a>';
	}

	if ($sum_field) {
		$dbsum = new DB_Admin;
		$dbsum->query("SELECT SUM($curr_field) AS alltogether FROM {$tableDef->name};");
		$dbsum->next_record();
		echo ' &sum;' . intval($dbsum->f('alltogether'));
	}

	// columns settings out
	if ($settingsOut) {
		echo '&nbsp;</td><td align="right" nowrap="nowrap">';
		$href = "index_listattr.php?table={$tableDef->name}&amp;scope=columns";
		echo "<a href=\"$href\" target=\"dbsearch_opt\" onclick=\"return popup(this,260,500);\" title=\"" . htmlconstant('_OVERVIEW_EDITCOLUMNS___') . "\">|||</a>";
		echo '</td></tr></table>';
	}

	// end head cell
	$site->skin->cellEnd();
	$tableHeadCellsRendered++;
}

function getSortField($tableDef)
{
	for ($r = 0; $r < sizeof((array) $tableDef->rows); $r++) {
		$rowflags	= intval($tableDef->rows[$r]->flags);
		$rowtype	= $rowflags & TABLE_ROW;
		if (($rowflags & TABLE_LIST || $rowflags & TABLE_SUMMARY)
			&& $rowtype != TABLE_MATTR
			&& $rowtype != TABLE_SATTR
			&& $rowtype != TABLE_SECONDARY
		) {
			return $tableDef->rows[$r]->name;
		}
	}
	return 'id'; // nothing else found
}

function renderTableHead(&$hi, $table, $prefix = '')
{
	global $columnsHash;
	global $site;

	if ($prefix == '') {
		// select column (this column is not counted)
		if (isset($_REQUEST['object'])) {
			$site->skin->cellStart();
			echo htmlconstant('_SELECT');
			$site->skin->cellEnd();
		}

		// ID / BIN / VIEW column	
		if ($columnsHash[$hi++]) {
			renderTableHeadCell('id', htmlconstant('_ID'), 1);
		}
	}

	// "direct" columns
	$tableDef = Table_Find_Def($table);
	for ($r = 0; $r < sizeof((array) $tableDef->rows); $r++) {
		$rowflags	= intval($tableDef->rows[$r]->flags);
		$rowtype	= $rowflags & TABLE_ROW;

		if ($rowtype == TABLE_SECONDARY) {
			renderTableHead(
				$hi,
				$tableDef->rows[$r]->addparam->name,
				$tableDef->rows[$r]->name . '.'
			);
		} else {
			if ($columnsHash[$hi++]) {
				// find sort criteria
				if ($prefix == '') {
					switch ($rowtype) {
						case TABLE_MATTR:
						case TABLE_SATTR:
							$curr_field = $tableDef->rows[$r]->name . '.' . getSortField($tableDef->rows[$r]->addparam);
							break;

						default:
							$curr_field = $tableDef->rows[$r]->name;
							break;
					}
				} else {
					switch ($rowtype) {
						case TABLE_MATTR:
						case TABLE_SATTR:
							$curr_field = '';
							break;

						default:
							$curr_field = $prefix . $tableDef->rows[$r]->name;
							break;
					}
				}

				renderTableHeadCell(
					$curr_field,
					$tableDef->rows[$r]->descr,
					($rowtype == TABLE_DATE || $rowtype == TABLE_DATETIME) ? 1 : 0,
					($rowtype == TABLE_INT && ($rowflags & TABLE_SUM)) ? 1 : 0
				);
			}
		}
	}

	if ($prefix == '') {
		// secondary summary column
		if ($columnsHash[$hi++]) {
			renderTableHeadCell('', htmlconstant('_SUMMARY'));
		}

		// "rights" columns
		$test = array(
			'date_created',		'_OVERVIEW_CREATED',	'_OVERVIEW_CREATED',	1,
			'user_created', 	'_OVERVIEW_CREATEDBY',	'_OVERVIEW_BY',			0,
			'date_modified', 	'_OVERVIEW_MODIFIED',	'_OVERVIEW_MODIFIED',	1,
			'user_modified',	'_OVERVIEW_MODIFIEDBY',	'_OVERVIEW_BY',			0,
			'user_grp',			'_GROUP',				'_GROUP',				0,
			'user_access', 		'_RIGHTS',				'_RIGHTS',				0,
			'',					'_REFABBR',				'_REFABBR',				0
		);

		for ($r = 0; $r < sizeof($test); $r += 4) {
			if ($columnsHash[$hi++]) {
				renderTableHeadCell($test[$r], htmlconstant($test[$r + (isset($lastSet) && $lastSet ? 2 : 1)]), $test[$r + 3]);
				$lastSet = 1;
			} else {
				$lastSet = 0;
			}
		}
	}
}

function canRead()
{
	global $curr_access;

	if ($curr_access & ACL_READ) {
		return 1;
	} else {
		global $curr_access_hint_printed;
		global $noaccessimgsize;
		global $site;

		if (!$curr_access_hint_printed) {
			$title = htmlconstant('_NOACCESS');
			echo "<img src=\"{$site->skin->imgFolder}/noaccess.gif\" width=\"{$noaccessimgsize[0]}\" height=\"{$noaccessimgsize[1]}\" border=\"0\" alt=\"$title\" title=\"$title\" />";
			$curr_access_hint_printed = 1;
		} else {
			echo '&nbsp;';
		}

		return 0;
	}
}

function getHtmlContent(&$tableDef, $r, &$db)
{
	global $dba;

	$rowflags	= intval($tableDef->rows[$r]->flags);
	$rowtype	= $rowflags & TABLE_ROW;

	switch ($rowtype) {
		case TABLE_BLOB:
			$value = explode(';', $db->fs($tableDef->rows[$r]->name));
			if ($value[0] != '') {
				return isohtmlentities(strval($value[0]));
			} else {
				return '&nbsp;';
			}

		case TABLE_TEXT:
		case TABLE_TEXTAREA:
			$value = smart_truncate($db->fs($tableDef->rows[$r]->name));
			if ($value != '') {
				return isohtmlentities(strval($value));
			} else {
				return '&nbsp;';
			}

		case TABLE_MATTR:
			$value = '';

			$id = $db->f('id');
			$dba->query("SELECT attr_id FROM $tableDef->name" . '_' . $tableDef->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
			while ($dba->next_record()) {
				$attrid = $dba->f('attr_id');
				if ($value) {
					$value .= ', ';
				}
				$value .= $tableDef->rows[$r]->addparam->get_summary($attrid, '; ' /*value seperator*/);
			}

			if ($value) {
				return isohtmlentities(strval($value));
			} else {
				return '&nbsp;';
			}

		case TABLE_SATTR:
			$value = isohtmlentities(strval($tableDef->rows[$r]->addparam->get_summary($db->f($tableDef->rows[$r]->name), '; ' /*value seperator*/)));
			return $value == '' ? '&nbsp;' : $value;

		case TABLE_INT:
			$value = $tableDef->formatField($tableDef->rows[$r]->name, $db->f($tableDef->rows[$r]->name));
			if ($value) {
				return isohtmlspecialchars($value);
			} else {
				if ($tableDef->rows[$r]->default_value !== 0 && $value === 0) {
					return intval($value); // "0" if price or similar where "0" is not the same as "nothing"
				} elseif ($rowtype == TABLE_INT) {
					return "&nbsp;"; // "&nbsp;" if no price (etc.) at all
				}
			}

		default:
			$value = $tableDef->formatField($tableDef->rows[$r]->name, $db->f($tableDef->rows[$r]->name));
			if ($value) {
				return isohtmlspecialchars($value);
			} else {
				return '&nbsp;';
			}
	}
}



/*=============================================================================
Global Part begins here
=============================================================================*/

if (isset($_REQUEST['object'])) require_once('deprecated_edit_class.php'); // must be included _before_ the session is started in functions.inc.php as the edit instances may be stored in the session


require_once("WisyKi/wisykistart.php");
if (isset($GLOBALS['WisyKi'])) {
	require_once("WisyKi/wisykicompetence.php");
	require_once("WisyKi/indexwisyki.php");
} else
	require_once("indexwisy.php");

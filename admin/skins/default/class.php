<?php


/*=============================================================================
Skin rendering the Admin-Tool
===============================================================================

file:	
	default/class.php
	
author:	
	Bjoern Petersen

parameters:
	none, only function definitions in this file.
	moreover, this file should create a skin object in $site->skin

usage:
	$ob = new DEFAULT_SKIN_CLASS;
	

	$ob->mainmenuStart();			// mainmenu area
		$ob->mainmenuItem($title, $ahref, $selected);
		...
	$ob->mainmenuBreak();
		...
	$ob->mainmenuEnd();
	
	
	$ob->submenuStart();			// submenu area, the submenu can also be 
									// used as a simple title
		$ob->submenuItem($type, $title, $ahref);
	$ob->submenuBreak();
		...
	$ob->submenuEnd();
	
	
	$ob->msgStart($type);			// message area
		...
	$ob->msgEnd();
	
	
	$ob->workspaceStart();			// workspace area
		...
	$ob->workspaceEnd();
	
	
	$ob->tableStart();				// table area
		$ob->headStart();
			$ob->cellStart();
				...
			$ob->cellEnd();
			...
		$ob->headEnd();
		$ob->rowStart();
			$ob->cellStart();
				...
			$ob->cellEnd();
			...
		$ob->rowEnd();
		...
	$ob->tableEnd();
	
	
	$ob->dialogStart();				// dialog area
		$ob->controlStart();		// a control is one row in the dialog
			...
		$ob->controlBreak();
			...
		$ob->controlEnd();
		...
	$ob->dialogEnd();
	
	$ob->buttonsStart();
	$ob->buttonsEnd();
	
	
	each area is completly independent and can be included in sections which 
	may be expandable/shrinkable or may be included in a menu:
	
	$ob->sectionDeclare($title1, $href, $selected);		// all sections must be declard _before_
	$ob->sectionDeclare($title2, $href, $selected);		// the first section ist started
	$ob->sectionStart();			
		// section 1 here
	$ob->sectionEnd();
	$ob->sectionStart();			
		// section 2 here
	$ob->sectionEnd();

=============================================================================*/



class SKIN_DEFAULT_CLASS
{
	// Texticons:
	var $ti_sortdesc = '&darr;';	// Alternative: &#9660; ist aber etwas zu auffaellig, &darr; passt besser
	var $ti_sortasc  = '&uarr;';	// Alternative: &#9650;       - " -
	var $ti_next     = '&gt;&gt;';	// Alternative: &#9654; ist aber je nach Browser etwas klein
	var $ti_prev     = '&lt;&lt;';	// Alternative: &#9664;       - " -
	// weitere Texticons: http://www.fileformat.info/info/unicode/block/geometric_shapes/utf8test.htm


	function __construct($folder)
	{
		$this->imgFolder	= $folder . '/img';
		$this->sectionCount = -1;
		$this->msgCount		= -1;
		$this->useTabs		= true;
	}



	/*=========================================================================
	mainmenu
	======================================================================== */



	function mainmenuStart()
	{
		echo '<table class="mm">';
		echo '<tr>';
		echo '<td class="mml">';
	}
	function mainmenuItem($title, $ahref, $selected, $id = '')
	{
		$addParam  = '';

		if ($selected) {
			$addParam .= $selected == 2 ? ' class="mms mmbc"' /*select as breadcrumb*/ : ' class="mms"' /*just select*/;
		}
		if ($id) {
			$addParam .= " id=\"$id\"";
		}

		echo str_replace('<a ', "<a$addParam ", $ahref) . "$title</a>";
	}
	function mainmenuBreak()
	{
		echo '</td>';
		echo '<td class="mmr">';
	}
	function mainmenuEnd()
	{
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		//echo '<img src="lang/help/files/w3c.gif" />';
	}



	/*=========================================================================
	submenu
	======================================================================== */



	function submenuStart($type = '') // $type := '', 'nowrap'
	{
		$this->submenuItemCount = 0;
		echo '<table class="sm">';
		echo '<tr>';
		echo '<td class="sml"';
		if ($type == 'nowrap') echo ' nowrap="nowrap"';
		echo '>';
	}
	function submenuItem($type, $descr, $ahref = '')
	{
		$textIcon	= '';
		$text		= $descr;

		switch ($type) {
			case 'mhelp':
				$textIcon = '?';
				break;

			case 'mlogout':
				$textIcon = '&times;';
				break;

			case 'mview':
				$textIcon = "&#8599; $descr";
				break;

			case 'mprev':
				$text = $this->ti_prev;
				break;

			case 'mnext':
				$text = $this->ti_next;
				break;

			case '':
				echo '<b>' . isohtmlentities("ERROR: unset type for: submenuItem(\"$type\", \"$descr\", \"" . isohtmlentities(strval($ahref)) . "\");") . '</b><br />';
				break;
		}

		if ($textIcon) {
			echo $ahref ? str_replace("<a ", "<a title=\"$text\" ", $ahref) : '<span>'; // use <span>..</span> to create real HTML-items even for disabled items (needed to add additional items with JavaScript)
			echo " &nbsp;$textIcon&nbsp; ";
			echo $ahref ? '</a>' : '</span>';
		} else {
			echo $ahref ? $ahref : '<span>'; // use <span>..</span> to create real HTML-items even for disabled items (needed to add additional items with JavaScript)

			if ($this->submenuItemCount) {
				echo ' &nbsp;';
			}

			echo $text;

			echo '&nbsp; ';

			echo $ahref ? '</a>' : '</span>';
		}

		$this->submenuItemCount++;
	}
	function submenuBreak()
	{
		echo '</td>';
		echo '<td class="smr">';
	}
	function submenuEnd($displayDBLoad = false)
	{
		echo '</td>';
		echo '</tr>';
		if ($displayDBLoad)
			echo $this->getDBLoadStatus();
		echo '</table>';
	}

	function getDBLoadStatus()
	{

		$additionalLoad = array();

		$display = false;
		$dbLoad = new DB_Admin;
		$dbLoad->query("SELECT svalue FROM `x_state` WHERE skey = 'what'");
		$dbLoad->next_record();
		$value = $dbLoad->f("svalue");
		if ($value == "kurseSlow")
			$keep = true;

		$dbLoad->query("SELECT svalue FROM `x_state` WHERE skey = 'updatestick' AND (svalue <> '0000-00-00 00:00:00' && svalue <> '')");
		$dbLoad->next_record();
		if (isset($keep) && $keep && $dbLoad->f("svalue"))
			$additionalLoad[] = "Suchindex-Update";

		if (count($additionalLoad))
			return '<tr><td class="additionalload"><span class="blink_me"></span>&nbsp;&nbsp;&nbsp;&nbsp;Besondere Datenbankbelastung: ' . $additionalLoad[0] . '</td><td></td></tr>';
		else
			return '';
	}



	/*=========================================================================
	messages
	======================================================================== */



	function msgStart(
		$type /* [e]rror, [w]arning, [i]information or [s]aved */,
		$onclose = 'hide msg' /* 'no close', 'hide msg' or 'close window' */
	) {
		$img	= "{$this->imgFolder}/icon{$type}.gif";

		$size_w	= 32;
		$size_h = 32;
		$this->msgCount++;
		$this->msgId = "sectmsg{$this->msgCount}";
		$this->msgOnClose = $onclose;
		echo "<div id=\"{$this->msgId}\" style=\"display:block;\">";
		echo '<table cellpadding="0" class="msg">';
		echo '<tr>';
		echo '<td class="msgi">';
		echo "<img src=\"$img\" width=\"{$size_w}\" height=\"{$size_h}\" border=\"0\" alt=\"\" />";
		echo '</td>';
		echo '<td class="msgt">';
	}
	function msgEnd()
	{
		echo '</td>';
		echo '<td class="msgc">';
		if ($this->msgOnClose != 'no close') {
			echo "<a href=\"\" onclick=\"return sectd('{$this->msgId}');\" title=\"" . htmlconstant('_CLOSE') . "\">&nbsp;&times;&nbsp;</a>";
		} else {
			echo '&nbsp;';
		}
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}



	/*=========================================================================
	workspace
	======================================================================== */



	function workspaceStart()
	{
		echo '<div class="ws">';
	}
	function workspaceEnd()
	{
		echo '</div>';
	}



	/*=========================================================================
	table 
	======================================================================== */


	function tableStart()
	{
		echo '<table class="tb">';
		$this->inTableHead = false;
	}
	function headStart()
	{
		echo "<thead><tr>";
		$this->inTableHead = true;
	}
	function headEnd()
	{
		echo "</tr></thead><tbody>";
		$this->inTableHead = false;
	}
	function rowStart($attr = '')
	{
		if ($attr != '') {
			$attr = " $attr";
		}
		echo "<tr$attr>";
	}
	function cellStart($attr = '') // $attr := '', 'nowrap', <attributes> 
	{
		if ($attr == 'nowrap') {
			$attr = ' class="nw"';
		} else if ($attr != '') {
			$attr = " $attr";
		}

		echo isset($this->inTableHead) && $this->inTableHead ? "<th$attr>" : "<td$attr>";
	}
	function cellEnd()
	{
		echo isset($this->inTableHead) && $this->inTableHead ? '</th>' : '</td>';
	}
	function rowEnd()
	{
		echo "</tr>\n";
	}
	function tableEnd()
	{
		if (isset($this->tbodyStarted) && $this->tbodyStarted) echo '</tbody>';

		echo "</table>\n";
	}




	/*=========================================================================
	dialog
	======================================================================== */



	function dialogStart()
	{
		echo '<table cellpadding="0" class="dl">';
	}
	function controlStart()
	{
		echo '<tr>';
		echo '<td class="dll">';
	}
	function controlBreak()
	{
		echo '</td>';
		echo '<td class="dlr">';
	}
	function controlEnd()
	{
		echo '</td>';
		echo '</tr>';
	}
	function dialogEnd()
	{
		echo '</table>';
	}



	/*=========================================================================
	buttons
	======================================================================== */



	function buttonsStart()
	{
		echo '<table cellpadding="0" class="sm">';
		echo '<tr>';
		echo '<td class="sml bt" nowrap="nowrap">';
	}
	function buttonsBreak()
	{
		echo '</td>';
		echo '<td class="smr">';
	}
	function buttonsEnd()
	{
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}


	/*=========================================================================
	fixed stuff
	======================================================================== */

	function fixedHeaderStart()
	{
		echo '<div id="fheader"><div id="fheader2">'; // two divs are needed to position the header with "valign=bottom"
	}
	function fixedHeaderEnd()
	{
		echo '</div></div>';
		echo '<div id="fcontent1"></div>'; // add some space above the content to allow full scrolling of the content out of the fixed area
		flush();
	}

	function fixedFooterStart()
	{
		echo '<div id="fcontent2"></div>'; // add some space below the content to allow full scrolling of the content out of the fixed area
		echo '<div id="ffooter">';		   // this also leaves enough whitespace below the buttons - otherwise most 2013er-browsers show the link-popups just over the buttons ...
	}
	function fixedFooterEnd()
	{
		echo '</div>';
		flush();
	}



	/*=========================================================================
	sections
	======================================================================== */

	function useTabsForSections($useTabs)
	{
		$this->useTabs = $useTabs;
	}
	function sectionDeclare($title, $href, $selected)
	{
		if ($this->sectionCount != -1) {
			echo '<h1>ERROR: cannot declare more sections as the sections are already started.</h1>';
			exit();
		} else {
			$this->sections[] = array($title, $href, $selected ? 1 : 0);
		}
	}
	function sectionDeclareRight($html)
	{
		$this->sectionRightHtml = $html;
	}

	function sectionStart()
	{

		if ($this->useTabs) {
			if ($this->sectionCount == -1) {
				$this->mainmenuStart();
				$numSections = sizeof((array) $this->sections);
				for ($s = 0; $s < $numSections; $s++) {
					$ahref = '';
					if (isset($this->sections[$s][1]) && $this->sections[$s][1]) {
						$ahref = '<a href="' . isohtmlentities($this->sections[$s][1]) . "\" onclick=\"return sect(this,$s,$numSections);\">";
					}
					$this->mainmenuItem($this->sections[$s][0], $ahref, $this->sections[$s][2], "sectmm$s");
				}
				$this->mainmenuBreak();

				echo isset($this->sectionRightHtml) && $this->sectionRightHtml ? $this->sectionRightHtml : '&nbsp;';

				$this->mainmenuEnd();
			}

			$this->sectionCount++;

			$display = isset($this->sections[$this->sectionCount][2]) && $this->sections[$this->sectionCount][2] ? 'block' : 'none';

			echo "<div id=\"sect{$this->sectionCount}\" style=\"display:$display;\">";
		} else if (isset($this->sectionRightHtml) && $this->sectionRightHtml) {
			$this->mainmenuStart();
			echo '&nbsp;';
			$this->mainmenuBreak();
			echo $this->sectionRightHtml;
			$this->mainmenuEnd();
			$this->sectionRightHtml = '';
		}
	}

	function sectionEnd()
	{
		if ($this->useTabs) {
			echo '</div>';
		}
	}



	/*=========================================================================
	other
	======================================================================== */



	function getCssTags($param)
	{
		if (!is_array($param)) $param = array();

		$cssParam = isset($param['css'])        ? $param['css'] : null;
		$cssColors = isset($param['colors'])    ? $param['colors'] : null;
		$cssPt = isset($param['pt'])            ? $param['pt'] : null;

		switch ($cssParam) {
			case 'print':
				$css = 'skins/default/cssprint.php?pt=' . $cssPt;
				break;

			default:
				$css = 'skins/default/cssscreen.php?colors=' . $cssColors;
				break;
		}

		return '<link rel="stylesheet" type="text/css" href="' . $css . '&amp;iv=' . CMS_VERSION . '" />' . "\n";
	}
}

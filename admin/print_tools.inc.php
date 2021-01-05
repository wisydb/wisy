<?php


/*****************************************************************************
 * embed tools
 *****************************************************************************/


// define hash file extension <--> mime type, the first value is the default
global $embed_ext2mime;
$embed_ext2mime = array
(
	 'asf'		=> 'video/x-ms-asf|video/x-msvideo'
	,'avi'		=> 'video/avi'
	,'bmp'		=> 'image/bmp'
	,'gif'		=> 'image/gif'
	,'htm|html'	=> 'text/html'
	,'jpg|jpeg'	=> 'image/jpg|image/jpeg|image/pjpg|image/pjpeg'
	,'mid'		=> 'audio/mid'
	,'mp3'		=> 'audio/mpeg|audio/mpg|audio/mp3'
	,'mpg|mpeg'	=> 'video/mpeg|video/mpg'
	,'pdf'		=> 'application/pdf'
	,'png'		=> 'image/png|image/x-png'
	,'qt'		=> 'video/quicktime'
	,'rm|ra|ram'=> 'application/vnd.rn-realmedia|application/realmedia|application/realaudio|application/realvideo'
	,'swf'		=> 'application/x-shockwave-flash'
	,'txt'		=> 'text/plain'
	,'wav'		=> 'audio/wav'
	,'xml'		=> 'text/xml'
);



// the embedded HTML code
global $embed_htmlcode;
$embed_htmlcode = array
(
	 'asf|avi|mpg'		=> '<embed src="%f" width="%a" height="%a" autostart="true" />'
	,'mid|mp3|wav'		=> '<embed src="%f" width="%a" height="180" autostart="true" />'
	,'bmp|gif|jpg|png'	=> '<img src="%f" width="%w" height="%h" border="0" alt="" />'
	,'htm|txt|xml'		=> '<iframe src="%f" scrolling="yes" width="%w" height="%h" frameborder="0"></iframe>'
	,'pdf'				=> '<iframe src="%f#toolbar=0&amp;scrollbar=0" scrolling="no" width="%w" height="%h" frameborder="0"></iframe>'
	,'qt'				=> '<embed src="%f" width="%a" height="%a" autostart="true" />'
	,'rm'				=> '<embed src="%f" type="audio/x-pn-realaudio-plugin" console="Clip1" controls="ImageWindow" width="%a" height="%a" autostart="true"><br /><EMBED type="audio/x-pn-realaudio-plugin" console="Clip1" controls="PlayButton" height="25" width="45" autostart="true">'
	,'swf'				=> '<embed src="%f" width="%w" height="%h" autostart="true" />'
);



function render_embed__search_hash(&$arr, $keyOrValue /* 'key' or 'value' */, $keyToFind)
{
	reset($arr);
	foreach($arr as $currKey => $currValue)
	{
		if( $keyOrValue == 'value' ) {
			$temp = $currKey;
			$currKey = $currValue;
			$currValue = $temp;
		}
		
		$currKey = explode('|', $currKey);
		$currValue = explode('|', $currValue);
		
		for( $i = 0; $i < sizeof($currKey); $i++ ) {
			if( $currKey[$i] == $keyToFind ) {
				return $currValue[0];
			}
		}
	}
	
	return ''; // nothing found
}



function render_embed_error($embed_w, $embed_h, $file_url, $error)
{
	$file_url = isohtmlentities($file_url);
	return	"<table cellpadding=\"0\" cellspacing=\"0\" border=\"1\" width=\"$embed_w\" height=\"$embed_h\">"
	.			'<tr>'
	.				'<td align="center" valign="middle">'
	.					"<a href=\"$file_url\" target=\"_blank\" rel=\"noopener noreferrer\">$file_url</a><br />($error)"
	.				'</td>'
	.			'</tr>'
	.		'</table>';
}

// render an embedded object
function render_embed(	$embed_w, $embed_h, 
						$file_url, $file_mime = '', $file_w = 0, $file_h = 0)
{
	global $embed_ext2mime;
	global $embed_htmlcode;
	
	// get mime type
	if( !$file_mime ) {
		$p = strrpos($file_url, '.');
		if( !$p ) {
			return render_embed_error($embed_w, $embed_h, $file_url, 'No Extension given');
		}
		
		$file_ext = substr($file_url, $p+1);
		$file_mime = render_embed__search_hash($embed_ext2mime, 'key', $file_ext);
		if( !$file_mime ) {
			return render_embed_error($embed_w, $embed_h, $file_url, 'Unknown Extension');
		}
	}
	
	// get file extension
	$file_ext = render_embed__search_hash($embed_ext2mime, 'value', $file_mime);
	if( !$file_ext ) {
		return render_embed_error($embed_w, $embed_h, $file_url, "Unknown MIME &quot;$file_mime&quot;");
	}
	
	// check $embed_w/$embed_h against  $file_w/$file_h
	if( $file_w && !strpos($file_w, '%')
	 && $file_h && !strpos($file_h, '%')
	 && !strpos($embed_w, '%')
	 && !strpos($embed_h, '%') )
	{
		if( $file_w > $embed_w ) {
			$file_h = intval(($file_h*$embed_w) / $file_w);
			$file_w = $embed_w;
		}

		if( $file_h > $embed_h ) {
			$file_w = intval(($file_w*$embed_h) / $file_h);
			$file_h = $embed_h;
		}
		
		$embed_w = $file_w;
		$embed_h = $file_h;
	}
	
	if( $embed_w > $embed_h ) {
		$embed_aspect = $embed_h;
	}
	else {
		$embed_aspect = $embed_w;
	}
	
	// get HTML code mask
	$htmlcode = render_embed__search_hash($embed_htmlcode, 'key', $file_ext);
	if( !$htmlcode ) {
		return render_embed_error($embed_w, $embed_h, $file_url, "Implementation missing for &quot;$file_ext&quot;");
	}

	// render embed
	$htmlcode = str_replace('%f', $file_url, $htmlcode);
	$htmlcode = str_replace('%x', $file_ext, $htmlcode);
	$htmlcode = str_replace('%w', $embed_w, $htmlcode);
	$htmlcode = str_replace('%h', $embed_h, $htmlcode);
	$htmlcode = str_replace('%a', $embed_aspect, $htmlcode);
	
	return $htmlcode;
}



/*****************************************************************************
 * print tools
 *****************************************************************************/


//
// Media Preview
//
function media_preview($mime, $url, $filename, $bytes, $w, $h, $renderLinks = 1)
{
	$media_w = 270;
	$media_h = 338;

	// media information out
	$ret = '';
	
	$ret .= isohtmlentities($filename);

	if( $mime ) {
		if( $ret ) $ret .= ', ';
		$ret .= isohtmlentities($mime);
	}

	if( $bytes ) {
		if( $ret ) $ret .= ', ';
		$ret .= ($bytes < 1024)? '&lt;1K' : (intval($bytes/1024) . 'K');
	}
	
	if( $w && $h ) {
		if( $ret ) $ret .= ', ';
		$ret .= $w."x$h";
	}
	
	$ret .= '<br />';
	
	$ret .= render_embed($media_w, $media_h, $url, $mime, $w, $h);
	
	// done
	return $ret;
}



//
// user or group preview
//
function user_or_group_preview($datefield, $id, $table, $field1, $field2, $renderLinks)
{
	$ret = '';

	// date output
	if( $datefield ) {
		$ret .= sql_date_to_human($datefield, 'datetime');
		$ret .= ' ' . htmlconstant('_BY') . ' ';
	}
	
	// user output
	$db = new DB_Admin;
	$db->query("SELECT $field1,$field2 FROM $table WHERE id=$id");
	if( $db->next_record() ) {
		
		/*
		if( $renderLinks && acl_get_access("$table.COMMON", $id) ) {
			$ret .= "<a href=\"preview.php?preview=2&amp;table=$table&amp;id=$id\">";
		}
		*/
		
		$name = $db->fs($field1);
		if( !$name ) $name = $db->fs($field2);
		if( !$name ) $name = $id;
		$ret .= isohtmlentities($name);
		
		/*
		if( $renderLinks && acl_get_access("$table.COMMON", $id) ) {
			$ret .= '</a>';
		}
		*/
	}
	else {
		$ret .= htmlconstant('_UNKNOWN');
	}
	
	return $ret;
}


function preview_field(&$table_def, $r, &$db, $renderLinks)
{
	$rowflags	= intval($table_def->rows[$r]->flags);
	$rowtype	= $rowflags&TABLE_ROW;
	
	switch( $rowtype )
	{
		case TABLE_INT:
		case TABLE_DATE:
		case TABLE_DATETIME:
		case TABLE_PASSWORD:
			$val = isohtmlentities($db->fs($table_def->rows[$r]->name));
			
			if( $rowtype == TABLE_INT )
			{
				if( ($rowflags&TABLE_EMPTYONNULL && $val=='0')
				 || ($rowflags&TABLE_EMPTYONMINUSONE && $val=='-1') ) {
					$val = '';
				}
			}
			
			if( $val != '' )
			{
				if( $rowtype == TABLE_DATETIME ) {
					return $val=='0000-00-00 00:00:00'? '' : sql_date_to_human($val, 'datetime');
				}
				else if( $rowtype == TABLE_DATE ) {
					return $val=='0000-00-00 00:00:00'? '' :  sql_date_to_human($val, ($rowflags & TABLE_DAYMONTHOPT)? 'dateopt' : 'date');
				}
				else if( $rowtype == TABLE_PASSWORD ) {
					return '*****';
				}
				else {
					return $val;
				}
			}
			break;

		case TABLE_TEXT:
		case TABLE_TEXTAREA:
			$val = trim($db->fs($table_def->rows[$r]->name));
		
			if( $val != '' )
			{
				$val = nl2br(isohtmlentities($val));
				$ret .= $val;
				return $ret;
			}
			break;

		case TABLE_BLOB:
			$name = $table_def->rows[$r]->name;
			$ob = new G_BLOB_CLASS($db->f($name));
			$bytes = sizeof($ob->blob);
			if( $bytes ) 
			{
				$mime = $ob->mime;
				$filename = $ob->name;
				$width = $ob->w;
				$height = $ob->h;
				
				return media_preview($mime, "media.php/{$name}/{$table_def->name}/" . $db->f('id') . "/$filename", $filename, $bytes, $width, $height, $renderLinks);
			}
			break;

		case TABLE_MATTR:
			global $preview_field_db;
			if( !is_object($preview_field_db) ) {
				$preview_field_db = new DB_Admin;
			}
			
			$ret = '';
			$preview_field_db->query("SELECT attr_id FROM $table_def->name" . '_' . $table_def->rows[$r]->name . " WHERE primary_id=".$db->f('id'));
			while( $preview_field_db->next_record() )
			{
				$attrid = $preview_field_db->f('attr_id');
				$ret .= $ret? ', ' : '';
				
				/*
				if( $renderLinks ) {
					$ret .= '<a href="preview.php?table=' .$table_def->rows[$r]->addparam->name. '&amp;id=' .$attrid. '&amp;preview=2">';
				}
				*/
				
				$ret .= $table_def->rows[$r]->addparam->get_summary($attrid, '; ' /*value seperator*/);
				
				/*
				if( $renderLinks ) {
					$ret .= '</a>';
				}
				*/
			}
			return $ret;

		case TABLE_SATTR:
			$attrid = $db->f($table_def->rows[$r]->name);
			if( $attrid )
			{
				$ret = '';

				/*
				if( $renderLinks ) {
					$ret .= '<a href="preview.php?table=' .$table_def->rows[$r]->addparam->name. '&amp;id=' .$attrid. '&amp;preview=2">';
				}
				*/

				$ret .= $table_def->rows[$r]->addparam->get_summary($attrid, '; ' /*value seperator*/);

				/*
				if( $renderLinks ) {
					$ret .= '</a>';
				}
				*/

				return $ret;
			}
			break;

		case TABLE_ENUM:
			$enumval = $db->f($table_def->rows[$r]->name);
			return isohtmlspecialchars($table_def->get_enum_summary($r, $enumval));

		case TABLE_BITFIELD:
			$bits = $db->f($table_def->rows[$r]->name);
			return $table_def->get_bitfield_summary($r, $bits, $all_bits);

		case TABLE_FLAG:
			return htmlconstant($db->f($table_def->rows[$r]->name)? '_YES' : '_NO');
	}
	
	return '';
}



//
// common HTML output function, function returns the created HTML output -
// nothing will be written to STDOUT
//
function preview_do($table, $id, $renderLinks, $view = 'details' /*or 'list'*/,
					$level = 0, $fieldStart = '')
{
	$table_def = Table_Find_Def($table);
	if( !$table_def ) {
		return ''; // error
	}

	$ret = '';
	
	if( $level == 0 ) {
		$ret .= '<div class="prtitle">' .htmlconstant('_TABLE'). " $table_def->descr - ID $id</div>";
	}
	
	$dba = new DB_Admin;
	$db = new DB_Admin;
	$db->query("SELECT * FROM $table WHERE id=$id");
	if( $db->next_record() )
	{
		// go through all fields (rows)
	    for( $r = 0; $r < sizeof((array) $table_def->rows); $r++ )
		{
			// parse this field
			$rowflags	= $table_def->rows[$r]->flags;
			$rowtype	= $rowflags & TABLE_ROW; 
			switch( $rowtype ) 
			{
				case TABLE_TEXTAREA:
					$val = preview_field($table_def, $r, $db, $renderLinks);
					if( $val != '' ) {
						$ret .= "<b>{$table_def->rows[$r]->descr}:</b><br />$val<br />";
					}
					break;
				
				case TABLE_SECONDARY:
					$dba->query("SELECT secondary_id FROM $table" . '_' . $table_def->rows[$r]->name . " WHERE primary_id=$id ORDER BY structure_pos");
					$records = 0;
					while( $dba->next_record() ) {
						$ret .= '<div class="prtitle">' . $table_def->rows[$r]->descr . '</div>';
						$ret .= preview_do($table_def->rows[$r]->addparam->name, $dba->f('secondary_id'), $renderLinks, $view, $level+1);
						$records++;
					}
					if( $records ) {
						$ret .= '<div class="prtitle"><img src="skins/default/img/1x1.gif" width="1" height="1" border="0" alt="" /></div>';
					}
					break;

				default:
					$val = preview_field($table_def, $r, $db, $renderLinks);
					if( $val != '' ) {
						$ret .= "<b>{$table_def->rows[$r]->descr}:</b> $val<br />";
					}
					break;
			}
		}
		
		if( $level == 0) {
			$ret .= '<b>' . htmlconstant('_OVERVIEW_CREATED') . ':</b> ';
			$ret .= user_or_group_preview($db->f('date_created'), $db->f('user_created'), 'user', 'name', 'loginname', $renderLinks) . '<br />';
			$ret .= '<b>' . htmlconstant('_OVERVIEW_MODIFIED') . ':</b> ';
			$ret .= user_or_group_preview($db->f('date_modified'), $db->f('user_modified'), 'user', 'name', 'loginname', $renderLinks) . '<br />';
			$ret .= '<b>' . htmlconstant('_GROUP') . ':</b> ';
			$ret .= user_or_group_preview('', $db->f('user_grp'), 'user_grp', 'shortname', 'name', $renderLinks) . '<br />';
			$ret .= '<b>' . htmlconstant('_RIGHTS') . ':</b> ';
			$ret .= access_to_human($db->f('user_access')) . '<br />';
		}
	}
	
	return $ret;
}




<?php

/*=============================================================================
Edit User Access, Server Part; This is a plugin
===============================================================================

file:	
	deprecated_edit_access.php
	
author:	
	Bjoern Petersen

=============================================================================*/


function plugin_edit_access_get_add_rules($regId) 
{
	$ret = '';

	$addrules = strtoupper(regGet($regId, ''));
	$addrules = strtr($addrules, "; \n\r\t", "     ");
	$addrules = str_replace(' ', '', $addrules);
	$addrules = explode(',', $addrules);
	
	for( $i = 0; $i < sizeof($addrules); $i++ ) {
		if( $addrules[$i] ) {
			$ret .= ",$addrules[$i]";
		}
	}
	
	return $ret;
}



$pluginfunc = 'plugin_edit_access';
function plugin_edit_access(&$param)
{
	switch( $param['cmd'] ) 
	{

		//
		// load the control values from a database, return any value/array/object
		// parameters (all read-only):
		//		table
		//		field
		//		id
		//		db		-	contains the result of the record
		//
		case 'load':
			return $param['db']->fs($param['field']);


		
		//
		// init the control values, return any value/array/object
		// parameters (all read-only):
		//		table
		//		field
		//
		case 'init':
			return '';



		//
		// return the HTML-Code needed to render this control
		// parameters (all read-only):
		//		table
		//		field
		//		id		-	-1 if unknown
		//		control	-	an unique control index
		//		values	-	the value(s) as returned by 'load'/'init'/'derender'
		//
		case 'render':
			// init translations
			$transl = array();
			$transl['entries'] = htmlconstant('_ENTRIES');
			$transl['languages'] = htmlconstant('_LANGUAGES');

			// get all groups
			$allgroups = "";
			$db = new DB_Admin;
			$db->query("SELECT shortname,name,id FROM user_grp ORDER BY shortname, name");
			while( $db->next_record() ) {
				$allgroups .= ($allgroups? ',' : '') . $db->f('id');
				
				$name = $db->fs('shortname');
				if( !$name ) {
					$name = $db->fs('name');
					if( !$name ) {
						$name = $db->f('id');
					}
				}
				
				$transl[$db->f('id')] = $name;
			}

			// create code			
			global $site;
			$code = "<script type=\"text/javascript\" src=\"deprecated_edit_access.js\"></script>"
			.		"<script type=\"text/javascript\"><!--\n"
			.		"euat=\"";

			global $Table_Def;
			for( $t = 0; $t < sizeof((array) $Table_Def); $t++ )
			{
				$code .= $t? ';' : '';
				$code .= $Table_Def[$t]->name . ';';
				for( $r = 0; $r < sizeof((array) $Table_Def[$t]->rows); $r++ ) {
					$code .= $Table_Def[$t]->rows[$r]->name . ',';

					$transl[$Table_Def[$t]->rows[$r]->name] = $Table_Def[$t]->rows[$r]->descr;
				}
				

				$code .= 'RIGHTS'.plugin_edit_access_get_add_rules("addrules.{$Table_Def[$t]->name}");
				
				$transl[$Table_Def[$t]->name] = $Table_Def[$t]->descr;
			}
			
			
			$code .= ";SUPERVISOR;$allgroups;SYSTEM;LOCALIZELANG,LOCALIZEENTRIES,EXPORT,IMPORT".plugin_edit_access_get_add_rules('addrules.system')."\";euar=\"";
			
			$i = 0;
			$ent = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT|ENT_HTML401, 'ISO-8859-1');
			$ent = array_flip($ent);
			reset($transl);
			foreach($transl as $k => $v) {
				$v = strtr($v, $ent);
				$v = strtr($v, ";\"\',:", "     ");
				$code .= ($i?';':'') . "$k;$v";
				$i++;
			}
			
			$code.= "\";euaRender(\"" . strtr($param['values'], array("\n"=>";", "\r"=>"", "\t"=>"", "'"=>"", "\""=>"", " "=>"")) . "\");/"."/--></script>"
			.		"<noscript>"
			.			"<textarea rows=\"5\" cols=\"40\" name=\"euahidden\">"
			.				isohtmlentities( strval( $param['values'] ) )
			.			"</textarea>"
			.		"</noscript>";

			if( $param['readonly'] )
			{
				// field is readonly
				$entries = explode(';', $param['values']);
				
				$code = '<table cellpadding="0" cellspacing="0" border="0">';
					for( $e = 0; $e < sizeof($entries); $e++ )
					{
						list($path, $grants) = explode(':', $entries[$e]);
						$path = trim($path);
						
						if( $path )
						{
							$code .= '<tr>';
								$code .= '<td valign="top">';
									list($path1, $path2) = explode('.', $path);
									$code .= $transl[$path1]? $transl[$path1] : $path1;
									$code .= '.';
									$code .= $transl[$path2]? $transl[$path2] : $path2;
								$code .= '</td>';
								$code .= '<td>&nbsp;&nbsp;&nbsp;</td>';
								$code .= '<td valign="top">';
									$grants		= " $grants";
									$grantsStr	= '';
									
									if( strpos($grants, 'n') 
									 && strpos($grants, 'r')
									 && strpos($grants, 'w')
									 && strpos($grants, 'd') ) {
										$grantsStr = htmlconstant('_ALLRIGHTS');
									}
									else {
										if(strpos($grants, 'n')) { $grantsStr.=$grantsStr?', ':''; $grantsStr .= htmlconstant('_NEW');		}
										if(strpos($grants, 'r')) { $grantsStr.=$grantsStr?', ':''; $grantsStr .= htmlconstant('_READ');		}
										if(strpos($grants, 'w')) { $grantsStr.=$grantsStr?', ':''; $grantsStr .= htmlconstant('_EDIT');		}
										if(strpos($grants, 'd')) { $grantsStr.=$grantsStr?', ':''; $grantsStr .= htmlconstant('_DELETE');	}
										if($grantsStr==''	   ) { $grantsStr=htmlconstant('_NOACCESS');									}
									}
									
									$code .= $grantsStr;
								$code .= '</td>';
							$code .= '</tr>';
						}
					}
				$code .= '</table>';
			}

			return $code;

		//
		// re-init the values from the rendered HTML-Code, return any value / array / object
		// parameters (all read-only):
		//		table
		//		field
		//		id		-	-1 if unknown
		//		control	-	an unique control index, same as given to 'render'
		//
		case 'derender':
		    return isset( $_REQUEST['euahidden'] ) ? $_REQUEST['euahidden'] : null;



		//
		// verify a value, return an error string or '' for no error.
		// parameters (all read-only):
		//		table
		//		field
		//		id
		//		values	-	the value(s) as returned by 'load'/'init'/'derender'
		//
		case 'verify':
			return ''; // no error


	}
}
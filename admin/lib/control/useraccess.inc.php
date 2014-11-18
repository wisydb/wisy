<?php
/******************************************************************************
The User Access Control
***************************************************************************//**

The user access control is used to edit the user access rights for a single
_user_ record.
Do not mix up this with the "rights control" in the lower right corner of
_every_ record editor.

@author Björn Petersen, http://b44t.com

******************************************************************************/



class CONTROL_USERACCESS_CLASS extends CONTROL_BASE_CLASS
{
	private function plugin_edit_access_get_add_rules($regId) 
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

	public function get_default_dbval($use_tracked_defaults)
	{
		return $this->row_def->default_value=='0'? '' : $this->row_def->default_value;
	}

	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		// normally, we should use x_user_input() (see remarks there), 
		// however, as there is _always_ exactly one of the user access controls, a simple $_REQUEST will do the job
		$this->dbval = $_REQUEST['euahidden'];
	}

	public function render_html($addparam)
	{
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
		$code = "<script type=\"text/javascript\" src=\"lib/control/useraccess.js\"></script>"
		.		"<script type=\"text/javascript\"><!--\n"
		.		"euat=\"";

		global $Table_Def;
		for( $t = 0; $t < sizeof($Table_Def); $t++ )
		{
			$code .= $t? ';' : '';
			$code .= $Table_Def[$t]->name . ';';
			for( $r = 0; $r < sizeof($Table_Def[$t]->rows); $r++ ) {
				$code .= $Table_Def[$t]->rows[$r]->name . ',';

				$transl[$Table_Def[$t]->rows[$r]->name] = $Table_Def[$t]->rows[$r]->descr;
			}
			

			$code .= 'RIGHTS'.$this->plugin_edit_access_get_add_rules("addrules.{$Table_Def[$t]->name}");
			
			$transl[$Table_Def[$t]->name] = $Table_Def[$t]->descr;
		}
		
		
		$code .= ";SUPERVISOR;$allgroups;SYSTEM;LOCALIZELANG,LOCALIZEENTRIES,EXPORT,IMPORT".$this->plugin_edit_access_get_add_rules('addrules.system')."\";euar=\"";
		
		$i = 0;
		$ent = get_html_translation_table(HTML_ENTITIES);
		$ent = array_flip($ent);
		reset($transl);
		while( list($k, $v) = each($transl) ) {
			$v = strtr($v, $ent);
			$v = strtr($v, ";\"\',:", "     ");
			$code .= ($i?';':'') . "$k;$v";
			$i++;
		}
		
		$code.= "\";euaRender(\"" . strtr($this->dbval, array("\n"=>";", "\r"=>"", "\t"=>"", "'"=>"", "\""=>"", " "=>"")) . "\");/"."/--></script>"
		.		"<noscript>"
		.			"<textarea rows=\"5\" cols=\"40\" name=\"euahidden\">"
		.				isohtmlentities($this->dbval)
		.			"</textarea>"
		.		"</noscript>";

		return $code;
	}
	

};


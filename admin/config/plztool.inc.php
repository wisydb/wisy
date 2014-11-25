<?php
/*******************************************************************************
Search for PLZ using the table "plztool"
********************************************************************************

To set up the table, please refer to plztool_init.php or 
plztool_init.php-disabled.

@author Bjoern Petersen
 
*******************************************************************************/


class PLZTOOL_CLASS
{
	private $plz_normalize_array;
	

	private function plz_normalize($strasse)
	{
		if( !is_array($this->plz_normalize_array) )
		{
			$this->plz_normalize_array = array
			(
				  'hamburg'		=>  'hh'
				, 'straáe'		=>	'str'
				, 'straße'		=>	'str'
				, 'strasse'		=>	'str'
				, 'ä'			=>	'a'
				, 'ö'			=>	'o'
				, 'ü'			=>	'u'
				, 'ß'			=>	'ss'
				, '\''			=>	'' // this also makes addslashes() unnecessary
				, '.'			=>	''
				, '-'			=>	''
				, ' '			=>	''
			);
		}
		
		return strtr(strtolower($strasse), $this->plz_normalize_array);
	}

	/***************************************************************************
	Search for a PLZ by street/ort, returns an array as
	
		array(
			'plz'		=> '22767',
			'stadtteil'	=> 'Altona'
 		);
 
 	If the PLZ and/or street/ort are not unique, any of the returned values may
 	be an empty string.
 	
 	If no data can be found at all (eg. street typo), "false" is returned.
	***************************************************************************/
	function search_plz_by_str_ort($strasse_hsnr, $ort)
	{
		$db = new DB_Admin;
		
		$strasse = trim(preg_replace('/(\d+.*)/', '', $strasse_hsnr));

		$ort_norm = $this->plz_normalize($ort);
		$strasse_norm = $this->plz_normalize($strasse);
		$sql = "SELECT plz, stadtteil FROM plztool WHERE strasse_norm='$strasse_norm' AND ort_norm='$ort_norm'";
		$db->query($sql);
		if( !$db->next_record() )
			return false; // no record found

		$plz = $db->fs('plz');
		$stadtteil = $db->fs('stadtteil');

		return array('plz'=>$plz, 'stadtteil'=>$stadtteil);
	}

};








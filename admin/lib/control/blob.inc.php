<?php

class CONTROL_BLOB_CLASS extends CONTROL_BASE_CLASS
{
	private $current_blob;
	
	public function get_default_dbval($use_tracked_defaults)
	{
		return '';
	}
	
	public function set_dbval_from_user_input($user_input, &$errors, &$warnings, $addparam)
	{
		// get some additional user inputs
		$useraction			= $this->x_user_input('x_action_'); // useraction: "delete"=>delete, "<anything else>"=>update
		if( $this->is_in_secondary() ) {
			$userfile_name		= $_FILES['x_ul_'.substr($this->name, 0, -2)][$this->x_secondary_index]['name'];
			$userfile_type 		= $_FILES['x_ul_'.substr($this->name, 0, -2)][$this->x_secondary_index]['type'];
			$userfile_size 		= $_FILES['x_ul_'.substr($this->name, 0, -2)][$this->x_secondary_index]['size'];
			$userfile_tmp_name 	= $_FILES['x_ul_'.substr($this->name, 0, -2)][$this->x_secondary_index]['tmp_name'];
		}
		else {
			$userfile_name		= $_FILES['x_ul_'.$this->name]['name'];
			$userfile_type 		= $_FILES['x_ul_'.$this->name]['type'];
			$userfile_size 		= $_FILES['x_ul_'.$this->name]['size'];
			$userfile_tmp_name 	= $_FILES['x_ul_'.$this->name]['tmp_name'];
		}

		// by default, keep the file
		$this->dbval = $user_input;
	
		if( $userfile_tmp_name && $userfile_tmp_name != 'none' && is_uploaded_file($userfile_tmp_name) && $userfile_size > 0 )
		{
			// file uploaded ...
			if( $useraction == 'update' ) // otherwise, the user may have uploaded a file and clicked "delete" afterwards. in this case, we just keep the database file
			{
				$userfile_handle = fopen($userfile_tmp_name, 'rb');
				if( $userfile_handle )	
				{
					// create new blob object ...
					$blob = new G_BLOB_CLASS();
					$blob->name = $userfile_name? $userfile_name : 'noname';
					$blob->blob = fread($userfile_handle, $userfile_size);
					fclose($userfile_handle);
					
					// ... set blob object's dimensions
					$old_level = error_reporting(0);
						$userfile_dim = GetImageSize($userfile_tmp_name);
					error_reporting($old_level);				
					$blob->w = $userfile_dim[0];
					$blob->h = $userfile_dim[1];

					// ... set mime type
					$blob->mime = $userfile_type;
					$blob->mime = str_replace(',', ' ', $blob->mime);
					$blob->mime = str_replace(';', ' ', $blob->mime);
					$blob->mime = explode(' ', trim($blob->mime));
					$blob->mime = $blob->mime[0];
					if( !$blob->mime ) {
						switch($userfile_dim[2]) {
							case 1: $blob->mime = 'image/gif'; break;
							case 2: $blob->mime = 'image/jpeg'; break;
							case 3: $blob->mime = 'image/png'; break;
						}
					}
				
					$this->dbval = $blob->encode_as_str();
				}			
			}
		}
		else if( $useraction == 'delete' )
		{
			// delete a file
			$this->dbval = '';
		}
	}
	
	public function render_html($addparam)
	{
		$html = '';
	
		// render the logo itself
		$blob = new G_BLOB_CLASS($this->dbval);
		if( strlen($blob->blob) > 0 ) 
		{
			$info = $blob->name . ' (' . $blob->mime . ', ' . strlen($blob->blob) . ' Bytes)';
			if( $blob->w > 0 && $blob->h > 0 ) 
			{
				// image blob
				$html .= '<img class="e_blobimg" width="'.$blob->w.'" height="'.$blob->h.'" src="data:'.$blob->mime.';base64,'.base64_encode($blob->blob).'" title="'.isohtmlspecialchars($info).'" /> ';
			}
			else {
				// other blob
				$html .= '<b class="e_blobimg">'.isohtmlspecialchars($info).'</b> ';
			}
		}
		
		$html .= '<input type="hidden" name="' . $this->name . '" value="' . isohtmlspecialchars($this->dbval) . '"/>';
		$html .= '<span>';
			$html .= ' <a href="#" class="e_defhide_more" data-defhide-id="blob" title="Datei auswählen...">&#x25ba;</a>';
			$html .= '<span class="e_defhide_blob e_hidden">';

				$useraction = 'update';
				
				$checked = $useraction!='delete'? ' checked="checked" ' : '';
				$html .= '<input ' .$checked. ' type="radio" name="x_action_' . $this->name . '" id="' . $this->name . '_action_update" value="update" /><label for="' . $this->name . '_action_update">Ändern:</label> ';
				
				$html .= ' <input name="x_ul_' . $this->name . '" type="file" value="" />';

				$checked = $useraction=='delete'? ' checked="checked" ' : '';
				$html .= ' <input ' .$checked. ' type="radio" name="x_action_' . $this->name . '" id="' . $this->name . '_action_delete" value="delete" /><label for="' . $this->name . '_action_delete">Löschen</label>';
		
			$html .= '</span>';
		$html .= '</span>';
		
		return $html;
	}
};

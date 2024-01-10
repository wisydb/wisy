<?php

/*=============================================================================
Create ZIP-Files
=============================================================================*/



class EXP_ZIPWRITER_CLASS
{
    
    private $ctrl_dir		= array();								// central directory
    private $eof_ctrl_dir	= "\x50\x4b\x05\x06\x00\x00\x00\x00";	// end of central directory record
    private $old_offset   	= 0;									// Last offset position
	private $handle;

	function __construct($filename)
	{
		$this->handle = @fopen($filename, 'w+b');
	}

    
    // Converts an Unix timestamp to a four byte DOS date and time format (date
    // in high two bytes, time in low two bytes allowing magnitude comparison).
    // Returns the current date in a four byte DOS format.
    private function unix2DosTime_($unixtime = 0 /*the current Unix timestamp*/ )
	{
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980)
		{
        	$timearray['year']    = 1980;
        	$timearray['mon']     = 1;
        	$timearray['mday']    = 1;
        	$timearray['hours']   = 0;
        	$timearray['minutes'] = 0;
        	$timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } 

	
	
    // Adds a "file" to archive, with:
    // data = file contents
    // name = name of the file in the archive (may contains the path)
    // time = the current timestamp
    function add_data($data, $name, $time = 0)
    {
        if( !isset( $this->handle ) || !$this->handle )
			return false;
	
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime_($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        if( @fwrite($this->handle, $fr) === false )
			return false;

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this->old_offset ); // relative offset of local header
        $this->old_offset += strlen($fr);

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this->ctrl_dir[] = $cdrec;
		
		return true;
    } 


    // Dumps out things not yet written
    // returns a string with the zipped file
    private function getArchiveDump_()
    {
        $ctrldir = implode('', $this -> ctrl_dir);
        return
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', $this->old_offset) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    }

	function close()
	{
		@fwrite($this->handle, $this->getArchiveDump_());
		
		if( !@fclose($this->handle) )
			return false;
			
		$this->handle = false;
		return true;
	}	
};
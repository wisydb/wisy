<?php

class G_BLOB_CLASS
{
	public $name;
	public $mime;
	public $w;
	public $h;
	public $blob;
	
	function __construct($str = '')
	{
		$this->decode_from_str($str);
	}
	
	public function clear()
	{
		$this->name	= '';
		$this->mime	= '';
		$this->w	= 0;
		$this->h	= 0;
		$this->blob = '';
	}
	
	public function encode_as_str()
	{
		if( strlen($this->blob) > 0 )
		{
			$repl = array(';'=>',');
			return	strtr($this->name?$this->name:'noname', $repl) . ';'	// index 0
				.	strtr($this->mime, $repl) . ';'							// index 1
				.	intval($this->w) . ';'									// index 2
				.	intval($this->h) . ';'									// index 3
				.	chunk_split(base64_encode($this->blob), 76, "\n");		// index 4
		}
		else
		{
			return '';
		}
	}
	
	public function decode_from_str($str)
	{
		$str = explode(';', $str);
		if( sizeof($str) >= 5 )
		{
			$this->name	= $str[0];
			$this->mime	= $str[1];
			$this->w	= intval($str[2]);
			$this->h	= intval($str[3]);
			$this->blob = base64_decode($str[4]);
		}
		else
		{
			$this->clear();
		}
	}
};

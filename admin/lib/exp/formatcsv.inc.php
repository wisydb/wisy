<?php

/*=============================================================================
Export CSV class
===============================================================================

Author:
	Bjoern Petersen

===============================================================================
Wie wir CSV schreiben:

- Zeilen werden per Default mit CR LF = 0xD, 0xA = \r\n getrennt
- Felder werden per Default mit KOMMA getrennt
- Felder werden per Default mit DOUBLEQUOTE eingeschlossen
- Felder werden per Default mit DOUBLEQUOTE escaped
- Wir schreiben immer eine Headerzeile

Dies geht einher mit RFC 4180 und den Empfehlungen aus
http://mastpoint.curzonnassau.com/csv-1203/

Allerdings: Hier geht es nur um das Schreiben von CSV - beim Einlesen sollten
wir wesentlich toleranter sein!
=============================================================================*/




class EXP_FORMATCSV_CLASS extends EXP_GENERICTABLE_CLASS
{
	public  $options;

	private $handle;
	private $noheader;
	private $fieldstart;
	private $fieldend;
	private $fieldesc;
	private $lineend;

	function __construct()
	{
		parent::__construct();
		
		$this->handle = false;
		
		$this->options['table']		=	array('enum', '_EXP_TABLETOEXPORT', '', 'tables');
		$this->options['q']			= 	array('text', '_EXP_RECORDSQUERY', '', 60);
		$this->options['dummy']		= 	array('remark', '_EXP_RECORDSQREMARK');
		$this->options['attrasids']	=	array('enum', '_EXP_ATTR', 0, 
											 '0###_EXP_ATTRASTEXT###1###_EXP_ATTRASIDS');
		$this->options['fileformat']=	array('enum', '_EXP_LINEBREAKS', 'rn', 
											 'rn###Windows###'		/* 0xD, 0xA */
											.'n###Linux, Mac'		/* 0xA */
											//.'r###Mac OS 9###'	/* 0xD */				-- ungängige Formate nicht anbieten - im Zweifelsfall müssten wir das sonst selbst wieder importieren, was ungleich schwerer ist ...
											 );
		//$this->options['noheader']=	array('enum', 'Header schreiben', 0, '0###Ja (Standard)###1###Nein');	//	-- ungängige Formate nicht anbieten - im Zweifelsfall müssten wir das sonst selbst wieder importieren, was ungleich schwerer ist ...
		$this->options['fieldlinebreaks']
									=	array('check', '_EXP_ALLOWLINEBREAKSINFIELDS', 1);
		$this->options['fieldsep']	=	array('enum', '_EXP_FIELDSEP', 'comma', 
											 'comma###, (Standard)###'
											.'semicolon###;###'
											.'tab###Tab');
		$this->options['fieldencl']	=	array('enum', '_EXP_FIELDENCL', 'doublequote', 
											 'doublequote###&quot; (Standard)###'
											//."singlequote###'###"							-- ungängige Formate nicht anbieten - im Zweifelsfall müssten wir das sonst selbst wieder importieren, was ungleich schwerer ist ...
											//."quote###&#180;###"
											//."backquote###&#96;###"
											//."backquotequote###&#96;&#180;###"
											.'none###_EXP_DONTENCLFIELDS');
		$this->options['fieldesc']	=	array('enum', '_EXP_FIELDESC', 'doublequote', 
											 'doublequote###&quot; (Standard)###'	
											.'backslash###&#92;###'
											//."singlequote###'###"							-- ungängige Formate nicht anbieten - im Zweifelsfall müssten wir das sonst selbst wieder importieren, was ungleich schwerer ist ...
											//."quote###´###"
											//."backquote###&#96;###"
											.'none###_EXP_DONTESCFIELDS');
	}
	
	
	
	function tableStart($tableName, $type)
	{
		$tableName = str_replace('_', '-', $tableName);
		$filename = $this->allocateFileName("$tableName.csv");
		$this->handle = fopen($filename, 'w+b' /*export binary data, we'll handle the different lineends ourself*/ );
		if( !$this->handle ) 
			$this->progress_abort("Cannot open $filename.");
	}
	function tableEnd()
	{
		fclose($this->handle);
		$this->handle = false;
	}
	
	
	
	function declareStart()
	{
		if( !$this->noheader ) {
			$this->recordStart();
		}
	}
	function declareField($name, $rowtype)
	{
		if( !$this->noheader ) {
			$this->recordField(strtoupper($name));
		}
	}
	function declareEnd()
	{
		if( !$this->noheader ) {
			$this->recordEnd();
		}
	}
	
	
	
	function recordStart()
	{
		$this->f = 0;
	}
	function recordField($data)
	{
		$towrite  = $this->f? $this->fieldsep : '';
		$towrite .= $this->fieldstart;
		$towrite .= strtr($data, $this->fieldesc);
		$towrite .= $this->fieldend;

		if( fwrite($this->handle, $towrite) === false )
			$this->progress_abort("Cannot write.");
		
		$this->f++;
	}
	function recordEnd()
	{
		$towrite = $this->lineend;
		
		if( fwrite($this->handle, $towrite) === false )
			$this->progress_abort("Cannot write.");
	}
	

	
	//
	// start the export
	//
	function export($param)
	{
		// write header
		$this->noheader = $param['noheader'];
		
		// get line end
		switch( $param['fileformat'] )
		{
			case 'r':	$this->lineend = chr(13);			break;
			case 'n':	$this->lineend = chr(10);			break;
			default:	$this->lineend = chr(13) . chr(10);	break;
		}
		
		// get field seperator
		switch( $param['fieldsep'] )
		{
			case 'tab':		$this->fieldsep = chr(8);	break;
			case 'comma':	$this->fieldsep = ',';		break;
			default:		$this->fieldsep = ';';		break;
		}
		
		// get field encosing characters
		switch( $param['fieldencl'] )
		{
			case 'quote':			$this->fieldstart = chr(180);	$this->fieldend = chr(180);	break;
			case 'backquote':		$this->fieldstart = chr(96);	$this->fieldend = chr(96);	break;
			case 'backquotequote':	$this->fieldstart = chr(96);	$this->fieldend = chr(180);	break;
			case 'singlequote':		$this->fieldstart = chr(39);	$this->fieldend = chr(39);	break;
			case 'none':			$this->fieldstart = '';			$this->fieldend = '';		break;
			default:				$this->fieldstart = chr(34);	$this->fieldend = chr(34);	break;
		}
		
		// get field escaping data
		$this->fieldesc = array();
		
		if( $param['fieldesc'] != 'none' && $this->fieldstart != '' ) 
		{
			switch( $param['fieldesc'] )
			{
				case 'quote':			$temp = chr(180);	break;
				case 'backquote':		$temp = chr(96);	break;
				case 'singlequote':		$temp = chr(39);	break;
				case 'doublequote':		$temp = chr(34);	break;
				default:				$temp = chr(92);	break;
			}
			
			$this->fieldesc[$this->fieldstart]	= $temp . $this->fieldstart;
			$this->fieldesc[$this->fieldend]	= $temp . $this->fieldend;
			$this->fieldesc[$temp]				= $temp . $temp;
			
			if( !$param['fieldlinebreaks'] )
			{
				$this->fieldesc[chr(10)]			= ' ';
				$this->fieldesc[chr(13)]			= ' ';
			}
		}
		else 
		{
			$this->fieldesc[chr(10)]			= ' ';
			$this->fieldesc[chr(13)]			= ' ';
			
			if( $this->fieldstart == '' )
			{
				$this->fieldesc[$this->fieldsep]	= ' ';
			}
			else
			{
				$this->fieldesc[$this->fieldstart] = ' ';
				$this->fieldesc[$this->fieldend]   = ' ';
			}
		}
		
		// start export by calling the parent method
		$param['enums'] = 1;
		parent::export($param);
	}
}




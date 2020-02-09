<?php



//
// settings
//
///////////////////////////////////////////////////////////////////////////////



global $g_stichwPruefsiegel;
$g_stichwPruefsiegel	= 8;	// ID of the keyword indicating "Pruefsiegel"

global $g_stichwBU;
$g_stichwBU				= 1;	// ID of the keyword indicating "Bildungsurlaub"




//
// RTF output functions
//
///////////////////////////////////////////////////////////////////////////////



class RTF_WRITER_CLASS
{
	var $output_state;	// one of 'init', 'data', 'finalize'
	var $output_type;
	var $output;

	var $colors;
	var $fontFamilies;
	var $fonts;

	var $styles;
	var $styles_index;
	var $styles_type;	// 'para' or 'char'

	var $write_style_format;

	var $global_tabs;
	var $text_transl;

	var $sect_counter;
	
	//
	// PUBLIC STUFF
	// all public functions return >= 0 on success and -1 for errors
	//
	///////////////////////////////////////////////////////////////////////////


	//
	// PUBLIC STUFF: Common RTF-File Definitions
	//
	///////////////////////////////////////////////////////////////////////////


	// set output to file	
	function outputToFile($filename)
	{
		if( $this->output_state == 'init' ) {
			$this->close();
			$this->output = @fopen($filename, "w+");
			if( $this->output ) {
				$this->output_type = 'file';
				return 0; // success
			}
			else {
				$this->output_type = 'echo';
			}
		}
		return -1; // error
	}
	
	// set output to string, after wFinalize() output can be found in $output
	function outputToString(&$string)
	{
		if( $this->output_state == 'init' ) {
			$this->close();
			$this->output_type = 'string';
			$this->output = &$string;
			return 0; // success
		}
		return -1; // error
	}
	
	// set output to echo, this is the default
	function outputToEcho()
	{
		if( $this->output_state == 'init' ) {
			$this->close();
			$this->output_type = 'echo';
			return 0; // success
		}
		return -1; // error
	}
	
	// add or search a color, return the color index. the first color added by 
	// the user has the index 1, index 0 is always the auto color. color 
	// values should be given in HMTL format as '#FF0000' for 'red', '' for auto 
	// color.
	function defineColor($rgb)
	{
		// get RTF color
		if( substr($rgb, 0, 1) == '#' && strlen($rgb)==7 ) {
			list($r, $g, $b) = sscanf($rgb, "#%02x%02x%02x");
			$rtfcolor = "\\red$r\\green$g\\blue$b";
		}
		else {
			$rtfcolor = '';
		}
		
		// search color
		for( $i = 0; $i < sizeof((array) $this->colors); $i++ ) {
			if( $this->colors[$i] == $rtfcolor ) {
				return $i; // success
			}
		}
	
		// add color
		if( $this->output_state == 'init' ) {
			$this->colors[] = $rtfcolor;
			return sizeof($this->colors)-1; // success
		}
		
		// cannot add, use default color
		return 0; 
	}
	
	// define a font family, the standard font families are already defined
	// by default. to find out a font family for a font, you can ommit the 
	// second parameter.
	function defineFontFamily($fontname, $fontfamily = '')
	{
		if( $fontfamily ) {
			$this->fontFamilies[$fontname] = $fontfamily;
			return $fontfamily;
		}
		else {
			$fontfamily = $this->fontFamilies[$fontname];
			return $fontfamily? $fontfamily : 'swiss';
		}
	}
	
	// add for find a font, the first font added by the user has the index 0.
	function defineFont($fontname)
	{
		$fontfamily = $this->defineFontFamily($fontname);
		
		if( $this->output_state == 'init' ) {
			$this->fonts[] = "$fontfamily $fontname";
			return sizeof($this->fonts)-1;
		}
		
		return 0; // default
	}
	
	// the first style added by the user has the index 1
	function defineStyle($name, $type = '', $format = '')
	{
		// already defined?
		if( $this->styles[$name] || $type == '' ) {
			return intval($this->styles_index[$name]); // already defined
		}
		
		if( $this->output_state == 'init' ) {
			$this->styles[$name]		= $format;
			$this->styles_index[$name]	= sizeof($this->styles)-1;
			$this->styles_type[$name]	= $type;
			return $this->styles_index[$name]; // success
		}
		
		return 0; // default
	}
	
	// 1 cm ~ 567 tab units
	function defineGlobalTab($tabunits)
	{
		if( $this->output_state == 'init' ) {
			$this->global_tabs[] = $tabunits;
		}
		return -1; // error
	}


	//
	// PUBLIC STUFF: RTF Output Functions
	//
	///////////////////////////////////////////////////////////////////////////

	// start a section
	function wSect()
	{
		$this->initialize();

		$this->w('{');
		$this->sect_counter++;
	}
	
	// end a section
	function wSectEnd()
	{
		$this->initialize();

		if( $this->sect_counter > 0 ) {
			$this->w("}\n");
			$this->sect_counter--;
		}
	}

	// switch to the given paragraph style
	function wStyle($stylename)
	{
		$this->initialize();

		$index = intval($this->styles_index[$stylename]);

		if( $this->styles_type[$stylename] == 'para' ) {
			$this->w("\\s");
		}
		else {
			$this->w("\\cs");
		}
		
		$this->w($index);
		if( $this->write_style_format ) {
			$this->w($this->styles[$stylename]);
		}
		$this->w(' ');
	}

	// function writes out unquoted, simple text
	function wText($text) 
	{
		$this->initialize();
		
		$this->w(strtr($text, $this->text_transl));
	}

	// function writes a RTF paragraph break
	function wBreak() 
	{
		$this->initialize();

		$this->w("\\par ");
	}
	
	// function writes a RTF tab
	function wTab() 
	{
		$this->initialize();
		
		$this->w("\\tab ");
	}
	
	// function writes out the text in the given character style
	function wSymbol($symbol, $symbolfont = '') 
	{
		$this->initialize();

		if( !$symbolfont ) {
			$symbolfont = 'Wingdings';
		}
		$this->initialize();
		$this->w("{\\field{\\*\\fldinst SYMBOL $symbol \\\\f \"$symbolfont\"}}");
	}

	// function writes out an RTF command
	function wCmd($cmd)
	{
		$this->initialize();

		$cmd = trim($cmd) . ' ';
		$this->w($cmd);
	}

	// finalize the RTF file
	function wFinalize()
	{
		if( $this->output_state != 'finalized' ) 
		{
			$this->initialize();

			for( $i = 0; $i < $this->sect_counter; $i++ ) {
				$this->w('}');
			}
			
			$this->w('}');
			$this->close();
			$this->output_state = 'finalized';
			return 0; // success
		}
		return -1; // error
	}

	//
	// PRIVATE STUFF
	//
	
	// create a new RTF-Writer
	function RTF_WRITER_CLASS()
	{
		$this->output_state = 'init';
		$this->output_type	= 'echo';
		
		$this->colors = array();
		$this->defineColor('' /*auto color*/);
		
		$this->fonts = array();
		$this->defineFontFamily('Arial',			'swiss');
		$this->defineFontFamily('Arial Narrow',		'swiss');
		$this->defineFontFamily('Arial Black',		'swiss');
		$this->defineFontFamily('Courier',			'modern');
		$this->defineFontFamily('Courier New',		'modern');
		$this->defineFontFamily('Garamond',			'roman');
		$this->defineFontFamily('Helvetica',		'swiss');
		$this->defineFontFamily('Times New Roman',	'roman');
		$this->defineFontFamily('Verdana',			'swiss');
		
		$this->styles					= array();
		$this->styles_index				= array();
		$this->styles_type				= array();
		$this->styles['Normal']			= '\f0\fs16\cf0';
		$this->styles_index['Normal']	= 0;
		$this->styles_type['Normal']	= 'para';
		
		$this->write_style_format		= 1;

		$this->global_tabs		= array();

		$this->text_transl 		= array
		(
			// always needed RTF character translations
				"\\"	 => "\\\\"		
			,	"{"	 	 => "\\{"		
			,	"}"	 	 => "\\}"		
			
			// needed for DOS to ANSI
			/*
			,	chr(132) => chr(228)	// ae
			,	chr(148) => chr(246)	// oe
			,	chr(129) => chr(252)	// ue
			,	chr(142) => chr(196)	// AE
			,	chr(153) => chr(214)	// OE
			,	chr(154) => chr(220)	// UE
			,	chr(225) => chr(223)	// sz
			*/
		);
		
		$this->sect_counter = 0;
	}

	// initialize the RTF file
	function initialize()
	{
		if( $this->output_state == 'init' ) 
		{
			$this->w('{\rtf1\ansi\deff0' . "\n");
			$this->w("{\\comment File created by PHP and RTF_WRITER_CLASS, see http:/"."/pocoso.de/ }\n");
			$this->w("{\\comment File creation date is " . strftime("%Y-%m-%d %H:%M:%S") . " }\n");
			
			// fonttable
			if( sizeof((array) $this->fonts) == 0 ) {
				$this->defineFont('Arial');
			}
			
			if( $this->write_style_format ) {
				$this->w("{\\fonttbl\n");
				for( $i = 0; $i < sizeof((array) $this->fonts); $i++ ) {
						$this->w('{\f' . $i . '\f' . $this->fonts[$i] . ";}\n");
					}
				$this->w("}\n");
			}
			
			// color table
			$this->w("{\colortbl\n");
			    for( $i = 0; $i < sizeof((array) $this->colors); $i++ ) {
					$this->w($this->colors[$i] . ";\n");
				}
			$this->w("}\n");
			
			
			// styles, let '\snext' point to the style itself
			$this->w('{\stylesheet' . "\n");
			$this->w('{\s0\sbasedon222\snext0\widctlpar');
			if( $this->write_style_format ) {
				$this->w($this->styles['Normal']);
			}
			$this->w(" Normal;}\n");
				
				reset($this->styles);
				foreach($this->styles as $name => $style) {
					$index = $this->styles_index[$name];
					if( $index > 0 ) {
						if( $this->styles_type[$name] == 'para' ) {
							$this->w('{\s' . $index . '\sbasedon0\snext' . $index);
							if( $this->write_style_format ) {
								$this->w($style);
							}
							$this->w(' ' . $name . ";}\n");
						}
						else {
							$this->w('{\*\cs' . $index  . '\additive');
							if( $this->write_style_format ) {
								$this->w($style);
							}
							$this->w(' ' . $name . ";}\n");
						}
					}
				}

			$this->w("}\n");

			// further common initialisations
			$this->w("\\pard\\plain");
			if( $this->write_style_format ) {
				$this->w("\\fs16");
			}
			$this->w("\n");
		
			// tabs
			global $debug_count;
			if( $this->write_style_format || $debug_count ) {
			    for( $i = 0; $i < sizeof((array) $this->global_tabs); $i++ ) {
					$this->w('\tx' . $this->global_tabs[$i]);
				}
				if( sizeof((array) $this->global_tabs) ) {
					$this->w("\n");
				}
			}
			
			
			// change state
			$this->output_state = 'data';
		}
	}

	// write a RTF command or quoted text, for user commands use wCmd() which
	// will add a space to the command if needed
	function w($data)
	{
		if( $this->output_type == 'file' ) {
			fwrite($this->output, $data);
		}
		else if( $this->output_type == 'string' ) {
			$this->output .= $data;
		}
		else {
			echo $data;
		}
	}

	// close output
	function close()
	{
		if( $this->output_type == 'file' ) {
			fclose($this->output);
			$this->output = 0;
		}
		else if( $this->output_type == 'echo' ) {
			flush();
		}
	}
}




//
// plugin class
//
///////////////////////////////////////////////////////////////////////////////



class EXP_FORMATTMALLERNEN_CLASS extends EXP_PLUGIN_CLASS
{
	function __construct()
	{
		parent::__construct();
	
		$db1 = new DB_Admin;
		$db1->query("SELECT id, kuerzel, thema FROM themen WHERE kuerzel_sorted LIKE '__________' ORDER BY kuerzel_sorted");
		while( $db1->next_record() ) {
			$this->options['hauptthemen'.$db1->f('id')]
									=	array('enum', isohtmlentities($db1->fs('thema')), '1',
											  '1###Kurse exportieren###'
											 .'3###Kurse zzgl. noch ausstehender Beginndaten exportieren###'
											 .'7###Kurse zzgl. auch abgelaufener Beginndaten exportieren###'
											 .'0###Themengebiet auslassen');
		}

		$this->options['refdate']	=	array('text', 'Referenzdatum', '', 10);
		$this->options['dummy2']	=	array('remark', 'Beginndaten <i>nach</i> diesem Datum werden bei der Einstellung &quot;Kurse zzgl. noch ausstehender Beginndaten&quot; exportiert. Geben Sie das Datum in der Form &quot;tt.mm.jjjj&quot; ein oder lassen Sie das Feld leer um das heutige Datum zu verwenden.');

		$this->options['linelen']	=	array('text', 'max. Zeilenl&auml;nge', '50', 3);
		$this->options['dummy3']	=	array('remark', 'F&uuml;r die max. Zeilenl&auml;nge hat sich ein Wert um 50 bew&auml;hrt.');
		$this->options['anbanb']	=	array('check', 'Anbieter ', 1, 'Anbieter exportieren');
		$this->options['anbtrainer']=	array('check', 'Trainer ', 0);
		$this->options['anbberatungsstellen']	
									=	array('check', 'Beratungsstellen ', 0);
		$this->options['anbverw']	=	array('check', 'Namensverweisungen', 1);
		
		global $codes_stichwort_eigenschaften;
		$temp = explode('###', $codes_stichwort_eigenschaften);
		for( $i = 0; $i < sizeof($temp); $i+=2 ) {
			$this->options['stichw'.$temp[$i]] = array('check', $temp[$i+1].($i==sizeof($temp)-2?'':' '), 1, $i==0? 'Stichw&ouml;rter exportieren' : '');
		}

		$allgroups = acl_get_all_groups();
		for( $i = 0; $i < sizeof((array) $allgroups); $i++ )
		{
			$this->options["group{$allgroups[$i][0]}"] = array('check', 
				"{$allgroups[$i][1]} ", /*trailing space->no wrap*/
				1, 
				$i==0? 'Gruppen exportieren' : '');
		}		
		$this->options["group0"] = array('check', "Andere/Unbekannt", 1, '');
		
		$this->options['themennr']	=	array('check', 'Themenk&uuml;rzel vor Themen&uuml;berschrift schreiben', 0, 'Sonstige Einstellungen');
		$this->options['physformat']=	array('check', 'zus. zu den Formatvorlagen Standard-Formatierung schreiben', 0);
		$this->options['test']		=	array('check', 'Testduchlauf mit nur wenigen Datens&auml;tzen', 0);
	}



	function log($txt1, $txt2 = '')
	{
		global $site;
		if( $txt2==='' ) {
			$this->dumps[2] .= $site->htmldeentities($txt1) . "\n";
		}
		else {
			$txt1 = substr($site->htmldeentities($txt1), 0, 35);
			$this->dumps[2] .= str_pad($txt1.' ', 38, '.') . ' '. $site->htmldeentities($txt2) . "\n";
		}
	}
	


	//
	// write a single thema as RTF
	//
	function writeThemaHeadline($newThemaId, $lastThemaId)
	{
		// get last theme
		$this->db2->query("SELECT kuerzel_sorted FROM themen WHERE id=$lastThemaId");
		$this->db2->next_record();
		$temp = $this->db2->f('kuerzel_sorted');
		$lastLevel = array(); 
		for( $i=0; $i < strlen($temp); $i+=10 ) $lastLevel[] = substr($temp, $i, 10);
		
		// get new theme
		$this->db2->query("SELECT kuerzel_sorted FROM themen WHERE id=$newThemaId");
		$this->db2->next_record();
		$temp = $this->db2->f('kuerzel_sorted');
		$newLevel = array(); 
		for( $i=0; $i < strlen($temp); $i+=10 ) $newLevel[] = substr($temp, $i, 10);
		
		// write theme
		$kuerzel_sorted = '';
		for( $i = 0; $i < sizeof($newLevel); $i++ )
		{
			$kuerzel_sorted .= $newLevel[$i];

			if( $newLevel[$i] != $lastLevel[$i] )
			{
				// load thema
				$this->db2->query("SELECT kuerzel, thema FROM themen WHERE kuerzel_sorted='$kuerzel_sorted'");
				if( !$this->db2->next_record() ) {
					$this->log("FEHLER: Thema mit standardisiertem Kuerzel $kuerzel_sorted nicht gefunden.");
					return 0;
				}
			
				$kuerzel= $this->db2->f('kuerzel');
				$thema	= $this->db2->f('thema');
			
				// write this level
				$this->fp->wSect();
					$this->fp->wStyle('Überschrift '.strval($i+1));

					if( $this->themennr ) {
						$this->fp->wText($kuerzel.' ');
					}

					$thema1stLetter	= substr($thema, 0, 1);
					$thema			= substr($thema, 1);
					
					$this->fp->wSect();
						$this->fp->wStyle('Überschrift '.strval($i+1). ' Erster Buchstabe');
						$this->fp->wText($thema1stLetter);
					$this->fp->wSectEnd();
					
					$this->fp->wText($thema);
		
					$this->fp->wBreak();
				$this->fp->wSectEnd();
			}
		}
		
		return 1;
	}


	//
	// write a single anbieter as RTF
	//
	function writeAnbieterHeadline($anbieterId)
	{
		// load data
		$this->db2->query("SELECT suchname, anspr_tel, din_nr FROM anbieter WHERE id=$anbieterId");
		if( !$this->db2->next_record() ) {
			$this->log("FEHLER: Anbieter ID $anbieterId nicht gefunden.");
			return 0;
		}
	
		$suchname	= trim($this->db2->fs('suchname'));
		$anspr_tel	= trim($this->db2->fs('anspr_tel'));
		$din_nr		= trim($this->db2->fs('din_nr'));

		global $g_stichwPruefsiegel;		
		$pruefsiegel = 0;
		$this->db2->query("SELECT attr_id FROM anbieter_stichwort WHERE primary_id=$anbieterId AND attr_id=$g_stichwPruefsiegel");
		if( $this->db2->next_record() ) {
			$pruefsiegel = 1;
		}
	
		// write anbieter...
		$this->fp->wSect();
			$this->fp->wStyle('Veranstalter');
			
			// ...suchname
			$this->fp->wText($suchname . ' ');
			
			// ...guetesiegel
			if( $pruefsiegel ) {
				$this->fp->wSect();
					$this->fp->wStyle('Veranstalter Prüfsiegel');
					$this->fp->wText('(Prüfsiegel)');
				$this->fp->wSectEnd();
				$this->fp->wText(' ');
			}
			
			// telefon
			$text = $anspr_tel;
			$text = trim(str_replace('040/', '', $text));
			if( $text ) {
				$this->fp->wSect();
					$this->fp->wStyle('Veranstalter Telefon');
					$this->fp->wSymbol(0x28 /* Wingdings telephone */);
					$this->fp->wText($text);
				$this->fp->wSectEnd();
				$this->fp->wText(' ');
			}
	
			// VER_NR
			$this->fp->wSect();
				$this->fp->wStyle('Veranstalter WISY-Nr.');
				$text = $din_nr;
				$this->fp->wText('(Web:'.$text.')');
			$this->fp->wSectEnd();
			$this->fp->wText(' ');
	
			$this->fp->wBreak();
		$this->fp->wSectEnd();
	}

	
	
	//
	// write a single kurs as RTF
	//
	function writeKurs_KUR_NR_FO_BI_URLA($nr, $bu)
	{
		// nr
		$this->fp->wText(' ');
		$this->fp->wSect();
			$this->fp->wStyle('Kurs Nummer');
			$this->fp->wText('(' . $nr . ')');
		$this->fp->wSectEnd();
		
	
		// bu
		if( $bu ) {
			$this->fp->wText(' ');
			$this->fp->wSect();
				$this->fp->wStyle('Kurs Bildungsurlaub');
				$this->fp->wText('BU');
			$this->fp->wSectEnd();
		}
	}
	function writeKurs($kursId, $kursTitel, $themaId)
	{
		
		//
		// load kurs...
		//

		// ...stichwoerter / bildungsurlaub
		global $g_stichwBU;
		$bu = 0;
		$stichwIds = array();
		$this->db2->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kursId");
		while( $this->db2->next_record() ) {
			$temp = $this->db2->f('attr_id');
			if( $temp == $g_stichwBU ) {
				$bu = 1;
			}
			$stichwIds[] = $temp;
		}

		// ...durchf. IDs
		$durchfIds = array();
		$this->db2->query("SELECT secondary_id FROM kurse_durchfuehrung WHERE primary_id=$kursId ORDER BY structure_pos");
		while( $this->db2->next_record() ) {
			$durchfIds[] = $this->db2->f('secondary_id');
			$this->durchfAvailable++;
		}

		// durchf.
		$durchf = array();
		for( $i = 0; $i < sizeof($durchfIds); $i++ ) {
			$this->db2->query("SELECT nr, tagescode, dauer, stunden, preis, beginn FROM durchfuehrung WHERE id=".intval($durchfIds[$i]));
			if( $this->db2->next_record() ) {
				$nr			= trim($this->db2->fs('nr'));
				
				$tagescode	= intval($this->db2->f('tagescode'));
				if( $tagescode==2 /*vorm.*/|| $tagescode==3 /*nachm.*/ ) $tagescode = 1; /*ganzt.*/
				
				$dauer		= intval($this->db2->f('dauer'));
				$stunden	= intval($this->db2->f('stunden'));
				$preis		= intval($this->db2->f('preis'));
				$beginn		= $this->db2->f('beginn');
				
				$hash = "$tagescode-$dauer-$stunden-$preis";
				if( $durchf[$hash] ) 
				{
					$durchf[$hash]['nr'][]		= $nr;
					$durchf[$hash]['beginn'][]	= $beginn;
				}
				else 
				{
					$this->durchfWritten++;
					$durchf[$hash] = array
						(
							'tagescode'	=> $tagescode,
							'dauer'		=> $dauer,
							'stunden'	=> $stunden,
							'preis'		=> $preis,
							'nr' 		=> array($nr),
							'beginn'	=> array($beginn)
						);
				}
				
			}
		}
		
		if( sizeof($durchf) == 0 ) {
			$this->log("FEHLER: keine Durchführungs für Kurs ID $kursId ($kursTitel)");
			return;
		}

		// write beginn?
		$refdate = '';
		if( $this->themen[$themaId][0]&2 )
		{
			$refdate = $this->themen[$themaId][0]&4? '1900-01-01 00:00:00' : $this->refdate;
		}

		//
		// go through all durchf., write...
		//

		$KURS_TH = wordwrap($kursTitel.'########', $this->linelen, "\n", 1);
		$KURS_TH = trim(str_replace('########', '', $KURS_TH));
		$KURS_TH = explode("\n", $KURS_TH);

		reset($durchf);
		foreach($durchf as $dummy => $param)
		{
			$this->fp->wSect();
				$this->fp->wStyle('Kurs');
		
				// ..."quadrat"
			
				$this->fp->wSect();	
					$this->fp->wStyle('Kurs Quadrat');
					$this->fp->wSymbol(0x6E /* Wingdings quadrat */);
				$this->fp->wSectEnd();
				
				// ...titel (first line)
			
				$this->fp->wTab();
				$this->fp->wText($KURS_TH[0]);
			
				if( sizeof($KURS_TH) <= 1 ) {
					$this->writeKurs_KUR_NR_FO_BI_URLA($param['nr'][0], $bu);
				}
				
				// ...tagescode
				
				$text = '';
				$symbol = 0;
				$symbolfont = 'Wingdings';
				switch( $param['tagescode'] ) 
				{
					case 1: // ganztägig
					case 2: // vorm.
					case 3: // nachm
						$symbolfont = 'Wingdings 2';
						$symbol = 0x98;
						break;
		
					case 4: // abends
						$symbolfont = 'Wingdings 2';
						$symbol = 0xBB;
						break;
					
					case 5: // wochenende
						$text = 'W';
						break;
		
					case 6: // fernunterr.
						$symbol = 0x2A;
						break;
	
					default:
						break;
				}
				
				$this->fp->wTab();
				if( $text || $symbol ) {
					$this->fp->wSect();
						$this->fp->wStyle('Kurs Code');
						if( $text ) {
							$this->fp->wText($text);
						}
						if( $symbol ) {
							$this->fp->wSymbol($symbol, $symbolfont);
						}
					$this->fp->wSectEnd();
				}
		
				// dauer
				
				$dauer = $param['dauer'];
				$stunden = $param['stunden'];
				
				$text = '';
				if( $dauer ) {
					if( $this->dauer[$dauer] ) {
						$text = $this->dauer[$dauer];
					}
					else {
						$text = "$dauer Tg";
					}
				}
				
				if( $stunden ) {
					$text .= $text? ' ' : '';
					$text .= "$stunden Std";
				}
				
				$this->fp->wTab();
				$this->fp->wSect();
					$this->fp->wStyle('Kurs Dauer');
					$this->fp->wText($text);
				$this->fp->wSectEnd();
				
				
				// preis
				
				$text = $param['preis'];
				if( $text < 0 ) {
					$text = '??'; // unknown
				}
				$text .=  ' €';
				
				$this->fp->wTab();
				$this->fp->wSect();
					$this->fp->wStyle('Kurs Preis');
					$this->fp->wText($text);
				$this->fp->wSectEnd();
				
				// ...linebreak
				
				$this->fp->wBreak();
				
				// ...titel (other lines)
		
				if( sizeof($KURS_TH) > 1 ) {
					for( $i = 1; $i < sizeof($KURS_TH); $i++ ) {
						$this->fp->wTab();
						$this->fp->wText($KURS_TH[$i]);
						if( $i == sizeof($KURS_TH)-1 ) {
							$this->writeKurs_KUR_NR_FO_BI_URLA($param['nr'][0], $bu);
						}
						$this->fp->wBreak();
					}
				}
			
				// ...termine
			
				if( $refdate ) 
				{
					$termine = array();
					for( $i = 0; $i < sizeof((array) $param['beginn']); $i++ ) {
						if( $param['beginn'][$i]
						 && $param['beginn'][$i] != '0000-00-00 00:00:00' 
						 && $param['beginn'][$i] >= $refdate ) {
							$termine[$param['beginn'][$i]] = 1;
						}
					}
					
					if( sizeof((array) $termine) )
					{
					    $this->fp->wTab();
					    $this->fp->wText(sizeof((array) $termine)==1? 'Termin: ' : 'Termine: ');
					    
					    ksort($termine);
					    reset($termine);
					    $i = 0;
					    foreach(array_keys($termine) as $termin)
						{
							if( $i ) { $this->fp->wText(', '); }
						
							$termin = strtr($termin, ' :', '--');
							$termin = explode('-', $termin);
							$this->fp->wText($termin[2].'.'.$termin[1].'.'.substr($termin[0], 2, 2));
							
							$i++;
						}
						
						$this->fp->wBreak();
					}
				}
			
			$this->fp->wSectEnd();
		}
	
		//
		// Stichwoerter / Abschluesse
		//
	
		for( $i = 0; $i < sizeof((array) $stichwIds); $i++ ) {
			if( is_array($this->stichw[$stichwIds[$i]]) ) {
				$this->fp->wSect();
					$this->fp->wCmd($this->stichw[$stichwIds[$i]][1]&1? '\xe\v\b' : '\xe\v');
					$this->fp->wText($this->stichw[$stichwIds[$i]][0]);
					$this->stichw[$stichwIds[$i]][2]++;
				$this->fp->wSectEnd();
			}
		}
	}
	
	
	
	//
	// start the export
	//
	function export($param)
	{
		//
		// init files...
		//
		
		
		$this->dumps[0] = "";
		$this->names[0] = "wisy.rtf";
		
		// ...anbieter liste
		$this->dumps[1] = "";
		$this->names[1] = "wisyanb.rtf";

		// ...init logfile
		$this->dumps[2] = "";
		$this->names[2] = "protokoll.txt";
		$this->log('1001 mal Lernen Export vom '.strftime("%d.%m.%Y, %H:%M Uhr"));
		$this->log('Programm von Bjoern Petersen - bpetersen at b44t dotcom');
		$this->log('=======================================================');
		$this->log('');

		//
		// connect to database
		//
		
		
		$db1 = new DB_Admin;
		$this->db2 = new DB_Admin;
		
		
		//
		// get settings...
		//
		
		
		$this->log('Einstellungen:');

		// ...scopes
		$db1->query("SELECT id, kuerzel, thema, kuerzel_sorted FROM themen WHERE kuerzel_sorted LIKE '__________' ORDER BY kuerzel_sorted");
		while( $db1->next_record() ) {
			$this->hauptthemen[$db1->f('kuerzel_sorted')] = intval($param['hauptthemen'.$db1->f('id')]);
			$this->log($db1->fs('kuerzel').' '.$db1->fs('thema'), $this->hauptthemen[$db1->f('kuerzel_sorted')]);
		}
		
		// ...reference date
		$this->refdate = $param['refdate']? $param['refdate'] : strftime("%d.%m.%Y");
		$this->refdate = sql_date_from_human($this->refdate);
		$this->log('Referenzdatum', $this->refdate);
		
		// ...max. line length
		$this->linelen = intval($param['linelen']);
		if( $this->linelen < 5 || $this->linelen > 500 ) $this->linelen = 50;
		$this->log('max. Zeilenlaenge', $this->linelen);
		
		// ...themennr.
		$this->themennr = $param['themennr']? 1 : 0;
		$this->log('Themennummer ausgeben', $this->themennr);

		// ...anbanb
		$this->anbanb = $param['anbanb']? 1 : 0;
		$this->log('Anbieter exportieren', $this->anbanb);

		// ...anbtrainer
		$this->anbtrainer = $param['anbtrainer']? 1 : 0;
		$this->log('Trainer exportieren', $this->anbtrainer);

		// ...anbberatungsstellen
		$this->anbberatungsstellen = $param['anbberatungsstellen']? 1 : 0;
		$this->log('Bertatungsstellen exportieren', $this->anbberatungsstellen);

		// ...anbverw
		$this->anbverw = $param['anbverw']? 1 : 0;
		$this->log('Namensverweisungen exportieren', $this->anbverw);

		// ...physformat
		$this->physformat = $param['physformat']? 1 : 0;
		$this->log('Standard-Formatierung schreiben', $this->physformat);

		// ...test
		$this->test = $param['test']? 1 : 0;
		$this->log('Test', $this->test);

		// ...settings done
		$this->log('');
		
		
		//
		// build themes hash
		//
		$this->progress_info("lade Themen...");
		$this->themen = array();
		$db1->query("SELECT id, kuerzel, kuerzel_sorted, thema FROM themen ORDER BY kuerzel_sorted");
		while( $db1->next_record() ) {
			
			$this->themen[$db1->f('id')] = array(
					intval($this->hauptthemen[substr($db1->f('kuerzel_sorted'), 0, 10)]),
					$db1->fs('kuerzel'),
					$db1->fs('thema')
				);
		}
		
		
		//
		// build stichwoerter hash
		//
		$this->progress_info("lade Stichwörter...");
		$this->stichw = array();
		$db1->query("SELECT id, stichwort, eigenschaften FROM stichwoerter");
		while( $db1->next_record() ) {
			$eigenschaften = intval($db1->f('eigenschaften'));
			if( $param["stichw$eigenschaften"] ) {
				$this->stichw[$db1->f('id')] = array(
						$db1->fs('stichwort'),
						$eigenschaften,
						0
					);
			}
		}
		

		//
		// build dauer hash
		//
		global $codes_dauer;
		$this->dauer = array();
		$temp = explode('###', $codes_dauer);
		for( $i = 0; $i < sizeof($temp); $i+=2 ) {
			$this->dauer[$temp[$i]] = strtr($temp[$i+1], array('Tage'=>'Tg', 'Wochen'=>'Wo', 'Monate'=>'Mon', 'Semester'=>'Sem', 'Jahre'=>'J'));
		}


		//
		// init kurse RTF
		//
	
		$this->fp = new RTF_WRITER_CLASS();
		if( $this->fp->outputToString($this->dumps[0]) != 0 ) {
			$this->progress_abort("Kann Kurs RTF-Datei nicht initialisieren.");
		}

		$this->fp->write_style_format = $this->physformat;
		$this->fp->defineColor('#FF0000'); // cf1
		$this->fp->defineColor('#0000FF'); // cf2
		$this->fp->defineFont('Arial Narrow'); // f0
		$this->fp->defineGlobalTab(250); 
		$this->fp->defineGlobalTab(7087); 
		$this->fp->defineGlobalTab(7371); 
		$this->fp->defineGlobalTab(8505); 
		$this->fp->defineStyle('Überschrift 1',						'para',	'\f0\fs24\cf1\b'	);
		$this->fp->defineStyle('Überschrift 2',						'para',	'\f0\fs24\cf1\b'	);
		$this->fp->defineStyle('Überschrift 3',						'para',	'\f0\fs24\cf1\b'	);
		$this->fp->defineStyle('Überschrift 1 Erster Buchstabe',	'char',	'\f0\fs34\cf1\b'	);
		$this->fp->defineStyle('Überschrift 2 Erster Buchstabe',	'char',	'\f0\fs34\cf1\b'	);
		$this->fp->defineStyle('Überschrift 3 Erster Buchstabe',	'char',	'\f0\fs34\cf1\b'	);
		$this->fp->defineStyle('Erster Buchstabe',					'char',	'\f0\fs30\cf1\b'	);
		$this->fp->defineStyle('Veranstalter',						'para',	'\cf0\f0\fs16\b'	);
		$this->fp->defineStyle('Veranstalter Prüfsiegel',			'char',	'\cf1' 				);
		$this->fp->defineStyle('Veranstalter Telefon',				'char',	'\cf0' 				);
		$this->fp->defineStyle('Veranstalter WISY-Nr.',				'char',	'\cf2'				);
		$this->fp->defineStyle('Kurs',								'para',	'\f0\fs16\cf0' 		);
		$this->fp->defineStyle('Kurs Quadrat',						'char',	'\f0\fs16\cf1'		);	
		$this->fp->defineStyle('Kurs Nummer',						'char',	'\f0\fs16\cf2' 		);	
		$this->fp->defineStyle('Kurs Bildungsurlaub',				'char',	'\f0\fs16\cf2\b' 	);
		$this->fp->defineStyle('Kurs Code',							'char',	'\f0\fs16\cf0' 		);
		$this->fp->defineStyle('Kurs Dauer',						'char',	'\f0\fs16\cf0' 		);	
		$this->fp->defineStyle('Kurs Preis',						'char',	'\f0\fs16\cf0' 		);

		
		//
		// go through all kurse
		//

		$recWritten = 0;
		$recSkipped = 0;
		$recErrors  = 0;
		$themenWritten = 0;
		$this->durchfAvailable = 0;
		$this->durchfWritten = 0;
		
		$lastThemaId = -1;
		$lastAnbieterId = -1;

		$allgroups = acl_get_all_groups();		
		$allgroupIds = array();
		for( $i = 0; $i < sizeof($allgroups); $i++ )
		{
			$allgroupIds[] = $allgroups[$i][0];
		}
		
		
		$db1->query("SELECT kurse.id, kurse.freigeschaltet, kurse.thema, kurse.anbieter, kurse.titel, kurse.user_grp FROM kurse LEFT JOIN themen ON kurse.thema=themen.id LEFT JOIN anbieter ON kurse.anbieter=anbieter.id ORDER BY themen.kuerzel_sorted, anbieter.suchname_sorted, kurse.titel_sorted, kurse.id");
		while( $db1->next_record() )
		{
			// get theme
			$kursId			= intval($db1->f('id'));
			$kursTitel		= trim($db1->fs('titel'));
			$themaId		= intval($db1->f('thema'));
			$anbieterId		= intval($db1->f('anbieter'));
			$group			= intval($db1->f('user_grp'));
			$freigeschaltet = intval($db1->f('freigeschaltet'));
			
			// convert unknwown group to group #0
			if( !in_array($group, $allgroupIds) ) { $group = 0; }
			
			if( $themaId <= 0 )
			{
				// kein thema gesetzt
				$this->log("FEHLER: kein Thema angegeben fuer Kurs ID $kursId ($kursTitel)");
				$recErrors++;
			}
			else if( $kursTitel === '' ) 
			{
				// kein titel gesetzt
				$this->log("FEHLER: kein Titel angegeben fuer Kurs ID $kursId");
				$recErrors++;
			}
			else if( ($freigeschaltet==1 || $freigeschaltet==4)
			      && $this->themen[$themaId][0]
				  && $param["group{$group}"] )
			{
				// thema schreiben
				if( $lastThemaId != $themaId ) {
					$this->writeThemaHeadline($themaId, $lastThemaId);
					$lastThemaId = $themaId;
					$lastAnbieterId = -1;
					$themenWritten++;
				}
				
				// anbieter schreiben
				if( $lastAnbieterId != $anbieterId ) {
					$this->writeAnbieterHeadline($anbieterId);
					$lastAnbieterId = $anbieterId;
				}
			
				// kurs schreiben
				$this->writeKurs($kursId, $kursTitel, $themaId);
				
				// progress information
				$recWritten++;
				if( ($recWritten % 500) == 0 ) {
					$this->progress_info("$recWritten Kurse bearbeitet...");
				}
				
				// test run?
				if( $this->test && $recWritten >= 1000 ) {
					break;
				}
			}
			else 
			{
				$recSkipped++;
			}
		}
		
		
		//
		// synonyme
		//
		if( $param['stichw64'] )
		{
			$this->progress_info("exportiere Synonyme...");
			$db2 = new DB_Admin;
			$db1->query("SELECT id, stichwort FROM stichwoerter WHERE eigenschaften=64");
			while( $db1->next_record() )
			{	
				$targets = array();
				$db2->query("SELECT attr_id FROM stichwoerter_verweis WHERE primary_id=".$db1->f('id'));
				while( $db2->next_record() ) 
				{
					$temp = $db2->f('attr_id');
					if( is_array($this->stichw[$temp]) && $this->stichw[$temp][2] > 0 ) {
						$targets[] = $temp;
					}
				}
				
				if( sizeof($targets) ) 
				{
					$this->fp->wSect();
						$this->fp->wCmd('\xe\v\i');
						$this->fp->wText($db1->fs('stichwort'));
						$this->fp->wSect();
							$this->fp->wCmd('\txe');
							for( $i = 0; $i < sizeof($targets); $i++ )
							{
								$db2->query("SELECT stichwort FROM stichwoerter WHERE id={$targets[$i]}");
								$db2->next_record();
								$this->fp->wText(($i? ', ' : 's. ').$db2->fs('stichwort'));
							}
						$this->fp->wSectEnd();
					$this->fp->wSectEnd();
				}
			}
		}
		
		
		
		//
		// exit kurs RTF
		//
		$this->fp->wFinalize();
		$this->fp = 0;


		//
		// write anbieter file
		//
		$this->writeAnbieterFile();


		//
		// statistik
		//
		$this->log('');
		$this->log('Statistik:');
		$this->log('geschriebene Kurse',				$recWritten);
		$this->log('ausgelassene Kurse',				$recSkipped);
		$this->log('fehlerhafte Kurse',					$recErrors);
		$this->log('bearbeitete Durchführungen',		$this->durchfAvailable);
		$this->log('geschriebene Durchführungen',		$this->durchfWritten);
		$this->log('geschriebene Anbieter',				$this->anbieterWritten);
		$this->log('geschriebene Trainer',				$this->trainerWritten);
		$this->log('geschriebene Beratungsstellen',		$this->beratungsstellenWritten);
		$this->log('geschriebene Namensverweisungen',	$this->anbieterverweiseWritten);
		$this->log('geschriebene Themen',				$themenWritten);
		
		//
		// erstelle die ZIP-Datei
		//
		$zipfile = new EXP_ZIPWRITER_CLASS($this->allocateFileName('1001-mal-lernen.zip'));
		for( $i = 0; $i < sizeof((array) $this->names); $i++ )
		{
			if( !$zipfile->add_data($this->dumps[$i], $this->names[$i]) )
				$this->progress_abort('cannot write zip');
		}
		if( !$zipfile->close() )
			$this->progress_abort('cannot close zip');
		
	}
	
	
	//
	// create the complete anbieter RTF
	//
	function writeAnbieterFile()
	{
		//
		// init anbieter RTF
		//
		$this->fp = new RTF_WRITER_CLASS();
		if( $this->fp->outputToString($this->dumps[1]) != 0 ) {
			$this->progress_abort("Kann Anbieter RTF-Datei nicht initialisieren.");
		}
		
		$this->fp->write_style_format = $this->physformat;
		$this->fp->defineColor('#FF0000'); // cf1
		$this->fp->defineColor('#0000FF'); // cf2
		$this->fp->defineFont('Arial Narrow'); // f0
		
		$this->fp->defineStyle('Anbieter',						'para',	'\cf0\f0\fs16'		);
		$this->fp->defineStyle('Anbieter Name',					'char',	'\b' 				);
		$this->fp->defineStyle('Anbieter Prüfsiegel',			'char',	'\cf1' 				);
		$this->fp->defineStyle('Anbieter WISY-Nr.',				'char',	'\cf2'				);

		$this->fp->defineStyle('Anbieter Prüfs.',				'para',	'\cf0\f0\fs16'		);
		$this->fp->defineStyle('Anbieter Prüfs. Name',			'char',	'\b' 				);
		$this->fp->defineStyle('Anbieter Prüfs. Prüfsiegel',	'char',	'\cf1' 				);
		$this->fp->defineStyle('Anbieter Prüfs. WISY-Nr.',		'char',	'\cf2'				);
		
		$this->fp->defineStyle('Trainer',						'para',	'\cf0\f0\fs16'		);
		$this->fp->defineStyle('Trainer Name',					'char',	'\b' 				);
		$this->fp->defineStyle('Trainer Prüfsiegel',			'char',	'\cf1' 				);
		$this->fp->defineStyle('Trainer WISY-Nr.',				'char',	'\cf2'				);
		
		$this->fp->defineStyle('Beratungsstelle',				'para',	'\cf0\f0\fs16'		);
		$this->fp->defineStyle('Beratungsstelle Name',			'char',	'\b' 				);
		$this->fp->defineStyle('Beratungsstelle Prüfsiegel',	'char',	'\cf1' 				);
		$this->fp->defineStyle('Beratungsstelle WISY-Nr.',		'char',	'\cf2'				);
		
		$this->fp->defineStyle('Namensverweisung',				'para',	'\cf1\f0\fs16'		);
		$this->fp->defineStyle('Namensverweisung Name',			'char',	'\b' 				);

		$this->fp->defineStyle('Leer',							'para',	'\cf0\f0\fs16'		);

		//
		// go through all anbieter
		//

		global $g_stichwPruefsiegel;
		
		$db = new DB_Admin;
		$db->query("SELECT id, typ, din_nr, suchname, anspr_tel, anspr_fax, anspr_email, homepage, strasse, plz, ort FROM anbieter WHERE freigeschaltet=1 ORDER BY suchname_sorted");
		$this->anbieterWritten = 0;
		$this->trainerWritten = 0;
		$this->beratungsstellenWritten = 0;
		$this->anbieterverweiseWritten = 0;
		$recCount = 0;
		while( $db->next_record() )
		{
			// load anbieter
			$anbieterId		= intval($db->f('id'));
			$typ			= intval($db->f('typ'));
			$suchname		= trim($db->fs('suchname'));
			$din_nr			= trim($db->fs('din_nr'));
			$anspr_tel		= trim($db->fs('anspr_tel'));
			$anspr_fax		= trim($db->fs('anspr_fax'));
			$anspr_email	= trim($db->fs('anspr_email'));
			$strasse		= trim($db->fs('strasse'));
			$plz			= trim($db->fs('plz'));
			$ort			= trim($db->fs('ort'));
			$homepage		= trim($db->fs('homepage'));

			$pruefsiegel = 0;
			$this->db2->query("SELECT attr_id FROM anbieter_stichwort WHERE primary_id=$anbieterId AND attr_id=$g_stichwPruefsiegel");
			if( $this->db2->next_record() ) {
				$pruefsiegel = 1;
			}
		
			$verweis = 0;
			$this->db2->query("SELECT attr_id FROM anbieter_verweis WHERE primary_id=$anbieterId");
			if( $this->db2->next_record() )
			{
				if( $this->anbverw )
				{
					$this->db2->query("SELECT suchname, typ FROM anbieter WHERE id=".$this->db2->f('attr_id'));
					$this->db2->next_record();
					$target = $this->db2->fs('suchname');
					$targetTyp = intval($this->db2->f('typ'));
				
					if( ($targetTyp==0 && $this->anbanb)
					 || ($targetTyp==1 && $this->anbtrainer)
					 || ($targetTyp==2 && $this->anbberatungsstellen) )
					{
						// write verweisung
						$this->fp->wSect();
							$this->fp->wStyle('Namensverweisung');
							
							$this->fp->wSect();
								$this->fp->wStyle('Namensverweisung Name');
								$this->fp->wText($suchname);
							$this->fp->wSectEnd();
							
							$this->fp->wText(" ");
							$this->fp->wSymbol(0xe0 /* arrow */);
							$this->fp->wText(" $target");
							$this->fp->wBreak();
							
						$this->fp->wSectEnd();
						
						// space
						$this->fp->wSect();
							$this->fp->wStyle('Leer');
							$this->fp->wBreak();
						$this->fp->wSectEnd();
						
						$this->anbieterverweiseWritten++;
					}
				}
			}
			else if( ($typ==0 && $this->anbanb)
				  || ($typ==1 && $this->anbtrainer)
				  || ($typ==2 && $this->anbberatungsstellen) )
			{
				// write anbieter
				if( $typ == 1 ) {
					$this->trainerWritten++;
					$paraStyle = 'Trainer';
				}
				else if( $typ == 2 ) {
					$this->beratungsstellenWritten++;
					$paraStyle = 'Beratungsstelle';
				}
				else {
					$this->anbieterWritten++;
					$paraStyle = $pruefsiegel? 'Anbieter Prüfs.' : 'Anbieter';
				}

				$this->fp->wSect();
					$this->fp->wStyle($paraStyle);
					
					// ...name
					$this->fp->wSect();
						$this->fp->wStyle("$paraStyle Name");
						$this->fp->wText($suchname);
					$this->fp->wSectEnd();
					$this->fp->wText(' ');
					
					
					// ...pruefsiegel
					if( $pruefsiegel ) {
						$this->fp->wSect();
							$this->fp->wStyle("$paraStyle Prüfsiegel");
							$this->fp->wSymbol(0x4a /* Smily */);
						$this->fp->wSectEnd();
						$this->fp->wText(' ');
					}
			
			
					// VER_NR
					$text = $din_nr;
					if( $text ) {
						$this->fp->wSect();
							$this->fp->wStyle("$paraStyle WISY-Nr.");
							$this->fp->wText('(Web:'.$text.')');
						$this->fp->wSectEnd();
					}
					$this->fp->wBreak();

					
					// VER_STR / VER_TEL
					$this->fp->wTab();
					$text = $strasse;
					if( $text ) {
						$this->fp->wText($text);
					}
			
					$text = $anspr_tel;
					if( $text ) {
						$this->fp->wTab();
						$this->fp->wSymbol(0x28 /* Wingdings telephone */);
						$this->fp->wText($text);
					}
					
					$this->fp->wBreak();
			
					// VER_PLZ_NR / VER_ORT / VER_FAX
					$this->fp->wTab();
					$text = "$plz $ort";
					if( $text ) {
						$this->fp->wText($text);
					}
			
					$text = $anspr_fax;
					if( $text ) {
						$this->fp->wTab();
						$this->fp->wText('Fax: ');
						$this->fp->wText($text);
					}
			
					$this->fp->wBreak();
			
			
					// E_MAIL / Internet
					$email = $anspr_email;
					$internet = $homepage;
					if( $email || $internet ) {
						$this->fp->wTab();
						if( $email ) {
							$this->fp->wText($email);
							$this->fp->wTab();
						}
						$this->fp->wText($internet);
						$this->fp->wBreak();
					}
			
				$this->fp->wSectEnd();
				
				// space
				$this->fp->wSect();
					$this->fp->wStyle('Leer');
					$this->fp->wBreak();
				$this->fp->wSectEnd();
			}

		
			// progress information
			$recCount++;
			if( ($recCount % 300) == 0 ) {
				$this->progress_info("$recCount Anbieter bearbeitet...");
			}
		}

		//
		// exit anbieter RTF
		//
		$this->fp->wFinalize();
		$this->fp = 0;

	}
}




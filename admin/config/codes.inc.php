<?php
if (isset($GLOBALS['WisyKi']))
	require_once('WisyKi/config/db.inc.php');
else {
	define('USER_ONLINEBAGATELLE', 19);
	define('USER_ONLINEPFLEGE', 20);
	define('USER_MENUCHECK', -1); // to be defined
	define('TAG_EINRICHTUNGSORT', 806392); // links course-Title in serp to portrait
	define('aeJS', '\u00e4', false);
	define('AEJS', '\u00c4', false);
	define('ueJS', '\u00fc', false);
	define('UEJS', '\u00dc', false);
	define('oeJS', '\u00f6', false);
	define('OEJS', '\u00d6', false);
	define('ssJS', '\u00df', false);

	// synonym => original
	global $geomap_orte;
	$geomap_orte = array(
		'Frankfurt' => "Frankfurt am Main",
		'Offenbach' => "Offenbach am Main",
		'R�sselsheim' => "R�sselsheim am Main",
		'Hofheim' => "Hofheim am Taunus",
		'Bad Homburg' => "Bad Homburg v.d.H.",
		'HH' => "Hamburg",
		'Barmbronx' => "Barmbek-Nord"
	);

	// macht nichts, wenn SW umbenannt wird: nur zur leichteren Lesbarkeit. Muss nur im Code konsistent verwendet werden.
	global $controlTags;
	$controlTags = array(
		'Bildungsurlaub' => 1,
		'Bildungsgutschein' => 3207,
		'Umschulung' => 6013,
		'Orientierungskurs' => 7074,
		'Integrationskurs Intensivkurs' => 7093,
		'Integrationskurs zu speziellem Foerderbedarf' => 7094,
		'Fernunterricht' => 7721,
		'Integrationskurs' => 9774,
		'Aktivierungsgutschein' => 16311,
		'Integrationskurs fuer Frauen' => 7090, // 2022, vorher Synonym
		'Integrationskurs fuer Eltern' => 7091, // 2022, vorher Synonym
		'Integrationskurs fuer junge Erwachsene' => 7092, // 2022, vorher Synonym
		'Integrationskurs mit Alphabetisierung' => 20371,
		'DeuFoeV' => 831461,
		'Integrationskurs fuer Zweitschriftlernende' => 846831,
		'Preis komplex' => 849451,
		'Einstieg bis Kursende moeglich' => 315,
		'E-Learning' => 806311,
		'rollstuhlgerecht' => 9,
		'Mit Kinderbetreuung' => 10,
		'Glossar:beginnoptionen' => 14241,
		'Glossar:tagescode' => 14231,
		'Glossar:dauer' => 14221,
		'Glossar:Durchfuehrungen' => 14211,
		'Glossar:unterrichtsart' => 14181,
		'Glossar:fu_knr' => 14191,
		'Glossar:foerderung' => 14171,
		'Glossar:azwv_knr' => 14161,
		'Glossar:bu_nummer' => 14141,
		'Glossar:stichwoerter' => 14251,
		"Glossar:unterrichtsart_speichern" => 14201
	);

	global $edit_tagid_blacklist;
	// 827571 = WeGebAU, 7635 = Bildungspaket, 833621 = Bildungspr�mie, 7464 = F�rderprogramme (der L�nder), 9846 = Politische Bildung (F�rderung), 827691 = Politische Bildung (HH) F�rderung Kurse 2017, 832831 = Politische Bildung (HH) F�rderung Kurse 2018, 834371 = Politische Bildung (HH) F�rderung Kurse 2019, 847861 = Politische Bildung (HH) F�rderung Kurse 2020, 852321 = Politische Bildung (HH) F�rderung Kurse 2021
	$edit_tagid_blacklist = array(827571, 7635, 833621, 7464, 9846, 827691, 832831, 834371, 847861, 852321);

	global $nonvenues;
	$nonvenues = array(".*ohne .*", ".*unbekannt.*", ".*E-Learning.*", ".*virtuell.*", ".*Web-Seminar.*", ".*Webinar.*", ".*Online.*", ".*WWW.*", ".*Fernstudium.*", ".*Live", ".*Internet.*", ".*Cloud.*", ".*Zoom.*", "N\.N\.", "--");

	// Only applies to full text search and venue/city detection - NOT for standard tag search!
	// Don't add operator keywords like "oder", "bei"... !
	// "in" must be contained otherwise venue may be identified by: in%
	// "f�rr" must be contained otherwise tag may be identified by: f�r%
	global $ignoreWords_DE;

	// don't add operator keywords like "oder", "bei"... ! "in" must be contained otherwise venue may be identified by: in%
	$ignoreWords_DE = array("f�r", "in", "aber", "abermals", "abgerufen", "abgerufene", "abgerufener", "abgerufenes", "�hnlich", "alle", "allein", "allem", "allemal", "allen", "allenfalls", "allenthalben", "aller", "allerdings", "allerlei", "alles", "allesamt", "allgemein", "allm�hlich", "allzu", "als", "alsbald", "also", "alt", "am", "an", "andauernd", "andere", "anderem", "anderen", "anderer", "andererseits", "anderes", "andern", "andernfalls", "anders", "anerkannt", "anerkannte", "anerkannter", "anerkanntes", "angesetzt", "angesetzte", "angesetzter", "anscheinend", "anstatt", "auch", "auf", "auffallend", "aufgrund", "aufs", "augenscheinlich", "aus", "ausdr�cklich", "ausdr�ckt", "ausdr�ckte", "ausgedr�ckt", "ausgenommen", "ausgerechnet", "ausnahmslos", "au�en", "au�er", "au�erdem", "au�erhalb", "�u�erst", "bald", "beide", "beiden", "beiderlei", "beides", "beim", "beinahe", "bekannt", "bekannte", "bekannter", "bekanntlich", "bereits", "besonders", "besser", "bestenfalls", "bestimmt", "betr�chtlich", "bevor", "bez�glich", "bin", "bisher", "bislang", "bist", "blo�", "Bsp", "bzw", "ca", "Co", "da", "dabei", "dadurch", "daf�r", "dagegen", "daher", "dahin", "damals", "damit", "danach", "daneben", "dank", "danke", "dann", "dannen", "daran", "darauf", "daraus", "darf", "darfst", "darin", "dar�ber", "darum", "darunter", "das", "dass", "dasselbe", "davon", "davor", "dazu", "dein", "deine", "deinem", "deinen", "deiner", "deines", "dem", "demgegen�ber", "demgem��", "demnach", "demselben", "den", "denen", "denkbar", "denn", "dennoch", "denselben", "der", "derart", "derartig", "deren", "derer", "derjenige", "derjenigen", "derselbe", "derselben", "derzeit", "des", "deshalb", "desselben", "dessen", "desto", "deswegen", "dich", "die", "diejenige", "dies", "diese", "dieselbe", "dieselben", "diesem", "diesen", "dieser", "dieses", "diesmal", "diesseits", "dir", "direkt", "direkte", "direkten", "direkter", "doch", "dort", "dorther", "dorthin", "drin", "dr�ber", "drunter", "du", "dunklen", "durch", "durchaus", "durchweg", "eben", "ebenfalls", "ebenso", "ehe", "eher", "eigenen", "eigenes", "eigentlich", "ein", "eine", "einem", "einen", "einer", "einerseits", "eines", "einfach", "einig", "einige", "einigem", "einigen", "einiger", "einigerma�en", "einiges", "einmal", "einseitig", "einseitige", "einseitigen", "einseitiger", "einst", "einstmals", "einzig", "e. K.", "entsprechend", "entweder", "er", "ergo", "erh�lt", "erheblich", "erneut", "erst", "ersten", "es", "etc", "etliche", "etwa", "etwas", "euch", "euer", "eure", "eurem", "euren", "eurer", "eures", "falls", "fast", "ferner", "folgende", "folgenden", "folgender", "folgenderma�en", "folgendes", "folglich", "f�rmlich", "fortw�hrend", "fraglos", "frei", "freie", "freies", "freilich", "f�r", "gab", "g�ngig", "g�ngige", "g�ngigen", "g�ngiger", "g�ngiges", "ganz", "ganze", "ganzem", "ganzen", "ganzer", "ganzes", "g�nzlich", "gar", "GbR", "GbdR", "geehrte", "geehrten", "geehrter", "gef�lligst", "gegen", "gehabt", "gekonnt", "gelegentlich", "gemacht", "gem��", "gemeinhin", "gemocht", "genau", "genommen", "gen�gend", "genug", "geradezu", "gern", "gestrige", "getan", "geteilt", "geteilte", "getragen", "gewesen", "gewiss", "gewisse", "gewisserma�en", "gewollt", "geworden", "ggf", "gib", "gibt", "gleich", "gleichsam", "gleichwohl", "gleichzeitig", "gl�cklicherweise", "GmbH", "Gott sei Dank", "gr��tenteils", "Grunde", "gute", "guten", "hab", "habe", "halb", "hallo", "halt", "hast", "hat", "hatte", "h�tte", "h�tte", "h�tten", "hattest", "hattet", "h�ufig", "heraus", "herein", "heute", "heutige", "hier", "hiermit", "hiesige", "hin", "hinein", "hingegen", "hinl�nglich", "hinten", "hinter", "hinterher", "hoch", "h�chst", "h�chstens", "ich", "ihm", "ihn", "ihnen", "ihr", "ihre", "ihrem", "ihren", "ihrer", "ihres", "im", "in", "immer", "immerhin", "immerzu", "indem", "indessen", "infolge", "infolgedessen", "innen", "innerhalb", "ins", "insbesondere", "insofern", "insofern", "inzwischen", "irgend", "irgendein", "irgendeine", "irgendjemand", "irgendwann", "irgendwas", "irgendwen", "irgendwer", "irgendwie", "irgendwo", "ist", "ja", "j�hrig", "j�hrige", "j�hrigen", "j�hriges", "je", "jede", "jedem", "jeden", "jedenfalls", "jeder", "jederlei", "jedes", "jedoch", "jemals", "jemand", "jene", "jenem", "jenen", "jener", "jenes", "jenseits", "jetzt", "kam", "kann", "kannst", "kaum", "kein", "keine", "keinem", "keinen", "keiner", "keinerlei", "keines", "keines", "keinesfalls", "keineswegs", "KG", "klar", "klare", "klaren", "klares", "klein", "kleinen", "kleiner", "kleines", "konkret", "konkrete", "konkreten", "konkreter", "konkretes", "k�nnen", "k�nnt", "konnte", "k�nnte", "konnten", "k�nnten", "k�nftig", "lag", "lagen", "langsam", "l�ngst", "l�ngstens", "lassen", "laut", "lediglich", "leer", "leicht", "leider", "lesen", "letzten", "letztendlich", "letztens", "letztes", "letztlich", "lichten", "links", "Ltd", "mag", "magst", "mal", "man", "manche", "manchem", "manchen", "mancher", "mancherorts", "manches", "manchmal", "mehr", "mehrere", "mehrfach", "mein", "meine", "meinem", "meinen", "meiner", "meines", "meinetwegen", "meist", "meiste", "meisten", "meistens", "meistenteils", "meta", "mich", "mindestens", "mir", "mit", "mithin", "mitunter", "m�glich", "m�gliche", "m�glichen", "m�glicher", "m�glicherweise", "m�glichst", "morgen", "morgige", "muss", "m�ssen", "musst", "m�sst", "musste", "m�sste", "m�ssten", "nach", "nachdem", "nachher", "nachhinein", "n�chste", "n�mlich", "naturgem��", "nat�rlich", "neben", "nebenan", "nebenbei", "nein", "neu", "neue", "neuem", "neuen", "neuer", "neuerdings", "neuerlich", "neues", "neulich", "nicht", "nichts", "nichtsdestotrotz", "nichtsdestoweniger", "nie", "niemals", "niemand", "nimm", "nimmer", "nimmt", "nirgends", "nirgendwo", "noch", "n�tigenfalls", "nun", "nunmehr", "nur", "ob", "oben", "oberhalb", "obgleich", "obschon", "obwohl", "offenbar", "offenkundig", "offensichtlich", "oft", "ohne", "ohnedies", "OHG", "OK", "partout", "per", "pers�nlich", "pl�tzlich", "praktisch", "pro", "quasi", "recht", "rechts", "regelm��ig", "reichlich", "relativ", "restlos", "richtiggehend", "riesig", "rund", "rundheraus", "rundum", "s�mtliche", "sattsam", "sch�tzen", "sch�tzt", "sch�tzte", "sch�tzten", "schlechter", "schlicht", "schlichtweg", "schlie�lich", "schlussendlich", "schnell", "schon", "schwerlich", "schwierig", "sehr", "sei", "seid", "sein", "seine", "seinem", "seinen", "seiner", "seines", "seit", "seitdem", "Seite", "Seiten", "seither", "selber", "selbst", "selbstredend", "selbstverst�ndlich", "selten", "seltsamerweise", "sich", "sicher", "sicherlich", "sie", "siehe", "sieht", "sind", "so", "sobald", "sodass", "soeben", "sofern", "sofort", "sog", "sogar", "solange", "solch", "solche", "solchem", "solchen", "solcher", "solches", "soll", "sollen", "sollst", "sollt", "sollte", "sollten", "solltest", "somit", "sondern", "sonders", "sonst", "sooft", "soviel", "soweit", "sowie", "sowieso", "sowohl", "sozusagen", "sp�ter", "spielen", "startet", "startete", "starteten", "statt", "stattdessen", "steht", "stellenweise", "stets", "tat", "tats�chlich", "tats�chlichen", "tats�chlicher", "tats�chliches", "teile", "total", "trotzdem", "�bel", "�ber", "�berall", "�berallhin", "�beraus", "�berdies", "�berhaupt", "�blicher", "�brig", "�brigens", "um", "umso", "umstandshalber", "umst�ndehalber", "unbedingt", "unbeschreiblich", "und", "unerh�rt", "ungef�hr", "ungemein", "ungew�hnlich", "ungleich", "ungl�cklicherweise", "unl�ngst", "unma�geblich", "unm�glich", "unm�gliche", "unm�glichen", "unm�glicher", "unn�tig", "uns", "unsagbar", "uns�glich", "unser", "unsere", "unserem", "unseren", "unserer", "unseres", "unserm", "unstreitig", "unten", "unter", "unterbrach", "unterbrechen", "unterhalb", "unwichtig", "unzweifelhaft", "usw", "vergleichsweise", "vermutlich", "viel", "viele", "vielen", "vieler", "vieles", "vielfach", "vielleicht", "vielmals", "voll", "vollends", "v�llig", "vollkommen", "vollst�ndig", "vom", "von", "vor", "voran", "vorbei", "vorher", "vorne", "vor�ber", "w�hrend", "w�hrenddessen", "wahrscheinlich", "wann", "war", "w�re", "waren", "w�ren", "warst", "warum", "was", "weder", "weg", "wegen", "weidlich", "weil", "Weise", "wei�", "weitem", "weiter", "weitere", "weiterem", "weiteren", "weiterer", "weiteres", "weiterhin", "weitgehend", "welche", "welchem", "welchen", "welcher", "welches", "wem", "wen", "wenig", "wenige", "weniger", "wenigstens", "wenn", "wenngleich", "wer", "werde", "werden", "werdet", "weshalb", "wessen", "wichtig", "wie", "wieder", "wiederum", "wieso", "wiewohl", "will", "willst", "wir", "wird", "wirklich", "wirst", "wo", "wodurch", "wogegen", "woher", "wohin", "wohingegen", "wohl", "wohlgemerkt", "wohlweislich", "wollen", "wollt", "wollte", "wollten", "wolltest", "wolltet", "womit", "wom�glich", "woraufhin", "woraus", "worin", "wurde", "w�rde", "w�rden", "z.B.", "z. B.", "zahlreich", "zeitweise", "ziemlich", "zu", "zudem", "zuerst", "zufolge", "zugegeben", "zugleich", "zuletzt", "zum", "zumal", "zumeist", "zur", "zur�ck", "zusammen", "zusehends", "zuvor", "zuweilen", "zwar", "zweifellos", "zweifelsfrei", "zweifelsohne", "zwischen");

	global $codes_tagescode;
	$codes_tagescode =
		'0######'					// 0=Berechnung noch nicht erfolgt ODER Berechnung ohne Ergebnis
		. '1###Ganzt�gig###'
		. '2###Vormittags###'
		. '3###Nachmittags###'
		. '4###Abends###'
		. '5###Wochenende';


	global $codes_kurstage;
	$codes_kurstage =
		'1###Mo. ###'
		. '2###Di. ###'
		. '4###Mi. ###'
		. '8###Do. ###'
		. '16###Fr. ###'
		. '32###Sa. ###'
		. '64###So.';


	global $codes_beginnoptionen;
	$codes_beginnoptionen =
		'0######'
		. '1###Beginnt laufend###'
		. '2###Beginnt w�chentlich###'
		. '4###Beginnt monatlich###'
		. '8###Beginnt zweimonatlich###'
		. '16###Beginnt quartalsweise###'
		. '32###Beginnt halbj�hrlich###'		// war vor 10/2011: 32: "Beginnt semesterweise"
		. '64###Beginnt j�hrlich###'
		//.'128###Laufender Einstieg###'		// war vor 10/2011: 128: "Beginn vereinbar"
		. '256###Termin noch offen###'		// war vor 10/2011: 256: "Beginn erfragen"
		. '512###Startgarantie';				// war vor 10/2011: 512: "Beginnt garantiert", vor 10/2012: Abgeschafft und ab 10/2012 wieder "Startgarantie" ...

	global $codes_rechtsform;
	$codes_rechtsform =
		'0######'
		. '1###GmbH###'
		. '2###GmbH gemeinn�tzig###'
		. '3###Verein###'
		. '4###Verein gemeinn�tzig###'
		. '5###Einzelunternehmen###'
		. '6###GbR###'
		. '7###Stiftung###'
		. '8###Staatlicher Tr�ger###' // ehem. Anstalt oeff. Rechts
		. '9###K�rperschaft �ff. Rechts###'
		. '10###Aktiengesellschaft###'
		. '11###Sonstige';

	global $codes_stichwort_eigenschaften;
	$codes_stichwort_eigenschaften =
		'0###Sachstichwort###'
		. '1###Abschluss###'
		. '65536###Zertifikat###'
		. '2###F�rderungsart###'
		. '4###Qualit�tszertifikat###'
		. '8###Zielgruppe###'
		. '32768###Unterrichtsart###'			// hinzugef�gt 2014-10-29 10:30 
		. '16###Abschlussart###'
		. '32###verstecktes Synonym###'
		. '64###Synonym###'
		. '128###Veranstaltungsort###'			// wird von der Redaktion/von Juergen verwendet - aber: wozu soll das sein? (bp) ACHTUNG: Wird in tag_type anders verwendet!
		//.'256###Termin###'					// wird nicht verwendet - wozu soll das sein? (bp)
		//.'256###Volltext Titel###'				// ACHTUNG: Wird in tag_type anders verwendet!
		//.'512###Volltext Beschreibung###'		// ACHTUNG: Wird in tag_type anders verwendet!
		. '1024###Sonstiges Merkmal###'
		. '2048###Verwaltungsstichwort###'
		. '4096###Thema###'
		. '8192###Schlagwort nicht verwenden###'	// 8192 war mal "Hierarchie", "Schlagwort nicht verwenden" war mal bit 32 -- in beiden F�llen: wozu soll das sein? (bp)
		. '16384###Anbieterstichwort###'	   // sollte mal exklusiv die Kurse infizieren, wenn bei einem Anbieter verwendet, aktuell (12/2014) nicht verwendet, alle nicht-versteckten Stichwoerter infizieren die Kurse, wenn einem Anbieter zugeordnet
		. '524288###ESCO-Kompetenz###'
		. '1048576###ESCO-T�tigkeit';
	// ACHTUNG: Werte ab 0x10000 werden in tag_type anders verwendet!
	// 131072 versteckte Anbieter-Namensverweisung
	// 262144 = neue Namensverweisung, damit von SW-Synonym (64) unterscheidbar
	// -> s. db.inc.php f�r Anbieter-Typ-Codes


	global $hidden_stichwort_eigenschaften;
	$hidden_stichwort_eigenschaften = 32 + 128 + 256 + 512 + 2048 + 8192; // EDIT 5/2017: Stichworttyp "Thema" (4096) wird nicht mehr gefiltern (warum war dies in der Vergangenheit so?) (bp)


	global $codes_dauer;
	$codes_dauer =
		'0######'
		. '1###1 Tag###'
		. '2###2 Tage###'
		. '3###3 Tage###'
		. '4###4 Tage###'
		. '5###5 Tage###'
		. '6###6 Tage###'
		. '7###1 Woche###'
		. '14###2 Wochen###'
		. '21###3 Wochen###'
		. '28###4 Wochen###'
		. '35###5 Wochen###'
		. '42###6 Wochen###'
		. '49###7 Wochen###'
		. '56###8 Wochen###'
		. '63###9 Wochen###'
		. '70###10 Wochen###'
		. '77###11 Wochen###'
		. '84###12 Wochen###'
		. '91###13 Wochen###' // weeks 13..52 added 2014-11-19 01:57 
		. '98###14 Wochen###'
		. '105###15 Wochen###'
		. '112###16 Wochen###'
		. '119###17 Wochen###'
		. '126###18 Wochen###'
		. '133###19 Wochen###'
		. '140###20 Wochen###'
		. '147###21 Wochen###'
		. '154###22 Wochen###'
		. '161###23 Wochen###'
		. '168###24 Wochen###'
		. '175###25 Wochen###'
		. '181###26 Wochen###' // CAVE: 26 weeks = 1 semester, subtracting 1
		. '189###27 Wochen###'
		. '196###28 Wochen###'
		. '203###29 Wochen###'
		. '209###30 Wochen###' // CAVE: 30 weeks = 7 months, subtracting 1
		. '217###31 Wochen###'
		. '224###32 Wochen###'
		. '231###33 Wochen###'
		. '238###34 Wochen###'
		. '245###35 Wochen###'
		. '252###36 Wochen###'
		. '259###37 Wochen###'
		. '266###38 Wochen###'
		. '273###39 Wochen###'
		. '280###40 Wochen###'
		. '287###41 Wochen###'
		. '294###42 Wochen###'
		. '301###43 Wochen###'
		. '308###44 Wochen###'
		. '315###45 Wochen###'
		. '322###46 Wochen###'
		. '329###47 Wochen###'
		. '336###48 Wochen###'
		. '343###49 Wochen###'
		. '350###50 Wochen###'
		. '357###51 Wochen###'
		. '363###52 Wochen###' // CAVE: 52 weeks = 2 semester, subtracting 1	
		. '30###1 Monat###'
		. '60###2 Monate###'
		. '90###3 Monate###'
		. '120###4 Monate###'
		. '150###5 Monate###'
		. '180###6 Monate###'
		. '210###7 Monate###'
		. '240###8 Monate###'
		. '270###9 Monate###'
		. '300###10 Monate###'
		. '330###11 Monate###'
		. '360###12 Monate###'
		. '390###13 Monate###'
		. '420###14 Monate###'
		. '450###15 Monate###'
		. '480###16 Monate###'
		. '510###17 Monate###'
		. '540###18 Monate###'
		. '570###19 Monate###'
		. '600###20 Monate###'
		. '630###21 Monate###'
		. '660###22 Monate###'
		. '690###23 Monate###'
		. '720###24 Monate###'
		. '750###25 Monate###'
		. '780###26 Monate###'
		. '810###27 Monate###'
		. '840###28 Monate###'
		. '870###29 Monate###'
		. '900###30 Monate###'
		. '930###31 Monate###'
		. '960###32 Monate###'
		. '990###33 Monate###'
		. '1020###34 Monate###'
		. '1050###35 Monate###'
		. '1080###36 Monate###'
		. '1170###39 Monate###'
		. '1260###42 Monate###'
		. '1440###48 Monate###'
		. '1620###54 Monate###'
		. '365###1 Jahr###'
		. '730###2 Jahre###'
		. '1095###3 Jahre###'
		. '1460###4 Jahre###'
		. '1825###5 Jahre###'
		. '2190###6 Jahre###'
		. '2555###7 Jahre###'
		. '2920###8 Jahre###'
		. '182###1 Semester###'
		. '364###2 Semester###'
		. '546###3 Semester###'
		. '728###4 Semester###'
		. '910###5 Semester###'
		. '1092###6 Semester###'
		. '1274###7 Semester###'
		. '1456###8 Semester';

	// 	.'2160###6 Jahre###' -- man darf nur eindeutige Abbildungen erstellen


	/******************************************************************************
	 * Berechnungen
	 ******************************************************************************/



	function berechne_tagescode($von, $bis, $kurstage)
	{
		$tagescode = 0;

		if ($von != '' && $von != '00:00' && $bis != '' && $bis != '00:00') {
			if ($von < '12:00') {
				if ($bis <= '14:00')
					$tagescode = 2; // vormittags
				else
					$tagescode = 1; // ganzer tag
			} else if ($von >= '17:00') {
				$tagescode = 4; // abends
			} else {
				$tagescode = 3; // nachmittags
			}
		}

		if (
			!($kurstage & (1 + 2 + 4 + 8)) /*nicht: mo-do*/
			&& (($kurstage & 32) || ($kurstage & 64)) /*muss: sa oder so*/
		) {
			$tagescode = 5; // wochenende
		}

		return $tagescode;
	}

	function berechne_wochentag($d)
	{
		$d = strtr($d, ' :', '--');
		$d = explode('-', $d);
		$timestamp = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
		if ($timestamp === -1 || $timestamp === false || intval($d[1]) == 0 || intval($d[2]) == 0 || intval($d[0]) == 0)
			return 0;
		$d = getdate($timestamp);
		switch (intval($d['wday'])) {
			case 0:/*so*/
				return 64;
			case 1:/*mo*/
				return 1;
			case 2:/*di*/
				return 2;
			case 3:/*mi*/
				return 4;
			case 4:/*do*/
				return 8;
			case 5:/*fr*/
				return 16;
			case 6:/*sa*/
				return 32;
			default:
				return 0;
		}
	}

	function berechne_wochentage($beginn, $ende)
	{
		$wochentag1 = berechne_wochentag($beginn);
		$wochentag2 = berechne_wochentag($ende == '0000-00-00 00:00:00' ? $beginn : $ende);
		return $wochentag1 | $wochentag2; /*einfache Berechnung - f�r die komplexe muss der Code unten anstelle dieser Zeile ausgefuehrt werden*/

		/*
	if( $wochentag1==0 || $wochentag2 == 0 ) { return 0; } // error
	if( $wochentag1&(32+64) || $wochentag2&(32+64) ) { return $wochentag1|$wochentag2; } // wochenende-spezialfall
	for( $wochentage = 0, $curr = $wochentag1, $i = 0; $i < 7; $i++ )
	{
		$wochentage |= $curr;
		if( $curr == $wochentag2 ) break; // done
		$curr = $curr * 2; // naechstes wochentagssbit
		if( $curr > 64 ) $curr = 1; // nach sonntag kommt montag
	}
	return $wochentage;
	*/
	}

	function berechne_dauer($start, $ende)
	{

		date_default_timezone_set('UTC'); // otherwise mktime asumes dst (UTC) wrong => too short duration for DF in March/Oct.

		// anzahl tage berechnen
		$d = strtr($start, ' :', '--');
		$d = explode('-', $d);
		$timestamp1 = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
		if ($timestamp1 === -1 || $timestamp1 === false || intval($d[1]) == 0 || intval($d[2]) == 0 || intval($d[0]) == 0)
			return 0;

		$d = strtr($ende, ' :', '--');
		$d = explode('-', $d);
		$timestamp2 = mktime(0, 0, 0, intval($d[1]), intval($d[2]), intval($d[0]));
		if ($timestamp2 === -1 || $timestamp2 === false || intval($d[1]) == 0 || intval($d[2]) == 0 || intval($d[0]) == 0)
			return 0;

		if ($timestamp1 > $timestamp2)
			return 0;

		$days = intval(($timestamp2 - $timestamp1) / 86400) + 1;
		if ($days <= 0)
			return 0;

		// auf die vorgegebenen Werte runden
		if ($days > 8 * 365.25) {
			return 8 * 365; // ... 8 Jahre
		} else if ($days > 24 * 30.4) {
			$years = round($days / 365.25);
			return $years * 365; // ... x Jahre
		} else if ($days > 12 * 7) {
			$months = round($days / 30.4);
			return $months * 30; // x Monate
		} else if ($days > 7) {
			$weeks = round($days / 7);
			return $weeks * 7;
		} else {
			return $days;
		}

		date_default_timezone_get("Europe/Berlin");
	}

	// output days in human readable format (days, months, years)
	function daysToReadable($days)
	{

		$years = floor($days / 365);
		$days_remaining = $days - ($years * 365);

		$months = floor($days_remaining / 30.5);
		$days_remaining = $days_remaining - floor($months * 30.5);

		if ($days_remaining > 25 || $days_remaining > 15 && $months == 11) {
			$months++;
			$days_remaining = 0;

			if ($months == 12) {
				$years++;
				$months = 0;
			}
		}


		if ($years > 0)
			$date_str = $years . '&nbsp;Jahre';

		if ($months > 0)
			$date_str .= ' ' . $months . '&nbsp;Monate';

		if ($days_remaining)
			$date_str .= ' ' . $days_remaining . '&nbsp;Tage';

		return $date_str;
	}

	// not for security relavant features
	function berechne_loginid()
	{
		return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . date("d.m.Y")); // $_SERVER['SERVER_SIGNATURE']
	}
}

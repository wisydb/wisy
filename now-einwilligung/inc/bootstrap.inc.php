<?php
/*******************************************************************************
 NOW-Einwilligung – WISY-Bootstrap
 ******************************************************************************
 Stellt eine minimale, aber vollständige WISY-Laufzeitumgebung bereit, damit
 - die Datenbank (DB_Admin) verfügbar ist,
 - Ratgeber-/Glossar-Seiten über die WISY-Wiki->HTML-Konvertierung gerendert
   werden können (createWisyObject + WISY_WIKI2HTML_CLASS),
 ohne den vollständigen Portal-Dispatch (core50/main.inc.php ruft am Ende
 unbedingt main() auf) zu starten.

 Wichtig: Die WISY-Klassen verwenden teils CWD-relative include-Pfade
 (z. B. require_once("admin/wiki2html8.inc.php")). Deshalb wechseln wir das
 Arbeitsverzeichnis ins WISY-Wurzelverzeichnis.
 *******************************************************************************/

if (!defined('NOW_EINW_IN')) {
    die('Direktaufruf nicht erlaubt.');
}

// --- WISY-Wurzel bestimmen und zum Arbeitsverzeichnis machen ----------------
define('NOW_WISY_ROOT', dirname(dirname(__DIR__)));   // .../wisy
chdir(NOW_WISY_ROOT);

if (!defined('IN_WISY')) {
    define('IN_WISY', true);
}

// PHP7-Konstante wie in index.php (von cs8()/diversen Klassen erwartet)
if (!defined('PHP7')) {
    define('PHP7', (PHP_VERSION_ID >= 70000));
}

// $wisyCore wird von loadWisyClass() benötigt
$GLOBALS['wisyCore'] = NOW_WISY_CORE;

// --- cs8(): ISO-8859-1 -> UTF-8 (nur < PHP7), wie in index.php --------------
if (!function_exists('cs8')) {
    function cs8($string)
    {
        return PHP7 ? $string : mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }
}

// --- Datenbank + Konfiguration ---------------------------------------------
require_once(NOW_WISY_ROOT . '/admin/sql_curr.inc.php');
require_once(NOW_WISY_ROOT . '/admin/config/config.inc.php');

if (!class_exists('DB_Admin')) {
    die('WISY-Konfiguration ungültig (DB_Admin nicht gefunden).');
}

$GLOBALS['now_db'] = new DB_Admin;

// --- explodeSettings(): Portal-Einstellungen parsen ------------------------
// Identisch zur WISY-Implementierung (index.php), inkl. Folgen von
// 'include'-Direktiven – wichtig, falls die mail.extern.*-Einstellungen in
// einer eingebundenen Datei liegen. CWD ist die WISY-Wurzel (s. chdir oben),
// daher lassen sich relative include-Pfade auflösen.
if (!function_exists('explodeSettings')) {
    function explodeSettings__($in, &$out, $follow_includes)
    {
        $in = strtr((string)$in, "\r\t", "\n ");
        $in = explode("\n", $in);
        for ($i = 0; $i < sizeof($in); $i++) {
            $equalPos = strpos($in[$i], '=');
            if ($equalPos) {
                $regKey = trim(substr($in[$i], 0, $equalPos));
                if ($regKey != '') {
                    $regValue = trim(substr($in[$i], $equalPos + 1));
                    if ($regKey == 'include') {
                        if ($follow_includes && @file_exists($regValue)) {
                            explodeSettings__(file_get_contents($regValue), $out, false);
                        }
                    } else {
                        $out[$regKey] = $regValue;
                    }
                }
            }
        }
    }
    function explodeSettings($in)
    {
        $out = array();
        explodeSettings__($in, $out, true);
        return $out;
    }
}

// --- Portal-Einstellungen des aktuellen Hosts laden ------------------------
// Wird von der Framework-Klasse (iniRead) und der URL-Erzeugung erwartet.
$GLOBALS['wisyPortalEinstellungen'] = array();
$GLOBALS['wisyPortalId']            = 0;
$GLOBALS['wisyPortalName']          = '';

$db = $GLOBALS['now_db'];
$portalLoaded = false;

// 1) Bevorzugt: fest konfigurierte Portal-ID (liefert u.a. mail.extern.*-SMTP).
if (defined('NOW_PORTAL_ID') && intval(NOW_PORTAL_ID) > 0) {
    $db->query("SELECT id, name, einstellungen FROM portale WHERE id=" . intval(NOW_PORTAL_ID));
    if ($db->next_record()) {
        $GLOBALS['wisyPortalId']            = intval($db->f('id'));
        $GLOBALS['wisyPortalName']          = $db->fs('name');
        $GLOBALS['wisyPortalEinstellungen'] = explodeSettings($db->fs('einstellungen'));
        $portalLoaded = true;
    }
}

// 2) Fallback: automatische Host-Erkennung.
if (!$portalLoaded) {
    $host = isset($_SERVER['SERVER_NAME']) ? strtolower(str_replace('www.', '', $_SERVER['SERVER_NAME'])) : '';
    if ($host !== '') {
        $db->query("SELECT id, name, einstellungen FROM portale WHERE status=1 AND domains LIKE " . $db->quote('%' . $host . '%'));
        if ($db->next_record()) {
            $GLOBALS['wisyPortalId']            = intval($db->f('id'));
            $GLOBALS['wisyPortalName']          = $db->fs('name');
            $GLOBALS['wisyPortalEinstellungen'] = explodeSettings($db->fs('einstellungen'));
        }
    }
}
// Modul-Laden in createWisyObject deaktivieren (wir brauchen nur die Wiki-Klasse)
if (!isset($GLOBALS['wisyPortalEinstellungen']['module'])) {
    $GLOBALS['wisyPortalEinstellungen']['module'] = '';
}

/*------------------------------------------------------------------
 Klassen-Fabrik (wie im WISY-Kern main.inc.php). Eigenständig definiert, damit
 nicht die gesamte main.inc.php eingebunden werden muss, die sofort main()
 startet und den vollständigen Portal-Dispatch auslösen würde.
------------------------------------------------------------------*/
global $wisyClasses;
$wisyClasses = array();

if (!function_exists('registerWisyClass')) {
    function registerWisyClass($className)
    {
        global $wisyClasses;
        $rootName = $className;
        while (($test = get_parent_class($rootName)) !== false) {
            $rootName = strtoupper($test);
        }
        if ($rootName == $className || substr($rootName, 0, 5) != 'WISY_') {
            die("Fehler in registerWisyClass(): Die Klasse &quot;$className&quot; ist nicht von einer WISY-Basisklasse abgeleitet.");
        }
        $wisyClasses[$rootName] = $className;
    }
}

if (!function_exists('loadWisyClass')) {
    function loadWisyClass($className)
    {
        global $wisyCore;
        require_once($wisyCore . '/' . str_replace('_', '-', strtolower($className)) . '.inc.php');
        global $wisyClasses;
        if (isset($wisyClasses[$className])) {
            $className = $wisyClasses[$className];
        }
        return $className;
    }
}

if (!function_exists('createWisyObject')) {
    function &createWisyObject($className, &$anyobject, $anyparam = 0)
    {
        if (!defined('WISY_MODULES_LOADED')) {
            define('WISY_MODULES_LOADED', true);
            global $wisyPortalEinstellungen;
            if (!empty($wisyPortalEinstellungen['module'])) {
                $module = explode(',', $wisyPortalEinstellungen['module']);
                foreach ($module as $file) {
                    $file = trim($file);
                    if ($file != '' && file_exists($file)) {
                        require_once($file);
                    }
                }
            }
        }
        $className = loadWisyClass($className);
        $obj = new $className($anyobject, $anyparam);
        return $obj;
    }
}

/*------------------------------------------------------------------
 Framework-Instanz erzeugen (für Wiki-Link-Auflösung & encode_windows_chars).
------------------------------------------------------------------*/
$GLOBALS['now_framework'] = null;
try {
    $null = 0;
    $GLOBALS['now_framework'] =& createWisyObject('WISY_FRAMEWORK_CLASS', $null);
} catch (Throwable $e) {
    // Framework optional – Glossar-Rendering fängt das Fehlen unten ab.
    $GLOBALS['now_framework'] = null;
}

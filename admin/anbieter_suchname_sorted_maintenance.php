<?php

/**
 * Maintenance-Skript fuer anbieter.suchname_sorted (wenn je nach Importweg nicht gefuellt):
 * - berechnet suchname_sorted fuer Anbieter-Datensaetze nachtraeglich,
 *   bei denen der Wert leer ist (z. B. nach alternativen Importwegen,
 *   die das Feld nicht setzen).
 * - nutzt die kanonische Funktion g_eql_normalize_natsort() aus eql.inc.php,
 *   damit das Ergebnis identisch zu Edit/Import/REST-Pfad ist.
 *
 * Aufruf:
 *   php admin/anbieter_suchname_sorted_maintenance.php
 *   php admin/anbieter_suchname_sorted_maintenance.php --id=123
 *   php admin/anbieter_suchname_sorted_maintenance.php --all
 *   php admin/anbieter_suchname_sorted_maintenance.php --dry-run
 * oder per Web (intern/cron):
 *   /admin/anbieter_suchname_sorted_maintenance.php
 *   /admin/anbieter_suchname_sorted_maintenance.php?id=123
 *   /admin/anbieter_suchname_sorted_maintenance.php?all=1
 *   /admin/anbieter_suchname_sorted_maintenance.php?dry-run=1
 */

define('G_SKIP_LOGIN', 1);

// Sicherstellen, dass relative Includes (skins/default/class.php in
// site.inc.php usw.) funktionieren, egal aus welchem Verzeichnis das
// Skript aufgerufen wird (Projekt-Root, admin/, oder per Web).
chdir(__DIR__);

require_once(__DIR__ . '/functions.inc.php');

// Die Auto-Detection in eql.inc.php deckt nur "Aufruf aus Projekt-Root"
// und "Aufruf aus /api/vN" ab. Damit der Include von table_def.inc.php
// dort unabhaengig von PHP_SELF / CWD aufloest, $site-Pfade explizit auf
// absolute Werte setzen.
global $site;
if (is_object($site)) {
    $site->sitePath = '';
    $site->adminDir = __DIR__;
}

require_once(__DIR__ . '/eql.inc.php');

@set_time_limit(0);

function get_request_or_cli_value($key)
{
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }

    if (PHP_SAPI === 'cli' && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
        foreach ($GLOBALS['argv'] as $arg) {
            if ($arg === '--' . $key) {
                return '1';
            }
            if (strpos($arg, '--' . $key . '=') === 0) {
                return substr($arg, strlen('--' . $key . '='));
            }
        }
    }

    return null;
}

function anbieter_suchname_sorted_maintenance_run($options = array())
{
    $db  = new DB_Admin();
    $db2 = new DB_Admin();

    $id      = isset($options['id'])      ? intval($options['id'])         : intval(get_request_or_cli_value('id'));
    $all     = isset($options['all'])     ? (bool)$options['all']          : (bool)get_request_or_cli_value('all');
    $dryRun  = isset($options['dry-run']) ? (bool)$options['dry-run']      : (bool)get_request_or_cli_value('dry-run');

    $where = array();
    if ($id > 0) {
        $where[] = 'id=' . $id;
    }
    if (!$all) {
        // Default: nur Datensaetze ohne berechneten Sortierwert.
        $where[] = "suchname_sorted=''";
    }
    // Leere suchname-Werte erzeugen ohnehin nur einen leeren Sortierwert,
    // also ueberspringen.
    $where[] = "suchname<>''";

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $db->query("SELECT id, suchname, suchname_sorted FROM anbieter" . $whereSql);

    $checked = 0;
    $updated = 0;
    while ($db->next_record()) {
        $checked++;
        $rowId      = intval($db->f('id'));
        $suchname   = (string)$db->f('suchname');
        $oldSorted  = (string)$db->f('suchname_sorted');
        $newSorted  = g_eql_normalize_natsort($suchname);

        if ($newSorted === $oldSorted) {
            continue;
        }

        if (!$dryRun) {
            $db2->query("UPDATE anbieter SET suchname_sorted='" . $newSorted . "' WHERE id=" . $rowId);
        }
        $updated++;

        if (($checked % 500) == 0) {
            echo "Geprueft: " . $checked . ", aktualisiert: " . $updated . "\n";
        }
    }

    return array('checked' => $checked, 'updated' => $updated, 'dry_run' => $dryRun);
}

if (!defined('ANBIETER_SUCHNAME_SORTED_MAINTENANCE_NO_AUTORUN')) {
    $result = anbieter_suchname_sorted_maintenance_run();
    if ($result['checked'] === 0) {
        echo "Keine passenden Datensaetze in anbieter gefunden.\n";
    }
    else {
        echo "Fertig.<br>"
            . "Geprueft: " . $result['checked'] . ", "
            . ($result['dry_run'] ? "wuerden aktualisiert: " : "aktualisiert: ")
            . $result['updated'] . "\n";
    }
}
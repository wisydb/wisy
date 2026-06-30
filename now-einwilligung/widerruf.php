<?php
/*******************************************************************************
 NOW-Einwilligung – Widerruf
 ******************************************************************************
 Seite, über die ein Anbieter die zuvor erteilte Einwilligung zur Übermittlung
 an „mein NOW“ widerruft.

 Aufruf:
   personalisiert:  /now-einwilligung/widerruf.php?a=<anbieterId>&t=<token>
   ohne Parameter:  Auswahl des Anbieters per Dropdown

 Setzt anbieter.now_zustimmung=0, legt einen Historien-Datensatz
 (aktion='widerrufen') an, schreibt einen Journaleintrag und sendet
 Bestätigungs-E-Mails an Anbieter UND Redaktion (NOW_ADMIN_EMAIL).
 *******************************************************************************/

define('NOW_EINW_IN', true);

// Ausgabe ist UTF-8 – HTTP-Header explizit setzen (Vorrang vor <meta charset>).
header('Content-Type: text/html; charset=UTF-8');

require_once(__DIR__ . '/inc/config.inc.php');
require_once(__DIR__ . '/inc/bootstrap.inc.php');
require_once(__DIR__ . '/inc/functions.inc.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['now_csrf'])) {
    $_SESSION['now_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['now_csrf'];

/*------------------------------------------------------------------ Kontext */
$linkAnbieterId = isset($_GET['a']) ? intval($_GET['a']) : 0;
$linkToken      = isset($_GET['t']) ? (string)$_GET['t'] : '';
$lockedAnbieter = null;
if ($linkAnbieterId > 0 && now_token_valid($linkAnbieterId, $linkToken)) {
    $lockedAnbieter = now_load_anbieter($linkAnbieterId);
}

/*------------------------------------------------------------------ Texte (für Archivkopie) */
$nowR        = now_render_glossar(NOW_GLOSSAR_RECHTE_ID);
$agb         = now_render_glossar(NOW_GLOSSAR_AGB_ID);
$datenschutz = now_render_glossar(NOW_GLOSSAR_DATENSCHUTZ_ID);
$nowRechteHtml   = $nowR        ? $nowR['html']          : '';
$nowRechteVer    = $nowR        ? $nowR['version']       : '';
$agbHtml         = $agb         ? $agb['html']           : '';
$agbVersion      = $agb         ? $agb['version']        : '';
$datenschutzHtml = $datenschutz ? $datenschutz['html']   : '';
$datenschutzVer  = $datenschutz ? $datenschutz['version']: '';

/*------------------------------------------------------------------ POST */
$errors  = array();
$success = false;
$savedFor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $errors[] = 'Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
    }
    if (!empty($_POST['website'])) { $errors[] = 'Ungültige Eingabe.'; }

    $postAnbieterId = 0;
    if ($lockedAnbieter) {
        $postAnbieterId = $lockedAnbieter['id'];
    } elseif (isset($_POST['anbieter_id'])) {
        $postAnbieterId = intval($_POST['anbieter_id']);
    }
    $anbieter = $postAnbieterId > 0 ? now_load_anbieter($postAnbieterId) : null;
    if (!$anbieter) { $errors[] = 'Bitte wählen Sie einen gültigen Anbieter aus.'; }

    $vorname  = trim((string)($_POST['vorname']  ?? ''));
    $nachname = trim((string)($_POST['nachname'] ?? ''));
    if (mb_strlen($vorname) < 2)  { $errors[] = 'Bitte geben Sie Ihren Vornamen an.'; }
    if (mb_strlen($nachname) < 2) { $errors[] = 'Bitte geben Sie Ihren Nachnamen an.'; }

    foreach (array('berechtigung', 'widerruf_bestaetigt') as $cb) {
        if (empty($_POST[$cb])) {
            $errors[] = 'Bitte bestätigen Sie die erforderlichen Punkte.';
            break;
        }
    }

    if (!$errors && $anbieter) {
        $data = array(
            'anbieter'            => $anbieter['id'],
            'anbieter_suchname'   => $anbieter['suchname'],
            'vorname'             => $vorname,
            'nachname'            => $nachname,
            'email_bestaetigung'  => $anbieter['pflege_email'] !== '' ? $anbieter['pflege_email'] : $anbieter['anspr_email'],
            'aktion'              => 'widerrufen',
            'text_now_rechte'     => $nowRechteHtml,
            'text_agb'            => $agbHtml,
            'text_datenschutz'    => $datenschutzHtml,
            'version_now_rechte'  => $nowRechteVer,
            'version_agb'         => $agbVersion,
            'version_datenschutz' => $datenschutzVer,
        );
        now_store_consent($data);
        now_send_confirmation($data['email_bestaetigung'], $data);

        $success  = true;
        $savedFor = $anbieter['suchname'];
        $_SESSION['now_csrf'] = bin2hex(random_bytes(16));
        $csrf = $_SESSION['now_csrf'];
    }
}

$anbieterListe = $lockedAnbieter ? array() : now_list_anbieter();
$queryString   = ($lockedAnbieter ? ('?a=' . $lockedAnbieter['id'] . '&t=' . now_token($lockedAnbieter['id'])) : '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Widerruf der Übermittlung an „mein NOW“</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <div class="header">
        <?php if (NOW_LOGO_PATH !== ''): ?>
            <img class="logo" src="<?= now_h(NOW_LOGO_PATH) ?>" alt="<?= now_h(NOW_PORTAL_NAME) ?>">
            <span class="arrow" aria-hidden="true">&times;</span>
        <?php endif; ?>
        <span class="now-logo">mein&nbsp;NOW</span>
    </div>

    <h1>Widerruf der Übermittlung an „mein&nbsp;NOW“</h1>

<?php if ($success): ?>

    <div class="box box-success">
        <h2>Widerruf gespeichert</h2>
        <p>Die Übermittlung der Daten von <strong><?= now_h($savedFor) ?></strong> an „mein&nbsp;NOW“ wurde widerrufen.</p>
        <p>Mit dem nächsten Update werden Ihre Daten nicht mehr an „mein&nbsp;NOW“ übertragen bzw. dort entfernt.
           Eine Bestätigung wurde an die Pflege-E-Mail-Adresse Ihres Anbieter-Eintrags und an die Redaktion gesendet.</p>
        <p>Sie können jederzeit erneut einwilligen.</p>
    </div>

<?php else: ?>

    <?php if ($errors): ?>
        <div class="box box-error"><ul>
            <?php foreach (array_unique($errors) as $e): ?><li><?= now_h($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <p>Hier können Sie die zuvor erteilte Einwilligung zur Übermittlung Ihrer Anbieter- und Kursdaten
       an „mein&nbsp;NOW“ <strong>widerrufen</strong>. Ihre Daten im Kursportal (WISY) bleiben
       davon unberührt; lediglich die Weitergabe an „mein&nbsp;NOW“ wird beendet.</p>

    <form id="widerrufForm" action="<?= now_h($queryString) ?>" method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= now_h($csrf) ?>">
        <div class="hp"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset>
            <legend>1. Anbieter</legend>
            <?php if ($lockedAnbieter): ?>
                <p class="anbieter-fix"><?= now_h($lockedAnbieter['suchname']) ?> <span class="muted">(ID <?= (int)$lockedAnbieter['id'] ?>)</span></p>
            <?php else: ?>
                <label for="anbieter_id">Bitte wählen Sie Ihren Anbieter:</label>
                <select id="anbieter_id" name="anbieter_id" required>
                    <option value="">– bitte auswählen –</option>
                    <?php foreach ($anbieterListe as $aid => $aname): ?>
                        <option value="<?= (int)$aid ?>"<?= (isset($_POST['anbieter_id']) && intval($_POST['anbieter_id']) === $aid) ? ' selected' : '' ?>>
                            <?= now_h($aname) ?> (ID <?= (int)$aid ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </fieldset>

        <fieldset>
            <legend>2. Ihr Name</legend>
            <div class="form-row">
                <div>
                    <label for="vorname">Vorname</label>
                    <input type="text" id="vorname" name="vorname" required value="<?= now_h($_POST['vorname'] ?? '') ?>">
                </div>
                <div>
                    <label for="nachname">Nachname</label>
                    <input type="text" id="nachname" name="nachname" required value="<?= now_h($_POST['nachname'] ?? '') ?>">
                </div>
            </div>
            <label class="check">
                <input type="checkbox" name="berechtigung" value="1" required>
                Ich bin berechtigt, für den oben genannten Anbieter zu handeln.
            </label>
            <label class="check">
                <input type="checkbox" name="widerruf_bestaetigt" value="1" required>
                Ich widerrufe die Einwilligung zur Übermittlung der Daten an „mein&nbsp;NOW“.
            </label>
        </fieldset>

        <button type="submit" id="submitBtn" disabled>Widerruf absenden</button>
        <p class="muted small">Sie können nach einem Widerruf jederzeit erneut einwilligen.</p>
    </form>

<?php endif; ?>

</div>
<script src="assets/widerruf.js"></script>
</body>
</html>

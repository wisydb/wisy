<?php
/*******************************************************************************
 NOW-Einwilligung – Formular
 ******************************************************************************
 Öffentliches Formular, über das Weiterbildungsanbieter aus dem Kursportal
 (WISY) der Übermittlung ihrer Daten an "mein NOW" zustimmen.

 Aufruf:
   personalisiert:  /now-einwilligung/?a=<anbieterId>&t=<token>
   ohne Parameter:  Auswahl des Anbieters per Dropdown

 Speichert revisionssicher (Tabelle anbieter_now_einwilligung), setzt das
 Live-Flag anbieter.now_zustimmung, schreibt einen Journaleintrag in
 anbieter.notizen und sendet eine Bestätigungs-E-Mail an die Pflege-Adresse.
 *******************************************************************************/

define('NOW_EINW_IN', true);

// Die Ausgabe ist UTF-8. Den Content-Type-Header explizit (und vor jeder
// Ausgabe) setzen, damit ein evtl. latin1-Default des Servers/PHP die Seite
// nicht falsch deklariert – der HTTP-Header hat Vorrang vor <meta charset>.
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

/*------------------------------------------------------------------
 Anbieter-Kontext bestimmen
------------------------------------------------------------------*/
$linkAnbieterId = isset($_GET['a']) ? intval($_GET['a']) : 0;
$linkToken      = isset($_GET['t']) ? (string)$_GET['t'] : '';
$lockedAnbieter = null; // per gültigem Link festgelegter Anbieter

if ($linkAnbieterId > 0 && now_token_valid($linkAnbieterId, $linkToken)) {
    $lockedAnbieter = now_load_anbieter($linkAnbieterId);
}

/*------------------------------------------------------------------
 Bestätigungspflichtige Texte rendern (für Anzeige UND Speicherung)
------------------------------------------------------------------*/
$nowR       = now_render_glossar(NOW_GLOSSAR_RECHTE_ID);
$agb        = now_render_glossar(NOW_GLOSSAR_AGB_ID);
$datenschutz= now_render_glossar(NOW_GLOSSAR_DATENSCHUTZ_ID);

$nowRechteHtml   = $nowR        ? $nowR['html']         : '<p><em>Die Übermittlungsbedingungen konnten nicht geladen werden.</em></p>';
$nowRechteVer    = $nowR        ? $nowR['version']      : '';
$agbHtml         = $agb         ? $agb['html']          : '<p><em>AGB konnten nicht geladen werden.</em></p>';
$agbVersion      = $agb         ? $agb['version']       : '';
$datenschutzHtml = $datenschutz ? $datenschutz['html']  : '<p><em>Datenschutzerklärung konnte nicht geladen werden.</em></p>';
$datenschutzVer  = $datenschutz ? $datenschutz['version']: '';

/*------------------------------------------------------------------
 POST-Verarbeitung
------------------------------------------------------------------*/
$errors    = array();
$success   = false;
$savedFor  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $errors[] = 'Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
    }

    // Honeypot (muss leer bleiben)
    if (!empty($_POST['website'])) {
        $errors[] = 'Ungültige Eingabe.';
    }

    // Anbieter ermitteln: gültiger Link hat Vorrang, sonst Dropdown
    $postAnbieterId = 0;
    if ($lockedAnbieter) {
        $postAnbieterId = $lockedAnbieter['id'];
    } elseif (isset($_POST['anbieter_id'])) {
        $postAnbieterId = intval($_POST['anbieter_id']);
    }

    $anbieter = $postAnbieterId > 0 ? now_load_anbieter($postAnbieterId) : null;
    if (!$anbieter) {
        $errors[] = 'Bitte wählen Sie einen gültigen Anbieter aus.';
    } elseif (!$lockedAnbieter && !now_anbieter_eligible($anbieter['id'])) {
        // Dropdown-Pfad: nur in Frage kommende Anbieter (Stichwort „NOW ja“) zulassen.
        $errors[] = 'Dieser Anbieter kommt für die Übermittlung an „mein NOW“ derzeit nicht in Frage.';
    } elseif (intval($anbieter['now_zustimmung_fix']) > 0) {
        // Die Redaktion hat den Übermittlungs-Status fixiert – das Formular
        // darf anbieter.now_zustimmung dann nicht ändern.
        $errors[] = 'Der Übermittlungs-Status dieses Anbieters wurde von der Redaktion fest hinterlegt und kann '
                  . 'derzeit nicht über dieses Formular geändert werden. Bitte wenden Sie sich an ' . NOW_CONTACT_EMAIL . '.';
    }

    // Felder
    $vorname  = trim((string)($_POST['vorname']  ?? ''));
    $nachname = trim((string)($_POST['nachname'] ?? ''));
    if (mb_strlen($vorname) < 2)  { $errors[] = 'Bitte geben Sie Ihren Vornamen an.'; }
    if (mb_strlen($nachname) < 2) { $errors[] = 'Bitte geben Sie Ihren Nachnamen an.'; }

    // Pflicht-Checkboxen
    foreach (array('berechtigung', 'now_info', 'agb_wisy', 'datenschutz_wisy') as $cb) {
        if (empty($_POST[$cb])) {
            $errors[] = 'Bitte bestätigen Sie alle erforderlichen Punkte.';
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
            'aktion'              => 'erteilt',
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
        // CSRF-Token erneuern (Schutz gegen Doppel-Absenden)
        $_SESSION['now_csrf'] = bin2hex(random_bytes(16));
        $csrf = $_SESSION['now_csrf'];
    }
}

/*------------------------------------------------------------------
 Daten für die Anzeige
------------------------------------------------------------------*/
$anbieterListe = $lockedAnbieter ? array() : now_list_anbieter();
$queryString   = ($lockedAnbieter ? ('?a=' . $lockedAnbieter['id'] . '&t=' . now_token($lockedAnbieter['id'])) : '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Einwilligung zur Übermittlung an „mein NOW“</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <div class="header">
        <?php if (NOW_LOGO_PATH !== ''): ?>
            <img class="logo" src="<?= now_h(NOW_LOGO_PATH) ?>" alt="<?= now_h(NOW_PORTAL_NAME) ?>">
            <span class="arrow" aria-hidden="true">&rarr;</span>
        <?php endif; ?>
        <span class="now-logo">mein&nbsp;NOW</span>
    </div>

    <h1>Einwilligung zur Übermittlung Ihrer Daten an „mein&nbsp;NOW“</h1>

<?php if ($success): ?>

    <div class="box box-success">
        <h2>Vielen Dank!</h2>
        <p>Ihre Einwilligung für <strong><?= now_h($savedFor) ?></strong> wurde gespeichert.</p>
        <p>Eine Bestätigung mit einer Kopie der bestätigten Texte wurde an die in WISY
           hinterlegte Pflege-E-Mail-Adresse Ihres Anbieter-Eintrags gesendet.</p>
        <p>Sie können die Übermittlung an „mein&nbsp;NOW“ jederzeit und ohne Frist widerrufen.</p>
    </div>

<?php else: ?>

    <?php if ($errors): ?>
        <div class="box box-error">
            <ul>
                <?php foreach (array_unique($errors) as $e): ?>
                    <li><?= now_h($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <p>Die Bundesagentur für Arbeit betreibt mit
       <a href="<?= now_h(NOW_URL_PORTAL) ?>" target="_blank" rel="noopener">„mein&nbsp;NOW“</a>
       das nationale Online-Portal für berufliche Weiterbildung. Ihr Bundesland beteiligt sich mit dem
       Kursportal WISY als eine Datenquelle. Ihre Anbieter- und Kursdaten werden nur dann an
       „mein&nbsp;NOW“ übermittelt und dort veröffentlicht, wenn Sie hier ausdrücklich zustimmen.
       Mehr über „mein&nbsp;NOW“:
       <a href="<?= now_h(NOW_URL_UEBERUNS) ?>" target="_blank" rel="noopener"><?= now_h(NOW_URL_UEBERUNS) ?></a>.</p>

    <?php if ($lockedAnbieter && intval($lockedAnbieter['now_zustimmung_fix']) > 0): ?>

        <div class="box box-info">
            Der Übermittlungs-Status von <strong><?= now_h($lockedAnbieter['suchname']) ?></strong> wurde von der
            Redaktion fest hinterlegt und kann derzeit nicht über dieses Formular geändert werden.
            Bitte wenden Sie sich bei Fragen an
            <a href="mailto:<?= now_h(NOW_CONTACT_EMAIL) ?>"><?= now_h(NOW_CONTACT_EMAIL) ?></a>.
        </div>

    <?php else: ?>

    <?php if ($lockedAnbieter && intval($lockedAnbieter['now_zustimmung']) === 1): ?>
        <div class="box box-info">
            Für <strong><?= now_h($lockedAnbieter['suchname']) ?></strong> liegt bereits eine Einwilligung vor.
            Sie können sie hier erneut bestätigen (z.&nbsp;B. nach Aktualisierung der Texte).
        </div>
    <?php endif; ?>

    <form id="consentForm" action="<?= now_h($queryString) ?>" method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= now_h($csrf) ?>">
        <!-- Honeypot -->
        <div class="hp"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset>
            <legend>1. Anbieter</legend>
            <?php if ($lockedAnbieter): ?>
                <p class="anbieter-fix">
                    <?= now_h($lockedAnbieter['suchname']) ?>
                    <span class="muted">(ID <?= (int)$lockedAnbieter['id'] ?>)</span>
                </p>
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
                    <input type="text" id="vorname" name="vorname" required
                           value="<?= now_h($_POST['vorname'] ?? '') ?>">
                </div>
                <div>
                    <label for="nachname">Nachname</label>
                    <input type="text" id="nachname" name="nachname" required
                           value="<?= now_h($_POST['nachname'] ?? '') ?>">
                </div>
            </div>
            <label class="check">
                <input type="checkbox" name="berechtigung" value="1" required>
                Ich bin berechtigt, für den oben genannten Anbieter zuzustimmen.
            </label>
        </fieldset>

        <fieldset>
            <legend>3. Texte zur Kenntnis nehmen</legend>
            <div class="tabs">
                <div class="tab-buttons" role="tablist">
                    <button type="button" class="tab-link active" data-tab="tab1" role="tab">Übermittlung an „mein NOW“</button>
                    <button type="button" class="tab-link" data-tab="tab2" role="tab">AGB (WISY)</button>
                    <button type="button" class="tab-link" data-tab="tab3" role="tab">Datenschutz (WISY)</button>
                </div>
                <div id="tab1" class="tab-content active" role="tabpanel"><?= $nowRechteHtml ?></div>
                <div id="tab2" class="tab-content" role="tabpanel"><?= $agbHtml ?></div>
                <div id="tab3" class="tab-content" role="tabpanel"><?= $datenschutzHtml ?></div>
            </div>
        </fieldset>

        <fieldset>
            <legend>4. Bestätigung</legend>
            <label class="check">
                <input type="checkbox" name="now_info" value="1" required>
                Ich habe die <strong>Bedingungen zur Übermittlung an „mein&nbsp;NOW“</strong> gelesen und willige in die
                dort beschriebene Übermittlung, Nutzung und Bearbeitung meiner Anbieter- und Kursdaten ein.
            </label>
            <label class="check">
                <input type="checkbox" name="agb_wisy" value="1" required>
                Ich habe die <strong>AGB des Kursportals (WISY)</strong> gelesen und bin mit ihnen einverstanden.
            </label>
            <label class="check">
                <input type="checkbox" name="datenschutz_wisy" value="1" required>
                Ich habe die <strong>Datenschutzerklärung des Kursportals (WISY)</strong> gelesen und bin mit ihr einverstanden.
            </label>
        </fieldset>

        <button type="submit" id="submitBtn" disabled>Einwilligung absenden</button>
        <p class="muted small">Sie können die Übermittlung an „mein&nbsp;NOW“ jederzeit und ohne Frist widerrufen.</p>
    </form>

    <?php endif; /* now_zustimmung_fix */ ?>

<?php endif; ?>

</div>

<script src="assets/form.js"></script>
</body>
</html>

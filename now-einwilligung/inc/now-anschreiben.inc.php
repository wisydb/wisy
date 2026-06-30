<?php
/*******************************************************************************
 NOW-Einwilligung – Anschreiben (Einladung zur Einwilligung)
 ******************************************************************************
 Versionierter E-Mail-Text, der die Anbieter zur Einwilligung einlädt.
 Wird als HTML-Mail (mit Text-Alternative) versendet; die personalisierten
 Links werden je Anbieter eingesetzt.

 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Der gesamte Anschreiben-Text unten ist betreiber-/regionsspezifisch und sollte
 vor dem Einsatz angepasst werden (Tonalität, Bundesland, Signatur etc.).
 Portalname und Kontaktadresse kommen aus der Config (NOW_PORTAL_NAME,
 NOW_CONTACT_EMAIL).

 Datei in UTF-8. Der Versand (now_send_mail) setzt CharSet/MIME auf UTF-8,
 sodass Umlaute in der versandten Mail korrekt ankommen.
 *******************************************************************************/

if (!defined('NOW_EINW_IN')) {
    die('Direktaufruf nicht erlaubt.');
}

define('NOW_ANSCHREIBEN_VERSION', '2026-06-26');

/**
 * Baut Betreff, HTML- und Text-Fassung des Anschreibens für einen Anbieter.
 * @param array $anbieter  mind. mit 'id' (für die personalisierten Links)
 * @return array ['subject'=>..., 'html'=>..., 'text'=>...]  (alles UTF-8)
 */
function now_anschreiben($anbieter)
{
    $link     = now_build_link(intval($anbieter['id']));
    $widerruf = now_build_widerruf_link(intval($anbieter['id']));
    $kontakt  = defined('NOW_CONTACT_EMAIL') ? NOW_CONTACT_EMAIL : NOW_ADMIN_EMAIL;

    $subject  = 'mein NOW: Ihre Einwilligung zur bundesweiten Veröffentlichung Ihrer Weiterbildungsangebote';

    $h  = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $lk = $h($link);
    $wd = $h($widerruf);
    $ke = $h($kontakt);
    $portalName = NOW_PORTAL_NAME;   // Roh (Text-Fassung)
    $pn = $h($portalName);           // HTML-escaped (HTML-Fassung)

    // -------- HTML-Fassung (Fettdruck via <strong>) --------
    $html = <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;line-height:1.5;">
<p>Sehr geehrte Damen und Herren,</p>

<p>die Bundesagentur für Arbeit hat ein nationales Onlineportal für berufliche Weiterbildung
<a href="https://mein-now.de">https://mein-now.de</a> ins Leben gerufen.</p>

<p>In dem Portal ist neben umfassenden Informationen zu Weiterbildungsthemen auch eine
Weiterbildungsdatenbank zur bundesweiten Suche nach Weiterbildungsangeboten enthalten. Die Redaktion
von „meinNOW“ ermittelt und erfasst die Angebote nicht selbst, sondern bezieht die Daten von
öffentlich geförderten Weiterbildungsdatenbanken.</p>

<p>Auch Ihr Bundesland wird sich mit dem Kursportal WISY daran beteiligen. Ihre im
WISY-Kursportal gelisteten Kursangebote und Anbieterdaten werden jedoch nur dann auf „meinNOW.de“
veröffentlicht, wenn Sie uns dafür ausdrücklich Ihre Zustimmung erteilen. Ihr Angebot gewinnt dadurch
an bundesweiter Reichweite.</p>

<p>Nähere Infos über „meinNOW“ finden Sie unter
<a href="https://mein-now.de/ueber-uns">https://mein-now.de/ueber-uns</a>.</p>

<p>Wir weisen Sie darauf hin, dass sich die Redaktion von „meinNOW“ das Recht vorbehält, Kurse nicht
anzunehmen, die deren inhaltlichem Fokus nicht entsprechen, die bereits aus anderer Quelle bekannt sind
oder die den formellen Hürden nicht genügen. Ferner darf „mein NOW“ Datensätze redaktionell bearbeiten.
Dabei sollen keine inhaltlichen Veränderungen vorgenommen werden; Anpassungen sollen ausschließlich aus
qualitätssichernden, fachlichen, redaktionellen oder technischen Gründen erfolgen, wie z.&nbsp;B. der
Anpassung eines Titels.</p>

<p><strong>Wenn Sie damit einverstanden sind, dass Ihre Kurs- und Anbieterdaten aus dem WISY-Portal auch
im nationalen Portal „meinNOW“ veröffentlicht werden, geben Sie bitte über den folgenden persönlichen
Link Ihre Einwilligung zur Übermittlung an mein NOW:</strong></p>

<p><strong><a href="$lk">$lk</a></strong></p>

<p>Widerruf jederzeit möglich über:<br>
<a href="$wd">$wd</a></p>

<p>Mit freundlichen Grüßen<br>
Ihr $pn</p>

<p><strong>Bitte leiten Sie diese Mail an eine hierüber entscheidungsbefugte Person.</strong></p>

<p>Bitte richten Sie Fragen zu dem Portal „meinNOW.de“ ausschließlich an folgende Adresse:
<a href="mailto:$ke">$ke</a></p>
</div>
HTML;

    // -------- Text-Fassung (Alternative für Mailclients ohne HTML) --------
    $text = <<<TEXT
Sehr geehrte Damen und Herren,

die Bundesagentur für Arbeit hat ein nationales Onlineportal für berufliche Weiterbildung
https://mein-now.de ins Leben gerufen.

In dem Portal ist neben umfassenden Informationen zu Weiterbildungsthemen auch eine
Weiterbildungsdatenbank zur bundesweiten Suche nach Weiterbildungsangeboten enthalten. Die Redaktion
von „meinNOW“ ermittelt und erfasst die Angebote nicht selbst, sondern bezieht die Daten von
öffentlich geförderten Weiterbildungsdatenbanken.

Auch Ihr Bundesland wird sich mit dem Kursportal WISY daran beteiligen. Ihre im
WISY-Kursportal gelisteten Kursangebote und Anbieterdaten werden jedoch nur dann auf „meinNOW.de“
veröffentlicht, wenn Sie uns dafür ausdrücklich Ihre Zustimmung erteilen. Ihr Angebot gewinnt dadurch
an bundesweiter Reichweite.

Nähere Infos über „meinNOW“ finden Sie unter https://mein-now.de/ueber-uns.

Wir weisen Sie darauf hin, dass sich die Redaktion von „meinNOW“ das Recht vorbehält, Kurse nicht
anzunehmen, die deren inhaltlichem Fokus nicht entsprechen, die bereits aus anderer Quelle bekannt sind
oder die den formellen Hürden nicht genügen. Ferner darf „mein NOW“ Datensätze redaktionell bearbeiten.
Dabei sollen keine inhaltlichen Veränderungen vorgenommen werden; Anpassungen sollen ausschließlich aus
qualitätssichernden, fachlichen, redaktionellen oder technischen Gründen erfolgen, wie z. B. der
Anpassung eines Titels.

Wenn Sie damit einverstanden sind, dass Ihre Kurs- und Anbieterdaten aus dem WISY-Portal auch im
nationalen Portal „meinNOW“ veröffentlicht werden, geben Sie bitte über den folgenden persönlichen Link
Ihre Einwilligung zur Übermittlung an mein NOW:

$link

Widerruf jederzeit möglich über:
$widerruf

Mit freundlichen Grüßen
Ihr $portalName

Bitte leiten Sie diese Mail an eine hierüber entscheidungsbefugte Person.

Bitte richten Sie Fragen zu dem Portal „meinNOW.de“ ausschließlich an folgende Adresse: $kontakt
TEXT;

    return array('subject' => $subject, 'html' => $html, 'text' => $text);
}

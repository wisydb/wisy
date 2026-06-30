<?php
/*******************************************************************************
 NOW-Einwilligung – Konfiguration
 ******************************************************************************
 Zentrale Einstellungen für das Einwilligungs-Formular zur Übermittlung der
 Anbieter-/Kursdaten an das nationale Portal "mein NOW" (mein-now.de).

 Diese Datei wird in UTF-8 gespeichert. Die WISY-Datenbank arbeitet intern in
 ISO-8859-1 (latin1); die Umrechnung erledigen die Helfer in functions.inc.php.

 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Diese Datei enthält ausschließlich betreiberindividuelle Werte (IDs, Adressen,
 Namen, Geheimnisse). Vor dem Einsatz bei einem anderen WISY-Betreiber bzw. in
 einer anderen Region alle Werte prüfen und anpassen.
 *******************************************************************************/

if (!defined('NOW_EINW_IN')) {
    die('Direktaufruf nicht erlaubt.');
}

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Geheimnis zum Signieren der personalisierten Anbieter-Links (HMAC).
 BITTE VOR PRODUKTIVBETRIEB DURCH EINEN LANGEN ZUFALLSWERT ERSETZEN, z. B.:
   php -r "echo bin2hex(random_bytes(32));"
 Solange hier der Platzhalter steht, werden Tokens NICHT erzwungen
 (Dropdown-Auswahl bleibt möglich), aber die Linksignatur ist unsicher.
 ACHTUNG: Wird NOW_LINK_SECRET geändert, werden alle bisher versendeten
 Links ungültig.
------------------------------------------------------------------*/
define('NOW_LINK_SECRET', 'BITTE-AENDERN-zufaelliges-geheimnis');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Schutz für den Link-Generator (linkgenerator.php). Aufruf nur mit ?pw=...
------------------------------------------------------------------*/
define('NOW_ADMIN_PW', 'BITTE-AENDERN-admin-passwort');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Anzeigename des Kursportals (betreiber-/regionsspezifisch). Wird in der
 Formularausgabe, den Bestätigungs-Mails und im Anschreiben verwendet.
------------------------------------------------------------------*/
define('NOW_PORTAL_NAME', 'Kursportal (WISY)');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Name der betreibenden Stelle / Redaktion (z. B. die ausführende Gesellschaft).
------------------------------------------------------------------*/
define('NOW_OPERATOR_NAME', 'WISY-Betreiber');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Logo im Formularkopf (URL/Pfad relativ zur Domain). Leer lassen = kein Logo.
------------------------------------------------------------------*/
define('NOW_LOGO_PATH', '');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Glossar-/Ratgeber-IDs der zu bestätigenden Texte (Inhalte werden aus der DB
 gerendert, nicht im Code gepflegt):
  - AGB, Datenschutz und die mein-NOW-Übermittlungsbedingungen (1. Reiter).
------------------------------------------------------------------*/
define('NOW_GLOSSAR_AGB_ID',         679);
define('NOW_GLOSSAR_DATENSCHUTZ_ID', 11721);
define('NOW_GLOSSAR_RECHTE_ID',      14931);

/*------------------------------------------------------------------
 Versionskennung des gesamten Einwilligungs-„Pakets".
 Bei inhaltlichen Änderungen an den bestätigungspflichtigen Texten erhöhen –
 so lassen sich spätere Anschreiben/erneute Einwilligungen unterscheiden.
------------------------------------------------------------------*/
define('NOW_PAKET_VERSION', '2026-06-24');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Absender der Bestätigungs-/Anschreiben-Mails (Fallback, falls keine SMTP-/
 PHPMailer-Einstellungen im Portal hinterlegt sind). Versand bevorzugt per
 SMTP/PHPMailer über die Portaleinstellungen (mail.extern.* – s. functions).
------------------------------------------------------------------*/
define('NOW_MAIL_FROM',     'kursportal@example.org');
define('NOW_MAIL_FROMNAME', NOW_PORTAL_NAME);

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Administrator-/Redaktions-Adresse, die bei JEDER Einwilligung UND jedem
 Widerruf eine Kopie der Bestätigung erhält.
------------------------------------------------------------------*/
define('NOW_ADMIN_EMAIL', 'redaktion@example.org');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Kontaktadresse für Rückfragen, die im Anbieter-Anschreiben genannt wird.
------------------------------------------------------------------*/
define('NOW_CONTACT_EMAIL', 'redaktion@example.org');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Basis-URL für die ERZEUGTEN Formular-Links (Anschreiben, Redaktions-Maske).
 - NOW_BASEURL_FROM_REQUEST = true: Basis-URL wird aus dem aktuellen
   Request-Host abgeleitet. Dadurch erzeugen Aufrufe aus einer Sandbox
   (z. B. https://sandbox.<ihre-domain>/...) automatisch Links auf DIESELBE
   Sandbox – praktisch zum Testen. Produktivaufrufe liefern Produktiv-Links.
 - NOW_PUBLIC_BASEURL dient als Fallback (z. B. CLI) bzw. wird verwendet,
   wenn NOW_BASEURL_FROM_REQUEST = false gesetzt ist.
------------------------------------------------------------------*/
define('NOW_BASEURL_FROM_REQUEST', true);
define('NOW_PUBLIC_BASEURL', 'https://www.example.org');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Vorauswahl per Verwaltungsstichwort:
 Nur Anbieter mit Stichwort "NOW ja" kommen für das Anschreiben / die Auswahl
 in Frage; "NOW nein" ist explizit ausgeschlossen. NOW_STICHWORT_ANSCHREIBEN
 wird beim Versand des Anschreibens automatisch gesetzt (Übersicht, wer bereits
 angeschrieben wurde). IDs = Stichwort-IDs in WISY.
------------------------------------------------------------------*/
define('NOW_STICHWORT_JA',          1812461); // "NOW ja"
define('NOW_STICHWORT_NEIN',        1812471); // "NOW nein"
define('NOW_STICHWORT_ANSCHREIBEN', 2128841); // "NOW Anschreiben initial"
// Filterung aktiv? (false = alle freigeschalteten Anbieter zulassen)
define('NOW_FILTER_BY_STICHWORT', true);

/*------------------------------------------------------------------
 Externe Infos / Links zum nationalen Portal (allgemeingültig, BA-betrieben).
------------------------------------------------------------------*/
define('NOW_URL_UEBERUNS', 'https://mein-now.de/ueber-uns');
define('NOW_URL_PORTAL',   'https://mein-now.de');

// WISY-Core, in dem die Wiki->HTML-Konvertierung liegt
define('NOW_WISY_CORE', 'core51');

/*------------------------------------------------------------------
 ###WISY-BETREIBER-INDIVIDUELL-GGF-TEXT-ERSETZEN###
 Portal-ID, aus der die Einstellungen geladen werden – insbesondere die
 SMTP-/PHPMailer-Zugangsdaten (mail.extern.*). Eine ID > 0 hat Vorrang vor der
 automatischen Host-Erkennung im Bootstrap; 0 = nur Host-Erkennung.
------------------------------------------------------------------*/
define('NOW_PORTAL_ID', 0);

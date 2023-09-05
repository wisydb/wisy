SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

--
-- Tabellenstruktur fuer Tabelle `anbieter`
--

CREATE TABLE `anbieter` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vollstaendigkeit` int(11) NOT NULL DEFAULT 0,
  `suchname` varchar(200) NOT NULL DEFAULT '',
  `suchname_sorted` varchar(200) NOT NULL DEFAULT '',
  `freigeschaltet` int(11) NOT NULL DEFAULT 1,
  `typ` int(11) NOT NULL DEFAULT 0,
  `postname` varchar(200) NOT NULL DEFAULT '',
  `postname_sorted` varchar(200) NOT NULL DEFAULT '',
  `strasse` varchar(200) NOT NULL DEFAULT '',
  `plz` varchar(200) NOT NULL DEFAULT '',
  `bezirk` varchar(200) NOT NULL,
  `adresszusatz` varchar(200) NOT NULL DEFAULT '',
  `ort` varchar(200) NOT NULL DEFAULT '',
  `stadtteil` varchar(200) NOT NULL DEFAULT '',
  `land` varchar(200) NOT NULL DEFAULT '',
  `rollstuhlgerecht` int(11) NOT NULL DEFAULT 0,
  `leitung_name` varchar(200) NOT NULL DEFAULT '',
  `leitung_tel` varchar(200) NOT NULL DEFAULT '',
  `thema` int(11) NOT NULL DEFAULT 0,
  `din_nr` varchar(200) NOT NULL DEFAULT '',
  `din_nr_sorted` varchar(200) NOT NULL DEFAULT '',
  `gruendungsjahr` int(11) NOT NULL DEFAULT 0,
  `rechtsform` int(11) NOT NULL DEFAULT 0,
  `firmenportraet` longtext NOT NULL,
  `logo` longtext NOT NULL,
  `logo_blob` longblob NOT NULL,
  `logo_name` varchar(200) NOT NULL DEFAULT '',
  `logo_mime` varchar(200) NOT NULL DEFAULT '',
  `logo_bytes` int(11) NOT NULL DEFAULT 0,
  `logo_w` int(11) NOT NULL DEFAULT 0,
  `logo_h` int(11) NOT NULL DEFAULT 0,
  `logo_ext` int(11) NOT NULL DEFAULT 0,
  `logo_rechte` text NOT NULL,
  `logo_position` int(11) DEFAULT NULL,
  `homepage` varchar(200) NOT NULL DEFAULT '',
  `pruefsiegel_seit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anspr_name` varchar(200) NOT NULL DEFAULT '',
  `anspr_zeit` longtext NOT NULL,
  `anspr_tel` varchar(200) NOT NULL DEFAULT '',
  `anspr_fax` varchar(200) NOT NULL DEFAULT '',
  `anspr_email` varchar(200) NOT NULL DEFAULT '',
  `kursplatzanfrage` int(11) NOT NULL DEFAULT 0,
  `partnernetz` int(11) NOT NULL DEFAULT 0,
  `pflege_name` varchar(200) NOT NULL DEFAULT '',
  `pflege_tel` varchar(200) NOT NULL DEFAULT '',
  `pflege_fax` varchar(200) NOT NULL DEFAULT '',
  `pflege_email` varchar(200) NOT NULL DEFAULT '',
  `pflege_weg` int(11) NOT NULL DEFAULT 1,
  `pflege_prot` int(11) NOT NULL DEFAULT 1,
  `pflege_akt` int(11) NOT NULL DEFAULT 0,
  `pflege_passwort` varchar(200) NOT NULL DEFAULT '',
  `pflege_pweinst` int(11) NOT NULL DEFAULT 0,
  `pflege_msg` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `notizen_fix` longtext NOT NULL,
  `in_wisy_seit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aufnahme_durch` int(11) NOT NULL DEFAULT 0,
  `herkunft` int(11) NOT NULL DEFAULT 0,
  `herkunftsID` varchar(200) NOT NULL,
  `wisy_annr` varchar(200) DEFAULT NULL,
  `bu_annr` varchar(200) DEFAULT NULL,
  `foerder_annr` varchar(200) DEFAULT NULL,
  `fu_annr` varchar(200) DEFAULT NULL,
  `azwv_annr` varchar(200) DEFAULT NULL,
  `settings` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `anbieter`
--
DELIMITER $$
CREATE TRIGGER `anbieter_bi_v9_10_1` BEFORE INSERT ON `anbieter` FOR EACH ROW BEGIN 
SET auto_increment_increment = 10;
SET auto_increment_offset = 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `anbieter_billing`
--

CREATE TABLE `anbieter_billing` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anbieter_id` int(11) NOT NULL DEFAULT 0,
  `portal_id` int(11) NOT NULL DEFAULT 0,
  `bill_type` int(11) NOT NULL DEFAULT 0,
  `credits` int(11) NOT NULL DEFAULT 0,
  `eur` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '0.00',
  `raw_data` text COLLATE latin1_general_ci NOT NULL,
  `notizen` text COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `anbieter_billing`
--
DELIMITER $$
CREATE TRIGGER `anbieter_billing_bi_v9_10_1` BEFORE INSERT ON `anbieter_billing` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `anbieter_promote`
--

CREATE TABLE `anbieter_promote` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kurs_id` int(11) NOT NULL DEFAULT 0,
  `anbieter_id` int(11) NOT NULL DEFAULT 0,
  `portal_id` int(11) NOT NULL DEFAULT 0,
  `promote_active` int(11) NOT NULL DEFAULT 0,
  `promote_mode` char(16) COLLATE latin1_general_ci NOT NULL,
  `promote_param` char(16) COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `anbieter_promote`
--
DELIMITER $$
CREATE TRIGGER `anbieter_promote_bi_v9_10_1` BEFORE INSERT ON `anbieter_promote` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `anbieter_promote_log`
--

CREATE TABLE `anbieter_promote_log` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anbieter_id` int(11) NOT NULL DEFAULT 0,
  `portal_id` int(11) NOT NULL DEFAULT 0,
  `kurs_id` int(11) NOT NULL DEFAULT 0,
  `event_type` int(11) NOT NULL DEFAULT 0,
  `lparam` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `anbieter_promote_log`
--
DELIMITER $$
CREATE TRIGGER `anbieter_promote_log_bi_v9_10_1` BEFORE INSERT ON `anbieter_promote_log` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `anbieter_stichwort`
--

CREATE TABLE `anbieter_stichwort` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `anbieter_verweis`
--

CREATE TABLE `anbieter_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `apikeys`
--

CREATE TABLE `apikeys` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `apikey` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `flags` int(11) NOT NULL,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `apikeys`
--
DELIMITER $$
CREATE TRIGGER `apikeys_bi_v9_10_1` BEFORE INSERT ON `apikeys` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `apikeys_usergrp`
--

CREATE TABLE `apikeys_usergrp` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `durchfuehrung`
--

CREATE TABLE `durchfuehrung` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `nr` varchar(200) NOT NULL DEFAULT '',
  `nr_sorted` varchar(200) NOT NULL DEFAULT '',
  `stunden` int(11) NOT NULL DEFAULT 0,
  `teilnehmer` int(11) NOT NULL DEFAULT 0,
  `preis` int(11) NOT NULL DEFAULT -1,
  `preishinweise` varchar(500) NOT NULL DEFAULT '',
  `sonderpreis` int(11) NOT NULL DEFAULT -1,
  `sonderpreistage` int(11) NOT NULL DEFAULT 0,
  `beginn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ende` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `beginnoptionen` int(11) NOT NULL DEFAULT 0,
  `dauer` int(11) NOT NULL DEFAULT 0,
  `dauer_fix` tinyint(1) NOT NULL,
  `zeit_von` varchar(200) NOT NULL DEFAULT '',
  `zeit_bis` varchar(200) NOT NULL DEFAULT '',
  `kurstage` int(11) NOT NULL DEFAULT 0,
  `tagescode` int(11) NOT NULL DEFAULT 1,
  `gebaeude` varchar(200) DEFAULT NULL,
  `strasse` varchar(200) NOT NULL DEFAULT '',
  `plz` varchar(200) NOT NULL DEFAULT '',
  `bezirk` varchar(200) NOT NULL,
  `ort` varchar(200) NOT NULL DEFAULT '',
  `stadtteil` varchar(200) NOT NULL DEFAULT '',
  `land` varchar(200) NOT NULL DEFAULT '',
  `rollstuhlgerecht` int(11) NOT NULL DEFAULT 0,
  `bemerkungen` longtext NOT NULL,
  `url` varchar(200) NOT NULL DEFAULT '',
  `bg_nummer` varchar(100) DEFAULT NULL,
  `bu_dnummer` varchar(100) DEFAULT NULL,
  `bg_nummer_count` smallint(6) DEFAULT NULL,
  `herkunft` int(11) NOT NULL DEFAULT 0,
  `herkunftsID` varchar(200) NOT NULL,
  `wisy_dnr` varchar(200) DEFAULT NULL,
  `fu_dnr` varchar(200) DEFAULT NULL,
  `foerder_dnr` varchar(200) DEFAULT NULL,
  `azwv_dnr` varchar(200) DEFAULT NULL,
  `ort_originalwert` varchar(200) DEFAULT NULL,
  `ort_aenderungsdatum` datetime DEFAULT NULL,
  `ort_korrigiert` varchar(200) DEFAULT NULL,
  `plz_originalwert` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `durchfuehrung`
--
DELIMITER $$
CREATE TRIGGER `durchfuehrung_bi_v9_10_1` BEFORE INSERT ON `durchfuehrung` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `url` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `ip` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `rating` int(11) NOT NULL,
  `descr` text COLLATE latin1_general_ci NOT NULL,
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `email` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `feedback`
--
DELIMITER $$
CREATE TRIGGER `feedback_bi_v9_10_1` BEFORE INSERT ON `feedback` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `geodb_locations`
--

CREATE TABLE `geodb_locations` (
  `id` int(11) NOT NULL DEFAULT 0,
  `typ` int(11) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `name_int` varchar(250) DEFAULT NULL,
  `gs` varchar(8) DEFAULT NULL,
  `adm0` varchar(2) DEFAULT NULL,
  `adm1` varchar(2) DEFAULT NULL,
  `adm2` varchar(250) DEFAULT NULL,
  `adm3` varchar(250) DEFAULT NULL,
  `adm4` varchar(250) DEFAULT NULL,
  `ort` varchar(250) DEFAULT NULL,
  `ortsteil` varchar(250) DEFAULT NULL,
  `gemteil` varchar(250) DEFAULT NULL,
  `wohnplatz` varchar(250) DEFAULT NULL,
  `breite` float DEFAULT NULL,
  `laenge` float DEFAULT NULL,
  `kfz` varchar(3) DEFAULT NULL,
  `plz` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `geowisii`
--

CREATE TABLE `geowisii` (
  `id` int(11) NOT NULL,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ort_hash` varchar(200) NOT NULL DEFAULT '',
  `lat` int(11) NOT NULL DEFAULT 0,
  `lng` int(11) NOT NULL DEFAULT 0,
  `durchf_ids` longtext NOT NULL,
  `accuracy` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `glossar`
--

CREATE TABLE `glossar` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `begriff` varchar(200) NOT NULL DEFAULT '',
  `begriff_sorted` varchar(200) NOT NULL DEFAULT '',
  `erklaerung` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `notizen_fix` longtext NOT NULL,
  `wikipedia` varchar(200) NOT NULL DEFAULT '',
  `freigeschaltet` int(11) NOT NULL DEFAULT 2,
  `status` int(11) NOT NULL DEFAULT 1,
  `rssfeed` varchar(200) DEFAULT NULL,
  `von` date NOT NULL DEFAULT '1900-01-01',
  `bis` date NOT NULL DEFAULT '2100-12-31'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `glossar`
--
DELIMITER $$
CREATE TRIGGER `glossar_bi_v9_10_1` BEFORE INSERT ON `glossar` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `itb_map_regions_rlp`
--

CREATE TABLE IF NOT EXISTS `itb_map_regions_rlp` (
  `id` tinytext NOT NULL,
  `kr` tinytext NOT NULL,
  `ge` tinytext NOT NULL,
  `vg` tinytext NOT NULL,
  `plz` int(11) NOT NULL,
  `name` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `kurse`
--

CREATE TABLE `kurse` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `titel` varchar(500) NOT NULL,
  `titel_sorted` varchar(500) NOT NULL,
  `freigeschaltet` int(11) NOT NULL DEFAULT 1,
  `anbieter` int(11) NOT NULL DEFAULT 0,
  `beschreibung` longtext NOT NULL,
  `thema` int(11) NOT NULL DEFAULT 0,
  `notizen` longtext NOT NULL,
  `notizen_fix` longtext DEFAULT NULL,
  `bu_nummer` varchar(100) DEFAULT NULL,
  `res_nummer` varchar(100) DEFAULT NULL,
  `vollstaendigkeit` int(11) NOT NULL DEFAULT 0,
  `org_titel` varchar(500) DEFAULT NULL,
  `fu_knr` varchar(200) DEFAULT NULL,
  `foerder_knr` varchar(200) DEFAULT NULL,
  `azwv_knr` varchar(200) DEFAULT NULL,
  `msgtooperator` varchar(200) NOT NULL,
  `msgtooperator_unterrichtsart` varchar(500) NOT NULL,
  `wisy_knr` varchar(200) NOT NULL COMMENT 'deprecated',
  `orig_titel_sorted` varchar(200) DEFAULT NULL COMMENT 'Originale title_sorted vor Ueberarbeitung, Entfernung Sonderzeichen, Umwandlung Umlaute',
  `x_df_lastinserted` datetime NOT NULL,
  `x_df_lastmodified` datetime NOT NULL,
  `x_df_lastdeleted` datetime NOT NULL,
  `x_df_lastinserted_origin` varchar(100) NOT NULL,
  `x_df_lastmodified_origin` varchar(100) NOT NULL,
  `x_df_lastdeleted_origin` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `kurse`
--
DELIMITER $$
CREATE TRIGGER `kurse_bi_v9_10_1` BEFORE INSERT ON `kurse` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `kurse_durchfuehrung`
--

CREATE TABLE `kurse_durchfuehrung` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `secondary_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `kurse_stichwort`
--

CREATE TABLE `kurse_stichwort` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `kurse_verweis`
--

CREATE TABLE `kurse_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Nachfolgende Tabellen werden nur verwendet, wenn man
-- den OpenThesaurus von openthesaurus.de importiert.
-- Wenn diese Tabellen nicht existieren, wird das entsprechende
-- Feature in der Suche einfach uebersprungen.

--
-- Tabellenstruktur fuer Tabelle `openth_category`
--
--
-- CREATE TABLE `openth_category` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `category_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
--  `is_disabled` bit(1) DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_category_link`
--
--
-- CREATE TABLE `openth_category_link` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `category_id` bigint(20) NOT NULL,
--  `synset_id` bigint(20) NOT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_synset`
--
--
-- CREATE TABLE `openth_synset` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `evaluation` int(11) DEFAULT NULL,
--  `is_visible` bit(1) NOT NULL,
--  `original_id` int(11) DEFAULT NULL,
--  `preferred_category_id` bigint(20) DEFAULT NULL,
--  `section_id` bigint(20) DEFAULT NULL,
--  `source_id` bigint(20) DEFAULT NULL,
--  `synset_preferred_term` varchar(255) DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
--
-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_synset_link`
--
--
-- CREATE TABLE `openth_synset_link` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `evaluation_status` int(11) DEFAULT NULL,
--  `fact_count` int(11) DEFAULT NULL,
--  `link_type_id` bigint(20) NOT NULL,
--  `synset_id` bigint(20) NOT NULL,
-- `target_synset_id` bigint(20) NOT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_tag`
--
--
-- CREATE TABLE `openth_tag` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `color` varchar(255) DEFAULT NULL,
--  `created` datetime NOT NULL,
--  `created_by` varchar(255) NOT NULL,
--  `name` varchar(255) NOT NULL,
--  `short_name` varchar(255) DEFAULT NULL,
--  `is_visible` bit(1) DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_term`
--

-- CREATE TABLE `openth_term` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `language_id` bigint(20) NOT NULL,
--  `level_id` bigint(20) DEFAULT NULL,
--  `normalized_word` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
--  `original_id` int(11) DEFAULT NULL,
--  `synset_id` bigint(20) NOT NULL,
--  `user_comment` varchar(400) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
--  `word` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
--  `normalized_word2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_term_level`
--
--
-- CREATE TABLE `openth_term_level` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `level_name` varchar(255) NOT NULL,
--  `short_level_name` varchar(255) NOT NULL,
--  `sort_value` int(11) DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_term_link`
--
--
-- CREATE TABLE `openth_term_link` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `link_type_id` bigint(20) NOT NULL,
--  `target_term_id` bigint(20) NOT NULL,
--  `term_id` bigint(20) NOT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_term_link_type`
--
--
-- CREATE TABLE `openth_term_link_type` (
--  `id` bigint(20) NOT NULL,
--  `version` bigint(20) NOT NULL,
--  `link_name` varchar(255) NOT NULL,
--  `other_direction_link_name` varchar(255) NOT NULL,
--  `verb_name` varchar(255) NOT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_term_tag`
--
--
-- CREATE TABLE `openth_term_tag` (
--  `term_tags_id` bigint(20) DEFAULT NULL,
--  `tag_id` bigint(20) DEFAULT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `openth_word_mapping`
--
--
-- CREATE TABLE `openth_word_mapping` (
--  `fullform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
--  `baseform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
-- ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Mapping Vollform nach Grundform aus Morphy';

-- Ende: openthesaurus --

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `plztool`
--

CREATE TABLE `plztool` (
  `id` int(11) NOT NULL,
  `strasse_norm` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort_norm` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `plz` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `stadtteil` varchar(255) COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `plztool2`
--

CREATE TABLE `plztool2` (
  `id` int(11) NOT NULL,
  `plz` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort` varchar(255) COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `plz_ortscron`
--

CREATE TABLE `plz_ortscron` (
  `id` int(11) NOT NULL,
  `plz` varchar(255) CHARACTER SET latin1 NOT NULL,
  `ort` varchar(255) CHARACTER SET latin1 NOT NULL,
  `bundesland` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `osm_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `portale`
--

CREATE TABLE `portale` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(200) NOT NULL DEFAULT '',
  `kurzname` varchar(200) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT 1,
  `domains` varchar(400) NOT NULL,
  `filter` longtext NOT NULL,
  `skindir` varchar(200) NOT NULL DEFAULT '',
  `css` longtext NOT NULL,
  `css_gz` longtext NOT NULL,
  `bodystart` longtext NOT NULL,
  `einstellungen` longtext NOT NULL,
  `einstellungen_hinweise` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `notizen_fix` longtext NOT NULL,
  `spalten` int(11) NOT NULL DEFAULT 65535,
  `horizont` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `horizontende` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `themen_erlauben` varchar(200) NOT NULL DEFAULT '*',
  `themen_verbieten` varchar(200) NOT NULL DEFAULT '',
  `betreiberID` int(11) NOT NULL DEFAULT 660,
  `print_img` varchar(255) DEFAULT NULL,
  `menuswitch` int(11) UNSIGNED NOT NULL DEFAULT 63,
  `logo_1` varchar(255) DEFAULT NULL,
  `logo_2` varchar(255) DEFAULT NULL,
  `logo_1_href` varchar(255) DEFAULT NULL,
  `logo_2_href` varchar(255) DEFAULT NULL,
  `qual_logo` varchar(255) DEFAULT NULL,
  `qual_logo_gloss` int(11) DEFAULT NULL,
  `qual_logo_stich` int(11) DEFAULT NULL,
  `gruppe` int(11) DEFAULT NULL,
  `iwwb` int(11) DEFAULT NULL,
  `iwwb_style` varchar(255) DEFAULT NULL,
  `iwwb_filter` longtext DEFAULT NULL,
  `einstcache` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `portale`
--
DELIMITER $$
CREATE TRIGGER `portale_bi_v9_10_1` BEFORE INSERT ON `portale` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `statistik`
--

CREATE TABLE `statistik` (
  `ID` int(11) NOT NULL,
  `IPhash` varchar(32) NOT NULL,
  `domain` varchar(200) NOT NULL,
  `sw_raw` varchar(500) NOT NULL,
  `sw_tags` text NOT NULL,
  `user_agent` varchar(200) NOT NULL,
  `erfolg` tinyint(1) NOT NULL,
  `ajaxfenster` tinyint(1) NOT NULL,
  `fehlervorschlag` tinyint(1) NOT NULL,
  `herkunft` int(11) NOT NULL,
  `naechsteaktion` int(11) NOT NULL,
  `search_hash` varchar(32) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `stichwoerter`
--

CREATE TABLE `stichwoerter` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `stichwort` varchar(200) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `stichwort_sorted` varchar(200) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `zusatzinfo` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `eigenschaften` int(11) NOT NULL DEFAULT 0,
  `thema` int(11) NOT NULL DEFAULT 0,
  `glossar` int(11) NOT NULL DEFAULT 0,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL,
  `notizen_fix` longtext COLLATE latin1_general_ci NOT NULL,
  `scope_note` longtext COLLATE latin1_general_ci DEFAULT NULL,
  `algorithmus` longtext COLLATE latin1_general_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `stichwoerter`
--
DELIMITER $$
CREATE TRIGGER `stichwoerter_bi_v9_10_1` BEFORE INSERT ON `stichwoerter` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `stichwoerter_verweis`
--

CREATE TABLE `stichwoerter_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `stichwoerter_verweis2`
--

CREATE TABLE `stichwoerter_verweis2` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `themen`
--

CREATE TABLE `themen` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `thema` varchar(200) NOT NULL DEFAULT '',
  `thema_sorted` varchar(200) NOT NULL DEFAULT '',
  `kuerzel` varchar(200) NOT NULL DEFAULT '',
  `kuerzel_sorted` varchar(200) NOT NULL DEFAULT '',
  `glossar` int(11) NOT NULL DEFAULT 0,
  `notizen` longtext NOT NULL,
  `scope_note` longtext DEFAULT NULL,
  `algorithmus` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `themen`
--
DELIMITER $$
CREATE TRIGGER `themen_bi_v9_10_1` BEFORE INSERT ON `themen` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `msgid` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `von_name` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `von_email` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `antwortan_name` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `antwortan_email` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `betreff` varchar(500) COLLATE latin1_general_ci NOT NULL,
  `nachricht_txt` text COLLATE latin1_general_ci NOT NULL,
  `nachricht_html` text COLLATE latin1_general_ci NOT NULL,
  `groesse` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Trigger `tickets`
--
DELIMITER $$
CREATE TRIGGER `tickets_bi_v9_10_1` BEFORE INSERT ON `tickets` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `loginname` varchar(200) NOT NULL DEFAULT '',
  `password` varchar(200) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `phone` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL DEFAULT '',
  `attr_role` int(11) NOT NULL DEFAULT 0,
  `msg_to_user` longtext NOT NULL,
  `access` longtext NOT NULL,
  `settings` longtext NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login_id` varchar(32) NOT NULL,
  `last_login_error` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `num_login_errors` int(11) NOT NULL DEFAULT 0,
  `remembered` longtext NOT NULL,
  `notizen` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `user`
--
DELIMITER $$
CREATE TRIGGER `user_bi_v9_10_1` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

INSERT INTO user (loginname, access) VALUES('root', '*.*:rwnd;');

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `user_attr_grp`
--

CREATE TABLE `user_attr_grp` (
  `primary_id` int(11) NOT NULL DEFAULT 0,
  `attr_id` int(11) NOT NULL DEFAULT 0,
  `structure_pos` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `user_fuzzy`
--

CREATE TABLE `user_fuzzy` (
  `word` varchar(128) NOT NULL DEFAULT '',
  `soundex` varchar(128) NOT NULL DEFAULT '',
  `metaphone` varchar(128) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `user_grp`
--

CREATE TABLE `user_grp` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shortname` varchar(200) NOT NULL DEFAULT '',
  `password` varchar(200) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `notizen` longtext DEFAULT NULL,
  `settings` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `user_grp`
--
DELIMITER $$
CREATE TRIGGER `user_grp_bi_v9_10_1` BEFORE INSERT ON `user_grp` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `sync_src` int(11) NOT NULL DEFAULT 2,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_modified` int(11) NOT NULL DEFAULT 0,
  `user_grp` int(11) NOT NULL DEFAULT 0,
  `user_access` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(200) NOT NULL DEFAULT '',
  `text_to_confirm` longtext NOT NULL,
  `email_notify` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Trigger `user_roles`
--
DELIMITER $$
CREATE TRIGGER `user_roles_bi_v9_10_1` BEFORE INSERT ON `user_roles` FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_confirm`
--

CREATE TABLE `x_cache_confirm` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_latlng_perm`
--

CREATE TABLE `x_cache_latlng_perm` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_latlng_search`
--

CREATE TABLE `x_cache_latlng_search` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_promoteips`
--

CREATE TABLE `x_cache_promoteips` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_rss`
--

CREATE TABLE `x_cache_rss` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_search`
--

CREATE TABLE `x_cache_search` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_sitemap`
--

CREATE TABLE `x_cache_sitemap` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longblob NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_cache_tagcloud`
--

CREATE TABLE `x_cache_tagcloud` (
  `ckey` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_kurse`
--

CREATE TABLE `x_kurse` (
  `kurs_id` int(11) NOT NULL,
  `beginn` date NOT NULL DEFAULT '0000-00-00',
  `beginn_last` date NOT NULL DEFAULT '0000-00-00',
  `dauer` int(11) NOT NULL DEFAULT 0,
  `preis` int(11) NOT NULL DEFAULT -1,
  `anbieter_sortonly` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `bezirk` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `ort_sortonly` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort_sortonly_secondary` text COLLATE latin1_general_ci NOT NULL,
  `begmod_hash` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `begmod_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_kurse_latlng`
--

CREATE TABLE `x_kurse_latlng` (
  `kurs_id` int(11) NOT NULL,
  `lat` int(11) NOT NULL,
  `lng` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_kurse_orte`
--

CREATE TABLE `x_kurse_orte` (
  `kurs_id` int(11) NOT NULL,
  `ort` varchar(500) NOT NULL,
  `ort_sortonly` varchar(500) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_kurse_plz`
--

CREATE TABLE `x_kurse_plz` (
  `kurs_id` int(11) NOT NULL,
  `plz` char(8) COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_kurse_tags`
--

CREATE TABLE `x_kurse_tags` (
  `kurs_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_logins`
--

CREATE TABLE `x_logins` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip` varbinary(255) NOT NULL,
  `freischalten` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_querystats`
--

CREATE TABLE `x_querystats` (
  `id` int(11) NOT NULL,
  `q` varchar(1500) NOT NULL,
  `ausloeser` varchar(2) NOT NULL,
  `quelle` varchar(10) NOT NULL,
  `tag` int(1) NOT NULL,
  `tag_type` tinyint(4) NOT NULL,
  `count` int(11) NOT NULL,
  `date_created` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_searchqueries`
--

CREATE TABLE `x_searchqueries` (
  `ukey` varchar(32) NOT NULL,
  `datum_uhrzeit` varchar(19) DEFAULT NULL,
  `portal_id` int(11) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `src` varchar(2) DEFAULT NULL,
  `query1` varchar(255) DEFAULT NULL,
  `query2` varchar(255) DEFAULT NULL,
  `query3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_state`
--

CREATE TABLE `x_state` (
  `skey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `svalue` longtext COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_tags`
--

CREATE TABLE `x_tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_eigenschaften` int(11) NOT NULL DEFAULT -1,
  `tag_descr` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_type` int(11) NOT NULL DEFAULT 0,
  `tag_help` int(11) NOT NULL,
  `tag_soundex` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_metaphone` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_tags_freq`
--

CREATE TABLE `x_tags_freq` (
  `tag_id` int(11) NOT NULL,
  `portal_id` int(11) NOT NULL,
  `tag_freq` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `x_tags_syn`
--

CREATE TABLE `x_tags_syn` (
  `tag_id` int(11) NOT NULL,
  `lemma_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


--
-- Indizes der exportierten Tabellen
--

--
-- Indizes fuer die Tabelle `x_searchqueries`
--

ALTER TABLE `x_searchqueries`
  ADD PRIMARY KEY (`ukey`);

--
-- Indizes fuer die Tabelle `anbieter`
--
ALTER TABLE `anbieter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `suchname` (`suchname`),
  ADD KEY `suchname_sorted` (`suchname_sorted`),
  ADD KEY `postname` (`postname`),
  ADD KEY `postname_sorted` (`postname_sorted`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `plz` (`plz`),
  ADD KEY `din_nr` (`din_nr`),
  ADD KEY `din_nr_sorted` (`din_nr_sorted`),
  ADD KEY `freigeschaltet` (`freigeschaltet`);
ALTER TABLE `anbieter` ADD FULLTEXT KEY `textfields` (`din_nr`,`suchname`,`postname`,`strasse`,`ort`,`stadtteil`,`anspr_name`,`notizen`,`leitung_name`,`homepage`,`firmenportraet`,`pflege_name`);

--
-- Indizes fuer die Tabelle `anbieter_billing`
--
ALTER TABLE `anbieter_billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date_created` (`date_created`),
  ADD KEY `anbieter_id` (`anbieter_id`);

--
-- Indizes fuer die Tabelle `anbieter_promote`
--
ALTER TABLE `anbieter_promote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kurs_id` (`kurs_id`),
  ADD KEY `anbieter_id` (`anbieter_id`),
  ADD KEY `portal_id` (`portal_id`);

--
-- Indizes fuer die Tabelle `anbieter_promote_log`
--
ALTER TABLE `anbieter_promote_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kurs_id` (`kurs_id`),
  ADD KEY `date_created` (`date_created`),
  ADD KEY `anbieter_id` (`anbieter_id`);

--
-- Indizes fuer die Tabelle `anbieter_stichwort`
--
ALTER TABLE `anbieter_stichwort`
  ADD KEY `anbieter_stichwort_i0` (`primary_id`),
  ADD KEY `anbieter_stichwort_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `anbieter_verweis`
--
ALTER TABLE `anbieter_verweis`
  ADD KEY `anbieter_verweis_i0` (`primary_id`),
  ADD KEY `anbieter_verweis_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `apikeys`
--
ALTER TABLE `apikeys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `apikey` (`apikey`);

--
-- Indizes fuer die Tabelle `apikeys_usergrp`
--
ALTER TABLE `apikeys_usergrp`
  ADD KEY `anbieter_stichwort_i0` (`primary_id`),
  ADD KEY `anbieter_stichwort_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `durchfuehrung`
--
ALTER TABLE `durchfuehrung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `nr` (`nr`),
  ADD KEY `nr_sorted` (`nr_sorted`),
  ADD KEY `beginn` (`beginn`),
  ADD KEY `preis` (`preis`),
  ADD KEY `tagescode` (`tagescode`),
  ADD KEY `sonderpreistage` (`sonderpreistage`);
ALTER TABLE `durchfuehrung` ADD FULLTEXT KEY `textfields` (`nr`,`strasse`,`plz`,`ort`,`bemerkungen`,`stadtteil`,`preishinweise`);

--
-- Indizes fuer die Tabelle `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `url` (`url`),
  ADD KEY `ip` (`ip`);

--
-- Indizes fuer die Tabelle `geodb_locations`
--
ALTER TABLE `geodb_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `id_2` (`id`),
  ADD KEY `ort` (`ort`),
  ADD KEY `staat` (`adm0`),
  ADD KEY `land` (`adm1`),
  ADD KEY `regbez` (`adm2`),
  ADD KEY `landkreis` (`adm3`);

--
-- Indizes fuer die Tabelle `geowisii`
--
ALTER TABLE `geowisii`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ort_hash` (`ort_hash`);

--
-- Indizes fuer die Tabelle `glossar`
--
ALTER TABLE `glossar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `begriff` (`begriff`),
  ADD KEY `begriff_sorted` (`begriff_sorted`),
  ADD KEY `date_modified` (`date_modified`);
ALTER TABLE `glossar` ADD FULLTEXT KEY `begriff_erklaerung` (`begriff`,`erklaerung`);

--
-- Indizes fuer die Tabelle `kurse`
--
ALTER TABLE `kurse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `titel` (`titel`),
  ADD KEY `titel_sorted` (`titel_sorted`),
  ADD KEY `thema` (`thema`),
  ADD KEY `anbieter` (`anbieter`),
  ADD KEY `freigeschaltet` (`freigeschaltet`);
ALTER TABLE `kurse` ADD FULLTEXT KEY `titel_beschreibung` (`titel`,`beschreibung`);

--
-- Indizes fuer die Tabelle `kurse_durchfuehrung`
--
ALTER TABLE `kurse_durchfuehrung`
  ADD KEY `kurse_durchfuehrung_i0` (`primary_id`),
  ADD KEY `kurse_durchfuehrung_i1` (`secondary_id`);

--
-- Indizes fuer die Tabelle `kurse_stichwort`
--
ALTER TABLE `kurse_stichwort`
  ADD KEY `kurse_stichwort_i0` (`primary_id`),
  ADD KEY `kurse_stichwort_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `kurse_verweis`
--
ALTER TABLE `kurse_verweis`
  ADD KEY `kurse_verweis_i0` (`primary_id`),
  ADD KEY `kurse_verweis_i1` (`attr_id`);
-- Ende: openthesaurus --
-- Nachfolgende Tabellen werden nur verwendet, wenn man
-- den OpenThesaurus von openthesaurus.de importiert.
-- Wenn diese Tabellen nicht existieren, wird das entsprechende
-- Feature in der Suche einfach uebersprungen.

--
-- Indizes fuer die Tabelle `openth_category`
--
-- ALTER TABLE `openth_category`
--  ADD PRIMARY KEY (`id`),
--  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indizes fuer die Tabelle `openth_category_link`
--
-- ALTER TABLE `openth_category_link`
--  ADD PRIMARY KEY (`id`),
--  ADD KEY `FK142F1A9BA3843755` (`synset_id`),
--  ADD KEY `FK142F1A9B6197A215` (`category_id`);

--
-- Indizes fuer die Tabelle `openth_synset`
--
-- ALTER TABLE `openth_synset`
--  ADD PRIMARY KEY (`id`),
--  ADD KEY `FKCB1A289A4A27BB5` (`source_id`),
--  ADD KEY `FKCB1A289AED375357` (`preferred_category_id`),
--  ADD KEY `FKCB1A289AD9BB831F` (`section_id`),
--  ADD KEY `is_visible` (`is_visible`),
--  ADD KEY `original_id` (`original_id`);

--
-- Indizes fuer die Tabelle `openth_synset_link`
--
-- ALTER TABLE `openth_synset_link`
--  ADD PRIMARY KEY (`id`),
--  ADD KEY `FK6336907FA3843755` (`synset_id`),
--  ADD KEY `FK6336907FE4B8FC6A` (`link_type_id`),
--  ADD KEY `FK6336907F9933A227` (`target_synset_id`);

--
-- Indizes fuer die Tabelle `openth_tag`
--
-- ALTER TABLE `openth_tag`
--  ADD PRIMARY KEY (`id`),
--  ADD UNIQUE KEY `name` (`name`),
--  ADD UNIQUE KEY `short_name` (`short_name`);

--
-- Indizes fuer die Tabelle `openth_term`
--
-- ALTER TABLE `openth_term`
--  ADD PRIMARY KEY (`id`),
--  ADD KEY `FK36446CA3843755` (`synset_id`),
--  ADD KEY `FK36446C534B2C73` (`level_id`),
--  ADD KEY `FK36446C5CA8CBD5` (`language_id`),
--  ADD KEY `word` (`word`),
--  ADD KEY `normalized_word` (`normalized_word`),
--  ADD KEY `normalized_word2` (`normalized_word2`);

--
-- Indizes fuer die Tabelle `openth_term_level`
--
-- ALTER TABLE `openth_term_level`
--  ADD PRIMARY KEY (`id`),
--  ADD UNIQUE KEY `level_name` (`level_name`),
--  ADD UNIQUE KEY `short_level_name` (`short_level_name`);

--
-- Indizes fuer die Tabelle `openth_term_link`
--
-- ALTER TABLE `openth_term_link`
--  ADD PRIMARY KEY (`id`),
--  ADD KEY `FK78CD07EDDBDC7376` (`link_type_id`),
--  ADD KEY `FK78CD07ED36F08D5` (`term_id`),
--  ADD KEY `FK78CD07ED2D3F0027` (`target_term_id`);

--
-- Indizes fuer die Tabelle `openth_term_link_type`
--
-- ALTER TABLE `openth_term_link_type`
--  ADD PRIMARY KEY (`id`),
--  ADD UNIQUE KEY `link_name` (`link_name`),
--  ADD UNIQUE KEY `verb_name` (`verb_name`);

--
-- Indizes fuer die Tabelle `openth_term_tag`
--
-- ALTER TABLE `openth_term_tag`
-- ADD KEY `FKB9931D47C610557F` (`tag_id`),
-- ADD KEY `FKB9931D47AD241D35` (`term_tags_id`);

--
-- Indizes fuer die Tabelle `openth_word_mapping`
--
-- ALTER TABLE `openth_word_mapping`
--  ADD KEY `idx` (`fullform`);

-- Ende: openthesaurus --

--
-- Indizes fuer die Tabelle `plztool`
--
ALTER TABLE `plztool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `strasse_norm` (`strasse_norm`);

--
-- Indizes fuer die Tabelle `plztool2`
--
ALTER TABLE `plztool2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plz` (`plz`),
  ADD KEY `ort` (`ort`);

--
-- Indizes fuer die Tabelle `plz_ortscron`
--
ALTER TABLE `plz_ortscron`
  ADD PRIMARY KEY (`id`);

--
-- Indizes fuer die Tabelle `portale`
--
ALTER TABLE `portale`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `begriff` (`name`),
  ADD KEY `begriff_sorted` (`domains`),
  ADD KEY `date_modified` (`date_modified`);
ALTER TABLE `portale` ADD FULLTEXT KEY `begriff_erklaerung` (`name`,`filter`);

--
-- Indizes fuer die Tabelle `statistik`
--
ALTER TABLE `statistik`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `search_hash` (`search_hash`),
  ADD UNIQUE KEY `search_hash_2` (`search_hash`);

--
-- Indizes fuer die Tabelle `stichwoerter`
--
ALTER TABLE `stichwoerter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `stichwort` (`stichwort`),
  ADD KEY `stichwort_sorted` (`stichwort_sorted`),
  ADD KEY `eigenschaften` (`eigenschaften`);

--
-- Indizes fuer die Tabelle `stichwoerter_verweis`
--
ALTER TABLE `stichwoerter_verweis`
  ADD KEY `stichwoerter_verweis_i0` (`primary_id`),
  ADD KEY `stichwoerter_verweis_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `stichwoerter_verweis2`
--
ALTER TABLE `stichwoerter_verweis2`
  ADD KEY `stichwoerter_verweis_i0` (`primary_id`),
  ADD KEY `stichwoerter_verweis_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `themen`
--
ALTER TABLE `themen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`),
  ADD KEY `kuerzel` (`kuerzel`),
  ADD KEY `kuerzel_sorted` (`kuerzel_sorted`),
  ADD KEY `thema` (`thema`),
  ADD KEY `thema_sorted` (`thema_sorted`);

--
-- Indizes fuer die Tabelle `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `msgid` (`msgid`),
  ADD KEY `user_modified` (`user_modified`),
  ADD KEY `date_modified` (`date_modified`);

--
-- Indizes fuer die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loginname` (`loginname`);

--
-- Indizes fuer die Tabelle `user_attr_grp`
--
ALTER TABLE `user_attr_grp`
  ADD KEY `user_attr_grp_i0` (`primary_id`),
  ADD KEY `user_attr_grp_i1` (`attr_id`);

--
-- Indizes fuer die Tabelle `user_fuzzy`
--
ALTER TABLE `user_fuzzy`
  ADD KEY `word` (`word`),
  ADD KEY `metaphone` (`metaphone`),
  ADD KEY `soundex` (`soundex`);

--
-- Indizes fuer die Tabelle `user_grp`
--
ALTER TABLE `user_grp`
  ADD PRIMARY KEY (`id`);

--
-- Indizes fuer die Tabelle `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indizes fuer die Tabelle `x_cache_confirm`
--
ALTER TABLE `x_cache_confirm`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_latlng_perm`
--
ALTER TABLE `x_cache_latlng_perm`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_latlng_search`
--
ALTER TABLE `x_cache_latlng_search`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_promoteips`
--
ALTER TABLE `x_cache_promoteips`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_rss`
--
ALTER TABLE `x_cache_rss`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_search`
--
ALTER TABLE `x_cache_search`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_cache_sitemap`
--
ALTER TABLE `x_cache_sitemap`
  ADD PRIMARY KEY (`ckey`),
  ADD KEY `cdateinserted` (`cdateinserted`);

--
-- Indizes fuer die Tabelle `x_kurse`
--
ALTER TABLE `x_kurse`
  ADD KEY `kurs_id` (`kurs_id`),
  ADD KEY `preis` (`preis`),
  ADD KEY `dauer` (`dauer`),
  ADD KEY `beginn` (`beginn`);

--
-- Indizes fuer die Tabelle `x_kurse_latlng`
--
ALTER TABLE `x_kurse_latlng`
  ADD KEY `lat` (`lat`),
  ADD KEY `lng` (`lng`),
  ADD KEY `kurs_id` (`kurs_id`);

--
-- Indizes fuer die Tabelle `x_kurse_plz`
--
ALTER TABLE `x_kurse_plz`
  ADD KEY `kurs_id` (`kurs_id`),
  ADD KEY `plz` (`plz`);

--
-- Indizes fuer die Tabelle `x_kurse_tags`
--
ALTER TABLE `x_kurse_tags`
  ADD KEY `kurs_id` (`kurs_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indizes fuer die Tabelle `x_querystats`
--
ALTER TABLE `x_querystats`
  ADD PRIMARY KEY (`id`);

--
-- Indizes fuer die Tabelle `x_state`
--
ALTER TABLE `x_state`
  ADD PRIMARY KEY (`skey`);

--
-- Indizes fuer die Tabelle `x_tags`
--
ALTER TABLE `x_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD KEY `tag_name` (`tag_name`),
  ADD KEY `tag_soundex` (`tag_soundex`),
  ADD KEY `tag_metaphone` (`tag_metaphone`);

--
-- Indizes fuer die Tabelle `x_tags_freq`
--
ALTER TABLE `x_tags_freq`
  ADD KEY `tag_id` (`tag_id`);

--
-- Indizes fuer die Tabelle `x_tags_syn`
--
ALTER TABLE `x_tags_syn`
  ADD KEY `lemma_id` (`lemma_id`);

--
-- AUTO_INCREMENT fuer exportierte Tabellen
--

--
-- AUTO_INCREMENT fuer Tabelle `anbieter`
--
ALTER TABLE `anbieter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `anbieter_billing`
--
ALTER TABLE `anbieter_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `anbieter_promote`
--
ALTER TABLE `anbieter_promote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `anbieter_promote_log`
--
ALTER TABLE `anbieter_promote_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `apikeys`
--
ALTER TABLE `apikeys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `durchfuehrung`
--
ALTER TABLE `durchfuehrung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `geowisii`
--
ALTER TABLE `geowisii`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `glossar`
--
ALTER TABLE `glossar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `kurse`
--
ALTER TABLE `kurse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `plztool`
--
ALTER TABLE `plztool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `plztool2`
--
ALTER TABLE `plztool2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `plz_ortscron`
--
ALTER TABLE `plz_ortscron`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `portale`
--
ALTER TABLE `portale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `statistik`
--
ALTER TABLE `statistik`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `stichwoerter`
--
ALTER TABLE `stichwoerter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `themen`
--
ALTER TABLE `themen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `user_grp`
--
ALTER TABLE `user_grp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `x_querystats`
--
ALTER TABLE `x_querystats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT fuer Tabelle `x_tags`
--
ALTER TABLE `x_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
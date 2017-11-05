-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 28. Nov 2014 um 13:32
-- Server Version: 5.5.40-0ubuntu0.14.04.1
-- PHP-Version: 5.5.9-1ubuntu4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `db314961`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter`
-- Die Anbieterfelder "logo_name", "logo_mime", "logo_bytes", "logo_w", "logo_h", "logo_ext" und "logo_blob" sollen nicht mehr verwendet werden, 
-- alle Daten befinden sich nun in Base64-Kodiert im TEXT-Feld "logo", was den Im-/Export wesentlich erleichtert.  
-- Anpassungen sind nur in Modulen notwendig, wenn die Blobs nicht über die Datei media.php gelesen werden.
-- Feld Hinzufügen mit: ALTER TABLE anbieter ADD logo LONGTEXT NOT NULL AFTER firmenportraet;
--

CREATE TABLE IF NOT EXISTS `anbieter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vollstaendigkeit` int(11) NOT NULL DEFAULT '0',
  `suchname` varchar(200) NOT NULL DEFAULT '',
  `suchname_sorted` varchar(200) NOT NULL DEFAULT '',
  `freigeschaltet` int(11) NOT NULL DEFAULT '1',
  `typ` int(11) NOT NULL DEFAULT '0',
  `postname` varchar(200) NOT NULL DEFAULT '',
  `postname_sorted` varchar(200) NOT NULL DEFAULT '',
  `strasse` varchar(200) NOT NULL DEFAULT '',
  `plz` varchar(200) NOT NULL DEFAULT '',
  `ort` varchar(200) NOT NULL DEFAULT '',
  `stadtteil` varchar(200) NOT NULL DEFAULT '',
  `land` varchar(200) NOT NULL DEFAULT '',
  `rollstuhlgerecht` int(11) NOT NULL DEFAULT '0',
  `leitung_name` varchar(200) NOT NULL DEFAULT '',
  `leitung_tel` varchar(200) NOT NULL DEFAULT '',
  `thema` int(11) NOT NULL DEFAULT '0',
  `din_nr` varchar(200) NOT NULL DEFAULT '',
  `din_nr_sorted` varchar(200) NOT NULL DEFAULT '',
  `gruendungsjahr` int(11) NOT NULL DEFAULT '0',
  `rechtsform` int(11) NOT NULL DEFAULT '0',
  `firmenportraet` longtext NOT NULL,
  `logo` longtext NOT NULL,
  `logo_blob` longblob NOT NULL,
  `logo_name` varchar(200) NOT NULL DEFAULT '',
  `logo_mime` varchar(200) NOT NULL DEFAULT '',
  `logo_bytes` int(11) NOT NULL DEFAULT '0',
  `logo_w` int(11) NOT NULL DEFAULT '0',
  `logo_h` int(11) NOT NULL DEFAULT '0',
  `logo_ext` int(11) NOT NULL DEFAULT '0',
  `homepage` varchar(200) NOT NULL DEFAULT '',
  `pruefsiegel_seit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anspr_name` varchar(200) NOT NULL DEFAULT '',
  `anspr_zeit` longtext NOT NULL,
  `anspr_tel` varchar(200) NOT NULL DEFAULT '',
  `anspr_fax` varchar(200) NOT NULL DEFAULT '',
  `anspr_email` varchar(200) NOT NULL DEFAULT '',
  `kursplatzanfrage` int(11) NOT NULL DEFAULT '0',
  `partnernetz` int(11) NOT NULL DEFAULT '0',
  `pflege_name` varchar(200) NOT NULL DEFAULT '',
  `pflege_tel` varchar(200) NOT NULL DEFAULT '',
  `pflege_fax` varchar(200) NOT NULL DEFAULT '',
  `pflege_email` varchar(200) NOT NULL DEFAULT '',
  `pflege_weg` int(11) NOT NULL DEFAULT '1',
  `pflege_prot` int(11) NOT NULL DEFAULT '1',
  `pflege_akt` int(11) NOT NULL DEFAULT '0',
  `pflege_passwort` varchar(200) NOT NULL DEFAULT '',
  `pflege_pweinst` int(11) NOT NULL DEFAULT '0',
  `pflege_msg` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `in_wisy_seit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aufnahme_durch` int(11) NOT NULL DEFAULT '0',
  `herkunft` int(11) NOT NULL DEFAULT '0',
  `herkunftsID` varchar(200) NOT NULL,
  `wisy_annr` varchar(200) DEFAULT NULL,
  `bu_annr` varchar(200) DEFAULT NULL,
  `foerder_annr` varchar(200) DEFAULT NULL,
  `fu_annr` varchar(200) DEFAULT NULL,
  `azwv_annr` varchar(200) DEFAULT NULL,
  `settings` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `suchname` (`suchname`),
  KEY `suchname_sorted` (`suchname_sorted`),
  KEY `postname` (`postname`),
  KEY `postname_sorted` (`postname_sorted`),
  KEY `date_modified` (`date_modified`),
  KEY `plz` (`plz`),
  KEY `din_nr` (`din_nr`),
  KEY `din_nr_sorted` (`din_nr_sorted`),
  KEY `freigeschaltet` (`freigeschaltet`),
  KEY `strasse` (`strasse`),
  FULLTEXT KEY `textfields` (`din_nr`,`suchname`,`postname`,`strasse`,`ort`,`stadtteil`,`anspr_name`,`notizen`,`leitung_name`,`homepage`,`firmenportraet`,`pflege_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=855462 ;

--
-- Trigger `anbieter`
--
DROP TRIGGER IF EXISTS `anbieter_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `anbieter_bi_v9_10_1` BEFORE INSERT ON `anbieter`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter_billing`
--

CREATE TABLE IF NOT EXISTS `anbieter_billing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anbieter_id` int(11) NOT NULL DEFAULT '0',
  `portal_id` int(11) NOT NULL DEFAULT '0',
  `bill_type` int(11) NOT NULL DEFAULT '0',
  `credits` int(11) NOT NULL DEFAULT '0',
  `eur` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '0.00',
  `raw_data` text COLLATE latin1_general_ci NOT NULL,
  `notizen` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_created` (`date_created`),
  KEY `anbieter_id` (`anbieter_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=362 ;

--
-- Trigger `anbieter_billing`
--
DROP TRIGGER IF EXISTS `anbieter_billing_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `anbieter_billing_bi_v9_10_1` BEFORE INSERT ON `anbieter_billing`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter_promote`
--

CREATE TABLE IF NOT EXISTS `anbieter_promote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `kurs_id` int(11) NOT NULL DEFAULT '0',
  `anbieter_id` int(11) NOT NULL DEFAULT '0',
  `portal_id` int(11) NOT NULL DEFAULT '0',
  `promote_active` int(11) NOT NULL DEFAULT '0',
  `promote_mode` char(16) COLLATE latin1_general_ci NOT NULL,
  `promote_param` char(16) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kurs_id` (`kurs_id`),
  KEY `anbieter_id` (`anbieter_id`),
  KEY `portal_id` (`portal_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=302 ;

--
-- Trigger `anbieter_promote`
--
DROP TRIGGER IF EXISTS `anbieter_promote_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `anbieter_promote_bi_v9_10_1` BEFORE INSERT ON `anbieter_promote`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter_promote_log`
--

CREATE TABLE IF NOT EXISTS `anbieter_promote_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `anbieter_id` int(11) NOT NULL DEFAULT '0',
  `portal_id` int(11) NOT NULL DEFAULT '0',
  `kurs_id` int(11) NOT NULL DEFAULT '0',
  `event_type` int(11) NOT NULL DEFAULT '0',
  `lparam` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `kurs_id` (`kurs_id`),
  KEY `date_created` (`date_created`),
  KEY `anbieter_id` (`anbieter_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=26122 ;

--
-- Trigger `anbieter_promote_log`
--
DROP TRIGGER IF EXISTS `anbieter_promote_log_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `anbieter_promote_log_bi_v9_10_1` BEFORE INSERT ON `anbieter_promote_log`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter_stichwort`
--

CREATE TABLE IF NOT EXISTS `anbieter_stichwort` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `anbieter_stichwort_i0` (`primary_id`),
  KEY `anbieter_stichwort_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `anbieter_verweis`
--

CREATE TABLE IF NOT EXISTS `anbieter_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `anbieter_verweis_i0` (`primary_id`),
  KEY `anbieter_verweis_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `apikeys`
--

CREATE TABLE IF NOT EXISTS `apikeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `apikey` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `flags` int(11) NOT NULL,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `apikey` (`apikey`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=72 ;

--
-- Trigger `apikeys`
--
DROP TRIGGER IF EXISTS `apikeys_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `apikeys_bi_v9_10_1` BEFORE INSERT ON `apikeys`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

--
-- Tabellenstruktur für Tabelle `apikeys_usergrp`
--

CREATE TABLE `apikeys_usergrp` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `apikeys_usergrp`
  ADD KEY `anbieter_stichwort_i0` (`primary_id`),
  ADD KEY `anbieter_stichwort_i1` (`attr_id`);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `durchfuehrung`
--

CREATE TABLE IF NOT EXISTS `durchfuehrung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `nr` varchar(200) NOT NULL DEFAULT '',
  `nr_sorted` varchar(200) NOT NULL DEFAULT '',
  `stunden` int(11) NOT NULL DEFAULT '0',
  `teilnehmer` int(11) NOT NULL DEFAULT '0',
  `preis` int(11) NOT NULL DEFAULT '-1',
  `preishinweise` varchar(200) NOT NULL DEFAULT '',
  `sonderpreis` int(11) NOT NULL DEFAULT '-1',
  `sonderpreistage` int(11) NOT NULL DEFAULT '0',
  `beginn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ende` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `beginnoptionen` int(11) NOT NULL DEFAULT '0',
  `dauer` int(11) NOT NULL DEFAULT '0',
  `zeit_von` varchar(200) NOT NULL DEFAULT '',
  `zeit_bis` varchar(200) NOT NULL DEFAULT '',
  `kurstage` int(11) NOT NULL DEFAULT '0',
  `tagescode` int(11) NOT NULL DEFAULT '1',
  `strasse` varchar(200) NOT NULL DEFAULT '',
  `plz` varchar(200) NOT NULL DEFAULT '',
  `ort` varchar(200) NOT NULL DEFAULT '',
  `stadtteil` varchar(200) NOT NULL DEFAULT '',
  `land` varchar(200) NOT NULL DEFAULT '',
  `rollstuhlgerecht` int(11) NOT NULL DEFAULT '0',
  `bemerkungen` longtext NOT NULL,
  `bg_nummer` varchar(100) DEFAULT NULL,
  `bu_dnummer` varchar(100) DEFAULT NULL,
  `bg_nummer_count` smallint(6) DEFAULT NULL,
  `herkunft` int(11) NOT NULL DEFAULT '0',
  `herkunftsID` varchar(200) NOT NULL,
  `wisy_dnr` varchar(200) DEFAULT NULL,
  `fu_dnr` varchar(200) DEFAULT NULL,
  `foerder_dnr` varchar(200) DEFAULT NULL,
  `azwv_dnr` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `date_modified` (`date_modified`),
  KEY `nr` (`nr`),
  KEY `nr_sorted` (`nr_sorted`),
  KEY `beginn` (`beginn`),
  KEY `preis` (`preis`),
  KEY `tagescode` (`tagescode`),
  KEY `sonderpreistage` (`sonderpreistage`),
  KEY `strasse` (`strasse`),
  FULLTEXT KEY `textfields` (`nr`,`strasse`,`plz`,`ort`,`bemerkungen`,`stadtteil`,`preishinweise`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9303642 ;

--
-- Trigger `durchfuehrung`
--
DROP TRIGGER IF EXISTS `durchfuehrung_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `durchfuehrung_bi_v9_10_1` BEFORE INSERT ON `durchfuehrung`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `url` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `ip` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `rating` int(11) NOT NULL,
  `descr` text COLLATE latin1_general_ci NOT NULL,
  `notizen` longtext COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `date_modified` (`date_modified`),
  KEY `url` (`url`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=254542 ;

--
-- Trigger `feedback`
--
DROP TRIGGER IF EXISTS `feedback_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `feedback_bi_v9_10_1` BEFORE INSERT ON `feedback`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geodb_locations`
--

CREATE TABLE IF NOT EXISTS `geodb_locations` (
  `id` int(11) NOT NULL DEFAULT '0',
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
  `plz` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `id_2` (`id`),
  KEY `ort` (`ort`),
  KEY `staat` (`adm0`),
  KEY `land` (`adm1`),
  KEY `regbez` (`adm2`),
  KEY `landkreis` (`adm3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `geowisii`
--

CREATE TABLE IF NOT EXISTS `geowisii` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ort_hash` varchar(200) NOT NULL DEFAULT '',
  `lat` int(11) NOT NULL DEFAULT '0',
  `lng` int(11) NOT NULL DEFAULT '0',
  `durchf_ids` longtext NOT NULL,
  `accuracy` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ort_hash` (`ort_hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4047 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `glossar`
--

CREATE TABLE IF NOT EXISTS `glossar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `begriff` varchar(200) NOT NULL DEFAULT '',
  `begriff_sorted` varchar(200) NOT NULL DEFAULT '',
  `erklaerung` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `wikipedia` varchar(200) NOT NULL DEFAULT '',
  `freigeschaltet` int(11) NOT NULL DEFAULT '2',
  `rssfeed` varchar(200) DEFAULT NULL,
  `von` date NOT NULL DEFAULT '1900-01-01',
  `bis` date NOT NULL DEFAULT '2100-12-31',
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `begriff` (`begriff`),
  KEY `begriff_sorted` (`begriff_sorted`),
  KEY `date_modified` (`date_modified`),
  FULLTEXT KEY `begriff_erklaerung` (`begriff`,`erklaerung`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7882 ;

--
-- Trigger `glossar`
--
DROP TRIGGER IF EXISTS `glossar_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `glossar_bi_v9_10_1` BEFORE INSERT ON `glossar`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `itb_map_regions_rlp`
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
-- Tabellenstruktur für Tabelle `kurse`
--

CREATE TABLE IF NOT EXISTS `kurse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `titel` varchar(500) NOT NULL DEFAULT '',
  `titel_sorted` varchar(500) NOT NULL DEFAULT '',
  `freigeschaltet` int(11) NOT NULL DEFAULT '1',
  `anbieter` int(11) NOT NULL DEFAULT '0',
  `beschreibung` longtext NOT NULL,
  `thema` int(11) NOT NULL DEFAULT '0',
  `notizen` longtext NOT NULL,
  `bu_nummer` varchar(100) DEFAULT NULL,
  `res_nummer` varchar(100) DEFAULT NULL,
  `vollstaendigkeit` int(11) NOT NULL DEFAULT '0',
  `org_titel` varchar(500) DEFAULT NULL,
  `fu_knr` varchar(200) DEFAULT NULL,
  `foerder_knr` varchar(200) DEFAULT NULL,
  `azwv_knr` varchar(200) DEFAULT NULL,
  `msgtooperator` varchar(200) NOT NULL,
  `wisy_knr` varchar(200) NOT NULL COMMENT 'deprecated',
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `date_modified` (`date_modified`),
  KEY `titel` (`titel`),
  KEY `titel_sorted` (`titel_sorted`),
  KEY `thema` (`thema`),
  KEY `anbieter` (`anbieter`),
  KEY `freigeschaltet` (`freigeschaltet`),
  FULLTEXT KEY `titel_beschreibung` (`titel`,`beschreibung`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3225682 ;

--
-- Trigger `kurse`
--
DROP TRIGGER IF EXISTS `kurse_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `kurse_bi_v9_10_1` BEFORE INSERT ON `kurse`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurse_durchfuehrung`
--

CREATE TABLE IF NOT EXISTS `kurse_durchfuehrung` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `secondary_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `kurse_durchfuehrung_i0` (`primary_id`),
  KEY `kurse_durchfuehrung_i1` (`secondary_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurse_stichwort`
--

CREATE TABLE IF NOT EXISTS `kurse_stichwort` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `kurse_stichwort_i0` (`primary_id`),
  KEY `kurse_stichwort_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kurse_verweis`
--

CREATE TABLE IF NOT EXISTS `kurse_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `kurse_verweis_i0` (`primary_id`),
  KEY `kurse_verweis_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `plztool`
--

CREATE TABLE IF NOT EXISTS `plztool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `strasse_norm` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort_norm` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `plz` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `stadtteil` varchar(255) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `strasse_norm` (`strasse_norm`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=8549 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `plztool2`
--

CREATE TABLE IF NOT EXISTS `plztool2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plz` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort` varchar(255) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `plz` (`plz`),
  KEY `ort` (`ort`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=8290 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `portale`
--

CREATE TABLE IF NOT EXISTS `portale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(200) NOT NULL DEFAULT '',
  `kurzname` varchar(200) NOT NULL DEFAULT '',
  `domains` varchar(200) NOT NULL DEFAULT '',
  `filter` longtext NOT NULL,
  `skindir` varchar(200) NOT NULL DEFAULT '',
  `css` longtext NOT NULL,
  `bodystart` longtext NOT NULL,
  `einstellungen` longtext NOT NULL,
  `notizen` longtext NOT NULL,
  `spalten` int(11) NOT NULL DEFAULT '65535',
  `horizont` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `horizontende` tinyint(3) unsigned NOT NULL DEFAULT '2',
  `themen_erlauben` varchar(200) NOT NULL DEFAULT '*',
  `themen_verbieten` varchar(200) NOT NULL DEFAULT '',
  `betreiberID` int(11) NOT NULL DEFAULT '660',
  `print_img` varchar(255) DEFAULT NULL,
  `menuswitch` int(11) unsigned NOT NULL DEFAULT '63',
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
  `iwwb_filter` longtext,
  `einstcache` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `begriff` (`name`),
  KEY `begriff_sorted` (`domains`),
  KEY `date_modified` (`date_modified`),
  FULLTEXT KEY `begriff_erklaerung` (`name`,`filter`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1422 ;

--
-- Trigger `portale`
--
DROP TRIGGER IF EXISTS `portale_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `portale_bi_v9_10_1` BEFORE INSERT ON `portale`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `stichwoerter`
--

CREATE TABLE IF NOT EXISTS `stichwoerter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `stichwort` varchar(200) NOT NULL DEFAULT '',
  `stichwort_sorted` varchar(200) NOT NULL DEFAULT '',
  `zusatzinfo` varchar(200) NOT NULL,
  `eigenschaften` int(11) NOT NULL DEFAULT '0',
  `thema` int(11) NOT NULL DEFAULT '0',
  `glossar` int(11) NOT NULL DEFAULT '0',
  `notizen` longtext NOT NULL,
  `scope_note` longtext,
  `algorithmus` longtext,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `date_modified` (`date_modified`),
  KEY `stichwort` (`stichwort`),
  KEY `stichwort_sorted` (`stichwort_sorted`),
  KEY `eigenschaften` (`eigenschaften`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=806472 ;

--
-- Trigger `stichwoerter`
--
DROP TRIGGER IF EXISTS `stichwoerter_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `stichwoerter_bi_v9_10_1` BEFORE INSERT ON `stichwoerter`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `stichwoerter_verweis`
--

CREATE TABLE IF NOT EXISTS `stichwoerter_verweis` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `stichwoerter_verweis_i0` (`primary_id`),
  KEY `stichwoerter_verweis_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `stichwoerter_verweis2`
--

CREATE TABLE IF NOT EXISTS `stichwoerter_verweis2` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `stichwoerter_verweis_i0` (`primary_id`),
  KEY `stichwoerter_verweis_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `themen`
--

CREATE TABLE IF NOT EXISTS `themen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `thema` varchar(200) NOT NULL DEFAULT '',
  `thema_sorted` varchar(200) NOT NULL DEFAULT '',
  `kuerzel` varchar(200) NOT NULL DEFAULT '',
  `kuerzel_sorted` varchar(200) NOT NULL DEFAULT '',
  `glossar` int(11) NOT NULL DEFAULT '0',
  `notizen` longtext NOT NULL,
  `scope_note` longtext,
  `algorithmus` longtext,
  PRIMARY KEY (`id`),
  KEY `user_modified` (`user_modified`),
  KEY `date_modified` (`date_modified`),
  KEY `kuerzel` (`kuerzel`),
  KEY `kuerzel_sorted` (`kuerzel_sorted`),
  KEY `thema` (`thema`),
  KEY `thema_sorted` (`thema_sorted`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=292 ;

--
-- Trigger `themen`
--
DROP TRIGGER IF EXISTS `themen_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `themen_bi_v9_10_1` BEFORE INSERT ON `themen`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `loginname` varchar(200) NOT NULL DEFAULT '',
  `password` varchar(200) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `phone` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL DEFAULT '',
  `attr_role` int(11) NOT NULL DEFAULT '0',
  `msg_to_user` longtext NOT NULL,
  `access` longtext NOT NULL,
  `settings` longtext NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login_error` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `num_login_errors` int(11) NOT NULL DEFAULT '0',
  `remembered` longtext NOT NULL,
  `notizen` longtext,
  PRIMARY KEY (`id`),
  KEY `loginname` (`loginname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=592 ;

--
-- Trigger `user`
--
DROP TRIGGER IF EXISTS `user_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `user_bi_v9_10_1` BEFORE INSERT ON `user`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

INSERT INTO user (loginname, access) VALUES('root', '*.*:rwnd;');
-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_attr_grp`
--

CREATE TABLE IF NOT EXISTS `user_attr_grp` (
  `primary_id` int(11) NOT NULL DEFAULT '0',
  `attr_id` int(11) NOT NULL DEFAULT '0',
  `structure_pos` int(11) NOT NULL DEFAULT '0',
  KEY `user_attr_grp_i0` (`primary_id`),
  KEY `user_attr_grp_i1` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_fuzzy`
--

CREATE TABLE IF NOT EXISTS `user_fuzzy` (
  `word` varchar(128) NOT NULL DEFAULT '',
  `soundex` varchar(128) NOT NULL DEFAULT '',
  `metaphone` varchar(128) NOT NULL DEFAULT '',
  KEY `word` (`word`),
  KEY `metaphone` (`metaphone`),
  KEY `soundex` (`soundex`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_grp`
--

CREATE TABLE IF NOT EXISTS `user_grp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shortname` varchar(200) NOT NULL DEFAULT '',
  `password` varchar(200) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `notizen` longtext,
  `settings` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=172 ;

--
-- Trigger `user_grp`
--
DROP TRIGGER IF EXISTS `user_grp_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `user_grp_bi_v9_10_1` BEFORE INSERT ON `user_grp`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_roles`
--

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_src` int(11) NOT NULL DEFAULT '1',
  `user_created` int(11) NOT NULL DEFAULT '0',
  `user_modified` int(11) NOT NULL DEFAULT '0',
  `user_grp` int(11) NOT NULL DEFAULT '0',
  `user_access` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(200) NOT NULL DEFAULT '',
  `text_to_confirm` longtext NOT NULL,
  `email_notify` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Trigger `user_roles`
--
DROP TRIGGER IF EXISTS `user_roles_bi_v9_10_1`;
DELIMITER //
CREATE TRIGGER `user_roles_bi_v9_10_1` BEFORE INSERT ON `user_roles`
 FOR EACH ROW BEGIN
									SET auto_increment_increment = 10;
									SET auto_increment_offset = 1;
								  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_confirm`
--

CREATE TABLE IF NOT EXISTS `x_cache_confirm` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_latlng_perm`
--

CREATE TABLE IF NOT EXISTS `x_cache_latlng_perm` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_latlng_search`
--

CREATE TABLE IF NOT EXISTS `x_cache_latlng_search` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_promoteips`
--

CREATE TABLE IF NOT EXISTS `x_cache_promoteips` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_rss`
--

CREATE TABLE IF NOT EXISTS `x_cache_rss` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_search`
--

CREATE TABLE IF NOT EXISTS `x_cache_search` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longtext COLLATE latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_sitemap`
--

CREATE TABLE IF NOT EXISTS `x_cache_sitemap` (
  `ckey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `cvalue` longblob NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY (`ckey`),
  KEY `cdateinserted` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse`
--

CREATE TABLE IF NOT EXISTS `x_kurse` (
  `kurs_id` int(11) NOT NULL,
  `beginn` date NOT NULL DEFAULT '0000-00-00',
  `dauer` int(11) NOT NULL DEFAULT '0',
  `preis` int(11) NOT NULL DEFAULT '-1',
  `anbieter_sortonly` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `ort_sortonly` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `begmod_hash` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `begmod_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `kurs_id` (`kurs_id`),
  KEY `preis` (`preis`),
  KEY `dauer` (`dauer`),
  KEY `beginn` (`beginn`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse_latlng`
--

CREATE TABLE IF NOT EXISTS `x_kurse_latlng` (
  `kurs_id` int(11) NOT NULL,
  `lat` int(11) NOT NULL,
  `lng` int(11) NOT NULL,
  KEY `lat` (`lat`),
  KEY `lng` (`lng`),
  KEY `kurs_id` (`kurs_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse_plz`
--

CREATE TABLE IF NOT EXISTS `x_kurse_plz` (
  `kurs_id` int(11) NOT NULL,
  `plz` char(8) COLLATE latin1_general_ci NOT NULL,
  KEY `kurs_id` (`kurs_id`),
  KEY `plz` (`plz`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse_tags`
--

CREATE TABLE IF NOT EXISTS `x_kurse_tags` (
  `kurs_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  KEY `kurs_id` (`kurs_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_state`
--

CREATE TABLE IF NOT EXISTS `x_state` (
  `skey` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `svalue` longtext COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_tags`
--

CREATE TABLE IF NOT EXISTS `x_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_descr` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_type` int(11) NOT NULL DEFAULT '0',
  `tag_help` int(11) NOT NULL,
  `tag_soundex` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `tag_metaphone` varchar(255) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `tag_name` (`tag_name`),
  KEY `tag_soundex` (`tag_soundex`),
  KEY `tag_metaphone` (`tag_metaphone`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=4711887 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_tags_freq`
--

CREATE TABLE IF NOT EXISTS `x_tags_freq` (
  `tag_id` int(11) NOT NULL,
  `portal_id` int(11) NOT NULL,
  `tag_freq` int(11) NOT NULL,
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_tags_syn`
--

CREATE TABLE IF NOT EXISTS `x_tags_syn` (
  `tag_id` int(11) NOT NULL,
  `lemma_id` int(11) NOT NULL,
  KEY `lemma_id` (`lemma_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

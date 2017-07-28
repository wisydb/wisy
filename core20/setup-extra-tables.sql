-- phpMyAdmin SQL Dump
-- version 2.11.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 18. September 2009 um 10:26
-- Server Version: 5.0.45
-- PHP-Version: 5.2.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `webwisy`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_cache_search`
--

CREATE TABLE `x_cache_search` (
  `ckey` varchar(255) collate latin1_general_ci NOT NULL,
  `cvalue` longtext collate latin1_general_ci NOT NULL,
  `cdateinserted` datetime NOT NULL,
  PRIMARY KEY  (`ckey`),
  KEY `cexpires` (`cdateinserted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse`
--

CREATE TABLE `x_kurse` (
  `kurs_id` int(11) NOT NULL,
  `beginn` date NOT NULL default '0000-00-00',
  `beginn_last` date NOT NULL default '0000-00-00',
  `dauer` int(11) NOT NULL default '0',
  `preis` int(11) NOT NULL default '-1',
  `anbieter_sortonly` varchar(255) collate latin1_general_ci NOT NULL,
  `ort_sortonly` varchar(255) collate latin1_general_ci NOT NULL,
  KEY `kurs_id` (`kurs_id`),
  KEY `beginn` (`beginn`),
  KEY `preis` (`preis`),
  KEY `dauer` (`dauer`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_kurse_tags`
--

CREATE TABLE `x_kurse_tags` (
  `kurs_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  KEY `kurs_id` (`kurs_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_state`
--

CREATE TABLE `x_state` (
  `skey` varchar(255) collate latin1_general_ci NOT NULL,
  `svalue` longtext collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`skey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_tags`
--

CREATE TABLE `x_tags` (
  `tag_id` int(11) NOT NULL auto_increment,
  `tag_name` varchar(255) collate latin1_general_ci NOT NULL,
  `tag_type` int(11) NOT NULL default '0',
  `tag_soundex` varchar(255) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`tag_id`),
  KEY `tag_name` (`tag_name`),
  KEY `tag_soundex` (`tag_soundex`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=10351 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `x_tags_syn`
--

CREATE TABLE `x_tags_syn` (
  `tag_id` int(11) NOT NULL,
  `lemma_id` int(11) NOT NULL,
  KEY `lemma_id` (`lemma_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

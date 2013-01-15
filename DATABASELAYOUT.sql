-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 30. Mai 2011 um 15:24
-- Server Version: 5.1.49
-- PHP-Version: 5.3.3-1ubuntu9.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `blindcoder_blindrss_live`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `entries`
--

CREATE TABLE IF NOT EXISTS `entries` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `feedID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `title` text CHARACTER SET latin1 NOT NULL,
  `link` text CHARACTER SET latin1 NOT NULL,
  `description` longtext CHARACTER SET latin1 NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isread` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `link` (`link`(512),`feedID`),
  KEY `isread` (`isread`),
  KEY `feedID` (`feedID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- RELATIONEN DER TABELLE `entries`:
--   `feedID`
--       `feeds` -> `ID`
--

--
-- Trigger `entries`
--
DROP TRIGGER IF EXISTS `unreadOnChange`;
DELIMITER //
CREATE TRIGGER `unreadOnChange` BEFORE UPDATE ON `entries`
 FOR EACH ROW BEGIN
    SELECT IF ((SELECT value FROM options WHERE `key` = 'unreadOnChange') = 'true', '1', '0') INTO @s;
    SELECT IF ((STRCMP(OLD.title, NEW.title) != 0 OR STRCMP(OLD.description, NEW.description) != 0), '0', '1') INTO @t;
    SELECT IF (NEW.isread = '1', '1', (SELECT IF (@s = '1', (SELECT IF (@t='1' AND OLD.isread='1', '1', '0')), OLD.isread))) INTO @r;
    SET NEW.isread=@r;
  END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `feeds`
--

CREATE TABLE IF NOT EXISTS `feeds` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `startID` bigint(20) unsigned NOT NULL,
  `endID` bigint(20) unsigned NOT NULL,
  `name` text CHARACTER SET latin1 NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `movedirection` enum('none','moveme') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name` (`name`(512))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `filter`
--

CREATE TABLE IF NOT EXISTS `filter` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedID` bigint(20) unsigned NOT NULL,
  `whiteorblack` enum('white','black') COLLATE utf8_unicode_ci NOT NULL,
  `regex` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `feedID` (`feedID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- RELATIONEN DER TABELLE `filter`:
--   `feedID`
--       `feeds` -> `ID`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `options`
--

CREATE TABLE IF NOT EXISTS `options` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `filter`
--
ALTER TABLE `filter`
  ADD CONSTRAINT `filter_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

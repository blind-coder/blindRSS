-- phpMyAdmin SQL Dump
--
-- Tabellenstruktur für Tabelle `entries`
--

DROP TABLE IF EXISTS `entries`;
CREATE TABLE `entries` (
  `ID` bigint(20) NOT NULL auto_increment,
  `feedID` bigint(20) unsigned NOT NULL default '0',
  `title` text character set latin1 NOT NULL,
  `link` text character set latin1 NOT NULL,
  `description` longtext character set latin1 NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `isread` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `link` (`link`(512),`feedID`),
  KEY `isread` (`isread`),
  KEY `feedID` (`feedID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `feeds`
--

DROP TABLE IF EXISTS `feeds`;
CREATE TABLE `feeds` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `startID` bigint(20) unsigned NOT NULL,
  `endID` bigint(20) unsigned NOT NULL,
  `name` text character set latin1 NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci default NULL,
  `movedirection` enum('none','moveme') collate utf8_unicode_ci NOT NULL default 'none',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`(512))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `options`
--

DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `key` varchar(20) collate utf8_unicode_ci NOT NULL,
  `value` varchar(20) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE;

--
-- Elemente der Tabelle `feeds`
--
INSERT INTO `feeds`
  (`startID`, `endID`, `name`, `url`, `movedirection`) VALUES
    (1, 2, 'All Feeds', NULL, 'none');

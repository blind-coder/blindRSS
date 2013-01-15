-- phpMyAdmin SQL Dump
-- phpMyAdmin SQL Dump
-- version 2.6.1-pl3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 13. November 2008 um 12:05
-- Server Version: 5.0.51
-- PHP-Version: 5.2.4-2ubuntu5.3
-- 
-- Datenbank: `blindrss`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `entries`
-- 

DROP TABLE IF EXISTS `entries`;
CREATE TABLE IF NOT EXISTS `entries` (
  `ID` bigint(20) NOT NULL auto_increment,
  `feedID` bigint(20) NOT NULL default '0',
  `title` text character set latin1 NOT NULL,
  `link` text character set latin1 NOT NULL,
  `description` longtext character set latin1 NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `isread` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `link` (`link`(512),`feedID`),
  KEY `isread` (`isread`),
  KEY `feedID` (`feedID`)
) ENGINE=MyISAM AUTO_INCREMENT=64592 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `feeds`
-- 

DROP TABLE IF EXISTS `feeds`;
CREATE TABLE IF NOT EXISTS `feeds` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `name` text character set latin1 NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci default NULL,
  `parentID` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `url` (`url`)
) ENGINe=mYISAM AUTO_INCREMENT=84 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

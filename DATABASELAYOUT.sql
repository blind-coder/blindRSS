-- phpMyAdmin SQL Dump
-- version 3.1.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 08, 2009 at 07:12 PM

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `blindrss`
--

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

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
-- Table structure for table `feeds`
--

CREATE TABLE `feeds` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `name` text character set latin1 NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci default NULL,
  `parentID` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `url` (`url`),
  KEY `parentID` (`parentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Default entries for dumped tables
--

--
-- Default entries for table `entries`
--

-- Default entries for table `feeds`
--

INSERT INTO `feeds` ( `ID` , `name` , `url` , `parentID`) VALUES ( '0', 'TOP LEVEL CONTAINER', NULL , '0' );
UPDATE `feeds` SET ID='0' WHERE ID = LAST_INSERT_ID();

--
-- Constraints for dumped tables
--

--
-- Constraints for table `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feeds`
--
ALTER TABLE `feeds`
  ADD CONSTRAINT `feeds_ibfk_1` FOREIGN KEY (`parentID`) REFERENCES `feeds` (`ID`) ON UPDATE CASCADE;

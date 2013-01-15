-- phpMyAdmin SQL Dump
-- version 2.6.1-pl3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jan 18, 2008 at 10:46 AM
-- Server version: 4.1.20
-- PHP Version: 5.1.6
-- 
-- Database: `blindrss`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `entries`
-- 

CREATE TABLE `entries` (
  `ID` bigint(20) NOT NULL auto_increment,
  `feedID` bigint(20) NOT NULL default '0',
  `title` text character set latin1 NOT NULL,
  `link` text character set latin1 NOT NULL,
  `description` longtext character set latin1 NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `isread` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `link` (`link`(512))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `feeds`
-- 

CREATE TABLE `feeds` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `name` text character set latin1 NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        

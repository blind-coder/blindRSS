DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link` text COLLATE utf8_unicode_ci NOT NULL,
  `sha1_link` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `content` longblob NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `sha1_link` (`sha1_link`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

DROP TABLE IF EXISTS `entries`;
CREATE TABLE IF NOT EXISTS `entries` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `link` text COLLATE utf8_unicode_ci NOT NULL,
  `sha1_link` char(40) CHARACTER SET ascii NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isread` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `favorite` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `feedID_2` (`feedID`,`sha1_link`),
  KEY `isread` (`isread`),
  KEY `feedID` (`feedID`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

DROP TABLE IF EXISTS `feeds`;
CREATE TABLE IF NOT EXISTS `feeds` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `startID` bigint(20) unsigned NOT NULL,
  `endID` bigint(20) unsigned NOT NULL,
  `name` text CHARACTER SET latin1 NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cacheimages` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `collapsed` enum('no','yes') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `movedirection` enum('none','moveme') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `name` (`name`(512)),
  KEY `startID` (`startID`),
  KEY `endID` (`endID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

INSERT INTO `feeds` (`ID`, `startID`, `endID`, `name`, `url`, `cacheimages`, `movedirection`) VALUES(1, 1, 2, 'All feeds', NULL, 'no', 'none');

DROP TABLE IF EXISTS `filter`;
CREATE TABLE IF NOT EXISTS `filter` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedID` bigint(20) unsigned NOT NULL,
  `whiteorblack` enum('white','black','ignore') COLLATE utf8_unicode_ci NOT NULL,
  `regex` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `feedID` (`feedID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

DROP TABLE IF EXISTS `options`;
CREATE TABLE IF NOT EXISTS `options` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'deleteFavorites', 'no');
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'deleteTagged', 'no');
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'unreadOnChange', 'true');
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'purgeAfter', '36135');
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'textOrIcons', 'text');

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `tags` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `entries_tags`;
CREATE TABLE `entries_tags` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entryID` bigint(20) unsigned NOT NULL,
  `tagID` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `entryID` (`entryID`),
  KEY `tagID` (`tagID`),
  UNIQUE KEY `onlyonce` (`entryID`, `tagID`),
  CONSTRAINT `entries_tags_ibfk_2` FOREIGN KEY (`tagID`) REFERENCES `tags` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `entries_tags_ibfk_1` FOREIGN KEY (`entryID`) REFERENCES `entries` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE;

ALTER TABLE `filter`
  ADD CONSTRAINT `filter_ibfk_1` FOREIGN KEY (`feedID`) REFERENCES `feeds` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

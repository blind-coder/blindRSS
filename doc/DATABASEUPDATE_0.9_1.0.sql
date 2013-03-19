ALTER TABLE `feeds` ADD `collapsed` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' AFTER `cacheimages`;
ALTER TABLE `entries` ADD `favorite` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' AFTER `isread`;

CREATE TABLE `tags` (
	 `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	 `tag` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
	 PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `entries_tags` (
	 `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	 `entryID` bigint(20) unsigned NOT NULL,
	 `tagID` bigint(20) unsigned NOT NULL,
	 PRIMARY KEY (`ID`),
	 KEY `entryID` (`entryID`),
	 KEY `tagID` (`tagID`),
	 CONSTRAINT `entries_tags_ibfk_2` FOREIGN KEY (`tagID`) REFERENCES `tags` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
	 CONSTRAINT `entries_tags_ibfk_1` FOREIGN KEY (`entryID`) REFERENCES `entries` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `options` (`ID` , `key` , `value`) VALUES (NULL , 'deleteFavorites', 'no');

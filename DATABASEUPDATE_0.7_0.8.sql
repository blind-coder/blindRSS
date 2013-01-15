ALTER TABLE `entries` ENGINE = InnoDB;
ALTER TABLE `entries` DROP INDEX `link`;
ALTER TABLE `entries` CHANGE `ID` `ID` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	CHANGE `feedID` `feedID` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
	CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE `link` `link` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE `description` `description` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE `date` `date` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	CHANGE `isread` `isread` ENUM( '0', '1' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0';
ALTER TABLE `entries` ADD `sha1_link` CHAR( 40 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL AFTER `link` ;
UPDATE entries SET sha1_link = SHA1(link);
ALTER TABLE `entries` ADD UNIQUE `link` ( `feedID` , `sha1_link` );
ALTER TABLE `feeds` ENGINE = InnoDB;
ALTER TABLE `feeds` ADD `cacheimages` ENUM( 'no', 'yes' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER `url`;
ALTER TABLE `entries` ADD FOREIGN KEY ( `feedID` ) REFERENCES `blindcoder_blindrss_demo`.`feeds` ( `ID`) ON DELETE CASCADE;
CREATE TABLE IF NOT EXISTS `cache` (
	`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`link` text COLLATE utf8_unicode_ci NOT NULL,
	`sha1_link` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
	`content` longblob NOT NULL,
	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `sha1_link` (`sha1_link`),
	KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS `filter` (
	`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`feedID` bigint(20) unsigned NOT NULL,
	`whiteorblack` enum('white','black') COLLATE utf8_unicode_ci NOT NULL,
	`regex` text COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`ID`),
	KEY `feedID` (`feedID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `filter` ADD FOREIGN KEY ( `feedID` ) REFERENCES `blindcoder_blindrss_demo`.`feeds` ( `ID`) ON DELETE CASCADE ;
SELECT endID INTO @x FROM feeds WHERE url='SEARCHRESULTS';
DELETE FROM feeds WHERE url='SEARCHRESULTS' LIMIT 1;
UPDATE feeds SET startID=startID-2 WHERE startID >= @x;
UPDATE feeds SET endID=endID-2 WHERE endID >= @x;

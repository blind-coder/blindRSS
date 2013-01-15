ALTER TABLE `feeds` CHANGE `startID` `startID` BIGINT( 20 ) UNSIGNED NOT NULL , CHANGE `endID` `endID` BIGINT( 20 ) UNSIGNED NOT NULL ;
CREATE TABLE `options` (
	`ID` bigint(20) unsigned NOT NULL auto_increment,
	`key` varchar(20) collate utf8_unicode_ci NOT NULL,
	`value` varchar(20) collate utf8_unicode_ci NOT NULL,
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

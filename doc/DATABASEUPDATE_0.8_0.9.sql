ALTER TABLE `entries` ADD INDEX `date` (`date`);

ALTER TABLE `feeds` ADD INDEX `startID` (`startID`);
ALTER TABLE `feeds` ADD INDEX `endID` (`endID`);

ALTER TABLE `filter` CHANGE `whiteorblack` `whiteorblack` ENUM( 'white', 'black', 'ignore' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

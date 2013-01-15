ALTER TABLE `entries` ADD KEY `isread` (`isread`);
ALTER TABLE `entries` ADD KEY `feedID` (`feedID`);
ALTER TABLE `feeds` CHANGE `url` `url` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `feeds` ADD `parentID` BIGINT DEFAULT '0' NOT NULL ;
ALTER TABLE `feeds` DROP INDEX `url`;
ALTER TABLE `feeds` ADD INDEX ( `url` );
ALTER TABLE `entries` DROP INDEX `link` ,
      ADD UNIQUE `link` ( `link` ( 512 ) , `feedID` ) 

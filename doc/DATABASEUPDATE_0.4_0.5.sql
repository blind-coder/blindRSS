ALTER TABLE `feeds` ADD `movedirection` enum('none', 'moveme') NOT NULL DEFAULT 'none' AFTER `url`;
ALTER TABLE `feeds` DROP `parentID`;
ALTER TABLE `feeds` ADD `endID` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER ID;
ALTER TABLE `feeds` ADD `startID` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER ID;
CREATE TABLE `temporaryfeeds` (
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`feedID` BIGINT UNSIGNED NOT NULL
) ENGINE = InnoDB;
INSERT INTO `temporaryfeeds` (feedID) SELECT ID FROM feeds ORDER BY ID;
UPDATE `feeds`, `temporaryfeeds` SET `startID` = `temporaryfeeds`.`ID` * 2, `endID` = `temporaryfeeds`.`ID` * 2 + 1 WHERE `feeds`.`ID` = `temporaryfeeds`.`feedID`;
SELECT MIN(startID)-1 INTO @a FROM feeds;
SELECT MAX(endID)+1 INTO @b FROM feeds;
INSERT INTO `feeds` (startID, endID, name, url) VALUES (@a, @b, "All Feeds", "");
DROP TABLE `temporaryfeeds`;

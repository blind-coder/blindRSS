SELECT `value` INTO @uoC FROM `options` WHERE `key` = "unreadOnChange";
ALTER TABLE `feeds` ADD `unreadOnChange` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes' AFTER `cacheimages`;
UPDATE feeds SET unreadOnChange = IF(@uoC='false','no','yes');
DELETE FROM `options` WHERE `key` = 'unreadOnChange';
INSERT INTO `options` (`key`, `value`) VALUES ('reloadEvery', '-1');
INSERT INTO `options` (`key`, `value`) VALUES ('autoAdvance', 'no');

SELECT `value` INTO @uoC FROM `options` WHERE `key` = "unreadOnChange";
ALTER TABLE `feeds` ADD `unreadOnChange` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes' AFTER `cacheimages`;
UPDATE feeds SET unreadOnChange = IF(@uoC='false','no','yes');
DELETE FROM `options` WHERE `key` = 'unreadOnChange';

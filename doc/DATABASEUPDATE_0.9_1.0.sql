ALTER TABLE `feeds` ADD `collapsed` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' AFTER `cacheimages`;

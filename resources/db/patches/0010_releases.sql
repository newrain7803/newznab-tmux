ALTER TABLE `releases` ADD INDEX `ix_releases_prehashid_searchname` (`prehashid`, `searchname`);
UPDATE `tmux` set `value` = '10' where `setting` = 'sqlpatch';
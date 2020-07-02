CREATE TABLE IF NOT EXISTS `application` ( `id` INT(11) AUTO_INCREMENT, `sql_schema` INT(11) NOT NULL, PRIMARY KEY (id) );

ALTER TABLE `user` DROP `user_online`;
ALTER TABLE `user` DROP `user_last_start`;
ALTER TABLE `user` DROP `user_last_end`;

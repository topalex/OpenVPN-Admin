CREATE TABLE IF NOT EXISTS `application` ( `id` INT(11) AUTO_INCREMENT, `sql_schema` INT(11) NOT NULL, PRIMARY KEY (id) );

ALTER TABLE `user` DROP `user_mail`;
ALTER TABLE `user` DROP `user_phone`;

ALTER TABLE `user` ADD `user_last_start` timestamp NULL DEFAULT NULL;
ALTER TABLE `user` ADD `user_last_end` timestamp NULL DEFAULT NULL;

ALTER TABLE `user` DROP KEY `user_pass`;

ALTER TABLE `user` ENGINE = InnoDB;
ALTER TABLE `log` ENGINE = InnoDB;
ALTER TABLE `admin` ENGINE = InnoDB;

ALTER TABLE `log` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE;

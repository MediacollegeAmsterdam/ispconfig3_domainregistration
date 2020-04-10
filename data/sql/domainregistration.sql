CREATE TABLE `domainregistration` (
  `id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` INT(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` INT(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` VARCHAR(5) DEFAULT NULL,
  `sys_perm_group` VARCHAR(5) DEFAULT NULL,
  `sys_perm_other` VARCHAR(5) DEFAULT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `registrar_identifier` VARCHAR(255) NOT NULL COMMENT 'The identifier under which this domain is known at the registrar',
  `registered_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  INDEX `registered_at` (`registered_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;

CREATE TABLE `domainregistration_config` (
  `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` VARCHAR(255) NOT NULL,
  `config_value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;

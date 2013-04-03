DROP TABLE IF EXISTS `issues`;

CREATE TABLE `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(33) DEFAULT NULL,
  `first_seen` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `silent` int(1) DEFAULT '0',
  `expected` int(1) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `notes` text,
  `type` enum('PHPERROR','PHPEXCEPTION','LOGICAL') DEFAULT 'PHPERROR',
  `repeat` int(7) DEFAULT '1',
  `status` enum('SOLVED','ACTIVE','EXPIRED') DEFAULT 'ACTIVE',
  `source` varchar(20) DEFAULT NULL,
  `hidden` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(100) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `line` int(10) DEFAULT NULL,
  `trace` mediumtext,
  `created_at` datetime DEFAULT NULL,
  `source` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

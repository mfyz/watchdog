DROP TABLE IF EXISTS `schema_version`;

CREATE TABLE `schema_version` (
		`version` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `schema_version` (`version`) VALUES ('1');
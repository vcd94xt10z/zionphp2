CREATE TABLE IF NOT EXISTS `zion_sequence` (
  `name` varchar(100) NOT NULL,
  `last_value` int(11) DEFAULT 1,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
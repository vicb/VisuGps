-- --------------------------------------------------------

-- 
-- Table structure for table `flight`
-- 

DROP TABLE IF EXISTS `flight`;
CREATE TABLE IF NOT EXISTS `flight` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pilotID` int(10) unsigned NOT NULL,
  `start` datetime default NULL,
  `end` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pilot`
-- 

DROP TABLE IF EXISTS `pilot`;
CREATE TABLE IF NOT EXISTS `pilot` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pseudo` char(8) NOT NULL,
  `name` char(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `pseudo` (`pseudo`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `point`
-- 

DROP TABLE IF EXISTS `point`;
CREATE TABLE IF NOT EXISTS `point` (
  `flightId` int(10) unsigned NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `elevation` int(11) NOT NULL,
  `time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

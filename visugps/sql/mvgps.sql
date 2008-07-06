-- --------------------------------------------------------

--
-- Table structure for table `flight`
--

CREATE TABLE flight (
  id int(10) unsigned NOT NULL auto_increment,
  pilotID int(10) unsigned NOT NULL,
  `start` datetime default NULL,
  `end` datetime default NULL,
  utc tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (id)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pilot`
--

CREATE TABLE pilot (
  id int(10) unsigned NOT NULL auto_increment,
  pseudo char(8) NOT NULL,
  `name` char(30) NOT NULL,
  email char(60) NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY pseudo (pseudo,`name`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `point`
--

CREATE TABLE `point` (
  flightId int(10) unsigned NOT NULL,
  latitude float NOT NULL,
  longitude float NOT NULL,
  elevation int(11) NOT NULL,
  `time` datetime NOT NULL,
  KEY flightId (flightId)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

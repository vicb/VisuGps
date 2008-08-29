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
  ua char(40) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `flightInfo`
-- 

CREATE TABLE flightInfo (
  id int(11) NOT NULL,
  startLocation char(30) collate latin1_german2_ci default NULL,
  startCountry char(30) collate latin1_german2_ci default NULL,
  endLocation char(30) collate latin1_german2_ci default NULL,
  endCountry char(30) collate latin1_german2_ci default NULL,
  timezone char(30) collate latin1_german2_ci default NULL,
  UNIQUE KEY id (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=latin1;

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
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  KEY flightId (flightId)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `test`
-- 

CREATE TABLE test (
  id smallint(5) unsigned NOT NULL default '0',
  `time` datetime NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
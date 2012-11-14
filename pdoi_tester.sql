-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 14, 2012 at 03:46 AM
-- Server version: 5.5.16
-- PHP Version: 5.4.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `pdoi_tester`
--

-- --------------------------------------------------------

--
-- Table structure for table `manifest`
--

CREATE TABLE IF NOT EXISTS `manifest` (
  `mani_id` int(11) NOT NULL AUTO_INCREMENT,
  `ship_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `role` varchar(15) NOT NULL,
  PRIMARY KEY (`mani_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `manifest`
--

INSERT INTO `manifest` (`mani_id`, `ship_id`, `person_id`, `role`) VALUES
(1, 4, 1, 'Captain');

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE IF NOT EXISTS `persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `species` varchar(20) NOT NULL,
  `planet` varchar(20) NOT NULL,
  `system` varchar(5) NOT NULL,
  `solar_years` int(11) NOT NULL,
  `class` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `persons`
--

INSERT INTO `persons` (`id`, `name`, `species`, `planet`, `system`, `solar_years`, `class`) VALUES
(1, 'Jim', 'Human', 'Earth', 'Sol', 27, 'Warrior'),
(2, 'Frank', 'Human', 'Earth', 'Sol', 45, 'Mage'),
(5, 'Larry', 'Human', 'Earth', 'Sol', 35, 'Captain'),
(8, 'Ziam', 'Martian', 'Mars', 'Sol', 8, 'Raider'),
(9, 'Zim', 'Martian', 'Mars', 'Sol', 8, 'Raider'),
(13, 'Estr', 'Venutian', 'Venus', 'Sol', 128, 'Soldier'),
(14, 'Nubi', 'Kemetin', 'Khm', 'Ank', 10000, 'god'),
(15, 'Nabe', 'Felis', 'Fera', 'Li', 4, 'Engineer');

-- --------------------------------------------------------

--
-- Table structure for table `ships`
--

CREATE TABLE IF NOT EXISTS `ships` (
  `ship_id` int(11) NOT NULL AUTO_INCREMENT,
  `ship_name` varchar(35) NOT NULL,
  `ship_description` text NOT NULL,
  PRIMARY KEY (`ship_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `ships`
--

INSERT INTO `ships` (`ship_id`, `ship_name`, `ship_description`) VALUES
(4, 'Cyathan', 'Fighter Ship'),
(5, 'Eschul', 'Mining Vessel'),
(6, 'Varuul', 'Carrier');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

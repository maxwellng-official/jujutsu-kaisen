-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 14, 2026 at 10:11 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `5114asst1`
--

-- --------------------------------------------------------

--
-- Table structure for table `album`
--

CREATE TABLE `album` (
  `idalbum` int(11) NOT NULL,
  `title` varchar(45) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL,
  `idcreator` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `idcomment` int(11) NOT NULL,
  `text` varchar(500) NOT NULL,
  `idcreator` int(11) NOT NULL,
  `idphoto` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creator`
--

CREATE TABLE `creator` (
  `idcreator` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

CREATE TABLE `photo` (
  `idphoto` int(11) NOT NULL,
  `title` varchar(45) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL,
  `comment` varchar(140) DEFAULT NULL,
  `idcreator` int(11) DEFAULT NULL,
  `idalbum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `photo`
--

INSERT INTO `photo` (`idphoto`, `title`, `imageurl`, `comment`, `idcreator`, `idalbum`) VALUES
(4, 'Retirement plan', 'uploads/TAL--new-zealand-MSTGORGCNTRY0524-60b4b6e4ba6249d2afef5c9e35d38ba8.jpg', 'New zealand', NULL, NULL),
(6, 'Hang out', 'uploads/central park.png', 'Central Park, New York City', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `album`
--
ALTER TABLE `album`
  ADD PRIMARY KEY (`idalbum`),
  ADD KEY `album_creator_idx` (`idcreator`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`idcomment`),
  ADD KEY `idcreator` (`idcreator`),
  ADD KEY `idphoto` (`idphoto`);

--
-- Indexes for table `creator`
--
ALTER TABLE `creator`
  ADD PRIMARY KEY (`idcreator`);

--
-- Indexes for table `photo`
--
ALTER TABLE `photo`
  ADD PRIMARY KEY (`idphoto`),
  ADD KEY `photo_album_idx` (`idalbum`),
  ADD KEY `photo_creator_idx` (`idcreator`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `album`
--
ALTER TABLE `album`
  MODIFY `idalbum` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `idcomment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `creator`
--
ALTER TABLE `creator`
  MODIFY `idcreator` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `photo`
--
ALTER TABLE `photo`
  MODIFY `idphoto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `album`
--
ALTER TABLE `album`
  ADD CONSTRAINT `album_creator` FOREIGN KEY (`idcreator`) REFERENCES `creator` (`idcreator`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`idcreator`) REFERENCES `creator` (`idcreator`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`idphoto`) REFERENCES `photo` (`idphoto`) ON DELETE CASCADE;

--
-- Constraints for table `photo`
--
ALTER TABLE `photo`
  ADD CONSTRAINT `image_album` FOREIGN KEY (`idalbum`) REFERENCES `album` (`idalbum`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `photo_creator` FOREIGN KEY (`idcreator`) REFERENCES `creator` (`idcreator`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

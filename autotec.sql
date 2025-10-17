-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 09:09 AM
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
-- Database: `autotec`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

CREATE TABLE `about_us` (
  `AboutID` int(11) NOT NULL,
  `BranchName` varchar(150) NOT NULL,
  `Picture` varchar(255) NOT NULL,
  `MapLink` text NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_us`
--

INSERT INTO `about_us` (`AboutID`, `BranchName`, `Picture`, `MapLink`, `Description`) VALUES
(1, 'Autotec Shaw', 'C:\\xampp\\htdocs\\autotec\\pictures\\branches\\shaw.jpg', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.241577384628!2d121.04533151052284!3d14.585305777392428!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c9f7ff105f7d%3A0xcd14a0f46957829b!2sAutoTEC%20(Automotive%20Testing%20Center)!5e0!3m2!1sen!2sph!4v1753117381151!5m2!1sen!2sph', 'Our main branch located at SHAW.'),
(2, 'Autotec Subic', 'uploads/branches/1754029445_ayatoe.png', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3857.0627523368034!2d120.29917107377001!3d14.821735813714891!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339671a7a730a4f1%3A0xdbd8454c90e11b57!2sAutoTEC%20(Automotive%20Testing%20and%20Emission%20Center)!5e0!3m2!1sen!2sph!4v1753117735031!5m2!1sen!2sph', 'Our other branch is located at Subic.');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `BranchName` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `Email`, `password`, `BranchName`) VALUES
(1, 'yanna', 'janicaannyac@gmail.com', 'pjmftsuga', 'Autotec Shaw');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

CREATE TABLE `contact_us` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_us`
--

INSERT INTO `contact_us` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `message`, `created_at`, `status`) VALUES
(1, 'Simon Vincent', 'Santos', 'janicaannyac@gmail.com', '0983712397', 'asdsad', '2025-07-16 15:03:51', 'unread'),
(2, 'Janica Annya', 'Santos', 'shiarariley@gmail.com', '0983712397', 'adssad', '2025-07-16 15:05:39', 'unread'),
(3, 'adsad', 'asdsad', 'vesperrr777@gmail.com', '0983712397', 'asdsadsad', '2025-07-16 15:06:15', 'unread'),
(4, 'kyla', 'adsad', 'janicaannyac@gmail.com', 'asdsad', 'asddasd', '2025-07-16 15:06:30', 'unread'),
(5, 'Simon Vincent', 'Castillo', 'janicaannyac@gmail.com', '0983712397', 'asdsadasd', '2025-07-16 16:41:30', 'unread');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ReservationID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `PlateNo` varchar(20) NOT NULL,
  `Brand` varchar(100) NOT NULL,
  `TypeID` int(11) DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `Fname` varchar(100) NOT NULL,
  `Lname` varchar(100) NOT NULL,
  `Mname` varchar(100) DEFAULT NULL,
  `PhoneNum` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Address` text NOT NULL,
  `BranchName` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`ReservationID`, `UserID`, `PlateNo`, `Brand`, `TypeID`, `CategoryID`, `Fname`, `Lname`, `Mname`, `PhoneNum`, `Email`, `Date`, `Time`, `Address`, `BranchName`) VALUES
(1, NULL, 'DEF-200', 'Toyota', 1, 3, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicachiu@gmail.com', '2025-07-25', '09:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(2, NULL, 'ABC-123', 'Toyota', 6, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannya@gmail.com', '2025-07-25', '11:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(3, NULL, 'DEF-200', 'Toyota', 6, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-08-28', '13:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(4, NULL, 'DEF-200', 'Toyota', 6, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'vesperrr777@gmail.com', '2025-08-22', '16:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(5, NULL, 'DEF-200', 'Toyota', 6, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicachiu@gmail.com', '2025-07-30', '10:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(6, NULL, 'DEF-200', 'Toyota', 6, 3, 'Janica Annya', 'Castillo', 'Quinsaat', '0939-253-1490', 'vesperrr777@gmail.com', '2025-09-25', '15:40:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(7, NULL, 'DEF-200', 'Toyota', 6, 1, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-09-19', '14:20:00', '11B Molave Street Jabson', NULL),
(8, NULL, 'DEF-200', 'Toyota', 6, 3, 'Simon Vincent', 'Santos', 'Quinsaat', '0939-253-1490', 'janicachiu@gmail.com', '2025-07-21', '16:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(9, NULL, 'DEF-200', 'Toyota', 6, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'vesperrr777@gmail.com', '2025-09-12', '16:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(10, NULL, 'DEF-200', 'Toyota', 6, 2, 'Simon Vincent', 'Chiu', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-08-13', '14:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(11, NULL, 'DEF-200', 'Toyota', 3, 3, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'vesperrr777@gmail.com', '2025-07-22', '10:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(12, NULL, 'DEF-200', 'Toyota', 6, 3, 'Simon Vincent', 'Santos', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-09-05', '16:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(13, 1, 'DEF-200', 'Toyota', 5, 3, 'Janica Annya', 'Chiu', 'Quinsaat', '0939-253-1490', 'janicachiu@gmail.com', '2025-09-30', '15:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(14, 2, 'ATK-400', 'Toyota', 3, 1, 'Janica Annya', 'estrella', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-09-30', '15:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(15, 2, 'ATK-900', 'Toyota', 3, 3, 'Janica Annya', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicachiu@gmail.com', '2025-09-12', '15:40:00', 'VJTI, Matunga, Mumbai, Maharashtra', NULL),
(16, 1, 'TRY-123', 'Toyota', 4, 3, 'Janica Annya', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-07-25', '13:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(17, 1, 'ATK-400', 'Toyota', 3, 2, 'Simon Vincent', 'Santos', 'Quinsaat', '0939-253-1490', 'janicaannya@gmail.com', '2025-09-23', '14:40:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(18, 1, 'TRY-123', 'Toyota', 6, 2, 'Janica Annya', 'Santos', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-09-01', '13:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(19, 1, 'ATK-900', 'Toyota', 3, 2, 'Janica Annya', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-10-17', '14:00:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(20, 1, 'DEF-200', 'Toyota', 4, 3, 'Janica Annya', 'Chiu', 'Quinsaat', '0939-253-1490', 'janicaannyac@gmail.com', '2025-09-22', '16:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', NULL),
(21, NULL, 'ATK-400', 'Toyota', 3, 3, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'shiarariley@gmail.com', '2025-10-16', '09:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', 'Autotec Shaw'),
(22, 1, 'ATK-400', 'Toyota', 3, 2, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'vesperrr777@gmail.com', '2025-10-09', '14:20:00', 'VJTI, Matunga, Mumbai, Maharashtra', 'Autotec Shaw'),
(23, 1, 'ATK-900', 'Toyota', 5, 3, 'Simon Vincent', 'Castillo', 'Quinsaat', '0939-253-1490', 'janicaannya@gmail.com', '2025-10-08', '14:20:00', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', 'Autotec Shaw');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Fname` varchar(100) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Fname`, `Username`, `password`, `Email`, `Address`, `CreatedAt`) VALUES
(1, 'Janica Annya Chiu', 'yanna', 'mingloss.03', 'janicaannyac@gmail.com', '11B Molave Street Jabson', '2025-07-16 17:55:37'),
(2, 'asteri', 'asteri', 'Kyla123', 'vesperrr777@gmail.com', NULL, '2025-07-21 02:09:09'),
(3, 'Nel Quez', 'terii', '123456789', 'janelleabarquez0304@gmail.com', 'pasig etivac', '2025-07-21 02:31:24'),
(4, 'Ian Emmanuel Palabrica', 'ronovasimp', 'ianian', 'ianpalabrica@gmail.com', '12-B Alcalde Jose, Pasig, 1600 Metro Manila', '2025-07-22 17:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_categories`
--

CREATE TABLE `vehicle_categories` (
  `CategoryID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_categories`
--

INSERT INTO `vehicle_categories` (`CategoryID`, `Name`) VALUES
(1, 'Private'),
(2, 'Commercial'),
(3, 'Government');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `VehicleTypeID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`VehicleTypeID`, `Name`, `Price`) VALUES
(1, 'Car', 750.00),
(2, 'Van', 750.00),
(3, 'Motorcycle', 650.00),
(4, 'Tricycle', 500.00),
(5, 'Jeepney', 500.00),
(6, 'try', 700.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_us`
--
ALTER TABLE `about_us`
  ADD PRIMARY KEY (`AboutID`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `contact_us`
--
ALTER TABLE `contact_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`ReservationID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `TypeID` (`TypeID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `email_2` (`Email`),
  ADD UNIQUE KEY `Email_3` (`Email`);

--
-- Indexes for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`VehicleTypeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_us`
--
ALTER TABLE `about_us`
  MODIFY `AboutID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `VehicleTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`TypeID`) REFERENCES `vehicle_types` (`VehicleTypeID`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`CategoryID`) REFERENCES `vehicle_categories` (`CategoryID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

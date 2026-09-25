-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 02:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schema`
--

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `crime_type` varchar(50) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `incident_date` datetime NOT NULL,
  `severity` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `reported_by_uid` varchar(255) DEFAULT NULL,
  `reported_by_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `barangay`, `crime_type`, `latitude`, `longitude`, `incident_date`, `severity`, `notes`, `created_at`, `is_archived`, `reported_by_uid`, `reported_by_name`) VALUES
(1, 'Barangay 1', 'Theft', 14.59950000, 120.98420000, '2026-05-15 14:30:00', 'Medium', 'Stolen smartphone near grocery store.', '2026-05-20 12:09:18', 0, NULL, NULL),
(2, 'Barangay 1', 'Theft', 14.60100000, 120.98900000, '2026-05-18 21:00:00', 'High', 'Snatching incident reported on main road.', '2026-05-20 12:09:18', 0, NULL, NULL),
(3, 'Barangay 2', 'Assault', 14.58000000, 121.00000000, '2026-05-10 02:15:00', 'High', 'Physical altercation outside a bar.', '2026-05-20 12:09:18', 1, NULL, NULL),
(4, 'Barangay 3', 'Robbery', 14.59000000, 120.97000000, '2026-05-19 18:45:00', 'Medium', 'Unattended commercial establishment broken into.', '2026-05-20 12:09:18', 0, NULL, NULL),
(5, 'Paseo de Roxas, District I', 'Theft', 14.55361100, 121.01956300, '2026-05-20 21:57:00', 'Medium', '', '2026-05-20 14:00:31', 0, NULL, NULL),
(6, 'san juan', 'Theft', 1.00000000, -1.00000000, '2026-05-21 11:38:00', 'Medium', '', '2026-05-21 08:38:18', 0, NULL, NULL),
(7, 'paciano', 'Theft', 14.59544500, 120.99302600, '2026-05-20 11:44:00', 'High', 'lkhfalksfakshflasdf', '2026-05-21 08:44:53', 0, NULL, NULL),
(8, 'Maysilo Circle, Brgy. Royal Townhomes, Mandaluyong', 'Assault', 14.57630700, 121.03407400, '2026-05-21 02:30:00', 'High', 'Name of the victim and suspect', '2026-05-21 09:30:55', 1, NULL, NULL),
(9, 'Paz Mendoza Guazon Street, Barangay 831, Manila', 'Theft', 14.58808600, 120.99767500, '2026-05-21 13:31:00', 'High', 'Key Legal Elements\r\nTo be convicted of carnapping, the following elements must be present: \r\nTaking: Actual physical possession of the motor vehicle.\r\nOwnership: The motor vehicle belongs to someone other than the offender.\r\nConsent: The taking is done without the owner\'s consent or involves violence, intimidation, or force upon things.\r\nIntent to Gain: The offender intends to benefit financially or unlawfully dispose of the vehicle', '2026-05-21 09:32:11', 0, NULL, NULL),
(10, 'Pasaje Ong, Barangay 831, Manila', 'Carnapping', 14.58571800, 120.99866000, '2026-05-21 14:32:00', 'High', 'Key Legal Elements\r\nTo be convicted of carnapping, the following elements must be present: \r\nTaking: Actual physical possession of the motor vehicle.\r\nOwnership: The motor vehicle belongs to someone other than the offender.\r\nConsent: The taking is done without the owner\'s consent or involves violence, intimidation, or force upon things.\r\nIntent to Gain: The offender intends to benefit financially or unlawfully dispose of the vehicle', '2026-05-21 09:32:47', 0, NULL, NULL),
(11, 'barangay 1', 'Assault', 4.00000000, -4.00000000, '2026-05-21 14:45:00', 'High', 'Physical altercation outside at home\r\n.', '2026-05-21 09:46:04', 1, NULL, NULL),
(12, 'Castaños Street, Brgy. Sampaloc, Manila', 'Robbery', 14.60211500, 120.99384800, '2026-05-21 15:37:00', 'Medium', 'akjsfajdfhasf', '2026-05-21 10:37:43', 0, NULL, NULL),
(13, '7th Street, Brgy. Santa Ana, Manila', 'Physical Injury', 14.58571000, 121.00756500, '2026-05-21 16:12:00', 'Medium', 'asdadas', '2026-05-21 11:14:37', 0, '5', 'rein bacsain'),
(14, 'Escolta Street, Brgy. Binondo, Manila', 'Rape', 14.59881700, 120.97948500, '2026-05-21 16:16:00', 'Medium', 'dasd', '2026-05-21 11:16:23', 0, '5', 'rein bacsain'),
(15, 'Ayala Boulevard, Brgy. 659, Manila', 'Carnapping of MV', 14.58637400, 120.98458200, '2026-05-21 16:38:00', 'Low', 'fsfg', '2026-05-21 11:38:41', 0, '7', 'John Mayer'),
(16, 'San Fernando Street, Brgy. San Nicolas, Manila', 'drugs', 14.59926600, 120.97271800, '2026-05-21 16:40:00', 'Medium', 'asdada', '2026-05-21 11:40:53', 0, '7', 'John Mayer'),
(17, 'J. Posadas Street, Brgy. Santa Ana, Manila', 'Homicide', 14.58538600, 121.00822700, '2026-05-21 16:45:00', 'High', '', '2026-05-21 11:45:37', 0, '7', 'John Mayer');

-- --------------------------------------------------------

--
-- Table structure for table `qr_logins`
--

CREATE TABLE `qr_logins` (
  `id` int(11) NOT NULL,
  `qr_token` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_logins`
--

INSERT INTO `qr_logins` (`id`, `qr_token`, `user_id`, `status`, `created_at`) VALUES
(1, 'da58ee79e82e371f87e869b0cb90330c', NULL, 'pending', '2026-05-21 10:22:36'),
(2, 'fbb8c7fee2a9f42d797d26a4ba11fb4a', NULL, 'pending', '2026-05-21 10:24:48'),
(3, '6cac316f145f8bf8fd24767190168b2d', NULL, 'pending', '2026-05-21 10:24:55'),
(4, 'b4f7b21f4bb861fcb357000726548948', NULL, 'pending', '2026-05-21 10:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` varchar(20) DEFAULT 'admin',
  `qr_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `fname`, `lname`, `email`, `password`, `type`, `qr_token`) VALUES
(1, 'System', 'Admin', 'admin@crimemapping.gov', '$2y$10$wgrqQYKzbhtkMRJAS.9NZ.42EcjwiAZnAReVlyTpnqBVG0GNHJ6Pq', 'admin', NULL),
(2, 'lei', 'la', 'leila@gmail.com', '$2y$10$7R3vXmN3E8K9SgWfJbH2uO0KzA5v6T7y8U9i0o1p2q3r4s5t6u7vW', 'admin', NULL),
(3, 'Jose', 'Rizal', 'joserizal@gmail.com', '$2y$10$JiHM3dAl9TLYKNF/fH3QIOf6QwaBHn9EsdX7eGE1Z/8OCXr9yyD2K', 'admin', NULL),
(4, 'sol', 'man', 'solman@gmail.com', '$2y$10$mDo7X0TtGIlUQJhCjml.MOG7aGQQ8u2RGxeTJE98oSyGUNxNoDVLq', 'admin', NULL),
(5, 'rein', 'bacsain', 'reinbacsain1@gmail.com', '$2y$10$5n.CpaP8LXQow4lVq7f1JOMrzRZDrSangnUcnAPaCZW4GB1WjH3ei', 'admin', NULL),
(6, 'Nami', 'Chan', 'reinbacsain@gmail.com', '$2y$10$.r/QPjtsEQtTmD.ABhBin.3B0cZaH.PFpVMtwutkqrjoLYOp9uL8e', 'admin', NULL),
(7, 'John', 'Mayer', 'leilabataller@gmail.com', '$2y$10$6kppxhJhgGGx7XOXLcEvUenNP3sT6BC0rMIJcm5INgey4Gjja0Xf.', 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `try_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qr_logins`
--
ALTER TABLE `qr_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_token` (`qr_token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `qr_token` (`qr_token`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `qr_logins`
--
ALTER TABLE `qr_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

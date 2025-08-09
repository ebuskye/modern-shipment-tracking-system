-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2025 at 05:50 PM
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
-- Database: `shipment_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `location_cache`
--

CREATE TABLE `location_cache` (
  `id` int(11) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_cache`
--

INSERT INTO `location_cache` (`id`, `location_name`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'Dubai, UAE', 25.20484930, 55.27078280, '2025-08-09 09:32:36'),
(2, 'Toronto, Canada', 43.65348170, -79.38393470, '2025-08-09 09:32:36'),
(3, 'Los Angeles, California, USA', 34.05490760, -118.24267730, '2025-08-09 09:32:36'),
(4, 'London, UK', 51.50732190, -0.12764740, '2025-08-09 09:32:36'),
(5, 'Paris, France', 48.85349510, 2.34839150, '2025-08-09 09:32:36'),
(6, 'Frankfurt, Germany', 50.11064440, 8.68209170, '2025-08-09 09:32:36'),
(7, 'Tokyo, Japan', 35.68283870, 139.75945490, '2025-08-09 09:32:36'),
(8, 'Singapore', 1.29664260, 103.77639390, '2025-08-09 09:32:36'),
(9, 'Mumbai, India', 19.07854510, 72.87765590, '2025-08-09 09:32:36'),
(10, 'New York, USA', 40.71272810, -74.00601520, '2025-08-09 09:32:36'),
(11, 'Chicago, USA', 41.87556160, -87.62442120, '2025-08-09 09:32:36'),
(12, 'Vancouver, Canada', 49.26087240, -123.11395290, '2025-08-09 09:32:36'),
(13, 'Sydney, Australia', -33.86984390, 151.20828480, '2025-08-09 09:32:36'),
(14, 'São Paulo, Brazil', -23.55065070, -46.63338240, '2025-08-09 09:32:36'),
(15, 'Lagos, Nigeria', 6.45505750, 3.39417950, '2025-08-09 09:44:34');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `origin_location` varchar(255) NOT NULL,
  `origin_description` varchar(500) DEFAULT NULL,
  `current_location` varchar(255) NOT NULL,
  `current_description` varchar(500) DEFAULT NULL,
  `destination_location` varchar(255) NOT NULL,
  `destination_description` varchar(500) DEFAULT NULL,
  `status` enum('dispatched','in_transit','delivered','delayed') DEFAULT 'in_transit',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `tracking_number`, `origin_location`, `origin_description`, `current_location`, `current_description`, `destination_location`, `destination_description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SH001234567890', 'Dubai, UAE', 'Package dispatched from Dubai distribution center', 'Toronto, Canada', 'Package in transit through Canadian customs', 'Los Angeles, California, USA', 'Destination delivery address', 'in_transit', '2025-08-09 09:32:36', '2025-08-09 09:32:36'),
(2, 'AWB833043833582', 'Lagos, Nigeria', 'from walehouse', 'Dubai, UAE', 'on transit', 'Los Angeles, California, USA', 'from walehouse', 'dispatched', '2025-08-09 09:44:29', '2025-08-09 09:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_history`
--

CREATE TABLE `shipment_history` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_history`
--

INSERT INTO `shipment_history` (`id`, `shipment_id`, `location`, `description`, `status`, `latitude`, `longitude`, `updated_by`, `created_at`) VALUES
(1, 1, 'Dubai, UAE', 'Package dispatched from Dubai distribution center', 'dispatched', 25.20484930, 55.27078280, 'system', '2025-08-09 09:32:36'),
(2, 1, 'Toronto, Canada', 'Package arrived at Toronto customs', 'in_transit', 43.65348170, -79.38393470, 'system', '2025-08-09 09:32:36'),
(3, 2, 'Lagos, Nigeria', 'from walehouse', 'dispatched', 6.45505750, 3.39417950, 'system', '2025-08-09 09:44:34'),
(4, 2, 'Dubai, UAE', 'on transit', 'current', 25.20484930, 55.27078280, 'system', '2025-08-09 09:46:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `location_cache`
--
ALTER TABLE `location_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `location_name` (`location_name`),
  ADD KEY `idx_location_name` (`location_name`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `idx_tracking_number` (`tracking_number`);

--
-- Indexes for table `shipment_history`
--
ALTER TABLE `shipment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shipment_history_id` (`shipment_id`),
  ADD KEY `idx_shipment_history_date` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `location_cache`
--
ALTER TABLE `location_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shipment_history`
--
ALTER TABLE `shipment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shipment_history`
--
ALTER TABLE `shipment_history`
  ADD CONSTRAINT `shipment_history_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

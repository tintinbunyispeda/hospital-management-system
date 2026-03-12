-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 03:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_datetime` datetime NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_datetime`, `status`, `notes`, `created_at`) VALUES
(1, 1, 9, '2025-12-02 15:00:00', 'Completed', '', '2025-04-15 08:22:22'),
(2, 2, 28, '2025-04-15 16:00:00', 'Completed', '', '2025-04-15 09:11:45'),
(3, 3, 25, '2025-05-14 15:00:00', 'Scheduled', '', '2025-05-14 00:54:50'),
(4, 3, 28, '2025-05-14 13:00:00', 'Completed', '', '2025-05-14 00:55:19'),
(5, 3, 9, '2025-06-02 16:00:00', 'Scheduled', '', '2025-05-14 00:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `name`, `specialty_id`, `room_number`, `phone`, `email`, `username`, `password`, `created_at`) VALUES
(9, 'Andika Pratama', 1, '101A', '081234567890', 'andika.pratama@hospital.com', 'drandika', '$2y$10$2voHN3k48ZlHLjLZFhCS.efxIg0HR1Lu536Y2HqERv1dlqqhjc/TW', '2025-04-15 07:39:09'),
(25, 'Siti Handayani', 5, '202B', '0989385428', 'siti@gmail.com', 'drsiti', '$2y$10$rcbsaed8O7GympSVIHRvcOBMG6Nj1Pd1SO6Wpf4DbhKGFu4lTSPPC', '2025-04-15 09:00:04'),
(26, 'Bambang Haryanto', 8, '303C', '0834256272', 'bambang@gmail.com', 'drbang', '$2y$10$Qmt1Ghc7GQRNG6TRezh8Ou29pUK1vRnKo3J2FxAgOil2X2I8O72fa', '2025-04-15 09:02:49'),
(27, 'Nina Paramitha', 6, '102A', '08253563212', 'nina@gmail.com', 'drnina', '$2y$10$fuxBDMLrx8QkwILQ51.fiOvDqrQppzMMQK/1.1jTj0aIxWCHLfbsC', '2025-04-15 09:04:07'),
(28, 'Cristine Valentina', 6, '103A', '08937492911', 'itin@gmail.com', 'dritin', '$2y$10$VsC0OhGwjg4IA.5mCjL/lOiNdJdese5DbiiU2VuxM1DjGI4vYPARa', '2025-04-15 09:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `doctor_id`, `appointment_id`, `diagnosis`, `prescription`, `notes`, `created_at`) VALUES
(1, 2, 28, 2, 'ADHD', '', '', '2025-04-15 09:54:37'),
(2, 1, 9, 1, 'ADHD', '', '', '2025-05-14 01:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `name`, `dob`, `gender`, `phone`, `email`, `address`, `username`, `password`, `created_at`) VALUES
(1, 'Naila Olivia Ramadhani', '2005-10-10', 'Female', '81384570236', 'naila.oliviaaa@gmail.com', 'perum.wahana pd gede blok m4 no2', 'fullsun', '$2y$10$eYTBcI1sVV5I3HDGbs7xYOKcLLgMfzVVEC39hrDsIl6825m.rtbDW', '2025-04-14 08:19:54'),
(2, 'Michelle', '2006-01-02', 'Other', '089823432234', 'misel@gmail.com', 'Medan', 'misel', '$2y$10$0WKuBJT1tHWSXHg91yX82uYOZ9H5W6F3STEUL59uXw2yWHvmHQ0PK', '2025-04-15 09:06:27'),
(3, 'shanty', '2007-02-12', 'Female', '085432445654', 'shanty@gmail.com', 'india', 'shanty', '$2y$10$KHBA25l.9XgnfwZjQ.BcCeOoJtvCI8t/vAcTd9W7Bra1Ov32GMAj6', '2025-05-14 00:54:10');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Credit Card','Insurance','Bank Transfer') NOT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `appointment_id`, `amount`, `payment_date`, `payment_method`, `status`) VALUES
(1, 2, 100.00, '2025-05-13', 'Bank Transfer', 'Completed'),
(2, 4, 100.00, '2025-05-14', '', 'Pending'),
(3, 1, 100.00, '2025-05-14', '', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `specialty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`specialty_id`, `name`) VALUES
(1, 'Cardiology'),
(5, 'Dermatology'),
(8, 'ENT'),
(6, 'Internal Medicine'),
(2, 'Neurology'),
(7, 'Ophthalmology'),
(3, 'Orthopedics'),
(4, 'Pediatrics');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`specialty_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`specialty_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

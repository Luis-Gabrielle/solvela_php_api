-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2025 at 01:40 PM
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
-- Database: `atmd_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `carddetails`
--

CREATE TABLE `carddetails` (
  `Id` int(11) NOT NULL,
  `CardHolderName` varchar(100) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `CardNumber` char(19) NOT NULL,
  `ExpiryDate` char(5) NOT NULL,
  `CurrentBalance` decimal(10,2) DEFAULT 1000.00,
  `CVV` varchar(3) DEFAULT NULL,
  `SavingsBalance` decimal(18,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carddetails`
--

INSERT INTO `carddetails` (`Id`, `CardHolderName`, `Email`, `CardNumber`, `ExpiryDate`, `CurrentBalance`, `CVV`, `SavingsBalance`) VALUES
(37, 'Luis Gabrielle Estacio', 'estacio.gabrielle31@gmail.com', '2857 9181 0106 9724', '04/27', 1000.00, '664', 0.00),
(38, 'Adrian Mhaki Macabali', 'adm@gmail.com', '4013 8080 5519 6635', '04/27', 1000.00, '361', 0.00),
(39, 'Megan Esguerra', '0906megan64@gmail.com', '9492 0494 8140 4776', '04/27', 1000.00, '748', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `user_id`, `otp`, `expiry`, `created_at`) VALUES
(33, 39, '291040', '2025-04-10 13:24:07', '2025-04-10 04:54:07');

-- --------------------------------------------------------

--
-- Table structure for table `pindetails`
--

CREATE TABLE `pindetails` (
  `Id` int(11) NOT NULL,
  `CardId` int(11) NOT NULL,
  `Pin` char(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pindetails`
--

INSERT INTO `pindetails` (`Id`, `CardId`, `Pin`) VALUES
(122, 37, '1111'),
(123, 38, '6601'),
(124, 39, '6834');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `Id` int(11) NOT NULL,
  `CardId` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `TransactionType` varchar(50) NOT NULL,
  `TransactionDate` datetime NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carddetails`
--
ALTER TABLE `carddetails`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pindetails`
--
ALTER TABLE `pindetails`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `CardId` (`CardId`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `CardId` (`CardId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carddetails`
--
ALTER TABLE `carddetails`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `pindetails`
--
ALTER TABLE `pindetails`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `carddetails` (`Id`);

--
-- Constraints for table `pindetails`
--
ALTER TABLE `pindetails`
  ADD CONSTRAINT `pindetails_ibfk_1` FOREIGN KEY (`CardId`) REFERENCES `carddetails` (`Id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`CardId`) REFERENCES `carddetails` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

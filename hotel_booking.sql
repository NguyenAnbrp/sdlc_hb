-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2025 at 09:42 AM
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
-- Database: `hotel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `banks`
--

CREATE TABLE `banks` (
  `BankID` int(11) NOT NULL,
  `BankName` varchar(100) NOT NULL,
  `BankCode` varchar(20) NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banks`
--

INSERT INTO `banks` (`BankID`, `BankName`, `BankCode`, `IsActive`, `CreatedAt`) VALUES
(1, 'Vietcombank', 'VCB', 1, '2025-08-07 02:00:46'),
(2, 'BIDV', 'BIDV', 1, '2025-08-07 02:00:46'),
(3, 'Agribank', 'AGB', 1, '2025-08-07 02:00:46'),
(4, 'Techcombank', 'TCB', 1, '2025-08-07 02:00:46'),
(5, 'MB Bank', 'MBB', 1, '2025-08-07 02:00:46'),
(6, 'ACB', 'ACB', 1, '2025-08-07 02:00:46'),
(7, 'Sacombank', 'STB', 1, '2025-08-07 02:00:46'),
(8, 'VPBank', 'VPB', 1, '2025-08-07 02:00:46');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int(11) NOT NULL,
  `RoomID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `CheckInDate` date DEFAULT NULL,
  `CheckOutDate` date DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Guests` int(11) DEFAULT 1,
  `SpecialRequest` text DEFAULT NULL,
  `GuestName` varchar(100) DEFAULT NULL,
  `GuestEmail` varchar(100) DEFAULT NULL,
  `GuestPhone` varchar(30) DEFAULT NULL,
  `GuestCountry` varchar(100) DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`BookingID`, `RoomID`, `UserID`, `CheckInDate`, `CheckOutDate`, `Status`, `CreatedAt`, `Guests`, `SpecialRequest`, `GuestName`, `GuestEmail`, `GuestPhone`, `GuestCountry`, `PaymentMethod`, `PaymentDate`) VALUES
(3, 11, 3, '2025-12-07', '2025-12-12', 'cancelled', '2025-08-07 01:51:19', 2, 'card', 'Nguyễn An', 'bugaccpress001@gmail.com', '0369962245', 'Vietnam', 'bank_transfer', '2025-08-07 09:01:39'),
(4, 12, 3, '2025-12-12', '2025-12-14', 'cancelled', '2025-08-07 06:00:12', 4, '', 'Nguyễn An', 'bugaccpress001@gmail.com', '0369962245', 'Vietnam', 'bank_transfer', '2025-08-07 13:00:30'),
(5, 13, 6, '2025-12-12', '2025-12-14', 'paid', '2025-08-07 06:19:34', 2, '', 'Nguyễn An', 'bugaccpress001@gmail.com', '0369962245', 'Vietnam', 'bank_transfer', '2025-08-07 13:19:49');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `BranchID` int(11) NOT NULL,
  `BranchName` varchar(100) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`BranchID`, `BranchName`, `Address`) VALUES
(1, 'Ha Noi', NULL),
(2, 'Ho Chi Minh City', NULL),
(3, 'Da Nang', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `paymentmethods`
--

CREATE TABLE `paymentmethods` (
  `PaymentMethodID` int(11) NOT NULL,
  `MethodName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `PaymentMethodID` int(11) DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `PaymentDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `MethodID` int(11) NOT NULL,
  `MethodName` varchar(100) NOT NULL,
  `MethodType` enum('card','bank_transfer','ewallet') NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`MethodID`, `MethodName`, `MethodType`, `IsActive`, `CreatedAt`) VALUES
(1, 'Credit Card', 'card', 1, '2025-08-07 02:00:46'),
(2, 'Debit Card', 'card', 1, '2025-08-07 02:00:46'),
(3, 'Bank Transfer', 'bank_transfer', 1, '2025-08-07 02:00:46'),
(4, 'MoMo', 'ewallet', 1, '2025-08-07 02:00:46'),
(5, 'ZaloPay', 'ewallet', 1, '2025-08-07 02:00:46'),
(6, 'VNPay', 'ewallet', 1, '2025-08-07 02:00:46'),
(7, 'ShopeePay', 'ewallet', 1, '2025-08-07 02:00:46');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`) VALUES
(1, 'admin'),
(2, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `RoomID` int(11) NOT NULL,
  `RoomTypeID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `RoomNumber` varchar(20) DEFAULT NULL,
  `Capacity` int(11) DEFAULT NULL,
  `PricePerNight` decimal(10,2) DEFAULT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`RoomID`, `RoomTypeID`, `BranchID`, `RoomNumber`, `Capacity`, `PricePerNight`, `Description`) VALUES
(6, 2, 2, 'Sunrise Nest', 3, 1200000.00, 'A cozy standard room ideal for couples or solo travelers.'),
(7, 3, 1, 'Lotus Breeze', 4, 1600000.00, 'Spacious room with city view and modern amenities.'),
(8, 3, 1, 'Family Haven', 5, 2000000.00, 'Comfortable room suitable for families, with two queen beds.'),
(9, 2, 2, 'Coconut Corner', 2, 1400000.00, 'Simple and clean room located near the elevator.'),
(10, 4, 2, 'Royal Orchid Suite', 4, 3000000.00, 'Luxury suite with bathtub, balcony, and premium service.'),
(11, 2, 3, 'Traveler’s Retreat', 2, 900000.00, 'Budget-friendly room for single travelers.'),
(12, 3, 3, 'Urban View', 4, 23240000.00, 'Modern design with workspace and mini-bar.'),
(13, 2, 1, 'A1', 2, 1.00, 'bbbbb');

-- --------------------------------------------------------

--
-- Table structure for table `roomtypes`
--

CREATE TABLE `roomtypes` (
  `RoomTypeID` int(11) NOT NULL,
  `TypeName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roomtypes`
--

INSERT INTO `roomtypes` (`RoomTypeID`, `TypeName`) VALUES
(1, 'normal'),
(2, 'Standard Room'),
(3, 'Deluxe Room'),
(4, 'Suite Room');

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

CREATE TABLE `room_images` (
  `ImageID` int(11) NOT NULL,
  `RoomID` int(11) NOT NULL,
  `ImagePath` varchar(255) NOT NULL,
  `IsMain` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_images`
--

INSERT INTO `room_images` (`ImageID`, `RoomID`, `ImagePath`, `IsMain`, `CreatedAt`) VALUES
(16, 6, 'rooms/688b01552d696_0.jpg', 1, '2025-07-31 05:38:29'),
(17, 6, 'rooms/688b01552d945_1.jpg', 0, '2025-07-31 05:38:29'),
(18, 6, 'rooms/688b01552da4f_2.jpg', 0, '2025-07-31 05:38:29'),
(19, 6, 'rooms/688b01552db02_3.jpg', 0, '2025-07-31 05:38:29'),
(20, 6, 'rooms/688b01552db6d_4.jpg', 0, '2025-07-31 05:38:29'),
(21, 6, 'rooms/688b01552dbbd_5.jpg', 0, '2025-07-31 05:38:29'),
(22, 6, 'rooms/688b01552dc45_6.jpg', 0, '2025-07-31 05:38:29'),
(23, 7, 'rooms/688b01923d2b6_0.jpg', 1, '2025-07-31 05:39:30'),
(24, 7, 'rooms/688b01923d466_1.jpg', 0, '2025-07-31 05:39:30'),
(25, 7, 'rooms/688b01923d5b1_2.jpg', 0, '2025-07-31 05:39:30'),
(26, 7, 'rooms/688b019240a46_3.jpg', 0, '2025-07-31 05:39:30'),
(27, 7, 'rooms/688b019240de8_4.jpg', 0, '2025-07-31 05:39:30'),
(28, 7, 'rooms/688b0192410fa_5.jpg', 0, '2025-07-31 05:39:30'),
(29, 7, 'rooms/688b019241688_6.jpg', 0, '2025-07-31 05:39:30'),
(30, 8, 'rooms/688b0214c781f_0.jpg', 1, '2025-07-31 05:41:40'),
(31, 8, 'rooms/688b0214c7938_1.jpg', 0, '2025-07-31 05:41:40'),
(32, 8, 'rooms/688b0214c7a5c_2.jpg', 0, '2025-07-31 05:41:40'),
(33, 8, 'rooms/688b0214c7af7_3.jpg', 0, '2025-07-31 05:41:40'),
(34, 8, 'rooms/688b0214c7b49_4.jpg', 0, '2025-07-31 05:41:40'),
(35, 8, 'rooms/688b0214c7b8f_5.jpg', 0, '2025-07-31 05:41:40'),
(36, 8, 'rooms/688b0214c7bd4_6.jpg', 0, '2025-07-31 05:41:40'),
(37, 9, 'rooms/688b0244032cb_0.jpg', 1, '2025-07-31 05:42:28'),
(38, 9, 'rooms/688b0244035e1_1.jpg', 0, '2025-07-31 05:42:28'),
(39, 9, 'rooms/688b02440374c_2.jpg', 0, '2025-07-31 05:42:28'),
(40, 9, 'rooms/688b024403b75_3.jpg', 0, '2025-07-31 05:42:28'),
(41, 9, 'rooms/688b024403d7f_4.jpg', 0, '2025-07-31 05:42:28'),
(42, 9, 'rooms/688b024403f39_5.jpg', 0, '2025-07-31 05:42:28'),
(43, 9, 'rooms/688b0244040ae_6.jpg', 0, '2025-07-31 05:42:28'),
(44, 10, 'rooms/688b02b0aef6c_0.jpg', 1, '2025-07-31 05:44:16'),
(45, 10, 'rooms/688b02b0af09b_1.jpg', 0, '2025-07-31 05:44:16'),
(46, 10, 'rooms/688b02b0af130_2.jpg', 0, '2025-07-31 05:44:16'),
(47, 10, 'rooms/688b02b0af1b0_3.jpg', 0, '2025-07-31 05:44:16'),
(48, 10, 'rooms/688b02b0af37b_4.jpg', 0, '2025-07-31 05:44:16'),
(49, 10, 'rooms/688b02b0af865_5.jpg', 0, '2025-07-31 05:44:16'),
(50, 10, 'rooms/688b02b0afbf4_6.jpg', 0, '2025-07-31 05:44:16'),
(51, 11, 'rooms/688b02ffe88a8_0.jpg', 1, '2025-07-31 05:45:35'),
(52, 11, 'rooms/688b02ffe8a6d_1.jpg', 0, '2025-07-31 05:45:35'),
(53, 11, 'rooms/688b02ffe8b26_2.jpg', 0, '2025-07-31 05:45:35'),
(54, 11, 'rooms/688b02ffe8cca_3.jpg', 0, '2025-07-31 05:45:35'),
(55, 11, 'rooms/688b02ffe8e0a_4.jpg', 0, '2025-07-31 05:45:35'),
(56, 11, 'rooms/688b02ffe8ee2_5.jpg', 0, '2025-07-31 05:45:35'),
(57, 11, 'rooms/688b02ffe8fe0_6.jpg', 0, '2025-07-31 05:45:35'),
(58, 12, 'rooms/688b033137770_0.jpg', 1, '2025-07-31 05:46:25'),
(59, 12, 'rooms/688b033137840_1.jpg', 0, '2025-07-31 05:46:25'),
(60, 12, 'rooms/688b0331378ad_2.jpg', 0, '2025-07-31 05:46:25'),
(61, 12, 'rooms/688b03313792f_3.jpg', 0, '2025-07-31 05:46:25'),
(62, 12, 'rooms/688b033137979_4.jpg', 0, '2025-07-31 05:46:25'),
(63, 12, 'rooms/688b0331379bb_5.jpg', 0, '2025-07-31 05:46:25'),
(64, 12, 'rooms/688b0331379fa_6.jpg', 0, '2025-07-31 05:46:25'),
(65, 12, 'rooms/688b033137a42_7.jpg', 0, '2025-07-31 05:46:25'),
(66, 12, 'rooms/688b033137bbe_8.jpg', 0, '2025-07-31 05:46:25'),
(67, 12, 'rooms/688b033137c36_9.jpg', 0, '2025-07-31 05:46:25'),
(68, 12, 'rooms/688b033137c82_10.jpg', 0, '2025-07-31 05:46:25'),
(69, 13, 'rooms/689444fa843d2_0.jpg', 1, '2025-08-07 06:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `userr`
--

CREATE TABLE `userr` (
  `UserID` int(11) NOT NULL,
  `RoleID` int(11) DEFAULT NULL,
  `FullName` varchar(100) DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userr`
--

INSERT INTO `userr` (`UserID`, `RoleID`, `FullName`, `Username`, `Email`, `PhoneNumber`, `Password`) VALUES
(3, 1, 'Admin', 'admin', 'admin@email.com', NULL, '$2y$10$p7R9dR6rm/V3zW3kn/LbCuj27ymcKwLKhXiNrn3X/MRgaI.yzhZru'),
(4, 2, 'Nguyễn Trung Thanh', 'adbeo', '123@gmail.com', NULL, '$2y$10$BmH4k2LRt/ofHbsk1LoaqeID.DiXEodKM/dhWlWRZvDLsV4kUOPMm'),
(5, 2, 'b', 'b111', 'b@gmai.com', NULL, '$2y$10$UXqaKM0SpFUapDeKcePlb.sXxXxyKqzdybKgHhgC91Ryoh1r4QJxa'),
(6, 2, 'ThanhAra', 'abcde', 'a@gmail.com', NULL, '$2y$10$O3Jvp0U5eWfP81irDYV0aOQR9jK4.SvUPOuJ9bDpT5Vt0AzQrlyo.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`BankID`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `RoomID` (`RoomID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`BranchID`);

--
-- Indexes for table `paymentmethods`
--
ALTER TABLE `paymentmethods`
  ADD PRIMARY KEY (`PaymentMethodID`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `PaymentMethodID` (`PaymentMethodID`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`MethodID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`RoomID`),
  ADD KEY `RoomTypeID` (`RoomTypeID`),
  ADD KEY `BranchID` (`BranchID`);

--
-- Indexes for table `roomtypes`
--
ALTER TABLE `roomtypes`
  ADD PRIMARY KEY (`RoomTypeID`);

--
-- Indexes for table `room_images`
--
ALTER TABLE `room_images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `RoomID` (`RoomID`);

--
-- Indexes for table `userr`
--
ALTER TABLE `userr`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `RoleID` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banks`
--
ALTER TABLE `banks`
  MODIFY `BankID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `BranchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paymentmethods`
--
ALTER TABLE `paymentmethods`
  MODIFY `PaymentMethodID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `MethodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `RoomID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `roomtypes`
--
ALTER TABLE `roomtypes`
  MODIFY `RoomTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_images`
--
ALTER TABLE `room_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `userr`
--
ALTER TABLE `userr`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`RoomID`) REFERENCES `rooms` (`RoomID`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `userr` (`UserID`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`PaymentMethodID`) REFERENCES `paymentmethods` (`PaymentMethodID`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`RoomTypeID`) REFERENCES `roomtypes` (`RoomTypeID`),
  ADD CONSTRAINT `rooms_ibfk_2` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`);

--
-- Constraints for table `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`RoomID`) REFERENCES `rooms` (`RoomID`) ON DELETE CASCADE;

--
-- Constraints for table `userr`
--
ALTER TABLE `userr`
  ADD CONSTRAINT `userr_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

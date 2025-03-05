-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2024 at 10:00 AM
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
-- Database: `bot`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigntasks`
--

CREATE TABLE `assigntasks` (
  `emp_id` int(11) NOT NULL,
  `emp_name` varchar(20) NOT NULL,
  `task_id` int(11) NOT NULL,
  `uname` varchar(20) NOT NULL,
  `room` varchar(20) NOT NULL,
  `request` varchar(20) NOT NULL,
  `details` varchar(100) NOT NULL,
  `status` varchar(10) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigntasks`
--

INSERT INTO `assigntasks` (`emp_id`, `emp_name`, `task_id`, `uname`, `room`, `request`, `details`, `status`, `create_at`, `completed_at`) VALUES
(21015451, 'Reorio', 6, 'Prim', '612', 'extra ', 'sabon panligo', 'complete', '2024-09-25 00:51:09', '2024-09-25 00:51:09'),
(21015005, 'Shekka', 5, 'tol', '452', 'extra amenities', 'tsinelas', 'complete', '2024-09-25 00:55:13', '2024-10-08 09:14:13'),
(21015005, 'Shekka', 4, 'audrey', '231', 'Extra ', 'extra towel 2', 'invalid', '2024-09-25 00:56:46', '2024-09-25 00:56:46'),
(21015451, 'Reorio', 3, 'Shekka', '232', 'Room service', 'may ipis ', 'complete', '2024-09-25 00:59:23', '2024-09-25 18:19:13'),
(21015005, 'Shekka', 2, 'Rhena', '231', 'Extra Amenities', '2 pillow', 'complete', '2024-09-25 01:01:31', '2024-09-25 01:01:31'),
(21015451, 'Reorio', 1, 'Reorio', '210', 'Room service', 'Linis ng cr', 'complete', '2024-09-25 01:07:16', '2024-09-25 01:07:16'),
(21015451, 'Reorio', 7, 'Reo', '123', 'Room service', 'okay', 'working', '2024-09-25 01:13:45', '2024-09-25 01:13:45'),
(21015005, 'Shekka', 8, 'luna', '431', 'Room service', 'extra pillow po', 'complete', '2024-09-25 01:15:22', '2024-09-26 06:52:45'),
(21015005, 'Shekka', 9, 'Yassy', '321', 'Room service', 'okay', 'invalid', '2024-09-25 01:17:20', '2024-09-25 03:00:41'),
(21015005, 'Shekka', 10, 'Rhena', '231', 'Room service', 'Room service', 'complete', '2024-09-25 19:48:46', '2024-09-25 19:49:33'),
(21015005, 'Shekka', 11, 'Shiku', '', 'Extra amenities', 'Hi', 'complete', '2024-10-08 09:22:57', '2024-10-09 06:22:35'),
(21015451, 'Reorio', 14, 'glean', '3232', 'Extra amenities', 'extra pillow po', 'complete', '2024-10-11 02:45:52', '2024-10-11 02:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot`
--

CREATE TABLE `chatbot` (
  `id` int(11) NOT NULL,
  `queries` varchar(300) NOT NULL,
  `replies` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot`
--

INSERT INTO `chatbot` (`id`, `queries`, `replies`) VALUES
(3, 'room service', 'Sure, what would you like for room service? Do you want to add some details regarding your request?'),
(4, 'extra amenities', 'We can provide extra amenities. Please specify which ones you need?'),
(5, 'Checkout time', 'Our standard checkout time is 11:00 AM. Would you like a late checkout?'),
(6, 'checkout', 'Are you ready to check out? Please confirm if you need assistance or have any additional requests.');

-- --------------------------------------------------------

--
-- Table structure for table `customer_messages`
--

CREATE TABLE `customer_messages` (
  `id` int(11) NOT NULL,
  `uname` varchar(11) DEFAULT NULL,
  `request` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `room` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_messages`
--

INSERT INTO `customer_messages` (`id`, `uname`, `request`, `details`, `room`, `status`, `created_at`) VALUES
(1, 'Reorio', 'Room service', 'Linis ng cr', '210', 'complete', '2024-09-25 00:48:37'),
(3, 'Shekka', 'Room service', 'may ipis ', '232', 'complete', '2024-09-25 00:49:19'),
(4, 'audrey', 'Extra ', 'extra towel 2', '231', 'invalid', '2024-09-25 00:49:48'),
(5, 'tol', 'extra amenities', 'tsinelas', '452', 'complete', '2024-09-25 00:50:14'),
(6, 'Prim', 'extra ', 'sabon panligo', '612', 'complete', '2024-09-25 00:50:33'),
(8, 'luna', 'Room service', 'extra pillow po', '431', 'complete', '2024-09-25 01:14:40'),
(11, 'Shiku', 'Extra amenities', 'Hi', '', 'complete', '2024-10-08 09:22:43'),
(14, 'glean', 'Extra amenities', 'extra pillow po', '3232', 'complete', '2024-10-09 06:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `emp_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`emp_id`, `name`, `status`) VALUES
(21015004, 'Shekka', 'inactive'),
(21015005, 'Shekka', 'active'),
(21015451, 'Reorio', 'inactive'),
(21015452, 'Rhena', 'inactive'),
(21015455, 'Mainte', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `guess`
--

CREATE TABLE `guess` (
  `uname` varchar(20) NOT NULL,
  `id` int(11) NOT NULL,
  `room` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guess`
--

INSERT INTO `guess` (`uname`, `id`, `room`) VALUES
('Reorio', 1, '210'),
('Rhena', 2, '231'),
('Shekka', 3, '232'),
('Reo', 4, '123'),
('audrey', 5, '231'),
('luna', 6, '431'),
('luna', 7, '312'),
('Audrey', 8, '513'),
('Prim', 9, '612'),
('tol', 10, '452'),
('Yassy', 11, '321'),
('shiku', 12, '232'),
('shika', 13, '323'),
('we', 14, '222'),
('glean', 15, '3232');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `available_stock` int(11) NOT NULL,
  `category` varchar(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `available_stock`, `category`, `last_updated`) VALUES
(1, 'Towel', 3, '51', '2024-10-12 04:53:18'),
(2, 'Extra Towels', 50, 'Linen', '2024-10-13 03:40:20'),
(3, 'Mini Fridge', 15, 'Electronics', '2024-10-13 03:40:20'),
(4, 'Shampoo Bottles', 200, 'Toiletries', '2024-10-13 03:40:20'),
(5, 'Room Service Menus', 30, 'Stationery', '2024-10-13 03:40:20'),
(6, 'Coffee Maker', 20, 'Electronics', '2024-10-13 03:40:20'),
(7, 'Bathrobes', 25, 'Linen', '2024-10-13 03:40:20'),
(8, 'Hair Dryers', 40, 'Electronics', '2024-10-13 03:40:20'),
(9, 'Safety Deposit Boxes', 10, 'Security', '2024-10-13 03:40:20'),
(10, 'Pool Towels', 60, 'Linen', '2024-10-13 03:40:20'),
(11, 'Remote', 5, 'Electronics', '2024-10-13 03:40:20');

-- --------------------------------------------------------

--
-- Table structure for table `login_accounts`
--

CREATE TABLE `login_accounts` (
  `emp_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `is_online` tinyint(4) DEFAULT 0,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_accounts`
--

INSERT INTO `login_accounts` (`emp_id`, `id`, `username`, `password`, `user_type`, `is_online`, `last_activity`) VALUES
(21015005, 1, '21015005', '$2y$10$f5/HKHH1zC.czjazIfFiWO7dCIpntXUpTRKlTcSt/9H0gwUmHZBUK', 'Admin', 1, '2024-10-14 00:48:53'),
(21015451, 2, '21015451', '$2y$10$FsHeETIlbz.nA4HbnIvRLenkIii7ZUARp2V0mHxiecvLcVgNDRmoS', 'Employee', 0, '2024-10-14 00:49:57'),
(21015004, 6, '21015004', '$2y$10$Gnp3nd81fWuymY4IeZp3ee0XjfG6Qg9kMjB9MVNFkATa0HoElpfI2', 'Employee', 0, '2024-10-13 00:45:32'),
(21015452, 7, '21015452', '$2y$10$BEhevgz6YBswNrQoWcNKEeZIF4/z2EhhtgnDLCRqtaUW5m4OsevkO', 'Employee', 0, '2024-10-14 00:50:20'),
(21015455, 15, '21015455', '$2y$10$OVZEWh6Cy3wXRn4HMvz7f.ljcnjEDiaQoeWdgguu3hibK1qNibcre', 'Maintenance', 0, '2024-10-14 00:48:49');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `emp_id`, `login_time`) VALUES
(1, 21015451, '2024-10-13 22:47:40'),
(2, 21015451, '2024-10-13 22:49:28'),
(3, 21015451, '2024-10-13 22:56:13'),
(4, 21015451, '2024-10-13 23:02:21'),
(5, 21015451, '2024-10-13 23:09:52'),
(6, 21015451, '2024-10-13 23:17:17'),
(7, 21015451, '2024-10-13 23:27:02'),
(8, 21015005, '2024-10-13 23:27:08'),
(9, 21015005, '2024-10-13 23:28:20'),
(10, 21015452, '2024-10-13 23:28:51'),
(11, 21015452, '2024-10-13 23:32:36'),
(12, 21015455, '2024-10-14 00:38:24'),
(13, 21015455, '2024-10-14 00:42:21'),
(14, 21015455, '2024-10-14 00:43:27'),
(15, 21015455, '2024-10-14 00:44:16'),
(16, 21015455, '2024-10-14 00:45:49'),
(17, 21015455, '2024-10-14 00:46:49'),
(18, 21015455, '2024-10-14 00:48:43'),
(19, 21015005, '2024-10-14 00:48:53'),
(20, 21015451, '2024-10-14 00:49:54'),
(21, 21015452, '2024-10-14 00:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `lost_and_found`
--

CREATE TABLE `lost_and_found` (
  `id` int(11) NOT NULL,
  `found_by` varchar(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `room` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `item` varchar(20) NOT NULL,
  `description` varchar(200) NOT NULL,
  `status` varchar(20) NOT NULL,
  `picture` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_and_found`
--

INSERT INTO `lost_and_found` (`id`, `found_by`, `type`, `room`, `date`, `item`, `description`, `status`, `picture`, `action`) VALUES
(19, 'Reorio', 'Found', '310', '2024-10-04', 'Ring', 'Gold plated', 'claimed', 'uploads/customer-sup', ''),
(20, 'Rhena Shekka', 'Lost', '200', '2024-10-04', 'Reorio', 'ip 16 pro max', 'pending', '', ''),
(22, 'Adurey', 'Lost', '321312', '2024-09-29', 'Ring', 'tangkad', 'pending', 'uploads/BPAlvl2.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `request_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `room_no` varchar(50) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `status` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `request_title`, `description`, `room_no`, `priority`, `status`, `created_at`) VALUES
(1, 'Elevator', 'ayaw gumana ng elevator', '203', 'High', 'In Progress', '2024-09-25 20:54:15'),
(2, 'Aircon', 'not cooling', '233', 'Medium', 'Pending', '2024-09-26 07:12:45'),
(3, 'Toilet', 'it\'s leaking', '201', 'Low', 'Pending', '2024-09-26 07:12:48'),
(4, 'Sink', 'no water coming out', '233', 'Medium', 'Pending', '2024-09-26 07:12:42'),
(5, 'Frontdesk computer', 'boots up but blackscreen', 'front', 'High', 'Completed', '2024-09-25 21:28:29'),
(6, 'CR', 'sira yung tiles', '230', 'Medium', 'Pending', '2024-09-26 07:14:48'),
(7, 'Door knob', 'kawalang hirap buksan', '210', 'Low', 'Pending', '2024-10-12 09:14:30'),
(8, 'Gripo', 'tumutulo e', '320', 'Medium', 'Pending', '2024-10-14 05:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `emp_id`, `message`, `link`, `created_at`) VALUES
(1, 21015451, 'You have successfully logged in.', NULL, '2024-10-14 05:49:28'),
(2, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 05:56:13'),
(3, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:02:21'),
(4, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:09:52'),
(5, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:17:17'),
(6, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:27:02'),
(7, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:27:08'),
(8, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:28:20'),
(9, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:28:51'),
(10, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:32:37'),
(11, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:38:24'),
(12, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:42:21'),
(13, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:43:27'),
(14, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:44:16'),
(15, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:45:49'),
(16, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:46:49'),
(17, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:48:43'),
(18, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 07:48:53'),
(19, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 07:49:54'),
(20, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 07:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `requested_stocks`
--

CREATE TABLE `requested_stocks` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requested_stocks`
--

INSERT INTO `requested_stocks` (`id`, `item_name`, `quantity`, `category`) VALUES
(1, 'Towel', 20, 'Amenities'),
(2, 'Towel', 20, 'Amenities'),
(3, 'Tsinelas', 10, 'Wares');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chatbot`
--
ALTER TABLE `chatbot`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_messages`
--
ALTER TABLE `customer_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`emp_id`);

--
-- Indexes for table `guess`
--
ALTER TABLE `guess`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_accounts`
--
ALTER TABLE `login_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_emp_id` (`emp_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `lost_and_found`
--
ALTER TABLE `lost_and_found`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `requested_stocks`
--
ALTER TABLE `requested_stocks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chatbot`
--
ALTER TABLE `chatbot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer_messages`
--
ALTER TABLE `customer_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `guess`
--
ALTER TABLE `guess`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `login_accounts`
--
ALTER TABLE `login_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `lost_and_found`
--
ALTER TABLE `lost_and_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `requested_stocks`
--
ALTER TABLE `requested_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login_accounts`
--
ALTER TABLE `login_accounts`
  ADD CONSTRAINT `fk_emp_id` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `login_accounts` (`emp_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `login_accounts` (`emp_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2025 at 01:15 PM
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
(21015451, '', 28, 'Guest', 'N/A', 'N/A', 'N/A', 'invalid', '2024-12-04 07:34:25', '2024-12-04 07:34:25'),
(21015451, 'Reorio', 29, 'Guest', 'N/A', 'N/A', 'N/A', 'invalid', '2024-12-04 07:45:47', NULL),
(21015451, 'Reorio', 30, 'Audrey', '231', 'ewewe', 'wewew', 'complete', '2024-12-04 07:53:02', '2024-12-04 08:58:52'),
(21015451, 'Reorio', 31, 'Shiku', '232', 'pwet', '222', 'complete', '2025-03-03 10:46:17', '2025-03-03 10:48:08'),
(21015451, 'Reorio', 32, 'Shekka', '232', 'linis pwet', '323232', 'complete', '2025-03-03 10:47:36', '2025-03-05 07:26:18');

--
-- Triggers `assigntasks`
--
DELIMITER $$
CREATE TRIGGER `after_task_assign` AFTER INSERT ON `assigntasks` FOR EACH ROW BEGIN
    INSERT INTO task_logs (task_id, emp_id, action, change_details, log_time)
    VALUES (
        NEW.task_id,
        NEW.emp_id,
        'assigned',
        CONCAT('Task assigned to ', NEW.emp_name, ' for room ', NEW.room),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_task_update` AFTER UPDATE ON `assigntasks` FOR EACH ROW BEGIN
    DECLARE change_description TEXT;

    -- Compare old and new values to generate a meaningful change log
    IF OLD.status != NEW.status THEN
        SET change_description = CONCAT('Status changed from ', OLD.status, ' to ', NEW.status);
    ELSE
        SET change_description = 'Other details updated.';
    END IF;

    -- Insert the change log
    INSERT INTO task_logs (task_id, action, change_details, log_time) 
    VALUES (NEW.task_id, 'updated', change_description, NOW());
END
$$
DELIMITER ;

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
(27, 'tol', 'rere', 'asdas', '452', 'invalid', '2024-12-04 07:25:54'),
(28, 'yassy', 'qweqw', 'qweqw', '321', 'invalid', '2024-12-04 07:33:43'),
(29, 'Reorio', 'asdasd', 'asdas', '210', 'invalid', '2024-12-04 07:38:48'),
(30, 'Audrey', 'ewewe', 'wewew', '231', 'complete', '2024-12-04 07:52:56'),
(31, 'Shiku', 'pwet', '222', '232', 'complete', '2025-03-03 10:45:57'),
(32, 'Shekka', 'linis pwet', '323232', '232', 'complete', '2025-03-03 10:47:22');

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
(21015000, 'Glean', 'inactive'),
(21015004, 'Shekka', 'inactive'),
(21015005, 'Shekka', 'active'),
(21015451, 'Reorio', 'inactive'),
(21015452, 'Rhena', 'inactive'),
(21015455, 'Mainte', 'active');

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
(1, 'Towel', 10, '51', '2024-12-01 14:50:41'),
(2, 'Extra Towels', 50, 'Linen', '2024-10-13 03:40:20'),
(3, 'Mini Fridge', 15, 'Electronics', '2024-10-13 03:40:20'),
(4, 'Shampoo Bottles', 200, 'Toiletries', '2024-10-13 03:40:20'),
(5, 'Room Service Menus', 30, 'Stationery', '2024-10-13 03:40:20'),
(6, 'Coffee Maker', 20, 'Electronics', '2024-10-13 03:40:20'),
(7, 'Bathrobes', 25, 'Linen', '2024-10-13 03:40:20'),
(8, 'Hair Dryers', 40, 'Electronics', '2024-10-13 03:40:20'),
(9, 'Safety Deposit Boxes', 10, 'Security', '2024-10-13 03:40:20'),
(10, 'Pool Towels', 60, 'Linen', '2024-10-13 03:40:20'),
(11, 'Remote', 10, 'Electronics', '2024-12-01 14:50:50'),
(12, 'Sabon', 2, 'Amenities', '2024-12-01 15:03:30'),
(13, 'Toothbrush', 2, 'Amenities', '2024-12-01 15:18:38');

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
(21015005, 1, '21015005', '$2y$10$f5/HKHH1zC.czjazIfFiWO7dCIpntXUpTRKlTcSt/9H0gwUmHZBUK', 'Admin', 1, '2025-03-06 19:25:28'),
(21015451, 2, '21015451', '$2y$10$FsHeETIlbz.nA4HbnIvRLenkIii7ZUARp2V0mHxiecvLcVgNDRmoS', 'Employee', 0, '2025-03-06 17:43:21'),
(21015004, 6, '21015004', '$2y$10$Gnp3nd81fWuymY4IeZp3ee0XjfG6Qg9kMjB9MVNFkATa0HoElpfI2', 'Employee', 0, '2024-10-13 00:45:32'),
(21015452, 7, '21015452', '$2y$10$BEhevgz6YBswNrQoWcNKEeZIF4/z2EhhtgnDLCRqtaUW5m4OsevkO', 'Employee', 0, '2024-10-14 00:50:20'),
(21015455, 15, '21015455', '$2y$10$OVZEWh6Cy3wXRn4HMvz7f.ljcnjEDiaQoeWdgguu3hibK1qNibcre', 'Maintenance', 1, '2025-03-06 19:09:22'),
(21015000, 18, '21015000', '$2y$10$gH4V4xw1jcOEFKaAZbIiIuDWIHjkV5WWBXKCkchtPcAIFbLqXiNga', 'maintenance-staff', 0, '2025-03-06 19:21:44');

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
(21, 21015452, '2024-10-14 00:50:17'),
(22, 21015005, '2024-12-01 23:26:54'),
(23, 21015005, '2024-12-01 23:33:42'),
(24, 21015005, '2024-12-01 23:56:28'),
(25, 21015005, '2024-12-02 00:06:00'),
(26, 21015005, '2024-12-03 15:42:16'),
(27, 21015005, '2024-12-04 00:22:08'),
(28, 21015005, '2024-12-04 09:04:40'),
(29, 21015005, '2024-12-04 09:55:23'),
(30, 21015005, '2024-12-04 10:33:29'),
(31, 21015005, '2024-12-04 11:11:20'),
(32, 21015455, '2024-12-04 11:14:57'),
(33, 21015455, '2024-12-04 11:16:45'),
(34, 21015451, '2024-12-04 11:17:01'),
(35, 21015455, '2024-12-04 11:24:49'),
(36, 21015005, '2024-12-04 11:25:34'),
(37, 21015005, '2024-12-04 11:25:41'),
(38, 21015455, '2024-12-04 11:25:49'),
(39, 21015455, '2024-12-04 11:31:09'),
(40, 21015005, '2024-12-04 11:48:44'),
(41, 21015455, '2024-12-04 11:49:30'),
(42, 21015005, '2024-12-04 11:53:31'),
(43, 21015451, '2024-12-04 11:53:50'),
(44, 21015451, '2024-12-04 12:04:21'),
(45, 21015451, '2024-12-04 12:05:24'),
(46, 21015451, '2024-12-04 12:06:37'),
(47, 21015455, '2024-12-04 12:07:03'),
(48, 21015451, '2024-12-04 12:07:22'),
(49, 21015451, '2024-12-04 12:07:51'),
(50, 21015451, '2024-12-04 12:08:16'),
(51, 21015451, '2024-12-04 12:09:17'),
(52, 21015451, '2024-12-04 12:10:43'),
(53, 21015451, '2024-12-04 12:10:59'),
(54, 21015451, '2024-12-04 12:11:13'),
(55, 21015455, '2024-12-04 12:11:24'),
(56, 21015451, '2024-12-04 12:11:28'),
(57, 21015451, '2024-12-04 12:12:00'),
(58, 21015451, '2024-12-04 12:12:37'),
(59, 21015451, '2024-12-04 12:12:54'),
(60, 21015005, '2024-12-04 12:13:25'),
(61, 21015455, '2024-12-04 12:13:35'),
(62, 21015455, '2024-12-04 12:14:19'),
(63, 21015455, '2024-12-04 12:14:41'),
(64, 21015451, '2024-12-04 12:30:34'),
(65, 21015451, '2024-12-04 13:13:23'),
(66, 21015005, '2024-12-04 13:14:08'),
(67, 21015005, '2024-12-04 13:56:10'),
(68, 21015451, '2024-12-04 14:02:24'),
(69, 21015455, '2024-12-04 14:06:09'),
(70, 21015455, '2024-12-04 14:07:03'),
(71, 21015451, '2024-12-04 15:09:41'),
(72, 21015005, '2024-12-04 15:28:17'),
(73, 21015005, '2024-12-05 09:30:38'),
(74, 21015005, '2024-12-14 14:34:39'),
(75, 21015005, '2024-12-20 17:36:12'),
(76, 21015451, '2025-01-18 07:24:27'),
(77, 21015005, '2025-01-18 07:24:39'),
(78, 21015005, '2025-02-11 10:49:11'),
(79, 21015455, '2025-02-14 14:32:55'),
(80, 21015005, '2025-02-15 01:41:50'),
(81, 21015455, '2025-02-15 04:37:39'),
(82, 21015455, '2025-02-15 04:40:43'),
(83, 21015005, '2025-02-15 04:41:04'),
(84, 21015455, '2025-02-17 04:33:09'),
(85, 21015455, '2025-02-17 04:38:35'),
(86, 21015005, '2025-02-19 12:51:58'),
(87, 21015005, '2025-03-01 12:25:06'),
(88, 21015451, '2025-03-01 12:45:20'),
(89, 21015451, '2025-03-01 12:55:09'),
(90, 21015451, '2025-03-01 13:09:06'),
(91, 21015005, '2025-03-01 13:09:41'),
(92, 21015451, '2025-03-01 13:11:25'),
(93, 21015451, '2025-03-01 13:11:53'),
(94, 21015451, '2025-03-01 13:15:10'),
(95, 21015451, '2025-03-01 13:16:21'),
(96, 21015451, '2025-03-01 15:50:09'),
(97, 21015005, '2025-03-01 16:25:08'),
(98, 21015005, '2025-03-01 16:29:35'),
(99, 21015005, '2025-03-03 13:47:11'),
(100, 21015451, '2025-03-03 14:20:48'),
(101, 21015455, '2025-03-03 14:45:05'),
(102, 21015451, '2025-03-03 14:45:31'),
(103, 21015005, '2025-03-03 14:51:14'),
(104, 21015005, '2025-03-03 16:58:39'),
(105, 21015451, '2025-03-05 12:56:40'),
(106, 21015451, '2025-03-05 14:26:18'),
(107, 21015005, '2025-03-05 15:26:02'),
(108, 21015455, '2025-03-05 15:57:57'),
(109, 21015451, '2025-03-05 15:58:27'),
(110, 21015455, '2025-03-05 16:32:26'),
(111, 21015451, '2025-03-05 16:32:44'),
(112, 21015455, '2025-03-05 16:32:54'),
(113, 21015455, '2025-03-05 17:53:57'),
(114, 21015455, '2025-03-05 18:40:17'),
(115, 21015455, '2025-03-06 12:18:35'),
(116, 21015005, '2025-03-06 12:32:54'),
(117, 21015000, '2025-03-06 16:56:58'),
(118, 21015000, '2025-03-06 17:04:05'),
(119, 21015005, '2025-03-06 17:08:32'),
(120, 21015000, '2025-03-06 17:09:52'),
(121, 21015000, '2025-03-06 17:11:01'),
(122, 21015000, '2025-03-06 17:12:04'),
(123, 21015451, '2025-03-06 17:13:02'),
(124, 21015455, '2025-03-06 17:13:21'),
(125, 21015000, '2025-03-06 17:13:46'),
(126, 21015000, '2025-03-06 17:14:16'),
(127, 21015455, '2025-03-06 17:26:21'),
(128, 21015451, '2025-03-06 17:43:12'),
(129, 21015000, '2025-03-06 17:43:27'),
(130, 21015455, '2025-03-06 17:44:42'),
(131, 21015005, '2025-03-06 18:00:00'),
(132, 21015000, '2025-03-06 19:05:58'),
(133, 21015005, '2025-03-06 19:07:13'),
(134, 21015000, '2025-03-06 19:07:25'),
(135, 21015000, '2025-03-06 19:09:12'),
(136, 21015455, '2025-03-06 19:09:22'),
(137, 21015000, '2025-03-06 19:18:22'),
(138, 21015005, '2025-03-06 19:25:28');

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
  `picture` varchar(500) NOT NULL,
  `action` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_and_found`
--

INSERT INTO `lost_and_found` (`id`, `found_by`, `type`, `room`, `date`, `item`, `description`, `status`, `picture`, `action`) VALUES
(30, 'Rio', 'Lost', '231', '2025-03-03', 'silpon', 'asdasd', 'pending', '../uploads/sunset-trees-silhouette-landscape-scenery-4k-wallpaper-uhdpaper.com-549@2@a.jpg', ''),
(31, 'poas', 'Found', '231', '2025-03-03', 'fdfdfd', 'sdad', 'pending', 'uploads/474346952_644518598230825_1956123584910237025_n.jpg', ''),
(32, 'Shekka', 'Found', '222', '2025-03-03', 'silpon', 'ree', 'pending', 'uploads/474506800_1329868934856603_7523598101632460796_n.jpg', ''),
(33, 'asdsds', 'Lost', '123', '2025-03-03', 'pore', '23232', 'pending', 'uploads/474113153_1737039650484925_8753929077807162961_n.jpg', ''),
(34, 'madri', 'Found', '2921', '2025-03-03', 'reore', 'ssssss', 'pending', '../uploads/1740999476_RobloxScreenShot20250115_004352991.png', '');

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
  `workon` varchar(50) NOT NULL,
  `status` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `schedule` date DEFAULT NULL,
  `emailed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `request_title`, `description`, `room_no`, `priority`, `workon`, `status`, `created_at`, `schedule`, `emailed`) VALUES
(1, 'Elevator', 'ayaw gumana ng elevator', '203', 'High', '21015455', 'Completed', '2025-03-06 06:16:22', NULL, 1),
(2, 'Aircon', 'not cooling', '233', 'Medium', '21015000', 'In Progress', '2025-03-06 11:47:13', '2025-03-07', 1),
(3, 'Toilet', 'it\'s leaking', '201', 'Low', '', 'Pending', '2024-09-26 07:12:48', NULL, 0),
(4, 'Sink', 'no water coming out', '233', 'Medium', '', 'Pending', '2024-09-26 07:12:42', NULL, 0),
(5, 'Frontdesk computer', 'boots up but blackscreen', 'front', 'High', '', 'Completed', '2025-03-06 06:10:53', NULL, 1),
(6, 'CR', 'sira yung tiles', '230', 'Medium', '21015455', 'In Progress', '2025-03-06 06:31:27', NULL, 0),
(7, 'Door knob', 'kawalang hirap buksan', '210', 'Low', '21015000', 'In Progress', '2025-03-06 09:27:10', NULL, 0),
(8, 'Gripo', 'tumutulo e', '320', 'Medium', '21015455', 'Completed', '2025-03-06 07:14:36', NULL, 0),
(9, 'aircon', 'leak', '302', 'Low', '', 'Pending', '2025-03-03 09:05:51', NULL, 0),
(10, 'pinto', 'kalawang yucvk', '222', 'Low', '', 'Pending', '2025-03-06 11:26:02', NULL, 1),
(11, 'tv', 'walang remote', '909', 'Low', '', 'Pending', '2025-03-03 11:03:20', NULL, 0),
(12, 'Faucet', 'may  tulo po', '444', 'High', '', 'Pending', '2025-03-03 11:03:58', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `item_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `emp_id`, `message`, `link`, `created_at`, `item_name`) VALUES
(1, 21015451, 'You have successfully logged in.', NULL, '2024-10-14 05:49:28', ''),
(2, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 05:56:13', ''),
(3, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:02:21', ''),
(4, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:09:52', ''),
(5, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:17:17', ''),
(6, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:27:02', ''),
(7, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:27:08', ''),
(8, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:28:20', ''),
(9, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:28:51', ''),
(10, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:32:37', ''),
(11, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:38:24', ''),
(12, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:42:21', ''),
(13, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:43:27', ''),
(14, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:44:16', ''),
(15, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:45:49', ''),
(16, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:46:49', ''),
(17, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:48:43', ''),
(18, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 07:48:53', ''),
(19, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 07:49:54', ''),
(20, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 07:50:17', ''),
(21, 21015005, 'Stock for Sabon is running low.', 'inventory.php', '2024-12-01 15:08:01', 'Sabon'),
(22, 21015005, 'Stock for Toothbrush is running low.', 'inventory.php', '2024-12-01 15:18:44', 'Toothbrush'),
(23, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:26:55', ''),
(24, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:33:42', ''),
(25, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:56:28', ''),
(26, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 16:06:00', ''),
(27, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-03 07:42:16', ''),
(28, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-03 16:22:08', ''),
(29, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-04 01:04:41', ''),
(30, 21015005, '21015005 have successfully logged in.', NULL, '2025-02-19 04:51:58', ''),
(31, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 04:25:06', ''),
(32, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 04:45:20', ''),
(33, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 04:55:09', ''),
(34, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:09:07', ''),
(35, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 05:09:41', ''),
(36, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:11:25', ''),
(37, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:11:53', ''),
(38, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:15:10', ''),
(39, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:16:21', ''),
(40, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 07:50:09', ''),
(41, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 08:25:08', ''),
(42, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 08:29:35', ''),
(43, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 05:47:11', ''),
(44, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-03 06:20:48', ''),
(45, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-03 06:45:05', ''),
(46, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-03 06:45:31', ''),
(47, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 06:51:14', ''),
(48, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 08:58:39', ''),
(49, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 04:56:40', ''),
(50, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 06:26:18', ''),
(51, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-05 07:26:02', ''),
(52, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 07:57:57', ''),
(53, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 07:58:27', ''),
(54, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 08:32:26', ''),
(55, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 08:32:44', ''),
(56, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 08:32:54', ''),
(57, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 09:53:57', ''),
(58, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 10:40:17', ''),
(59, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 04:18:35', ''),
(60, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 04:32:54', ''),
(61, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 08:56:58', ''),
(62, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:04:05', ''),
(63, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 09:08:32', ''),
(64, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:09:52', ''),
(65, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:11:01', ''),
(66, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:12:04', ''),
(67, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-06 09:13:02', ''),
(68, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:13:21', ''),
(69, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:13:46', ''),
(70, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:14:16', ''),
(71, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:26:21', ''),
(72, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-06 09:43:12', ''),
(73, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:43:27', ''),
(74, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:44:42', ''),
(75, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 10:00:00', ''),
(76, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:05:58', ''),
(77, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 11:07:13', ''),
(78, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:07:25', ''),
(79, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:09:12', ''),
(80, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 11:09:22', ''),
(81, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:18:22', ''),
(82, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 11:25:28', '');

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
(3, 'Tsinelas', 10, 'Wares'),
(4, 'Toothbrush', 30, 'Amenities'),
(5, 'Towel', 1, '51'),
(6, 'dffd', 23, '23wsd');

-- --------------------------------------------------------

--
-- Table structure for table `task_logs`
--

CREATE TABLE `task_logs` (
  `log_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `change_details` text DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_logs`
--

INSERT INTO `task_logs` (`log_id`, `task_id`, `emp_id`, `action`, `changed_by`, `change_details`, `log_time`) VALUES
(17, 28, 21015451, 'assigned', NULL, 'Task assigned to  for room N/A', '2024-12-04 07:34:25'),
(18, 29, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room N/A', '2024-12-04 07:45:47'),
(19, 30, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 231', '2024-12-04 07:53:02'),
(20, 28, NULL, 'updated', NULL, 'Status changed from working to invalid', '2024-12-04 08:19:37'),
(21, 30, NULL, 'updated', NULL, 'Status changed from working to complete', '2024-12-04 08:58:52'),
(22, 29, NULL, 'updated', NULL, 'Status changed from working to complete', '2025-02-19 04:52:09'),
(23, 29, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-01 04:25:20'),
(24, 31, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 232', '2025-03-03 10:46:17'),
(25, 32, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 232', '2025-03-03 10:47:36'),
(26, 31, NULL, 'updated', NULL, 'Status changed from working to complete', '2025-03-03 10:48:08'),
(27, 32, NULL, 'updated', NULL, 'Status changed from working to complete', '2025-03-05 07:26:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigntasks`
--
ALTER TABLE `assigntasks`
  ADD PRIMARY KEY (`task_id`);

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
-- Indexes for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `task_id` (`task_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `guess`
--
ALTER TABLE `guess`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `login_accounts`
--
ALTER TABLE `login_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `lost_and_found`
--
ALTER TABLE `lost_and_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `requested_stocks`
--
ALTER TABLE `requested_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigntasks`
--
ALTER TABLE `assigntasks`
  ADD CONSTRAINT `fk_task_id` FOREIGN KEY (`task_id`) REFERENCES `customer_messages` (`id`) ON UPDATE CASCADE;

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

--
-- Constraints for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD CONSTRAINT `task_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `assigntasks` (`task_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

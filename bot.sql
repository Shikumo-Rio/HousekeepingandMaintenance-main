-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2025 at 08:00 PM
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
-- Table structure for table `assigned_maintenance`
--

CREATE TABLE `assigned_maintenance` (
  `id` int(11) NOT NULL,
  `maintenance_request_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_maintenance`
--

INSERT INTO `assigned_maintenance` (`id`, `maintenance_request_id`, `emp_id`, `assigned_at`, `status`) VALUES
(10, 15, 21015000, '2025-03-19 10:51:45', 'Completed'),
(11, 23, 21010001, '2025-03-19 10:52:01', 'In Progress'),
(12, 22, 21015000, '2025-03-19 11:27:16', 'In Progress'),
(13, 22, 21010001, '2025-03-19 11:37:48', 'In Progress');

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
  `completed_at` timestamp NULL DEFAULT current_timestamp(),
  `priority` varchar(10) DEFAULT 'Low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigntasks`
--

INSERT INTO `assigntasks` (`emp_id`, `emp_name`, `task_id`, `uname`, `room`, `request`, `details`, `status`, `create_at`, `completed_at`, `priority`) VALUES
(21015451, 'Reorio', 27, 'tol', '452', 'rere', 'asdas', 'invalid', '2025-03-14 04:32:52', NULL, 'Low'),
(21015451, '', 28, 'Guest', 'N/A', 'N/A', 'N/A', 'invalid', '2024-12-04 07:34:25', '2024-12-04 07:34:25', 'Low'),
(21015451, 'Reorio', 29, 'Guest', 'N/A', 'N/A', 'N/A', 'invalid', '2024-12-04 07:45:47', NULL, 'Low'),
(21015451, 'Reorio', 30, 'Audrey', '231', 'ewewe', 'wewew', 'complete', '2024-12-04 07:53:02', '2024-12-04 08:58:52', 'Low'),
(21015451, 'Reorio', 31, 'Shiku', '232', 'pwet', '222', 'complete', '2025-03-03 10:46:17', '2025-03-03 10:48:08', 'Low'),
(21015451, 'Reorio', 32, 'Shekka', '232', 'linis pwet', '323232', 'complete', '2025-03-03 10:47:36', '2025-03-05 07:26:18', 'Low'),
(21015451, 'Reorio', 33, 'Reorio', '210', 'Request Amenities', 'guestName: Reorio, roomNumber: 210, towel: 1', 'complete', '2025-03-08 16:54:52', '2025-03-09 05:39:47', 'Low'),
(21015004, 'Shekka', 36, '', '210', 'Request Amenities', 'towel: 1, pillow: 1', 'complete', '2025-03-08 17:30:03', '2025-03-08 17:30:03', 'Low'),
(21010000, 'Reorio23', 37, '', '210', 'Housekeeping', 'Spill Clean Up', 'complete', '2025-03-08 17:47:19', '2025-03-09 07:42:00', 'Low'),
(21015451, 'Reorio', 38, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 1, shower_gel: 1', 'complete', '2025-03-09 07:56:33', '2025-03-09 07:57:11', 'Low'),
(21015451, 'Reorio', 39, '', '210', 'Request Amenities', 'towel: 1, pillow: 1, shampoo: 1', 'complete', '2025-03-09 08:02:58', '2025-03-09 08:04:05', 'Low'),
(21015451, 'Reorio', 40, '', '210', 'Request Amenities', 'towel: 1, pillow: 1', 'complete', '2025-03-09 08:07:23', '2025-03-09 08:07:34', 'Low'),
(21015451, 'Reorio', 41, '', '210', 'Request Amenities', 'towel: 1, pillow: 1, shampoo: 1', 'complete', '2025-03-09 08:11:18', '2025-03-09 08:11:37', 'Low'),
(21015451, 'Reorio', 42, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 2', 'complete', '2025-03-09 08:14:01', '2025-03-09 08:22:54', 'Low'),
(21015451, 'Reorio', 43, '', '210', 'Request Amenities', 'towel: 2', 'complete', '2025-03-09 08:24:46', '2025-03-09 08:25:37', 'Low'),
(21015451, 'Reorio', 44, '', '210', 'Request Amenities', 'pillow: 2', 'complete', '2025-03-09 08:33:54', '2025-03-09 08:34:10', 'Low'),
(21015451, 'Reorio', 45, '', '210', 'Request Amenities', 'towel: 2', 'complete', '2025-03-09 08:35:23', '2025-03-09 08:36:05', 'Low'),
(21015451, 'Reorio', 46, '', '210', 'Request Amenities', 'towel: 3', 'complete', '2025-03-09 08:37:23', '2025-03-09 08:38:06', 'Low'),
(21015451, 'Reorio', 47, '', '210', 'Request Amenities', 'blanket: 2', 'complete', '2025-03-09 08:42:08', '2025-03-09 08:42:20', 'Low'),
(21015451, 'Reorio', 48, '', '210', 'Request Amenities', 'towel: 2', 'complete', '2025-03-09 08:46:53', '2025-03-09 08:47:08', 'Low'),
(21015451, 'Reorio', 49, '', '210', 'Request Amenities', 'shampoo: 1, shower_gel: 1', 'invalid', '2025-03-09 08:52:53', NULL, 'Low'),
(21015451, 'Reorio', 50, '', '210', 'Request Amenities', 'towel: 1, pillow: 1', 'complete', '2025-03-09 08:55:33', '2025-03-09 08:55:56', 'Low'),
(21015451, 'Reorio', 51, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 1', 'complete', '2025-03-09 08:59:18', '2025-03-09 08:59:38', 'Low'),
(21015451, 'Reorio', 52, '', '210', 'Request Amenities', 'shampoo: 1, shower_gel: 1', 'complete', '2025-03-09 08:59:40', '2025-03-09 09:01:20', 'Low'),
(21015451, 'Reorio', 53, '', '210', 'Request Amenities', 'towel: 1, pillow: 2', 'complete', '2025-03-09 15:23:07', '2025-03-09 15:27:32', 'Low'),
(21015451, 'Reorio', 54, '', '210', 'Request Amenities', 'towel: 2, pillow: 1', 'complete', '2025-03-10 16:25:29', '2025-03-10 16:26:24', 'Low'),
(21015451, 'Reorio', 55, '', '210', 'Request Amenities', 'towel: 3', 'complete', '2025-03-14 04:39:35', '2025-03-14 04:44:14', 'Low'),
(21015451, 'Reorio', 56, '', '210', 'Request Amenities', 'pillow: 3', 'complete', '2025-03-14 04:49:45', '2025-03-14 04:53:12', 'Low'),
(21015451, 'Reorio', 57, '', '210', 'Request Amenities', 'pillow: 3', 'complete', '2025-03-14 04:55:46', '2025-03-14 04:58:11', 'Low'),
(21015451, 'Reorio', 58, '', '210', 'Request Amenities', 'water: 1, blanket: 1', 'complete', '2025-03-14 04:58:11', '2025-03-14 05:04:25', 'Low'),
(21010000, 'Reorio23', 59, '', '210', 'Housekeeping', 'Full Cleaning | palinis nman ako', 'complete', '2025-03-14 05:04:25', '2025-03-14 05:26:26', 'Low'),
(21010000, 'Reorio23', 60, '', '210', 'Housekeeping', 'Full Cleaning | palinis nman ako', 'complete', '2025-03-14 05:41:19', '2025-03-14 05:41:30', 'Low'),
(21015451, 'Reorio', 61, '', '210', 'Request Amenities', 'pillow: 2', 'complete', '2025-03-14 05:41:19', '2025-03-14 05:41:33', 'Low'),
(21015451, 'Reorio', 62, '', '210', 'Request Amenities', 'towel: 2', 'complete', '2025-03-14 05:44:49', '2025-03-14 05:45:13', 'Low'),
(21010000, 'Reorio23', 63, '', '210', 'Housekeeping', 'Bed Making', 'complete', '2025-03-14 05:44:49', '2025-03-14 05:45:17', 'Low'),
(21010000, 'Reorio23', 64, '', '210', 'Housekeeping', 'Vacuuming', 'complete', '2025-03-14 05:47:43', '2025-03-14 05:48:51', 'Low'),
(21015451, 'Reorio', 66, 'Reorio', '210', 'Request Amenities', 'towel: 4', 'invalid', '2025-03-14 09:17:07', '2025-03-14 09:17:07', 'Low'),
(21015451, 'Reorio', 67, '', '210', 'Request Amenities', 'towel: 2', 'invalid', '2025-03-14 09:18:14', '2025-03-14 09:18:14', 'Low'),
(21015451, 'Reorio', 68, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 1, shower_gel: 1', 'invalid', '2025-03-14 09:18:44', '2025-03-14 09:18:44', 'Low'),
(21015451, 'Reorio', 69, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 2', 'invalid', '2025-03-14 09:19:09', '2025-03-14 09:19:09', 'Low'),
(21015451, 'Reorio', 70, '', '210', 'Request Amenities', 'shampoo: 1, water: 1', 'invalid', '2025-03-14 09:19:54', '2025-03-14 09:19:54', 'Low'),
(21015451, 'Reorio', 71, '', '210', 'Request Amenities', 'towel: 2', 'invalid', '2025-03-14 09:23:06', '2025-03-14 09:23:06', 'Low'),
(21015451, 'Reorio', 72, '', '210', 'Request Amenities', 'pillow: 1, shampoo: 1', 'invalid', '2025-03-14 09:23:11', '2025-03-14 09:23:11', 'Low'),
(21010000, 'Reorio23', 73, '', '210', 'Housekeeping', 'Full Cleaning', 'invalid', '2025-03-14 15:59:01', '2025-03-14 15:59:01', 'Low'),
(21015451, 'Reorio', 74, '', '210', 'Request Amenities', 'shampoo: 2', 'invalid', '2025-03-14 09:23:46', '2025-03-14 09:23:46', 'Low'),
(21015451, 'Reorio', 75, '', '210', 'Request Amenities', 'towel: 3', 'invalid', '2025-03-14 09:28:43', '2025-03-14 09:28:43', 'Low'),
(21015451, 'Reorio', 76, '', '210', 'Request Amenities', 'towel: 2', 'invalid', '2025-03-14 09:29:33', '2025-03-14 09:29:33', 'Low'),
(21015451, 'Reorio', 77, '', '210', 'Request Amenities', 'towel: 2', 'invalid', '2025-03-14 09:31:46', '2025-03-14 09:31:46', 'Low'),
(21015451, 'Reorio', 78, '', '210', 'Request Amenities', 'pillow: 2', 'invalid', '2025-03-14 09:32:00', '2025-03-14 09:32:00', 'Low'),
(21015451, 'Reorio', 79, '', '210', 'Request Amenities', 'blanket: 2', 'invalid', '2025-03-14 09:34:36', '2025-03-14 09:34:36', 'Low'),
(21010000, 'Reorio23', 80, '', '210', 'Housekeeping', 'Trash Removal', 'invalid', '2025-03-14 17:38:36', '2025-03-14 17:38:36', 'Low'),
(21015451, 'Reorio', 81, '', '210', 'Request Amenities', 'towel: 1', 'invalid', '2025-03-19 04:56:41', '2025-03-19 04:56:41', 'Low'),
(21015451, 'Reorio', 82, '', '210', 'Request Amenities', 'pillow: 1', 'invalid', '2025-03-20 06:56:09', '2025-03-20 06:56:09', 'Low'),
(21015451, 'Reorio', 83, '', '210', 'Request Amenities', 'water: 1', 'invalid', '2025-03-20 07:43:35', '2025-03-20 07:43:35', 'Low'),
(21015451, 'Reorio', 84, 'reorio', '210', 'Request Amenities', 'towel: 1', 'invalid', '2025-03-20 07:43:51', '2025-03-20 07:43:51', 'Low'),
(21015451, 'Reorio', 85, 'reorio', '210', 'Request Amenities', 'water: 1', 'invalid', '2025-03-20 19:01:55', '2025-03-20 19:12:00', 'Low'),
(21015451, 'Reorio', 86, '', '210', 'Request Amenities', 'shower_gel: 1', 'invalid', '2025-03-21 11:51:33', '2025-03-21 11:53:18', 'Low'),
(21015451, 'Reorio', 90, 'asddasd', 'asdas', 'Towel Service', 'asdas', 'invalid', '2025-03-21 14:32:20', '2025-03-21 14:32:20', 'Low'),
(21015451, 'Reorio', 91, 'asdas', 'asdas', 'Room Cleaning', 'asda', 'invalid', '2025-03-21 14:34:41', '2025-03-21 14:34:41', 'Low'),
(21015451, 'Reorio', 92, 'asdas', 'adasd', 'Towel Service', 'asdas', 'invalid', '2025-03-21 14:35:19', '2025-03-21 14:35:19', 'Low');

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
-- Table structure for table `checkout_notices`
--

CREATE TABLE `checkout_notices` (
  `id` int(11) NOT NULL,
  `room_no` varchar(10) NOT NULL,
  `checkout_time` time NOT NULL,
  `request` varchar(50) NOT NULL,
  `special_request` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkout_notices`
--

INSERT INTO `checkout_notices` (`id`, `room_no`, `checkout_time`, `request`, `special_request`, `status`, `created_at`) VALUES
(1, '210', '23:30:00', '', 'clean the table', 'Pending', '2025-03-08 17:04:06'),
(2, '210', '18:20:00', 'Front Desk Notification', 'cnoine', 'Pending', '2025-03-08 17:20:13'),
(3, '210', '23:41:00', 'Maintenance', 'asdasda', 'Pending', '2025-03-08 17:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `lost_item_id` int(11) NOT NULL,
  `guest_name` varchar(100) NOT NULL,
  `room_no` varchar(50) NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `proof_ownership` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `claim_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `validated_by` varchar(100) DEFAULT NULL,
  `date_claimed` date NOT NULL,
  `date_lost` date NOT NULL,
  `proof_id` varchar(100) NOT NULL,
  `area_lost` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `lost_item_id`, `guest_name`, `room_no`, `contact_info`, `proof_ownership`, `description`, `claim_status`, `validated_by`, `date_claimed`, `date_lost`, `proof_id`, `area_lost`) VALUES
(13, 33, 'asdasd', '232', '092959959', 'uploads/proofs/1742211230_474113153_1737039650484925_8753929077807162961_n.jpg', 'asdasdasdasdas', 'pending', '21015005', '2025-03-17', '2025-03-13', '1231231231', '23232'),
(14, 30, 'asdas', 'asdas', 'asdasdas', '', 'asdasdasdas', 'pending', '21015005', '2025-03-17', '2025-03-12', 'asdasdas', 'asdas'),
(15, 34, 'Reorio', 'aaaa', 'aaa', '', 'aaaa', 'pending', '21015005', '2025-03-17', '2025-03-12', 'aaaa', 'aaa');

-- --------------------------------------------------------

--
-- Table structure for table `completed_maintenance`
--

CREATE TABLE `completed_maintenance` (
  `id` int(11) NOT NULL,
  `maintenance_request_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `completed_maintenance`
--

INSERT INTO `completed_maintenance` (`id`, `maintenance_request_id`, `emp_id`, `completed_at`, `remarks`, `photo`) VALUES
(3, 15, 21015000, '2025-03-19 11:26:27', '', 'uploads/proof_1742383587_15.jpg');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` varchar(10) DEFAULT 'Low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_messages`
--

INSERT INTO `customer_messages` (`id`, `uname`, `request`, `details`, `room`, `status`, `created_at`, `priority`) VALUES
(27, 'tol', 'rere', 'asdas', '452', 'invalid', '2024-12-04 07:25:54', 'Low'),
(28, 'yassy', 'qweqw', 'qweqw', '321', 'invalid', '2024-12-04 07:33:43', 'Low'),
(29, 'Reorio', 'asdasd', 'asdas', '210', 'invalid', '2024-12-04 07:38:48', 'Low'),
(30, 'Audrey', 'ewewe', 'wewew', '231', 'complete', '2024-12-04 07:52:56', 'Low'),
(31, 'Shiku', 'pwet', '222', '232', 'complete', '2025-03-03 10:45:57', 'Low'),
(32, 'Shekka', 'linis pwet', '323232', '232', 'complete', '2025-03-03 10:47:22', 'Low'),
(33, 'Reorio', 'Request Amenities', 'guestName: Reorio, roomNumber: 210, towel: 1', '210', 'complete', '2025-03-08 05:32:57', 'Low'),
(34, 'Reorio', 'Request Amenities', 'blanket: 1', '210', 'assigned', '2025-03-08 05:34:30', 'Low'),
(35, 'Reorio', 'Housekeeping', 'Full Cleaning | may ipis ilalim  ng kama', '210', 'assigned', '2025-03-08 05:49:27', 'Low'),
(36, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 1', '210', 'assigned', '2025-03-08 17:19:02', 'Low'),
(37, 'Reorio', 'Housekeeping', 'Spill Clean Up', '210', 'complete', '2025-03-08 17:19:09', 'Low'),
(38, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 1, shower_gel: 1', '210', 'complete', '2025-03-09 07:44:25', 'Low'),
(39, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 1, shampoo: 1', '210', 'complete', '2025-03-09 07:47:06', 'Low'),
(40, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 1', '210', 'complete', '2025-03-09 07:47:29', 'Low'),
(41, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 1, shampoo: 1', '210', 'complete', '2025-03-09 07:48:41', 'Low'),
(42, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 2', '210', 'complete', '2025-03-09 08:11:28', 'Low'),
(43, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'complete', '2025-03-09 08:13:12', 'Low'),
(44, 'Reorio', 'Request Amenities', 'pillow: 2', '210', 'complete', '2025-03-09 08:25:29', 'Low'),
(45, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'complete', '2025-03-09 08:33:52', 'Low'),
(46, 'Reorio', 'Request Amenities', 'towel: 3', '210', 'complete', '2025-03-09 08:35:09', 'Low'),
(47, 'Reorio', 'Request Amenities', 'blanket: 2', '210', 'complete', '2025-03-09 08:37:10', 'Low'),
(48, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'complete', '2025-03-09 08:41:36', 'Low'),
(49, 'Reorio', 'Request Amenities', 'shampoo: 1, shower_gel: 1', '210', 'invalid', '2025-03-09 08:46:42', 'Low'),
(50, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 1', '210', 'complete', '2025-03-09 08:52:24', 'Low'),
(51, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 1', '210', 'complete', '2025-03-09 08:54:35', 'Low'),
(52, 'Reorio', 'Request Amenities', 'shampoo: 1, shower_gel: 1', '210', 'complete', '2025-03-09 08:59:02', 'Low'),
(53, 'Reorio', 'Request Amenities', 'towel: 1, pillow: 2', '210', 'complete', '2025-03-09 15:19:46', 'Low'),
(54, 'Reorio', 'Request Amenities', 'towel: 2, pillow: 1', '210', 'complete', '2025-03-10 16:25:02', 'Low'),
(55, 'Reorio', 'Request Amenities', 'towel: 3', '210', 'complete', '2025-03-14 04:38:08', 'Low'),
(56, 'Reorio', 'Request Amenities', 'pillow: 3', '210', 'complete', '2025-03-14 04:49:37', 'Low'),
(57, 'Reorio', 'Request Amenities', 'pillow: 3', '210', 'complete', '2025-03-14 04:51:50', 'Low'),
(58, 'Reorio', 'Request Amenities', 'water: 1, blanket: 1', '210', 'complete', '2025-03-14 04:56:41', 'Low'),
(59, 'Reorio', 'Housekeeping', 'Full Cleaning | palinis nman ako', '210', 'complete', '2025-03-14 05:03:04', 'Low'),
(60, 'Reorio', 'Housekeeping', 'Full Cleaning | palinis nman ako', '210', 'complete', '2025-03-14 05:40:09', 'Low'),
(61, 'Reorio', 'Request Amenities', 'pillow: 2', '210', 'complete', '2025-03-14 05:40:15', 'Low'),
(62, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'complete', '2025-03-14 05:43:33', 'Low'),
(63, 'Reorio', 'Housekeeping', 'Bed Making', '210', 'complete', '2025-03-14 05:43:42', 'Low'),
(64, 'Reorio', 'Housekeeping', 'Vacuuming', '210', 'complete', '2025-03-14 05:47:42', 'Low'),
(65, 'Reorio', 'Housekeeping', 'Spill Clean Up', '210', 'invalid', '2025-03-14 05:49:03', 'Low'),
(66, 'Reorio', 'Request Amenities', 'towel: 4', '210', 'invalid', '2025-03-14 09:05:11', 'Low'),
(67, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'invalid', '2025-03-14 09:18:12', 'Low'),
(68, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 1, shower_gel: 1', '210', 'invalid', '2025-03-14 09:18:43', 'Low'),
(69, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 2', '210', 'invalid', '2025-03-14 09:19:07', 'Low'),
(70, 'Reorio', 'Request Amenities', 'shampoo: 1, water: 1', '210', 'invalid', '2025-03-14 09:19:50', 'Low'),
(71, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'invalid', '2025-03-14 09:23:01', 'Low'),
(72, 'Reorio', 'Request Amenities', 'pillow: 1, shampoo: 1', '210', 'invalid', '2025-03-14 09:23:10', 'Low'),
(73, 'Reorio', 'Housekeeping', 'Full Cleaning', '210', 'invalid', '2025-03-14 09:23:34', 'Low'),
(74, 'Reorio', 'Request Amenities', 'shampoo: 2', '210', 'invalid', '2025-03-14 09:23:42', 'Low'),
(75, 'Reorio', 'Request Amenities', 'towel: 3', '210', 'invalid', '2025-03-14 09:27:23', 'Low'),
(76, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'invalid', '2025-03-14 09:29:29', 'Low'),
(77, 'Reorio', 'Request Amenities', 'towel: 2', '210', 'invalid', '2025-03-14 09:31:44', 'Low'),
(78, 'Reorio', 'Request Amenities', 'pillow: 2', '210', 'invalid', '2025-03-14 09:31:48', 'Low'),
(79, 'Reorio', 'Request Amenities', 'blanket: 2', '210', 'invalid', '2025-03-14 09:34:23', 'Low'),
(80, 'Reorio', 'Housekeeping', 'Trash Removal', '210', 'invalid', '2025-03-14 17:38:34', 'Low'),
(81, 'Reorio', 'Request Amenities', 'towel: 1', '210', 'invalid', '2025-03-19 04:56:37', 'Low'),
(82, 'reorio', 'Request Amenities', 'pillow: 1', '210', 'invalid', '2025-03-20 06:56:04', 'Low'),
(83, 'reorio', 'Request Amenities', 'water: 1', '210', 'invalid', '2025-03-20 07:43:33', 'Low'),
(84, 'reorio', 'Request Amenities', 'towel: 1', '210', 'invalid', '2025-03-20 07:43:41', 'Low'),
(85, 'reorio', 'Request Amenities', 'water: 1', '210', 'invalid', '2025-03-20 19:01:50', 'Low'),
(86, 'Reorio', 'Request Amenities', 'shower_gel: 1', '210', 'invalid', '2025-03-21 11:50:55', 'Low'),
(87, 'Rerere', 'Room Cleaning', 'asdasdsa', 'ererer', 'invalid', '2025-03-21 07:16:22', 'Low'),
(88, 'Admin', 'Palinis ng public cr sa 3rd floor', 'asdada', 'cr', 'invalid', '2025-03-21 07:26:40', 'Low'),
(89, 'asdasd', 'Towel Service', 'asdasdasd', 'a2323', 'invalid', '2025-03-21 07:28:05', 'Low'),
(90, 'asddasd', 'Towel Service', 'asdas', 'asdas', 'invalid', '2025-03-21 07:32:14', 'Low'),
(91, 'asdas', 'Room Cleaning', 'asda', 'asdas', 'invalid', '2025-03-21 07:34:31', 'Low'),
(92, 'asdas', 'Towel Service', 'asdas', 'adasd', 'invalid', '2025-03-21 07:35:13', 'Low'),
(93, 'Sadsd', 'Room Cleaning', '22', '2323', 'invalid', '2025-03-21 09:22:49', 'Low');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `emp_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL,
  `role` varchar(50) DEFAULT 'housekeeper'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`emp_id`, `name`, `status`, `role`) VALUES
(21010000, 'Reorio23', 'inactive', 'linen_attendant'),
(21010001, 'Paul', 'inactive', 'Maintenance Electrician'),
(21015000, 'Glean', 'inactive', 'Maitenance Technician'),
(21015004, 'Shekka', 'inactive', 'room_attendant'),
(21015005, 'Shekka', 'active', 'housekeeper'),
(21015451, 'Reorio', 'inactive', 'room_attendant'),
(21015452, 'Rhena', 'inactive', 'linen_attendant'),
(21015455, 'Mainte', 'inactive', 'housekeeper');

-- --------------------------------------------------------

--
-- Table structure for table `employee_requests`
--

CREATE TABLE `employee_requests` (
  `request_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text NOT NULL,
  `preferred_shift` varchar(20) NOT NULL,
  `urgency_level` varchar(10) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `requested_by` varchar(50) NOT NULL,
  `request_date` datetime NOT NULL,
  `response_date` datetime DEFAULT NULL,
  `response_by` varchar(50) DEFAULT NULL,
  `response_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_requests`
--

INSERT INTO `employee_requests` (`request_id`, `role`, `quantity`, `reason`, `preferred_shift`, `urgency_level`, `status`, `requested_by`, `request_date`, `response_date`, `response_by`, `response_notes`) VALUES
(1, 'linen_attendant', 1, 'not enough', 'night', 'Low', 'Pending', '21015005', '2025-03-17 15:48:40', NULL, NULL, NULL),
(2, 'room_attendant', 2, 'need some night shifts', 'night', 'medium', 'Pending', '21015005', '2025-03-17 16:45:30', NULL, NULL, NULL),
(3, 'linen_attendant', 2, 'need another 2', 'night', 'low', 'Pending', '21015005', '2025-03-17 16:56:22', NULL, NULL, NULL),
(4, 'room_attendant', 2, 'wewewewew', 'morning', 'medium', 'Pending', '21015005', '2025-03-17 16:59:38', NULL, NULL, NULL),
(5, 'linen_attendant', 2, 'sds', 'afternoon', 'low', 'Pending', '21015005', '2025-03-17 17:00:35', NULL, NULL, NULL),
(6, 'room_attendant', 1, 'need more 1 night shift', 'night', 'low', 'Pending', '21015005', '2025-03-17 17:01:55', NULL, NULL, NULL),
(7, 'room_attendant', 22, 'ewewewew', 'morning', 'low', 'Pending', '21015005', '2025-03-17 17:17:25', NULL, NULL, NULL),
(8, 'linen_attendant', 2, 'test', 'morning', 'low', 'Pending', '21015005', '2025-03-17 17:19:00', NULL, NULL, NULL),
(9, 'room_attendant', 2, 'testing', 'morning', 'low', 'Pending', '21015005', '2025-03-17 17:20:54', NULL, NULL, NULL),
(10, 'room_attendant', 2, 'rererererererer', 'morning', 'low', 'Pending', '21015005', '2025-03-17 17:29:00', NULL, NULL, NULL),
(11, 'linen_attendant', 2, 'I need a night shift since the love of month is incoming', 'night', 'low', 'Pending', '21015005', '2025-03-20 12:54:05', NULL, NULL, NULL),
(12, 'room_attendant', 2, 'asdsd', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:19:43', NULL, NULL, NULL),
(13, 'room_attendant', 2, 'sdadas', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:22:21', NULL, NULL, NULL),
(14, 'room_attendant', 2, 'asdasd', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:24:05', NULL, NULL, NULL),
(15, 'room_attendant', 2, 'asdas', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:28:10', NULL, NULL, NULL),
(16, 'room_attendant', 2, '3232', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:31:47', NULL, NULL, NULL),
(17, 'room_attendant', 2, 'wedwew', 'morning', 'low', 'Pending', '21015005', '2025-03-22 00:37:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `foodorders`
--

CREATE TABLE `foodorders` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `food_item` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `totalprice` int(20) NOT NULL,
  `status` enum('pending','preparing','served','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foodorders`
--

INSERT INTO `foodorders` (`id`, `code`, `customer_name`, `food_item`, `quantity`, `totalprice`, `status`, `created_at`) VALUES
(1, 'FD12345678', 'John Doe', 'Burger', 2, 0, 'pending', '2025-03-17 06:34:44'),
(2, 'FD98765432', 'Jane Smith', 'Pizza', 1, 0, 'preparing', '2025-03-17 06:34:44'),
(3, 'FD45678901', 'Michael Jordan', 'Pasta', 3, 0, 'served', '2025-03-17 06:34:44'),
(4, 'FD78901234', 'Taylor Swift', 'Sushi', 4, 0, 'pending', '2025-03-17 06:34:44'),
(5, 'FD34567890', 'Lionel Messi', 'Steak', 1, 0, 'cancelled', '2025-03-17 06:34:44');

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
-- Table structure for table `guest_maintenance`
--

CREATE TABLE `guest_maintenance` (
  `id` int(11) NOT NULL,
  `uname` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `room` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_maintenance`
--

INSERT INTO `guest_maintenance` (`id`, `uname`, `title`, `description`, `room`, `status`, `created_at`) VALUES
(1, 'Reorio', 'Dookknow', 'may kalwang po', '210', 'Pending', '2025-03-08 14:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `inventory_id`, `category`, `item_name`, `sku`, `quantity`) VALUES
(21, 221, 'Housekeeping Supplies', 'domex', 'A1-1-1-071', 495),
(33, 233, 'Guest Room Supplies', 'Shampoo', 'H1-1-1-635', 198),
(34, 234, 'Guest Room Supplies', 'Soap', 'H1-1-2-138', 98),
(35, 235, 'Guest Room Supplies', 'Toothpaste', 'H1-1-3-981', 600),
(36, 236, 'Laundry Supplies', 'Fabric Conditioner', 'H2-1-1-767', 100),
(37, 237, 'Laundry Supplies', 'Laundry Detergent Powder', 'H2-1-2-599', 100),
(40, 240, 'Guest Room Supplies', 'Pillow', 'H2-1-1-375', 100),
(41, 241, 'Guest Room Supplies', 'Bed sheets', 'H2-1-2-655', 100),
(42, 242, 'Guest Room Supplies', 'Blanket', 'H2-1-3-818', 100),
(47, 247, 'Maintenance', 'Vacuum Cleaner', 'H4-1-1-298', 100),
(48, 248, 'Maintenance', 'Television', 'H4-1-2-258', 0),
(49, 249, 'Maintenance', 'Air Conditioner', 'H4-1-3-002', 0),
(50, 250, 'Maintenance', 'Washing Machine', 'H4-4-4-133', 0);

-- --------------------------------------------------------

--
-- Table structure for table `inventory2`
--

CREATE TABLE `inventory2` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `available_stock` int(11) NOT NULL,
  `category` varchar(20) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory2`
--

INSERT INTO `inventory2` (`id`, `item_name`, `available_stock`, `category`, `expiry_date`, `last_updated`) VALUES
(1, 'Towel', 10, '51', NULL, '2024-12-01 14:50:41'),
(2, 'Extra Towels', 50, 'Linen', NULL, '2024-10-13 03:40:20'),
(3, 'Mini Fridge', 15, 'Electronics', NULL, '2024-10-13 03:40:20'),
(4, 'Shampoo Bottles', 200, 'Toiletries', NULL, '2024-10-13 03:40:20'),
(5, 'Room Service Menus', 30, 'Stationery', NULL, '2024-10-13 03:40:20'),
(6, 'Coffee Maker', 20, 'Electronics', NULL, '2024-10-13 03:40:20'),
(7, 'Bathrobes', 25, 'Linen', NULL, '2024-10-13 03:40:20'),
(8, 'Hair Dryers', 40, 'Electronics', NULL, '2024-10-13 03:40:20'),
(9, 'Safety Deposit Boxes', 10, 'Security', NULL, '2024-10-13 03:40:20'),
(10, 'Pool Towels', 60, 'Linen', NULL, '2024-10-13 03:40:20'),
(11, 'Remote', 10, 'Electronics', NULL, '2024-12-01 14:50:50'),
(12, 'Sabon', 2, 'Amenities', NULL, '2024-12-01 15:03:30'),
(13, 'Toothbrush', 2, 'Amenities', NULL, '2024-12-01 15:18:38');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_usage`
--

CREATE TABLE `inventory_usage` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_usage`
--

INSERT INTO `inventory_usage` (`id`, `task_id`, `item_id`, `quantity`, `used_by`, `used_at`, `notes`) VALUES
(1, 85, 21, 2, '21015451', '2025-03-21 03:12:00', ''),
(2, 85, 33, 1, '21015451', '2025-03-21 03:12:00', ''),
(3, 85, 34, 1, '21015451', '2025-03-21 03:12:00', ''),
(4, 86, 21, 3, '21015451', '2025-03-21 19:53:18', ''),
(5, 86, 33, 1, '21015451', '2025-03-21 19:53:18', ''),
(6, 86, 34, 1, '21015451', '2025-03-21 19:53:18', '');

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
(21015005, 1, '21015005', '$2y$10$f5/HKHH1zC.czjazIfFiWO7dCIpntXUpTRKlTcSt/9H0gwUmHZBUK', 'Admin', 1, '2025-03-21 21:26:46'),
(21015451, 2, '21015451', '$2y$10$FsHeETIlbz.nA4HbnIvRLenkIii7ZUARp2V0mHxiecvLcVgNDRmoS', 'Employee', 0, '2025-03-21 19:58:56'),
(21015004, 6, '21015004', '$2y$10$Gnp3nd81fWuymY4IeZp3ee0XjfG6Qg9kMjB9MVNFkATa0HoElpfI2', 'Employee', 0, '2025-03-09 01:35:42'),
(21015452, 7, '21015452', 'reorio345', 'Employee', 0, '2025-03-09 01:40:01'),
(21015455, 15, '21015455', '$2y$10$OVZEWh6Cy3wXRn4HMvz7f.ljcnjEDiaQoeWdgguu3hibK1qNibcre', 'Maintenance', 0, '2025-03-21 20:18:57'),
(21015000, 18, '21015000', '$2y$10$gH4V4xw1jcOEFKaAZbIiIuDWIHjkV5WWBXKCkchtPcAIFbLqXiNga', 'maintenance-staff', 0, '2025-03-21 20:18:38'),
(21010000, 19, '21020000', '$2y$10$0ehO3/hxHuhHYakpG7ret.HX3mym3mrFzrP7ldNfkPrUfRAtT1cha', 'Employee', 0, '2025-03-15 01:40:16'),
(21010001, 20, '21010001', '$2y$10$hguiJk0XnuPSpt/KuNyig.IWON8GzWsc2c9uve8rwKQ3nC/swgq52', 'maintenance-staff', 0, '2025-03-19 19:04:03');

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
(138, 21015005, '2025-03-06 19:25:28'),
(139, 21015000, '2025-03-06 20:15:35'),
(140, 21015451, '2025-03-08 00:24:55'),
(141, 21015005, '2025-03-08 13:33:33'),
(142, 21015455, '2025-03-08 16:50:42'),
(143, 21015451, '2025-03-08 18:00:19'),
(144, 21015455, '2025-03-08 18:00:30'),
(145, 21015451, '2025-03-08 18:25:44'),
(146, 21015451, '2025-03-08 18:25:59'),
(147, 21015451, '2025-03-09 00:21:20'),
(148, 21015005, '2025-03-09 00:21:32'),
(149, 21015451, '2025-03-09 00:26:17'),
(150, 21015005, '2025-03-09 00:43:34'),
(151, 21015004, '2025-03-09 01:24:16'),
(152, 21015451, '2025-03-09 01:40:16'),
(153, 21015000, '2025-03-09 01:43:39'),
(154, 21010000, '2025-03-09 01:46:10'),
(155, 21010000, '2025-03-09 01:46:54'),
(156, 21015451, '2025-03-09 13:16:27'),
(157, 21015005, '2025-03-09 13:16:36'),
(158, 21015451, '2025-03-09 15:56:30'),
(159, 21015005, '2025-03-09 23:05:29'),
(160, 21015451, '2025-03-09 23:15:22'),
(161, 21015000, '2025-03-09 23:17:20'),
(162, 21015455, '2025-03-09 23:17:44'),
(163, 21015451, '2025-03-09 23:18:21'),
(164, 21015005, '2025-03-09 23:20:03'),
(165, 21015451, '2025-03-10 14:00:52'),
(166, 21015455, '2025-03-10 14:01:32'),
(167, 21015005, '2025-03-10 18:15:30'),
(168, 21015005, '2025-03-10 19:15:46'),
(169, 21015451, '2025-03-11 00:19:32'),
(170, 21015005, '2025-03-11 00:19:45'),
(171, 21015451, '2025-03-11 00:25:28'),
(172, 21015451, '2025-03-11 00:34:19'),
(173, 21015005, '2025-03-13 00:11:59'),
(174, 21015005, '2025-03-14 12:16:32'),
(175, 21015455, '2025-03-14 12:59:00'),
(176, 21010000, '2025-03-14 13:02:41'),
(177, 21015451, '2025-03-14 13:48:09'),
(178, 21010000, '2025-03-14 13:48:42'),
(179, 21015451, '2025-03-14 17:09:58'),
(180, 21015451, '2025-03-14 17:28:11'),
(181, 21015005, '2025-03-14 23:30:54'),
(182, 21015451, '2025-03-14 23:34:20'),
(183, 21010000, '2025-03-14 23:58:58'),
(184, 21015455, '2025-03-15 01:41:02'),
(185, 21015005, '2025-03-17 13:53:16'),
(186, 21015451, '2025-03-17 16:36:36'),
(187, 21015451, '2025-03-19 10:35:31'),
(188, 21015005, '2025-03-19 10:35:44'),
(189, 21015451, '2025-03-19 10:39:15'),
(190, 21015005, '2025-03-19 11:45:23'),
(191, 21015005, '2025-03-19 11:46:46'),
(192, 21015005, '2025-03-19 11:52:04'),
(193, 21015005, '2025-03-19 12:19:28'),
(194, 21015451, '2025-03-19 12:33:28'),
(195, 21015005, '2025-03-19 12:56:12'),
(196, 21015451, '2025-03-19 13:46:54'),
(197, 21015455, '2025-03-19 13:50:13'),
(198, 21015000, '2025-03-19 14:00:15'),
(199, 21015455, '2025-03-19 14:00:35'),
(200, 21015000, '2025-03-19 14:01:17'),
(201, 21015455, '2025-03-19 14:04:22'),
(202, 21015000, '2025-03-19 14:04:53'),
(203, 21015455, '2025-03-19 14:39:25'),
(204, 21015005, '2025-03-19 15:14:39'),
(205, 21015000, '2025-03-19 15:17:30'),
(206, 21015455, '2025-03-19 15:26:05'),
(207, 21015000, '2025-03-19 15:26:39'),
(208, 21015455, '2025-03-19 15:34:59'),
(209, 21015000, '2025-03-19 15:40:37'),
(210, 21015455, '2025-03-19 15:41:00'),
(211, 21015000, '2025-03-19 16:33:24'),
(212, 21015005, '2025-03-19 17:03:42'),
(213, 21015000, '2025-03-19 17:27:42'),
(214, 21015005, '2025-03-19 18:48:05'),
(215, 21015000, '2025-03-19 18:51:32'),
(216, 21015005, '2025-03-19 18:54:40'),
(217, 21010001, '2025-03-19 19:03:53'),
(218, 21015455, '2025-03-19 19:04:07'),
(219, 21015000, '2025-03-19 19:04:20'),
(220, 21015005, '2025-03-19 19:06:56'),
(221, 21015455, '2025-03-19 19:26:00'),
(222, 21015000, '2025-03-19 19:26:12'),
(223, 21015000, '2025-03-19 19:27:03'),
(224, 21015005, '2025-03-20 12:37:57'),
(225, 21015451, '2025-03-20 13:29:56'),
(226, 21015455, '2025-03-20 13:31:18'),
(227, 21015451, '2025-03-20 14:25:53'),
(228, 21015005, '2025-03-20 14:41:32'),
(229, 21015005, '2025-03-20 14:50:25'),
(230, 21015451, '2025-03-20 14:55:28'),
(231, 21015451, '2025-03-20 15:44:29'),
(232, 21015451, '2025-03-21 01:57:11'),
(233, 21015451, '2025-03-21 01:58:42'),
(234, 21015451, '2025-03-21 03:02:14'),
(235, 21015005, '2025-03-21 11:33:41'),
(236, 21015451, '2025-03-21 12:28:22'),
(237, 21015451, '2025-03-21 19:34:35'),
(238, 21015005, '2025-03-21 19:50:37'),
(239, 21015451, '2025-03-21 19:51:03'),
(240, 21015005, '2025-03-21 19:51:31'),
(241, 21015455, '2025-03-21 19:59:02'),
(242, 21015000, '2025-03-21 20:18:23'),
(243, 21015455, '2025-03-21 20:18:44'),
(244, 21015005, '2025-03-21 21:26:46');

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
(30, 'Rio', 'Lost', '231', '2025-03-03', 'silpon', 'asdasd', 'claimed', '../uploads/sunset-trees-silhouette-landscape-scenery-4k-wallpaper-uhdpaper.com-549@2@a.jpg', ''),
(31, 'poas', 'Found', '231', '2025-03-03', 'fdfdfd', 'sdad', 'claimed', 'uploads/474346952_644518598230825_1956123584910237025_n.jpg', ''),
(32, 'Shekka', 'Found', '222', '2025-03-03', 'silpon', 'ree', 'claimed', 'uploads/474506800_1329868934856603_7523598101632460796_n.jpg', ''),
(33, 'asdsds', 'Lost', '123', '2025-03-03', 'pore', '23232', 'claimed', 'uploads/474113153_1737039650484925_8753929077807162961_n.jpg', ''),
(34, 'madri', 'Found', '2921', '2025-03-03', 'reore', 'ssssss', 'claimed', '../uploads/1740999476_RobloxScreenShot20250115_004352991.png', ''),
(35, 'Rio', 'lost', '210', '2025-03-08', 'ring', 'gold', 'Pending', '67cbe69141a0d.png', 'Processing');

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
  `emp_id` int(11) NOT NULL,
  `status` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `schedule` datetime DEFAULT NULL,
  `emailed` tinyint(1) DEFAULT 0,
  `needs_assistance` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `request_title`, `description`, `room_no`, `priority`, `emp_id`, `status`, `created_at`, `schedule`, `emailed`, `needs_assistance`) VALUES
(15, 'Computer', 'not booting', 'frontdesk', 'High', 0, 'Completed', '2025-03-20 04:51:59', '2025-03-20 10:00:00', 1, 0),
(16, 'Pinto', 'not opening', '321', 'Low', 0, 'Pending', '2025-03-21 17:00:17', NULL, 1, 0),
(17, 'Aircon', 'hindi lumalamig', '332', 'Medium', 0, 'Pending', '2025-03-19 10:49:14', NULL, 0, 0),
(18, 'Faucet', 'leak', '321 322', 'Low', 0, 'Pending', '2025-03-19 10:49:24', NULL, 0, 0),
(19, 'Tiles', 'basag yung tiles sa cr', '532', 'Medium', 0, 'Pending', '2025-03-19 10:49:44', NULL, 0, 0),
(20, 'TV', 'basag yung lcd', '512', 'Low', 0, 'Pending', '2025-03-19 10:50:07', NULL, 0, 0),
(21, 'Door', 'bakbak na', '432', 'Medium', 0, 'Pending', '2025-03-19 10:50:30', NULL, 0, 0),
(22, 'POS', 'ayaw mag bukas ng cash register', 'resto', 'High', 0, 'In Progress', '2025-03-21 12:18:35', '2025-03-20 07:50:00', 0, 1),
(23, 'keys', 'stuck on doorknob', '222', 'Low', 0, 'In Progress', '2025-03-20 04:49:54', NULL, 1, 0),
(24, 'Elevator', 'Stuck', '202', 'Low', 0, 'Pending', '2025-03-21 16:47:04', NULL, 0, 0),
(25, 'RERE', 'sdasd', '223', 'Low', 0, 'Pending', '2025-03-21 16:49:30', NULL, 0, 0),
(26, 'RERE', 'sdasd', '223', 'Low', 0, 'Pending', '2025-03-21 16:50:20', NULL, 0, 0);

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
  `item_name` text NOT NULL,
  `notif_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `emp_id`, `message`, `link`, `created_at`, `item_name`, `notif_type`) VALUES
(1, 21015451, 'You have successfully logged in.', NULL, '2024-10-14 05:49:28', '', ''),
(2, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 05:56:13', '', ''),
(3, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:02:21', '', ''),
(4, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:09:52', '', ''),
(5, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:17:17', '', ''),
(6, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 06:27:02', '', ''),
(7, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:27:08', '', ''),
(8, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 06:28:20', '', ''),
(9, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:28:51', '', ''),
(10, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 06:32:37', '', ''),
(11, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:38:24', '', ''),
(12, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:42:21', '', ''),
(13, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:43:27', '', ''),
(14, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:44:16', '', ''),
(15, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:45:49', '', ''),
(16, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:46:49', '', ''),
(17, 21015455, '21015455 have successfully logged in.', NULL, '2024-10-14 07:48:43', '', ''),
(18, 21015005, '21015005 have successfully logged in.', NULL, '2024-10-14 07:48:53', '', ''),
(19, 21015451, '21015451 have successfully logged in.', NULL, '2024-10-14 07:49:54', '', ''),
(20, 21015452, '21015452 have successfully logged in.', NULL, '2024-10-14 07:50:17', '', ''),
(23, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:26:55', '', ''),
(24, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:33:42', '', ''),
(25, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 15:56:28', '', ''),
(26, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-01 16:06:00', '', ''),
(27, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-03 07:42:16', '', ''),
(28, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-03 16:22:08', '', ''),
(29, 21015005, '21015005 have successfully logged in.', NULL, '2024-12-04 01:04:41', '', ''),
(30, 21015005, '21015005 have successfully logged in.', NULL, '2025-02-19 04:51:58', '', ''),
(31, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 04:25:06', '', ''),
(32, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 04:45:20', '', ''),
(33, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 04:55:09', '', ''),
(34, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:09:07', '', ''),
(35, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 05:09:41', '', ''),
(36, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:11:25', '', ''),
(37, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:11:53', '', ''),
(38, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:15:10', '', ''),
(39, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 05:16:21', '', ''),
(40, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-01 07:50:09', '', ''),
(41, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 08:25:08', '', ''),
(42, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-01 08:29:35', '', ''),
(43, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 05:47:11', '', ''),
(44, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-03 06:20:48', '', ''),
(45, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-03 06:45:05', '', ''),
(46, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-03 06:45:31', '', ''),
(47, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 06:51:14', '', ''),
(48, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-03 08:58:39', '', ''),
(49, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 04:56:40', '', ''),
(50, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 06:26:18', '', ''),
(51, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-05 07:26:02', '', ''),
(52, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 07:57:57', '', ''),
(53, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 07:58:27', '', ''),
(54, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 08:32:26', '', ''),
(55, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-05 08:32:44', '', ''),
(56, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 08:32:54', '', ''),
(57, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 09:53:57', '', ''),
(58, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-05 10:40:17', '', ''),
(59, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 04:18:35', '', ''),
(60, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 04:32:54', '', ''),
(61, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 08:56:58', '', ''),
(62, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:04:05', '', ''),
(63, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 09:08:32', '', ''),
(64, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:09:52', '', ''),
(65, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:11:01', '', ''),
(66, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:12:04', '', ''),
(67, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-06 09:13:02', '', ''),
(68, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:13:21', '', ''),
(69, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:13:46', '', ''),
(70, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:14:16', '', ''),
(71, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:26:21', '', ''),
(72, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-06 09:43:12', '', ''),
(73, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 09:43:27', '', ''),
(74, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 09:44:42', '', ''),
(75, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 10:00:00', '', ''),
(76, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:05:58', '', ''),
(77, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 11:07:13', '', ''),
(78, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:07:25', '', ''),
(79, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:09:12', '', ''),
(80, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-06 11:09:22', '', ''),
(81, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 11:18:22', '', ''),
(82, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-06 11:25:28', '', ''),
(83, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-06 12:15:35', '', ''),
(84, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-07 16:24:55', '', ''),
(85, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-08 05:33:33', '', ''),
(86, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-08 08:50:42', '', ''),
(87, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 10:00:19', '', ''),
(88, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-08 10:00:30', '', ''),
(89, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 10:25:44', '', ''),
(90, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 10:25:59', '', ''),
(91, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 16:21:20', '', ''),
(92, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-08 16:21:32', '', ''),
(93, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 16:26:17', '', ''),
(94, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-08 16:43:34', '', ''),
(95, 21015004, '21015004 have successfully logged in.', NULL, '2025-03-08 17:24:16', '', ''),
(96, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-08 17:40:16', '', ''),
(97, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-08 17:43:39', '', ''),
(98, 21010000, '21010000 have successfully logged in.', NULL, '2025-03-08 17:46:10', '', ''),
(99, 21010000, '21010000 have successfully logged in.', NULL, '2025-03-08 17:46:54', '', ''),
(100, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-09 05:16:27', '', ''),
(101, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-09 05:16:36', '', ''),
(102, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-09 07:56:30', '', ''),
(103, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-09 15:05:29', '', ''),
(104, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-09 15:15:22', '', ''),
(105, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-09 15:17:20', '', ''),
(106, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-09 15:17:44', '', ''),
(107, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-09 15:18:21', '', ''),
(108, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-09 15:20:03', '', ''),
(109, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-10 06:00:52', '', ''),
(110, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-10 06:01:32', '', ''),
(111, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-10 10:15:30', '', ''),
(112, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-10 11:15:46', '', ''),
(113, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-10 16:19:32', '', ''),
(114, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-10 16:19:45', '', ''),
(115, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-10 16:25:28', '', ''),
(116, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-10 16:34:19', '', ''),
(117, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-12 16:11:59', '', ''),
(118, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-14 04:16:32', '', ''),
(119, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-14 04:59:00', '', ''),
(120, 21010000, '21010000 have successfully logged in.', NULL, '2025-03-14 05:02:41', '', ''),
(121, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-14 05:48:09', '', ''),
(122, 21010000, '21010000 have successfully logged in.', NULL, '2025-03-14 05:48:42', '', ''),
(123, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-14 09:09:58', '', ''),
(124, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-14 09:28:11', '', ''),
(125, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-14 15:30:54', '', ''),
(126, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-14 15:34:20', '', ''),
(127, 21010000, '21010000 have successfully logged in.', NULL, '2025-03-14 15:58:58', '', ''),
(128, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-14 17:41:03', '', ''),
(129, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-17 05:53:16', '', ''),
(130, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-17 08:36:36', '', ''),
(131, 21015005, 'Stock for Sabon is running low.', 'inventory.php', '2025-03-17 13:36:45', 'Sabon', ''),
(132, 21015005, 'Stock for Toothbrush is running low.', 'inventory.php', '2025-03-17 13:36:45', 'Toothbrush', ''),
(133, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-19 02:35:31', '', ''),
(134, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 02:35:44', '', ''),
(135, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-19 02:39:15', '', ''),
(136, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 03:45:23', '', ''),
(137, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 03:46:46', '', ''),
(138, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 03:52:04', '', ''),
(139, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 04:19:28', '', ''),
(140, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-19 04:33:28', '', ''),
(141, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 04:56:12', '', ''),
(142, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-19 05:46:54', '', ''),
(143, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 05:50:13', '', ''),
(144, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 06:00:15', '', ''),
(145, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 06:00:35', '', ''),
(146, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 06:01:17', '', ''),
(147, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 06:04:22', '', ''),
(148, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 06:04:53', '', ''),
(149, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 06:39:25', '', ''),
(150, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 07:14:39', '', ''),
(151, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 07:17:30', '', ''),
(152, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 07:26:05', '', ''),
(153, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 07:26:39', '', ''),
(154, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 07:34:59', '', ''),
(155, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 07:40:37', '', ''),
(156, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 07:41:00', '', ''),
(157, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 08:33:24', '', ''),
(158, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 09:03:42', '', ''),
(159, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 09:27:42', '', ''),
(160, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 10:48:05', '', ''),
(161, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 10:51:32', '', ''),
(162, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 10:54:40', '', ''),
(163, 21010001, '21010001 have successfully logged in.', NULL, '2025-03-19 11:03:53', '', ''),
(164, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 11:04:07', '', ''),
(165, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 11:04:20', '', ''),
(166, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-19 11:06:56', '', ''),
(167, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-19 11:26:00', '', ''),
(168, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 11:26:12', '', ''),
(169, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-19 11:27:03', '', ''),
(170, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-20 04:37:57', '', ''),
(171, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 05:29:56', '', ''),
(172, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-20 05:31:18', '', ''),
(173, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 06:25:53', '', ''),
(174, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-20 06:41:32', '', ''),
(175, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-20 06:50:25', '', ''),
(176, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 06:55:28', '', ''),
(177, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 07:44:29', '', ''),
(178, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 17:57:11', '', ''),
(179, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 17:58:42', '', ''),
(180, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-20 19:02:14', '', ''),
(181, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-21 03:33:41', '', ''),
(182, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-21 04:28:22', '', ''),
(183, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-21 11:34:35', '', ''),
(184, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-21 11:50:37', '', ''),
(185, 21015451, '21015451 have successfully logged in.', NULL, '2025-03-21 11:51:03', '', ''),
(186, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-21 11:51:31', '', ''),
(187, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-21 11:59:02', '', ''),
(188, 21015000, '21015000 have successfully logged in.', NULL, '2025-03-21 12:18:23', '', ''),
(189, 21015455, '21015455 have successfully logged in.', NULL, '2025-03-21 12:18:44', '', ''),
(190, 21015005, '21015005 have successfully logged in.', NULL, '2025-03-21 13:26:46', '', '');

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
(27, 32, NULL, 'updated', NULL, 'Status changed from working to complete', '2025-03-05 07:26:18'),
(28, 33, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-08 16:54:52'),
(31, 36, 21015004, 'assigned', NULL, 'Task assigned to Shekka for room 210', '2025-03-08 17:30:03'),
(32, 36, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-08 17:37:02'),
(33, 37, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-08 17:47:19'),
(34, 33, NULL, 'updated', NULL, 'Status changed from working to complete', '2025-03-09 05:39:47'),
(35, 37, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 07:42:00'),
(36, 38, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 07:56:33'),
(37, 38, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 07:57:11'),
(38, 39, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:02:58'),
(39, 39, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:04:05'),
(40, 40, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:07:23'),
(41, 40, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:07:34'),
(42, 41, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:11:18'),
(43, 41, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:11:37'),
(44, 42, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:14:01'),
(45, 42, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:22:54'),
(46, 43, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:24:46'),
(47, 43, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:25:37'),
(48, 44, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:33:54'),
(49, 44, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:34:10'),
(50, 45, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:35:23'),
(51, 45, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:36:05'),
(52, 46, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:37:23'),
(53, 46, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:38:06'),
(54, 47, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:42:08'),
(55, 47, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:42:20'),
(56, 48, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:46:53'),
(57, 48, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:47:08'),
(58, 49, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:52:53'),
(59, 49, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:53:24'),
(60, 50, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:55:33'),
(61, 50, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-09 08:55:56'),
(62, 51, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:59:18'),
(63, 51, NULL, 'updated', NULL, 'Status changed from Working to Completed', '2025-03-09 08:59:38'),
(64, 51, NULL, 'updated', NULL, 'Status changed from Completed to complete', '2025-03-09 08:59:38'),
(65, 52, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 08:59:40'),
(66, 52, NULL, 'updated', NULL, 'Status changed from Working to Completed', '2025-03-09 09:01:20'),
(67, 52, NULL, 'updated', NULL, 'Status changed from Completed to complete', '2025-03-09 09:01:20'),
(68, 49, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-09 15:21:33'),
(69, 53, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-09 15:23:07'),
(70, 53, NULL, 'updated', NULL, 'Status changed from Working to Completed', '2025-03-09 15:27:32'),
(71, 53, NULL, 'updated', NULL, 'Status changed from Completed to complete', '2025-03-09 15:27:32'),
(72, 54, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-10 16:25:29'),
(73, 54, NULL, 'updated', NULL, 'Status changed from Working to Completed', '2025-03-10 16:26:23'),
(74, 54, NULL, 'updated', NULL, 'Status changed from Completed to complete', '2025-03-10 16:26:24'),
(75, 27, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 452', '2025-03-14 04:32:52'),
(76, 27, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-14 04:32:57'),
(77, 55, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 04:39:35'),
(78, 55, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 04:44:14'),
(79, 56, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 04:49:45'),
(80, 56, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 04:53:12'),
(81, 57, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 04:55:46'),
(82, 57, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 04:58:11'),
(83, 58, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 04:58:11'),
(84, 58, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:04:25'),
(85, 59, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 05:04:25'),
(86, 59, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:26:26'),
(87, 60, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 05:41:19'),
(88, 61, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 05:41:19'),
(89, 60, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:41:30'),
(90, 61, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:41:33'),
(91, 62, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 05:44:49'),
(92, 63, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 05:44:49'),
(93, 62, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:45:13'),
(94, 63, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:45:17'),
(95, 64, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 05:47:43'),
(96, 64, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 05:48:51'),
(97, 66, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:17:07'),
(98, 66, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-14 09:17:28'),
(99, 67, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:18:14'),
(100, 67, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 09:18:33'),
(101, 68, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:18:44'),
(102, 69, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:19:09'),
(103, 70, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:19:54'),
(104, 68, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:22:24'),
(105, 69, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:22:28'),
(106, 70, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:22:31'),
(107, 71, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:23:06'),
(108, 72, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:23:11'),
(109, 74, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:23:46'),
(110, 71, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:27:10'),
(111, 72, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:27:14'),
(112, 74, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:27:17'),
(113, 75, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:28:43'),
(114, 76, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:29:33'),
(115, 76, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:31:32'),
(116, 75, NULL, 'updated', NULL, 'Status changed from Working to invalid', '2025-03-14 09:31:35'),
(117, 77, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:31:46'),
(118, 77, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 09:32:00'),
(119, 78, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:32:00'),
(120, 78, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 09:34:36'),
(121, 79, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-14 09:34:36'),
(122, 79, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 09:50:07'),
(123, 73, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 15:59:01'),
(124, 73, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 16:04:16'),
(125, 80, 21010000, 'assigned', NULL, 'Task assigned to Reorio23 for room 210', '2025-03-14 17:38:36'),
(126, 80, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-14 17:39:40'),
(127, 81, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-19 04:56:41'),
(128, 81, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-19 11:07:19'),
(129, 81, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 06:41:52'),
(130, 80, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 06:50:36'),
(131, 79, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 06:50:43'),
(132, 78, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 06:50:53'),
(133, 77, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 06:55:07'),
(134, 82, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-20 06:56:09'),
(135, 82, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-20 06:56:32'),
(136, 82, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 07:07:05'),
(137, 78, NULL, 'updated', NULL, 'Status changed from invalid to complete', '2025-03-20 07:07:17'),
(138, 78, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 07:42:28'),
(139, 67, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-20 07:42:33'),
(140, 83, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-20 07:43:35'),
(141, 84, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-20 07:43:51'),
(142, 84, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-20 07:45:07'),
(143, 83, NULL, 'updated', NULL, 'Status changed from Working to complete', '2025-03-20 07:47:21'),
(144, 85, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-20 19:01:55'),
(145, 85, NULL, 'updated', NULL, 'Status changed from working to completed', '2025-03-20 19:12:00'),
(146, 85, NULL, 'updated', NULL, 'Status changed from completed to complete', '2025-03-21 05:20:18'),
(147, 86, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room 210', '2025-03-21 11:51:33'),
(150, 86, NULL, 'updated', NULL, 'Status changed from Working to completed', '2025-03-21 11:53:18'),
(151, 86, NULL, 'updated', NULL, 'Status changed from completed to invalid', '2025-03-21 14:25:12'),
(152, 85, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-21 14:30:07'),
(153, 83, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-21 14:30:13'),
(154, 73, NULL, 'updated', NULL, 'Status changed from complete to invalid', '2025-03-21 14:30:28'),
(155, 90, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room asdas', '2025-03-21 14:32:20'),
(156, 90, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-21 14:32:25'),
(157, 91, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room asdas', '2025-03-21 14:34:41'),
(158, 91, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-21 14:35:05'),
(159, 92, 21015451, 'assigned', NULL, 'Task assigned to Reorio for room adasd', '2025-03-21 14:35:19'),
(160, 92, NULL, 'updated', NULL, 'Status changed from working to invalid', '2025-03-21 14:35:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_maintenance`
--
ALTER TABLE `assigned_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_assigned_request` (`maintenance_request_id`),
  ADD KEY `fk_assigned_staff` (`emp_id`);

--
-- Indexes for table `assigntasks`
--
ALTER TABLE `assigntasks`
  ADD PRIMARY KEY (`task_id`);

--
-- Indexes for table `checkout_notices`
--
ALTER TABLE `checkout_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lost_item_id` (`lost_item_id`);

--
-- Indexes for table `completed_maintenance`
--
ALTER TABLE `completed_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_completed_request` (`maintenance_request_id`);

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
-- Indexes for table `employee_requests`
--
ALTER TABLE `employee_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `foodorders`
--
ALTER TABLE `foodorders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `guess`
--
ALTER TABLE `guess`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guest_maintenance`
--
ALTER TABLE `guest_maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory2`
--
ALTER TABLE `inventory2`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_usage`
--
ALTER TABLE `inventory_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `item_id` (`item_id`);

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
-- AUTO_INCREMENT for table `assigned_maintenance`
--
ALTER TABLE `assigned_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `checkout_notices`
--
ALTER TABLE `checkout_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `completed_maintenance`
--
ALTER TABLE `completed_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_messages`
--
ALTER TABLE `customer_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `employee_requests`
--
ALTER TABLE `employee_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `foodorders`
--
ALTER TABLE `foodorders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `guess`
--
ALTER TABLE `guess`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `guest_maintenance`
--
ALTER TABLE `guest_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `inventory2`
--
ALTER TABLE `inventory2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `inventory_usage`
--
ALTER TABLE `inventory_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_accounts`
--
ALTER TABLE `login_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT for table `lost_and_found`
--
ALTER TABLE `lost_and_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `requested_stocks`
--
ALTER TABLE `requested_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_maintenance`
--
ALTER TABLE `assigned_maintenance`
  ADD CONSTRAINT `fk_assigned_request` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assigned_staff` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON DELETE CASCADE;

--
-- Constraints for table `assigntasks`
--
ALTER TABLE `assigntasks`
  ADD CONSTRAINT `fk_task_id` FOREIGN KEY (`task_id`) REFERENCES `customer_messages` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`lost_item_id`) REFERENCES `lost_and_found` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `completed_maintenance`
--
ALTER TABLE `completed_maintenance`
  ADD CONSTRAINT `fk_completed_request` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_usage`
--
ALTER TABLE `inventory_usage`
  ADD CONSTRAINT `inventory_usage_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `assigntasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_usage_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

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

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2026 at 03:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campushub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$4q9Bw.Jk4Gk5fL6M7N8O9eP0Q1R2S3T4U5V6W7X8Y9Z0aBbCcDdEe');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL DEFAULT 'Present',
  `marked_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `course_id`, `student_id`, `attendance_date`, `status`, `marked_by`, `created_at`) VALUES
(1, 5, '2026-CSE-086', '2026-08-13', 'Present', 'teacher283', '2026-08-13 10:48:25'),
(2, 5, '2026-CSE-213', '2026-08-13', 'Present', 'teacher283', '2026-08-13 10:48:25'),
(3, 5, '2026-CSE-345', '2026-08-13', 'Present', 'teacher283', '2026-08-13 10:48:25'),
(4, 5, '2026-CSE-372', '2026-08-13', 'Present', 'teacher283', '2026-08-13 10:48:25'),
(5, 5, '2026-CSE-533', '2026-08-13', 'Present', 'teacher283', '2026-08-13 10:48:25'),
(21, 9, '2026-CSE-086', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:06'),
(22, 9, '2026-CSE-213', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:06'),
(23, 9, '2026-CSE-345', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:06'),
(24, 9, '2026-CSE-372', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:06'),
(25, 9, '2026-CSE-533', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:06'),
(26, 11, '2026-CSE-086', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:16'),
(27, 11, '2026-CSE-213', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:16'),
(28, 11, '2026-CSE-345', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:16'),
(29, 11, '2026-CSE-372', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:16'),
(30, 11, '2026-CSE-533', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:16'),
(31, 7, '2026-CSE-086', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:21'),
(32, 7, '2026-CSE-213', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:21'),
(33, 7, '2026-CSE-345', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:21'),
(34, 7, '2026-CSE-372', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:21'),
(35, 7, '2026-CSE-533', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:21'),
(36, 10, '2026-CSE-086', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:26'),
(37, 10, '2026-CSE-213', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:26'),
(38, 10, '2026-CSE-345', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:26'),
(39, 10, '2026-CSE-372', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:26'),
(40, 10, '2026-CSE-533', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:26'),
(41, 5, '2026-CSE-086', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:30'),
(42, 5, '2026-CSE-213', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:30'),
(43, 5, '2026-CSE-345', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:30'),
(44, 5, '2026-CSE-372', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:30'),
(45, 5, '2026-CSE-533', '2026-08-10', 'Present', 'teacher283', '2026-08-13 10:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `credits` decimal(3,2) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `assigned_teacher` varchar(100) DEFAULT NULL,
  `batch` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `credits`, `department_id`, `assigned_teacher`, `batch`) VALUES
(5, 'CSE115', 'Algorithm', 2.50, NULL, 'teacher283', '61'),
(6, 'CSE333', 'Discrete Math', 2.00, NULL, 'teacher283', '61'),
(7, 'CSE404', 'Data Structure', 3.00, NULL, 'teacher283', '61'),
(8, 'CSE511', 'Art Of Living', 3.00, NULL, 'teacher283', '61'),
(9, 'CSE888', 'BNS', 3.00, NULL, 'teacher283', '61'),
(10, 'CSE301', 'Biology', 3.00, NULL, 'teacher101', '61'),
(11, 'CSE533', 'OOP', 3.00, NULL, 'teacher283', '61');

-- --------------------------------------------------------

--
-- Table structure for table `course_enrollments`
--

CREATE TABLE `course_enrollments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_username` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('note','assignment','notice') DEFAULT 'note',
  `file_path` varchar(255) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `batch` varchar(20) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_materials`
--

INSERT INTO `course_materials` (`id`, `course_id`, `teacher_username`, `title`, `type`, `file_path`, `department`, `batch`, `uploaded_at`) VALUES
(1, 5, 'teacher283', 'write an assignment', 'notice', 'uploads/materials/1786569488_DIU_Cover_Page (3).jpg', NULL, NULL, '2026-08-12 21:18:08'),
(2, 8, 'teacher283', 'diu cover page', 'notice', 'uploads/materials/1786599156_DIU_Cover_Page.jpg', 'CSE', '61', '2026-08-13 05:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(1, 'Computer Science and Engineering'),
(3, 'Electrical and Electronic Engineering'),
(2, 'Software Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `fees`
--
DELIMITER $$
CREATE TRIGGER `update_payment_date` BEFORE UPDATE ON `fees` FOR EACH ROW BEGIN
    IF NEW.status = 'Paid' AND OLD.status = 'Unpaid' THEN
        SET NEW.payment_date = CURDATE();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `marks` int(11) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `posted_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `course_code`, `course_name`, `marks`, `grade`, `posted_by`, `created_at`) VALUES
(2, '2026-CSE-213', 'CSE321', 'Database Management System', 87, 'A+', 'teacher1', '2026-08-02 17:26:48'),
(3, '2026-CSE-372', 'CSE321', 'Database Management System', 70, 'A-', 'teacher283', '2026-08-13 05:39:01');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `posted_by` varchar(100) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `description`, `file_name`, `posted_by`, `created_at`) VALUES
(1, 'Varsity holiday', 'when the varsity being off', '1785616733_8045.png', 'Teacher', '2026-08-01 20:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `gpa` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_bookings`
--

CREATE TABLE `room_bookings` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('Booked','Cancelled') DEFAULT 'Booked',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_bookings`
--

INSERT INTO `room_bookings` (`id`, `room_number`, `teacher_id`, `course_id`, `booking_date`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(2, 'Room 102', 'teacher283', 5, '2006-08-13', '10:30:00', '12:00:00', 'Booked', '2026-08-13 12:54:54'),
(3, 'Room 103', 'teacher283', 6, '2026-08-14', '10:30:00', '12:00:00', 'Booked', '2026-08-13 12:55:36'),
(4, 'Room 102', 'teacher283', 5, '2026-08-14', '13:00:00', '14:30:00', 'Booked', '2026-08-13 13:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `routines`
--

CREATE TABLE `routines` (
  `id` int(11) NOT NULL,
  `day` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `room_no` varchar(20) NOT NULL,
  `teacher_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routines`
--

INSERT INTO `routines` (`id`, `day`, `time_slot`, `course_code`, `course_name`, `room_no`, `teacher_name`) VALUES
(1, 'Saturday', '09.00AM-10.30AM', 'CSE321', 'Database Management System', 'G-004', 'Sayed Eftesum');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `batch` varchar(20) DEFAULT '60'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `department`, `created_at`, `batch`) VALUES
(2, '2026-CSE-213', 'Parvez Ahmod Ashik', 'ashik242-15-213@diu.edu.bd', 'CSE', '2026-08-02 17:22:47', '61'),
(3, '2026-CSE-372', 'Junayed Ahmed', 'junayed242-15-372@diu.edu.bd', 'CSE', '2026-08-12 20:07:33', '61'),
(4, '2026-CSE-533', 'Md.Rakibul Alam Ananda', 'anondo242-15-533@diu.edu.bd', 'CSE', '2026-08-12 20:09:30', '61'),
(5, '2026-CSE-086', 'Nazmul Hossain', 'nazmul086@diu.edu.bd', 'CSE', '2026-08-12 20:11:14', '61'),
(6, '2026-CSE-345', 'Minhaj Ul Hasan', 'minhaz345@diu.edu.bd', 'CSE', '2026-08-12 20:14:15', '61');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_result_view`
-- (See below for the actual view)
--
CREATE TABLE `student_result_view` (
`student_id` varchar(50)
,`student_name` varchar(100)
,`course_code` varchar(20)
,`course_name` varchar(100)
,`gpa` decimal(3,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(50) DEFAULT 'CSE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `name`, `email`, `department`, `created_at`) VALUES
(1, 'teacher283', 'Farhan Tanvi Anik', '252-15-283@diu.edu.bd', 'CSE', '2026-08-12 20:30:08'),
(2, 'teacher101', 'Momit Roy', 'momit101@diu.edu.bd', 'CSE', '2026-08-12 20:38:39'),
(3, 'teacher990', 'Tareq Rahman', 'tareq990@diu.edu.bd', 'CSE', '2026-08-12 20:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_campus_logs`
--

CREATE TABLE `teacher_campus_logs` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `log_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_campus_logs`
--

INSERT INTO `teacher_campus_logs` (`id`, `teacher_id`, `log_date`, `check_in_time`, `check_out_time`, `created_at`) VALUES
(1, 'teacher283', '2026-08-13', '14:29:39', '14:30:09', '2026-08-13 12:29:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `role`) VALUES
(6, 'admin', 'admin@campushub.com', '0192023a7bbd73250516f069df18b500', '2026-08-02 17:21:08', 'admin'),
(7, '2026-CSE-213', 'ashik242-15-213@diu.edu.bd', 'e10adc3949ba59abbe56e057f20f883e', '2026-08-02 17:22:47', 'student'),
(9, '2026-CSE-372', 'junayed242-15-372@diu.edu.bd', 'edbc420e68a46e08f04a8ab35e7417b6', '2026-08-12 20:07:33', 'student'),
(10, '2026-CSE-533', 'anondo242-15-533@diu.edu.bd', 'cdf0e3a6a8b4c9f3fa79a4de175b5b91', '2026-08-12 20:09:30', 'student'),
(11, '2026-CSE-086', 'nazmul086@diu.edu.bd', '4bfd136a9cbba9b5a3b43e90e325c523', '2026-08-12 20:11:14', 'student'),
(12, '2026-CSE-345', 'minhaz345@diu.edu.bd', '9eda24e8ff87de4584f6c497e61ce7df', '2026-08-12 20:14:15', 'student'),
(14, 'teacher283', '252-15-283@diu.edu.bd', '35f5ff31d5fa6f9e7575d426e0c1fb90', '2026-08-12 20:30:08', 'teacher'),
(15, 'teacher101', 'momit101@diu.edu.bd', 'd02a9136ee694657ff6dee75d6df27d9', '2026-08-12 20:38:39', 'teacher'),
(16, 'teacher990', 'tareq990@diu.edu.bd', 'e077e5de931937d086769641005af473', '2026-08-12 20:40:32', 'teacher');

-- --------------------------------------------------------

--
-- Structure for view `student_result_view`
--
DROP TABLE IF EXISTS `student_result_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_result_view`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`name` AS `student_name`, `c`.`course_code` AS `course_code`, `c`.`course_name` AS `course_name`, `r`.`gpa` AS `gpa` FROM ((`results` `r` join `students` `s` on(`r`.`student_id` = `s`.`id`)) join `courses` `c` on(`r`.`course_id` = `c`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`course_id`,`student_id`,`attendance_date`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routines`
--
ALTER TABLE `routines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_campus_logs`
--
ALTER TABLE `teacher_campus_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_log` (`teacher_id`,`log_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_bookings`
--
ALTER TABLE `room_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `routines`
--
ALTER TABLE `routines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_campus_logs`
--
ALTER TABLE `teacher_campus_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_enrollments`
--
ALTER TABLE `course_enrollments`
  ADD CONSTRAINT `course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

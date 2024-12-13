-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 13, 2024 at 10:46 AM
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
-- Database: `uph_college`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `user_id`, `action`, `title`, `description`, `created_at`) VALUES
(117, '987d46e7ceb3683bc0f82dcc889814f8', 'users', 'update a user role', 'Updated the role of user to: student', '2024-12-12 20:15:56'),
(118, '987d46e7ceb3683bc0f82dcc889814f8', 'user status', 'updated a user status', 'Updated the user status: ', '2024-12-12 20:16:02'),
(119, 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'applications', 'updated an application', 'Updated the application of: 09f9856ccdc8695b804cca4b60435302 to approved', '2024-12-12 20:19:54'),
(120, 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'applications', 'updated an application', 'Updated the application of: 95245b01c076aa84665e3ea6da8c17df to approved', '2024-12-12 20:19:59'),
(121, 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'applications', 'updated an application', 'Updated the application of: 64cd215ecb6391db67c658391498cdc6 to approved', '2024-12-12 20:20:11'),
(122, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 09f9856ccdc8695b804cca4b60435302 to accepted', '2024-12-12 20:22:35'),
(123, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 64cd215ecb6391db67c658391498cdc6 to accepted', '2024-12-12 20:22:51'),
(124, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 95245b01c076aa84665e3ea6da8c17df to accepted', '2024-12-12 20:23:48'),
(125, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 09f9856ccdc8695b804cca4b60435302 to accepted', '2024-12-12 20:23:51'),
(126, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 64cd215ecb6391db67c658391498cdc6 to accepted', '2024-12-12 20:23:54'),
(127, '987d46e7ceb3683bc0f82dcc889814f8', 'applications', 'updated an application', 'Updated the application of: 64cd215ecb6391db67c658391498cdc6 to accepted', '2024-12-13 11:55:36'),
(128, 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'applications', 'updated an application', 'Updated the application of: 0171e65eaa3f7d46827fda4963f6c5a5 to approved', '2024-12-13 14:27:29'),
(129, '987d46e7ceb3683bc0f82dcc889814f8', 'users', 'update a user role', 'Updated the role of user to: student', '2024-12-13 14:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `application_id` varchar(255) NOT NULL,
  `scholarship_type_id` varchar(255) DEFAULT NULL,
  `type_id` varchar(255) DEFAULT NULL,
  `user_id` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `application_id`, `scholarship_type_id`, `type_id`, `user_id`, `status`, `created_at`) VALUES
(133, '24507d4ecb453e0b77dcd429bd39d9e4', 'bb32127037342ec9c79ac4a49ac58b99', '87da19adee954b7446906e00fbe63c27', '5d30b6019301ab98bf6f320d9c17cd56', 'pending', '2024-12-13 12:29:30'),
(134, '59f8039bf6910888bced2715f32a3629', 'bb32127037342ec9c79ac4a49ac58b99', 'bdcd6cb5a01b1def31be848c7bd35da6', '5d30b6019301ab98bf6f320d9c17cd56', 'pending', '2024-12-13 12:31:07'),
(135, 'e0955b1b44a7729b437ab7a1dc104136', 'bb32127037342ec9c79ac4a49ac58b99', 'c382c97c5468a3caf9a7eebf6885fad1', '46ce627b6eea6c240b45c6329f1fd688', 'pending', '2024-12-13 12:33:11'),
(136, 'c36bcbd5ec5c7378b6f13c2fc93571f8', 'bb32127037342ec9c79ac4a49ac58b99', '0b0db50551e8e80b33748c318166553f', '46ce627b6eea6c240b45c6329f1fd688', 'pending', '2024-12-13 12:34:56'),
(137, '0171e65eaa3f7d46827fda4963f6c5a5', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '1a083b7909b4bf2a2d60af17a5fcadf1', '46ce627b6eea6c240b45c6329f1fd688', 'approved', '2024-12-13 13:54:23'),
(138, '63265769646d1ea70bec17d0afd492b2', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '703ec62941725294ef4074b5befac6ca', '5d30b6019301ab98bf6f320d9c17cd56', 'pending', '2024-12-13 14:08:08'),
(139, 'e1ad828d34301d20667469f8eb406262', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '703ec62941725294ef4074b5befac6ca', '46ce627b6eea6c240b45c6329f1fd688', 'pending', '2024-12-13 14:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_id` varchar(255) NOT NULL,
  `department_code` varchar(255) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_id`, `department_code`, `department_name`, `created_at`) VALUES
(1, '13d95ee87ec67bda', 'CCS', 'College of Computer Studies', '2024-12-06 23:03:29'),
(2, 'b2288fe3996cb803', 'CBAA', 'College of Business Administration and Accountancy', '2024-12-07 10:44:34'),
(3, '654d9a068da96465', 'CORT', 'College of Radiologic Technology', '2024-12-08 15:42:42'),
(4, '4ec86083ba09d4f9', 'COPT', 'College of Physical Therapy', '2024-12-08 15:42:53'),
(5, 'c9290d8add222ea7', 'COPH', 'College of Pharmacy', '2024-12-08 15:43:03'),
(6, '87151409eac706c5', 'CON', 'College of Nursing', '2024-12-08 15:43:13'),
(7, 'c0709eeec121f1c9', 'COMT', 'College of Medical Technology', '2024-12-08 15:43:23'),
(8, '699385ca88a7aaef', 'CITHM', 'College of International Tourism and Hospitality Management', '2024-12-08 15:43:34'),
(9, 'eccb83035e9cbf36', 'COE', 'College of Engineering', '2024-12-08 15:43:43'),
(10, '70abda40179bddc4', 'COC', 'College of Criminology', '2024-12-08 15:43:53'),
(11, 'b785057f9d642bb4', 'COA', 'College of Architecture', '2024-12-08 15:44:05'),
(12, '4a8187ac096a289e', 'CASED', '	College of Arts, Sciences, and Education', '2024-12-08 15:44:15');

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `application_id` varchar(255) DEFAULT NULL,
  `scholarship_type_id` varchar(255) DEFAULT NULL,
  `type_id` varchar(255) DEFAULT NULL,
  `referral_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `suffix` varchar(10) NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `year_level` varchar(10) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `program` varchar(255) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `subjects` varchar(255) NOT NULL,
  `academic_weighted_average` varchar(10) NOT NULL,
  `honors_received` varchar(255) NOT NULL,
  `general_weighted_average` varchar(10) NOT NULL,
  `date_of_birth` datetime NOT NULL,
  `place_of_birth` varchar(255) NOT NULL,
  `citizenship` varchar(255) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `name_of_father` varchar(255) NOT NULL,
  `name_of_mother` varchar(255) NOT NULL,
  `occupation_of_father` varchar(255) NOT NULL,
  `occupation_of_mother` varchar(255) NOT NULL,
  `special_skills` varchar(255) NOT NULL,
  `copy_of_registration` varchar(255) NOT NULL,
  `barangay_clearance` varchar(255) NOT NULL,
  `certification_of_perfect_discipline` varchar(255) NOT NULL,
  `copy_of_grades_last_semester` varchar(255) NOT NULL,
  `medical_clearance` varchar(255) NOT NULL,
  `proof_of_family_income` varchar(255) NOT NULL,
  `resume` varchar(255) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `user_id`, `application_id`, `scholarship_type_id`, `type_id`, `referral_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `academic_year`, `year_level`, `semester`, `program`, `email_address`, `contact_number`, `subjects`, `academic_weighted_average`, `honors_received`, `general_weighted_average`, `date_of_birth`, `place_of_birth`, `citizenship`, `gender`, `name_of_father`, `name_of_mother`, `occupation_of_father`, `occupation_of_mother`, `special_skills`, `copy_of_registration`, `barangay_clearance`, `certification_of_perfect_discipline`, `copy_of_grades_last_semester`, `medical_clearance`, `proof_of_family_income`, `resume`, `attachment`, `created_at`) VALUES
(60, '5d30b6019301ab98bf6f320d9c17cd56', '24507d4ecb453e0b77dcd429bd39d9e4', 'bb32127037342ec9c79ac4a49ac58b99', '87da19adee954b7446906e00fbe63c27', '02e45c09c13d8a7095670b06ab80ff4d', '', '', '', '', '', '', '', 'Bachelor of Science in Computer Science', '', '', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'uploads/application-87da19adee954b7446906e00fbe63c27-1734064170.pdf', '2024-12-13 12:29:30'),
(61, '5d30b6019301ab98bf6f320d9c17cd56', '59f8039bf6910888bced2715f32a3629', 'bb32127037342ec9c79ac4a49ac58b99', 'bdcd6cb5a01b1def31be848c7bd35da6', '02e45c09c13d8a7095670b06ab80ff4d', '', '', '', '', '', '', '', 'Bachelor of Science in Computer Science', '', '', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'uploads/application-bdcd6cb5a01b1def31be848c7bd35da6-1734064267.pdf', '2024-12-13 12:31:07'),
(62, '46ce627b6eea6c240b45c6329f1fd688', 'e0955b1b44a7729b437ab7a1dc104136', 'bb32127037342ec9c79ac4a49ac58b99', 'c382c97c5468a3caf9a7eebf6885fad1', '02e45c09c13d8a7095670b06ab80ff4d', '', '', '', '', '', '', '', 'Bachelor of Science in Computer Science', '', '', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'uploads/application-c382c97c5468a3caf9a7eebf6885fad1-1734064391.pdf', '2024-12-13 12:33:11'),
(63, '46ce627b6eea6c240b45c6329f1fd688', 'c36bcbd5ec5c7378b6f13c2fc93571f8', 'bb32127037342ec9c79ac4a49ac58b99', '0b0db50551e8e80b33748c318166553f', '02e45c09c13d8a7095670b06ab80ff4d', '', '', '', '', '', '', '', 'Bachelor of Science in Computer Science', '', '', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'uploads/application-0b0db50551e8e80b33748c318166553f-1734064496.pdf', '2024-12-13 12:34:56'),
(64, '46ce627b6eea6c240b45c6329f1fd688', '0171e65eaa3f7d46827fda4963f6c5a5', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '1a083b7909b4bf2a2d60af17a5fcadf1', 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'CheapDevs', 'LB', 'PH', '', '2024', '', '1', 'Bachelor of Science in Computer Science', 'cheapdevsph@gmail.com', '09631877961', '', '', 'best in everything', '1', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '2024-12-13 13:54:23'),
(65, '5d30b6019301ab98bf6f320d9c17cd56', '63265769646d1ea70bec17d0afd492b2', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '703ec62941725294ef4074b5befac6ca', 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'Alfie', '', 'Figuracion', 'n/A', '2024', '1', '1', 'Bachelor of Science in Computer Science', 'alfiefiguracion1108@gmail.com', '09631879961', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '2024-12-13 00:00:00'),
(66, '46ce627b6eea6c240b45c6329f1fd688', 'e1ad828d34301d20667469f8eb406262', '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '703ec62941725294ef4074b5befac6ca', 'ca0fc1d6228299bf3e4092c9a4bc6fea', 'CheapDevs', 'LB', 'PH', 'n/A', '2024', '1', '1', 'Bachelor of Science in Computer Science', 'cheapdevsph@gmail.com', '09631879961', '', '', '', '', '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '2024-12-13 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `department_id` varchar(255) NOT NULL,
  `program_id` varchar(255) NOT NULL,
  `program_code` varchar(255) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `department_id`, `program_id`, `program_code`, `program_name`, `created_at`) VALUES
(1, '13d95ee87ec67bda', '72a1bae0270aee02', 'BSCS', 'Bachelor of Science in Computer Science', '2024-12-06 23:25:21'),
(2, 'b2288fe3996cb803', '56e918a231b7bab7', 'BSM', 'Bachelor of Science in Marketing', '2024-12-07 11:23:51'),
(3, '13d95ee87ec67bda', 'b5fc72e76d73bbca', 'BSIT', 'Bachelor of Science in Information Technology', '2024-12-07 11:27:52'),
(4, '654d9a068da96465', 'f0ebe20d2fb37ffc', 'BSRT', 'Bachelor of Science in Radiologic Technology', '2024-12-08 15:48:56'),
(5, '4ec86083ba09d4f9', 'c622d831373af1a6', 'BSPT', 'Bachelor of Science in Physical Therapy', '2024-12-08 15:49:11'),
(6, 'c9290d8add222ea7', '4cf0e64998f14062', 'BSP', 'Bachelor of Science in Pharmacy', '2024-12-08 15:49:28'),
(7, '87151409eac706c5', '36c65958bdd3e97c', 'BSN', 'Bachelor of Science in Nursing', '2024-12-08 15:49:40'),
(8, 'c0709eeec121f1c9', 'db29bbe8a7a73381', 'BSMLT', 'Bachelor of Science in Medical Technology', '2024-12-08 15:49:53'),
(9, '699385ca88a7aaef', '8858b3443b9c9814', 'BSHM', 'Bachelor of Science in Hospitality Management', '2024-12-08 15:50:07'),
(10, '699385ca88a7aaef', '1e13e5f72d43fcdc', 'BSTM', 'Bachelor of Science in Tourism Management', '2024-12-08 15:50:22'),
(11, 'eccb83035e9cbf36', 'ceae349306d361df', 'BSECE', 'Bachelor of Science in Electronics Engineering', '2024-12-08 15:50:37'),
(12, 'eccb83035e9cbf36', '2a56204f00ce6fff', 'BSCE', 'Bachelor of Science in Computer Engineering', '2024-12-08 15:50:52');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_types`
--

CREATE TABLE `scholarship_types` (
  `id` int(11) NOT NULL,
  `scholarship_type_id` varchar(255) NOT NULL,
  `scholarship_type` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `eligibility` text NOT NULL,
  `archive` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarship_types`
--

INSERT INTO `scholarship_types` (`id`, `scholarship_type_id`, `scholarship_type`, `category`, `description`, `eligibility`, `archive`, `created_at`) VALUES
(10, 'f31040dfd734a87c94445c6d40c78eb3', 'Government', 'External', '[Description]', '[Eligibility]', NULL, '2024-09-23 11:54:40'),
(11, '70f8cfb8ac08902483c9aa083ba68170', 'Private', 'External', '[Description]', '[Eligibility]', NULL, '2024-09-23 11:54:47'),
(27, '6c8e4ad0ca3eb0486e29e7acc63e2bdf', 'Academic', 'Internal', '[Description]', '[Eligibility]', '', '2024-10-05 22:11:54'),
(32, 'bb32127037342ec9c79ac4a49ac58b99', 'Curricular', 'Internal', 'test', 'test', '', '2024-12-03 08:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `form_id` varchar(255) DEFAULT NULL,
  `subject_id` varchar(255) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `units` varchar(20) NOT NULL,
  `name_of_instructor` varchar(255) NOT NULL,
  `grade` float NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `form_id`, `subject_id`, `subject_code`, `units`, `name_of_instructor`, `grade`, `created_at`) VALUES
(19, 'ab9e71974420dc84aa2630e2abbc4445', 'd05d1acaefcb7a83', 'GEC 101', '3.0', 'Test 1', 1, '0000-00-00 00:00:00'),
(20, 'ab9e71974420dc84aa2630e2abbc4445', 'b4b76d11b623744f', 'GEC 102', '3.0', 'Test 2', 1.25, '0000-00-00 00:00:00'),
(21, 'ab9e71974420dc84aa2630e2abbc4445', '4a0a770a6072c84b', 'GEC 103', '3.0', 'Test 3', 1, '0000-00-00 00:00:00'),
(22, '34797155466c4d5c9029c1a28f8d8fcc', '440e96bc33403a35', 'GEC 101', '3.0', 'Instructor 1', 1, '0000-00-00 00:00:00'),
(23, '34797155466c4d5c9029c1a28f8d8fcc', '8144423435ae49c5', 'GEC 102', '3.0', 'Instructor 2', 1, '0000-00-00 00:00:00'),
(24, '612bd3b996c15f859c908ef6f881862e', '167c15ac8b2cf582', 'ABC 101', '1.0', 'Instruct 1', 1, '0000-00-00 00:00:00'),
(25, '95245b01c076aa84665e3ea6da8c17df', 'b22df61ee9a92d12', 'GEC 101', '3.0', 'Instructor 1', 1, '0000-00-00 00:00:00'),
(26, '95245b01c076aa84665e3ea6da8c17df', '33a3c185acab3df6', 'GEC 102', '3.0', 'Instructor 2', 1, '0000-00-00 00:00:00'),
(27, '95245b01c076aa84665e3ea6da8c17df', 'da859bd5bdc58bf8', 'GEC 103', '3.0', 'Instructor 3', 1.25, '0000-00-00 00:00:00'),
(28, 'e1ad828d34301d20667469f8eb406262', '31cd041151df6cf8', 'GEC 101', '3.0', 'Instructor 1', 1, '0000-00-00 00:00:00'),
(29, 'e1ad828d34301d20667469f8eb406262', '6f19bf53a1bcb18e', 'GEC 102', '3.0', 'Instructor 2', 1.25, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `types`
--

CREATE TABLE `types` (
  `id` int(11) NOT NULL,
  `scholarship_type_id` varchar(255) NOT NULL,
  `type_id` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `eligibility` varchar(255) NOT NULL,
  `archive` varchar(255) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `types`
--

INSERT INTO `types` (`id`, `scholarship_type_id`, `type_id`, `type`, `description`, `eligibility`, `archive`, `start_date`, `end_date`, `created_at`) VALUES
(9, '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '1a083b7909b4bf2a2d60af17a5fcadf1', 'Entrance Scholarship', 'tests', 'tests', '', '2024-12-01 00:00:00', '2024-12-31 00:00:00', '2024-10-05 22:12:07'),
(10, '9474d62b016bdb86f1b0ce80579b9ce5', 'fc06b9fdda48d561dd1f69137dae8368', 'test', 'test', 'test', 'hide', NULL, NULL, '2024-10-08 12:37:35'),
(11, 'bb32127037342ec9c79ac4a49ac58b99', 'bdcd6cb5a01b1def31be848c7bd35da6', 'The Perpetual Archives', 'test', 'test', '', '2024-11-01 00:00:00', '2024-12-31 00:00:00', '2024-12-03 08:44:08'),
(12, '70f8cfb8ac08902483c9aa083ba68170', 'e493ffae9fb7284df2de24660b6d6575', 'test', 'test', 'test', NULL, NULL, NULL, '2024-12-03 09:10:14'),
(13, '6c8e4ad0ca3eb0486e29e7acc63e2bdf', '703ec62941725294ef4074b5befac6ca', 'Dean\'s List', 'test\n\ntest', '[Eligibility]', '', '2024-12-01 00:00:00', '2024-12-31 00:00:00', '2024-12-09 10:08:53'),
(15, 'bb32127037342ec9c79ac4a49ac58b99', 'c382c97c5468a3caf9a7eebf6885fad1', 'Presidential/Board Director Scholars', '[Description]', '[Eligibility]', '', '2024-12-01 00:00:00', '2024-12-31 00:00:00', '2024-12-09 10:47:25'),
(16, 'bb32127037342ec9c79ac4a49ac58b99', '0b0db50551e8e80b33748c318166553f', 'College Council President', '[Description]', '[Eligibility]', '', '2024-12-01 00:00:00', '2024-12-31 00:00:00', '2024-12-09 10:47:47'),
(17, 'bb32127037342ec9c79ac4a49ac58b99', '87da19adee954b7446906e00fbe63c27', 'SSC Scholars', '[Description]', '[Eligibility]', '', '2024-12-01 00:00:00', '2024-12-31 00:00:00', '2024-12-09 10:48:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `student_number` varchar(255) DEFAULT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `date_of_birth` varchar(255) DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `security_key` varchar(255) DEFAULT NULL,
  `last_login` datetime NOT NULL,
  `joined_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `student_number`, `profile`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `place_of_birth`, `academic_year`, `department`, `program`, `email`, `password`, `role`, `status`, `token`, `security_key`, `last_login`, `joined_at`) VALUES
(16, 'ca0fc1d6228299bf3e4092c9a4bc6fea', '1004-2024', 'https://lh3.googleusercontent.com/a/ACg8ocLoNSZQV9TgNB6u9iYjQ2fZoMgRTF9asN0HUte_pKqm64k-dg=s96-c', 'Alfie', '', 'Figuracion', NULL, NULL, NULL, 'College of Computer Studies', 'Bachelor of Science in Computer Science', 'alfiefiguracion1108@gmail.com', '$2y$10$Kscy0ph1yJOmmmR1y6T3Oe24u0uDJejeR7z/RSAq2rIGAiDx7MNAG', 'dean', '', '', 'caa5ec2f8f87c77980dfba6e8b631570', '2024-12-13 12:35:45', '2024-09-23 22:23:34'),
(21, '41f3befda27a72c81a6ae14274d7bda1', '1003-2024', 'https://lh3.googleusercontent.com/a/ACg8ocK2WN4a_f-dGbBTFAgEgCe2ZrFc0OGmbMCvZXKO8KY-whvsU3k6=s96-c', 'Romeo', NULL, 'Razon', NULL, NULL, NULL, NULL, NULL, 'romeorazon0225@gmail.com', '$2y$10$NmQ4KHnqFepj7w1UsUR0x.giadepLZ05ebfMVRepyxWdAP1yLzEtW', 'pending', NULL, '', 'eab7d003edb4d44b035e093d352a5ad4', '2024-09-24 20:19:47', '2024-09-24 20:18:42'),
(23, '46ce627b6eea6c240b45c6329f1fd688', '1005-2024', 'https://lh3.googleusercontent.com/a/ACg8ocIpix2hiQZWdU1WZM3J1O-q16kevMNZQvNzEMP0f2lX0TepeiI=s96-c', 'CheapDevs', 'LB', 'PH', '2000-06-07', 'Los Banos Laguna', NULL, 'College of Computer Studies', 'Bachelor of Science in Computer Science', 'cheapdevsph@gmail.com', '$2y$10$.Qn1Gbs84PRXaIk6pGCin.8oLsfSPWJWNZcuobaPET2gZyP8/rEHy', 'student', '', '', '62ec34bcb50835a154ea73392419488a', '2024-12-12 20:17:10', '2024-10-05 11:44:05'),
(25, '987d46e7ceb3683bc0f82dcc889814f8', '', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'Test', 'L', 'Student', '', '', NULL, NULL, NULL, 'test@gmail.com', '$2y$10$MYHbW5/yAvjnVGMHKESupuQ5KJ5S26EWTG6xV5YzOwhb0CkDfnG8S', 'admin', NULL, '', '549f130af00e76b994c9dc059114a92d', '2024-12-13 14:35:05', '2024-10-06 10:56:18'),
(26, '02e45c09c13d8a7095670b06ab80ff4d', '0422-0632', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'Mark Nicholas', 'Limpin', 'Razon', 'June 7, 2004', 'Los Banos Laguna', NULL, 'College of Computer Studies', 'Bachelor of Science in Computer Science', 'razonmarknicholas.cdlb@gmail.com', '$2y$10$G5Lqneobkl9IzmJ6u4TeO.rlH1VkQiZVrhpSsf5g6jFZSwfI8AH6W', 'adviser', '', '', '4e507027036b874f1a09ad8ffbad5f53', '2024-12-13 12:17:16', '2024-10-06 05:24:57'),
(27, '5d30b6019301ab98bf6f320d9c17cd56', '0422-0631', 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png', 'test', 'limpin', 'student', '2024-12-19', 'Los Banos Laguna', NULL, 'College of Computer Studies', 'Bachelor of Science in Computer Science', 'teststudent@gmail.com', '$2y$10$yKhSFBIjXIfWpuZJFIvKOesxXi2m/AbvT6QfyHkx77VLl59t7uKni', 'student', NULL, '', 'ac3abe7e6f2e1370d8c9b8c26bf4e920', '2024-12-12 21:55:59', '2024-11-03 10:11:58'),
(28, '0bec5e113b553bba799e454334c5316e', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocI6HKjgAjr_qKU-jcWZsyDlA_QzAFNFQ26z8p961mVx7-s3sg=s96-c', 'Nicen', NULL, 'Mendoza', NULL, NULL, NULL, NULL, NULL, 'nicenmendoza04@gmail.com', '$2y$10$uHvE6o5Gua85f60t7RpaueQIVtmjsbm.gi/ENKhm6KMtRyDOzjH0O', 'pending', NULL, '', 'f72fbe6a567aaac2540b167b0d7a7d97', '0000-00-00 00:00:00', '2024-12-13 14:38:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scholarship_types`
--
ALTER TABLE `scholarship_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `scholarship_types`
--
ALTER TABLE `scholarship_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

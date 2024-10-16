-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2024 at 09:41 AM
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
-- Database: `activity`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `event_level` varchar(100) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `semester` varchar(20) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `stat` varchar(100) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `points` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `student_id`, `event_type`, `event_level`, `from_date`, `to_date`, `semester`, `pdf_file`, `stat`, `remarks`, `points`) VALUES
(9, 9, 'Tech-fest', 'Level I College Events', '2024-09-06', '2024-09-07', '', 'uploads/activities (3).pdf', 'approved', 'Approved', 5),
(10, 12, 'Seminar', 'Level I College Events', '2024-05-02', '2024-05-02', '', 'uploads/S1 CE Python Attendance.pdf', 'approved', 'Good', 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(200) DEFAULT NULL,
  `usertype` varchar(100) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `division` varchar(10) DEFAULT NULL,
  `admission_year` int(20) DEFAULT NULL,
  `ktu_register_no` varchar(50) DEFAULT NULL,
  `approval` varchar(50) DEFAULT NULL,
  `reset_token` varchar(255) NOT NULL,
  `token_expires` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `usertype`, `semester`, `branch`, `division`, `admission_year`, `ktu_register_no`, `approval`, `reset_token`, `token_expires`) VALUES
(2, 'Bibin Vincent', 'bibinv@tistcochin.edu.in', '$2y$10$pSsLlbDoUzs9dXd3wA24Oe.JlY3mZRSj9vpaJXwoLlXLIlZ4e9q0m', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 'a805450bc3152c07f5986c5dd5fbe6a58d6c2fa4c49c7060bdd80824c82bff22cf4635ee55d8de77471af05d357089e05e4b', 1728634991),
(3, 'Mima Manuel', 'mima@gmail.com', '$2y$10$YqpWU7Htiei9PnoQSVbGCeQ1fn/PWX7QRH2O1qrrBfzUI7spamwAm', 'faculty', 'S5', 'CSE', 'A', 2022, NULL, NULL, '', 0),
(4, 'Chintu', 'c@gmail.com', '$2y$10$zOXGXU2Fn5L2zoJ5NMP4euJ88FRogpsFjaJTa8RKqDR.BLQ/lITuy', 'faculty', 'S5', 'CSE', 'B', 2022, NULL, NULL, '', 0),
(5, 'Philo', 'p@gmail.com', '$2y$10$dOXdP4aothYSiH8YKCiCpuZaAsQQ6Y5ichm0/2FuGIMA8gHCocNjO', 'faculty', 'S3', 'CSE', 'A', 2023, NULL, NULL, '', 0),
(8, 'Student S5 B', 'students5b@gmail.com', '$2y$10$oNDE9xnaouag2oZkC0zQ8.WoyO.YE0Hqg9Aqoh13l2.MhZ6x0Q41K', 'student', NULL, 'CSE', 'B', 2022, 'KTU76CS002', 'approved', '', 0),
(9, 'Student S5 A', 'students5a@gmail.com', '$2y$10$xcGsH2CjBy4D8IAfB1bLTOH17CL2y/dOKT/Gu.qB6wbQ35lLD4zFW', 'student', NULL, 'CSE', 'A', 2022, 'KTU76CS0045', 'approved', '', 0),
(10, 'Student2 S5 A', 'student2s5a@gmail.com', '$2y$10$Rjgluz2JnE96pFNAgvOp7eGgoWbwNNEP5aUmXnrht6vPUM2ABQ3Te', 'student', NULL, 'CSE', 'A', 2022, 'KTU76CS0345', 'approved', '', 0),
(11, 'Abhay Varghese', 'abhay@gmail.com', '$2y$10$KX19JMnKITfn2HcaFPV2mOopjmzRiVFBqcb4CMs.HNliytTJLxFTa', 'student', NULL, 'CSE', 'A', 2023, 'KTU76CS45', 'pending', '', 0),
(12, 'Student3 S5 A', 'student3s5a@gmail.com', '$2y$10$R8eq5.v1I5nK2M97oitSn.7Qf8zYh0g5ZhR2VPPAS8KRsNPHiEJMu', 'student', NULL, 'CSE', 'A', 2022, 'KTU36CS45', 'approved', '', 0),
(13, 'Student4 S5 A', 'student4s5a@gmail.com', '$2y$10$GVdSqBF.jZmFD0CzaV2.FumXu2bRtmBlDpITLQFrl6jWr3Mwg4ndW', 'student', NULL, 'CSE', 'A', 2022, 'KTU00CS45', 'approved', '', 0),
(16, 'Dr. Sreela Sreedhar', 'sreela@tistcochin.edu.in', '$2y$10$CTQ2RHBFuDI0D4kqVSxP7uZSEWMBPqUbcUD/DZDb7pPFXJEi0b382', 'hod', NULL, 'CSE', NULL, NULL, NULL, NULL, '', 0),
(17, 'Student6 S5 A', 'student6s5a@gmail.com', '$2y$10$Oao0R3RGpSZWXgw4StvxN..tsww6NWoael6KPBir5gYmwv7vVBMse', 'student', NULL, 'CSE', 'A', 2022, '22KTUCS002', 'pending', '', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
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
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

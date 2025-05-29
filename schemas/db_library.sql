-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 29, 2025 at 07:34 AM
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
-- Database: `db_library`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `anggota_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `class` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`anggota_id`, `name`, `nim`, `class`, `gender`, `date_of_birth`, `place_of_birth`, `address`, `phone`, `email`, `created_at`, `updated_at`) VALUES
(8, 'Administrator', 'ADMIN001', NULL, 'Male', '1990-01-01', NULL, NULL, NULL, NULL, '2025-05-29 05:30:14', '2025-05-29 05:30:14'),
(9, 'Staff User', 'STAFF001', NULL, 'Female', '1995-01-01', NULL, NULL, NULL, NULL, '2025-05-29 05:30:14', '2025-05-29 05:30:14'),
(10, 'Azka Putra Aulia', '23173041', 'XI RPL', 'Male', '2008-08-01', 'Jakarta', 'ciputat', '123142', 'fafaf@gmail.com', '2025-05-29 05:32:42', '2025-05-29 05:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `year_published` year(4) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover` longblob DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `available_stock` int(11) NOT NULL DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `publisher`, `year_published`, `isbn`, `category`, `description`, `cover`, `stock`, `available_stock`, `location`, `created_at`, `updated_at`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', '1925', NULL, 'Fiction', 'A classic American novel set in the Jazz Age', NULL, 5, 5, NULL, '2025-05-28 07:21:20', '2025-05-28 07:21:20'),
(2, 'To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott &amp; Co.', '1960', NULL, 'Fiction', 'A novel about racial injustice in the American South', NULL, 0, 3, NULL, '2025-05-28 07:21:20', '2025-05-28 09:10:56'),
(3, '1984', 'George Orwell', 'Secker & Warburg', '1949', NULL, 'Fiction', 'A dystopian social science fiction novel', NULL, 3, 3, NULL, '2025-05-28 07:21:20', '2025-05-29 05:32:51'),
(4, 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', '2009', NULL, 'Technology', 'Comprehensive textbook on computer algorithms', NULL, 2, 2, NULL, '2025-05-28 07:21:20', '2025-05-28 09:54:35'),
(5, 'A Brief History of Time', 'Stephen Hawking', 'Bantam Books', '1988', NULL, 'Science', 'Popular science book about cosmology', NULL, 3, 2, NULL, '2025-05-28 07:21:20', '2025-05-28 09:55:37');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `borrowing_id` int(11) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','lost') NOT NULL DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`borrowing_id`, `anggota_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `fine_amount`, `notes`, `created_at`, `updated_at`) VALUES
(13, 10, 3, '2025-05-01', '2025-05-09', NULL, 'overdue', 0.00, NULL, '2025-05-29 05:32:51', '2025-05-29 05:33:42');

--
-- Triggers `borrowings`
--
DELIMITER $$
CREATE TRIGGER `update_book_stock_after_borrow` AFTER INSERT ON `borrowings` FOR EACH ROW BEGIN
    UPDATE books 
    SET available_stock = available_stock - 1 
    WHERE book_id = NEW.book_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_book_stock_after_return` AFTER UPDATE ON `borrowings` FOR EACH ROW BEGIN
    IF OLD.status != 'returned' AND NEW.status = 'returned' THEN
        UPDATE books 
        SET available_stock = available_stock + 1 
        WHERE book_id = NEW.book_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Fiction', 'Fictional literature and novels', '2025-05-28 07:21:20'),
(2, 'Non-Fiction', 'Educational and factual books', '2025-05-28 07:21:20'),
(3, 'Science', 'Scientific and technical books', '2025-05-28 07:21:20'),
(4, 'History', 'Historical books and biographies', '2025-05-28 07:21:20'),
(5, 'Technology', 'Computer science and technology books', '2025-05-28 07:21:20'),
(6, 'Literature', 'Classic and modern literature', '2025-05-28 07:21:20'),
(7, 'Reference', 'Dictionaries, encyclopedias, and reference materials', '2025-05-28 07:21:20'),
(8, 'Children', 'Books for children and young adults', '2025-05-28 07:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `fine_id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','waived') NOT NULL DEFAULT 'pending',
  `paid_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `days_overdue` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`fine_id`, `borrowing_id`, `anggota_id`, `amount`, `reason`, `status`, `paid_at`, `created_at`, `updated_at`, `days_overdue`) VALUES
(3, 13, 10, 30000.00, NULL, 'pending', NULL, '2025-05-29 05:33:42', '2025-05-29 05:33:42', 20);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:37:31'),
(2, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:40:02'),
(3, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:43:12'),
(4, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:46:47'),
(5, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:53:32'),
(6, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 07:53:40'),
(7, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 08:12:22'),
(8, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 08:13:30'),
(9, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 08:30:30'),
(10, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 08:32:20'),
(11, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:07:25'),
(12, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:08:03'),
(13, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:08:21'),
(14, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:09:37'),
(15, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:10:29'),
(16, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:11:16'),
(17, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:12:00'),
(18, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:27:58'),
(19, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:44:39'),
(20, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:46:08'),
(21, NULL, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-28 09:56:00'),
(22, 9, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-29 05:31:53'),
(23, 10, 'User login', NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:137.0) Gecko/20100101 Firefox/137.0', '2025-05-29 05:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'fine_per_day', '1500', '2025-05-28 09:53:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `p_role` enum('admin','staff','member') NOT NULL DEFAULT 'member',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'inactive',
  `registration_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `anggota_id`, `username`, `password`, `full_name`, `p_role`, `status`, `registration_status`, `last_login`, `created_at`, `updated_at`) VALUES
(8, 8, 'admin', '0192023a7bbd73250516f069df18b500', 'Administrator', 'admin', 'active', 'approved', NULL, '2025-05-29 05:30:14', '2025-05-29 05:30:14'),
(9, 9, 'staff', 'de9bf5643eabf80f4a56fda3bbb84483', 'Staff User', 'staff', 'active', 'approved', '2025-05-29 05:31:53', '2025-05-29 05:30:14', '2025-05-29 05:31:53'),
(10, 10, 'Kadem', '72d8b63af4f2eeea75eebe0c8344938d', 'Azka Putra Aulia', 'member', 'active', 'approved', '2025-05-29 05:32:48', '2025-05-29 05:32:42', '2025-05-29 05:32:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`anggota_id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `idx_anggota_nim` (`nim`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `idx_books_title` (`title`),
  ADD KEY `idx_books_author` (`author`),
  ADD KEY `idx_books_category` (`category`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`borrowing_id`),
  ADD KEY `anggota_id` (`anggota_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_borrowings_status` (`status`),
  ADD KEY `idx_borrowings_dates` (`borrow_date`,`due_date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`fine_id`),
  ADD KEY `borrowing_id` (`borrowing_id`),
  ADD KEY `anggota_id` (`anggota_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_user_action` (`user_id`,`action`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `anggota_id` (`anggota_id`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`p_role`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `anggota_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `borrowing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `fine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`borrowing_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

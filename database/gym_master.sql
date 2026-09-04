-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2026 at 10:51 PM
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
-- Database: `gym_master`
--

-- --------------------------------------------------------

--
-- Table structure for table `check_ins`
--

CREATE TABLE `check_ins` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `check_in_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `check_ins`
--

INSERT INTO `check_ins` (`id`, `member_id`, `check_in_time`) VALUES
(1, 3, '2026-08-04 22:31:28'),
(2, 3, '2026-08-05 16:47:03'),
(3, 5, '2026-08-05 16:47:10'),
(4, 10, '2026-08-06 10:38:27');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `title`, `amount`, `category`, `notes`, `created_at`) VALUES
(1, 'صيانة جهاز المشاية', 500.00, 'صيانة', '', '2026-08-02 18:05:44');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `type` enum('subscription','product','service') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `member_id`, `type`, `amount`, `tax`, `total_amount`, `created_at`) VALUES
(1, NULL, 'service', 10.00, 0.00, 10.00, '2026-08-04 18:45:07'),
(2, NULL, 'service', 20.00, 0.00, 20.00, '2026-08-04 19:30:51'),
(3, NULL, 'service', 20.00, 0.00, 20.00, '2026-08-05 14:03:42'),
(4, NULL, 'service', 20.00, 0.00, 20.00, '2026-08-05 19:06:51');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `birth_date` date DEFAULT NULL,
  `join_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `membership_type` varchar(50) DEFAULT NULL,
  `subscription_start` date DEFAULT NULL,
  `subscription_end` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `full_name`, `phone`, `email`, `address`, `photo`, `gender`, `birth_date`, `join_date`, `notes`, `created_at`, `membership_type`, `subscription_start`, `subscription_end`, `status`) VALUES
(3, 'احمد محمد', '01223232323', 'ahmed@gmail.com', 'الغربية مركز سمنود', NULL, 'male', '2005-01-11', '0000-00-00', NULL, '2026-08-04 15:35:15', 'شهري', '2026-08-04', '2026-09-05', 'نشط'),
(5, 'كريم محمد', '01221212121', 'kareem@gmail.com', 'المنصورة طلخا', NULL, 'male', '2000-07-12', '0000-00-00', NULL, '2026-08-04 18:58:32', 'شهري', '2026-07-15', '2026-08-15', 'منتهي'),
(9, 'محمود احمد', '01278787878', 'mahmoud@gmail.com', 'المنصورة طلخا', NULL, 'male', '2008-12-10', '0000-00-00', NULL, '2026-08-05 21:41:09', 'شهري', '2026-08-06', '2026-09-07', 'نشط'),
(10, 'عمر محمد', '01289898989', 'omar@gmail.com', 'الغربية  سمنود', NULL, 'male', '2005-06-15', '0000-00-00', NULL, '2026-08-06 07:35:14', '3 شهور', '2026-08-06', '2026-11-06', 'نشط'),
(11, 'ايمن محمد', '01236363636', NULL, 'المنصورة طلخا', NULL, 'male', '2006-10-11', '0000-00-00', NULL, '2026-08-06 07:43:38', 'شهري', '2026-07-03', '2026-08-03', 'expired'),
(12, 'محمود كريم', '01258585858', 'mahmoud@gmail.com', 'الغربية  سمنود', NULL, 'male', '2005-12-15', '0000-00-00', NULL, '2026-08-06 09:58:59', 'شهري', '2026-08-06', '2026-09-07', 'نشط');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `duration_days`, `price`, `created_at`) VALUES
(1, 'اشتراك شهري (Month Pass)', 30, 500.00, '2026-08-04 13:24:48'),
(2, 'اشتراك 3 شهور (Quarterly)', 90, 1350.00, '2026-08-04 13:24:48'),
(3, 'اشتراك سنوي (Annual VIP)', 365, 4500.00, '2026-08-04 13:24:48'),
(4, 'حصة واحدة (Daily Pass)', 1, 50.00, '2026-08-04 17:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'مكملات',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `quantity`, `created_at`) VALUES
(1, 'مياه', 'مشروبات', 10.00, 36, '2026-08-02 18:22:16'),
(2, 'تويست', 'مشروبات', 20.00, 45, '2026-08-02 18:51:04'),
(3, 'قهوة', 'مشروبات', 20.00, 98, '2026-08-02 18:51:35');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `member_id`, `product_id`, `quantity`, `total_price`, `created_at`) VALUES
(1, NULL, 1, 1, 10.00, '2026-08-02 18:22:24'),
(2, NULL, 2, 1, 20.00, '2026-08-02 18:57:29'),
(5, NULL, 3, 1, 20.00, '2026-08-04 18:55:35'),
(6, NULL, 3, 1, 20.00, '2026-08-04 19:30:22'),
(7, NULL, 1, 1, 10.00, '2026-08-05 14:03:17'),
(8, NULL, 1, 1, 10.00, '2026-08-05 14:08:14'),
(9, NULL, 1, 1, 10.00, '2026-08-05 14:09:02'),
(10, NULL, 2, 1, 20.00, '2026-08-05 19:12:16'),
(11, NULL, 2, 1, 20.00, '2026-08-06 10:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_sessions`
--

CREATE TABLE `schedule_sessions` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `time_range` varchar(50) NOT NULL,
  `session_name` varchar(150) NOT NULL,
  `room` varchar(100) NOT NULL,
  `status` enum('متاح','مكتمل') NOT NULL DEFAULT 'متاح',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_sessions`
--

INSERT INTO `schedule_sessions` (`id`, `trainer_id`, `day_name`, `time_range`, `session_name`, `room`, `status`, `sort_order`) VALUES
(1, 3, 'السبت', '06:00 ص - 07:00 ص', 'كروس فيت', 'قاعة 1', 'متاح', 1),
(2, 2, 'السبت', '10:00 ص - 11:00 ص', 'يوجا وبيلاتس', 'قاعة الاستوديو', 'متاح', 2),
(3, 1, 'السبت', '06:00 م - 07:00 م', 'كمال أجسام', 'صالة الأوزان', 'متاح', 3),
(4, 4, 'الأحد', '07:00 ص - 08:00 ص', 'سباحة', 'المسبح', 'متاح', 1),
(5, 6, 'الأحد', '05:00 م - 06:00 م', 'زومبا وكارديو', 'قاعة الاستوديو', 'متاح', 2),
(6, 5, 'الأحد', '07:00 م - 08:00 م', 'ملاكمة ودفاع عن النفس', 'قاعة 2', 'متاح', 3),
(7, 3, 'الاثنين', '06:00 ص - 07:00 ص', 'كروس فيت', 'قاعة 1', 'متاح', 1),
(8, 1, 'الاثنين', '06:00 م - 07:00 م', 'كمال أجسام', 'صالة الأوزان', 'متاح', 2),
(9, 2, 'الثلاثاء', '10:00 ص - 11:00 ص', 'يوجا وبيلاتس', 'قاعة الاستوديو', 'متاح', 1),
(10, 6, 'الثلاثاء', '05:00 م - 06:00 م', 'زومبا وكارديو', 'قاعة الاستوديو', 'مكتمل', 2),
(11, 5, 'الثلاثاء', '07:00 م - 08:00 م', 'ملاكمة ودفاع عن النفس', 'قاعة 2', 'متاح', 3),
(12, 4, 'الأربعاء', '07:00 ص - 08:00 ص', 'سباحة', 'المسبح', 'متاح', 1),
(13, 3, 'الأربعاء', '06:00 م - 07:00 م', 'كروس فيت', 'قاعة 1', 'متاح', 2),
(14, 2, 'الخميس', '10:00 ص - 11:00 ص', 'يوجا وبيلاتس', 'قاعة الاستوديو', 'متاح', 1),
(15, 1, 'الخميس', '06:00 م - 07:00 م', 'كمال أجسام', 'صالة الأوزان', 'متاح', 2),
(16, 6, 'الخميس', '08:00 م - 09:00 م', 'زومبا وكارديو', 'قاعة الاستوديو', 'متاح', 3);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `member_id`, `package_id`, `start_date`, `end_date`, `created_at`, `status`) VALUES
(1, 3, 1, '2026-08-04', '2026-09-03', '2026-08-04 16:58:17', 'active'),
(6, 5, 1, '2026-07-16', '2026-08-15', '2026-08-04 19:18:07', 'expired'),
(7, 9, 1, '2026-08-06', '2026-09-05', '2026-08-05 21:41:52', 'active'),
(8, 10, 2, '2026-08-06', '2026-11-04', '2026-08-06 07:39:16', 'active'),
(9, 11, 1, '2026-07-03', '2026-08-02', '2026-08-06 07:45:02', 'active'),
(10, 12, 3, '2026-08-06', '2027-08-06', '2026-08-06 09:59:31', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `gym_name` varchar(255) NOT NULL DEFAULT 'Gym Master',
  `phone` varchar(50) NOT NULL DEFAULT '01000000000',
  `tax_rate` decimal(5,2) DEFAULT 14.00,
  `invoice_message` varchar(255) DEFAULT 'نتمنى لكم تمريناً سعيداً',
  `open_time` time DEFAULT '08:00:00',
  `close_time` time DEFAULT '00:00:00',
  `currency` varchar(10) DEFAULT 'ج.م'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `gym_name`, `phone`, `tax_rate`, `invoice_message`, `open_time`, `close_time`, `currency`) VALUES
(1, 'Gym Master', '01228249057', 10.00, 'نتمنى لكم تمريناً سعيداً', '08:00:00', '00:00:00', 'ج.م');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `experience_years` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `photo` longtext DEFAULT NULL,
  `status` enum('نشط','إجازة') NOT NULL DEFAULT 'نشط',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `name`, `specialty`, `phone`, `experience_years`, `photo`, `status`, `created_at`) VALUES
(1, 'أحمد يوسف', 'كمال أجسام', '01012345678', 8, 'user1-128x128.jpg', 'نشط', '2026-08-03 12:44:06'),
(2, 'سارة مصطفى', 'يوجا وبيلاتس', '01098765432', 5, 'trainers/trainer_6a71fea8e34d9.jpg', 'نشط', '2026-08-03 12:44:06'),
(3, 'محمد عادل', 'كروس فيت', '01123456789', 6, 'trainers/trainer_6a71febc4ddb7.jpg', 'نشط', '2026-08-03 12:44:06'),
(4, 'نورهان طارق', 'سباحة', '01234567890', 4, 'user4-128x128.jpg', 'إجازة', '2026-08-03 12:44:06'),
(5, 'خالد سمير', 'ملاكمة ودفاع عن النفس', '01555555555', 10, 'trainers/trainer_6a71fe93161cc.jpg', 'نشط', '2026-08-03 12:44:06'),
(6, 'مريم حسن', 'زومبا وكارديو', '01211111111', 2, 'trainers/trainer_6a71fe66414d6.jpg', 'نشط', '2026-08-03 12:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `photo` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` enum('admin','staff','user') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `photo`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'mazen elbasyouny', 'mazen@gmail.com', NULL, '$2y$10$uccy7q3Yvw4M6EhI1Ig68uXVksguYLG9iO6QQg83nVLXqKwBthZyC', '01228249057', 'admin', '2026-08-04 14:36:03'),
(2, 'kareem mohamed', 'kareem@gmail.com', NULL, '$2y$10$l.DG5Czzd0hEdvSl0V.E1eVuYjIP02acnG7Zqq0tJ4G4kMfKIuqf.', '01225252525', 'user', '2026-08-04 19:27:13'),
(3, 'ismail ahmed', 'ismail@gmail.com', NULL, '$2y$10$GufJZlZcvIVFjsoJp6V4Ie9ToM770uK2yJN7dJksCTuFw7txwZhBW', '01252525252', 'staff', '2026-08-05 16:54:27'),
(5, 'mahmoud mohamed', 'mahmoud@gmail.com', NULL, '$2y$10$BsWfse3A2DEoMQ45YptpRuTamwvZSa3Hcod67Z0sfUR1tBNLo.ru.', '01278787878', 'user', '2026-08-05 21:05:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `check_ins`
--
ALTER TABLE `check_ins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `schedule_sessions`
--
ALTER TABLE `schedule_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `check_ins`
--
ALTER TABLE `check_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `schedule_sessions`
--
ALTER TABLE `schedule_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `check_ins`
--
ALTER TABLE `check_ins`
  ADD CONSTRAINT `check_ins_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_sessions`
--
ALTER TABLE `schedule_sessions`
  ADD CONSTRAINT `schedule_sessions_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

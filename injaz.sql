-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 18 يوليو 2025 الساعة 21:46
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `injaz`
--
CREATE DATABASE IF NOT EXISTS `injaz` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `injaz`;

-- --------------------------------------------------------

--
-- بنية الجدول `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `clients`
--

INSERT INTO `clients` (`client_id`, `company_name`, `contact_person`, `phone`, `email`) VALUES
(1, 'أحمد', NULL, '0551234567', ''),
(2, 'سارة', NULL, '0562345678', ''),
(3, 'شركة البيان', NULL, '0573456789', ''),
(5, 'نوف', NULL, '0595678901', ''),
(6, 'عبدالله', NULL, '0506789012', ''),
(14, 'تيفا', 'خالد', '009889', NULL),
(16, 'عمران', 'عبدالسلام', '0544592410', NULL),
(17, 'مروه', 'مروان', '0544592410', ''),
(18, 'نسمات الأريج', 'رمزي العامري', '0539433992', ''),
(19, 'صقر السروات', 'عبدالسلام', '0544592410', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('مدير','مصمم','معمل','محاسب') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `role`, `phone`, `email`, `password`) VALUES
(1, 'admin', 'مدير', '0500000001', '', '$2y$10$alFxnQl/OthZYXAE8k9h6..zEtzSbK9fNUJ5KmZFIRqYYdWbxKy1G'),
(2, 'توفيق عبدالبر', 'مصمم', '0500000002', '', '$2y$10$COIe.NS8.IlwXpRuccupoe2.ISe2kgX5TdmVpYJe4jEzcIpVV3gpu'),
(3, 'عمر الهادي', 'مصمم', '0500000003', '', '$2y$10$PC5359eQq0UGkxCDnHkdser4ji4eqGlMx3h.TOzW0/wb9XuB1x6G2'),
(4, 'حسام الشيخ', 'معمل', '0500000004', '', '$2y$10$GRpAWH2rQVlesJ5NyPwU0uRc9abLYffJYRdvdkAx3d4NIPY4r9y7.'),
(5, 'صهيب عادل', 'محاسب', '0500000005', '', '$2y$10$XJxnAW1zRdlhIz.i6pAzLOIfo52LrnUnnJYI3ho0G.wGo3h2gTgKm');

-- --------------------------------------------------------

--
-- بنية الجدول `employee_permissions`
--

CREATE TABLE `employee_permissions` (
  `employee_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `employee_permissions`
--

INSERT INTO `employee_permissions` (`employee_id`, `permission_key`) VALUES
(1, 'client_add'),
(1, 'client_delete'),
(1, 'client_edit'),
(1, 'client_export'),
(1, 'client_import'),
(1, 'client_view'),
(1, 'dashboard_reports_view'),
(1, 'dashboard_view'),
(1, 'employee_add'),
(1, 'employee_delete'),
(1, 'employee_edit'),
(1, 'employee_password_reset'),
(1, 'employee_view'),
(1, 'order_add'),
(1, 'order_delete'),
(1, 'order_edit'),
(1, 'order_edit_status'),
(1, 'order_view_all'),
(1, 'order_view_own'),
(1, 'product_add'),
(1, 'product_delete'),
(1, 'product_edit'),
(1, 'product_view'),
(2, 'client_add'),
(2, 'client_edit'),
(2, 'client_view'),
(2, 'dashboard_view'),
(2, 'employee_view'),
(2, 'order_add'),
(2, 'order_edit'),
(2, 'order_edit_status'),
(2, 'order_view_own'),
(2, 'product_add'),
(2, 'product_edit'),
(2, 'product_view'),
(2, 'task_card_actions'),
(2, 'task_card_edit'),
(2, 'task_card_view_client'),
(2, 'task_card_view_countdown'),
(2, 'task_card_view_designer'),
(2, 'task_card_view_summary'),
(2, 'task_card_whatsapp'),
(3, 'client_edit'),
(3, 'client_view'),
(3, 'dashboard_view'),
(3, 'order_add'),
(3, 'order_edit'),
(3, 'order_edit_status'),
(3, 'order_view_own'),
(3, 'product_add'),
(3, 'product_edit'),
(3, 'product_view'),
(3, 'task_card_actions'),
(3, 'task_card_edit'),
(3, 'task_card_view_client'),
(3, 'task_card_view_countdown'),
(3, 'task_card_view_designer'),
(3, 'task_card_view_summary'),
(3, 'task_card_whatsapp'),
(4, 'client_add'),
(4, 'client_edit'),
(4, 'client_view'),
(4, 'dashboard_view'),
(4, 'employee_view'),
(4, 'order_add'),
(4, 'order_edit_status'),
(4, 'order_view_own'),
(4, 'product_add'),
(4, 'product_edit'),
(4, 'product_view'),
(4, 'task_card_actions'),
(4, 'task_card_edit'),
(4, 'task_card_view_client'),
(4, 'task_card_view_countdown'),
(4, 'task_card_view_designer'),
(4, 'task_card_view_summary'),
(4, 'task_card_whatsapp'),
(5, 'client_add'),
(5, 'client_balance_report_view'),
(5, 'client_edit'),
(5, 'client_view'),
(5, 'dashboard_view'),
(5, 'employee_view'),
(5, 'financial_reports_view'),
(5, 'order_edit_status'),
(5, 'order_financial_settle'),
(5, 'order_view_own'),
(5, 'product_add'),
(5, 'product_edit'),
(5, 'product_view'),
(5, 'task_card_actions'),
(5, 'task_card_edit'),
(5, 'task_card_view_client'),
(5, 'task_card_view_countdown'),
(5, 'task_card_view_designer'),
(5, 'task_card_view_payment'),
(5, 'task_card_view_summary'),
(5, 'task_card_whatsapp');

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `notifications`
--

INSERT INTO `notifications` (`notification_id`, `employee_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(75, 1, 'تم تحويل الطلب #12 إلى مرحلة التنفيذ.', 'edit_order.php?id=12', 0, '2025-07-16 00:23:33'),
(76, 4, 'تم تحويل الطلب #12 إلى مرحلة التنفيذ.', 'edit_order.php?id=12', 0, '2025-07-16 00:23:33'),
(77, 1, 'تم تحويل الطلب #12 إلى مرحلة التنفيذ.', 'edit_order.php?id=12', 0, '2025-07-16 00:25:27'),
(78, 4, 'تم تحويل الطلب #12 إلى مرحلة التنفيذ.', 'edit_order.php?id=12', 0, '2025-07-16 00:25:27'),
(79, 1, 'أصبح الطلب #12 جاهزاً للتسليم.', 'edit_order.php?id=12', 0, '2025-07-16 00:25:56'),
(80, 1, 'تم تأكيد استلام العميل للطلب #12.', 'edit_order.php?id=12', 0, '2025-07-16 00:26:27'),
(81, 5, 'تم تأكيد استلام العميل للطلب #12.', 'edit_order.php?id=12', 0, '2025-07-16 00:26:27'),
(82, 4, 'تم تحويل الطلب #12 إلى مرحلة التنفيذ.', 'edit_order.php?id=12', 0, '2025-07-16 00:34:42'),
(83, 1, 'تم تحويل الطلب #2 إلى مرحلة التنفيذ.', 'edit_order.php?id=2', 0, '2025-07-16 00:43:34'),
(84, 4, 'تم تحويل الطلب #2 إلى مرحلة التنفيذ.', 'edit_order.php?id=2', 0, '2025-07-16 00:43:34'),
(85, 5, 'تم تأكيد استلام العميل للطلب #10.', 'edit_order.php?id=10', 0, '2025-07-16 00:53:01'),
(86, 1, 'تم تحويل الطلب #13 إلى مرحلة التنفيذ.', 'edit_order.php?id=13', 1, '2025-07-16 18:58:48'),
(87, 4, 'تم تحويل الطلب #13 إلى مرحلة التنفيذ.', 'edit_order.php?id=13', 0, '2025-07-16 18:58:48'),
(88, 1, 'أصبح الطلب #2 جاهزاً للتسليم.', 'edit_order.php?id=2', 0, '2025-07-16 18:59:28'),
(89, 1, 'تم تأكيد استلام العميل للطلب #2.', 'edit_order.php?id=2', 0, '2025-07-16 19:00:48'),
(90, 5, 'تم تأكيد استلام العميل للطلب #2.', 'edit_order.php?id=2', 0, '2025-07-16 19:00:48'),
(91, 1, 'تم تحويل الطلب #14 إلى مرحلة التنفيذ.', 'edit_order.php?id=14', 0, '2025-07-16 20:15:11'),
(92, 4, 'تم تحويل الطلب #14 إلى مرحلة التنفيذ.', 'edit_order.php?id=14', 0, '2025-07-16 20:15:11'),
(93, 1, 'أصبح الطلب #1 جاهزاً للتسليم.', 'edit_order.php?id=1', 0, '2025-07-16 20:15:41'),
(94, 1, 'تم تأكيد استلام العميل للطلب #1.', 'edit_order.php?id=1', 1, '2025-07-16 20:16:52'),
(95, 5, 'تم تأكيد استلام العميل للطلب #1.', 'edit_order.php?id=1', 0, '2025-07-16 20:16:52'),
(96, 1, 'تم إغلاق الطلب #10 تلقائياً لاكتماله.', 'edit_order.php?id=10', 0, '2025-07-17 18:44:32'),
(97, 3, 'تم تقييم مرحلة التصميم للطلب #15 (نسمات الأريج) بدرجة 6/10', 'timeline_reports.php', 0, '2025-07-17 23:03:23'),
(98, 3, 'تم تقييم مرحلة التصميم للطلب #15 (نسمات الأريج) بدرجة 1/10', 'timeline_reports.php', 0, '2025-07-17 23:03:26');

-- --------------------------------------------------------

--
-- بنية الجدول `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `workshop_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('مدفوع','غير مدفوع','مدفوع جزئياً') NOT NULL DEFAULT 'غير مدفوع',
  `payment_method` enum('نقدي','تحويل بنكي','فوري','غيره') DEFAULT 'نقدي',
  `order_date` datetime DEFAULT current_timestamp(),
  `delivered_at` datetime DEFAULT NULL COMMENT 'تاريخ تأكيد تسليم الطلب للعميل',
  `payment_settled_at` datetime DEFAULT NULL COMMENT 'تاريخ تأكيد الدفع الكامل',
  `due_date` date DEFAULT NULL,
  `status` enum('قيد التصميم','قيد التنفيذ','جاهز للتسليم','بانتظار الإغلاق','مكتمل','ملغي') NOT NULL DEFAULT 'قيد التصميم',
  `priority` enum('عاجل جداً','عالي','متوسط','منخفض') DEFAULT 'متوسط',
  `notes` text DEFAULT NULL,
  `design_completed_at` datetime DEFAULT NULL COMMENT 'تاريخ انتهاء مرحلة التصميم',
  `execution_completed_at` datetime DEFAULT NULL COMMENT 'تاريخ انتهاء مرحلة التنفيذ',
  `created_by` int(11) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `design_rating` int(2) DEFAULT NULL COMMENT 'تقييم مرحلة التصميم من 1-10',
  `execution_rating` int(2) DEFAULT NULL COMMENT 'تقييم مرحلة التنفيذ من 1-10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `orders`
--

INSERT INTO `orders` (`order_id`, `client_id`, `designer_id`, `workshop_id`, `total_amount`, `deposit_amount`, `remaining_amount`, `payment_status`, `payment_method`, `order_date`, `delivered_at`, `payment_settled_at`, `due_date`, `status`, `priority`, `notes`, `design_completed_at`, `execution_completed_at`, `created_by`, `last_update`, `updated_at`, `design_rating`, `execution_rating`) VALUES
(1, 1, 2, 4, 200.00, 50.00, 150.00, 'مدفوع جزئياً', 'نقدي', '2025-07-13 19:53:25', '2025-07-16 23:16:52', NULL, '2025-07-15', 'جاهز للتسليم', 'عالي', 'من المتجر', NULL, '2025-07-16 23:15:41', 1, NULL, '2025-07-17 17:25:30', NULL, NULL),
(2, 1, 3, NULL, 1000.00, 600.00, 400.00, 'مدفوع جزئياً', 'نقدي', '2025-07-14 00:04:19', '2025-07-16 22:00:48', NULL, '2025-07-13', 'جاهز للتسليم', 'عالي', 'من الواتساب', '2025-07-16 03:43:34', '2025-07-16 21:59:28', 1, NULL, '2025-07-16 19:14:41', NULL, NULL),
(10, 14, 2, NULL, 300.00, 300.00, 0.00, 'مدفوع', 'نقدي', '2025-07-14 05:00:34', '2025-07-16 03:53:01', '2025-07-17 21:44:32', '2025-07-14', 'مكتمل', 'عاجل جداً', 'من الواتس آب', NULL, '2025-07-16 03:41:42', 1, NULL, '2025-07-17 18:44:32', NULL, NULL),
(11, 16, 1, NULL, 250.00, 0.00, 250.00, 'غير مدفوع', 'نقدي', '2025-07-15 03:43:35', NULL, NULL, '2025-07-15', 'قيد التصميم', 'عالي', 'من المتجر', NULL, NULL, 1, NULL, NULL, NULL, NULL),
(12, 17, 3, 4, 700.00, 300.00, 400.00, 'مدفوع جزئياً', 'تحويل بنكي', '2025-07-15 05:27:26', '2025-07-16 03:26:27', NULL, '2025-07-15', 'قيد التنفيذ', 'عالي', 'اتصال ', '2025-07-16 03:34:42', '2025-07-16 03:25:56', 1, NULL, '2025-07-17 20:24:34', NULL, NULL),
(13, 18, 3, 4, 300.00, 100.00, 200.00, 'مدفوع جزئياً', 'نقدي', '2025-07-16 21:57:27', NULL, NULL, '2025-07-15', 'قيد التنفيذ', 'عالي', 'عبر الواتساب', '2025-07-16 21:58:48', NULL, 1, NULL, '2025-07-17 20:24:34', NULL, NULL),
(14, 18, 2, 4, 80.00, 0.00, 80.00, 'غير مدفوع', 'نقدي', '2025-07-16 22:11:47', NULL, NULL, '2025-07-16', 'قيد التنفيذ', 'متوسط', '', '2025-07-16 23:15:11', NULL, 1, NULL, '2025-07-17 20:24:34', NULL, NULL),
(15, 18, 3, NULL, 9.00, 0.00, 9.00, 'غير مدفوع', 'نقدي', '2025-07-16 22:15:15', NULL, NULL, '2025-07-16', 'قيد التصميم', 'متوسط', '', NULL, NULL, 1, NULL, '2025-07-17 23:03:26', 1, NULL),
(16, 19, 2, NULL, 900.00, 0.00, 900.00, 'غير مدفوع', 'نقدي', '2025-07-16 22:17:02', NULL, NULL, '2025-07-16', 'قيد التصميم', 'متوسط', '', NULL, NULL, 2, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `item_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `item_notes`) VALUES
(1, 13, 1, 2000, 'استاندر'),
(2, 14, 3, 566, 'تفاصيل عدل اسم العميل'),
(3, 2, 2, 1, ''),
(4, 15, 1, 6, ''),
(5, 16, 3, 1, ''),
(6, 10, 6, 1, ''),
(8, 1, 1, 1, ''),
(9, 11, 2, 1, ''),
(11, 12, 5, 1, ''),
(12, 12, 6, 34, 'كبيرة ');

-- --------------------------------------------------------

--
-- بنية الجدول `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_size` varchar(50) DEFAULT NULL,
  `default_material` varchar(50) DEFAULT NULL,
  `default_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `products`
--

INSERT INTO `products` (`product_id`, `name`, `default_size`, `default_material`, `default_details`) VALUES
(1, 'كرت عمل', '9x5 سم', 'ورق فاخر', 'تصميم من الوكالة'),
(2, 'درع', '25x20 سم', 'كريستال', 'قاعدة خشب'),
(3, 'رول أب', '85x200 سم', 'PVC', 'يشمل الشنطة'),
(4, 'بنر', '150x100 سم', 'فلكس', 'تركيب ميداني'),
(5, 'لوحة', '300x150 سم', 'ألمنيوم', 'إضاءة LED'),
(6, 'أكريلك', 'حسب الطلب', 'أكريلك شفاف', 'حفر بالأبعاد');

-- --------------------------------------------------------

--
-- بنية الجدول `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `push_subscriptions`
--

INSERT INTO `push_subscriptions` (`id`, `employee_id`, `endpoint`, `p256dh`, `auth`, `created_at`) VALUES
(1, 1, 'https://fcm.googleapis.com/fcm/send/d8O_6m1PSw4:APA91bFmaArfWRcitTjoxTujhmrSpmNuwqOCPvKSXsWw0jCgAivuGx4vgGiv_wcspExQeSwQ-VIJQRB8gZ7OKpciYXDYkc0jyhjxxzkk19H1iThEAd0SnpWYOzR5KZ_2yR4QZFDsqExU', 'BB67Q-qw_ETizPY-nqMW1s1RnG7kJc265x7xKDreFY3id3WjyntavZxk34qznXutxJk0Fx7u7vqAzrszOjQdacU', 'rEjsLdVoWk7401oSVp50-g', '2025-07-15 03:41:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `company_name_idx` (`company_name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `role_idx` (`role`);

--
-- Indexes for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD PRIMARY KEY (`employee_id`,`permission_key`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `employee_id_idx` (`employee_id`),
  ADD KEY `is_read_idx` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `workshop_id` (`workshop_id`),
  ADD KEY `client_id_idx` (`client_id`),
  ADD KEY `designer_id_idx` (`designer_id`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `due_date_idx` (`due_date`),
  ADD KEY `payment_status_idx` (`payment_status`),
  ADD KEY `fk_orders_creator` (`created_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id_idx` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `endpoint_unique` (`endpoint`(255)),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=826;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD CONSTRAINT `employee_permissions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- قيود الجداول `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- قيود الجداول `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `fk_orders_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `fk_orders_designer` FOREIGN KEY (`designer_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`designer_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`workshop_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `orders_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`);

--
-- قيود الجداول `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- قيود الجداول `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
--
-- Database: `injaz2`
--
CREATE DATABASE IF NOT EXISTS `injaz2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `injaz2`;

-- --------------------------------------------------------

--
-- بنية الجدول `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `clients`
--

INSERT INTO `clients` (`client_id`, `name`, `phone`) VALUES
(1, 'أحمد', '0551234567'),
(2, 'سارة', '0562345678'),
(3, 'شركة البيان', '0573456789'),
(5, 'نوف', '0595678901'),
(6, 'عبدالله', '0506789012'),
(14, 'تيفا - خالد', '009889');

-- --------------------------------------------------------

--
-- بنية الجدول `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('مدير','مصمم','معمل','محاسب') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `role`, `email`, `password`, `permissions`) VALUES
(1, 'admin', 'مدير', 'admin@example.com', '$2y$10$alFxnQl/OthZYXAE8k9h6..zEtzSbK9fNUJ5KmZFIRqYYdWbxKy1G', '[\"client_add\",\"client_delete\",\"client_edit\",\"client_export\",\"client_import\",\"client_view\",\"dashboard_reports_view\",\"dashboard_view\",\"employee_add\",\"employee_delete\",\"employee_edit\",\"employee_password_reset\",\"employee_view\",\"order_add\",\"order_delete\",\"order_edit\",\"order_edit_status\",\"order_view_all\",\"order_view_own\",\"product_add\",\"product_delete\",\"product_edit\",\"product_view\"]'),
(2, 'توفيق عبدالبر', 'مصمم', 'tawfiq@example.com', '$2y$10$0YyKiEApOZV.wlwS8XPUbeJeTn8y3lGoC1J49NTQXEhpGsfYQWA3m', '[\"client_add\",\"client_edit\",\"client_view\",\"dashboard_view\",\"order_add\",\"order_edit\",\"order_edit_status\",\"order_view_own\",\"product_add\",\"product_edit\",\"product_view\"]'),
(3, 'عمر الهادي', 'مصمم', 'omar@example.com', '$2y$10$PC5359eQq0UGkxCDnHkdser4ji4eqGlMx3h.TOzW0/wb9XuB1x6G2', '[\"client_edit\",\"client_view\",\"dashboard_view\",\"order_add\",\"order_edit\",\"order_edit_status\",\"order_view_own\",\"product_add\",\"product_edit\",\"product_view\"]'),
(4, 'حسام الشيخ', 'معمل', 'hussam@example.com', '$2y$10$GRpAWH2rQVlesJ5NyPwU0uRc9abLYffJYRdvdkAx3d4NIPY4r9y7.', '[\"client_view\",\"dashboard_view\",\"employee_view\",\"order_add\",\"order_edit_status\",\"order_view_own\",\"product_view\"]'),
(5, 'صهيب عادل', 'محاسب', 'suhaib@example.com', '$2y$10$BKOBscQQUbxUW7xNW22CXOgIAD2D5wzSTjFsaMi1ly/DaTwAyvRVa', '[\"client_add\",\"client_edit\",\"client_view\",\"dashboard_view\",\"employee_view\",\"order_edit_status\",\"order_view_own\",\"product_add\",\"product_edit\",\"product_view\"]');

-- --------------------------------------------------------

--
-- بنية الجدول `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `workshop_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('مدفوع','غير مدفوع','مدفوع جزئياً') NOT NULL DEFAULT 'غير مدفوع',
  `payment_method` enum('نقدي','تحويل بنكي','فوري','غيره') DEFAULT 'نقدي',
  `order_date` datetime DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `status` enum('قيد التصميم','قيد التنفيذ','جاهز للتسليم','مكتمل','ملغي') NOT NULL DEFAULT 'قيد التصميم',
  `priority` enum('عاجل جداً','عالي','متوسط','منخفض') DEFAULT 'متوسط',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `orders`
--

INSERT INTO `orders` (`order_id`, `client_id`, `designer_id`, `workshop_id`, `total_amount`, `deposit_amount`, `payment_status`, `payment_method`, `order_date`, `due_date`, `status`, `priority`, `notes`, `created_by`) VALUES
(1, 1, 2, 4, 200.00, 50.00, 'غير مدفوع', 'نقدي', '2025-07-13 19:53:25', '2025-07-15', 'جاهز للتسليم', 'عالي', 'من المتجر', 1),
(2, 1, 3, NULL, 1000.00, 700.00, 'غير مدفوع', 'نقدي', '2025-07-14 00:04:19', '2025-07-13', 'قيد التصميم', 'عالي', 'من الواتساب', 1),
(3, 14, 2, NULL, 300.00, 300.00, 'مدفوع', 'نقدي', '2025-07-14 05:00:34', '2025-07-14', 'قيد التصميم', 'عاجل جداً', 'من الواتس آب', 1),
(4, 2, 3, 4, 450.00, 200.00, 'مدفوع جزئياً', 'تحويل بنكي', '2025-07-15 10:00:00', '2025-07-20', 'قيد التنفيذ', 'متوسط', 'طلب عاجل', 2),
(5, 3, 2, NULL, 750.00, 500.00, 'مدفوع جزئياً', 'فوري', '2025-07-15 12:00:00', '2025-07-18', 'قيد التصميم', 'عالي', 'تصميم خاص', 1),
(6, 5, 3, 4, 1200.00, 600.00, 'غير مدفوع', 'نقدي', '2025-07-15 14:30:00', '2025-07-22', 'قيد التنفيذ', 'منخفض', 'من العميل مباشرة', 3),
(7, 6, 2, NULL, 300.00, 100.00, 'مدفوع جزئياً', 'تحويل بنكي', '2025-07-15 16:00:00', '2025-07-19', 'قيد التصميم', 'متوسط', 'طلب جديد', 2),
(8, 1, 3, 4, 500.00, 250.00, 'مدفوع جزئياً', 'فوري', '2025-07-16 09:00:00', '2025-07-21', 'جاهز للتسليم', 'عالي', 'من المتجر', 1),
(9, 2, 2, NULL, 200.00, 0.00, 'غير مدفوع', 'نقدي', '2025-07-16 10:30:00', '2025-07-20', 'قيد التصميم', 'منخفض', 'تصميم بسيط', 3),
(10, 14, 3, 4, 800.00, 400.00, 'مدفوع جزئياً', 'تحويل بنكي', '2025-07-16 11:00:00', '2025-07-23', 'قيد التنفيذ', 'عاجل جداً', 'طلب عاجل', 1),
(11, 3, 2, NULL, 600.00, 300.00, 'مدفوع', 'فوري', '2025-07-16 12:00:00', '2025-07-18', 'مكتمل', 'عالي', 'تم التسليم', 2),
(12, 5, 3, 4, 1000.00, 500.00, 'مدفوع جزئياً', 'نقدي', '2025-07-16 13:00:00', '2025-07-22', 'قيد التنفيذ', 'متوسط', 'من العميل', 3),
(13, 6, 2, NULL, 400.00, 200.00, 'غير مدفوع', 'تحويل بنكي', '2025-07-16 14:00:00', '2025-07-19', 'قيد التصميم', 'منخفض', 'تصميم جديد', 1),
(14, 1, 3, 4, 700.00, 350.00, 'مدفوع جزئياً', 'فوري', '2025-07-16 15:00:00', '2025-07-21', 'جاهز للتسليم', 'عالي', 'من الواتساب', 2),
(15, 2, 2, NULL, 250.00, 100.00, 'غير مدفوع', 'نقدي', '2025-07-16 16:00:00', '2025-07-20', 'قيد التصميم', 'متوسط', 'طلب بسيط', 3),
(16, 14, 3, 4, 900.00, 450.00, 'مدفوع جزئياً', 'تحويل بنكي', '2025-07-16 17:00:00', '2025-07-23', 'قيد التنفيذ', 'عاجل جداً', 'طلب عاجل', 1),
(17, 3, 2, NULL, 550.00, 300.00, 'مدفوع', 'فوري', '2025-07-16 18:00:00', '2025-07-18', 'مكتمل', 'عالي', 'تم التسليم', 2),
(18, 5, 3, 4, 1100.00, 550.00, 'مدفوع جزئياً', 'نقدي', '2025-07-16 19:00:00', '2025-07-22', 'قيد التنفيذ', 'متوسط', 'من العميل', 3);

-- --------------------------------------------------------

--
-- بنية الجدول `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `item_notes` text DEFAULT NULL
) ;

--
-- إرجاع أو استيراد بيانات الجدول `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `item_notes`) VALUES
(1, 1, 6, 7, 28.57, 'مع القواعد'),
(2, 1, 4, 5, 30.00, 'مقاس استاندر'),
(3, 2, 2, 6, 166.67, 'زجاجي 778*67'),
(4, 3, 6, 7, 42.86, 'شي'),
(5, 4, 1, 10, 45.00, 'كروت عمل تصميم خاص'),
(6, 4, 4, 2, 150.00, 'بنر خارجي'),
(7, 5, 3, 2, 300.00, 'رول أب للمعرض'),
(8, 5, 5, 1, 450.00, 'لوحة مضيئة'),
(9, 6, 2, 5, 200.00, 'دروع تكريم'),
(10, 6, 6, 2, 100.00, 'أكريلك صغير'),
(11, 7, 1, 8, 37.50, 'كروت شخصية'),
(12, 8, 4, 3, 150.00, 'بنر داخلي'),
(13, 8, 6, 2, 125.00, 'أكريلك مخصص'),
(14, 9, 1, 5, 40.00, 'كروت بسيطة'),
(15, 10, 3, 2, 300.00, 'رول أب للعرض'),
(16, 10, 5, 1, 500.00, 'لوحة كبيرة'),
(17, 11, 2, 3, 200.00, 'دروع تكريم'),
(18, 12, 4, 4, 150.00, 'بنر خارجي'),
(19, 12, 6, 3, 166.67, 'أكريلك مخصص'),
(20, 13, 1, 10, 40.00, 'كروت عمل'),
(21, 14, 3, 2, 300.00, 'رول أب للمعرض'),
(22, 14, 5, 1, 400.00, 'لوحة مضيئة'),
(23, 15, 1, 5, 50.00, 'كروت شخصية'),
(24, 16, 2, 4, 200.00, 'دروع تكريم'),
(25, 16, 6, 2, 250.00, 'أكريلك كبير'),
(26, 17, 3, 2, 275.00, 'رول أب مخصص'),
(27, 18, 4, 5, 150.00, 'بنر خارجي'),
(28, 18, 6, 3, 183.33, 'أكريلك مخصص');

-- --------------------------------------------------------

--
-- بنية الجدول `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `products`
--

INSERT INTO `products` (`product_id`, `name`, `default_price`) VALUES
(1, 'كرت عمل', 50.00),
(2, 'درع', 200.00),
(3, 'رول أب', 300.00),
(4, 'بنر', 150.00),
(5, 'لوحة', 500.00),
(6, 'أكريلك', 250.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `workshop_id` (`workshop_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`designer_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`workshop_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`);

--
-- قيود الجداول `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- بنية الجدول `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- إرجاع أو استيراد بيانات الجدول `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"injaz\",\"table\":\"clients\"},{\"db\":\"injaz\",\"table\":\"employees\"},{\"db\":\"injaz\",\"table\":\"orders\"},{\"db\":\"injaz2\",\"table\":\"orders\"},{\"db\":\"injaz2\",\"table\":\"employees\"},{\"db\":\"injaz\",\"table\":\"notifications\"},{\"db\":\"injaz\",\"table\":\"products\"},{\"db\":\"injaz\",\"table\":\"employee_permissions\"},{\"db\":\"injaz\",\"table\":\"order_items\"},{\"db\":\"enjaz\",\"table\":\"clients\"}]');

-- --------------------------------------------------------

--
-- بنية الجدول `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

--
-- إرجاع أو استيراد بيانات الجدول `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'enjaz', 'employees', '{\"sorted_col\":\"`employees`.`mobile` DESC\"}', '2025-07-12 13:38:55'),
('root', 'injaz', 'orders', '{\"sorted_col\":\"`status` DESC\"}', '2025-07-16 00:34:11'),
('root', 'takamul', 'programs', '{\"CREATE_TIME\":\"2025-06-24 22:23:42\",\"sorted_col\":\"`programs`.`google_map` DESC\"}', '2025-07-07 02:21:06'),
('root', 'takamul', 'users', '{\"CREATE_TIME\":\"2025-07-06 22:47:41\",\"sorted_col\":\"`can_manage_users` DESC\"}', '2025-07-06 19:53:31');

-- --------------------------------------------------------

--
-- بنية الجدول `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- إرجاع أو استيراد بيانات الجدول `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2025-07-18 19:46:13', '{\"Console\\/Mode\":\"collapse\",\"lang\":\"ar\",\"NavigationWidth\":356}');

-- --------------------------------------------------------

--
-- بنية الجدول `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- بنية الجدول `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `takamul`
--
CREATE DATABASE IF NOT EXISTS `takamul` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `takamul`;

-- --------------------------------------------------------

--
-- بنية الجدول `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `organizer` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` varchar(50) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `age_group` varchar(255) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `registration_link` varchar(255) DEFAULT NULL,
  `ad_link` varchar(255) DEFAULT NULL,
  `Direction` varchar(100) DEFAULT NULL,
  `google_map` varchar(255) DEFAULT NULL,
  `status` enum('pending','published','rejected','reviewed') NOT NULL DEFAULT 'pending',
  `end_date` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `programs`
--

INSERT INTO `programs` (`id`, `organizer`, `title`, `description`, `start_date`, `duration`, `age_group`, `price`, `location`, `registration_link`, `ad_link`, `Direction`, `google_map`, `status`, `end_date`) VALUES
(1, 'وقف مؤمنة', 'الحصالة', 'برنامج ترفيهي ممتع بجانب اقتصادي بسيط بطريقة مختلفه جذابة', '١/٤ الاحد', '3 أسابيع', 'متوسط وثانوي', '449', 'مركز مؤمنة حي السويدي', 'https://scanned.page/68489be5a154a', 'صورة الإعلان', 'غرب الرياض', NULL, 'published', ''),
(2, 'مؤسسة صاد', 'صيف صاد', 'برنامج قيمي تفاعلي موجه للأطفال، يهدف إلى غرس قيمة الإحسان في نفوسهم من خلال أنشطة مهاريه وترفيهية مبتكرة، تُقدم في بيئة آمنة ومحفزة، تدمج بين المتعة والتوجيه القيمي، وتساعد الطفل على ترجمة الإحسان إلى سلوك يومي عملي في علاقاته ومواقفه المختلفة', '11/1/1447', '4 أسابيع\r\nبواقع 16 يوم', 'الفتيات والفتيات من سن 5-6 الفتيات من سن 8-12', 'رسوم الاستثمار 749\r\nـ 649 التسجيل المبكر', 'روضة البيان حي الصفا', 'https://qr.me-qr.com/LmgbMxsa', NULL, 'شرق الرياض', NULL, 'published', ''),
(3, 'مؤسسة الغرس الواعد للترفيه', 'عوالم صيف الغرس', 'في (عوالم صيف الغرس) نخوض كل أسبوع عالمًا مختلفًا من المتعة والاستكشاف! الأسبوع الأول: عالم الحيوان الأسبوع الثاني: عالم المهن الأسبوع الثالث: عالم الدول الأسبوع الرابع: عالم الفواكه من خلال فقرات متنوعة مشوّقة، هادفة ماتعة..', '١١ صفر ١٤٤٧هـ', '4 أسابيع', 'للبنين: ٤-٨ سنوات | للبنات: ٤-١١ سنة', '١٤٠٠ للفترة كاملة \r\n- ٤٤٠ للأسبوع الواحد', 'دار رياض الذاكرات بحي الملقا', 'https://qr.me-qr.com/feAJawbL', NULL, 'شمال الرياض', NULL, 'published', ''),
(4, 'مركز الرسالة للفتيات', 'محطات', 'صيف محطات يتنقل فيه أطفالنا بين أمتع و أجمل المحطات، ليمارسوا التجارب العلمية والأعمال الفنية والمهارات المهنية، وورش قيمية تنمي لديهم جوانب عدة في شخصياتهم بأساليب مشوقة مليئة بالإبداع والتحدي', '1447/1/19', 'شهر', 'الإبتدائي من ٧- ١٠ سنوات بنات ، رياض أطفال من ٤-٦ سنوات اولاد وبنات', '500', 'مركز الرسالة للفتيات ، حي الملك فهد', 'https://forms.gle/rBM4coRW4dsPztDs6', NULL, 'شمال الرياض', NULL, 'published', ''),
(5, 'أكاديمية مكارم للفتيات التابعة لجمعية مكارم الأخلاق', 'بياض', 'برنامج قيمي تفاعلي نقتبس فيه لمحاتٍ من سيرة أشرف الخلق ﷺ، لنقتدي بالخطى ونسلك السبيل، في لقاءات تجمع بين الحوار القصصي، والعروض المسرحية، والتحديات التربوية الممتعة! نستكشف فيه القيم النبوية بأسلوبٍ تفاعلي ثري، يمسّ القلب ويُزهر الهوية.', 'من الأحد ١١- ١- ١٤٤٧هـ إلى يوم الخميس ٢٢-١-١٤٤٧هـ', 'عشرة أيام', 'المرحلة المتوسطة', '٤٥٠ ريال', 'أكاديمية مكارم للفتيات - حي الروابي', 'https://forms.gle/jX9tWB3yxpPbtXtW7', NULL, 'شرق الرياض', NULL, 'reviewed', ''),
(6, 'جمعية أفكار الاجتماعية', 'صيف أفكار ٣', 'نحول فيه المألوف إلى استثناء ونرى الأشياء بعين جديدة فنستمتع بحر الصيف بظل الابتكار والإلهام', '١١-١-١٤٤٧', '٤ أسابيع', 'متوسط- ثانوي - جامعي', '499', 'شمال الرياض (مدارس بواكير - حي الندى) شمال الرياض (مدارس معالم التربية - حي الصحافة) غرب الرياض (جمعية أفكار الاجتماعية- البديعة) جنوب الرياض (دار سلمى بنت عميس - الشفاء)', 'https://store.afkar.org.sa/?fbclid=PAQ0xDSwK_1zZleHRuA2FlbQIxMQABp7zt21ZHoBBqz6N0bxJjIZCdCzgrjheBsO4o2u_cgXuKLGZOJ6qGm6KDM1Kc_aem_C-hUNSgZjfCX6vofKOQEig', NULL, 'شمال الرياض', NULL, 'published', ''),
(7, 'جمعية وهج الثقافية', 'نمارق', 'معارف تنهض بالعقول، وتحديات تشعل الإبداع، وتجارب تصنع الدهشة، ولقاءات تُضفي الفائدة، تنافس ومرح ومباهج أخرى', '10 محرم 1447', '3 أسابيع', 'الثانوية ـ الجامعية', '200-500', 'مجمع نور بحي النرجس', 'https://salla.sa/wahj_sa/gyydlzo', NULL, 'شمال الرياض', NULL, 'published', ''),
(8, 'الهمّة العلياء', 'جذور', 'برنامج صيفي تربوي مكثف لمدة ثلاثة أسابيع يستهدف فتيات المرحلة الابتدائية في مقر جمعية الهمة العلياء بالرياض. يرتكز المشروع على مفهوم (لأصالة بالثبات على المبدأ الصحيح)، ويهدف إلى بناء فتيات واعيات، متمسكات بقيمهن الإسلامية الراسخة، واثقات بقدراتهن، ومؤثرات إيجابيات في أسرهن ومجتمعهن. يتم تحقيق ذلك من خلال فقرات متتالية تجمع بين الترفيه الهادف وورش عمل تفاعلية تركز على: المجال الشرعي: ورش عمل وقصص تفاعلية لترسيخ قيمة الثبات على الحق والمبادئ الإسلامية الأساسية في الحياة اليومية. المجال الأسري: ورش عمل وأنشطة تعاونية لتعزيز أهمية القيم الإسلامية في بناء أسرة متماسكة واعتبارها نواة للثبات الاجتماعي. المجال المهاري: ورش عمل عملية لتعلم مهارات يدوية بسيطة (الفخار والصوف) لتعزيز قيمة العمل المنتج والاعتماد على الذات. الجانب الترفيهي الهادف: أنشطة وألعاب موجهة لترسيخ القيم وتعزيز التفاعل الإيجابي وتنمية الإبداع بطرق تتوافق مع مبادئنا. تنمية الثقة بالنفس: تدريبات وأنشطة لتعزيز قدرة الفتيات على التعبير عن آرائهن المستندة إلى الحق بثبات واحترام. يهدف المشروع إلى غرس بذور القيم الأصيلة المستمدة من الشريعة الإسلامية، وتعزيز الثقة بالنفس، وتنمية المهارات الحياتية الأساسية، وتعميق الانتماء الإيجابي للأسرة والمجتمع لدى الفتيات المشاركات.', '١١ محرم', '3 أسابيع', 'فتيات المرحلة الابتدائية (٧-١٢ سنة)', '٤٠٠ ريال للمقعد الواحد - ٧٢٠ لمقعدين', 'في مقر الجمعية في حي الفلاح', 'لم يعلن بعد', NULL, 'شمال الرياض', NULL, 'published', ''),
(9, 'الهمّة العلياء', 'مد وجزر الموسم الثالث', 'ترسيخ مفهوم القناعة والرضا والتأمل ببعض الأدلة الواردة بالقرآن والسنة من خلال لقاءات إيمانية، وتعزيزها وغرسها والإجابة على التساؤلات الفكرية (كيف نبدل قناعات متمسكين بها ؟). التوضيح بالربط بين القناعة والرضا وأيهما يؤدي إلى الآخر وذلك من خلال لقاءات حوارية تربوية وكذلك ترفيهية، وأيضًا مفهوم التوازن النفسي والترابط الأسري وربطها بالحياة الواقعية و مهارة التركيز والتأمل على ما تملكه وإدراك معرفتها واهتمامها به من خلال اللقاءات. السعي لغرس الاستشعار بلذة الإهتمام بما تملكه والابتعاد عن المقارنة من خلال تقنين مهاراتها وقدراتها في صنع مهارات يدوية فنية من خلال لقاءات مهارية وتحديات، وتنمية مهارات التواصل مع الآخرين والتفاعل واكتساب المشتركة بعض من جوانب القناعة من خلال لقاءات ترفيهية وتحديات. الاهتمام بتعزيز الإنتماء الأسري من خلال جلسات حوارية وطرح للمشكلات التي تواجه الفتيات واقتراح حلول لها وأهمية تعزيز القدوات في المجتمع الأسري عبر أنشطة وألعاب ترفيهية وتحديات.', '٢ صفر', '3 أسابيع', 'فتيات المرحلة المتوسطة والثانوية (١٢-١٧) سنه', '٣٦٠ ريال لمقعد واحد - ٦٤٨ ريال لمقعدين', 'مقر الجمعية في حي الفلاح', 'لم ينشر بعد', NULL, 'شمال الرياض', NULL, 'published', ''),
(10, 'جمعية تعلّم', 'مَوئِل', 'يصحبنا في رحلة شيّقة وماتعة ومتنوعة من برامج ترفيهية وتعليمية، والحرص على الاستفادة فيها من الفتن التي مرت في سورة الكهف', '١١/ ١/ ١٤٤٧هـ', '4 أسابيع', 'جميع المستويات من المرحلة التمهيدية حتى مرحلة الأمهات', '200', 'دار الخنساء النسائية في حي الفيحاء', 'التسجيل حضوريًّا في المقر', NULL, 'شرق الرياض', NULL, 'published', ''),
(11, 'شركة تكوين القيم', 'صيف واعدة', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'مرحلة ما قبل المدرسة من 4 إلى 6 سنوات', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'published', ''),
(12, 'شركة تكوين القيم', 'صيف واعدة', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'من 6 إلى 12 سنة', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'published', ''),
(13, 'شركة تكوين القيم', 'صيف نهى', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'طالبات المرحلة المتوسطة والثانوية', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'published', ''),
(14, 'مركز وابل للتدريب', 'برنامج رباعيات الصيفي الموسم 2', 'برنامج مهاري قيمي ترفيهي في أربعة مجالات: المجال القيادي والمجال الاجتماعي والمجال التقني والمجال الفني. خلال أربعة أسابيع وبواقع أربعة أيام في الأسبوع', '4/1/1447', '4 أسابيع', 'الفتيات من 10-18 سنة (ابتدائي متوسط ثانوي)', '750 كامل البرنامج والتسجيل المبكر والمجموعات فقط 680 ريال', 'مركز وابل للتدريب الرياض مخرج 7 حي الوادي', 'https://forms.gle/erhwyTKmwPED4A1V7', NULL, 'شمال الرياض', NULL, 'published', ''),
(15, 'مركز حياتي مشروع', 'مركز خلّة للفتيات', 'مركز صيفي للفتيات', '١/١١ حتى ١/٢٩', '٣ أسابيع', 'ابتدائي / ناشئة (متوسط حتى الجامعي)', '600', 'حي المحمدية في دار المحمدية', 'https://docs.google.com/forms/d/e/1FAIpQLSeRUxBTvyDlBb68hhbsjzDroSu7q1SgpdMkpV9F-GyVSFTywg/viewform', NULL, 'شمال الرياض', NULL, 'published', ''),
(16, 'جمعية كون النسائية للريادة الشبابية', '(وِصال) لزائرات وساكِنات الرياض حديثاً', 'اللقاء الأول من سلسلة (وِصال) الخاصة بالشابات من زائرات الرياض وساكنات الرياض حديثاً بهدف تكوين مجتمع آمن وخاص بهن مع شابات يشاركنهن الاهتمامات والتطلعات والاحتياجات. اللقاء الأول سيتضمن: جلسة حوارية مع الأخصائية النفسية أ.الهنوف الزهيميل بعنوان (الاندماج والهوية) ووجبة عشاء ونشاط تفاعلي', '24/6/2025', '3 ساعات مدة اللقاء، من الساعة 6 إلى 9 مساءً', 'الشابات زائرات وساكنات الرياض حديثاً (من عمر 18 إلى 35 سنة)', '100 ريال', 'مقر جمعية كون النسائية للريادة الشبابية - حي السليمانية', 'https://forms.gle/e3HwqbdS1wWR1ohs9', NULL, 'شمال الرياض', NULL, 'published', ''),
(17, 'نادي غمائم', 'كوكبة', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 6 إلى 12 سنة', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/KRjnbBE', NULL, 'شرق الرياض', NULL, 'published', ''),
(18, 'نادي غمائم', 'تلاد', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 13 إلى 15', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/XzeGdww', NULL, 'شرق الرياض', NULL, 'published', ''),
(19, 'نادي غمائم', 'توليب', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 16 إلى 25', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/KRjnBGQ', NULL, 'شرق الرياض', NULL, 'published', ''),
(20, 'مركز الرواء العلمي للتدريب النسائي', 'قدوة', 'برنامج لتأهيل القائدات وفق نموذج قيادي مستلهم من السنة النبوية. محاور الدورة: 1- ابرز سمات القائد الناجح الواردة في الصحيحين البخاري ومسلم 2- المهارات الفنية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 3- المهارات الإنسانية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 4- المهارات الذاتية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 5- المهارات الفكرية للقائد الناجح الواردة في الصحيحين البخاري ومسلم', '6/1/1447هـ', 'يومان', 'الفئة المستهدفة: القائدات من عمر 25 فأعلى', '250 ريال', 'مجمع نور حي النرج', 'https://alrawaac.com/QzdzQWW?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(21, 'مركز الرواء العلمي للتدريب النسائي', 'ريادة', 'هو برنامج تدريبي تطويري متكامل للفتيات من عمر 16 إلى 20 سنة، يهدف إلى تمكينهم من مهارات ريادة الأعمال الحديثة من منظور تكاملي يجمع بين المعرفة الريادية، التطبيق العملي، والبناء القيمي، في تجربة تعليمية وملهمة. يعتمد البرنامج على مزيج متوازن من التعليم النظري، والتطبيق العملي، وغرس القيم الأخلاقية المرتبطة بريادة الأعمال، مثل الصدق، والإتقان، والأمانة. يُعد أحد النماذج الفريدة في تقديم تجربة متكاملة تجمع بين بناء الشخصية الريادية وتعزيز قيم الالتزام والابتكار، بما يتماشى مع توجهات رؤية السعودية 2030. يختتم البرنامج بتقديم منتج عملي يمثل مشروعًا ريادي مصغرًا من إبداع كل فتاة، يتم عرضه في معرض ختامي أمام جمهور ولجنة مختصة.', '11/1/1447', '6 أيام', 'فتيات من 16 إلى 20', '750 ريال', 'مجمع نور بحي النرجس', 'https://alrawaac.com/Oyqywje?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(22, 'جمعية وافر التعليمية', 'فجر', 'برنامج تعليمي قيمي مركز، قائم على استراتيجية السرد القصصي، يهدف إلى تقريب السيرة النبوية للفتيات، وفق الخط الزمني لها بدءًا من مولد رسول الله صلى الله عليه وسلم، وانتهاءً بقصة وفاته، من خلال قصص ومواقف من حياته، مع التركيز على الجوانب الأخلاقية والقيمية والتربوية بشكل ممنهج وموازى؛ لترسيخ معاني الأنموذج الحي في حياة الفتيات وتعزيز أثره على فكرهم وسلوكهم وبناء شخصيات قيادية فاعلة.', '4-1-1447هـ', 'عشرة أيام', 'فتيات من 17 إلى 25', '260 ريال', 'مجمع نور بحي النرجس', 'https://waffir.org.sa/VDqDAyZ?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(23, 'جمعية وافر التعليمية', 'مجالس معرفية لمدارسة كتاب الأدب المفرد', 'مجالس معرفية متخصصة في دراسة كتاب الأدب المفرد والتعليق عليه.', '18-1-1447هـ', 'عشرة أيام', '20 سنة فأعلى', '260 ريال', 'مجمع نور حي النرجس', 'https://waffir.org.sa/jgZgyVD?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(24, 'جمعية وافر التعليمية', 'مجالس معرفية في مدارسة سورة الإنسان', 'مجالس علمية نوعية في مدارسة معاني سورة الإنسان؛ بهدف تقريب هدايات القرآن للمستفيدات، وسط بيئة نسائية تفاعلية واحترافية.', '17 + 24 / 1 / 1447هـ', 'يومان', 'لغير التخصصات الشرعية 18-40 عام', '100 ريال', 'مجمع نور حي النرجس', 'https://waffir.org.sa/KRjRlQV?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(25, 'جمعية مكنون مدرسة أم الكرام السلمية', 'بوصلة', 'تمكين الفتاة من معرفة ذاتها وتحديد مسارها الشخصي والمهني بثقة ووضوح. الرؤية: فتيات قادرات على اتخاذ القرار وقيادة مساراتهن بثبات وإبداع. الأهداف: • بناء وعي ذاتي لدى الفتيات. • تطوير مهارات اتخاذ القرار وتحديد الأهداف. • تعزيز الثقة بالنفس وروح المبادرة. • التعرف على مسارات مهنية وشخصية بناء.', '4-1-1447هـ', '4 أسابيع', 'مرحلتي المتوسط والثانوي والجامعي', '500 ريال', 'مدرسة أم الكرام السلمية في حي الصحافة في الرياض', 'التسجيل حضوري في المدرسة', NULL, 'شمال الرياض', NULL, 'published', ''),
(26, 'جمعية غراس لتنمية الطفل', 'صيف رواء للفتيات', 'برنامج يهدف إلى إضفاء المرح لوقت فتياتنا والجمع بين المتعة والفائدة في واحة من القيم السامية عبر عدة برامج (الخرف والتحديات والمسابقات وأثر القيم في حياتنا).', '4-1-1447هـ', '3 أسابيع', 'الفتيات من 8 سنوات - 13 سنة', 'مسار رواء 699 ريال / مسار الخوارزمي ومسار بيان 799 ريال (شاملة للضريبة)', 'حي ظهرة البديعة - في مجمع رواد الإبداع - مدامس المتقدمة الأهلية', 'https://forms.gle/yZcYXycUK4JhoCjU6', NULL, 'غرب الرياض', NULL, 'published', ''),
(27, 'إثراء المعرفة', 'نبع الفاتحة', 'سرّ البدء ومقصد الوصول. برنامج قرآني قيمي، لمدراسة أم الكتاب .. تستخرج فيه القيم وتستبان المعاني، وتكتشف المقاصد. مجالس يفيض فيها النور.', '18-1-1447هـ', 'ثلاثة أيام', 'قدم البرنامج للمرحلتين (الثانوية والجامعية) في حلقتين منفصلتين.', '300 ريال', 'مجمع نور بحي النرجس', 'https://store.ethraa.com/category/mKwVGb?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(28, 'إثراء المعرفة', 'مرتقى العشرين جزء', 'حلقة لمراجعة العشرين جزءًا الأولى من القرآن وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'حافظة متقنة لأول عشرين جزءًا من القرآن', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/BprpXlw?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(29, 'إثراء المعرفة', 'مرتقى الخاتمات', 'ختمة صيفية لمراجعة القرآن الكريم كاملًا وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'الخاتمات المتقنات', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/pAQAnDg?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(30, 'إثراء المعرفة', 'مرتقى العشرة أجزاء', 'حلقة لمراجعة العشرة أجزاء الأولى من القرآن وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'حافظة متقنة لأول عشرة أجزاء من القرآن', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/WzlzPeX?s=tk', NULL, 'شمال الرياض', NULL, 'published', ''),
(31, 'جمعية التنمية الأهلية بحي الملقا', 'زمام', 'تطوير مهارات الفتيات في القيادة وتحمل المسؤولية من خلال تزويدهن بالمعارف والمهارات الأساسية التي تمكنهن من اتخاذ القرارات بثقة، وإدارة المواقف الحياتية بفعالية، وتعزيز روح المبادرة والريادة، مع التأكيد على القيم الإسلامية والأخلاقية في ممارسة القيادة.', '11-1-1447هـ', '4 أسابيع', 'فتيات المتوسط والثانوي', '299 ريال', 'حي الملقا خلف جامع العسكر', 'https://forms.gle/LisMZnZHYBBVzP8h9', NULL, 'شمال الرياض', NULL, 'published', ''),
(32, 'جمعية تعلّم', 'حصاد ا اللآلئ', 'برنامج ترويحي قيمي للفتيات يجمع بين المتعة والمعرفة عبر لقاءات علمية وأنشطة تنمي المهارات وتعزز القيم', '4-1-1447هـ', '3 أسابيع', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', '600 ريال خصم خاص', 'بصائر حي الجزيرة', 'https://forms.gle/K1Gq23hygGA5B9dKA', NULL, 'شرق الرياض', NULL, 'published', ''),
(33, 'جمعية وهج الثقافية', 'نمارق', 'معارف تنهض بالعقول، وتحديات تشعل الإبداع، وتجارب تصنع الدهشة، ولقاءات تُضفي الفائدة، تنافس ومرح ومباهج أخرى', '10 محرم 1447', '3 أسابيع', 'المتوسطة', '200-500', 'مجمع نور بحي النرجس', 'https://salla.sa/wahj_sa/OyyDPGm', NULL, 'شمال الرياض', NULL, 'published', ''),
(34, 'جمعية الرياحين', 'درب وهج', 'فقرات البرنامج  :    \r\n\r\n- قيمي \r\n- ⁠ترفيهي \r\n- ⁠مهاري \r\n- ⁠ثقافي.         \r\n\r\n-مدة البرنامج : اربع أيام في الاسبوع.', '04/01/1447', 'أسبوعين 4', '9 - 18 عام', '400', 'حي السويدي', 'https://forms.gle/KgxJKv7V8Z46YWDf6', NULL, 'غرب الرياض', NULL, 'published', ''),
(35, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'قادة المستقبل', 'برنامج تربوي يختص بتطوير وصقل مهارات القيادة لدى الأطفال مثل التواصل، والتعاون، وإدارة الوقت.', '04/01/1447', '2', '٨ - ١٢ سنة', '100 -350 - 1200', 'حي المعذر', 'https://forms.gle/WFLJqAvxPszUVJgu8', NULL, 'غرب الرياض', NULL, 'published', '12/01/1447'),
(39, 'تجريبي', 'شيب', 'جرب من عندي', '14/01/1447', 'يوم', '12-20', 'مجاني', 'تجريبي', 'https://chatgpt.com/', 'uploads/ad_68647da6ae0e43.69252644.jpeg', 'شرق الرياض', 'www', 'published', '07/01/1447'),
(40, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'ببببب', 'بببب', '07/01/1447', 'ثلاث أسابيع', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', '7000', 'حي المعذر', 'https://chatgpt.com/', NULL, 'شرق الرياض', 'www', 'published', '15/01/1447'),
(42, 'تجريبي', 'شيسبش', 'شسيبش', '01/01/1447', 'يوم', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', 'مجاني', 'الرياض ـ حي العليا', 'https://chatgpt.com/', NULL, 'شرق الرياض', 'www', 'published', '04/01/1447'),
(43, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'رررررر', 'ررررررررررررر', '15/01/1447', 'ثلاث أسابيع', '٨ - ١٢ سنة', '7000', 'الرياض ـ حي العليا', 'https://chatgpt.com/', NULL, 'غرب الرياض', 'www', 'published', NULL),
(44, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'رررررر', 'ررررررررررررر', '15/01/1447', 'ثلاث أسابيع', '٨ - ١٢ سنة', '7000', 'الرياض ـ حي العليا', 'https://chatgpt.com/', NULL, 'غرب الرياض', 'www', 'published', NULL),
(45, 'تجريبي', 'شيسبش', 'شسيبش', '01/01/1447', 'يوم', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', 'مجاني', 'الرياض ـ حي العليا', 'https://chatgpt.com/', NULL, 'شرق الرياض', 'www', 'published', '04/01/1447'),
(46, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'ببببب', 'بببب', '07/01/1447', 'ثلاث أسابيع', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', '7000', 'حي المعذر', 'https://chatgpt.com/', NULL, 'شرق الرياض', 'www', 'published', '15/01/1447'),
(47, 'تجريبي', 'شيب', 'جرب من عندي', '14/01/1447', 'يوم', '12-20', 'مجاني', 'تجريبي', 'https://chatgpt.com/', NULL, 'شرق الرياض', 'https://maps.app.goo.gl/JHmfRecqkmRd428U8', 'published', '07/01/1447'),
(48, 'جمعية التنمية بالمعذر ـ فريق ألفة', 'قادة المستقبل', 'برنامج تربوي يختص بتطوير وصقل مهارات القيادة لدى الأطفال مثل التواصل، والتعاون، وإدارة الوقت.', '04/01/1447', '2', '٨ - ١٢ سنة', '100 -350 - 1200', 'حي المعذر', 'https://forms.gle/WFLJqAvxPszUVJgu8', NULL, 'غرب الرياض', NULL, 'published', '12/01/1447'),
(49, 'جمعية الرياحين', 'درب وهج', 'فقرات البرنامج  :    \n\n- قيمي \n- ⁠ترفيهي \n- ⁠مهاري \n- ⁠ثقافي.         \n\n-مدة البرنامج : اربع أيام في الاسبوع.', '04/01/1447', 'أسبوعين 4', '9 - 18 عام', '400', 'حي السويدي', 'https://forms.gle/KgxJKv7V8Z46YWDf6', NULL, 'غرب الرياض', NULL, 'published', NULL),
(50, 'جمعية وهج الثقافية', 'نمارق', 'معارف تنهض بالعقول، وتحديات تشعل الإبداع، وتجارب تصنع الدهشة، ولقاءات تُضفي الفائدة، تنافس ومرح ومباهج أخرى', '10 محرم 1447', '3 أسابيع', 'المتوسطة', '200-500', 'مجمع نور بحي النرجس', 'https://salla.sa/wahj_sa/OyyDPGm', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(51, 'جمعية تعلّم', 'حصاد ا اللآلئ', 'برنامج ترويحي قيمي للفتيات يجمع بين المتعة والمعرفة عبر لقاءات علمية وأنشطة تنمي المهارات وتعزز القيم', '4-1-1447هـ', '3 أسابيع', 'الفتيات من رابع ابتدائي إلى ثالث ثانوي', '600 ريال خصم خاص', 'بصائر حي الجزيرة', 'https://forms.gle/K1Gq23hygGA5B9dKA', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(52, 'جمعية التنمية الأهلية بحي الملقا', 'زمام', 'تطوير مهارات الفتيات في القيادة وتحمل المسؤولية من خلال تزويدهن بالمعارف والمهارات الأساسية التي تمكنهن من اتخاذ القرارات بثقة، وإدارة المواقف الحياتية بفعالية، وتعزيز روح المبادرة والريادة، مع التأكيد على القيم الإسلامية والأخلاقية في ممارسة القيادة.', '11-1-1447هـ', '4 أسابيع', 'فتيات المتوسط والثانوي', '299 ريال', 'حي الملقا خلف جامع العسكر', 'https://forms.gle/LisMZnZHYBBVzP8h9', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(53, 'إثراء المعرفة', 'مرتقى العشرة أجزاء', 'حلقة لمراجعة العشرة أجزاء الأولى من القرآن وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'حافظة متقنة لأول عشرة أجزاء من القرآن', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/WzlzPeX?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(54, 'إثراء المعرفة', 'مرتقى الخاتمات', 'ختمة صيفية لمراجعة القرآن الكريم كاملًا وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'الخاتمات المتقنات', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/pAQAnDg?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(55, 'إثراء المعرفة', 'مرتقى العشرين جزء', 'حلقة لمراجعة العشرين جزءًا الأولى من القرآن وتثبيتها بخطة متابعة رصينة في بيئة محفزة.', '11-1-1447هـ', '5 أيام', 'حافظة متقنة لأول عشرين جزءًا من القرآن', '300 ريال', 'مجمع نور حي النرجس', 'https://store.ethraa.ws/BprpXlw?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(56, 'إثراء المعرفة', 'نبع الفاتحة', 'سرّ البدء ومقصد الوصول. برنامج قرآني قيمي، لمدراسة أم الكتاب .. تستخرج فيه القيم وتستبان المعاني، وتكتشف المقاصد. مجالس يفيض فيها النور.', '18-1-1447هـ', 'ثلاثة أيام', 'قدم البرنامج للمرحلتين (الثانوية والجامعية) في حلقتين منفصلتين.', '300 ريال', 'مجمع نور بحي النرجس', 'https://store.ethraa.com/category/mKwVGb?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(57, 'جمعية غراس لتنمية الطفل', 'صيف رواء للفتيات', 'برنامج يهدف إلى إضفاء المرح لوقت فتياتنا والجمع بين المتعة والفائدة في واحة من القيم السامية عبر عدة برامج (الخرف والتحديات والمسابقات وأثر القيم في حياتنا).', '4-1-1447هـ', '3 أسابيع', 'الفتيات من 8 سنوات - 13 سنة', 'مسار رواء 699 ريال / مسار الخوارزمي ومسار بيان 799 ريال (شاملة للضريبة)', 'حي ظهرة البديعة - في مجمع رواد الإبداع - مدامس المتقدمة الأهلية', 'https://forms.gle/yZcYXycUK4JhoCjU6', NULL, 'غرب الرياض', NULL, 'reviewed', NULL),
(58, 'جمعية مكنون مدرسة أم الكرام السلمية', 'بوصلة', 'تمكين الفتاة من معرفة ذاتها وتحديد مسارها الشخصي والمهني بثقة ووضوح. الرؤية: فتيات قادرات على اتخاذ القرار وقيادة مساراتهن بثبات وإبداع. الأهداف: • بناء وعي ذاتي لدى الفتيات. • تطوير مهارات اتخاذ القرار وتحديد الأهداف. • تعزيز الثقة بالنفس وروح المبادرة. • التعرف على مسارات مهنية وشخصية بناء.', '4-1-1447هـ', '4 أسابيع', 'مرحلتي المتوسط والثانوي والجامعي', '500 ريال', 'مدرسة أم الكرام السلمية في حي الصحافة في الرياض', 'التسجيل حضوري في المدرسة', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(59, 'جمعية وافر التعليمية', 'مجالس معرفية في مدارسة سورة الإنسان', 'مجالس علمية نوعية في مدارسة معاني سورة الإنسان؛ بهدف تقريب هدايات القرآن للمستفيدات، وسط بيئة نسائية تفاعلية واحترافية.', '17 + 24 / 1 / 1447هـ', 'يومان', 'لغير التخصصات الشرعية 18-40 عام', '100 ريال', 'مجمع نور حي النرجس', 'https://waffir.org.sa/KRjRlQV?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(60, 'جمعية وافر التعليمية', 'مجالس معرفية لمدارسة كتاب الأدب المفرد', 'مجالس معرفية متخصصة في دراسة كتاب الأدب المفرد والتعليق عليه.', '18-1-1447هـ', 'عشرة أيام', '20 سنة فأعلى', '260 ريال', 'مجمع نور حي النرجس', 'https://waffir.org.sa/jgZgyVD?s=tk', NULL, 'شمال الرياض', NULL, 'rejected', NULL),
(61, 'جمعية وافر التعليمية', 'فجر', 'برنامج تعليمي قيمي مركز، قائم على استراتيجية السرد القصصي، يهدف إلى تقريب السيرة النبوية للفتيات، وفق الخط الزمني لها بدءًا من مولد رسول الله صلى الله عليه وسلم، وانتهاءً بقصة وفاته، من خلال قصص ومواقف من حياته، مع التركيز على الجوانب الأخلاقية والقيمية والتربوية بشكل ممنهج وموازى؛ لترسيخ معاني الأنموذج الحي في حياة الفتيات وتعزيز أثره على فكرهم وسلوكهم وبناء شخصيات قيادية فاعلة.', '4-1-1447هـ', 'عشرة أيام', 'فتيات من 17 إلى 25', '260 ريال', 'مجمع نور بحي النرجس', 'https://waffir.org.sa/VDqDAyZ?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(62, 'مركز الرواء العلمي للتدريب النسائي', 'ريادة', 'هو برنامج تدريبي تطويري متكامل للفتيات من عمر 16 إلى 20 سنة، يهدف إلى تمكينهم من مهارات ريادة الأعمال الحديثة من منظور تكاملي يجمع بين المعرفة الريادية، التطبيق العملي، والبناء القيمي، في تجربة تعليمية وملهمة. يعتمد البرنامج على مزيج متوازن من التعليم النظري، والتطبيق العملي، وغرس القيم الأخلاقية المرتبطة بريادة الأعمال، مثل الصدق، والإتقان، والأمانة. يُعد أحد النماذج الفريدة في تقديم تجربة متكاملة تجمع بين بناء الشخصية الريادية وتعزيز قيم الالتزام والابتكار، بما يتماشى مع توجهات رؤية السعودية 2030. يختتم البرنامج بتقديم منتج عملي يمثل مشروعًا ريادي مصغرًا من إبداع كل فتاة، يتم عرضه في معرض ختامي أمام جمهور ولجنة مختصة.', '11/1/1447', '6 أيام', 'فتيات من 16 إلى 20', '750 ريال', 'مجمع نور بحي النرجس', 'https://alrawaac.com/Oyqywje?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(63, 'مركز الرواء العلمي للتدريب النسائي', 'قدوة', 'برنامج لتأهيل القائدات وفق نموذج قيادي مستلهم من السنة النبوية. محاور الدورة: 1- ابرز سمات القائد الناجح الواردة في الصحيحين البخاري ومسلم 2- المهارات الفنية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 3- المهارات الإنسانية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 4- المهارات الذاتية للقائد الناجح الواردة في الصحيحين البخاري ومسلم 5- المهارات الفكرية للقائد الناجح الواردة في الصحيحين البخاري ومسلم', '6/1/1447هـ', 'يومان', 'الفئة المستهدفة: القائدات من عمر 25 فأعلى', '250 ريال', 'مجمع نور حي النرج', 'https://alrawaac.com/QzdzQWW?s=tk', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(64, 'نادي غمائم', 'توليب', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 16 إلى 25', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/KRjnBGQ', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(65, 'نادي غمائم', 'تلاد', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 13 إلى 15', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/XzeGdww', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(66, 'نادي غمائم', 'كوكبة', 'مشروع يهدف إلى تعزيز القيم من خلال إقامة لقاءات وجلسات حوارية ومسابقات ترفيهية وأنشطة تفاعلية وتجارب علمية وورش عمل مهارية ومبادرات مجتمعية بطريقة مشوقة ومحفزة للأطفال والفتيات', '4/1/1447', '3 أسابيع', 'فتيات من 6 إلى 12 سنة', '400 ريال خصم 10% للأخوات', 'مدارس الأرقم للبنات القسم الإبتدائي', 'https://tanmiyahhamras.com/KRjnbBE', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(67, 'جمعية كون النسائية للريادة الشبابية', '(وِصال) لزائرات وساكِنات الرياض حديثاً', 'اللقاء الأول من سلسلة (وِصال) الخاصة بالشابات من زائرات الرياض وساكنات الرياض حديثاً بهدف تكوين مجتمع آمن وخاص بهن مع شابات يشاركنهن الاهتمامات والتطلعات والاحتياجات. اللقاء الأول سيتضمن: جلسة حوارية مع الأخصائية النفسية أ.الهنوف الزهيميل بعنوان (الاندماج والهوية) ووجبة عشاء ونشاط تفاعلي', '24/6/2025', '3 ساعات مدة اللقاء، من الساعة 6 إلى 9 مساءً', 'الشابات زائرات وساكنات الرياض حديثاً (من عمر 18 إلى 35 سنة)', '100 ريال', 'مقر جمعية كون النسائية للريادة الشبابية - حي السليمانية', 'https://forms.gle/e3HwqbdS1wWR1ohs9', NULL, 'شمال الرياض', NULL, 'rejected', NULL),
(68, 'مركز حياتي مشروع', 'مركز خلّة للفتيات', 'مركز صيفي للفتيات', '١/١١ حتى ١/٢٩', '٣ أسابيع', 'ابتدائي / ناشئة (متوسط حتى الجامعي)', '600', 'حي المحمدية في دار المحمدية', 'https://docs.google.com/forms/d/e/1FAIpQLSeRUxBTvyDlBb68hhbsjzDroSu7q1SgpdMkpV9F-GyVSFTywg/viewform', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(69, 'مركز وابل للتدريب', 'برنامج رباعيات الصيفي الموسم 2', 'برنامج مهاري قيمي ترفيهي في أربعة مجالات: المجال القيادي والمجال الاجتماعي والمجال التقني والمجال الفني. خلال أربعة أسابيع وبواقع أربعة أيام في الأسبوع', '4/1/1447', '4 أسابيع', 'الفتيات من 10-18 سنة (ابتدائي متوسط ثانوي)', '750 كامل البرنامج والتسجيل المبكر والمجموعات فقط 680 ريال', 'مركز وابل للتدريب الرياض مخرج 7 حي الوادي', 'https://forms.gle/erhwyTKmwPED4A1V7', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(70, 'شركة تكوين القيم', 'صيف نهى', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'طالبات المرحلة المتوسطة والثانوية', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(71, 'شركة تكوين القيم', 'صيف واعدة', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'من 6 إلى 12 سنة', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(72, 'شركة تكوين القيم', 'صيف واعدة', 'غرس القيم التربوية وتنمية المهارات الحياتية، والمهارات العاطفية والاجتماعية والمعرفية.', '٤ محرم ١٤٤٧ هـ', '٤ أسابيع', 'مرحلة ما قبل المدرسة من 4 إلى 6 سنوات', '١٥٠٠ ريال للمشاركة الواحدة لكامل المدة - ٥٠٠ ريال للمشاركة الواحدة للأسبوع الواحد', 'مدرسة رواد الخليج حي المغرزات بمدينة الرياض', 'https://tcween.com/zovYNlb', NULL, 'شمال الرياض', NULL, 'rejected', NULL),
(73, 'جمعية تعلّم', 'مَوئِل', 'يصحبنا في رحلة شيّقة وماتعة ومتنوعة من برامج ترفيهية وتعليمية، والحرص على الاستفادة فيها من الفتن التي مرت في سورة الكهف', '١١/ ١/ ١٤٤٧هـ', '4 أسابيع', 'جميع المستويات من المرحلة التمهيدية حتى مرحلة الأمهات', '200', 'دار الخنساء النسائية في حي الفيحاء', 'التسجيل حضوريًّا في المقر', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(74, 'الهمّة العلياء', 'مد وجزر الموسم الثالث', 'ترسيخ مفهوم القناعة والرضا والتأمل ببعض الأدلة الواردة بالقرآن والسنة من خلال لقاءات إيمانية، وتعزيزها وغرسها والإجابة على التساؤلات الفكرية (كيف نبدل قناعات متمسكين بها ؟). التوضيح بالربط بين القناعة والرضا وأيهما يؤدي إلى الآخر وذلك من خلال لقاءات حوارية تربوية وكذلك ترفيهية، وأيضًا مفهوم التوازن النفسي والترابط الأسري وربطها بالحياة الواقعية و مهارة التركيز والتأمل على ما تملكه وإدراك معرفتها واهتمامها به من خلال اللقاءات. السعي لغرس الاستشعار بلذة الإهتمام بما تملكه والابتعاد عن المقارنة من خلال تقنين مهاراتها وقدراتها في صنع مهارات يدوية فنية من خلال لقاءات مهارية وتحديات، وتنمية مهارات التواصل مع الآخرين والتفاعل واكتساب المشتركة بعض من جوانب القناعة من خلال لقاءات ترفيهية وتحديات. الاهتمام بتعزيز الإنتماء الأسري من خلال جلسات حوارية وطرح للمشكلات التي تواجه الفتيات واقتراح حلول لها وأهمية تعزيز القدوات في المجتمع الأسري عبر أنشطة وألعاب ترفيهية وتحديات.', '٢ صفر', '3 أسابيع', 'فتيات المرحلة المتوسطة والثانوية (١٢-١٧) سنه', '٣٦٠ ريال لمقعد واحد - ٦٤٨ ريال لمقعدين', 'مقر الجمعية في حي الفلاح', 'لم ينشر بعد', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(75, 'الهمّة العلياء', 'جذور', 'برنامج صيفي تربوي مكثف لمدة ثلاثة أسابيع يستهدف فتيات المرحلة الابتدائية في مقر جمعية الهمة العلياء بالرياض. يرتكز المشروع على مفهوم (لأصالة بالثبات على المبدأ الصحيح)، ويهدف إلى بناء فتيات واعيات، متمسكات بقيمهن الإسلامية الراسخة، واثقات بقدراتهن، ومؤثرات إيجابيات في أسرهن ومجتمعهن. يتم تحقيق ذلك من خلال فقرات متتالية تجمع بين الترفيه الهادف وورش عمل تفاعلية تركز على: المجال الشرعي: ورش عمل وقصص تفاعلية لترسيخ قيمة الثبات على الحق والمبادئ الإسلامية الأساسية في الحياة اليومية. المجال الأسري: ورش عمل وأنشطة تعاونية لتعزيز أهمية القيم الإسلامية في بناء أسرة متماسكة واعتبارها نواة للثبات الاجتماعي. المجال المهاري: ورش عمل عملية لتعلم مهارات يدوية بسيطة (الفخار والصوف) لتعزيز قيمة العمل المنتج والاعتماد على الذات. الجانب الترفيهي الهادف: أنشطة وألعاب موجهة لترسيخ القيم وتعزيز التفاعل الإيجابي وتنمية الإبداع بطرق تتوافق مع مبادئنا. تنمية الثقة بالنفس: تدريبات وأنشطة لتعزيز قدرة الفتيات على التعبير عن آرائهن المستندة إلى الحق بثبات واحترام. يهدف المشروع إلى غرس بذور القيم الأصيلة المستمدة من الشريعة الإسلامية، وتعزيز الثقة بالنفس، وتنمية المهارات الحياتية الأساسية، وتعميق الانتماء الإيجابي للأسرة والمجتمع لدى الفتيات المشاركات.', '١١ محرم', '3 أسابيع', 'فتيات المرحلة الابتدائية (٧-١٢ سنة)', '٤٠٠ ريال للمقعد الواحد - ٧٢٠ لمقعدين', 'في مقر الجمعية في حي الفلاح', 'لم يعلن بعد', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(76, 'جمعية وهج الثقافية', 'نمارق', 'معارف تنهض بالعقول، وتحديات تشعل الإبداع، وتجارب تصنع الدهشة، ولقاءات تُضفي الفائدة، تنافس ومرح ومباهج أخرى', '10 محرم 1447', '3 أسابيع', 'الثانوية ـ الجامعية', '200-500', 'مجمع نور بحي النرجس', 'https://salla.sa/wahj_sa/gyydlzo', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(77, 'جمعية أفكار الاجتماعية', 'صيف أفكار ٣', 'نحول فيه المألوف إلى استثناء ونرى الأشياء بعين جديدة فنستمتع بحر الصيف بظل الابتكار والإلهام', '١١-١-١٤٤٧', '٤ أسابيع', 'متوسط- ثانوي - جامعي', '499', 'شمال الرياض (مدارس بواكير - حي الندى) شمال الرياض (مدارس معالم التربية - حي الصحافة) غرب الرياض (جمعية أفكار الاجتماعية- البديعة) جنوب الرياض (دار سلمى بنت عميس - الشفاء)', 'https://store.afkar.org.sa/?fbclid=PAQ0xDSwK_1zZleHRuA2FlbQIxMQABp7zt21ZHoBBqz6N0bxJjIZCdCzgrjheBsO4o2u_cgXuKLGZOJ6qGm6KDM1Kc_aem_C-hUNSgZjfCX6vofKOQEig', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(78, 'أكاديمية مكارم للفتيات التابعة لجمعية مكارم الأخلاق', 'بياض', 'برنامج قيمي تفاعلي نقتبس فيه لمحاتٍ من سيرة أشرف الخلق ﷺ، لنقتدي بالخطى ونسلك السبيل، في لقاءات تجمع بين الحوار القصصي، والعروض المسرحية، والتحديات التربوية الممتعة! نستكشف فيه القيم النبوية بأسلوبٍ تفاعلي ثري، يمسّ القلب ويُزهر الهوية.', 'من الأحد ١١- ١- ١٤٤٧هـ إلى يوم الخميس ٢٢-١-١٤٤٧هـ', 'عشرة أيام', 'المرحلة المتوسطة', '٤٥٠ ريال', 'أكاديمية مكارم للفتيات - حي الروابي', 'https://forms.gle/jX9tWB3yxpPbtXtW7', NULL, 'شرق الرياض', NULL, 'reviewed', NULL),
(79, 'مركز الرسالة للفتيات', 'محطات', 'صيف محطات يتنقل فيه أطفالنا بين أمتع و أجمل المحطات، ليمارسوا التجارب العلمية والأعمال الفنية والمهارات المهنية، وورش قيمية تنمي لديهم جوانب عدة في شخصياتهم بأساليب مشوقة مليئة بالإبداع والتحدي', '1447/1/19', 'شهر', 'الإبتدائي من ٧- ١٠ سنوات بنات ، رياض أطفال من ٤-٦ سنوات اولاد وبنات', '500', 'مركز الرسالة للفتيات ، حي الملك فهد', 'https://forms.gle/rBM4coRW4dsPztDs6', NULL, 'شمال الرياض', NULL, 'reviewed', NULL),
(80, 'مؤسسة الغرس الواعد للترفيه', 'عوالم صيف الغرس', 'في (عوالم صيف الغرس) نخوض كل أسبوع عالمًا مختلفًا من المتعة والاستكشاف! الأسبوع الأول: عالم الحيوان الأسبوع الثاني: عالم المهن الأسبوع الثالث: عالم الدول الأسبوع الرابع: عالم الفواكه من خلال فقرات متنوعة مشوّقة، هادفة ماتعة..', '١١ صفر ١٤٤٧هـ', '4 أسابيع', 'للبنين: ٤-٨ سنوات | للبنات: ٤-١١ سنة', '١٤٠٠ للفترة كاملة - ٤٤٠ للأسبوع الواحد', 'دار رياض الذاكرات بحي الملقا', 'https://qr.me-qr.com/feAJawbL', NULL, 'شمال الرياض', 'https://maps.app.goo.gl/Vxp8pdiTsKxqdEbw8?g_st=ic', 'reviewed', NULL),
(81, 'مؤسسة صاد', 'صيف صاد', 'برنامج قيمي تفاعلي موجه للأطفال، يهدف إلى غرس قيمة الإحسان في نفوسهم من خلال أنشطة مهاريه وترفيهية مبتكرة، تُقدم في بيئة آمنة ومحفزة، تدمج بين المتعة والتوجيه القيمي، وتساعد الطفل على ترجمة الإحسان إلى سلوك يومي عملي في علاقاته ومواقفه المختلفة', '11/1/1447', '4 أسابيع\nبواقع 16 يوم', 'الفتيات والفتيات من سن 5-6 الفتيات من سن 8-12', 'رسوم الاستثمار 749\nـ 649 التسجيل المبكر', 'روضة البيان حي الصفا', 'https://qr.me-qr.com/LmgbMxsa', NULL, 'شرق الرياض', NULL, 'published', NULL),
(82, 'وقف مؤمنة', 'الحصالة', 'برنامج ترفيهي ممتع بجانب اقتصادي بسيط بطريقة مختلفه جذابة', '١/٤ الاحد', '3 أسابيع', 'متوسط وثانوي', '449', 'مركز مؤمنة حي السويدي', 'https://scanned.page/68489be5a154a', NULL, 'غرب الرياض', 'https://www.google.com/maps/place/24.774265,46.738586/@24.774265,46.738586,17z', 'published', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('contact_email', 'takamul15@gmail.com'),
('contact_number', '⁦+⁦+966554429920⁩'),
('guide_name', 'دليل البرامج الصيفية للفتيات'),
('guide_pdf_enabled', '1'),
('guide_pdf_footer_enabled', '1'),
('guide_pdf_header_enabled', '1'),
('guide_pdf_path', 'uploads/settings/guide_pdf_path_1751410271.pdf'),
('guide_subtitle', 'في مدينة الرياض 1447هـ'),
('logo_path', 'uploads/settings/logo_path_1751410271.png'),
('telegram_channel_enabled', '0'),
('telegram_channel_footer_enabled', '0'),
('telegram_channel_header_enabled', '0'),
('telegram_channel_url', 'https://www.whatsapp.com/channel/0029VahQ1kvLI8YTd9OMQl35'),
('whatsapp_channel_enabled', '1'),
('whatsapp_channel_footer_enabled', '1'),
('whatsapp_channel_header_enabled', '1'),
('whatsapp_channel_url', 'https://www.whatsapp.com/channel/0029VahQ1kvLI8YTd9OMQl35');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_manage_users` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'صلاحية إدارة المستخدمين',
  `can_add_programs` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'صلاحية إضافة برامج',
  `can_edit_programs` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'صلاحية تعديل برامج',
  `can_delete_programs` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'صلاحية حذف برامج',
  `can_manage_settings` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'صلاحية اعدادات الموقع',
  `can_publish_programs` tinyint(1) NOT NULL DEFAULT 0,
  `can_review_programs` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `can_manage_users`, `can_add_programs`, `can_edit_programs`, `can_delete_programs`, `can_manage_settings`, `can_publish_programs`, `can_review_programs`) VALUES
(4, 'supervisor', '$2y$10$qH8xG8q6n430td/V6/fHCeHxindUaMKwTVI3tjO2X4MBeAO2Mbq7G', 1, 1, 1, 1, 1, 1, 1),
(5, 'user', '$2y$10$DhDVvZhqbtDiP8Z3Gd8NP.mJ/wlc4Fic6oDgThF2.prVUE3hZeIIy', 0, 1, 1, 1, 0, 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

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
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

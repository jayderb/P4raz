-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 11:01 AM
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
-- Database: `zedauto_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_details`
--

CREATE TABLE `customer_details` (
  `customer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispatch_orders`
--

CREATE TABLE `dispatch_orders` (
  `order_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','dispatched','cancelled') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_details`
--

CREATE TABLE `employee_details` (
  `employee_id` int(11) NOT NULL,
  `hire_date` date NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `part_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`log_id`, `user_id`, `email`, `ip_address`, `user_agent`, `success`, `timestamp`) VALUES
(1, 1, '', '', NULL, 0, '2025-05-04 22:27:11'),
(2, 1, '', '', NULL, 0, '2025-05-04 22:27:11'),
(3, 2, '', '', NULL, 0, '2025-05-04 22:27:11'),
(4, 2, '', '', NULL, 0, '2025-05-04 22:27:11'),
(5, 3, '', '', NULL, 0, '2025-05-04 22:27:11'),
(6, 3, '', '', NULL, 0, '2025-05-04 22:27:11'),
(7, 4, '', '', NULL, 0, '2025-05-04 22:27:11'),
(8, 4, '', '', NULL, 0, '2025-05-04 22:27:11'),
(9, 5, '', '', NULL, 0, '2025-05-04 22:27:11'),
(10, 5, '', '', NULL, 0, '2025-05-04 22:27:11'),
(11, 7, '', '', NULL, 0, '2025-05-04 22:27:11'),
(12, 7, '', '', NULL, 0, '2025-05-04 22:27:11'),
(13, 9, '', '', NULL, 0, '2025-05-04 22:27:11'),
(14, 9, '', '', NULL, 0, '2025-05-04 22:27:11'),
(15, 1, '', '', NULL, 0, '2025-05-06 14:43:01'),
(16, 1, '', '', NULL, 0, '2025-05-06 14:43:01'),
(17, 1, '', '', NULL, 0, '2025-05-07 12:44:01'),
(18, 1, '', '', NULL, 0, '2025-05-07 12:44:01'),
(19, 1, '', '', NULL, 0, '2025-05-07 14:45:45'),
(20, 1, '', '', NULL, 0, '2025-05-07 14:45:45');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'view_orders', 'Can view orders'),
(2, 'create_orders', 'Can create new orders'),
(3, 'cancel_orders', 'Can cancel orders'),
(4, 'view_inventory', 'Can view inventory'),
(5, 'manage_inventory', 'Can add/update inventory'),
(6, 'view_customers', 'Can view customer information'),
(7, 'manage_customers', 'Can add/update customer information'),
(8, 'view_employees', 'Can view employee information'),
(9, 'manage_employees', 'Can add/update employee information'),
(10, 'view_reports', 'Can view business reports'),
(11, 'manage_settings', 'Can change system settings');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `created_at`) VALUES
(1, 'Oil Filter', 74.99, 32, '2025-05-01 21:50:20'),
(2, 'Brake Pads', 149.99, 13, '2025-05-01 21:50:20'),
(3, 'Car Battery', 249.99, 2, '2025-05-01 21:50:20'),
(4, 'Tire', 849.99, 27, '2025-05-01 21:50:20'),
(5, 'Spark plugs', 100.00, 89, '2025-05-06 13:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('customer','employee','manager') NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission_id`) VALUES
('customer', 1),
('customer', 2),
('customer', 3),
('employee', 1),
('employee', 2),
('employee', 3),
('employee', 4),
('employee', 5),
('employee', 6),
('manager', 1),
('manager', 2),
('manager', 3),
('manager', 4),
('manager', 5),
('manager', 6),
('manager', 7),
('manager', 8),
('manager', 9),
('manager', 10),
('manager', 11);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','delivered') DEFAULT 'pending',
  `customer_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `employee_id`, `total`, `sale_date`, `status`, `customer_id`, `payment_method`) VALUES
(2, 1, 2749.87, '2025-05-01 22:03:08', 'delivered', NULL, NULL),
(3, 1, 5374.86, '2025-05-01 22:08:03', 'pending', NULL, NULL),
(4, 1, 2774.91, '2025-05-01 23:03:14', 'delivered', NULL, NULL),
(7, 9, 3074.81, '2025-05-02 15:13:05', 'delivered', NULL, NULL),
(8, 9, 3449.92, '2025-05-04 22:47:05', 'delivered', NULL, NULL),
(9, 2, 234.56, '2025-05-09 23:36:00', 'pending', NULL, NULL),
(11, NULL, 5000.00, '2025-05-06 14:24:37', 'delivered', NULL, NULL),
(12, NULL, 20000.00, '2025-05-06 14:32:49', 'pending', NULL, NULL),
(13, 9, 1274.97, '2025-05-07 12:44:01', 'pending', 1, NULL),
(14, 9, 13349.70, '2025-05-07 14:45:45', 'pending', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`) VALUES
(1, 2, 4, 1, 849.99),
(2, 2, 3, 1, 249.99),
(3, 2, 2, 11, 149.99),
(4, 3, 1, 3, 74.99),
(5, 3, 2, 6, 149.99),
(6, 3, 4, 5, 849.99),
(7, 4, 2, 5, 149.99),
(8, 4, 4, 2, 849.99),
(9, 4, 3, 1, 249.99),
(10, 4, 1, 1, 74.99),
(11, 7, 1, 5, 74.99),
(12, 7, 2, 8, 149.99),
(13, 7, 3, 6, 249.99),
(14, 8, 1, 2, 74.99),
(15, 8, 3, 3, 249.99),
(16, 8, 4, 3, 849.99),
(17, 13, 1, 1, 74.99),
(18, 13, 3, 1, 249.99),
(19, 13, 4, 1, 849.99),
(20, 13, 5, 1, 100.00),
(21, 14, 1, 6, 74.99),
(22, 14, 2, 7, 149.99),
(23, 14, 3, 6, 249.99),
(24, 14, 4, 11, 849.99),
(25, 14, 5, 10, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('customer','sales','manager','customs') DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `phone` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `role`, `warehouse_id`, `created_at`, `last_login`, `phone`) VALUES
(1, 'jaykalobwe18@gmail.com', '$2y$10$MrJWkORbQgkwEyrR2Aw9bukxggno4TcPYQe7BlwmTSykPTcfVl.bO', 'John', 'Doe', 'customer', 0, '2025-04-29 13:34:01', '2025-05-04 22:27:11', 972483388),
(2, 'employee@example.com', '$2y$10$qpTirFDSyu7GYJeDWH2QPOSNzixEqVHh8V/hyElsj/ymWiqHoRvHu', 'Jane', 'Smith', 'sales', 0, '2025-04-29 13:34:01', '2025-05-04 22:27:11', 555),
(3, 'manager@example.com', '$2y$10$F.dvWcUw9xxzfq61I9Ibz.dhRkQW1wOClbQmK16IZy9vXSQrNm9Mq', 'Michael', 'Johnson', 'manager', 0, '2025-04-29 13:34:01', '2025-05-04 22:27:11', 555),
(5, 'employee@zedauto.com', '$2y$10$rFJGhyEZkHKiNrAuGv3WxOBcysx7rzm1BnqbZ9HKSjwXm/Qw3nYPK', 'Test', 'Employee', 'customer', 0, '2025-04-30 14:18:12', '2025-05-04 22:27:11', 2147483647),
(7, 'admin1@zedauto.com', '$2y$10$rFJGhyEZkHKiNrAuGv3WxOBcysx7rzm1BnqbZ9HKSjwXm/Qw3nYPK', 'Admin', 'User', 'manager', 0, '2025-04-30 14:29:18', '2025-05-04 22:27:11', 2147483647),
(9, 'employee1@zedauto.com', '$2y$10$3FmhaW0hqHhOkwAc13qGbe62j8dUJUkENEV6WDPoM6ijZYdsEDMX6', 'Daniel', 'Kay', 'sales', 0, '2025-05-02 14:58:40', '2025-05-04 22:27:11', 0),
(13, 'customs@zedauto.com', '$2y$10$3y4dGxbCgctYVC5Z0YXptu8KSOCD6USVK4xO2EnPSxyfR0n9fB9t6', 'Eu', 'Customs', 'customs', 0, '2025-05-04 23:22:20', NULL, 0);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `log_user_login` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.last_login IS NOT NULL THEN
        INSERT INTO login_logs (user_id) VALUES (NEW.id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `logs_login` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.last_login IS NOT NULL THEN
        INSERT INTO login_logs (user_id) VALUES (NEW.id);
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `dispatch_orders`
--
ALTER TABLE `dispatch_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `employee_details`
--
ALTER TABLE `employee_details`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`part_id`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_customer_id` (`customer_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_warehouse_id` (`warehouse_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispatch_orders`
--
ALTER TABLE `dispatch_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `users` (`warehouse_id`);

--
-- Constraints for table `customer_details`
--
ALTER TABLE `customer_details`
  ADD CONSTRAINT `customer_details_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispatch_orders`
--
ALTER TABLE `dispatch_orders`
  ADD CONSTRAINT `dispatch_orders_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `users` (`warehouse_id`),
  ADD CONSTRAINT `dispatch_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `dispatch_orders_ibfk_3` FOREIGN KEY (`part_id`) REFERENCES `inventory` (`part_id`);

--
-- Constraints for table `employee_details`
--
ALTER TABLE `employee_details`
  ADD CONSTRAINT `employee_details_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory` (`part_id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

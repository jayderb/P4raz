-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 02:55 AM
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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `warehouse_id`, `action`, `details`, `timestamp`) VALUES
(1, 1, 'Stock Updated', 'Added 100 Brake Pads to inventory', '2025-05-16 20:51:11'),
(2, 1, 'Order Received', 'Order ID 1 placed for 10 Brake Pads', '2025-05-16 20:51:11'),
(3, 1, 'Order Dispatched', 'Order ID 1 dispatched', '2025-05-16 20:58:23'),
(4, 1, 'Order Dispatched', 'Order ID 2 dispatched', '2025-05-16 21:00:01'),
(5, 1, 'Order Dispatched', 'Order ID 4 dispatched', '2025-05-16 21:01:43'),
(6, 1, 'Order Dispatched', 'Order ID 3 dispatched', '2025-05-16 21:03:02'),
(7, 1, 'Stock Added', 'Added 30 Muffler (Part ID: 4) to warehouse 1', '2025-05-16 21:52:09'),
(8, 1, 'Stock Added', 'Added 50 Toyota Runx Engine (Part ID: 5) to warehouse 1', '2025-05-16 23:53:22'),
(9, 1, 'Stock Added', 'Added 50 Toyota Runx Engine (Part ID: 6) to warehouse 1', '2025-05-16 23:54:15'),
(10, 1, 'Stock Added', 'Added 50 Toyota Runx Engine (Part ID: 7) to warehouse 1', '2025-05-16 23:54:43'),
(11, 1, 'Stock Added', 'Added 50 Toyota Runx Engine (Part ID: 8) to warehouse 1', '2025-05-16 23:55:18'),
(12, 1, 'Order Added', 'Order for 5 of Part ID 5 for customer 33 in warehouse 1', '2025-05-17 00:15:38'),
(13, 1, 'Order Dispatched', 'Order ID 5 dispatched', '2025-05-17 00:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `brand`, `model`, `price`, `stock_quantity`) VALUES
(1, 'toyota', 'Corolla', 250000.00, 5),
(2, 'toyota', 'Camry', 350000.00, 3),
(3, 'mercedes', 'C-Class', 600000.00, 2),
(4, 'bmw', '3 Series', 550000.00, 4);

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

--
-- Dumping data for table `dispatch_orders`
--

INSERT INTO `dispatch_orders` (`order_id`, `warehouse_id`, `customer_id`, `part_id`, `quantity`, `status`, `order_date`) VALUES
(1, 1, 1, 1, 5, 'dispatched', '2025-05-15 09:17:48'),
(2, 1, 1, 2, 3, 'dispatched', '2025-05-15 09:17:48'),
(3, 1, 1, 1, 10, 'dispatched', '2025-05-16 20:50:21'),
(4, 1, 1, 2, 5, 'dispatched', '2025-05-16 20:50:21'),
(5, 1, 33, 5, 5, 'dispatched', '2025-05-17 00:15:38');

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

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`part_id`, `warehouse_id`, `part_name`, `description`, `category`, `quantity`, `unit_price`, `reorder_level`, `supplier_id`, `last_updated`) VALUES
(1, 1, 'Oil Filter', NULL, NULL, 50, 74.99, 10, NULL, '2025-05-15 09:17:21'),
(2, 1, 'Brake Pads', NULL, NULL, 30, 149.99, 5, NULL, '2025-05-15 09:17:21'),
(3, 1, 'Car Battery', NULL, NULL, 20, 249.99, 8, NULL, '2025-05-15 09:17:21'),
(4, 1, 'Muffler', NULL, NULL, 30, 850.00, 0, NULL, '2025-05-16 21:52:09'),
(5, 1, 'Toyota Runx Engine', NULL, NULL, 45, 5200.00, 0, NULL, '2025-05-17 00:15:38');

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
(20, 1, '', '', NULL, 0, '2025-05-07 14:45:45'),
(21, 1, '', '', NULL, 0, '2025-05-15 09:18:44'),
(22, 1, '', '', NULL, 0, '2025-05-15 09:18:44'),
(23, 2, '', '', NULL, 0, '2025-05-15 09:18:44'),
(24, 2, '', '', NULL, 0, '2025-05-15 09:18:44'),
(25, 3, '', '', NULL, 0, '2025-05-15 09:18:44'),
(26, 3, '', '', NULL, 0, '2025-05-15 09:18:44'),
(27, 5, '', '', NULL, 0, '2025-05-15 09:18:44'),
(28, 5, '', '', NULL, 0, '2025-05-15 09:18:44'),
(29, 7, '', '', NULL, 0, '2025-05-15 09:18:44'),
(30, 7, '', '', NULL, 0, '2025-05-15 09:18:44'),
(31, 9, '', '', NULL, 0, '2025-05-15 09:18:44'),
(32, 9, '', '', NULL, 0, '2025-05-15 09:18:44'),
(33, 1, '', '', NULL, 0, '2025-05-15 09:19:09'),
(34, 1, '', '', NULL, 0, '2025-05-15 09:19:09'),
(35, 2, '', '', NULL, 0, '2025-05-15 09:19:09'),
(36, 2, '', '', NULL, 0, '2025-05-15 09:19:09'),
(37, 3, '', '', NULL, 0, '2025-05-15 09:19:09'),
(38, 3, '', '', NULL, 0, '2025-05-15 09:19:09'),
(39, 5, '', '', NULL, 0, '2025-05-15 09:19:10'),
(40, 5, '', '', NULL, 0, '2025-05-15 09:19:10'),
(41, 7, '', '', NULL, 0, '2025-05-15 09:19:10'),
(42, 7, '', '', NULL, 0, '2025-05-15 09:19:10'),
(43, 9, '', '', NULL, 0, '2025-05-15 09:19:10'),
(44, 9, '', '', NULL, 0, '2025-05-15 09:19:10');

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
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
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
(1, 'Oil Filter', 74.99, 17, '2025-05-01 21:50:20'),
(2, 'Brake Pads', 100.00, 9, '2025-05-01 21:50:20'),
(3, 'Car Battery', 249.99, 19, '2025-05-01 21:50:20'),
(4, 'Tire', 849.99, 18, '2025-05-01 21:50:20'),
(5, 'Spark plugs', 100.00, 52, '2025-05-06 13:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `rating`, `comment`, `created_at`) VALUES
(1, 20, 5, 'Good Service but shipping took time.', '2025-05-15 15:36:27'),
(2, 20, 5, 'Great Service', '2025-05-15 20:05:01');

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
(3, 1, 5374.86, '2025-05-01 22:08:03', 'delivered', NULL, NULL),
(4, 1, 2774.91, '2025-05-01 23:03:14', 'delivered', NULL, NULL),
(7, 9, 3074.81, '2025-05-02 15:13:05', 'delivered', NULL, NULL),
(8, 9, 3449.92, '2025-05-04 22:47:05', 'delivered', NULL, NULL),
(9, 21, 234.56, '2025-05-09 23:36:00', 'delivered', NULL, NULL),
(11, NULL, 5000.00, '2025-05-06 14:24:37', 'delivered', NULL, NULL),
(12, NULL, 20000.00, '2025-05-06 14:32:49', 'delivered', NULL, NULL),
(13, 9, 1274.97, '2025-05-07 12:44:01', 'delivered', 1, NULL),
(14, 9, 13349.70, '2025-05-07 14:45:45', 'delivered', 1, NULL),
(15, 21, 2000.00, '2025-05-15 15:40:42', 'delivered', 20, NULL),
(16, NULL, 149.99, '2025-05-15 20:33:07', 'delivered', 20, NULL),
(17, NULL, 8474.80, '2025-05-16 12:17:31', 'delivered', 30, NULL),
(18, 21, 1200.00, '2025-05-16 15:18:26', 'delivered', 20, NULL),
(19, 0, 425.11, '2025-05-16 16:28:25', 'delivered', 20, NULL),
(20, 21, 1924.89, '2025-05-16 21:04:04', 'delivered', 20, NULL),
(21, 21, 1000.00, '2025-05-16 21:05:19', 'delivered', 20, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_cars`
--

CREATE TABLE `sales_cars` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(25, 14, 5, 10, 100.00),
(26, 15, 1, 10, 74.99),
(27, 15, 2, 1, 149.99),
(28, 15, 3, 1, 249.99),
(29, 15, 5, 20, 100.00),
(30, 16, 2, 1, 149.99),
(31, 17, 1, 11, 74.99),
(32, 17, 4, 9, 849.99),
(33, 18, 1, 9, 74.99),
(34, 18, 5, 6, 100.00),
(35, 19, 1, 1, 74.99),
(36, 19, 2, 1, 100.00),
(37, 19, 3, 1, 249.99),
(38, 20, 1, 11, 74.99),
(39, 20, 5, 11, 100.00),
(40, 21, 2, 10, 100.00);

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
  `role` enum('customer','sales','manager','customs','warehouse','admin') DEFAULT NULL,
  `warehouse_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `receive_notifications` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `role`, `warehouse_id`, `created_at`, `last_login`, `phone`, `receive_notifications`) VALUES
(1, 'jaykalobwe18@gmail.com', '$2y$10$MrJWkORbQgkwEyrR2Aw9bukxggno4TcPYQe7BlwmTSykPTcfVl.bO', 'John', 'Doe', 'customer', 0, '2025-04-29 13:34:01', '2025-05-04 22:27:11', '972483388', 1),
(13, 'customs@zedauto.com', '$2y$10$3y4dGxbCgctYVC5Z0YXptu8KSOCD6USVK4xO2EnPSxyfR0n9fB9t6', 'Eu', 'Customs', 'customs', 0, '2025-05-04 23:22:20', NULL, '0987654321', 1),
(14, 'warehouse@zedauto.com', '$2y$10$Ar6n8K0GEp.LVu/M/lfKkO5SPthx2jWgCCRxM9IB5J2PmkH76gQ1y', 'Warehouse', 'User', 'warehouse', 1, '2025-05-15 09:16:03', NULL, '123456789', 1),
(19, 'manager@zedauto.com', '$2y$10$/Obg.DuZajbNyTu4iZPR2eRMcDguNQ0SAZvw7e7xgCNU/E4OS7cXC', 'Joy', 'Kapinda', 'manager', 0, '2025-05-15 09:41:22', NULL, '123456789', 1),
(20, 'customer@zedauto.com', '$2y$10$6ik1FXUZUw48WadJuPw01u4vDM0VGTjEQBpSuLwi21z4C7kRYr8xC', 'Salvio', 'Daka', 'customer', 0, '2025-05-15 09:41:22', NULL, '1234567890', 1),
(21, 'sales@zedauto.com', '$2y$10$MDReA.m.pla9ypLjXY.m5ewaVJD43gicIWGyAAYO5jD0LvUBIHjR6', 'The', 'Sean', 'sales', 0, '2025-05-15 09:41:22', NULL, '123456789', 1),
(30, 'drake@zedauto.com', '$2y$10$FWdao.GR1dURJa/DAnCP1OozfqoUofbG6Pgix6tKdhMc4gAiGxftu', 'Kondwani', 'Nyanga', 'customer', 0, '2025-05-16 11:40:07', NULL, '123456', 1),
(31, 'warehouse2@zedauto.com', '$2y$10$tPZY4VOiKoJIjKsr6IfG7.5zPG/Wd83hjjyHdRUElUh9q31OjuCCe', 'Warehouse', 'Two', 'warehouse', 2, '2025-05-16 22:06:38', NULL, '123456789', 1),
(33, 'steven@zedauto.com', '$2y$10$pfIkE.CmuSmaYcaCFcej8ujY9KhKU6LhAFYek6XHWSkhiQ0AWELaS', 'Steven', 'Mwewa', 'customer', 0, '2025-05-17 00:12:30', NULL, '0972483388', 1);

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
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

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
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- Indexes for table `sales_cars`
--
ALTER TABLE `sales_cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `car_id` (`car_id`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dispatch_orders`
--
ALTER TABLE `dispatch_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

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
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sales_cars`
--
ALTER TABLE `sales_cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory` (`part_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `sales_cars`
--
ALTER TABLE `sales_cars`
  ADD CONSTRAINT `sales_cars_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_cars_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

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

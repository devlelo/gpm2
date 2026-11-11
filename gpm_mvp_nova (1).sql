-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11/11/2026 às 19:46
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gpm_mvp_nova`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `active`, `created_at`, `updated_at`) VALUES
(1, 'cano', 'cano', '', NULL, 1, '2026-10-20 16:19:07', '2026-10-20 16:19:07'),
(2, 'fogão', 'fog-ao', '', NULL, 1, '2026-10-27 13:39:18', '2026-10-27 13:39:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_settings`
--

CREATE TABLE `company_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(180) NOT NULL DEFAULT 'GPM',
  `nif` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'AOA',
  `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `company_settings`
--

INSERT INTO `company_settings` (`id`, `company_name`, `nif`, `address`, `city`, `province`, `phone`, `email`, `logo_path`, `currency`, `delivery_fee`, `updated_at`) VALUES
(1, 'GPM', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'AOA', 0.00, '2026-10-20 16:04:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `subject` varchar(180) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `last_message_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `conversations`
--

INSERT INTO `conversations` (`id`, `order_id`, `customer_id`, `assigned_user_id`, `status`, `last_message_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'open', '2026-10-21 16:50:00', '2026-10-21 15:49:40', '2026-10-21 15:50:00'),
(2, 2, 2, NULL, 'open', '2026-10-27 17:44:57', '2026-10-27 16:34:15', '2026-10-27 16:44:57'),
(3, 3, 2, NULL, 'open', '2026-10-27 17:45:59', '2026-10-27 16:45:59', '2026-10-27 16:45:59'),
(4, 4, 2, NULL, 'open', '2026-10-29 14:11:40', '2026-10-29 13:11:22', '2026-10-29 13:11:40'),
(5, 5, 2, NULL, 'open', '2026-10-30 15:11:20', '2026-10-30 14:11:20', '2026-10-30 14:11:20'),
(6, 6, 2, NULL, 'open', '2026-10-30 15:25:20', '2026-10-30 14:25:20', '2026-10-30 14:25:20'),
(7, 7, 2, NULL, 'open', '2026-10-30 15:26:52', '2026-10-30 14:26:52', '2026-10-30 14:26:52'),
(8, 8, 2, NULL, 'open', '2026-10-30 15:46:43', '2026-10-30 14:46:43', '2026-10-30 14:46:43'),
(9, 9, 2, NULL, 'open', '2026-11-04 17:18:40', '2026-10-30 14:57:39', '2026-11-04 16:18:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_type` enum('individual','company') NOT NULL DEFAULT 'individual',
  `name` varchar(180) NOT NULL,
  `nif` varchar(50) DEFAULT NULL,
  `phone` varchar(40) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `customers`
--

INSERT INTO `customers` (`id`, `customer_type`, `name`, `nif`, `phone`, `email`, `password`, `address`, `city`, `province`, `active`, `email_verified_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'company', 'jose lelo', '006218586CA048', '955639957', 'joselelowork@gmail.com', '$2y$10$YB8TDI/bKO8NRAUmqhey9.7FZwhre1mn.xz1sY9kpg.oLkbqcucQ6', NULL, NULL, NULL, 1, NULL, '2026-10-30 15:07:20', '2026-10-21 15:48:47', '2026-10-30 14:07:20'),
(2, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', '$2y$10$2557jCoeLfS9DcZ57tWeYOkYTV8K0CJP8PwpkE9sirQYvbedffJvq', NULL, NULL, NULL, 1, NULL, '2026-10-30 15:11:02', '2026-10-27 13:48:38', '2026-10-30 14:11:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `street` varchar(180) DEFAULT NULL,
  `neighborhood` varchar(120) DEFAULT NULL,
  `reference_point` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `customer_id`, `label`, `address`, `street`, `neighborhood`, `reference_point`, `city`, `province`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 2, 'ssssss', 'egegege', 'egegege', 'ssssss', NULL, 'efwe', 'benguela', 1, '2026-10-30 14:46:43', '2026-10-30 14:52:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('customer','staff') NOT NULL,
  `customer_sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_type`, `customer_sender_id`, `user_sender_id`, `body`, `read_at`, `created_at`) VALUES
(1, 1, 'customer', 1, NULL, 'Olá. Fiz o pedido GPM-20261021-7B8FAC e gostaria de acompanhar o seu estado.', NULL, '2026-10-21 15:49:40'),
(2, 1, 'customer', 1, NULL, 'oi', NULL, '2026-10-21 15:50:00'),
(3, 2, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261027-40F214 e gostaria de acompanhar o seu estado.', NULL, '2026-10-27 16:34:15'),
(4, 2, 'customer', 2, NULL, 'Bom dia', NULL, '2026-10-27 16:42:39'),
(5, 2, 'customer', 2, NULL, 'Bom dia', NULL, '2026-10-27 16:43:52'),
(6, 2, 'customer', 2, NULL, 'ss', NULL, '2026-10-27 16:43:58'),
(7, 2, 'customer', 2, NULL, 'xx', NULL, '2026-10-27 16:44:57'),
(8, 3, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261027-64DF79 e gostaria de acompanhar o seu estado.', NULL, '2026-10-27 16:45:59'),
(9, 4, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261029-B7EC17 e gostaria de acompanhar o seu estado.', '2026-11-11 15:21:38', '2026-10-29 13:11:22'),
(10, 4, 'customer', 2, NULL, 'dfggdf', '2026-11-11 15:21:38', '2026-10-29 13:11:40'),
(11, 5, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261030-42ED55 e gostaria de acompanhar o seu estado.', NULL, '2026-10-30 14:11:20'),
(12, 6, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261030-D6E07A e gostaria de acompanhar o seu estado.', NULL, '2026-10-30 14:25:20'),
(13, 7, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261030-3486F6 e gostaria de acompanhar o seu estado.', '2026-11-06 16:53:16', '2026-10-30 14:26:52'),
(14, 8, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261030-B8D73A e gostaria de acompanhar o seu estado.', '2026-11-04 17:18:45', '2026-10-30 14:46:43'),
(15, 9, 'customer', 2, NULL, 'Olá. Fiz o pedido GPM-20261030-1919CD e gostaria de acompanhar o seu estado.', '2026-11-04 17:18:04', '2026-10-30 14:57:39'),
(16, 9, 'staff', NULL, 1, 'Boa tarde senhor x vamos responder logo', NULL, '2026-11-04 16:18:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(40) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `responsible_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_type` enum('individual','company') NOT NULL DEFAULT 'individual',
  `customer_name` varchar(180) NOT NULL,
  `customer_nif` varchar(50) DEFAULT NULL,
  `customer_phone` varchar(40) NOT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_province` varchar(100) DEFAULT NULL,
  `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(80) DEFAULT NULL,
  `payment_status` enum('pending','partial','paid','refunded') NOT NULL DEFAULT 'pending',
  `status` enum('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_street` varchar(180) DEFAULT NULL,
  `delivery_neighborhood` varchar(120) DEFAULT NULL,
  `delivery_reference` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `responsible_user_id`, `customer_type`, `customer_name`, `customer_nif`, `customer_phone`, `customer_email`, `delivery_address`, `delivery_city`, `delivery_province`, `delivery_fee`, `discount`, `subtotal`, `total`, `payment_method`, `payment_status`, `status`, `notes`, `stock_deducted`, `ordered_at`, `confirmed_at`, `completed_at`, `cancelled_at`, `created_at`, `updated_at`, `delivery_street`, `delivery_neighborhood`, `delivery_reference`) VALUES
(1, 'GPM-20261021-7B8FAC', 1, NULL, 'company', 'jose lelo', '006218586CA048', '955639957', 'joselelowork@gmail.com', 'egegege', 'efwe', NULL, 0.00, 0.00, 130000.00, 130000.00, 'dinheiro', 'pending', 'pending', 'eferg', 0, '2026-10-21 16:49:40', NULL, NULL, NULL, '2026-10-21 15:49:40', '2026-10-21 15:49:40', NULL, NULL, NULL),
(2, 'GPM-20261027-40F214', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 196664.00, 196664.00, 'transferencia', 'pending', 'pending', 'adsds', 0, '2026-10-27 17:34:15', NULL, NULL, NULL, '2026-10-27 16:34:15', '2026-10-27 16:34:15', NULL, NULL, NULL),
(3, 'GPM-20261027-64DF79', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 1000.00, 1000.00, 'dinheiro', 'pending', 'pending', 'vsdvds', 0, '2026-10-27 17:45:59', NULL, NULL, NULL, '2026-10-27 16:45:59', '2026-10-27 16:45:59', NULL, NULL, NULL),
(4, 'GPM-20261029-B7EC17', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 12222.00, 12222.00, 'dinheiro', 'pending', 'confirmed', 'efgtrrt', 0, '2026-10-29 14:11:22', '2026-10-30 15:08:00', NULL, NULL, '2026-10-29 13:11:22', '2026-10-30 14:08:00', NULL, NULL, NULL),
(5, 'GPM-20261030-42ED55', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 58888.00, 58888.00, NULL, 'pending', 'pending', NULL, 0, '2026-10-30 15:11:20', NULL, NULL, NULL, '2026-10-30 14:11:20', '2026-10-30 14:11:20', NULL, NULL, NULL),
(6, 'GPM-20261030-D6E07A', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 1000.00, 1000.00, 'dinheiro', 'pending', 'pending', 'ffgddf', 0, '2026-10-30 15:25:20', NULL, NULL, NULL, '2026-10-30 14:25:20', '2026-10-30 14:25:20', NULL, NULL, NULL),
(7, 'GPM-20261030-3486F6', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 111000.00, 111000.00, NULL, 'pending', 'pending', 'eeeeeeeee', 0, '2026-10-30 15:26:52', NULL, NULL, NULL, '2026-10-30 14:26:52', '2026-10-30 14:26:52', NULL, NULL, NULL),
(8, 'GPM-20261030-B8D73A', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 'efwe', 'benguela', 0.00, 0.00, 4000.00, 4000.00, 'multicaixa', 'pending', 'pending', 'sssssss', 0, '2026-10-30 15:46:43', NULL, NULL, NULL, '2026-10-30 14:46:43', '2026-10-30 14:46:43', NULL, NULL, NULL),
(9, 'GPM-20261030-1919CD', 2, NULL, 'individual', 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege, Bairro ssssss, efwe, Luanda. Referência: aaaaaaaaaaa', 'efwe', 'Luanda', 0.00, 0.00, 1000.00, 1000.00, NULL, 'pending', 'pending', 'aaaaa', 0, '2026-10-30 15:57:39', NULL, NULL, NULL, '2026-10-30 14:57:39', '2026-10-30 14:57:39', 'egegege', 'ssssss', 'aaaaaaaaaaa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_sku` varchar(80) DEFAULT NULL,
  `unit` varchar(40) NOT NULL DEFAULT 'unidade',
  `quantity` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `unit`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 'Cano 2t', '123', 'kg', 13.000, 10000.00, 130000.00, '2026-10-21 15:49:40'),
(2, 2, 1, 'Cano 2t', '123', 'kg', 5.000, 10000.00, 50000.00, '2026-10-27 16:34:15'),
(3, 2, 4, 'Cano 2t2', '123412', 'th', 12.000, 12222.00, 146664.00, '2026-10-27 16:34:15'),
(4, 3, 5, 'ventilador', '342', 'kg', 1.000, 1000.00, 1000.00, '2026-10-27 16:45:59'),
(5, 4, 4, 'Cano 2t2', '123412', 'th', 1.000, 12222.00, 12222.00, '2026-10-29 13:11:22'),
(6, 5, 1, 'Cano 2t', '123', 'kg', 1.000, 10000.00, 10000.00, '2026-10-30 14:11:20'),
(7, 5, 4, 'Cano 2t2', '123412', 'th', 4.000, 12222.00, 48888.00, '2026-10-30 14:11:20'),
(8, 6, 5, 'ventilador', '342', 'kg', 1.000, 1000.00, 1000.00, '2026-10-30 14:25:20'),
(9, 7, 5, 'ventilador', '342', 'kg', 111.000, 1000.00, 111000.00, '2026-10-30 14:26:52'),
(10, 8, 5, 'ventilador', '342', 'kg', 4.000, 1000.00, 4000.00, '2026-10-30 14:46:43'),
(11, 9, 5, 'ventilador', '342', 'kg', 1.000, 1000.00, 1000.00, '2026-10-30 14:57:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `user_id`, `old_status`, `new_status`, `note`, `created_at`) VALUES
(1, 1, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-21 15:49:40'),
(2, 2, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-27 16:34:15'),
(3, 3, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-27 16:45:59'),
(4, 4, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-29 13:11:22'),
(5, 4, 1, 'pending', 'confirmed', '', '2026-10-30 14:08:00'),
(6, 5, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-30 14:11:20'),
(7, 6, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-30 14:25:20'),
(8, 7, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-30 14:26:52'),
(9, 8, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-30 14:46:43'),
(10, 9, NULL, NULL, 'pending', 'Pedido criado pelo marketplace.', '2026-10-30 14:57:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(15,2) DEFAULT NULL,
  `stock_quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `min_stock` decimal(15,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(40) NOT NULL DEFAULT 'unidade',
  `main_image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `sku`, `description`, `price`, `cost_price`, `stock_quantity`, `min_stock`, `unit`, `main_image`, `active`, `featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cano 2t', 'cano-2t', '123', 'cano t21', 10000.00, NULL, 100.000, 10.000, 'kg', NULL, 1, 0, '2026-10-20 16:20:11', '2026-10-20 16:20:11'),
(4, 1, 'Cano 2t2', 'cano-2t2', '123412', '', 12222.00, NULL, 12.000, 4.000, 'th', 'uploads/produtos/e402fcab26049ef62c3c21d6e2920b5e.jpg', 1, 0, '2026-10-20 16:31:31', '2026-10-20 16:31:31'),
(5, 2, 'ventilador', 'ventilador', '342', 'wewf', 1000.00, NULL, 6.000, 120.000, 'kg', 'uploads/produtos/956114bdbd6b278ca3914294ddd3e311.webp', 1, 0, '2026-10-27 14:23:19', '2026-10-30 14:57:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(180) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `sort_order`, `created_at`) VALUES
(1, 4, 'uploads/produtos/8939eac8748bdb79c2b8b921bf35a3d8.jpg', NULL, 0, '2026-10-20 16:31:31'),
(2, 4, 'uploads/produtos/33c4758d29c595a9ed4c4402d550ad14.jpg', NULL, 0, '2026-10-20 16:31:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `proformas`
--

CREATE TABLE `proformas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `proforma_number` varchar(50) NOT NULL,
  `company_name` varchar(180) DEFAULT NULL,
  `company_nif` varchar(50) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_phone` varchar(40) DEFAULT NULL,
  `company_email` varchar(190) DEFAULT NULL,
  `customer_name` varchar(180) NOT NULL,
  `customer_nif` varchar(50) DEFAULT NULL,
  `customer_phone` varchar(40) DEFAULT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','issued','accepted','rejected','expired') NOT NULL DEFAULT 'issued',
  `issue_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `proformas`
--

INSERT INTO `proformas` (`id`, `order_id`, `proforma_number`, `company_name`, `company_nif`, `company_address`, `company_phone`, `company_email`, `customer_name`, `customer_nif`, `customer_phone`, `customer_email`, `customer_address`, `subtotal`, `delivery_fee`, `discount`, `total`, `status`, `issue_date`, `valid_until`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'PF-20261021-FCBB49', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '955639957', 'joselelowork@gmail.com', 'egegege', 130000.00, 0.00, 0.00, 130000.00, 'issued', '2026-10-21', NULL, 'Proforma criada a partir do pedido GPM-20261021-7B8FAC', NULL, '2026-10-21 15:49:40', '2026-10-21 15:49:40'),
(2, 2, 'PF-20261027-6D5BAD', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 196664.00, 0.00, 0.00, 196664.00, 'issued', '2026-10-27', NULL, 'Proforma criada a partir do pedido GPM-20261027-40F214', NULL, '2026-10-27 16:34:15', '2026-10-27 16:34:15'),
(3, 3, 'PF-20261027-64D3F2', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 1000.00, 0.00, 0.00, 1000.00, 'issued', '2026-10-27', NULL, 'Proforma criada a partir do pedido GPM-20261027-64DF79', NULL, '2026-10-27 16:45:59', '2026-10-27 16:45:59'),
(4, 4, 'PF-20261029-D0AAB5', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 12222.00, 0.00, 0.00, 12222.00, 'issued', '2026-10-29', NULL, 'Proforma criada a partir do pedido GPM-20261029-B7EC17', NULL, '2026-10-29 13:11:22', '2026-10-29 13:11:22'),
(5, 5, 'PF-20261030-9CF589', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 58888.00, 0.00, 0.00, 58888.00, 'issued', '2026-10-30', NULL, 'Proforma criada a partir do pedido GPM-20261030-42ED55', NULL, '2026-10-30 14:11:20', '2026-10-30 14:11:20'),
(6, 6, 'PF-20261030-B3162E', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 1000.00, 0.00, 0.00, 1000.00, 'issued', '2026-10-30', NULL, 'Proforma criada a partir do pedido GPM-20261030-D6E07A', NULL, '2026-10-30 14:25:20', '2026-10-30 14:25:20'),
(7, 7, 'PF-20261030-18F32A', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 111000.00, 0.00, 0.00, 111000.00, 'issued', '2026-10-30', NULL, 'Proforma criada a partir do pedido GPM-20261030-3486F6', NULL, '2026-10-30 14:26:52', '2026-10-30 14:26:52'),
(8, 8, 'PF-20261030-EC7087', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege', 4000.00, 0.00, 0.00, 4000.00, 'issued', '2026-10-30', NULL, 'Proforma criada a partir do pedido GPM-20261030-B8D73A', NULL, '2026-10-30 14:46:43', '2026-10-30 14:46:43'),
(9, 9, 'PF-20261030-F5A979', NULL, NULL, NULL, NULL, NULL, 'jose lelo', '006218586CA048', '+244955639957', 'joselelowork3@gmail.com', 'egegege, Bairro ssssss, efwe, Luanda. Referência: aaaaaaaaaaa', 1000.00, 0.00, 0.00, 1000.00, 'issued', '2026-10-30', NULL, 'Proforma criada a partir do pedido GPM-20261030-1919CD', NULL, '2026-10-30 14:57:39', '2026-10-30 14:57:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `movement_type` enum('entry','exit','sale','sale_return','adjustment_in','adjustment_out') NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `stock_before` decimal(15,3) NOT NULL,
  `stock_after` decimal(15,3) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','seller','stock_manager') NOT NULL DEFAULT 'seller',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`, `must_change_password`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'joselelo', 'joselelowork@gmail.com', '$2y$10$rGwBCYMwn1F2EDHdQS3WhuNEwZzW1YQR9Yo9fE5ShdlwQBtsEdxwC', 'admin', 1, 0, NULL, '2026-10-20 16:18:11', '2026-10-20 16:18:11');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user_date` (`user_id`,`created_at`),
  ADD KEY `idx_activity_entity` (`entity_type`,`entity_id`);

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_categories_active` (`active`);

--
-- Índices de tabela `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_messages_created_at` (`created_at`);

--
-- Índices de tabela `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_conversations_customer` (`customer_id`),
  ADD KEY `idx_conversations_assigned` (`assigned_user_id`),
  ADD KEY `idx_conversations_activity` (`last_message_at`);

--
-- Índices de tabela `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customers_name` (`name`),
  ADD KEY `idx_customers_nif` (`nif`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_email` (`email`);

--
-- Índices de tabela `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_addresses_customer` (`customer_id`);

--
-- Índices de tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_messages_customer` (`customer_sender_id`),
  ADD KEY `fk_messages_user` (`user_sender_id`),
  ADD KEY `idx_messages_conversation_date` (`conversation_id`,`created_at`),
  ADD KEY `idx_messages_unread` (`conversation_id`,`read_at`);

--
-- Índices de tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_customer_date` (`customer_id`,`ordered_at`),
  ADD KEY `idx_orders_status_date` (`status`,`ordered_at`),
  ADD KEY `idx_orders_responsible` (`responsible_user_id`);

--
-- Índices de tabela `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Índices de tabela `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_status_history_user` (`user_id`),
  ADD KEY `idx_status_history_order_date` (`order_id`,`created_at`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_category_active` (`category_id`,`active`),
  ADD KEY `idx_products_stock` (`stock_quantity`),
  ADD KEY `idx_products_featured` (`featured`,`active`);

--
-- Índices de tabela `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_images_order` (`product_id`,`sort_order`);

--
-- Índices de tabela `proformas`
--
ALTER TABLE `proformas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proforma_number` (`proforma_number`),
  ADD KEY `fk_proformas_order` (`order_id`),
  ADD KEY `fk_proformas_user` (`created_by`),
  ADD KEY `idx_proformas_issue_date` (`issue_date`),
  ADD KEY `idx_proformas_status` (`status`);

--
-- Índices de tabela `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stock_user` (`user_id`),
  ADD KEY `idx_stock_product_date` (`product_id`,`created_at`),
  ADD KEY `idx_stock_order` (`order_id`),
  ADD KEY `idx_stock_type` (`movement_type`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_active` (`role`,`active`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `proformas`
--
ALTER TABLE `proformas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conversations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversations_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversations_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `fk_customer_addresses_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_customer` FOREIGN KEY (`customer_sender_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_responsible` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_status_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `proformas`
--
ALTER TABLE `proformas`
  ADD CONSTRAINT `fk_proformas_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_proformas_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

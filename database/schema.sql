-- AvtoZapchast Database Schema
-- Generated from source code analysis

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ----------------------------
-- Users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)     NOT NULL,
  `email`         VARCHAR(150)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `role`          ENUM('buyer','manager','admin','superadmin') NOT NULL DEFAULT 'buyer',
  `phone`         VARCHAR(30)     DEFAULT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Categories
-- ----------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120)  NOT NULL,
  `slug`        VARCHAR(120)  NOT NULL,
  `parent_id`   INT UNSIGNED  DEFAULT NULL,
  `description` TEXT          DEFAULT NULL,
  `sort_order`  INT           NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Brands
-- ----------------------------
CREATE TABLE IF NOT EXISTS `brands` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `slug`        VARCHAR(100)  NOT NULL,
  `country`     VARCHAR(80)   DEFAULT NULL,
  `description` TEXT          DEFAULT NULL,
  `logo_path`   VARCHAR(255)  DEFAULT NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Parts
-- ----------------------------
CREATE TABLE IF NOT EXISTS `parts` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255)    NOT NULL,
  `part_number` VARCHAR(100)    NOT NULL,
  `brand_id`    INT UNSIGNED    DEFAULT NULL,
  `category_id` INT UNSIGNED    DEFAULT NULL,
  `price`       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `stock`       INT             NOT NULL DEFAULT 0,
  `weight`      DECIMAL(8,3)    DEFAULT NULL,
  `dimensions`  VARCHAR(100)    DEFAULT NULL,
  `description` TEXT            DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `category_id` (`category_id`),
  KEY `part_number` (`part_number`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Cart
-- ----------------------------
CREATE TABLE IF NOT EXISTS `cart` (
  `id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`  INT UNSIGNED  NOT NULL,
  `part_id`  INT UNSIGNED  NOT NULL,
  `quantity` INT           NOT NULL DEFAULT 1,
  `added_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_part` (`user_id`, `part_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Orders
-- ----------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED   NOT NULL,
  `total_amount`     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `shipping_address` TEXT           NOT NULL,
  `notes`            TEXT           DEFAULT NULL,
  `status`           ENUM('pending','confirmed','processing','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Order Items
-- ----------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED   NOT NULL,
  `part_id`    INT UNSIGNED   NOT NULL,
  `quantity`   INT            NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2)  NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Site Settings
-- ----------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(80)   NOT NULL,
  `value`      TEXT          DEFAULT NULL,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Exchange Rates
-- ----------------------------
CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `currency`   VARCHAR(10)    NOT NULL,
  `name`       VARCHAR(80)    NOT NULL,
  `symbol`     VARCHAR(10)    NOT NULL DEFAULT '',
  `rate`       DECIMAL(12,6)  NOT NULL DEFAULT 1.000000,
  `is_active`  TINYINT(1)     NOT NULL DEFAULT 1,
  `updated_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Warehouse Cache
-- ----------------------------
CREATE TABLE IF NOT EXISTS `warehouse_cache` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `part_number`     VARCHAR(100)   NOT NULL,
  `warehouse_stock` INT            NOT NULL DEFAULT 0,
  `warehouse_price` DECIMAL(12,2)  DEFAULT NULL,
  `warehouse_eta`   VARCHAR(100)   DEFAULT NULL,
  `raw_response`    TEXT           DEFAULT NULL,
  `last_checked`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_number` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ----------------------------
-- Default Data
-- ----------------------------

-- Default currencies
INSERT IGNORE INTO `exchange_rates` (`currency`, `name`, `symbol`, `rate`, `is_active`) VALUES
('RUB', 'Российский рубль', '₽', 1.000000, 1),
('USD', 'Доллар США',       '$', 90.000000, 1),
('EUR', 'Евро',             '€', 98.000000, 1),
('TJS', 'Таджикский сомони','с', 8.500000,  1),
('KZT', 'Казахский тенге',  '₸', 0.200000,  1);

-- Default settings
INSERT IGNORE INTO `site_settings` (`key`, `value`) VALUES
('site_name',        'АвтоЗапчасть'),
('site_email',       'info@avtozapchast.ru'),
('site_phone',       '+7 (800) 555-35-35'),
('site_address',     'г. Москва, ул. Автозаводская, 1'),
('default_currency', 'RUB'),
('items_per_page',   '12'),
('warehouse_enabled','0'),
('working_hours',    'Пн–Пт: 9:00–20:00');

-- Default superadmin (password: Password123!)
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`) VALUES
('superadmin', 'superadmin@avtozapchast.ru', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1),
('admin',      'admin@avtozapchast.ru',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      1),
('manager',    'manager@avtozapchast.ru',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager',    1),
('buyer',      'buyer@avtozapchast.ru',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer',      1);

-- Sample categories
INSERT IGNORE INTO `categories` (`name`, `slug`, `parent_id`, `sort_order`, `is_active`) VALUES
('Двигатель',          'dvigatel',       NULL, 1, 1),
('Тормозная система',  'tormoza',        NULL, 2, 1),
('Подвеска',           'podveska',       NULL, 3, 1),
('Трансмиссия',        'transmissiya',   NULL, 4, 1),
('Кузов и оптика',     'kuzov',          NULL, 5, 1),
('Электрика',          'elektrika',      NULL, 6, 1),
('Фильтры',            'filtry',         NULL, 7, 1),
('Масла и жидкости',   'masla',          NULL, 8, 1);

-- Sample brands
INSERT IGNORE INTO `brands` (`name`, `slug`, `country`, `is_active`) VALUES
('Bosch',       'bosch',       'Германия',   1),
('NGK',         'ngk',         'Япония',     1),
('Sachs',       'sachs',       'Германия',   1),
('Febi',        'febi',        'Германия',   1),
('SKF',         'skf',         'Швеция',     1),
('Gates',       'gates',       'США',        1),
('Mann',        'mann',        'Германия',   1),
('LUK',         'luk',         'Германия',   1);

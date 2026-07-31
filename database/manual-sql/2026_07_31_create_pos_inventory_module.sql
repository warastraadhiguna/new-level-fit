-- POS & Inventory per Branch Store
-- Jalankan satu kali melalui phpMyAdmin pada database perusahaan tujuan.
-- Default fitur NONAKTIF. Aktifkan melalui Secret Branch Store setelah aplikasi di-deploy.

ALTER TABLE `branch_stores`
    ADD COLUMN `pos_inventory_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trainer_discount_enabled`;

CREATE TABLE `pos_product_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_product_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `sku` VARCHAR(50) NOT NULL,
  `barcode` VARCHAR(100) NULL,
  `name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_products_sku_unique` (`sku`),
  UNIQUE KEY `pos_products_barcode_unique` (`barcode`),
  KEY `pos_products_category_id_foreign` (`category_id`),
  CONSTRAINT `pos_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `pos_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(150) NULL,
  `address` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_branch_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_store_id` SMALLINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `stock_qty` DECIMAL(14,3) NOT NULL DEFAULT 0,
  `average_cost` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `minimum_stock` DECIMAL(14,3) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_branch_product_unique` (`branch_store_id`,`product_id`),
  KEY `pos_branch_products_product_id_foreign` (`product_id`),
  CONSTRAINT `pos_branch_products_branch_store_id_foreign` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `pos_branch_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_purchases` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_store_id` SMALLINT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `received_by` INT UNSIGNED NULL,
  `purchase_number` VARCHAR(50) NOT NULL,
  `supplier_invoice_number` VARCHAR(100) NULL,
  `purchase_date` DATE NOT NULL,
  `received_at` DATETIME NULL,
  `status` ENUM('draft','received','cancelled') NOT NULL DEFAULT 'draft',
  `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `idempotency_key` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_purchases_purchase_number_unique` (`purchase_number`),
  UNIQUE KEY `pos_purchases_idempotency_key_unique` (`idempotency_key`),
  KEY `pos_purchases_branch_store_id_foreign` (`branch_store_id`),
  KEY `pos_purchases_supplier_id_foreign` (`supplier_id`),
  KEY `pos_purchases_created_by_foreign` (`created_by`),
  KEY `pos_purchases_received_by_foreign` (`received_by`),
  CONSTRAINT `pos_purchases_branch_store_id_foreign` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `pos_purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `pos_suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pos_purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pos_purchases_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_purchase_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(14,3) NOT NULL,
  `unit_cost` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `pos_purchase_items_product_id_foreign` (`product_id`),
  CONSTRAINT `pos_purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `pos_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_stock_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_store_id` SMALLINT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `adjustment_number` VARCHAR(50) NOT NULL,
  `reason` TEXT NOT NULL,
  `idempotency_key` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_stock_adjustments_number_unique` (`adjustment_number`),
  UNIQUE KEY `pos_stock_adjustments_idempotency_key_unique` (`idempotency_key`),
  KEY `pos_stock_adjustments_branch_store_id_foreign` (`branch_store_id`),
  KEY `pos_stock_adjustments_created_by_foreign` (`created_by`),
  CONSTRAINT `pos_stock_adjustments_branch_store_id_foreign` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `pos_stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_stock_adjustment_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `adjustment_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity_change` DECIMAL(14,3) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_stock_adjustment_items_adjustment_id_foreign` (`adjustment_id`),
  KEY `pos_stock_adjustment_items_product_id_foreign` (`product_id`),
  CONSTRAINT `pos_stock_adjustment_items_adjustment_id_foreign` FOREIGN KEY (`adjustment_id`) REFERENCES `pos_stock_adjustments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_store_id` SMALLINT UNSIGNED NOT NULL,
  `cashier_id` INT UNSIGNED NOT NULL,
  `sale_number` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(150) NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(15,2) NOT NULL,
  `paid_amount` DECIMAL(15,2) NOT NULL,
  `change_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status` ENUM('completed','void') NOT NULL DEFAULT 'completed',
  `notes` TEXT NULL,
  `idempotency_key` VARCHAR(64) NOT NULL,
  `voided_at` DATETIME NULL,
  `voided_by` INT UNSIGNED NULL,
  `void_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_sales_sale_number_unique` (`sale_number`),
  UNIQUE KEY `pos_sales_idempotency_key_unique` (`idempotency_key`),
  KEY `pos_sales_branch_store_id_foreign` (`branch_store_id`),
  KEY `pos_sales_cashier_id_foreign` (`cashier_id`),
  KEY `pos_sales_voided_by_foreign` (`voided_by`),
  CONSTRAINT `pos_sales_branch_store_id_foreign` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `pos_sales_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `pos_sales_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_sale_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(50) NOT NULL,
  `quantity` DECIMAL(14,3) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `unit_cost` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_items_sale_id_foreign` (`sale_id`),
  KEY `pos_sale_items_product_id_foreign` (`product_id`),
  CONSTRAINT `pos_sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_sale_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `method_payment_id` INT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `reference_number` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_payments_sale_id_foreign` (`sale_id`),
  KEY `pos_sale_payments_method_payment_id_foreign` (`method_payment_id`),
  CONSTRAINT `pos_sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pos_sale_payments_method_payment_id_foreign` FOREIGN KEY (`method_payment_id`) REFERENCES `method_payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pos_inventory_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_store_id` SMALLINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `movement_type` ENUM('purchase','sale','adjustment','sale_void') NOT NULL,
  `quantity_before` DECIMAL(14,3) NOT NULL,
  `quantity_change` DECIMAL(14,3) NOT NULL,
  `quantity_after` DECIMAL(14,3) NOT NULL,
  `unit_cost` DECIMAL(15,2) NOT NULL,
  `reference_type` VARCHAR(50) NOT NULL,
  `reference_id` BIGINT UNSIGNED NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_movement_lookup` (`branch_store_id`,`product_id`,`created_at`),
  KEY `pos_movement_reference` (`reference_type`,`reference_id`),
  KEY `pos_inventory_movements_product_id_foreign` (`product_id`),
  KEY `pos_inventory_movements_created_by_foreign` (`created_by`),
  CONSTRAINT `pos_inventory_movements_branch_store_id_foreign` FOREIGN KEY (`branch_store_id`) REFERENCES `branch_stores` (`id`),
  CONSTRAINT `pos_inventory_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `pos_products` (`id`),
  CONSTRAINT `pos_inventory_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


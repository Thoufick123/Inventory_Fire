-- -------------------------------------------------------------
-- Migration: Shop Cash & Cash Flow Register System
-- Adds tables for till shifts, cash transactions, and bank drops
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `shop_shifts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT UNSIGNED NULL,
    `shift_code` VARCHAR(50) NOT NULL UNIQUE,
    `cashier_id` INT UNSIGNED NOT NULL,
    `opening_float` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME DEFAULT NULL,
    `closed_by` INT UNSIGNED DEFAULT NULL,
    `expected_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `actual_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `variance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `denominations` JSON DEFAULT NULL,
    `closing_notes` TEXT DEFAULT NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_shift_concern_status` (`concern_id`, `status`),
    INDEX `idx_shift_opened_at` (`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cash_flow_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT UNSIGNED NULL,
    `shift_id` INT UNSIGNED NULL,
    `tx_number` VARCHAR(50) NOT NULL UNIQUE,
    `transaction_type` ENUM('FLOAT_OPEN', 'FLOAT_TOPUP', 'PETTY_EXPENSE', 'BANK_DROP', 'SALE_INFLOW', 'REFUND_OUTFLOW') NOT NULL,
    `flow` ENUM('INFLOW', 'OUTFLOW') NOT NULL,
    `payment_mode` VARCHAR(50) NOT NULL DEFAULT 'CASH',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `source_or_recipient` VARCHAR(255) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `reason` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cftx_concern` (`concern_id`),
    INDEX `idx_cftx_shift` (`shift_id`),
    INDEX `idx_cftx_type` (`transaction_type`),
    INDEX `idx_cftx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_deposits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT UNSIGNED NULL,
    `shift_id` INT UNSIGNED NULL,
    `deposit_number` VARCHAR(50) NOT NULL UNIQUE,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `bank_name` VARCHAR(150) NOT NULL,
    `deposit_date` DATE NOT NULL,
    `slip_number` VARCHAR(100) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bd_concern` (`concern_id`),
    INDEX `idx_bd_shift` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

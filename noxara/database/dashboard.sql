-- NOXARA Investment Platform Database
-- Engine: InnoDB | Charset: utf8mb4 | Collate: utf8mb4_unicode_ci
-- DO NOT add CREATE DATABASE or USE statements

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE: admin_users
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('superadmin','cs','finance') NOT NULL DEFAULT 'cs',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `two_fa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_fa_secret` VARCHAR(100) DEFAULT NULL,
  `failed_login_count` TINYINT NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `last_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `pin` VARCHAR(255) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `referral_code` VARCHAR(20) NOT NULL,
  `referred_by` INT UNSIGNED DEFAULT NULL,
  `vip_level` TINYINT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `block_reason` TEXT DEFAULT NULL,
  `is_frozen` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_referral_code` (`referral_code`),
  KEY `idx_referred_by` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: user_wallets
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `main_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `free_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_deposit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_withdraw` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_profit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_referral` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallet_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_token` (`session_token`),
  KEY `idx_session_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_login_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `username_attempt` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `status` ENUM('success','failed','blocked') NOT NULL DEFAULT 'failed',
  `failed_count` TINYINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_user` (`user_id`),
  KEY `idx_login_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: vip_levels
-- ============================================================
CREATE TABLE IF NOT EXISTS `vip_levels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` TINYINT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `min_deposit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `min_withdraw` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `withdraw_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_withdraw_daily` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `description` TEXT DEFAULT NULL,
  `badge_color` VARCHAR(20) DEFAULT '#00D4FF',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vip_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: vip_codes
-- ============================================================
CREATE TABLE IF NOT EXISTS `vip_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vip_level` TINYINT NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `usage_limit` INT NOT NULL DEFAULT 0,
  `used_count` INT NOT NULL DEFAULT 0,
  `expired_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vip_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: bank_accounts (user)
-- ============================================================
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bank_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_bank_accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_bank_accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: deposits
-- ============================================================
CREATE TABLE IF NOT EXISTS `deposits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `admin_bank_id` INT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `unique_code` INT NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `voucher_id` INT UNSIGNED DEFAULT NULL,
  `voucher_discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `status` ENUM('pending','confirmed','rejected','expired') NOT NULL DEFAULT 'pending',
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `admin_note` TEXT DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `expired_at` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_deposit_user` (`user_id`),
  KEY `idx_deposit_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: withdrawals
-- ============================================================
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `bank_account_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending','approved','rejected','processing') NOT NULL DEFAULT 'pending',
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `admin_note` TEXT DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wd_user` (`user_id`),
  KEY `idx_wd_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `wallet_type` ENUM('main','free') NOT NULL DEFAULT 'main',
  `direction` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `balance_before` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance_after` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tx_user` (`user_id`),
  KEY `idx_tx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: transaction_ledger
-- ============================================================
CREATE TABLE IF NOT EXISTS `transaction_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `wallet_type` ENUM('main','free') NOT NULL DEFAULT 'main',
  `transaction_type` VARCHAR(60) NOT NULL,
  `direction` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `balance_before` DECIMAL(15,2) NOT NULL,
  `balance_after` DECIMAL(15,2) NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ledger_user` (`user_id`),
  KEY `idx_ledger_type` (`transaction_type`),
  KEY `idx_ledger_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: product_categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(15,2) NOT NULL,
  `profit_per_day` DECIMAL(15,2) NOT NULL,
  `duration_days` INT NOT NULL DEFAULT 30,
  `total_roi` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `min_vip_level` TINYINT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prod_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_products
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `price_paid` DECIMAL(15,2) NOT NULL,
  `profit_per_day` DECIMAL(15,2) NOT NULL,
  `duration_days` INT NOT NULL,
  `days_claimed` INT NOT NULL DEFAULT 0,
  `total_profit_earned` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `wallet_used` ENUM('main','free','mixed') NOT NULL DEFAULT 'main',
  `free_amount_used` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `main_amount_used` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','expired','completed') NOT NULL DEFAULT 'active',
  `last_claim_date` DATE DEFAULT NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` DATETIME DEFAULT NULL,
  `notified_3days` TINYINT(1) NOT NULL DEFAULT 0,
  `notified_1day` TINYINT(1) NOT NULL DEFAULT 0,
  `modal_returned` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_up_user` (`user_id`),
  KEY `idx_up_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: mining_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `mining_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `user_product_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `claim_date` DATE NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ml_user` (`user_id`),
  KEY `idx_ml_date` (`claim_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: referrals
-- ============================================================
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id` INT UNSIGNED NOT NULL,
  `referred_id` INT UNSIGNED NOT NULL,
  `level` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_referral` (`referrer_id`,`referred_id`),
  KEY `idx_ref_referrer` (`referrer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: commissions
-- ============================================================
CREATE TABLE IF NOT EXISTS `commissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `from_user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('deposit','purchase') NOT NULL,
  `level` TINYINT NOT NULL,
  `base_amount` DECIMAL(15,2) NOT NULL,
  `percent` DECIMAL(5,2) NOT NULL,
  `commission_amount` DECIMAL(15,2) NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('pending','paid') NOT NULL DEFAULT 'paid',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comm_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: commission_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `commission_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('deposit','purchase') NOT NULL,
  `level` TINYINT NOT NULL,
  `percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comm_type_level` (`type`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: vouchers
-- ============================================================
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('deposit','product') NOT NULL DEFAULT 'deposit',
  `discount_type` ENUM('percent','nominal') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `min_vip_level` TINYINT NOT NULL DEFAULT 0,
  `min_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `usage_limit` INT NOT NULL DEFAULT 0,
  `used_count` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `expired_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_vouchers
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_vouchers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uv_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: ads
-- ============================================================
CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `reward_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `reward_wallet` ENUM('main','free') NOT NULL DEFAULT 'free',
  `watch_duration` INT NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ad_watches
-- ============================================================
CREATE TABLE IF NOT EXISTS `ad_watches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `ad_id` INT UNSIGNED NOT NULL,
  `reward_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `watch_date` DATE NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aw_user` (`user_id`),
  KEY `idx_aw_date` (`watch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ad_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `ad_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `max_ads_per_day` INT NOT NULL DEFAULT 10,
  `cooldown_seconds` INT NOT NULL DEFAULT 60,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: daily_reward_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `daily_reward_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `reset_hour` TINYINT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: daily_reward_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `daily_reward_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('free_balance','extra_ad','boost_profit','jackpot') NOT NULL DEFAULT 'free_balance',
  `value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `probability` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_daily_claims
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_daily_claims` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `reward_item_id` INT UNSIGNED NOT NULL,
  `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `claim_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_udc_user` (`user_id`),
  KEY `idx_udc_date` (`claim_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: missions
-- ============================================================
CREATE TABLE IF NOT EXISTS `missions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `type` ENUM('daily','weekly','milestone') NOT NULL DEFAULT 'daily',
  `action_type` VARCHAR(50) NOT NULL,
  `target_value` INT NOT NULL DEFAULT 1,
  `reward_type` ENUM('free_balance','voucher') NOT NULL DEFAULT 'free_balance',
  `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `reward_voucher_id` INT UNSIGNED DEFAULT NULL,
  `min_vip_level` TINYINT NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_missions
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_missions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `mission_id` INT UNSIGNED NOT NULL,
  `current_value` INT NOT NULL DEFAULT 0,
  `status` ENUM('in_progress','completed','claimed') NOT NULL DEFAULT 'in_progress',
  `completed_at` DATETIME DEFAULT NULL,
  `claimed_at` DATETIME DEFAULT NULL,
  `period_start` DATE DEFAULT NULL,
  `period_end` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_um_user` (`user_id`),
  KEY `idx_um_mission` (`mission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: chat_rooms
-- ============================================================
CREATE TABLE IF NOT EXISTS `chat_rooms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `last_message_at` DATETIME DEFAULT NULL,
  `user_unread` INT NOT NULL DEFAULT 0,
  `admin_unread` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: chat_messages
-- ============================================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('user','admin') NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `message` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cm_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: chat_templates
-- ============================================================
CREATE TABLE IF NOT EXISTS `chat_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bell',
  `action_url` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_broadcast` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notification_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fonnte_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `fonnte_token` VARCHAR(255) DEFAULT NULL,
  `wa_deposit_template` TEXT DEFAULT NULL,
  `wa_withdraw_template` TEXT DEFAULT NULL,
  `wa_profit_template` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: banners
-- ============================================================
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `type` ENUM('info','warning','success','danger') NOT NULL DEFAULT 'info',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `expired_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: platform_info
-- ============================================================
CREATE TABLE IF NOT EXISTS `platform_info` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pi_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: contact_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `contact_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `whatsapp` VARCHAR(50) DEFAULT NULL,
  `telegram` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `instagram` VARCHAR(100) DEFAULT NULL,
  `facebook` VARCHAR(100) DEFAULT NULL,
  `twitter` VARCHAR(100) DEFAULT NULL,
  `youtube` VARCHAR(100) DEFAULT NULL,
  `tiktok` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: popup_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `popup_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_key` VARCHAR(100) NOT NULL,
  `title` VARCHAR(200) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `type` ENUM('success','error','warning','info') NOT NULL DEFAULT 'info',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `duration` INT NOT NULL DEFAULT 4000,
  `button_text` VARCHAR(100) DEFAULT NULL,
  `button_url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_popup_key` (`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: marquee_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `marquee_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `speed` INT NOT NULL DEFAULT 40,
  `color` VARCHAR(20) NOT NULL DEFAULT '#00D4FF',
  `include_deposits` TINYINT(1) NOT NULL DEFAULT 1,
  `include_purchases` TINYINT(1) NOT NULL DEFAULT 1,
  `include_vip_upgrades` TINYINT(1) NOT NULL DEFAULT 1,
  `custom_messages` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: leaderboard_cache
-- ============================================================
CREATE TABLE IF NOT EXISTS `leaderboard_cache` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('deposit','referral','profit') NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rank` INT NOT NULL,
  `value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `period` VARCHAR(20) NOT NULL DEFAULT 'all',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lb_type` (`type`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `target_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `status` ENUM('success','failed') NOT NULL DEFAULT 'success',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_admin` (`admin_id`),
  KEY `idx_al_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: cron_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `cron_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cron_name` VARCHAR(100) NOT NULL,
  `status` ENUM('success','failed','running') NOT NULL DEFAULT 'running',
  `records_processed` INT NOT NULL DEFAULT 0,
  `message` TEXT DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `finished_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cl_name` (`cron_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: password_resets
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `expired_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_token` (`token`),
  KEY `idx_pr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: backup_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `filesize` BIGINT NOT NULL DEFAULT 0,
  `type` ENUM('manual','auto') NOT NULL DEFAULT 'auto',
  `status` ENUM('success','failed') NOT NULL DEFAULT 'success',
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: legal_pages
-- ============================================================
CREATE TABLE IF NOT EXISTS `legal_pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_legal_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_security_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_security_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: install_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `install_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `step` VARCHAR(100) NOT NULL,
  `status` ENUM('success','failed') NOT NULL DEFAULT 'success',
  `message` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin default (password: Admin@123456)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`, `is_active`) VALUES
('superadmin', 'admin@noxara.page', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'superadmin', 1);

-- VIP Levels
INSERT INTO `vip_levels` (`level`, `name`, `min_deposit`, `min_withdraw`, `withdraw_fee_percent`, `max_withdraw_daily`, `description`, `badge_color`) VALUES
(0, 'VIP 0 - Bronze', 0.00, 100000.00, 15.00, 5000000.00, 'Member baru bergabung', '#CD7F32'),
(1, 'VIP 1 - Silver', 500000.00, 50000.00, 10.00, 10000000.00, 'Deposit kumulatif Rp 500.000', '#C0C0C0'),
(2, 'VIP 2 - Gold', 2000000.00, 40000.00, 7.00, 20000000.00, 'Deposit kumulatif Rp 2.000.000', '#FFD700'),
(3, 'VIP 3 - Platinum', 5000000.00, 30000.00, 5.00, 50000000.00, 'Deposit kumulatif Rp 5.000.000', '#E5E4E2'),
(4, 'VIP 4 - Diamond', 15000000.00, 20000.00, 3.00, 100000000.00, 'Deposit kumulatif Rp 15.000.000', '#00D4FF'),
(5, 'VIP 5 - Obsidian', 50000000.00, 10000.00, 1.00, 0.00, 'Deposit kumulatif Rp 50.000.000 - Tanpa batas', '#7B2FFF');

-- Product Categories
INSERT INTO `product_categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Mining Pemula', 'mining-pemula', 'Paket mining untuk pemula dengan modal terjangkau', 1),
('Mining Menengah', 'mining-menengah', 'Paket mining menengah dengan profit lebih besar', 2),
('Mining Premium', 'mining-premium', 'Paket mining premium dengan profit tertinggi', 3);

-- Products (12 produk mining)
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `profit_per_day`, `duration_days`, `total_roi`, `min_vip_level`, `is_active`, `sort_order`) VALUES
(1, 'STONE I', 'Paket mining pemula level 1. Cocok untuk mulai berinvestasi.', 50000.00, 2500.00, 30, 75000.00, 0, 1, 1),
(1, 'STONE II', 'Paket mining pemula level 2. Profit lebih besar dari STONE I.', 100000.00, 5500.00, 30, 165000.00, 0, 1, 2),
(1, 'STONE III', 'Paket mining pemula level 3. Ideal untuk investasi awal.', 200000.00, 12000.00, 30, 360000.00, 0, 1, 3),
(1, 'STONE IV', 'Paket mining pemula level 4. Terbaik di kategori pemula.', 500000.00, 32000.00, 30, 960000.00, 1, 1, 4),
(2, 'IRON I', 'Paket mining menengah level 1. ROI kompetitif.', 1000000.00, 70000.00, 30, 2100000.00, 1, 1, 1),
(2, 'IRON II', 'Paket mining menengah level 2. Profit konsisten setiap hari.', 2000000.00, 148000.00, 30, 4440000.00, 1, 1, 2),
(2, 'IRON III', 'Paket mining menengah level 3. Cocok untuk investor aktif.', 5000000.00, 385000.00, 30, 11550000.00, 2, 1, 3),
(2, 'IRON IV', 'Paket mining menengah level 4. Terbaik di kategori menengah.', 10000000.00, 800000.00, 30, 24000000.00, 2, 1, 4),
(3, 'GOLD I', 'Paket mining premium level 1. Untuk investor serius.', 20000000.00, 1700000.00, 30, 51000000.00, 3, 1, 1),
(3, 'GOLD II', 'Paket mining premium level 2. Profit eksklusif setiap hari.', 50000000.00, 4500000.00, 30, 135000000.00, 3, 1, 2),
(3, 'GOLD III', 'Paket mining premium level 3. Hasil investasi maksimal.', 100000000.00, 9500000.00, 30, 285000000.00, 4, 1, 3),
(3, 'GOLD IV', 'Paket mining premium level 4. Paket terbaik NOXARA.', 250000000.00, 25000000.00, 30, 750000000.00, 5, 1, 4);

-- Commission Settings
INSERT INTO `commission_settings` (`type`, `level`, `percent`, `is_active`) VALUES
('deposit', 1, 2.00, 1),
('deposit', 2, 1.00, 1),
('deposit', 3, 0.50, 1),
('purchase', 1, 3.00, 1),
('purchase', 2, 1.50, 1),
('purchase', 3, 0.75, 1);

-- Ad Settings
INSERT INTO `ad_settings` (`max_ads_per_day`, `cooldown_seconds`, `is_enabled`) VALUES (10, 60, 1);

-- Daily Reward Settings
INSERT INTO `daily_reward_settings` (`is_enabled`, `reset_hour`) VALUES (1, 0);

-- Daily Reward Items
INSERT INTO `daily_reward_items` (`name`, `type`, `value`, `probability`, `is_active`) VALUES
('Saldo Gratis Rp 5.000', 'free_balance', 5000.00, 35.00, 1),
('Saldo Gratis Rp 10.000', 'free_balance', 10000.00, 25.00, 1),
('Saldo Gratis Rp 25.000', 'free_balance', 25000.00, 15.00, 1),
('Extra Kuota Iklan +5', 'extra_ad', 5.00, 12.00, 1),
('Boost Profit 2x 1 Hari', 'boost_profit', 2.00, 8.00, 1),
('Jackpot Rp 500.000', 'jackpot', 500000.00, 0.50, 1),
('Saldo Gratis Rp 50.000', 'free_balance', 50000.00, 4.50, 1);


-- Missions
INSERT INTO `missions` (`title`, `description`, `type`, `action_type`, `target_value`, `reward_type`, `reward_value`, `sort_order`, `is_active`) VALUES
('Login Harian', 'Login ke akun NOXARA hari ini', 'daily', 'login', 1, 'free_balance', 2000.00, 1, 1),
('Klaim Profit Harian', 'Klaim profit harian dari paket aktif', 'daily', 'claim_profit', 1, 'free_balance', 5000.00, 2, 1),
('Tonton 5 Iklan', 'Tonton minimal 5 iklan hari ini', 'daily', 'watch_ads', 5, 'free_balance', 3000.00, 3, 1),
('Login 7 Hari Berturut', 'Login selama 7 hari berturut-turut', 'weekly', 'login_streak', 7, 'free_balance', 50000.00, 1, 1),
('Klaim Profit 7 Hari', 'Klaim profit harian 7 hari berturut-turut', 'weekly', 'claim_streak', 7, 'free_balance', 100000.00, 2, 1),
('Deposit Pertama', 'Lakukan deposit pertama kali', 'milestone', 'first_deposit', 1, 'free_balance', 50000.00, 1, 1),
('Beli Paket Pertama', 'Beli paket mining pertama kali', 'milestone', 'first_purchase', 1, 'free_balance', 100000.00, 2, 1),
('Ajak 5 Teman', 'Ajak 5 teman bergabung via link referral', 'milestone', 'referral', 5, 'free_balance', 200000.00, 3, 1),
('Ajak 10 Teman', 'Ajak 10 teman bergabung via link referral', 'milestone', 'referral', 10, 'free_balance', 500000.00, 4, 1),
('Capai VIP 1', 'Tingkatkan level VIP ke level 1', 'milestone', 'vip_level', 1, 'free_balance', 150000.00, 5, 1),
('Capai VIP 2', 'Tingkatkan level VIP ke level 2', 'milestone', 'vip_level', 2, 'free_balance', 300000.00, 6, 1),
('Capai VIP 3', 'Tingkatkan level VIP ke level 3', 'milestone', 'vip_level', 3, 'free_balance', 750000.00, 7, 1);

-- Chat Templates
INSERT INTO `chat_templates` (`title`, `message`, `sort_order`) VALUES
('Salam Pembuka', 'Halo! Selamat datang di NOXARA. Ada yang bisa kami bantu?', 1),
('Cara Deposit', 'Untuk melakukan deposit, silakan kunjungi menu Isi Ulang dan ikuti langkah-langkahnya. Jika ada kendala, silakan hubungi kami.', 2),
('Cara Withdraw', 'Untuk melakukan penarikan, pastikan sudah mengatur rekening bank dan PIN transaksi. Minimal penarikan sesuai level VIP Anda.', 3),
('Konfirmasi Deposit', 'Deposit Anda sedang kami proses. Mohon tunggu konfirmasi dari tim kami dalam 1x24 jam.', 4),
('Salam Penutup', 'Terima kasih telah menghubungi NOXARA. Semoga investasi Anda berkembang!', 5);

-- Contact Settings
INSERT INTO `contact_settings` (`whatsapp`, `telegram`, `email`) VALUES
('+6281234567890', '@noxara_official', 'support@noxara.page');

-- Notification Settings
INSERT INTO `notification_settings` (`fonnte_enabled`, `wa_deposit_template`, `wa_withdraw_template`, `wa_profit_template`) VALUES
(0, 'Deposit Anda sebesar {amount} telah dikonfirmasi. Saldo utama Anda telah diperbarui.', 'Penarikan Anda sebesar {amount} telah diproses.', 'Profit harian Anda sebesar {amount} telah masuk ke saldo utama.');

-- Marquee Settings
INSERT INTO `marquee_settings` (`is_enabled`, `speed`, `color`, `include_deposits`, `include_purchases`, `include_vip_upgrades`) VALUES
(1, 40, '#00D4FF', 1, 1, 1);

-- Admin Bank Account
INSERT INTO `admin_bank_accounts` (`bank_name`, `account_number`, `account_name`, `is_primary`, `is_active`) VALUES
('Bank BCA', '1234567890', 'PT NOXARA INVESTASI', 1, 1),
('Bank Mandiri', '0987654321', 'PT NOXARA INVESTASI', 0, 1);

-- Platform Info
INSERT INTO `platform_info` (`setting_key`, `setting_value`) VALUES
('total_members', '284750'),
('total_payout', '15800000000'),
('platform_rating', '4.9'),
('platform_since', '2024'),
('tagline', 'Invest Smarter, Grow Faster');


-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'NOXARA', 'general'),
('site_tagline', 'Invest Smarter, Grow Faster', 'general'),
('site_email', 'admin@noxara.page', 'general'),
('site_phone', '+6281234567890', 'general'),
('site_logo', '', 'general'),
('site_favicon', '', 'general'),
('maintenance_mode', '0', 'general'),
('maintenance_message', 'Website sedang dalam pemeliharaan. Silakan coba lagi nanti.', 'general'),
('deposit_enabled', '1', 'deposit'),
('deposit_min', '50000', 'deposit'),
('deposit_max', '100000000', 'deposit'),
('deposit_unique_code_max', '999', 'deposit'),
('deposit_expired_hours', '3', 'deposit'),
('withdraw_enabled', '1', 'withdraw'),
('withdraw_open_hour', '08', 'withdraw'),
('withdraw_close_hour', '20', 'withdraw'),
('withdraw_max_daily', '1', 'withdraw'),
('referral_enabled', '1', 'referral'),
('free_balance_enabled', '1', 'bonus'),
('free_balance_register', '10000', 'bonus'),
('free_balance_register_enabled', '1', 'bonus'),
('meta_description', 'NOXARA - Platform investasi digital terpercaya. Invest Smarter, Grow Faster.', 'seo'),
('meta_keywords', 'investasi, mining, profit harian, NOXARA', 'seo'),
('admin_session_timeout', '3600', 'security'),
('admin_max_failed_login', '5', 'security'),
('admin_lock_duration', '1800', 'security'),
('backup_retention_days', '30', 'backup'),
('backup_auto_enabled', '1', 'backup'),
('chat_enabled', '1', 'chat'),
('cs_status', 'online', 'chat'),
('welcome_popup_enabled', '1', 'popup'),
('welcome_popup_title', 'Selamat Datang di NOXARA', 'popup'),
('welcome_popup_message', 'Selamat datang di platform investasi NOXARA. Bergabunglah ke grup WhatsApp resmi kami untuk mendapatkan informasi terbaru, bantuan, promo, dan update penting.', 'popup'),
('welcome_popup_whatsapp_url', 'https://chat.whatsapp.com/noxara', 'popup'),
('welcome_popup_button_text', 'Gabung Grup WhatsApp', 'popup'),
('welcome_popup_image', '', 'popup'),
('welcome_popup_animation', 'zoom', 'popup'),
('welcome_popup_show_mode', 'once_session', 'popup'),
('google_analytics_id', '', 'tracking'),
('favicon_url', '', 'general'),
('copyright_text', '© 2024 NOXARA. All rights reserved.', 'general');

-- Popup Settings
INSERT INTO `popup_settings` (`event_key`, `title`, `message`, `type`, `is_active`, `duration`) VALUES
('login_success', 'Login Berhasil', 'Selamat datang kembali di NOXARA!', 'success', 1, 3000),
('login_failed', 'Login Gagal', 'Username atau password salah. Silakan coba lagi.', 'error', 1, 4000),
('logout_success', 'Logout Berhasil', 'Anda telah keluar dari akun NOXARA.', 'success', 1, 3000),
('register_success', 'Pendaftaran Berhasil', 'Akun Anda berhasil dibuat. Selamat bergabung di NOXARA!', 'success', 1, 4000),
('register_failed', 'Pendaftaran Gagal', 'Pendaftaran gagal. Periksa kembali data yang Anda masukkan.', 'error', 1, 4000),
('deposit_sent', 'Deposit Berhasil Dikirim', 'Bukti transfer Anda berhasil dikirim. Menunggu konfirmasi admin.', 'success', 1, 4000),
('deposit_failed', 'Deposit Gagal', 'Pengiriman deposit gagal. Silakan coba lagi.', 'error', 1, 4000),
('deposit_approved', 'Deposit Dikonfirmasi', 'Deposit Anda telah dikonfirmasi. Saldo telah diperbarui.', 'success', 1, 5000),
('deposit_rejected', 'Deposit Ditolak', 'Deposit Anda ditolak oleh admin.', 'error', 1, 5000),
('withdraw_success', 'Withdraw Berhasil Diajukan', 'Permintaan penarikan Anda berhasil dikirim. Sedang diproses.', 'success', 1, 4000),
('withdraw_failed', 'Withdraw Gagal', 'Permintaan penarikan gagal. Periksa saldo dan syarat penarikan.', 'error', 1, 4000),
('withdraw_approved', 'Withdraw Disetujui', 'Penarikan Anda telah disetujui dan sedang diproses.', 'success', 1, 5000),
('withdraw_rejected', 'Withdraw Ditolak', 'Penarikan Anda ditolak. Dana dikembalikan ke saldo utama.', 'warning', 1, 5000),
('purchase_success', 'Pembelian Berhasil', 'Paket mining berhasil dibeli. Mulai hasilkan profit harian!', 'success', 1, 4000),
('purchase_failed', 'Pembelian Gagal', 'Pembelian paket gagal. Periksa saldo Anda.', 'error', 1, 4000),
('profit_claimed', 'Profit Berhasil Diklaim', 'Profit harian berhasil masuk ke saldo utama Anda!', 'success', 1, 4000),
('profit_failed', 'Klaim Profit Gagal', 'Klaim profit gagal. Pastikan belum klaim hari ini.', 'error', 1, 4000),
('pin_created', 'PIN Berhasil Dibuat', 'PIN transaksi berhasil dibuat.', 'success', 1, 3000),
('pin_changed', 'PIN Berhasil Diubah', 'PIN transaksi berhasil diubah.', 'success', 1, 3000),
('pin_wrong', 'PIN Salah', 'PIN transaksi yang Anda masukkan salah.', 'error', 1, 3000),
('bank_added', 'Rekening Bank Ditambahkan', 'Rekening bank berhasil ditambahkan.', 'success', 1, 3000),
('bank_failed', 'Rekening Bank Gagal', 'Gagal menambahkan rekening bank. Coba lagi.', 'error', 1, 3000),
('profile_updated', 'Profil Diperbarui', 'Profil Anda berhasil diperbarui.', 'success', 1, 3000),
('password_changed', 'Password Berhasil Diubah', 'Password Anda berhasil diubah.', 'success', 1, 3000),
('daily_reward_claimed', 'Hadiah Harian Diklaim', 'Selamat! Hadiah harian berhasil Anda klaim.', 'success', 1, 4000),
('mission_claimed', 'Misi Berhasil Diklaim', 'Reward misi berhasil diklaim. Terus semangat!', 'success', 1, 4000),
('voucher_applied', 'Voucher Berhasil', 'Voucher berhasil diterapkan.', 'success', 1, 3000),
('voucher_failed', 'Voucher Gagal', 'Kode voucher tidak valid atau sudah kadaluarsa.', 'error', 1, 3000),
('saldo_insufficient', 'Saldo Tidak Cukup', 'Saldo Anda tidak mencukupi untuk transaksi ini.', 'error', 1, 4000),
('withdraw_hour_closed', 'Jam Withdraw Tutup', 'Layanan penarikan hanya tersedia pada jam operasional.', 'warning', 1, 4000),
('admin_action_success', 'Aksi Berhasil', 'Tindakan berhasil dilakukan.', 'success', 1, 3000),
('admin_action_failed', 'Aksi Gagal', 'Tindakan gagal. Silakan coba lagi.', 'error', 1, 3000),
('backup_success', 'Backup Berhasil', 'Database berhasil dibackup.', 'success', 1, 4000),
('backup_failed', 'Backup Gagal', 'Backup database gagal. Periksa izin folder backups.', 'error', 1, 4000),
('maintenance_mode', 'Mode Maintenance', 'Website sedang dalam pemeliharaan.', 'warning', 1, 0);

-- Legal Pages
INSERT INTO `legal_pages` (`slug`, `title`, `content`, `is_active`) VALUES
('tentang-kami', 'Tentang Kami', '<h2>Tentang NOXARA</h2><p>NOXARA adalah platform investasi digital yang menggabungkan teknologi blockchain dan kecerdasan buatan untuk memberikan pengalaman investasi terbaik bagi pengguna Indonesia.</p><p>Didirikan pada tahun 2024, NOXARA hadir dengan misi memberikan akses investasi digital yang mudah, aman, dan menguntungkan bagi semua kalangan masyarakat Indonesia.</p><p>Dengan sistem mining digital yang transparan dan profit harian yang konsisten, NOXARA menjadi pilihan utama investor digital di Indonesia.</p>', 1),
('syarat-ketentuan', 'Syarat & Ketentuan', '<h2>Syarat & Ketentuan</h2><p>Dengan menggunakan layanan NOXARA, Anda menyetujui syarat dan ketentuan berikut:</p><h3>1. Pendaftaran</h3><p>Pengguna wajib berusia minimal 17 tahun dan memberikan data yang valid saat pendaftaran.</p><h3>2. Investasi</h3><p>Setiap investasi mengandung risiko. NOXARA tidak menjamin keuntungan pasti.</p><h3>3. Penarikan</h3><p>Penarikan dilakukan sesuai ketentuan VIP level yang berlaku.</p>', 1),
('kebijakan-privasi', 'Kebijakan Privasi', '<h2>Kebijakan Privasi</h2><p>NOXARA berkomitmen untuk melindungi privasi pengguna. Data yang dikumpulkan hanya digunakan untuk keperluan operasional platform.</p><h3>Data yang Dikumpulkan</h3><p>Nama, email, nomor telepon, dan data transaksi.</p><h3>Penggunaan Data</h3><p>Data digunakan untuk verifikasi identitas, proses transaksi, dan komunikasi resmi.</p>', 1),
('kebijakan-deposit', 'Kebijakan Deposit', '<h2>Kebijakan Deposit</h2><p>Deposit minimum sesuai level VIP yang berlaku. Semua deposit dikonfirmasi manual oleh tim NOXARA dalam 1x24 jam.</p>', 1),
('kebijakan-withdraw', 'Kebijakan Withdraw', '<h2>Kebijakan Withdraw</h2><p>Penarikan dana diproses sesuai level VIP pengguna. Jam operasional penarikan sesuai setting admin. Fee penarikan berlaku sesuai level VIP.</p>', 1),
('risiko-investasi', 'Risiko Investasi', '<h2>Risiko Investasi</h2><p>Setiap bentuk investasi mengandung risiko. Investasi di NOXARA mengandung risiko kehilangan modal sebagian atau seluruhnya. Pastikan Anda memahami risiko sebelum berinvestasi.</p>', 1),
('kontak-resmi', 'Kontak Resmi', '<h2>Kontak Resmi NOXARA</h2><p>Email: support@noxara.page</p><p>WhatsApp: +6281234567890</p><p>Telegram: @noxara_official</p><p>Jam Operasional: Senin - Jumat, 08.00 - 20.00 WIB</p>', 1);

SET FOREIGN_KEY_CHECKS = 1;

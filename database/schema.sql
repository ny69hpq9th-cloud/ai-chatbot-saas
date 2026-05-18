-- AI Chatbot SaaS Platform - Database Schema
-- Engine: MySQL 8.0+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -------------------------------------------------------
-- Users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `uuid`             CHAR(36)        NOT NULL,
  `email`            VARCHAR(255)    NOT NULL,
  `password_hash`    VARCHAR(255)    NOT NULL,
  `full_name`        VARCHAR(150)    NOT NULL DEFAULT '',
  `company`          VARCHAR(150)    NOT NULL DEFAULT '',
  `role`             ENUM('owner','admin','member') NOT NULL DEFAULT 'owner',
  `stripe_customer_id` VARCHAR(100)  DEFAULT NULL,
  `email_verified_at` DATETIME       DEFAULT NULL,
  `remember_token`   VARCHAR(100)    DEFAULT NULL,
  `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_uuid`  (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Subscriptions (Stripe)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`              INT UNSIGNED NOT NULL,
  `stripe_subscription_id` VARCHAR(100) NOT NULL,
  `stripe_price_id`      VARCHAR(100) NOT NULL,
  `plan`                 ENUM('starter','pro','business','enterprise') NOT NULL DEFAULT 'starter',
  `status`               ENUM('trialing','active','past_due','canceled','unpaid','incomplete') NOT NULL DEFAULT 'trialing',
  `trial_ends_at`        DATETIME     DEFAULT NULL,
  `current_period_start` DATETIME     DEFAULT NULL,
  `current_period_end`   DATETIME     DEFAULT NULL,
  `canceled_at`          DATETIME     DEFAULT NULL,
  `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stripe_sub` (`stripe_subscription_id`),
  KEY `fk_sub_user` (`user_id`),
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Chatbots
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbots` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `uuid`             CHAR(36)      NOT NULL,
  `user_id`          INT UNSIGNED  NOT NULL,
  `name`             VARCHAR(150)  NOT NULL,
  `slug`             VARCHAR(100)  NOT NULL,
  `description`      TEXT          DEFAULT NULL,
  `system_prompt`    TEXT          DEFAULT NULL,
  `model`            VARCHAR(60)   NOT NULL DEFAULT 'claude-sonnet-4-6',
  `temperature`      DECIMAL(3,2)  NOT NULL DEFAULT '0.70',
  `max_tokens`       SMALLINT UNSIGNED NOT NULL DEFAULT 1024,
  `welcome_message`  TEXT          DEFAULT NULL,
  `widget_color`     VARCHAR(7)    NOT NULL DEFAULT '#6366f1',
  `widget_position`  ENUM('bottom-right','bottom-left') NOT NULL DEFAULT 'bottom-right',
  `allowed_domains`  TEXT          DEFAULT NULL COMMENT 'JSON array of allowed domains',
  `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chatbot_uuid` (`uuid`),
  UNIQUE KEY `uq_chatbot_slug` (`user_id`, `slug`),
  KEY `fk_chatbot_user` (`user_id`),
  CONSTRAINT `fk_chatbot_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Conversations
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `conversations` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `uuid`           CHAR(36)      NOT NULL,
  `chatbot_id`     INT UNSIGNED  NOT NULL,
  `session_id`     VARCHAR(100)  NOT NULL,
  `visitor_ip`     VARCHAR(45)   DEFAULT NULL,
  `visitor_ua`     VARCHAR(500)  DEFAULT NULL,
  `referrer`       VARCHAR(500)  DEFAULT NULL,
  `page_url`       VARCHAR(500)  DEFAULT NULL,
  `started_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_message_at` DATETIME     DEFAULT NULL,
  `message_count`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_resolved`    TINYINT(1)    NOT NULL DEFAULT 0,
  `metadata`       JSON          DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conv_uuid`    (`uuid`),
  KEY `fk_conv_chatbot`        (`chatbot_id`),
  KEY `idx_conv_session`       (`session_id`),
  CONSTRAINT `fk_conv_chatbot` FOREIGN KEY (`chatbot_id`) REFERENCES `chatbots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Messages
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED    NOT NULL,
  `role`            ENUM('user','assistant','system') NOT NULL,
  `content`         TEXT            NOT NULL,
  `tokens_used`     SMALLINT UNSIGNED DEFAULT NULL,
  `latency_ms`      SMALLINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_msg_conv`        (`conversation_id`),
  KEY `idx_msg_created`    (`created_at`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- API Keys (per chatbot)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `chatbot_id`  INT UNSIGNED  NOT NULL,
  `key_hash`    VARCHAR(64)   NOT NULL COMMENT 'SHA-256 of the raw key',
  `label`       VARCHAR(100)  NOT NULL DEFAULT '',
  `last_used_at` DATETIME     DEFAULT NULL,
  `expires_at`  DATETIME      DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_key_hash` (`key_hash`),
  KEY `fk_apikey_chatbot` (`chatbot_id`),
  CONSTRAINT `fk_apikey_chatbot` FOREIGN KEY (`chatbot_id`) REFERENCES `chatbots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Usage stats (daily rollups)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usage_stats` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chatbot_id`      INT UNSIGNED NOT NULL,
  `stat_date`       DATE         NOT NULL,
  `conversations`   INT UNSIGNED NOT NULL DEFAULT 0,
  `messages_sent`   INT UNSIGNED NOT NULL DEFAULT 0,
  `tokens_consumed` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usage_chatbot_date` (`chatbot_id`, `stat_date`),
  KEY `fk_usage_chatbot` (`chatbot_id`),
  CONSTRAINT `fk_usage_chatbot` FOREIGN KEY (`chatbot_id`) REFERENCES `chatbots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Password reset tokens
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email`      VARCHAR(255) NOT NULL,
  `token_hash` VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

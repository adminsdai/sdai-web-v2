-- ==============================================================================
-- SCRIPT DE BASE DE DATOS MYSQL UNIFICADO — SDAI CHILE SpA
-- Web Principal (sdaichile.com) + Escáner de Cumplimiento Ley N° 21.719 (/scan)
-- Cumplimiento Ley N° 21.719 — Art. 3, letra f (Principio de Seguridad e Inmutabilidad)
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. TABLA DE MENSAJES DE CONTACTO Y CONSENTIMIENTO WEB
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
  `consentGiven` TINYINT(1) NOT NULL DEFAULT 0,
  `consentText` TEXT NULL,
  `policyVersion` VARCHAR(50) NULL,
  `ipAddress` VARCHAR(45) NULL,
  `userAgent` VARCHAR(500) NULL,
  `originUrl` VARCHAR(500) NULL,
  `consentTimestampUtc` VARCHAR(30) NULL,
  `recordHash` VARCHAR(64) NULL,
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABLA DE USUARIOS AUTORIZADOS (ESCÁNER LEY 21.719)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `authorized_users` (
    `user_id` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar administrador inicial por defecto
INSERT IGNORE INTO `authorized_users` (`user_id`, `role`)
VALUES ('admin@sdaichile.com', 'admin');

-- ------------------------------------------------------------------------------
-- 3. TABLA DE BITÁCORA INMUTABLE DE AUDITORÍA Y CONSENTIMIENTO (ESCÁNER)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consent_logs` (
    `id` VARCHAR(36) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_anonymized` VARCHAR(255) NOT NULL,
    `user_agent` TEXT NULL,
    `url_scanned` TEXT NOT NULL,
    `action` VARCHAR(50) NOT NULL DEFAULT 'AGREED_TO_POLICIES',
    `user_id` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `policy_version` VARCHAR(20) NOT NULL DEFAULT 'v1.0',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_consent_user` FOREIGN KEY (`user_id`) REFERENCES `authorized_users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABLA DE CREDENCIALES PASSKEYS / WEBAUTHN (ESCÁNER LEY 21.719)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `passkey_credentials` (
    `id` VARCHAR(36) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `credential_id` VARCHAR(512) NOT NULL,
    `public_key` TEXT NOT NULL,
    `counter` BIGINT NOT NULL DEFAULT 0,
    `transports` JSON NULL,
    `user_id` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_credential_id` (`credential_id`(255)),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_passkey_user` FOREIGN KEY (`user_id`) REFERENCES `authorized_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABLA DE TOKENS OTP PARA ACCESO AL ESCÁNER (FLUJO EMAIL + TOKEN)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `scan_tokens` (
    `id` VARCHAR(36) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `attempts` TINYINT NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_origin` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

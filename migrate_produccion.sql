-- ==============================================================================
-- SCRIPT DE MIGRACIÓN — SDAI CHILE PRODUCCIÓN (Hostinger MariaDB)
-- Ejecutar en phpMyAdmin del panel de Hostinger → hPanel → Databases → phpMyAdmin
-- Base de datos: la configurada en tu Hostinger (DB_NAME de producción)
-- ==============================================================================

-- 1. Agregar columna email a consent_logs (si no existe)
--    IGNORAR error si ya existe: "Duplicate column name 'email'"
ALTER TABLE `consent_logs`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) NULL AFTER `user_id`;

-- 2. Crear tabla scan_tokens para flujo OTP (si no existe)
CREATE TABLE IF NOT EXISTS `scan_tokens` (
    `id`          VARCHAR(36)  NOT NULL,
    `email`       VARCHAR(255) NOT NULL,
    `domain`      VARCHAR(255) NOT NULL,
    `token_hash`  VARCHAR(64)  NOT NULL,
    `used`        TINYINT(1)   NOT NULL DEFAULT 0,
    `attempts`    TINYINT      NOT NULL DEFAULT 0,
    `expires_at`  DATETIME     NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_origin`   VARCHAR(45)  NULL,
    `user_agent`  TEXT         NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_email`      (`email`),
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires`    (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Verificar resultado
SELECT 'OK: scan_tokens creada' AS status
WHERE EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name = 'scan_tokens'
);

SELECT 'OK: email en consent_logs' AS status
WHERE EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'consent_logs'
    AND column_name = 'email'
);

-- 4. Mostrar tablas actuales
SHOW TABLES;

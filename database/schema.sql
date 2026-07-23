-- =====================================================================
--  Servidor ADMS ZKTeco - Esquema de base de datos (MySQL / MariaDB)
--  Municipalidad de Lago Puelo
--
--  Importar en phpMyAdmin (donweb) o por consola:
--      mysql -u USUARIO -p NOMBRE_BASE < database/schema.sql
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ---------------------------------------------------------------------
--  Dispositivos (relojes). Se registran solos al conectarse, pero podés
--  completar nombre y ubicación desde el panel.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `devices` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(190) NULL,          -- nombre del reloj
    `no_sn`      VARCHAR(190) NOT NULL,      -- número de serie (SN)
    `lokasi`     VARCHAR(190) NULL,          -- ubicación
    `online`     DATETIME NULL,              -- último contacto
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `devices_no_sn_unique` (`no_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
--  Empleados. Se vinculan con las marcaciones por el PIN/ID del reloj.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `secretarias` (
    `id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(190) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `secretarias_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reparticiones` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `secretaria_id` BIGINT UNSIGNED NOT NULL,
    `nombre`        VARCHAR(190) NOT NULL,
    `es_secretaria` TINYINT NOT NULL DEFAULT 0,
    `tipo`          VARCHAR(40) NULL DEFAULT 'Otro',
    `parent_id`     BIGINT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reparticiones_unique` (`secretaria_id`, `nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `system_users` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario`    VARCHAR(190) NOT NULL,
    `pass_hash`  VARCHAR(255) NOT NULL,
    `rol`        VARCHAR(30) NOT NULL DEFAULT 'consulta',  -- admin | enrolador | consulta
    `nombre`     VARCHAR(190) NULL,
    `activo`     TINYINT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `system_users_usuario_unique` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employees` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pin`            VARCHAR(190) NOT NULL,      -- PIN/ID del empleado en el reloj
    `nombre`         VARCHAR(190) NULL,
    `apellido`       VARCHAR(190) NULL,
    `dni`            VARCHAR(60) NULL,
    `sector`         VARCHAR(190) NULL,
    `cargo`          VARCHAR(190) NULL,
    `foto`           VARCHAR(255) NULL,          -- ruta de la foto (carpeta uploads/)
    `reparticion_id` BIGINT UNSIGNED NULL,       -- dependencia (organigrama)
    `activo`         TINYINT NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `employees_pin_unique` (`pin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
--  Marcaciones de asistencia (fichadas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendances` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sn`          VARCHAR(190) NOT NULL,     -- SN del reloj que la envió
    `table`       VARCHAR(190) NULL,
    `stamp`       VARCHAR(190) NULL,
    `employee_id` INT NOT NULL,              -- PIN/ID del empleado en el reloj
    `timestamp`   DATETIME NOT NULL,         -- fecha/hora de la marcación
    `status1`     TINYINT NULL,
    `status2`     TINYINT NULL,
    `status3`     TINYINT NULL,
    `status4`     TINYINT NULL,
    `status5`     TINYINT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `attendances_employee_idx` (`employee_id`),
    KEY `attendances_timestamp_idx` (`timestamp`),
    KEY `attendances_sn_idx` (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
--  Cola de comandos hacia los relojes (panel de pruebas).
--  El reloj los retira en GET /iclock/getrequest y reporta el resultado
--  en POST /iclock/devicecmd.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `device_commands` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sn`           VARCHAR(190) NOT NULL,    -- SN del reloj destino
    `command`      TEXT NOT NULL,            -- cuerpo del comando (sin el "C:id:")
    `label`        VARCHAR(190) NULL,        -- descripción legible
    `status`       ENUM('pending','sent','done','error') NOT NULL DEFAULT 'pending',
    `return_code`  VARCHAR(50) NULL,         -- código devuelto por el reloj
    `response`     TEXT NULL,                -- respuesta cruda del reloj
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at`      DATETIME NULL,
    `completed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `device_commands_sn_status_idx` (`sn`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
--  Logs crudos del protocolo (diagnóstico)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `device_log` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `data`       TEXT NULL,
    `tgl`        DATE NULL,
    `sn`         VARCHAR(190) NULL,
    `option`     VARCHAR(190) NULL,
    `url`        TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `finger_log` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `data`       LONGTEXT NULL,
    `url`        TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `error_log` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `error`      TEXT NULL,
    `data`       TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

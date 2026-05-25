-- ============================================================
-- AnunciosFácil — Migración inicial
-- Motor: MySQL 8.0 | Charset: utf8mb4
-- ============================================================

-- Forzar charset UTF-8 en el cliente antes de cualquier INSERT
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS anuncios_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE anuncios_db;

-- --------------------------------------------------------
-- Tabla: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)      NOT NULL,
    email         VARCHAR(255)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    email_public  TINYINT(1)       NOT NULL DEFAULT 0  COMMENT '1 = visible en anuncios',
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100)  NOT NULL,
    slug  VARCHAR(100)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorías iniciales
INSERT INTO categories (name, slug) VALUES
    ('Electrónica',        'electronica'),
    ('Vehículos',          'vehiculos'),
    ('Inmuebles',          'inmuebles'),
    ('Servicios',          'servicios'),
    ('Ropa y Accesorios',  'ropa-accesorios'),
    ('Hogar y Jardín',     'hogar-jardin'),
    ('Deportes',           'deportes'),
    ('Otros',              'otros');

-- --------------------------------------------------------
-- Tabla: ads
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ads (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED  NOT NULL,
    category_id  INT UNSIGNED  NOT NULL,
    title        VARCHAR(150)  NOT NULL,
    description  VARCHAR(300)  NOT NULL  COMMENT 'Resumen corto visible en listado',
    body         TEXT          NOT NULL  COMMENT 'Texto completo del anuncio',
    image_path   VARCHAR(500)      NULL  DEFAULT NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ads_user_id     (user_id),
    KEY idx_ads_category_id (category_id),
    KEY idx_ads_status      (status),
    CONSTRAINT fk_ads_user     FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_ads_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

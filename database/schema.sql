-- =====================================================================
-- database/schema.sql
-- Mini Project 3 - Inventory Management System (Pemrograman Web 4)
-- Jalankan di MySQL/MariaDB (XAMPP) melalui phpMyAdmin > Import.
-- PERINGATAN: file ini menghapus tabel lama (DROP TABLE) lalu membuatnya ulang.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS inventory_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

-- Hapus dari tabel anak ke tabel induk
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS product_suppliers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- users: data login (authentication + role)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,                 -- hasil password_hash(), bukan teks biasa
    role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- user_profiles: ONE-TO-ONE dengan users (FK + UNIQUE)
-- ---------------------------------------------------------------------
CREATE TABLE user_profiles (
    id        INT PRIMARY KEY AUTO_INCREMENT,
    user_id   INT NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone     VARCHAR(30)  NULL,
    email     VARCHAR(100) NULL,
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE               -- profil tidak berarti tanpa user-nya
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- categories: tabel induk ONE-TO-MANY ke products
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- suppliers
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
    id      INT PRIMARY KEY AUTO_INCREMENT,
    name    VARCHAR(150) NOT NULL,
    phone   VARCHAR(30)  NULL,
    email   VARCHAR(100) NULL,
    address VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- products: tabel anak categories (ONE-TO-MANY)
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    category_id   INT NOT NULL,
    sku           VARCHAR(50)  NOT NULL UNIQUE,
    name          VARCHAR(150) NOT NULL,
    price         DECIMAL(12,2) NOT NULL,
    stock         INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_stock CHECK (stock >= 0),
    CONSTRAINT chk_products_min_stock CHECK (minimum_stock >= 0),
    INDEX idx_products_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- product_suppliers: junction table MANY-TO-MANY products <-> suppliers
-- composite primary key mencegah pasangan ganda
-- ---------------------------------------------------------------------
CREATE TABLE product_suppliers (
    product_id     INT NOT NULL,
    supplier_id    INT NOT NULL,
    supplier_price DECIMAL(12,2) NULL,
    PRIMARY KEY (product_id, supplier_id),
    CONSTRAINT fk_ps_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ps_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_ps_price CHECK (supplier_price IS NULL OR supplier_price >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- stock_movements: histori barang masuk (IN) / keluar (OUT)
-- RESTRICT: produk dan user yang punya histori tidak boleh dihapus
-- ---------------------------------------------------------------------
CREATE TABLE stock_movements (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    type       ENUM('IN','OUT') NOT NULL,
    quantity   INT NOT NULL,
    note       VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sm_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sm_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_sm_quantity CHECK (quantity > 0),
    INDEX idx_sm_created_at (created_at)
) ENGINE=InnoDB;

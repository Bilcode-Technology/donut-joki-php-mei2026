-- ============================================================
-- SETUP DATABASE: DonutShop
-- Import file ini di phpMyAdmin hosting kamu
-- ============================================================

CREATE DATABASE IF NOT EXISTS `donut_shop` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `donut_shop`;

-- Tabel produk (dengan kolom gambar)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(150) NOT NULL,
  `kategori` VARCHAR(100) NOT NULL,
  `harga` INT NOT NULL DEFAULT 0,
  `deskripsi` TEXT,
  `kelebihan_varian` VARCHAR(255),
  `gambar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom gambar: tambahkan jika tabel sudah ada tapi belum punya kolom gambar
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `gambar` VARCHAR(255) DEFAULT NULL;

-- Tabel ulasan (dengan kolom status approve)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 1,
  `nama_pelanggan` VARCHAR(150) NOT NULL,
  `rating` TINYINT DEFAULT 5,
  `komentar` TEXT,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom status: tambahkan jika tabel sudah ada
ALTER TABLE `reviews` ADD COLUMN IF NOT EXISTS `status` ENUM('pending','approved','rejected') DEFAULT 'pending';

-- Tabel buku tamu
CREATE TABLE IF NOT EXISTS `buku_tamu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 1,
  `nama_tamu` VARCHAR(150) NOT NULL,
  `waktu_kunjungan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel promo harian
CREATE TABLE IF NOT EXISTS `daily_promos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul_promo` VARCHAR(255) NOT NULL,
  `status_aktif` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel keranjang belanja
CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL DEFAULT 1,
  `product_id` INT NOT NULL,
  `kuantitas` INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pesanan
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 1,
  `nama_penerima` VARCHAR(150) DEFAULT 'Pelanggan',
  `total_harga` INT DEFAULT 0,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `catatan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kolom status pesanan yang lebih lengkap
ALTER TABLE `orders` MODIFY COLUMN IF EXISTS `status` VARCHAR(50) DEFAULT 'Pending';

-- Data promo default (jika tabel kosong)
INSERT INTO `daily_promos` (`judul_promo`, `status_aktif`)
SELECT 'Diskon Opening 50% All Variant!', 1
WHERE NOT EXISTS (SELECT 1 FROM `daily_promos` LIMIT 1);

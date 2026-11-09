-- =========================================================
-- TixZone Database Schema
-- Aplikasi Booking Tiket Emte Highland Resort
-- Sesuai ERD: ERD-Database-Schema-TixZone.drawio
-- =========================================================

CREATE DATABASE IF NOT EXISTS tixzone_db;
USE tixzone_db;

-- =========================================================
-- 1. ROLES
-- =========================================================
CREATE TABLE roles (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL,
    deskripsi TEXT NULL
);

-- =========================================================
-- 2. USERS (admin / petugas, login internal)
-- =========================================================
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50) NULL,
    id_role INT,
    status VARCHAR(20) DEFAULT 'aktif',
    last_login DATETIME NULL,
    FOREIGN KEY (id_role) REFERENCES roles(id_role)
);

-- =========================================================
-- 3. PEGAWAI (petugas tiket, terhubung ke akun users)
-- =========================================================
CREATE TABLE pegawai (
    id_pegawai INT AUTO_INCREMENT PRIMARY KEY,
    nama_pegawai VARCHAR(100) NOT NULL,
    jabatan VARCHAR(50) NOT NULL,
    id_user INT,
    status VARCHAR(20) DEFAULT 'aktif',
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

-- =========================================================
-- 4. CUSTOMERS (akun pengunjung)
-- =========================================================
CREATE TABLE customers (
    id_customer INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_telepon VARCHAR(15) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT NULL,
    password VARCHAR(255) NOT NULL,
    jenis_kelamin VARCHAR(10) NULL,
    tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'aktif'
);

-- =========================================================
-- 5. KATEGORI_DESTINASI
-- =========================================================
CREATE TABLE kategori_destinasi (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT NULL
);

-- =========================================================
-- 6. DESTINASI
-- =========================================================
CREATE TABLE destinasi (
    id_destinasi INT AUTO_INCREMENT PRIMARY KEY,
    nama_destinasi VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    fasilitas TEXT NULL,
    jam_operasional VARCHAR(50) NULL,
    id_kategori INT,
    harga_tiket DECIMAL(10,2) NOT NULL,
    kuota INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'aktif',
    FOREIGN KEY (id_kategori) REFERENCES kategori_destinasi(id_kategori)
);

-- =========================================================
-- 7. BOOKING
-- =========================================================
CREATE TABLE booking (
    id_booking INT AUTO_INCREMENT PRIMARY KEY,
    id_customer INT,
    kode_booking VARCHAR(20) UNIQUE NOT NULL,
    tanggal_kunjungan DATE NOT NULL,
    tanggal_booking DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- status: menunggu | menunggu_verifikasi | lunas | selesai | ditolak | batal
    status VARCHAR(20) DEFAULT 'menunggu',
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    metode_pembayaran VARCHAR(30) NULL,
    bukti_bayar VARCHAR(255) NULL,
    tanggal_bayar DATETIME NULL,
    FOREIGN KEY (id_customer) REFERENCES customers(id_customer)
);

-- =========================================================
-- 8. DETAIL_BOOKING
-- =========================================================
CREATE TABLE detail_booking (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_booking INT,
    id_destinasi INT,
    jumlah INT NOT NULL,
    FOREIGN KEY (id_booking) REFERENCES booking(id_booking),
    FOREIGN KEY (id_destinasi) REFERENCES destinasi(id_destinasi)
);

-- =========================================================
-- 9. E_TICKET
-- =========================================================
CREATE TABLE e_ticket (
    id_eticket INT AUTO_INCREMENT PRIMARY KEY,
    id_booking INT,
    id_pegawai INT,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    status_checkin VARCHAR(20) DEFAULT 'belum',
    tanggal_checkin DATETIME NULL,
    FOREIGN KEY (id_booking) REFERENCES booking(id_booking),
    FOREIGN KEY (id_pegawai) REFERENCES pegawai(id_pegawai)
);

-- =========================================================
-- DATA AWAL (role wajib; sisanya ada di seed_data.sql)
-- =========================================================
INSERT INTO roles (nama_role, deskripsi) VALUES
('admin', 'Mengelola destinasi, tiket, dan verifikasi booking'),
('petugas', 'Validasi tiket dan check-in pengunjung');

-- Tambahkan di paling bawah file migrasi_v2.sql

CREATE TABLE IF NOT EXISTS pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    bank_pengirim VARCHAR(50) NOT NULL,
    nama_pemilik VARCHAR(100) NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    tanggal_transfer DATETIME NOT NULL,
    bukti_file VARCHAR(255) NOT NULL,
    bukti_mime VARCHAR(50) NOT NULL,
    status ENUM('pending', 'diverifikasi', 'ditolak') DEFAULT 'pending',
    tanggal_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_verifikasi DATETIME NULL,
    admin_id INT NULL
);
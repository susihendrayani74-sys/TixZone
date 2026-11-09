-- =========================================================
-- DATA CONTOH (jalankan SEKALI setelah tixzone.sql / migrasi_v2.sql)
-- Password akun contoh: "password"  (segera ganti setelah login)
--   admin   -> username: admin    / password: password
--   petugas -> username: petugas1 / password: password
-- Customer: daftar sendiri lewat halaman Registrasi.
-- =========================================================
USE tixzone_db;

INSERT IGNORE INTO roles (id_role, nama_role, deskripsi) VALUES
(1, 'admin', 'Mengelola destinasi, tiket, dan verifikasi booking'),
(2, 'petugas', 'Validasi tiket dan check-in pengunjung');

INSERT IGNORE INTO users (username, password, nama, jabatan, id_role) VALUES
('admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Emte', 'Pengelola', (SELECT id_role FROM roles WHERE nama_role = 'admin' LIMIT 1)),
('petugas1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Tiket 1', 'Petugas Tiket', (SELECT id_role FROM roles WHERE nama_role = 'petugas' LIMIT 1));

INSERT INTO pegawai (nama_pegawai, jabatan, id_user)
SELECT 'Petugas Tiket 1', 'Petugas Tiket', id_user FROM users WHERE username = 'petugas1'
  AND NOT EXISTS (SELECT 1 FROM pegawai WHERE id_user = users.id_user);

INSERT INTO kategori_destinasi (nama_kategori, deskripsi) VALUES
('Pemandian Air Panas', 'Kolam air panas alami'),
('Camping & Glamping', 'Area berkemah dan glamping'),
('Restoran', 'Tempat makan'),
('Spot Foto', 'Area foto pemandangan');

INSERT INTO destinasi (nama_destinasi, deskripsi, fasilitas, jam_operasional, id_kategori, harga_tiket, kuota) VALUES
('Tiket Pemandian Air Panas', 'Berendam di kolam air panas dengan pemandangan pegunungan.', 'Kolam, ruang bilas, mushola', '08.00 - 17.00', (SELECT id_kategori FROM kategori_destinasi WHERE nama_kategori = 'Pemandian Air Panas' LIMIT 1), 25000, 200),
('Tiket Camping', 'Area berkemah dengan udara sejuk.', 'Toilet, area api unggun', '24 jam', (SELECT id_kategori FROM kategori_destinasi WHERE nama_kategori = 'Camping & Glamping' LIMIT 1), 35000, 80),
('Tiket Glamping', 'Tenda glamping nyaman untuk keluarga.', 'Kasur, listrik, kamar mandi', '24 jam', (SELECT id_kategori FROM kategori_destinasi WHERE nama_kategori = 'Camping & Glamping' LIMIT 1), 150000, 20),
('Tiket Spot Foto', 'Spot foto dengan latar kebun teh dan pegunungan.', 'Area parkir, gazebo', '08.00 - 17.00', (SELECT id_kategori FROM kategori_destinasi WHERE nama_kategori = 'Spot Foto' LIMIT 1), 15000, 150);

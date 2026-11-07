# TixZone

Aplikasi web booking tiket untuk **Emte Highland Resort, Ciwidey** (proyek kelompok TixZone).

## Tim
- Susi Hendrayani — Frontend / UI-UX
- Nayla Khoirunnisa — Backend / Database

## Tech Stack
PHP native (PDO) + MySQL, HTML/CSS vanilla. Jalan di XAMPP / Laragon tanpa build tool.

## Role Sistem
1. **Customer** — registrasi, login, lihat destinasi & sisa kuota, booking (pilih tanggal & jumlah), bayar (upload bukti), e-ticket + QR, riwayat booking.
2. **Admin** — CRUD destinasi & kategori, atur harga/kuota, verifikasi pembayaran (otomatis membuat e-ticket), cari/filter booking, kelola customer, laporan penjualan per periode.
3. **Petugas Tiket** — cari kode booking / isi QR, lihat detail booking, catat check-in.

## Database (9 tabel sesuai ERD final)
`roles, users, pegawai, customers, kategori_destinasi, destinasi, booking, detail_booking, e_ticket`

Penyesuaian terhadap ERD:
- **Tiket = destinasi** (`harga_tiket` dan `kuota` ada di tabel `destinasi`). `kuota` dibaca sebagai kapasitas **per hari**; sisa kuota tanggal tertentu = kuota − jumlah tiket pada booking aktif di tanggal itu.
- Tabel pembayaran tidak ada di ERD, jadi 4 kolom ditambahkan ke `booking`: `total_harga`, `metode_pembayaran`, `bukti_bayar`, `tanggal_bayar`.
- Status booking: `menunggu` → `menunggu_verifikasi` → `lunas` → `selesai` (atau `ditolak` / `batal`).
- Login: customer pakai **email** (tabel `customers`), admin & petugas pakai **username** (tabel `users`).

## Cara Menjalankan (XAMPP)
1. Salin folder ini ke `C:\xampp\htdocs\tixzone`, jalankan Apache + MySQL.
2. phpMyAdmin:
   - **Database baru:** import `database/tixzone.sql`.
   - **Sudah punya `tixzone_db` (9 tabel):** import `database/migrasi_v2.sql` saja (sekali).
3. Import `database/seed_data.sql` (sekali) untuk akun & data contoh.
4. Buka `http://localhost/tixzone/`.
5. Akun contoh (password `password`, segera ganti): admin → `admin`, petugas → `petugas1`. Customer daftar lewat halaman Registrasi.

Kredensial DB ada di `includes/db.php`.

## Struktur Folder
```
admin/       dashboard, destinasi, tiket_harga, booking, customers, laporan
customer/    register, home (riwayat), pilih_tiket, isi_data_pemesan, pembayaran, booking_berhasil, e_ticket
petugas/     dashboard, validasi_booking
includes/    db.php (PDO + helper), auth.php (role guard, CSRF, flash), layout.php, navbar, sidebar, stepper
database/    tixzone.sql, migrasi_v2.sql, seed_data.sql
uploads/bukti_bayar/   bukti pembayaran customer
```

## Status
- [x] Alur customer end-to-end, validasi kuota harian (dengan transaksi + lock), total harga otomatis, kode booking
- [x] Verifikasi pembayaran admin → e-ticket + QR (QR digambar di browser via qrcodejs dari CDN, perlu internet)
- [x] Check-in petugas (hanya booking lunas, tanggal kunjungan = hari ini, tidak bisa dipakai dua kali)
- [x] Password di-hash, CSRF token, upload dicek dari isi file, query pakai prepared statement
- [ ] Export laporan PDF/Excel (saat ini cetak lewat browser)
- [ ] Scan QR lewat kamera (saat ini scanner barcode USB / ketik kode)
- [ ] Foto destinasi (belum ada kolom di ERD)

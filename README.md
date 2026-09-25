# TixZone 🎟️

Aplikasi booking tiket berbasis web untuk destinasi wisata Emte Highland Resort Ciwidey.

## Fitur Utama

### Customer / Pengunjung
- Registrasi dan login
- Melihat informasi destinasi
- Melihat jenis, harga, dan ketersediaan tiket
- Memilih tanggal kunjungan
- Memilih jumlah tiket
- Booking tiket
- Pembayaran dan upload bukti pembayaran
- Mendapatkan e-ticket dan QR Code
- Melihat riwayat booking

### Admin / Pengelola
- Login admin
- Dashboard
- Kelola informasi destinasi
- CRUD jenis tiket
- Kelola harga dan kuota
- Melihat dan memfilter booking
- Verifikasi pembayaran
- Mengelola e-ticket
- Melihat laporan transaksi dan pengunjung

### Petugas Tiket
- Login petugas
- Scan QR Code / input kode booking
- Melihat detail booking
- Validasi tiket
- Check-in pengunjung
- Laporan verifikasi harian

## Alur Utama

Customer:
Registrasi/Login → Destinasi → Tiket → Tanggal → Booking → Pembayaran → E-Ticket → Kunjungan

Admin:
Login → Dashboard → Destinasi → Tiket → Harga & Kuota → Booking → Verifikasi Pembayaran → Laporan

Petugas:
Login → Verifikasi → Scan QR/Kode Booking → Detail Booking → Validasi → Check-in

## Struktur Repository

```text
TixZone/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── views/
│   └── css/
├── routes/
│   └── web.php
├── docs/
│   ├── proposal/
│   ├── erd/
│   └── screenshots/
├── tests/
├── .env.example
├── .gitignore
└── README.md
```

## Status

🚧 Dalam tahap pengembangan.

Prioritas pengerjaan mengikuti MVP:
1. Authentication
2. Informasi destinasi
3. Data tiket
4. Booking
5. Pembayaran
6. E-Ticket
7. Riwayat booking
8. Laporan

## Tim

- Susi Hendrayani — Frontend Engineer & UI/UX Designer
- Nayla Khoirunnisa — Backend Engineer & Database/API Specialist

## Catatan

Repository ini dibuat berdasarkan proposal pengembangan aplikasi TixZone.

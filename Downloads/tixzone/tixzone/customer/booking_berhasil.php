<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Cek login & cek apakah ada booking_id di session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['booking_id'])) {
    header("Location: pilih_tiket.php");
    exit;
}

$booking_id = $_SESSION['booking_id'];
$pesan_error = "";

// Proses Upload Bukti Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = $_POST['metode_pembayaran'] ?? 'Transfer Bank';
    $file = $_FILES['bukti_pembayaran'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nama_baru = "bukti_" . time() . "." . $ext;
        $tujuan = "../uploads/bukti_bayar/" . $nama_baru;

        if (move_uploaded_file($file['tmp_name'], $tujuan)) {
            // Update database: simpan bukti bayar & ubah status jadi 'pending' (menunggu admin)
            $stmt = $pdo->prepare("UPDATE booking SET metode_pembayaran = ?, bukti_pembayaran = ?, status_pembayaran = 'pending' WHERE id = ?");
            $stmt->execute([$metode, $nama_baru, $booking_id]);
        } else {
            $pesan_error = "Gagal mengupload file.";
        }
    } else {
        $pesan_error = "File bukti bayar wajib diupload!";
    }
}

// Ambil Data Booking untuk ditampilkan
$stmt = $pdo->prepare("SELECT * FROM booking WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

// Hapus session booking_id agar tidak bisa di-refresh sembarangan
unset($_SESSION['booking_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Berhasil - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .success-icon { color: var(--primary); font-size: 5rem; }
        .kode-box { background: #e8f5e9; border: 2px dashed var(--primary); padding: 20px; border-radius: 10px; }
        .btn-emte { background-color: var(--primary); color: white; border: none; }
        .btn-emte:hover { background-color: #146c3e; color: white; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-5 text-center">
                <?php if ($pesan_error): ?>
                    <div class="alert alert-danger"><?= $pesan_error ?></div>
                    <a href="pembayaran.php" class="btn btn-warning">Kembali ke Pembayaran</a>
                <?php else: ?>
                    <i class="fas fa-check-circle success-icon mb-3"></i>
                    <h2 class="fw-bold">Booking Berhasil!</h2>
                    <p class="text-muted mb-4">Terima kasih telah melakukan pemesanan tiket di EmtE Highland Resort.<br>Silakan tunggu admin memverifikasi pembayaran Anda.</p>

                    <div class="kode-box mb-4">
                        <p class="text-muted small mb-1">Kode Booking Anda</p>
                        <h3 class="fw-bold text-emte mb-0"><?= $booking['kode_booking'] ?></h3>
                    </div>

                    <div class="row text-start bg-light p-4 rounded mb-4 mx-2">
                        <div class="col-md-6 mb-2">
                            <p class="text-muted small mb-0">Tanggal Kunjungan</p>
                            <strong><?= date('d F Y', strtotime($booking['tanggal_kunjungan'])) ?></strong>
                        </div>
                        <div class="col-md-6 mb-2">
                            <p class="text-muted small mb-0">Total Pembayaran</p>
                            <strong class="text-emte">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></strong>
                        </div>
                    </div>

                    <a href="e_ticket.php?id=<?= $booking['id'] ?>" class="btn btn-emte btn-lg px-5 py-3">
                        <i class="fas fa-ticket-alt me-2"></i>Lihat E-Ticket
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
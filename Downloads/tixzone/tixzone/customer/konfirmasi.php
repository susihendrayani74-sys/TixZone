<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");

// Ambil data booking terakhir yang baru saja dibuat
$stmt = $pdo->prepare("SELECT * FROM booking WHERE id_customer = ? ORDER BY id_booking DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: booking.php");
    exit;
}

$tanggal_format = date('d F Y', strtotime($booking['tanggal_kunjungan']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: bold; color: var(--primary) !important; }
        .stepper { display: flex; justify-content: center; margin: 30px 0; }
        .step { display: flex; align-items: center; color: #adb5bd; font-weight: 600; }
        .step.active { color: var(--primary); }
        .step-number { width: 30px; height: 30px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; margin-right: 8px; font-size: 0.9rem; }
        .step.active .step-number { background: var(--primary); color: white; }
        .step-divider { width: 40px; height: 2px; background: #e9ecef; margin: 0 15px; }
        .card-box { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-primary-custom { background-color: var(--primary); color: white; border: none; padding: 12px; font-weight: 600; }
        .btn-primary-custom:hover { background-color: #146c3e; color: white; }
        .success-icon { font-size: 4rem; color: var(--primary); margin-bottom: 15px; }
        .info-label { color: #6c757d; font-size: 0.9rem; margin-bottom: 5px; }
        .info-value { font-weight: bold; font-size: 1.1rem; color: #2c3e50; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-mountain"></i> EmtE Highland Resort <small class="text-muted fw-normal">Ciwidey</small></a>
        <div class="d-flex">
            <a href="dashboard.php" class="btn btn-outline-success btn-sm me-2"><i class="fas fa-user"></i> Dashboard</a>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="stepper">
        <div class="step"><span class="step-number">1</span> Pilih Tiket</div>
        <div class="step-divider"></div>
        <div class="step"><span class="step-number">2</span> Data Pemesan</div>
        <div class="step-divider"></div>
        <div class="step"><span class="step-number">3</span> Pembayaran</div>
        <div class="step-divider"></div>
        <div class="step active"><span class="step-number">4</span> Konfirmasi</div>
    </div>

    <div class="text-center mt-4">
        <i class="fas fa-check-circle success-icon"></i>
        <h2 class="fw-bold text-success">Booking Berhasil!</h2>
        <p class="text-muted">Terima kasih telah melakukan pemesanan tiket di EmtE Highland Resort.</p>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-md-8 col-lg-6">
            <div class="card card-box p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="info-label">Kode Booking</div>
                        <div class="info-value text-primary"><?= htmlspecialchars($booking['kode_booking']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">Tanggal Kunjungan</div>
                        <div class="info-value"><?= $tanggal_format ?></div>
                    </div>
                    <div class="col-6">
                        <div class="info-label">Jumlah Tiket</div>
                        <div class="info-value">2 Orang</div> <!-- Hardcoded sesuai PDF, bisa dinamis -->
                    </div>
                    <div class="col-6">
                        <div class="info-label">Total Pembayaran</div>
                        <div class="info-value text-success">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="e_ticket.php?id=<?= $booking['id_booking'] ?>" class="btn btn-primary-custom btn-lg w-100">
                        <i class="fas fa-ticket-alt me-2"></i> Lihat E-Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
cek_login('customer');

$bookings = $pdo->prepare("SELECT * FROM booking WHERE customer_id = ? ORDER BY id DESC");
$bookings->execute([$_SESSION['user_id']]);
$my_bookings = $bookings->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Home Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-brand { color: #1a7a4a !important; font-weight: bold; }
        .btn-emte { background: #1a7a4a; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="home.php"><i class="fas fa-mountain"></i> EmtE Resort</a>
            <div class="d-flex align-items-center">
                <span class="me-3">Halo, <?= $_SESSION['nama'] ?></span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">Riwayat Booking Saya</h2>
        <a href="booking.php" class="btn btn-emte mb-3"><i class="fas fa-plus"></i> Booking Tiket Baru</a>
        
        <div class="row">
            <?php foreach($my_bookings as $b): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?= $b['kode_booking'] ?></h5>
                        <p class="mb-1">Tanggal: <?= $b['tanggal_kunjungan'] ?></p>
                        <p class="mb-1">Total: Rp <?= number_format($b['total_harga'],0,',','.') ?></p>
                        <span class="badge bg-<?= $b['status_pembayaran']=='terverifikasi'?'success':'warning' ?>"><?= $b['status_pembayaran'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
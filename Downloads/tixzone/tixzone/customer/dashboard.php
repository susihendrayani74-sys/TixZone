<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");

// Ambil data customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id_customer = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil riwayat booking - PAKAI id_customer DAN id_booking
$stmt_booking = $pdo->prepare("
    SELECT * FROM booking 
    WHERE id_customer = ? 
    ORDER BY tanggal_booking DESC
");
$stmt_booking->execute([$_SESSION['user_id']]);
$bookings = $stmt_booking->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$total_booking = count($bookings);
$booking_sukses = 0;
$booking_pending = 0;
foreach ($bookings as $b) {
    $status = $b['status_pembayaran'] ?? $b['status'] ?? 'pending';
    if ($status === 'terverifikasi' || $status === 'selesai') $booking_sukses++;
    elseif ($status === 'pending' || $status === 'menunggu') $booking_pending++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Customer - TixZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background: #f8f9fa; font-family: sans-serif; }
        .navbar-brand { color: var(--primary) !important; font-weight: bold; }
        .btn-emte { background-color: var(--primary); color: white; border: none; }
        .btn-emte:hover { background-color: #146c3e; color: white; }
        .card-stat { border: none; border-radius: 10px; color: white; padding: 20px; }
        .bg-green { background: #16a34a; }
        .bg-blue { background: #2563eb; }
        .bg-orange { background: #f97316; }
        .badge-berhasil { background: #16a34a; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8em; }
        .badge-pending { background: #fbbf24; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 0.8em; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-mountain"></i> TixZone</a>
        <div class="d-flex align-items-center">
            <span class="me-3">Halo, <?= htmlspecialchars($customer['nama']) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">Dashboard Saya</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-stat bg-green">
                <h6>Total Booking</h6>
                <h3><?= $total_booking ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat bg-blue">
                <h6>Booking Sukses</h6>
                <h3><?= $booking_sukses ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-stat bg-orange">
                <h6>Menunggu Pembayaran</h6>
                <h3><?= $booking_pending ?></h3>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <a href="booking.php" class="btn btn-emte btn-lg"><i class="fas fa-plus"></i> Booking Tiket Baru</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Riwayat Booking</h5>
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode Booking</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $b): 
                                $status = $b['status_pembayaran'] ?? $b['status'] ?? 'pending';
                                $badge = '';
                                if ($status === 'terverifikasi' || $status === 'selesai') $badge = 'badge-berhasil';
                                elseif ($status === 'pending' || $status === 'menunggu') $badge = 'badge-pending';
                            ?>
                            <tr>
                                <td><strong><?= $b['kode_booking'] ?></strong></td>
                                <td><?= date('d M Y', strtotime($b['tanggal_kunjungan'])) ?></td>
                                <td>Rp <?= number_format($b['total_harga'] ?? 0, 0, ',', '.') ?></td>
                                <td><span class="<?= $badge ?>"><?= ucfirst($status) ?></span></td>
                                <td>
                                    <a href="e_ticket.php?id=<?= $b['id_booking'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-ticket-alt"></i> E-Ticket
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada booking. Yuk booking tiket pertamamu!</p>
                    <a href="booking.php" class="btn btn-emte">Booking Sekarang</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
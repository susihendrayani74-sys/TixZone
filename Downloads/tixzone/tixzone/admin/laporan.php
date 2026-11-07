<?php
require_once '../config.php';
session_start();

// 1. Cek Autentikasi & Otorisasi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 2. Handle Filter Tanggal
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Validasi format tanggal agar aman dari injection/error
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari))   $dari = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');

// 3. Query Statistik Ringkas (Hanya booking terverifikasi)
$ringkas = $pdo->prepare(
    "SELECT COUNT(*) jml_booking, COALESCE(SUM(total_harga),0) pendapatan
     FROM booking WHERE status_pembayaran = 'terverifikasi' AND tanggal_kunjungan BETWEEN ? AND ?"
);
$ringkas->execute([$dari, $sampai]);
$r = $ringkas->fetch(PDO::FETCH_ASSOC);

// 4. Query Penjualan per Tiket/Destinasi
$per_tiket = $pdo->prepare(
    "SELECT d.nama_destinasi, SUM(dt.jumlah) tiket, SUM(dt.jumlah * d.harga_tiket) nilai
     FROM detail_booking dt
     JOIN booking b ON b.id_booking = dt.id_booking
     JOIN destinasi d ON d.id_destinasi = dt.id_destinasi
     WHERE b.status_pembayaran = 'terverifikasi' AND b.tanggal_kunjungan BETWEEN ? AND ?
     GROUP BY d.id_destinasi ORDER BY tiket DESC"
);
$per_tiket->execute([$dari, $sampai]);
$list_per_tiket = $per_tiket->fetchAll(PDO::FETCH_ASSOC);

// 5. Query Per Tanggal Kunjungan
$per_hari = $pdo->prepare(
    "SELECT b.tanggal_kunjungan tgl, COUNT(*) jml_booking, SUM(b.total_harga) pendapatan,
            (SELECT COALESCE(SUM(dt.jumlah),0) FROM detail_booking dt
              JOIN booking b2 ON b2.id_booking = dt.id_booking
              WHERE b2.tanggal_kunjungan = b.tanggal_kunjungan AND b2.status_pembayaran = 'terverifikasi') pengunjung
     FROM booking b 
     WHERE b.status_pembayaran = 'terverifikasi' AND b.tanggal_kunjungan BETWEEN ? AND ?
     GROUP BY b.tanggal_kunjungan ORDER BY b.tanggal_kunjungan"
);
$per_hari->execute([$dari, $sampai]);
$list_per_hari = $per_hari->fetchAll(PDO::FETCH_ASSOC);

// 6. Total Check-in (Verifikasi Loket)
$checkin = $pdo->prepare("SELECT COUNT(*) FROM e_ticket WHERE status_checkin = 'sudah' AND DATE(tanggal_checkin) BETWEEN ? AND ?");
$checkin->execute([$dari, $sampai]);
$total_checkin = (int) $checkin->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; --sidebar: #1e293b; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar */
        .sidebar { background: var(--sidebar); min-height: 100vh; color: white; position: fixed; width: 240px; z-index: 1000; }
        .sidebar-brand { padding: 20px; font-size: 1.2rem; font-weight: bold; border-bottom: 1px solid #334155; }
        .sidebar-menu { list-style: none; padding: 15px 0; margin: 0; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #cbd5e1; text-decoration: none; transition: 0.2s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #334155; color: white; border-left: 4px solid var(--primary); }
        .sidebar-menu li a i { margin-right: 10px; width: 20px; }
        
        /* Main Content */
        .main { margin-left: 240px; padding: 30px; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* Stat Cards */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 5px solid var(--primary); }
        .stat-card .label { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        
        /* Filter Bar */
        .filter-bar { background: white; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        
        /* Print Styles */
        @media print {
            .sidebar, .filter-bar, .no-print { display: none !important; }
            .main { margin-left: 0; padding: 0; }
            body { background: white; }
            .content-card { box-shadow: none; border: 1px solid #dee2e6; break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar no-print">
    <div class="sidebar-brand"><i class="fas fa-user-shield"></i> Panel Admin</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="customers.php"><i class="fas fa-users"></i> Data Customer</a></li>
        <li><a href="destinasi.php"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li><a href="laporan.php" class="active"><i class="fas fa-chart-line"></i> Laporan</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1 class="fw-bold mb-4">Laporan Penjualan</h1>

    <!-- FILTER BAR -->
    <form method="get" class="filter-bar no-print">
        <div>
            <label class="form-label fw-bold small">Dari Tanggal</label>
            <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>" class="form-control">
        </div>
        <div>
            <label class="form-label fw-bold small">Sampai Tanggal</label>
            <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / PDF
        </button>
    </form>

    <!-- STATISTIK RINGKAS -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="label"><i class="fas fa-ticket-alt"></i> Booking Terverifikasi</div>
            <div class="value"><?= number_format((int) $r['jml_booking']) ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #16a34a;">
            <div class="label"><i class="fas fa-money-bill-wave"></i> Total Pendapatan</div>
            <div class="value" style="color: #16a34a;">Rp <?= number_format((float) $r['pendapatan'], 0, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <div class="label"><i class="fas fa-check-circle"></i> Pengunjung Check-in</div>
            <div class="value" style="color: #f59e0b;"><?= number_format($total_checkin) ?></div>
        </div>
    </div>

    <!-- TABEL PENJUALAN PER TIKET -->
    <div class="content-card">
        <h4 class="fw-bold mb-3"><i class="fas fa-list-ol"></i> Penjualan per Destinasi</h4>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Destinasi</th>
                        <th>Jumlah Tiket Terjual</th>
                        <th>Total Nilai Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list_per_tiket) > 0): ?>
                    <?php foreach ($list_per_tiket as $t): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($t['nama_destinasi']) ?></td>
                            <td><?= number_format((int) $t['tiket']) ?> Tiket</td>
                            <td class="text-success fw-bold">Rp <?= number_format((float) $t['nilai'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada penjualan pada periode tanggal ini.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL PER TANGGAL KUNJUNGAN -->
    <div class="content-card">
        <h4 class="fw-bold mb-3"><i class="fas fa-calendar-alt"></i> Rekapitulasi per Tanggal Kunjungan</h4>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Kunjungan</th>
                        <th>Jumlah Booking</th>
                        <th>Total Pengunjung</th>
                        <th>Pendapatan Harian</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list_per_hari) > 0): ?>
                    <?php foreach ($list_per_hari as $h): ?>
                        <tr>
                            <td><?= date('d F Y', strtotime($h['tgl'])) ?></td>
                            <td><?= number_format((int) $h['jml_booking']) ?></td>
                            <td><?= number_format((int) $h['pengunjung']) ?> Orang</td>
                            <td class="fw-bold">Rp <?= number_format((float) $h['pendapatan'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data kunjungan pada periode ini.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
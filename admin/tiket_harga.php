<?php
require_once '../config.php';
session_start();

// 1. Cek Autentikasi & Otorisasi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$hari_ini = date('Y-m-d');

// 2. Proses Update Harga, Kuota, dan Status (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_destinasi = (int) ($_POST['id_destinasi'] ?? 0);
    $harga_tiket = max(0, (float) ($_POST['harga_tiket'] ?? 0));
    $kuota = max(0, (int) ($_POST['kuota'] ?? 0));
    $status = ($_POST['status'] ?? '') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($id_destinasi > 0) {
        try {
            $stmt = $pdo->prepare('UPDATE destinasi SET harga_tiket = ?, kuota = ?, status = ? WHERE id_destinasi = ?');
            $stmt->execute([$harga_tiket, $kuota, $status, $id_destinasi]);
            $_SESSION['flash_success'] = "Data tiket berhasil diperbarui.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "ID Destinasi tidak valid.";
    }
    
    header("Location: tiket_harga.php");
    exit;
}

// 3. Ambil Data Destinasi
$list = $pdo->query('SELECT * FROM destinasi ORDER BY nama_destinasi')->fetchAll(PDO::FETCH_ASSOC);

// 4. Hitung Sisa Kuota Hari Ini per Destinasi
$sisa_kuota_map = [];
if (count($list) > 0) {
    // Ambil total tiket yang sudah terjual (booking terverifikasi) untuk hari ini
    $sql_sisa = "SELECT dt.id_destinasi, SUM(dt.jumlah) as total_terjual 
                 FROM detail_booking dt 
                 JOIN booking b ON b.id_booking = dt.id_booking 
                 WHERE b.tanggal_kunjungan = ? AND b.status_pembayaran = 'terverifikasi' 
                 GROUP BY dt.id_destinasi";
                 
    $stmt_sisa = $pdo->prepare($sql_sisa);
    $stmt_sisa->execute([$hari_ini]);
    $data_terjual = $stmt_sisa->fetchAll(PDO::FETCH_ASSOC);

    // Mapping hasil query ke array asosiatif agar mudah diakses saat looping tabel
    foreach ($data_terjual as $row) {
        $sisa_kuota_map[(int)$row['id_destinasi']] = (int)$row['total_terjual'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket, Harga & Kuota - Admin</title>
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
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .badge-aktif { background: #16a34a; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-nonaktif { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        
        /* Inline Form Styling */
        .inline-form { display: inline-block; }
        .form-inline-input { 
            width: 100%; padding: 6px 10px; border: 1px solid #dee2e6; 
            border-radius: 6px; font-size: 0.9rem; 
        }
        .form-inline-select {
            width: 100%; padding: 6px 10px; border: 1px solid #dee2e6; 
            border-radius: 6px; font-size: 0.9rem; background-color: white;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-shield"></i> Panel Admin</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="customers.php"><i class="fas fa-users"></i> Data Customer</a></li>
        <li><a href="destinasi.php"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li><a href="tiket_harga.php" class="active"><i class="fas fa-tags"></i> Tiket & Kuota</a></li>
        <li><a href="laporan.php"><i class="fas fa-chart-line"></i> Laporan</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1 class="fw-bold mb-2">Tiket, Harga & Kuota</h1>
    <p class="text-muted mb-4">
        Kelola harga dan batas maksimal pengunjung (kuota) per hari. 
        Data "Sisa Kuota" dihitung otomatis dari booking yang sudah <strong>terverifikasi</strong> pada tanggal <?= date('d F Y', strtotime($hari_ini)) ?>.
    </p>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="content-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Nama Destinasi / Tiket</th>
                        <th style="width: 15%;">Harga (Rp)</th>
                        <th style="width: 15%;">Kuota / Hari</th>
                        <th style="width: 15%;">Sisa Kuota Hari Ini</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list) > 0): ?>
                    <?php foreach ($list as $d): 
                        $fid = 'form_' . (int) $d['id_destinasi'];
                        
                        // Hitung sisa kuota
                        $terjual_hari_ini = $sisa_kuota_map[(int)$d['id_destinasi']] ?? 0;
                        $sisa = max(0, (int)$d['kuota'] - $terjual_hari_ini);
                        
                        // Tentukan warna badge sisa kuota
                        $badge_sisa = 'bg-secondary';
                        if ($sisa == 0) $badge_sisa = 'bg-danger';
                        elseif ($sisa <= ((int)$d['kuota'] * 0.2)) $badge_sisa = 'bg-warning text-dark';
                        else $badge_sisa = 'bg-success';
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($d['nama_destinasi']) ?></td>
                            <td>
                                <form id="<?= $fid ?>" method="post" class="inline-form w-100">
                                    <input type="hidden" name="id_destinasi" value="<?= (int) $d['id_destinasi'] ?>">
                                    <input form="<?= $fid ?>" type="number" name="harga_tiket" class="form-inline-input" min="0" value="<?= (int) $d['harga_tiket'] ?>">
                                </form>
                            </td>
                            <td>
                                <input form="<?= $fid ?>" type="number" name="kuota" class="form-inline-input" min="0" value="<?= (int) $d['kuota'] ?>">
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $badge_sisa ?> px-3 py-2">
                                    <?= number_format($sisa) ?> / <?= number_format((int)$d['kuota']) ?>
                                </span>
                            </td>
                            <td>
                                <select form="<?= $fid ?>" name="status" class="form-inline-select">
                                    <option value="aktif" <?= $d['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="nonaktif" <?= $d['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </td>
                            <td>
                                <button form="<?= $fid ?>" type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data destinasi/tiket.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
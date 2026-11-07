<?php
// File: petugas/validasi_booking.php
// Halaman Validasi & Check-in Tiket (Terintegrasi config.php & Session Native)

require_once '../config.php';
session_start();

// 1. Cek Autentikasi & Otorisasi Petugas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$petugas_nama = htmlspecialchars($_SESSION['nama']);
$hari_ini = date('Y-m-d');

// Ambil ID Pegawai dari tabel users (jika kolom id_pegawai ada) atau gunakan user_id sebagai fallback
$stmt_petugas = $pdo->prepare("SELECT id_user as id_pegawai FROM users WHERE id_user = ? LIMIT 1");
$stmt_petugas->execute([$user_id]);
$id_pegawai = $stmt_petugas->fetchColumn() ?: $user_id;

$kode = trim($_GET['kode'] ?? $_POST['kode'] ?? '');
$pesan = null;
$tipe_pesan = '';
$hasil = null;

// Fungsi Pencarian Tiket (Bisa pakai Kode Booking atau Isi QR Code)
function cari_tiket(PDO $pdo, string $kode): ?array {
    $s = $pdo->prepare(
        'SELECT b.*, c.nama as nama_pemesan, c.email, c.no_telepon, e.id_ticket, e.qr_code, e.status_checkin, e.tanggal_checkin
         FROM booking b
         JOIN customers c ON c.id_customer = b.id_customer
         LEFT JOIN e_ticket e ON e.id_booking = b.id_booking
         WHERE b.kode_booking = ? OR e.qr_code = ? LIMIT 1'
    );
    $s->execute([$kode, $kode]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    
    if (!$r) return null;
    
    // Ambil detail item tiket
    $it = $pdo->prepare('SELECT d.nama_destinasi, dt.jumlah FROM detail_booking dt
                         JOIN destinasi d ON d.id_destinasi = dt.id_destinasi WHERE dt.id_booking = ?');
    $it->execute([$r['id_booking']]);
    $r['items'] = $it->fetchAll(PDO::FETCH_ASSOC);
    return $r;
}

// Proses Cari Tiket (GET)
if ($kode !== '') {
    $hasil = cari_tiket($pdo, $kode);
    if (!$hasil) {
        $pesan = 'Kode booking atau QR Code tidak ditemukan dalam database.';
        $tipe_pesan = 'danger';
    }
}

// Proses Check-in (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_checkin'])) {
    $post_kode = trim($_POST['kode'] ?? '');
    $hasil_post = cari_tiket($pdo, $post_kode);
    
    if (!$hasil_post) {
        $pesan = 'Data tiket tidak valid.';
        $tipe_pesan = 'danger';
    } elseif ($hasil_post['status_pembayaran'] !== 'terverifikasi') {
        $pesan = 'Booking belum terverifikasi oleh admin. Silakan hubungi kasir.';
        $tipe_pesan = 'warning';
    } elseif (!$hasil_post['id_ticket']) {
        $pesan = 'E-Ticket belum digenerate untuk booking ini.';
        $tipe_pesan = 'warning';
    } elseif ($hasil_post['tanggal_kunjungan'] !== $hari_ini) {
        $pesan = 'Tanggal kunjungan tiket ini adalah ' . date('d F Y', strtotime($hasil_post['tanggal_kunjungan'])) . ', bukan hari ini (' . date('d F Y') . ').';
        $tipe_pesan = 'warning';
    } elseif ($hasil_post['status_checkin'] === 'sudah') {
        $pesan = 'Tiket ini SUDAH PERNAH di-check-in pada ' . date('d/m/Y H:i', strtotime($hasil_post['tanggal_checkin']));
        $tipe_pesan = 'info';
    } else {
        // Eksekusi Check-in
        try {
            $pdo->beginTransaction();
            
            // Update status check-in di e_ticket
            $pdo->prepare("UPDATE e_ticket SET status_checkin = 'sudah', tanggal_checkin = NOW(), id_pegawai = ? WHERE id_ticket = ?")
                ->execute([$id_pegawai, $hasil_post['id_ticket']]);
            
            // Opsional: Update status booking menjadi 'selesai' jika diperlukan
            // $pdo->prepare("UPDATE booking SET status_pembayaran = 'selesai' WHERE id_booking = ?")->execute([$hasil_post['id_booking']]);
            
            $pdo->commit();
            
            $pesan = 'Check-in BERHASIL! Selamat menikmati liburan, ' . htmlspecialchars($hasil_post['nama_pemesan']) . '.';
            $tipe_pesan = 'success';
            
            // Refresh data hasil agar tampilan update real-time
            $hasil = cari_tiket($pdo, $post_kode);
            $kode = $post_kode; 
        } catch (PDOException $e) {
            $pdo->rollBack();
            $pesan = 'Gagal memproses check-in: ' . $e->getMessage();
            $tipe_pesan = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Tiket - Petugas</title>
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
        .main { margin-left: 240px; padding: 30px; max-width: 900px; }
        .content-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* Summary Rows */
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f3f5; }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #6c757d; font-size: 0.9rem; }
        .summary-value { font-weight: 600; color: #1e293b; text-align: right; }
        
        /* Status Badges */
        .badge-terverifikasi { background: #16a34a; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-pending { background: #fbbf24; color: #1e293b; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-checkin { background: #0dcaf0; color: #000; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-tag"></i> Panel Petugas</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="validasi_booking.php" class="active"><i class="fas fa-qrcode"></i> Validasi Tiket</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold mb-0">Validasi & Check-in Tiket</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>

    <!-- Flash Messages -->
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show shadow-sm">
            <i class="fas <?= $tipe_pesan === 'success' ? 'fa-check-circle' : ($tipe_pesan === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?> me-2"></i>
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FORM PENCARIAN / SCAN QR -->
    <div class="content-card">
        <form method="get">
            <label class="form-label fw-bold mb-2"><i class="fas fa-search me-2"></i>Masukkan Kode Booking atau Arahkan Scanner QR Code</label>
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white"><i class="fas fa-ticket-alt text-muted"></i></span>
                <input type="text" name="kode" value="<?= htmlspecialchars($kode) ?>" class="form-control" placeholder="Contoh: MH20261101XXXX" autofocus required autocomplete="off">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i> Cari</button>
            </div>
            <div class="form-text text-muted mt-2">Pastikan kursor aktif di kolom ini sebelum men-scan QR Code dari E-Ticket customer.</div>
        </form>
    </div>

    <!-- DETAIL HASIL PENCARIAN -->
    <?php if ($hasil): ?>
        <div class="content-card border-start border-4 <?= $hasil['status_checkin'] === 'sudah' ? 'border-info' : 'border-success' ?>">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h4 class="fw-bold mb-0">Detail Booking</h4>
                <span class="fs-4 fw-bold text-success"><?= htmlspecialchars($hasil['kode_booking']) ?></span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Nama Pemesan</span>
                <span class="summary-value fs-5"><?= htmlspecialchars($hasil['nama_pemesan']) ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Kontak</span>
                <span class="summary-value"><?= htmlspecialchars($hasil['no_telepon']) ?> &bull; <?= htmlspecialchars($hasil['email']) ?></span>
            </div>
            
            <div class="mt-3 mb-2 fw-bold text-muted small">DAFTAR TIKET:</div>
            <?php foreach ($hasil['items'] as $it): ?>
                <div class="summary-row bg-light rounded px-3 mb-2">
                    <span class="summary-label"><?= htmlspecialchars($it['nama_destinasi']) ?></span>
                    <span class="summary-value">x<?= (int) $it['jumlah'] ?></span>
                </div>
            <?php endforeach; ?>

            <div class="summary-row">
                <span class="summary-label">Tanggal Kunjungan</span>
                <span class="summary-value <?= $hasil['tanggal_kunjungan'] === $hari_ini ? 'text-success fw-bold' : '' ?>">
                    <?= date('d F Y', strtotime($hasil['tanggal_kunjungan'])) ?>
                    <?php if ($hasil['tanggal_kunjungan'] === $hari_ini): ?><span class="badge bg-success ms-2">Hari Ini</span><?php endif; ?>
                </span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Status Pembayaran</span>
                <span class="summary-value">
                    <?php if ($hasil['status_pembayaran'] === 'terverifikasi'): ?>
                        <span class="badge-terverifikasi">Terverifikasi</span>
                    <?php else: ?>
                        <span class="badge-pending"><?= ucfirst($hasil['status_pembayaran']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($hasil['status_checkin'] === 'sudah'): ?>
                <div class="summary-row">
                    <span class="summary-label">Waktu Check-in</span>
                    <span class="summary-value text-info">
                        <i class="fas fa-check-double me-1"></i> Sudah Check-in pada <?= date('d/m/Y H:i', strtotime($hasil['tanggal_checkin'])) ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Tombol Aksi Check-in -->
            <?php if ($hasil['status_pembayaran'] === 'terverifikasi' && $hasil['status_checkin'] !== 'sudah' && $hasil['tanggal_kunjungan'] === $hari_ini): ?>
                <form method="post" class="mt-4 pt-3 border-top">
                    <input type="hidden" name="kode" value="<?= htmlspecialchars($kode) ?>">
                    <button type="submit" name="aksi_checkin" value="1" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm">
                        <i class="fas fa-check-circle me-2"></i> CATAT CHECK-IN SEKARANG
                    </button>
                </form>
            <?php elseif ($hasil['status_checkin'] === 'sudah'): ?>
                <div class="alert alert-info mt-4 mb-0 text-center fw-bold">
                    <i class="fas fa-info-circle me-2"></i> Tiket ini sudah divalidasi dan tidak perlu check-in ulang.
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-4 mb-0 text-center">
                    <i class="fas fa-ban me-2"></i> Tidak dapat melakukan check-in (Status belum terverifikasi atau bukan tanggal hari ini).
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
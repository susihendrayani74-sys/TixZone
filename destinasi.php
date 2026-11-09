<?php
// File: destinasi.php (Halaman Publik Destinasi & Tiket)
require_once 'config.php';
session_start();

$kat = (int) ($_GET['kategori'] ?? 0);
$tanggal = $_GET['tanggal'] ?? '';

// Validasi format tanggal (harus YYYY-MM-DD dan tidak boleh di masa lalu)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $tanggal < date('Y-m-d')) {
    $tanggal = '';
}
$jumlah = max(1, (int) ($_GET['jumlah'] ?? 1));

// 1. Ambil Data Kategori
$kategori = $pdo->query('SELECT * FROM kategori_destinasi ORDER BY nama_kategori')->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil Data Destinasi (Hanya yang status = 'aktif')
$sql = "SELECT d.*, k.nama_kategori FROM destinasi d
        LEFT JOIN kategori_destinasi k ON k.id_kategori = d.id_kategori
        WHERE d.status = 'aktif'";
$params = [];
if ($kat > 0) {
    $sql .= ' AND d.id_kategori = ?';
    $params[] = $kat;
}
$sql .= ' ORDER BY d.nama_destinasi';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Helper Function untuk Menghitung Sisa Kuota (Pengganti sisa_kuota)
function getSisaKuota($pdo, $id_destinasi, $tanggal) {
    $stmt = $pdo->prepare("
        SELECT d.kuota - COALESCE(SUM(dt.jumlah), 0) as sisa
        FROM destinasi d
        LEFT JOIN detail_booking dt ON d.id_destinasi = dt.id_destinasi
        LEFT JOIN booking b ON dt.id_booking = b.id_booking AND b.tanggal_kunjungan = ? AND b.status_pembayaran = 'terverifikasi'
        WHERE d.id_destinasi = ?
        GROUP BY d.id_destinasi, d.kuota
    ");
    $stmt->execute([$tanggal, $id_destinasi]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int)$result['sisa'] : 0;
}

// Ambil data user untuk navbar
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';
$user_nama = htmlspecialchars($_SESSION['nama'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinasi & Tiket - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; --primary-hover: #146c3e; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { color: var(--primary) !important; font-weight: 800; }
        .btn-primary-custom { background-color: var(--primary); border-color: var(--primary); color: white; transition: 0.3s; }
        .btn-primary-custom:hover { background-color: var(--primary-hover); border-color: var(--primary-hover); color: white; }
        
        .card-media { 
            height: 140px; 
            background: linear-gradient(135deg, #1a7a4a 0%, #2ecc71 100%); 
            display: flex; align-items: center; justify-content: center; 
            color: white; font-weight: bold; font-size: 1.1rem; 
            border-radius: 8px 8px 0 0; 
        }
        .card-price { color: var(--primary); font-weight: 800; font-size: 1.15rem; }
        .nav-pills .nav-link { color: #6c757d; border-radius: 20px; padding: 8px 20px; font-weight: 500; }
        .nav-pills .nav-link.active { background-color: var(--primary); color: white; }
        .nav-pills .nav-link:hover:not(.active) { background-color: #e9ecef; color: var(--primary); }
    </style>
</head>
<body>

<!-- Navbar Publik -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-mountain me-2"></i>EmtE Highland Resort</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="destinasi.php">Destinasi & Tiket</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#kontak">Kontak</a></li>
                
                <?php if ($is_logged_in && $user_role === 'customer'): ?>
                    <li class="nav-item ms-lg-3"><a class="nav-link" href="customer/dashboard.php">Booking Saya</a></li>
                    <li class="nav-item ms-lg-2"><a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a></li>
                <?php elseif ($is_logged_in): ?>
                    <li class="nav-item ms-lg-3"><a href="<?= $user_role ?>/dashboard.php" class="btn btn-success btn-sm rounded-pill px-3">Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3"><a href="login.php" class="btn btn-primary-custom btn-sm rounded-pill px-4">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<section class="py-5" id="tiket">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6">Destinasi & Tiket</h2>
            <p class="text-muted">Pilih tiket sesuai kebutuhanmu dan nikmati pengalaman seru di EmtE Highland Resort.</p>
        </div>

        <!-- Filter Kategori (Tabs) -->
        <ul class="nav nav-pills justify-content-center mb-5 flex-wrap">
            <li class="nav-item mb-2">
                <a class="nav-link <?= $kat === 0 ? 'active' : '' ?>" href="destinasi.php">Semua</a>
            </li>
            <?php foreach ($kategori as $k): ?>
                <li class="nav-item mb-2">
                    <a class="nav-link <?= $kat === (int) $k['id_kategori'] ? 'active' : '' ?>" 
                       href="destinasi.php?kategori=<?= (int) $k['id_kategori'] ?>">
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Grid Destinasi -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($items as $d): 
                // Hitung sisa kuota jika tanggal dipilih
                $sisa = $tanggal ? getSisaKuota($pdo, (int) $d['id_destinasi'], $tanggal) : null;
                
                // Buat link ke halaman isi data pemesan dengan parameter yang lengkap
                $link = 'customer/isi_data_pemesan.php?id_destinasi=' . (int) $d['id_destinasi'] 
                      . '&tiket=' . urlencode($d['nama_destinasi'])
                      . '&harga=' . (int) $d['harga_tiket']
                      . ($tanggal ? '&tanggal=' . $tanggal : '') 
                      . '&jumlah=' . $jumlah;
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-media">
                            <i class="fas fa-mountain me-2"></i> <?= htmlspecialchars($d['nama_kategori'] ?? 'Destinasi') ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold mb-2"><?= htmlspecialchars($d['nama_destinasi']) ?></h5>
                            <div class="card-price mb-2">Rp <?= number_format($d['harga_tiket'], 0, ',', '.') ?> <small class="text-muted fw-normal">/ orang</small></div>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($d['deskripsi'] ?? 'Nikmati keindahan alam dan fasilitas terbaik kami.') ?></p>
                            
                            <?php if (!empty($d['jam_operasional'])): ?>
                                <small class="text-muted mb-2 d-block"><i class="far fa-clock me-1"></i> <?= htmlspecialchars($d['jam_operasional']) ?></small>
                            <?php endif; ?>
                            
                            <?php if ($sisa !== null): ?>
                                <small class="mb-3 d-block <?= $sisa > 0 ? 'text-success' : 'text-danger fw-bold' ?>">
                                    <i class="fas fa-ticket-alt me-1"></i> 
                                    <?= $sisa > 0 ? 'Sisa kuota: ' . $sisa : 'Kuota Penuh' ?>
                                </small>
                            <?php endif; ?>
                            
                            <a href="<?= $link ?>" class="btn btn-primary-custom w-100 mt-auto">
                                Pilih Tiket <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($items)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted fs-5">Belum ada tiket tersedia untuk kategori ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4 mt-5">
    <div class="container">
        <p class="mb-0">&copy; 2026 EmtE Highland Resort Ciwidey. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
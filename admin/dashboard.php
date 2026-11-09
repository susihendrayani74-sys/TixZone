<?php
require_once '../config.php';
session_start();

// Cek autentikasi & otorisasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// === PROSES UPDATE STATUS (VERIFIKASI / TOLAK) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_booking = (int)$_POST['id_booking'];
    $status_baru = $_POST['status_baru']; // 'terverifikasi' atau 'ditolak'
    
    if (in_array($status_baru, ['terverifikasi', 'ditolak'])) {
        try {
            $stmt = $pdo->prepare("UPDATE booking SET status_pembayaran = ? WHERE id_booking = ?");
            $stmt->execute([$status_baru, $id_booking]);
            $_SESSION['flash_success'] = "Status booking berhasil diubah menjadi {$status_baru}.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Gagal mengubah status: " . $e->getMessage();
        }
    }
    header("Location: dashboard.php");
    exit;
}

// === STATISTIK ===
$total_pending = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_pembayaran = 'pending'")->fetchColumn();
$total_verif = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_pembayaran = 'terverifikasi'")->fetchColumn();
$total_tolak = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_pembayaran = 'ditolak'")->fetchColumn();
$total_pendapatan = $pdo->query("SELECT COALESCE(SUM(total_harga),0) FROM booking WHERE status_pembayaran = 'terverifikasi'")->fetchColumn();

// === DATA BOOKING ===
$bookings = $pdo->query("
    SELECT b.*, c.nama as nama_customer, 
    GROUP_CONCAT(CONCAT(d.nama_destinasi, ' (', db.jumlah, 'x)') SEPARATOR ', ') as detail_tiket
    FROM booking b 
    LEFT JOIN customers c ON b.id_customer = c.id_customer 
    LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
    LEFT JOIN destinasi d ON db.id_destinasi = d.id_destinasi
    GROUP BY b.id_booking
    ORDER BY b.tanggal_booking DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#1a7a4a;--sidebar:#1e293b;}
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif;}
        .sidebar{background:var(--sidebar);min-height:100vh;color:white;position:fixed;width:240px;z-index:1000;}
        .sidebar-brand{padding:20px;font-size:1.2rem;font-weight:bold;border-bottom:1px solid #334155;}
        .sidebar-menu{list-style:none;padding:15px 0;margin:0;}
        .sidebar-menu li a{display:block;padding:12px 20px;color:#cbd5e1;text-decoration:none;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:#334155;color:white;border-left:4px solid var(--primary);}
        .sidebar-menu li a i{margin-right:10px;width:20px;}
        .main{margin-left:240px;padding:30px;}
        .stat-card{border:none;border-radius:12px;color:white;padding:25px;margin-bottom:20px;transition:transform 0.2s;}
        .stat-card:hover{transform:translateY(-5px);}
        .stat-card.yellow{background:#f59e0b;color:#1e293b;}
        .stat-card.green{background:#16a34a;}
        .stat-card.red{background:#ef4444;}
        .stat-card.blue{background:#2563eb;}
        .content-card{background:white;border-radius:12px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .badge-pending{background:#fbbf24;color:#1e293b;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .badge-terverifikasi{background:#16a34a;color:white;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .badge-ditolak{background:#ef4444;color:white;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .btn-cek-bukti{background:#0dcaf0;color:#000;border:none;font-weight:600;}
        .btn-cek-bukti:hover{background:#31d2f2;color:#000;}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-shield"></i> Panel Admin</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="customers.php"><i class="fas fa-users"></i> Data Customer</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1 class="fw-bold">Dashboard Admin</h1>
    <p class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</p>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    
    <!-- STATISTICS CARDS -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card yellow">
                <h6><i class="fas fa-clock"></i> Pending</h6>
                <h2><?= $total_pending ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <h6><i class="fas fa-check-circle"></i> Terverifikasi</h6>
                <h2><?= $total_verif ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card red">
                <h6><i class="fas fa-times-circle"></i> Ditolak</h6>
                <h2><?= $total_tolak ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue">
                <h6><i class="fas fa-money-bill"></i> Pendapatan</h6>
                <h5>Rp <?= number_format($total_pendapatan,0,',','.') ?></h5>
            </div>
        </div>
    </div>
    
    <!-- BOOKING TABLE -->
    <div class="content-card mt-3">
        <h5 class="fw-bold mb-3">Daftar Booking</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Customer</th>
                        <th>Detail Tiket</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($bookings as $b): 
                    $s = $b['status_pembayaran'] ?? 'pending';
                    $badge = 'badge-pending';
                    if ($s === 'terverifikasi') $badge = 'badge-terverifikasi';
                    if ($s === 'ditolak') $badge = 'badge-ditolak';
                    
                    // Path gambar bukti
                    $img_path = !empty($b['bukti_pembayaran']) ? '../uploads/'.$b['bukti_pembayaran'] : 'https://via.placeholder.com/300?text=Belum+Upload';
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['kode_booking']) ?></strong></td>
                        <td><?= htmlspecialchars($b['nama_customer'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['detail_tiket'] ?? '-') ?></td>
                        <td>Rp <?= number_format($b['total_harga']??0,0,',','.') ?></td>
                        <td><span class="<?= $badge ?>"><?= ucfirst($s) ?></span></td>
                        <td>
                            <!-- TOMBOL CEK BUKTI (PENTING: data-bs-toggle & data-bs-target) -->
                            <button type="button" class="btn btn-cek-bukti btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#buktiModal" 
                                    data-img="<?= $img_path ?>" 
                                    data-id="<?= $b['id_booking'] ?>"
                                    data-kode="<?= htmlspecialchars($b['kode_booking']) ?>">
                                <i class="fas fa-image"></i> Cek Bukti
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL POPUP BUKTI TRANSFER (WAJIB ADA DI SINI) -->
<div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="buktiModalLabel">
            <i class="fas fa-receipt"></i> Bukti Pembayaran: <span id="modalKode">...</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center bg-light p-4">
        <img id="modalImg" src="" alt="Bukti Transfer" class="img-fluid rounded shadow" style="max-height: 400px; max-width: 100%;">
        <p class="text-muted mt-3 small">Periksa nominal dan tanggal transfer sebelum memverifikasi.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <!-- Form TOLAK -->
        <form method="POST" id="formTolak" style="display:inline;">
            <input type="hidden" name="id_booking" id="inputIdTolak">
            <input type="hidden" name="status_baru" value="ditolak">
            <input type="hidden" name="update_status" value="1">
            <button type="submit" class="btn btn-danger px-4" onclick="return confirm('Yakin ingin menolak booking ini?')">
                <i class="fas fa-times"></i> Tolak Booking
            </button>
        </form>
        
        <!-- Form VERIFIKASI -->
        <form method="POST" id="formVerif" style="display:inline;">
            <input type="hidden" name="id_booking" id="inputIdVerif">
            <input type="hidden" name="status_baru" value="terverifikasi">
            <input type="hidden" name="update_status" value="1">
            <button type="submit" class="btn btn-success px-4">
                <i class="fas fa-check"></i> Verifikasi & Setujui
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle (WAJIB UNTUK MODAL) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Pengisi Data Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var buktiModal = document.getElementById('buktiModal');
    
    buktiModal.addEventListener('show.bs.modal', function (event) {
        // Ambil tombol yang memicu modal
        var button = event.relatedTarget;
        
        // Ambil data dari atribut tombol
        var imgSrc = button.getAttribute('data-img');
        var idBooking = button.getAttribute('data-id');
        var kode = button.getAttribute('data-kode');
        
        // Update elemen di dalam modal
        var modalImg = buktiModal.querySelector('#modalImg');
        var modalKode = buktiModal.querySelector('#modalKode');
        var inputIdTolak = buktiModal.querySelector('#inputIdTolak');
        var inputIdVerif = buktiModal.querySelector('#inputIdVerif');
        
        modalImg.src = imgSrc;
        modalKode.textContent = kode;
        inputIdTolak.value = idBooking;
        inputIdVerif.value = idBooking;
    });
});
</script>

</body>
</html>
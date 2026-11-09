<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Proses Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    $id_booking = (int)$_POST['id_booking'];
    $pdo->prepare("UPDATE booking SET status_pembayaran = 'terverifikasi' WHERE id_booking = ?")->execute([$id_booking]);
    header("Location: dashboard.php");
    exit;
}

$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Statistik
$total_hari_ini = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE DATE(tanggal_kunjungan) = ?");
$total_hari_ini->execute([$filter_tanggal]); $count_hari = $total_hari_ini->fetchColumn();

$total_verif = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE DATE(tanggal_kunjungan) = ? AND status_pembayaran = 'terverifikasi'");
$total_verif->execute([$filter_tanggal]); $count_verif = $total_verif->fetchColumn();

$total_pending = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE DATE(tanggal_kunjungan) = ? AND status_pembayaran = 'pending'");
$total_pending->execute([$filter_tanggal]); $count_pending = $total_pending->fetchColumn();

// Data Booking
$stmt = $pdo->prepare("
    SELECT b.*, b.nama_pemesan, c.nama as nama_customer, d.nama_destinasi 
    FROM booking b 
    LEFT JOIN customers c ON b.id_customer = c.id_customer
    LEFT JOIN detail_booking db ON b.id_booking = db.id_booking
    LEFT JOIN destinasi d ON db.id_destinasi = d.id_destinasi
    WHERE DATE(b.tanggal_kunjungan) = ? 
    GROUP BY b.id_booking
    ORDER BY b.tanggal_booking DESC
");
$stmt->execute([$filter_tanggal]); 
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#1a7a4a;--sidebar:#1e293b;}
        body{background:#f4f6f9;font-family:'Segoe UI',sans-serif;}
        .sidebar{background:var(--sidebar);min-height:100vh;color:white;position:fixed;width:240px;}
        .sidebar-brand{padding:20px;font-size:1.2rem;font-weight:bold;border-bottom:1px solid #334155;}
        .sidebar-menu{list-style:none;padding:15px 0;margin:0;}
        .sidebar-menu li a{display:block;padding:12px 20px;color:#cbd5e1;text-decoration:none;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:#334155;color:white;border-left:4px solid var(--primary);}
        .sidebar-menu li a i{margin-right:10px;width:20px;}
        .main{margin-left:240px;padding:30px;}
        .stat-card{border:none;border-radius:12px;color:white;padding:25px;margin-bottom:20px;}
        .stat-card.blue{background:#2563eb;}
        .stat-card.green{background:#16a34a;}
        .stat-card.yellow{background:#f59e0b;color:#1e293b;}
        .content-card{background:white;border-radius:12px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .badge-pending{background:#fbbf24;color:#1e293b;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .badge-terverifikasi{background:#16a34a;color:white;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .badge-ditolak{background:#ef4444;color:white;padding:5px 12px;border-radius:20px;font-size:0.8rem;}
        .filter-box{background:white;padding:15px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-tag"></i> Panel Petugas</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="verifikasi.php"><i class="fas fa-qrcode"></i> Verifikasi Manual</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>
<div class="main">
    <h1 class="fw-bold">Dashboard Petugas</h1>
    <p class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</p>
    
    <div class="filter-box">
        <label class="fw-bold mb-0"><i class="fas fa-calendar-alt"></i> Filter Tanggal:</label>
        <form method="GET" style="display:flex;gap:10px;">
            <input type="date" name="tanggal" value="<?= $filter_tanggal ?>" class="form-control" style="width:200px;">
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-filter"></i> Terapkan</button>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>
    </div>
    
    <div class="row">
        <div class="col-md-4"><div class="stat-card blue"><h6><i class="fas fa-calendar-day"></i> Booking Tanggal Ini</h6><h2><?= $count_hari ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card green"><h6><i class="fas fa-check-circle"></i> Sudah Diverifikasi</h6><h2><?= $count_verif ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card yellow"><h6><i class="fas fa-clock"></i> Menunggu Verifikasi</h6><h2><?= $count_pending ?></h2></div></div>
    </div>
    
    <div class="content-card mt-3">
        <h5 class="fw-bold mb-3">Daftar Booking Tanggal <?= date('d M Y', strtotime($filter_tanggal)) ?></h5>
        <table class="table">
            <thead><tr><th>Kode</th><th>Nama Pemesan</th><th>Tiket</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(count($bookings) > 0): foreach($bookings as $b): 
                $s = $b['status_pembayaran'] ?? 'pending';
                $badge = 'badge-pending';
                if ($s === 'terverifikasi') $badge = 'badge-terverifikasi';
                if ($s === 'ditolak') $badge = 'badge-ditolak';
                $img_path = !empty($b['bukti_pembayaran']) ? '../uploads/'.$b['bukti_pembayaran'] : 'https://via.placeholder.com/300?text=Belum+Upload';
            ?>
                <tr>
                    <td><strong><?= $b['kode_booking'] ?></strong></td>
                    <td><?= htmlspecialchars($b['nama_pemesan'] ?? $b['nama_customer'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['nama_destinasi'] ?? '-') ?></td>
                    <td>Rp <?= number_format($b['total_harga']??0,0,',','.') ?></td>
                    <td><span class="<?= $badge ?>"><?= ucfirst($s) ?></span></td>
                    <td>
                        <!-- Tombol Cek Bukti SELALU MUNCUL untuk semua status -->
                        <button type="button" class="btn btn-info btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#buktiModal" 
                                data-img="<?= $img_path ?>" 
                                data-id="<?= $b['id_booking'] ?>"
                                data-kode="<?= $b['kode_booking'] ?>"
                                data-status="<?= $s ?>">
                            <i class="fas fa-image"></i> Cek Bukti
                        </button>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada booking untuk tanggal ini.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL POPUP BUKTI TRANSFER (PETUGAS) -->
<div class="modal fade" id="buktiModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-receipt"></i> Bukti Pembayaran: <span id="modalKode"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center bg-light">
        <img id="modalImg" src="" class="img-fluid rounded shadow" style="max-height: 400px; max-width: 100%;">
        <p class="text-muted mt-2 small">Periksa bukti transfer sebelum memverifikasi tiket.</p>
        <div class="mt-2">
            <span class="badge" id="modalStatus"></span>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Form Verifikasi - Hanya muncul jika status pending -->
        <form method="POST" id="formAction" style="display:none;">
            <input type="hidden" name="id_booking" id="inputIdBooking">
            <button type="submit" name="verifikasi" class="btn btn-success btn-lg" onclick="return confirm('Yakin ingin memverifikasi tiket ini?')">
                <i class="fas fa-check-circle"></i> Verifikasi Tiket Ini
            </button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var buktiModal = document.getElementById('buktiModal')
    buktiModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget
        var imgSrc = button.getAttribute('data-img')
        var idBooking = button.getAttribute('data-id')
        var kode = button.getAttribute('data-kode')
        var status = button.getAttribute('data-status')
        
        document.getElementById('modalImg').src = imgSrc
        document.getElementById('modalKode').textContent = kode
        document.getElementById('inputIdBooking').value = idBooking
        
        // Tampilkan badge status di modal
        var statusBadge = document.getElementById('modalStatus')
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1)
        if (status === 'pending') {
            statusBadge.className = 'badge bg-warning text-dark'
            document.getElementById('formAction').style.display = 'inline'
        } else if (status === 'terverifikasi') {
            statusBadge.className = 'badge bg-success'
            document.getElementById('formAction').style.display = 'none'
        } else if (status === 'ditolak') {
            statusBadge.className = 'badge bg-danger'
            document.getElementById('formAction').style.display = 'none'
        }
    })
</script>
</body>
</html>
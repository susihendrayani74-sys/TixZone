<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil data user yang login
$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Ambil data tiket dari URL (dari halaman pilih_tiket.php)
$tiket_nama   = $_GET['id'] ?? 'Tiket Pemandian Air Panas';
$harga_satuan = (int)($_GET['harga'] ?? 25000);
$tanggal      = $_GET['tanggal'] ?? '2025-10-12';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemesan - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { color: var(--primary) !important; font-weight: 800; }
        .btn-emte { background-color: var(--primary); color: white; border: none; }
        .btn-emte:hover { background-color: #146c3e; color: white; }
        .text-emte { color: var(--primary); }
        
        /* Stepper */
        .stepper { display: flex; justify-content: center; margin: 30px 0; }
        .step { display: flex; align-items: center; }
        .step-circle { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
        .step.active .step-circle, .step.done .step-circle { background: var(--primary); color: white; }
        .step-label { color: #6c757d; font-weight: 500; }
        .step.active .step-label, .step.done .step-label { color: var(--primary); font-weight: bold; }
        .step-line { width: 60px; height: 3px; background: #dee2e6; margin: 0 15px; }
        .step-line.done { background: var(--primary); }

        .card-box { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .summary-box { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: sticky; top: 20px; }
        .form-control[readonly] { background-color: #e8f4fd; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-mountain"></i> EmtE Highland Resort</a>
        <div class="d-flex align-items-center">
            <span class="me-3 text-muted">Halo, <?= htmlspecialchars($user['nama']) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="text-center fw-bold mb-4">Booking Tiket</h2>

    <!-- Stepper -->
    <div class="stepper">
        <div class="step done"><div class="step-circle">1</div><div class="step-label">Pilih Tiket</div></div>
        <div class="step-line done"></div>
        <div class="step active"><div class="step-circle">2</div><div class="step-label">Data Pemesan</div></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-circle">3</div><div class="step-label">Pembayaran</div></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-circle">4</div><div class="step-label">Konfirmasi</div></div>
    </div>

    <form action="proses_booking.php" method="POST">
        <div class="row g-4">
            <!-- Form Kiri -->
            <div class="col-lg-7">
                <div class="card-box">
                    <h4 class="fw-bold mb-4">Data Pemesan</h4>
                    
                    <!-- Hidden Inputs -->
                    <input type="hidden" name="tiket_nama" value="<?= htmlspecialchars($tiket_nama) ?>">
                    <input type="hidden" name="harga_satuan" value="<?= $harga_satuan ?>">
                    <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
                    <input type="hidden" name="jumlah" id="hidden_jumlah" value="2">
                    <input type="hidden" name="total_harga" id="hidden_total" value="<?= $harga_satuan * 2 ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="input_nama" class="form-control form-control-lg" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor WhatsApp</label>
                        <input type="tel" name="no_hp" class="form-control form-control-lg" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control form-control-lg" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Jumlah Pengunjung</label>
                        <input type="number" name="jumlah_input" id="input_jumlah" class="form-control form-control-lg" value="2" min="1" max="50" required>
                        <div class="form-text text-muted">Harga per orang: Rp <?= number_format($harga_satuan, 0, ',', '.') ?></div>
                    </div>

                    <button type="submit" class="btn btn-emte w-100 py-3 fw-bold fs-5">Lanjut ke Pembayaran <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <!-- Rincian Kanan -->
            <div class="col-lg-5">
                <div class="summary-box">
                    <h4 class="fw-bold mb-3">Rincian Pemesanan</h4>
                    <hr>
                    <div class="mb-3">
                        <h6 class="fw-bold"><?= htmlspecialchars($tiket_nama) ?></h6>
                        <p class="text-muted small mb-0">Rp <?= number_format($harga_satuan, 0, ',', '.') ?> / orang</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Nama Pemesan</span>
                        <strong id="preview_nama"><?= htmlspecialchars($user['nama']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tanggal Kunjungan</span>
                        <strong><?= date('d F Y', strtotime($tanggal)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Jumlah Pengunjung</span>
                        <strong id="preview_jumlah">2 orang</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Total Harga</span>
                        <span class="fw-bold text-emte fs-4" id="preview_total">Rp <?= number_format($harga_satuan * 2, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const inputNama = document.getElementById('input_nama');
    const inputJumlah = document.getElementById('input_jumlah');
    const hiddenJumlah = document.getElementById('hidden_jumlah');
    const hiddenTotal = document.getElementById('hidden_total');
    const previewNama = document.getElementById('preview_nama');
    const previewJumlah = document.getElementById('preview_jumlah');
    const previewTotal = document.getElementById('preview_total');
    const hargaSatuan = <?= $harga_satuan ?>;

    function updateRincian() {
        previewNama.textContent = inputNama.value || 'Belum diisi';
        let jumlah = parseInt(inputJumlah.value) || 1;
        const total = jumlah * hargaSatuan;
        
        previewJumlah.textContent = jumlah + ' orang';
        previewTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
        
        hiddenJumlah.value = jumlah;
        hiddenTotal.value = total;
    }

    inputNama.addEventListener('input', updateRincian);
    inputJumlah.addEventListener('input', updateRincian);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
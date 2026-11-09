<?php
// File: customer/isi_data_pemesan.php
// Halaman Step 2: Data Pemesan (Terintegrasi config.php & Session Native)

require_once '../config.php';
session_start();

// 1. CEK LOGIN (Wajib Customer)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nama = htmlspecialchars($_SESSION['nama']);

// 2. Ambil Data Lengkap Customer dari Database (untuk pre-fill No HP)
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id_customer = ?");
$stmt->execute([$user_id]);
$customer_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika data customer tidak ditemukan di DB, redirect ke login
if (!$customer_data) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// 3. Ambil Data Tiket dari URL (dari halaman booking.php / index.php)
// Menggunakan GET agar user bisa refresh tanpa kehilangan data tiket
$tiket_nama   = trim($_GET['tiket'] ?? 'Tiket Masuk');
$harga_satuan = max(0, (int)($_GET['harga'] ?? 0));
$tanggal      = trim($_GET['tanggal'] ?? date('Y-m-d'));

// Validasi dasar jika harga 0 atau tiket kosong
if ($harga_satuan <= 0 || empty($tiket_nama)) {
    header("Location: booking.php");
    exit;
}
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
        :root { --primary-color: #1a7a4a; --primary-hover: #146c3e; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { color: var(--primary-color) !important; font-weight: 800; }
        .btn-emte { background-color: var(--primary-color); color: white; border: none; transition: 0.3s; }
        .btn-emte:hover { background-color: var(--primary-hover); color: white; }
        
        /* Stepper Styles */
        .stepper-container { display: flex; justify-content: center; align-items: center; margin: 30px 0 40px; flex-wrap: wrap; gap: 10px; }
        .step-item { display: flex; align-items: center; }
        .step-circle { width: 40px; height: 40px; border-radius: 50%; background-color: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 10px; transition: 0.3s; }
        .step-item.active .step-circle, .step-item.completed .step-circle { background-color: var(--primary-color); color: white; }
        .step-label { font-size: 0.9rem; color: #6c757d; font-weight: 500; }
        .step-item.active .step-label, .step-item.completed .step-label { color: var(--primary-color); font-weight: 700; }
        .step-line { width: 60px; height: 3px; background-color: #dee2e6; margin: 0 10px; }
        .step-line.completed { background-color: var(--primary-color); }

        /* Card Styles */
        .form-card, .summary-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .summary-card { position: sticky; top: 20px; }
        .form-control[readonly] { background-color: #e8f4fd; cursor: not-allowed; border-color: #b6e4ff; }
        
        @media (max-width: 991px) {
            .summary-card { position: static; margin-top: 20px; }
            .step-line { display: none; } /* Sembunyikan garis di mobile */
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-mountain"></i> EmtE Highland Resort</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-muted me-3 d-none d-md-block">Halo, <?= $user_nama ?>!</span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 class="text-center fw-bold mb-4" style="color: #212529;">Lengkapi Data Pemesan</h1>

    <!-- Stepper -->
    <div class="stepper-container">
        <div class="step-item completed">
            <div class="step-circle"><i class="fas fa-check"></i></div>
            <div class="step-label">Pilih Tiket</div>
        </div>
        <div class="step-line completed"></div>
        <div class="step-item active">
            <div class="step-circle">2</div>
            <div class="step-label">Data Pemesan</div>
        </div>
        <div class="step-line"></div>
        <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-label">Pembayaran</div>
        </div>
        <div class="step-line"></div>
        <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-label">E-Ticket</div>
        </div>
    </div>

    <form action="pembayaran.php" method="POST" id="formBooking">
        <!-- Hidden Inputs untuk dikirim ke halaman pembayaran -->
        <input type="hidden" name="destinasi_id" value="<?= htmlspecialchars($_GET['id_destinasi'] ?? '') ?>">
        <input type="hidden" name="nama_pemesan" id="hidden_nama" value="<?= htmlspecialchars($customer_data['nama']) ?>">
        <input type="hidden" name="jumlah" id="hidden_jumlah" value="1">
        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">

        <div class="row g-4">
            <!-- KOLOM KIRI: Form Data Pemesan -->
            <div class="col-lg-7">
                <div class="form-card">
                    <h4 class="fw-bold mb-4"><i class="fas fa-user-edit text-success me-2"></i>Informasi Kontak</h4>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap Pemesan</label>
                        <input type="text" name="nama_lengkap" id="input_nama" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($customer_data['nama']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor WhatsApp</label>
                        <input type="tel" name="no_hp" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($customer_data['no_telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                        <div class="form-text text-muted">Digunakan untuk mengirim konfirmasi booking.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control form-control-lg" 
                               value="<?= htmlspecialchars($customer_data['email']) ?>" readonly>
                        <div class="form-text text-muted">Email diambil dari akun Anda dan tidak dapat diubah.</div>
                    </div>

                    <hr class="my-4">

                    <h4 class="fw-bold mb-3"><i class="fas fa-ticket-alt text-success me-2"></i>Detail Kunjungan</h4>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Kunjungan</label>
                        <input type="date" class="form-control form-control-lg" value="<?= htmlspecialchars($tanggal) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Pengunjung</label>
                        <div class="input-group input-group-lg">
                            <button class="btn btn-outline-secondary" type="button" id="btnMin"><i class="fas fa-minus"></i></button>
                            <input type="number" name="jumlah_input" id="input_jumlah" class="form-control text-center fw-bold" 
                                   value="1" min="1" max="50" required>
                            <button class="btn btn-outline-secondary" type="button" id="btnPlus"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="form-text text-muted mt-2">Harga per orang: <strong>Rp <?= number_format($harga_satuan, 0, ',', '.') ?></strong></div>
                    </div>

                    <button type="submit" class="btn btn-emte w-100 py-3 mt-4 fw-bold fs-5 rounded-pill shadow-sm">
                        Lanjut ke Pembayaran <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- KOLOM KANAN: Rincian Pemesanan (Dinamis) -->
            <div class="col-lg-5">
                <div class="summary-card">
                    <h4 class="fw-bold mb-3">Rincian Pemesanan</h4>
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark"><?= htmlspecialchars($tiket_nama) ?></h6>
                        <p class="text-muted small mb-0">Rp <?= number_format($harga_satuan, 0, ',', '.') ?> / orang</p>
                    </div>
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Nama Pemesan</span>
                        <strong id="preview_nama" class="text-end"><?= htmlspecialchars($customer_data['nama']) ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tanggal Kunjungan</span>
                        <strong><?= date('d F Y', strtotime($tanggal)) ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Jumlah Pengunjung</span>
                        <strong id="preview_jumlah">1 orang</strong>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="fw-bold fs-5">Total Harga</span>
                        <span class="fw-bold fs-3" style="color: var(--primary-color);" id="preview_total">Rp <?= number_format($harga_satuan, 0, ',', '.') ?></span>
                    </div>
                    <p class="text-muted small text-end mt-1 mb-0">Belum termasuk biaya admin</p>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Script JavaScript untuk Update Real-Time -->
<script>
    const inputNama    = document.getElementById('input_nama');
    const inputJumlah  = document.getElementById('input_jumlah');
    const hiddenJumlah = document.getElementById('hidden_jumlah');
    const hiddenNama   = document.getElementById('hidden_nama');

    const previewNama   = document.getElementById('preview_nama');
    const previewJumlah = document.getElementById('preview_jumlah');
    const previewTotal  = document.getElementById('preview_total');

    const hargaSatuan = <?= $harga_satuan ?>; 

    // Fungsi Helper untuk format Rupiah
    function formatRupiah(angka) {
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    function updateRincian() {
        // 1. Update Nama
        let nama = inputNama.value.trim();
        if (nama === '') nama = 'Belum diisi';
        previewNama.textContent = nama;
        hiddenNama.value = inputNama.value.trim();

        // 2. Update Jumlah & Total Harga
        let jumlah = parseInt(inputJumlah.value);
        if (isNaN(jumlah) || jumlah < 1) {
            jumlah = 1;
            inputJumlah.value = 1;
        }
        if (jumlah > 50) {
            jumlah = 50;
            inputJumlah.value = 50;
        }

        const total = jumlah * hargaSatuan;

        previewJumlah.textContent = jumlah + ' orang';
        previewTotal.textContent = formatRupiah(total);
        
        hiddenJumlah.value = jumlah;
    }

    // Event Listeners
    inputNama.addEventListener('input', updateRincian);
    inputJumlah.addEventListener('change', updateRincian);
    inputJumlah.addEventListener('input', updateRincian);

    // Tombol Plus Minus
    document.getElementById('btnMin').addEventListener('click', () => {
        let val = parseInt(inputJumlah.value) || 1;
        if (val > 1) {
            inputJumlah.value = val - 1;
            updateRincian();
        }
    });

    document.getElementById('btnPlus').addEventListener('click', () => {
        let val = parseInt(inputJumlah.value) || 1;
        if (val < 50) {
            inputJumlah.value = val + 1;
            updateRincian();
        }
    });

    // Jalankan sekali saat halaman dimuat
    updateRincian();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
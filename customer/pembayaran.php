<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['booking_temp'])) { header("Location: booking.php"); exit; }

$b = $_SESSION['booking_temp'];
$stmt = $pdo->prepare("SELECT nama_destinasi, harga_tiket FROM destinasi WHERE id_destinasi = ?"); 
$stmt->execute([$b['destinasi_id']]); 
$dest = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $dest['harga_tiket'] * $b['jumlah']; 
$tgl = date('d F Y', strtotime($b['tanggal']));

// Generate kode booking sementara untuk QR
$kode_sementara = 'MH' . date('Ymd') . rand(1000, 9999);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Wajib melampirkan foto struk bukti pembayaran.";
    } else {
        $file = $_FILES['bukti'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
            $errors[] = "Berkas harus berupa gambar foto yang valid.";
        }
        if ($file['size'] > 2048 * 1024) {
            $errors[] = "Ukuran berkas struk maksimal adalah 2MB.";
        }
    }

    if (empty($_POST['metode_pembayaran'])) {
        $errors[] = "Metode pembayaran wajib dipilih.";
    }

    if (!empty($errors)) {
        die("Error: " . implode(", ", $errors) . "<br><a href='pembayaran.php'>Kembali</a>");
    }

    $ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
    $nama_file_baru = 'bukti_' . date('YmdHis') . '.' . $ext;
    $folder_upload = '../uploads/';
    if (!is_dir($folder_upload)) { mkdir($folder_upload, 0777, true); } 
    move_uploaded_file($_FILES['bukti']['tmp_name'], $folder_upload . $nama_file_baru);

    $kode = 'MH' . date('Ymd') . rand(1000, 9999);
    $nama_pemesan = $b['nama_pemesan'];
    $metode = $_POST['metode_pembayaran'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO booking (id_customer, kode_booking, nama_pemesan, tanggal_kunjungan, total_harga, status_pembayaran, tanggal_booking, bukti_pembayaran, metode_pembayaran) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $kode, $nama_pemesan, $b['tanggal'], $total, $nama_file_baru, $metode]);
        $id_booking = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO detail_booking (id_booking, id_destinasi, jumlah) VALUES (?, ?, ?)");
        $stmt->execute([$id_booking, $b['destinasi_id'], $b['jumlah']]);

        $qr_code = 'QR-' . $kode;
        $stmt = $pdo->prepare("INSERT INTO e_ticket (id_booking, id_pegawai, qr_code, status_checkin, tanggal_checkin) VALUES (?, NULL, ?, 'belum', NULL)");
        $stmt->execute([$id_booking, $qr_code]);

        $pdo->commit();
        unset($_SESSION['booking_temp']); 
        header("Location: e_ticket.php?id=" . $id_booking); 
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Gagal menyimpan: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; --primary-dark: #145f38; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { color: var(--primary) !important; font-weight: 800; font-size: 1.3rem; }
        .navbar-brand i { margin-right: 8px; }
        .page-title { text-align: center; font-weight: 800; font-size: 2.2rem; margin: 30px 0 20px; }
        
        /* Stepper */
        .stepper { display: flex; justify-content: center; align-items: center; margin-bottom: 40px; padding: 0 20px; }
        .step { display: flex; align-items: center; }
        .step-circle { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; }
        .step-circle.active { background: var(--primary); color: white; }
        .step-circle.done { background: var(--primary); color: white; }
        .step-label { margin-left: 10px; font-weight: 600; color: #6c757d; }
        .step-label.active { color: var(--primary); }
        .step-line { width: 60px; height: 3px; background: #e9ecef; margin: 0 10px; }
        .step-line.done { background: var(--primary); }
        
        /* Card */
        .card-box { border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); padding: 25px; background: white; }
        .card-box h4 { font-weight: 800; margin-bottom: 20px; }
        
        /* Metode Pembayaran Grid */
        .section-title { font-size: 0.85rem; font-weight: 700; color: #6c757d; letter-spacing: 1px; margin-bottom: 12px; margin-top: 10px; }
        .method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .method-item { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; background: white; }
        .method-item:hover { border-color: var(--primary); background: #f8fff9; }
        .method-item.selected { border-color: var(--primary); background: #e8f5e9; }
        .method-item input { display: none; }
        .method-icon { margin-right: 10px; font-size: 1.3rem; }
        .method-icon.bank { color: #0d6efd; }
        .method-icon.wallet { color: #f59e0b; }
        .method-icon.qris { color: #212529; }
        .method-name { font-weight: 700; font-size: 1rem; flex-grow: 1; }
        .method-check { color: var(--primary); font-size: 1.2rem; display: none; }
        .method-item.selected .method-check { display: block; }
        
        /* Info Pembayaran */
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f3f5; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6c757d; }
        .info-value { font-weight: 700; }
        .info-value.total { font-size: 1.3rem; color: var(--primary); }
        .info-value.rekening { color: #0d6efd; font-size: 1.1rem; }
        
        .divider { border-top: 1px solid #dee2e6; margin: 15px 0; }
        
        .btn-confirm { background: var(--primary); color: white; border: none; padding: 15px; font-weight: 700; font-size: 1.1rem; border-radius: 8px; width: 100%; }
        .btn-confirm:hover { background: var(--primary-dark); color: white; }
        .btn-confirm i { margin-right: 8px; }
        
        /* QR Code Box */
        .qr-box {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border: 2px dashed var(--primary);
            border-radius: 12px;
        }
        .qr-box img {
            max-width: 200px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        .qr-box .qr-title {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .qr-box .qr-subtitle {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-mountain"></i> EmtE Highland Resort</a>
    </div>
</nav>

<div class="container py-4">
    <h1 class="page-title">Metode Pembayaran</h1>
    
    <!-- Stepper -->
    <div class="stepper">
        <div class="step">
            <div class="step-circle done">1</div>
            <div class="step-label">Pilih Tiket</div>
        </div>
        <div class="step-line done"></div>
        <div class="step">
            <div class="step-circle done">2</div>
            <div class="step-label">Data Pemesan</div>
        </div>
        <div class="step-line done"></div>
        <div class="step">
            <div class="step-circle active">3</div>
            <div class="step-label active">Pembayaran</div>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-circle">4</div>
            <div class="step-label">Konfirmasi</div>
        </div>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            <!-- KIRI: Pilih Metode -->
            <div class="col-lg-7">
                <div class="card-box">
                    <h4>Pilih Metode Pembayaran</h4>
                    
                    <div class="section-title">TRANSFER BANK</div>
                    <div class="method-grid">
                        <label class="method-item selected" data-bank="BCA" data-nomor="1234 5678 9012" data-nama="EmtE Highland Resort" data-qr="false">
                            <input type="radio" name="metode_pembayaran" value="BCA" checked>
                            <i class="fas fa-university method-icon bank"></i>
                            <span class="method-name">BCA</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="BRI" data-nomor="0987 6543 2100" data-nama="EmtE Highland Resort" data-qr="false">
                            <input type="radio" name="metode_pembayaran" value="BRI">
                            <i class="fas fa-university method-icon bank"></i>
                            <span class="method-name">BRI</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="BNI" data-nomor="0456 7890 1234" data-nama="EmtE Highland Resort" data-qr="false">
                            <input type="radio" name="metode_pembayaran" value="BNI">
                            <i class="fas fa-university method-icon bank"></i>
                            <span class="method-name">BNI</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="Mandiri" data-nomor="1300 0555 6666" data-nama="EmtE Highland Resort" data-qr="false">
                            <input type="radio" name="metode_pembayaran" value="Mandiri">
                            <i class="fas fa-university method-icon bank"></i>
                            <span class="method-name">Mandiri</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                    </div>
                    
                    <div class="section-title mt-4">E-WALLET</div>
                    <div class="method-grid">
                        <label class="method-item" data-bank="DANA" data-nomor="0812-3456-7890" data-nama="EmtE Resort" data-qr="true">
                            <input type="radio" name="metode_pembayaran" value="DANA">
                            <i class="fas fa-wallet method-icon wallet"></i>
                            <span class="method-name">DANA</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="OVO" data-nomor="0812-3456-7890" data-nama="EmtE Resort" data-qr="true">
                            <input type="radio" name="metode_pembayaran" value="OVO">
                            <i class="fas fa-wallet method-icon wallet"></i>
                            <span class="method-name">OVO</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="GoPay" data-nomor="0812-3456-7890" data-nama="EmtE Resort" data-qr="true">
                            <input type="radio" name="metode_pembayaran" value="GoPay">
                            <i class="fas fa-wallet method-icon wallet"></i>
                            <span class="method-name">GoPay</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                        <label class="method-item" data-bank="ShopeePay" data-nomor="0812-3456-7890" data-nama="EmtE Resort" data-qr="true">
                            <input type="radio" name="metode_pembayaran" value="ShopeePay">
                            <i class="fas fa-wallet method-icon wallet"></i>
                            <span class="method-name">ShopeePay</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                    </div>
                    
                    <div class="section-title mt-4">QRIS</div>
                    <div class="method-grid">
                        <label class="method-item" data-bank="QRIS" data-nomor="Scan QR di Samping" data-nama="EmtE Highland Resort" data-qr="true">
                            <input type="radio" name="metode_pembayaran" value="QRIS">
                            <i class="fas fa-qrcode method-icon qris"></i>
                            <span class="method-name">QRIS</span>
                            <i class="fas fa-check-circle method-check"></i>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- KANAN: Informasi Pembayaran -->
            <div class="col-lg-5">
                <div class="card-box">
                    <h4>Informasi Pembayaran</h4>
                    
                    <div class="info-row">
                        <span class="info-label">Total Pembayaran</span>
                        <span class="info-value total">Rp <?= number_format($total,0,',','.') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Rekening</span>
                        <span class="info-value rekening" id="rekNomor">1234 5678 9012</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Atas Nama</span>
                        <span class="info-value" id="rekNama">EmtE Highland Resort</span>
                    </div>
                    
                    <!-- QR CODE BOX -->
                    <div id="qrBox" class="qr-box" style="display: none;">
                        <div class="qr-title"><i class="fas fa-qrcode"></i> Scan QR untuk Pembayaran</div>
                        <img id="qrImage" src="" alt="QR Code">
                        <div class="qr-subtitle">Gunakan aplikasi DANA, OVO, GoPay, atau Mobile Banking</div>
                        <div class="qr-subtitle" style="font-size: 0.7rem; color: #999;">Kode: <strong id="qrKode"><?= $kode_sementara ?></strong></div>
                    </div>
                    <!-- END QR BOX -->
                    
                    <div class="divider"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Upload Bukti Pembayaran</label>
                        <input type="file" name="bukti" class="form-control" accept="image/*" required>
                        <small class="text-muted">Format: JPG, PNG (Maks 2MB)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-confirm mt-3">
                        <i class="fas fa-check-circle"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Fungsi untuk update info pembayaran dan QR Code
function updatePaymentInfo(bank, nomor, nama, showQR) {
    document.getElementById('rekNomor').innerText = nomor;
    document.getElementById('rekNama').innerText = nama;
    
    const qrBox = document.getElementById('qrBox');
    const qrImage = document.getElementById('qrImage');
    
    if (showQR === 'true') {
        // Generate QR Code menggunakan Google Charts API
        const total = '<?= $total ?>';
        const kode = '<?= $kode_sementara ?>';
        const qrData = `EMTE-RESORT|${kode}|${total}|${bank}`;
        const qrUrl = `https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=${encodeURIComponent(qrData)}&choe=UTF-8`;
        
        qrImage.src = qrUrl;
        qrBox.style.display = 'block';
    } else {
        qrBox.style.display = 'none';
    }
}

// Event listener untuk setiap metode pembayaran
document.querySelectorAll('.method-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.method-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
        
        const bank = this.dataset.bank;
        const nomor = this.dataset.nomor;
        const nama = this.dataset.nama;
        const showQR = this.dataset.qr;
        
        updatePaymentInfo(bank, nomor, nama, showQR);
    });
});

// Inisialisasi pertama (BCA, tanpa QR)
updatePaymentInfo('BCA', '1234 5678 9012', 'EmtE Highland Resort', 'false');
</script>

</body>
</html>
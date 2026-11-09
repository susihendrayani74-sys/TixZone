<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id_customer = ?"); 
$stmt->execute([$_SESSION['user_id']]); 
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

$destinasi_list = $pdo->query("SELECT id_destinasi, nama_destinasi, harga_tiket FROM destinasi WHERE status = 'aktif'")->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinasi_id = isset($_POST['destinasi_id']) ? (int)$_POST['destinasi_id'] : 0;
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $no_telepon = isset($_POST['no_telepon']) ? trim($_POST['no_telepon']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // Validasi yang lebih longgar dan jelas
    if ($destinasi_id <= 0 || empty($tanggal) || $jumlah <= 0 || empty($nama)) {
        $error = "Mohon lengkapi semua data: tiket, tanggal, jumlah, dan nama pemesan.";
    } else {
        $_SESSION['booking_temp'] = [
            'destinasi_id' => $destinasi_id,
            'tanggal' => $tanggal,
            'jumlah' => $jumlah,
            'nama_pemesan' => $nama,
            'no_telepon' => $no_telepon,
            'email' => $email
        ];
        header("Location: pembayaran.php"); 
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Tiket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background: #f8f9fa; }
        .btn-primary-custom { background: var(--primary); color: white; border: none; }
        .btn-primary-custom:hover { background: #145f38; color: white; }
        .card-box { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .total-harga { font-size: 1.5rem; font-weight: bold; color: var(--primary); }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php" style="color:var(--primary)">EmtE Highland Resort</a>
        <a href="dashboard.php" class="btn btn-outline-success btn-sm">Dashboard</a>
    </div>
</nav>

<div class="container py-4">
    <h3 class="text-center mb-4 fw-bold">Booking Tiket</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card card-box p-4">
                    <h5 class="fw-bold mb-3">Rincian Pemesanan</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Tiket *</label>
                        <select name="destinasi_id" id="destinasi_id" class="form-select" required onchange="hitungTotal()">
                            <option value="">-- Pilih Destinasi --</option>
                            <?php foreach($destinasi_list as $d): ?>
                                <option value="<?= $d['id_destinasi'] ?>" data-harga="<?= $d['harga_tiket'] ?>">
                                    <?= htmlspecialchars($d['nama_destinasi']) ?> - Rp <?= number_format($d['harga_tiket'],0,',','.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tanggal Kunjungan *</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jumlah Pengunjung *</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" value="1" min="1" required oninput="hitungTotal()">
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Harga</span>
                            <span id="total_harga" class="total-harga">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-box p-4">
                    <h5 class="fw-bold mb-3">Data Pemesan</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Pemesan (Bebas) *</label>
                        <input type="text" name="nama" class="form-control" value="" placeholder="Ketik nama siapa saja..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nomor WhatsApp *</label>
                        <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($customer['no_telepon'] ?? '') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 btn-lg">Lanjut ke Pembayaran</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function hitungTotal() {
    const s = document.getElementById('destinasi_id');
    const j = document.getElementById('jumlah').value || 1;
    const h = s.options[s.selectedIndex]?.getAttribute('data-harga') || 0;
    document.getElementById('total_harga').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(h * j);
}
</script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pesan = ""; $tipe = ""; $data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_booking']);
    $stmt = $pdo->prepare("SELECT b.*, c.nama FROM booking b JOIN customers c ON b.id_customer = c.id_customer WHERE b.kode_booking = ?");
    $stmt->execute([$kode]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $pesan = "Kode Booking tidak ditemukan!"; $tipe = "danger";
    } elseif ($data['status_pembayaran'] === 'terverifikasi') {
        $pesan = "Tiket ini sudah pernah diverifikasi/dipakai!"; $tipe = "warning";
    } else {
        $pdo->prepare("UPDATE booking SET status_pembayaran = 'terverifikasi' WHERE kode_booking = ?")->execute([$kode]);
        $pesan = "Tiket berhasil diverifikasi! Selamat menikmati."; $tipe = "success";
        $data['status_pembayaran'] = 'terverifikasi';
    }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Verifikasi Petugas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>:root{--primary:#1a7a4a;}body{background:#f4f6f9;}.card-box{background:white;border-radius:12px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}.ticket-box{border:2px dashed var(--primary);border-radius:12px;padding:20px;background:#f8fff9;}</style></head><body>
<nav class="navbar navbar-light bg-white shadow-sm"><div class="container"><a class="navbar-brand fw-bold" href="../index.php" style="color:var(--primary)">Panel Petugas</a><a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a></div></nav>
<div class="container py-4"><h3 class="text-center mb-4 fw-bold">Verifikasi Tiket Pengunjung</h3>

<?php if ($pesan): ?><div class="alert alert-<?= $tipe ?> text-center"><?= $pesan ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card-box">
            <h5 class="fw-bold mb-3">Cari Kode Booking</h5>
            <form method="POST">
                <input type="text" name="kode_booking" class="form-control form-control-lg text-center mb-3" placeholder="Contoh: MH20261010..." required autofocus>
                <button type="submit" class="btn btn-success w-100 btn-lg">Verifikasi Sekarang</button>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card-box">
            <h5 class="fw-bold mb-3">Detail Tiket</h5>
            <?php if ($data): ?>
            <div class="ticket-box">
                <div class="row">
                    <div class="col-7">
                        <div class="text-muted small">Kode Booking</div><h4 class="fw-bold text-success"><?= $data['kode_booking'] ?></h4>
                        <div class="text-muted small">Nama Pemesan</div><h5 class="fw-bold"><?= htmlspecialchars($data['nama_pemesan']) ?></h5>
                        <div class="text-muted small">Nama Akun</div><div class="fw-bold"><?= htmlspecialchars($data['nama']) ?></div>
                    </div>
                    <div class="col-5 text-end">
                        <div class="text-muted small">Tanggal Kunjungan</div><div class="fw-bold"><?= date('d M Y', strtotime($data['tanggal_kunjungan'])) ?></div>
                        <div class="text-muted small">Total Bayar</div><div class="fw-bold text-success fs-4">Rp <?= number_format($data['total_harga'],0,',','.') ?></div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <div class="text-muted small mb-1">Status Tiket</div>
                    <h4 class="fw-bold <?= $data['status_pembayaran'] === 'terverifikasi' ? 'text-success' : 'text-warning' ?>"><?= strtoupper($data['status_pembayaran']) ?></h4>
                </div>
            </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted"><p>Masukkan kode booking di sebelah kiri untuk melihat detail.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div></div></body></html>
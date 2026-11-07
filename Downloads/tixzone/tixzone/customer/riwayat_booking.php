<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
cek_login('customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = 'TIX-' . strtoupper(substr(md5(time()), 0, 6));
    $stmt = $pdo->prepare("INSERT INTO booking (kode_booking, customer_id, tanggal_kunjungan, total_harga, status_pembayaran) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$kode, $_SESSION['user_id'], $_POST['tanggal'], $_POST['total']]);
    header("Location: home.php");
    exit;
}

$destinasi = $pdo->query("SELECT * FROM destinasi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Tiket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-body p-4">
                <h3 class="text-center mb-4" style="color: #1a7a4a;">Booking Tiket</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Pilih Destinasi</label>
                        <select name="destinasi" class="form-select" id="destinasi" required>
                            <?php foreach($destinasi as $d): ?>
                                <option value="<?= $d['harga'] ?>"><?= $d['nama_destinasi'] ?> - Rp <?= number_format($d['harga'],0,',','.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Kunjungan</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Tiket</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Harga</label>
                        <input type="number" name="total" id="total" class="form-control" readonly>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Bayar Sekarang</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function hitungTotal() {
            const harga = document.getElementById('destinasi').value;
            const jumlah = document.getElementById('jumlah').value;
            document.getElementById('total').value = harga * jumlah;
        }
        document.getElementById('destinasi').addEventListener('change', hitungTotal);
        document.getElementById('jumlah').addEventListener('input', hitungTotal);
        hitungTotal();
    </script>
</body>
</html>
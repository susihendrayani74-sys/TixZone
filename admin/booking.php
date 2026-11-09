<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id_booking']; $aksi = $_POST['aksi'];
    if ($aksi === 'terima') $pdo->prepare("UPDATE booking SET status_pembayaran = 'terverifikasi' WHERE id_booking = ?")->execute([$id]);
    elseif ($aksi === 'tolak') $pdo->prepare("UPDATE booking SET status_pembayaran = 'ditolak' WHERE id_booking = ?")->execute([$id]);
}
$bookings = $pdo->query("SELECT b.*, c.nama, c.email FROM booking b LEFT JOIN customers c ON b.id_customer = c.id_customer ORDER BY b.tanggal_booking DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Kelola Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>:root{--primary:#1a7a4a;--sidebar:#1e293b;}body{background:#f4f6f9;}.sidebar{background:var(--sidebar);min-height:100vh;color:white;position:fixed;width:240px;}.sidebar-brand{padding:20px;font-weight:bold;border-bottom:1px solid #334155;}.sidebar-menu{list-style:none;padding:15px 0;}.sidebar-menu li a{display:block;padding:12px 20px;color:#cbd5e1;text-decoration:none;}.sidebar-menu li a:hover,.sidebar-menu li a.active{background:#334155;color:white;border-left:4px solid var(--primary);}.main{margin-left:240px;padding:30px;}.content-card{background:white;border-radius:12px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}.badge-berhasil{background:#16a34a;color:white;padding:5px 12px;border-radius:20px;}.badge-pending{background:#fbbf24;color:#1e293b;padding:5px 12px;border-radius:20px;}.badge-ditolak{background:#ef4444;color:white;padding:5px 12px;border-radius:20px;}</style></head><body>
<div class="sidebar"><div class="sidebar-brand"><i class="fas fa-mountain"></i> Admin Panel</div><ul class="sidebar-menu">
<li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
<li><a href="booking.php" class="active"><i class="fas fa-calendar-check"></i> Kelola Booking</a></li>
<li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
</ul></div>
<div class="main"><h1 class="fw-bold">Kelola Booking</h1>
<div class="content-card"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Email</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($bookings as $b): $s=$b['status_pembayaran']??'pending'; if($s==='terverifikasi'||$s==='berhasil'){$badge='badge-berhasil';$label='Berhasil';}elseif($s==='ditolak'){$badge='badge-ditolak';$label='Ditolak';}else{$badge='badge-pending';$label='Pending';} ?>
<tr><td><?= $b['kode_booking'] ?></td><td><?= htmlspecialchars($b['nama']??'-') ?></td><td><?= htmlspecialchars($b['email']??'-') ?></td><td><?= date('d M Y', strtotime($b['tanggal_kunjungan'])) ?></td><td>Rp <?= number_format($b['total_harga']??0,0,',','.') ?></td><td><span class="<?= $badge ?>"><?= $label ?></span></td><td>
<?php if($s==='pending'): ?><form method="POST" style="display:inline;"><input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>"><input type="hidden" name="aksi" value="terima"><button class="btn btn-sm btn-success">Terima</button></form><form method="POST" style="display:inline;"><input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>"><input type="hidden" name="aksi" value="tolak"><button class="btn btn-sm btn-danger">Tolak</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div></div></body></html>
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
cek_login('admin');

$pesan = "";
$tipe_pesan = "";

// ============================================
// PROSES VERIFIKASI (Terima / Tolak)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_booking = (int)($_POST['booking_id'] ?? 0);
    $aksi       = $_POST['aksi'] ?? ''; // 'terima' atau 'tolak'

    if ($id_booking > 0 && in_array($aksi, ['terima', 'tolak'])) {
        $pdo->beginTransaction();
        try {
            // 1. Ambil data booking untuk validasi
            $stmt = $pdo->prepare("SELECT * FROM booking WHERE id = ?");
            $stmt->execute([$id_booking]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Data booking tidak ditemukan.");
            }

            if ($aksi === 'terima') {
                // Update status pembayaran
                $upd = $pdo->prepare("
                    UPDATE booking 
                    SET status_pembayaran = 'terverifikasi' 
                    WHERE id = ?
                ");
                $upd->execute([$id_booking]);

                // 2. Generate e_ticket untuk setiap item di detail_booking
                $det = $pdo->prepare("SELECT * FROM detail_booking WHERE booking_id = ?");
                $det->execute([$id_booking]);
                $items = $det->fetchAll();

                foreach ($items as $it) {
                    for ($i = 0; $i < $it['jumlah']; $i++) {
                        $kode_ticket = 'ETK-' . strtoupper(substr(md5(uniqid(mt_rand(), true) . $i), 0, 10));
                        $ins = $pdo->prepare("
                            INSERT INTO e_ticket (kode_ticket, booking_id, customer_id, status) 
                            VALUES (?, ?, ?, 'aktif')
                        ");
                        $ins->execute([$kode_ticket, $id_booking, $booking['customer_id']]);
                    }
                }

                $pesan = "✅ Pembayaran berhasil diverifikasi. E-Ticket telah dibuat.";
                $tipe_pesan = "success";

            } else { // tolak
                $upd = $pdo->prepare("
                    UPDATE booking 
                    SET status_pembayaran = 'ditolak', metode_pembayaran = NULL, bukti_pembayaran = NULL 
                    WHERE id = ?
                ");
                $upd->execute([$id_booking]);

                $pesan = "❌ Pembayaran ditolak. Customer dapat upload bukti baru.";
                $tipe_pesan = "warning";
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $pesan = "❌ Error: " . $e->getMessage();
            $tipe_pesan = "error";
        }
    }
}

// ============================================
// FILTER & PENCARIAN
// ============================================
$filter_status = $_GET['status'] ?? 'pending'; // pending, terverifikasi, ditolak, semua
$cari          = trim($_GET['cari'] ?? '');

$where = "1=1";
$params = [];

if ($filter_status !== 'semua') {
    $where .= " AND b.status_pembayaran = ?";
    $params[] = $filter_status;
}

if ($cari !== '') {
    $where .= " AND (b.kode_booking LIKE ? OR c.nama LIKE ? OR c.email LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

// ============================================
// AMBIL DATA BOOKING
// ============================================
$sql = "
    SELECT b.*, c.nama AS customer_nama, c.email, c.no_hp,
           COUNT(et.id) AS jumlah_e_ticket
    FROM booking b
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN e_ticket et ON et.booking_id = b.id
    WHERE $where
    GROUP BY b.id
    ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Hitung total per status untuk badge
$counter = $pdo->query("
    SELECT 
        SUM(CASE WHEN status_pembayaran = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END) AS terverifikasi,
        SUM(CASE WHEN status_pembayaran = 'ditolak' THEN 1 ELSE 0 END) AS ditolak
    FROM booking
")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Admin TixZone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }
        .badge-pending       { background: #f0ad4e; }
        .badge-terverifikasi { background: #5cb85c; }
        .badge-ditolak       { background: #d9534f; }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar select, .filter-bar input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .filter-bar button {
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-bar button:hover { background: #0056b3; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        tr:hover { background: #f9f9f9; }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-terima { background: #5cb85c; color: #fff; }
        .btn-terima:hover { background: #4cae4c; }
        .btn-tolak  { background: #d9534f; color: #fff; }
        .btn-tolak:hover  { background: #c9302c; }
        .btn-lihat  { background: #5bc0de; color: #fff; }
        .btn-lihat:hover  { background: #46b8da; }
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .bukti-img {
            max-width: 100px;
            max-height: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .stats-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border-radius: 6px;
            color: #fff;
            text-align: center;
        }
        .stat-pending       { background: #f0ad4e; }
        .stat-terverifikasi { background: #5cb85c; }
        .stat-ditolak       { background: #d9534f; }
        .stat-card h3 { margin: 0; font-size: 28px; }
        .stat-card p  { margin: 5px 0 0; font-size: 14px; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
        }
        .modal-content {
            margin: 5% auto;
            max-width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        .modal-content img {
            max-width: 100%;
            height: auto;
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
    </style>
</head>
<body>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content" style="margin-left: 250px; padding: 20px;">

    <div class="page-header">
        <h1>💳 Verifikasi Pembayaran</h1>
        <a href="dashboard.php" class="btn" style="background:#6c757d; color:#fff;">← Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= $pesan ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-bar">
        <div class="stat-card stat-pending">
            <h3><?= (int)$counter['pending'] ?></h3>
            <p>Menunggu Verifikasi</p>
        </div>
        <div class="stat-card stat-terverifikasi">
            <h3><?= (int)$counter['terverifikasi'] ?></h3>
            <p>Terverifikasi</p>
        </div>
        <div class="stat-card stat-ditolak">
            <h3><?= (int)$counter['ditolak'] ?></h3>
            <p>Ditolak</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" class="filter-bar">
        <select name="status">
            <option value="pending"       <?= $filter_status === 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
            <option value="terverifikasi" <?= $filter_status === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
            <option value="ditolak"       <?= $filter_status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
            <option value="semua"         <?= $filter_status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
        </select>
        <input type="text" name="cari" placeholder="Cari kode booking / nama / email..." value="<?= htmlspecialchars($cari) ?>">
        <button type="submit">🔍 Filter</button>
        <a href="verifikasi_pembayaran.php" class="btn" style="background:#6c757d; color:#fff;">Reset</a>
    </form>

    <!-- Tabel Booking -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Booking</th>
                <th>Customer</th>
                <th>Tanggal Kunjungan</th>
                <th>Total Harga</th>
                <th>Metode</th>
                <th>Bukti Bayar</th>
                <th>Status</th>
                <th>E-Ticket</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($bookings)): ?>
            <tr>
                <td colspan="10" style="text-align:center; padding:30px;">
                    Tidak ada data booking untuk filter ini.
                </td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($bookings as $b): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($b['kode_booking']) ?></strong></td>
                <td>
                    <?= htmlspecialchars($b['customer_nama']) ?><br>
                    <small style="color:#666;"><?= htmlspecialchars($b['email']) ?></small><br>
                    <small style="color:#666;"><?= htmlspecialchars($b['no_hp']) ?></small>
                </td>
                <td><?= $b['tanggal_kunjungan'] ?></td>
                <td><strong>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></strong></td>
                <td><?= htmlspecialchars($b['metode_pembayaran'] ?? '-') ?></td>
                <td>
                    <?php if ($b['bukti_pembayaran']): 
                        $ext = strtolower(pathinfo($b['bukti_pembayaran'], PATHINFO_EXTENSION));
                        $file_path = "../uploads/bukti_bayar/" . $b['bukti_pembayaran'];
                    ?>
                        <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                            <img src="<?= $file_path ?>" class="bukti-img" 
                                 onclick="showModal('<?= $file_path ?>')" 
                                 title="Klik untuk memperbesar">
                        <?php else: ?>
                            <a href="<?= $file_path ?>" target="_blank" class="btn btn-lihat">📄 Lihat</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#999;">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                        $badge_class = 'badge-' . $b['status_pembayaran'];
                        $label = strtoupper($b['status_pembayaran']);
                    ?>
                    <span class="badge <?= $badge_class ?>"><?= $label ?></span>
                </td>
                <td style="text-align:center;">
                    <?= $b['jumlah_e_ticket'] ?>
                </td>
                <td>
                    <?php if ($b['status_pembayaran'] === 'pending'): ?>
                        <form method="POST" style="display:inline;" 
                              onsubmit="return confirm('Terima pembayaran ini dan buat E-Ticket?');">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <button type="submit" name="aksi" value="terima" class="btn btn-terima">✓ Terima</button>
                        </form>
                        <form method="POST" style="display:inline;" 
                              onsubmit="return confirm('Yakin tolak pembayaran ini?');">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <button type="submit" name="aksi" value="tolak" class="btn btn-tolak">✗ Tolak</button>
                        </form>
                    <?php elseif ($b['status_pembayaran'] === 'terverifikasi'): ?>
                        <a href="../customer/e_ticket.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-lihat">️ Lihat E-Ticket</a>
                    <?php else: ?>
                        <span style="color:#999; font-size:13px;">Menunggu upload baru</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Modal untuk preview gambar bukti pembayaran -->
<div id="buktiModal" class="modal" onclick="closeModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h
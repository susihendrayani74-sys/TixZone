<?php
require_once '../config.php';
session_start();

// 1. Cek Autentikasi & Otorisasi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// === PROSES FORM (POST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // A. SIMPAN / UPDATE DESTINASI
    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id_destinasi'] ?? 0);
        $nama = trim($_POST['nama_destinasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '') ?: null;
        $fasilitas = trim($_POST['fasilitas'] ?? '') ?: null;
        $jam_operasional = trim($_POST['jam_operasional'] ?? '') ?: null;
        $id_kategori = (int) ($_POST['id_kategori'] ?? 0) ?: null;
        $harga_tiket = max(0, (float) ($_POST['harga_tiket'] ?? 0));
        $kuota = max(0, (int) ($_POST['kuota'] ?? 0));
        $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($nama === '') {
            $_SESSION['flash_error'] = "Nama destinasi wajib diisi.";
        } else {
            try {
                if ($id > 0) {
                    // Update data existing
                    $stmt = $pdo->prepare('UPDATE destinasi SET nama_destinasi=?, deskripsi=?, fasilitas=?, jam_operasional=?, id_kategori=?, harga_tiket=?, kuota=?, status=? WHERE id_destinasi=?');
                    $stmt->execute([$nama, $deskripsi, $fasilitas, $jam_operasional, $id_kategori, $harga_tiket, $kuota, $status, $id]);
                    $_SESSION['flash_success'] = "Destinasi berhasil diperbarui.";
                } else {
                    // Tambah data baru
                    $stmt = $pdo->prepare('INSERT INTO destinasi (nama_destinasi, deskripsi, fasilitas, jam_operasional, id_kategori, harga_tiket, kuota, status) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$nama, $deskripsi, $fasilitas, $jam_operasional, $id_kategori, $harga_tiket, $kuota, $status]);
                    $_SESSION['flash_success'] = "Destinasi baru berhasil ditambahkan.";
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Gagal menyimpan data: " . $e->getMessage();
            }
        }
        header("Location: destinasi.php");
        exit;
    } 
    
    // B. HAPUS / NONAKTIFKAN DESTINASI
    elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id_destinasi'] ?? 0);
        
        try {
            // Cek apakah destinasi ini sudah pernah dibooking
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM detail_booking WHERE id_destinasi = ?');
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() > 0) {
                // SOFT DELETE: Jika ada riwayat, hanya nonaktifkan agar data transaksi aman
                $pdo->prepare("UPDATE destinasi SET status='nonaktif' WHERE id_destinasi=?")->execute([$id]);
                $_SESSION['flash_warning'] = "Destinasi memiliki riwayat booking, sehingga hanya dinonaktifkan (tidak dihapus permanen).";
            } else {
                // HARD DELETE: Jika belum ada riwayat, hapus permanen
                $pdo->prepare('DELETE FROM destinasi WHERE id_destinasi=?')->execute([$id]);
                $_SESSION['flash_success'] = "Destinasi berhasil dihapus permanen.";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Gagal memproses penghapusan: " . $e->getMessage();
        }
        header("Location: destinasi.php");
        exit;
    }
    
    // C. TAMBAH KATEGORI
    elseif ($aksi === 'kategori') {
        $nama_kat = trim($_POST['nama_kategori'] ?? '');
        $desc_kat = trim($_POST['deskripsi_kategori'] ?? '') ?: null;
        
        if ($nama_kat !== '') {
            try {
                $pdo->prepare('INSERT INTO kategori_destinasi (nama_kategori, deskripsi) VALUES (?, ?)')->execute([$nama_kat, $desc_kat]);
                $_SESSION['flash_success'] = "Kategori baru berhasil ditambahkan.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Gagal menambah kategori: " . $e->getMessage();
            }
        }
        header("Location: destinasi.php");
        exit;
    }
}

// === AMBIL DATA UNTUK DITAMPILKAN (GET) ===
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM destinasi WHERE id_destinasi = ?');
    $s->execute([(int) $_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
}

$kategori = $pdo->query('SELECT * FROM kategori_destinasi ORDER BY nama_kategori')->fetchAll(PDO::FETCH_ASSOC);
$list = $pdo->query('SELECT d.*, k.nama_kategori FROM destinasi d LEFT JOIN kategori_destinasi k ON k.id_kategori = d.id_kategori ORDER BY d.id_destinasi DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Destinasi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; --sidebar: #1e293b; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: var(--sidebar); min-height: 100vh; color: white; position: fixed; width: 240px; z-index: 1000; }
        .sidebar-brand { padding: 20px; font-size: 1.2rem; font-weight: bold; border-bottom: 1px solid #334155; }
        .sidebar-menu { list-style: none; padding: 15px 0; margin: 0; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #cbd5e1; text-decoration: none; transition: 0.2s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #334155; color: white; border-left: 4px solid var(--primary); }
        .sidebar-menu li a i { margin-right: 10px; width: 20px; }
        .main { margin-left: 240px; padding: 30px; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .badge-aktif { background: #16a34a; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-nonaktif { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-shield"></i> Panel Admin</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="customers.php"><i class="fas fa-users"></i> Data Customer</a></li>
        <li><a href="destinasi.php" class="active"><i class="fas fa-map-marked-alt"></i> Kelola Destinasi</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1 class="fw-bold mb-4">Kelola Destinasi</h1>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_warning']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_warning']); ?>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT DESTINASI -->
    <div class="content-card">
        <h4 class="fw-bold mb-3"><?= $edit ? '<i class="fas fa-edit"></i> Edit Destinasi' : '<i class="fas fa-plus-circle"></i> Tambah Destinasi Baru' ?></h4>
        <form method="post">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id_destinasi" value="<?= (int) ($edit['id_destinasi'] ?? 0) ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nama Destinasi</label>
                    <input type="text" name="nama_destinasi" class="form-control" value="<?= htmlspecialchars($edit['nama_destinasi'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Kategori</label>
                    <select name="id_kategori" class="form-select">
                        <option value="">- Pilih Kategori -</option>
                        <?php foreach ($kategori as $k): ?>
                            <option value="<?= (int) $k['id_kategori'] ?>" <?= ((int)($edit['id_kategori'] ?? 0)) == $k['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Harga Tiket (Rp)</label>
                    <input type="number" name="harga_tiket" class="form-control" min="0" value="<?= (int) ($edit['harga_tiket'] ?? 0) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Kuota per Hari</label>
                    <input type="number" name="kuota" class="form-control" min="0" value="<?= (int) ($edit['kuota'] ?? 100) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= ($edit['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($edit['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Jam Operasional</label>
                    <input type="text" name="jam_operasional" class="form-control" placeholder="Contoh: 08.00 - 17.00" value="<?= htmlspecialchars($edit['jam_operasional'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Fasilitas</label>
                    <textarea name="fasilitas" class="form-control" rows="2"><?= htmlspecialchars($edit['fasilitas'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary px-4"><?= $edit ? 'Simpan Perubahan' : 'Tambah Destinasi' ?></button>
                    <?php if ($edit): ?>
                        <a href="destinasi.php" class="btn btn-outline-secondary px-4">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR DESTINASI -->
    <div class="content-card">
        <h4 class="fw-bold mb-3">Daftar Destinasi</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Kuota/Hari</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list) > 0): ?>
                    <?php foreach ($list as $d): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($d['nama_destinasi']) ?></td>
                            <td><?= htmlspecialchars($d['nama_kategori'] ?? '-') ?></td>
                            <td>Rp <?= number_format($d['harga_tiket'], 0, ',', '.') ?></td>
                            <td><?= (int) $d['kuota'] ?></td>
                            <td>
                                <span class="<?= $d['status'] === 'aktif' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                    <?= htmlspecialchars($d['status']) ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap">
                                <a href="destinasi.php?edit=<?= (int) $d['id_destinasi'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus destinasi ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_destinasi" value="<?= (int) $d['id_destinasi'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data destinasi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FORM TAMBAH KATEGORI -->
    <div class="content-card">
        <h4 class="fw-bold mb-3">Kategori Destinasi</h4>
        <p class="text-muted small mb-3">
            <?php if ($kategori): ?>
                Kategori saat ini: 
                <?php foreach ($kategori as $index => $k): ?>
                    <span class="badge bg-secondary me-1"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                Belum ada kategori yang dibuat.
            <?php endif; ?>
        </p>
        <form method="post" class="row g-3 align-items-end">
            <input type="hidden" name="aksi" value="kategori">
            <div class="col-md-5">
                <label class="form-label fw-bold small">Nama Kategori</label>
                <input type="text" name="nama_kategori" class="form-control" required>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small">Deskripsi (Opsional)</label>
                <input type="text" name="deskripsi_kategori" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus"></i> Tambah</button>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
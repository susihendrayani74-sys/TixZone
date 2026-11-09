<?php
require_once '../config.php';
session_start();

// Cek autentikasi & otorisasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// === PROSES TOGGLE STATUS (POST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_customer'])) {
    $id = (int) ($_POST['id_customer'] ?? 0);
    $q = trim($_POST['q'] ?? '');
    
    if ($id > 0) {
        try {
            // Cek status saat ini
            $checkStmt = $pdo->prepare("SELECT status FROM customers WHERE id_customer = ?");
            $checkStmt->execute([$id]);
            $currentStatus = $checkStmt->fetchColumn();
            
            if ($currentStatus !== false) {
                // Toggle status
                $newStatus = ($currentStatus === 'aktif') ? 'nonaktif' : 'aktif';
                
                $updateStmt = $pdo->prepare("UPDATE customers SET status = ? WHERE id_customer = ?");
                $updateStmt->execute([$newStatus, $id]);
                
                if ($updateStmt->rowCount() > 0) {
                    $_SESSION['flash_success'] = "Status customer berhasil diubah menjadi '{$newStatus}'.";
                } else {
                    $_SESSION['flash_error'] = "Gagal mengubah status customer.";
                }
            } else {
                $_SESSION['flash_error'] = "Customer tidak ditemukan.";
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $_SESSION['flash_error'] = "Terjadi kesalahan sistem.";
        }
    } else {
        $_SESSION['flash_error'] = "ID customer tidak valid.";
    }
    
    $redirectUrl = 'customers.php';
    if (!empty($q)) {
        $redirectUrl .= '?q=' . urlencode($q);
    }
    header("Location: " . $redirectUrl);
    exit;
}

// === TAMPILKAN DATA (GET) ===
$q = trim($_GET['q'] ?? '');
$searchTerm = '%' . $q . '%';

$stmt = $pdo->prepare(
    "SELECT c.*, COUNT(b.id_booking) AS jml_booking
     FROM customers c 
     LEFT JOIN booking b ON b.id_customer = c.id_customer
     WHERE c.nama LIKE ? OR c.email LIKE ? OR c.no_telepon LIKE ?
     GROUP BY c.id_customer 
     ORDER BY c.tanggal_daftar DESC"
);
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Customer - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; --sidebar: #1e293b; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Styles */
        .sidebar { background: var(--sidebar); min-height: 100vh; color: white; position: fixed; width: 240px; z-index: 1000; }
        .sidebar-brand { padding: 20px; font-size: 1.2rem; font-weight: bold; border-bottom: 1px solid #334155; }
        .sidebar-menu { list-style: none; padding: 15px 0; margin: 0; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #cbd5e1; text-decoration: none; transition: 0.2s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #334155; color: white; border-left: 4px solid var(--primary); }
        .sidebar-menu li a i { margin-right: 10px; width: 20px; }
        
        /* Main Content */
        .main { margin-left: 240px; padding: 30px; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* Badges */
        .badge-aktif { background: #16a34a; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-nonaktif { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
        /* Filter Bar */
        .filter-bar { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        
        /* Tombol Aksi - PENTING: Pastikan tidak ada style disabled */
        .btn-toggle-status {
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer !important; /* Force cursor pointer */
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            opacity: 1 !important; /* Force full opacity */
        }
        
        .btn-toggle-status.btn-nonaktif {
            background-color: #ef4444;
            color: white;
        }
        .btn-toggle-status.btn-nonaktif:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }
        
        .btn-toggle-status.btn-aktif {
            background-color: #16a34a;
            color: white;
        }
        .btn-toggle-status.btn-aktif:hover {
            background-color: #15803d;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-user-shield"></i> Panel Admin</div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="customers.php" class="active"><i class="fas fa-users"></i> Data Customer</a></li>
        <li><a href="../logout.php" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1 class="fw-bold">Data Customer</h1>
    <p class="text-muted">Kelola akun customer</p>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="get" style="display:flex; gap:10px; flex-grow:1; align-items:center;">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Cari nama / email / telepon" style="max-width:400px;">
            <button type="submit" class="btn btn-success"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($q)): ?>
                <a href="customers.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Table Card -->
    <div class="content-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Terdaftar</th>
                        <th>Total Booking</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list) > 0): ?>
                    <?php foreach ($list as $c): 
                        $isAktif = ($c['status'] === 'aktif');
                        $btnClass = $isAktif ? 'btn-nonaktif' : 'btn-aktif';
                        $btnText = $isAktif ? 'Nonaktifkan' : 'Aktifkan';
                        $btnIcon = 'fa-power-off';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nama']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['no_telepon']) ?></td>
                            <td><?= date('d M Y', strtotime($c['tanggal_daftar'] ?? 'now')) ?></td>
                            <td><?= (int) $c['jml_booking'] ?></td>
                            <td>
                                <span class="<?= $isAktif ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <!-- FORM TOGGLE STATUS -->
                                <form method="post" style="display:inline;" onsubmit="return confirmToggle(this);">
                                    <input type="hidden" name="toggle_customer" value="1">
                                    <input type="hidden" name="id_customer" value="<?= (int) $c['id_customer'] ?>">
                                    <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                                    
                                    <button type="submit" class="btn-toggle-status <?= $btnClass ?>">
                                        <i class="fas <?= $btnIcon ?>"></i> <?= $btnText ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <?= !empty($q) ? 'Tidak ada customer yang cocok dengan pencarian.' : 'Tidak ada customer.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Konfirmasi -->
<script>
function confirmToggle(form) {
    const button = form.querySelector('button');
    const actionText = button.textContent.trim();
    return confirm(`Apakah Anda yakin ingin ${actionText.toLowerCase()} customer ini?`);
}
</script>

</body>
</html>
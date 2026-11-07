<?php
session_start();

$host = 'localhost';
$db = 'tixzone_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal. Silakan coba lagi nanti.");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PERBAIKAN: Tambahkan trim() untuk menghapus spasi tersembunyi di awal/akhir
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);

    if (empty($nama) || empty($email) || empty($no_telepon) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 4) {
        $error = "Password minimal 4 karakter!";
    } elseif (strlen($password) > 8) {
        $error = "Password MAKSIMAL 8 karakter!";
    } elseif ($password !== $password_confirm) {
        $error = "Password tidak cocok!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_customer FROM customers WHERE email = ? OR no_telepon = ?");
            $stmt->execute([$email, $no_telepon]);
            
            if ($stmt->fetch()) {
                $error = "Email atau No. Telepon sudah terdaftar!";
            } else {
                // Hash password untuk keamanan (sesuai checklist security)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO customers (nama, email, no_telepon, password, jenis_kelamin, status) 
                    VALUES (?, ?, ?, ?, 'L', 'aktif')
                ");
                $stmt->execute([$nama, $email, $no_telepon, $hashed_password]);
                
                $success = "Registrasi berhasil! Silakan login dengan email dan password kamu.";
                header("refresh:3;url=../login.php");
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; }
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px 0; }
        .card { width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .btn-daftar { background-color: var(--primary); color: white; border: none; padding: 12px; }
        .btn-daftar:hover { background-color: #146c3e; color: white; }
        .brand { color: var(--primary); font-weight: bold; font-size: 1.8rem; }
        .brand i { margin-right: 8px; }
        .info-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 15px; border-radius: 5px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="card p-4">
        <div class="text-center mb-4">
            <h2 class="brand"><i class="fas fa-mountain"></i> EmtE Highland Resort</h2>
            <p class="text-muted mb-0">Buat akun baru</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="info-box mb-3">
            <i class="fas fa-info-circle text-warning"></i> 
            <strong>Catatan:</strong> Password maksimal <b>8 karakter</b>. Gunakan email aktif untuk menerima konfirmasi booking.
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">No. Telepon</label>
                <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Password (max 8 karakter)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" maxlength="8" required>
                <small class="text-muted">Maksimal 8 karakter</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Konfirmasi Password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" maxlength="8" required>
            </div>
            <button type="submit" class="btn btn-daftar w-100">Daftar</button>
        </form>
        <div class="text-center mt-3 small">
            Sudah punya akun? <a href="../login.php" class="fw-bold" style="color: var(--primary);">Login di sini</a>
        </div>
    </div>
</body>
</html>
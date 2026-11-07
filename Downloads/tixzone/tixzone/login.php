<?php
require_once 'config.php';
session_start();

// Regenerate session ID untuk mencegah session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($pass)) {
        $error = "Email dan kata sandi wajib diisi!";
    } else {
        // CEK DI TABEL users (Admin & Petugas)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Cek password (support hash dan plain text untuk backward compatibility)
            $pass_valid = false;
            if (password_verify($pass, $user['password'])) {
                $pass_valid = true;
            } elseif ($pass === $user['password']) {
                // Upgrade ke hash otomatis
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?")->execute([$hash, $user['id_user']]);
                $pass_valid = true;
            }
            
            if ($pass_valid) {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = strtolower($user['jabatan']);
                $_SESSION['jabatan'] = $user['jabatan'];
                
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?")->execute([$user['id_user']]);
                
                if ($user['jabatan'] === 'Admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('petugas/dashboard.php');
                }
            }
        }
        
        // CEK DI TABEL customers
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? AND status = 'aktif'");
        $stmt->execute([$email]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cust) {
            $pass_valid = false;
            if (password_verify($pass, $cust['password'])) {
                $pass_valid = true;
            } elseif ($pass === $cust['password']) {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE customers SET password = ? WHERE id_customer = ?")->execute([$hash, $cust['id_customer']]);
                $pass_valid = true;
            }
            
            if ($pass_valid) {
                $_SESSION['user_id'] = $cust['id_customer']; 
                $_SESSION['nama'] = $cust['nama']; 
                $_SESSION['role'] = 'customer';
                redirect('index.php'); 
            } else {
                $error = "Kata sandi salah!";
            }
        } else {
            $error = "Email tidak terdaftar!"; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: 100%; max-width: 450px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 40px; background: white; }
        .brand { color: #1a7a4a; font-weight: 800; font-size: 1.5rem; text-align: center; }
        .btn-masuk { background-color: #1a7a4a; border: none; color: white; padding: 12px; font-weight: 600; }
        .btn-masuk:hover { background-color: #145f38; color: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="brand mb-4"><i class="fas fa-mountain"></i> EmtE Highland Resort</h2>
        <h4 class="text-center fw-bold mb-4">Login</h4>
        
        <?php if ($error): ?><div class="alert alert-danger py-2 small text-center"><?= sanitize($error) ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Kata Sandi</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-masuk w-100 mb-3">Masuk</button>
        </form>
        <div class="text-center small text-muted">Belum punya akun? <a href="customer/register.php" class="fw-bold" style="color: #1a7a4a;">Daftar di sini</a></div>
    </div>
</body>
</html>
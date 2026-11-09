<?php session_start(); 
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>EmtE Highland Resort Ciwidey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1a7a4a; } body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: var(--primary) !important; }
        .hero { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1920&q=80'); background-size: cover; color: white; padding: 120px 0; text-align: center; }
        .hero h1 { font-size: 3.5rem; font-weight: bold; }
        .search-box { background: white; padding: 25px; border-radius: 10px; margin-top: -50px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-primary-custom { background-color: var(--primary); color: white; border: none; }
        .ticket-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .ticket-card .harga { color: var(--primary); font-weight: bold; font-size: 1.3rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-mountain"></i> EmtE Highland Resort</a>
        <div class="collapse navbar-collapse"><ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($is_admin): ?>
                    <li class="nav-item ms-3"><a class="btn btn-warning btn-sm text-dark" href="admin/dashboard.php"><i class="fas fa-user-shield"></i> Panel Admin</a></li>
                <?php else: ?>
                    <li class="nav-item ms-3"><a class="btn btn-outline-success btn-sm" href="customer/dashboard.php"><i class="fas fa-user"></i> Dashboard Saya</a></li>
                <?php endif; ?>
                <li class="nav-item ms-2"><a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a></li>
            <?php else: ?>
                <li class="nav-item ms-3"><a class="btn btn-primary-custom btn-sm text-white" href="login.php">Login</a></li>
            <?php endif; ?>
        </ul></div>
    </div>
</nav>

<div class="hero"><div class="container">
    <h1>EmtE Highland Resort Ciwidey</h1>
    <p class="lead">Nikmati keindahan alam pegunungan dan berbagai wahana seru.</p>
    <?php if (!$is_admin): ?>
        <a href="customer/booking.php" class="btn btn-primary-custom btn-lg">Booking Sekarang <i class="fas fa-arrow-right"></i></a>
    <?php else: ?>
        <div class="alert alert-warning d-inline-block mt-3">Anda login sebagai Admin. Silakan masuk ke Panel Admin untuk mengelola sistem.</div>
    <?php endif; ?>
</div></div>

<div class="container my-5" id="tiket">
    <h2 class="text-center fw-bold mb-4">Destinasi & Tiket</h2>
    <div class="row g-4">
    <?php
    $pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
    $stmt = $pdo->query("SELECT * FROM destinasi WHERE status = 'aktif'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
    ?>
        <div class="col-md-3">
            <div class="card ticket-card h-100 p-3">
                <h5 class="fw-bold"><?= htmlspecialchars($row['nama_destinasi']) ?></h5>
                <p class="harga">Rp <?= number_format($row['harga_tiket'], 0, ',', '.') ?> <small class="text-muted fs-6">/ orang</small></p>
                <p class="text-muted small"><?= htmlspecialchars($row['deskripsi']) ?></p>
                <?php if (!$is_admin): ?>
                    <a href="customer/booking.php" class="btn btn-primary-custom w-100 mt-auto">Pilih Tiket</a>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 mt-auto" disabled>Hanya Customer</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
</div>
</body>
</html>
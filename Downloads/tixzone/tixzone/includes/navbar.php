<?php
// File: includes/header.php
// Navbar Terintegrasi config.php & Session Native

require_once __DIR__ . '/../config.php';
session_start();

// Ambil data user dari session jika sudah login
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';
$user_nama = htmlspecialchars($_SESSION['nama'] ?? '');

// Tentukan halaman aktif untuk highlight menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="site-header">
  <div class="container d-flex justify-content-between align-items-center py-3">
    <!-- Brand Logo -->
    <a href="../index.php" class="brand text-decoration-none fw-bold fs-4" style="color: #1a7a4a;">
      <i class="fas fa-mountain me-2"></i>EmtE Highland Resort
      <small class="d-block text-muted fw-normal" style="font-size: 0.7rem; line-height: 1;">Ciwidey</small>
    </a>

    <!-- Navigation Links -->
    <nav class="nav-links d-flex align-items-center gap-3">
      <a href="../index.php" class="text-decoration-none <?= $current_page === 'index' ? 'fw-bold text-success' : 'text-dark' ?>">Home</a>
      
      <?php if ($is_logged_in && $user_role === 'customer'): ?>
        <a href="booking.php" class="text-decoration-none <?= $current_page === 'booking' ? 'fw-bold text-success' : 'text-dark' ?>">Booking Tiket</a>
        <a href="dashboard.php" class="text-decoration-none <?= $current_page === 'dashboard' ? 'fw-bold text-success' : 'text-dark' ?>">Booking Saya</a>
      <?php endif; ?>

      <a href="../index.php#tentang" class="text-decoration-none text-dark">Tentang</a>
      <a href="../index.php#kontak" class="text-decoration-none text-dark">Kontak</a>

      <!-- Auth Buttons -->
      <?php if ($is_logged_in): ?>
        <?php if ($user_role === 'customer'): ?>
          <span class="text-muted small d-none d-md-inline ms-2">Halo, <?= $user_nama ?>!</span>
          <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
        <?php else: ?>
          <!-- Untuk Admin & Petugas -->
          <a href="<?= $user_role ?>/dashboard.php" class="btn btn-success btn-sm rounded-pill px-3">
            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
          </a>
        <?php endif; ?>
      <?php else: ?>
        <a href="../login.php" class="btn btn-primary btn-sm rounded-pill px-4" style="background-color: #1a7a4a; border-color: #1a7a4a;">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- Tambahkan CSS Inline agar Navbar Rapi Tanpa File Eksternal -->
<style>
  .site-header {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
  }
  .nav-links a {
    font-size: 0.95rem;
    transition: color 0.2s;
  }
  .nav-links a:hover {
    color: #1a7a4a !important;
  }
  @media (max-width: 768px) {
    .nav-links {
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px !important;
    }
    .brand small { display: none; }
  }
</style>
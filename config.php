<?php
// === KONFIGURASI DATABASE ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'tixzone_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// === KONFIGURASI APLIKASI ===
define('APP_NAME', 'EmtE Highland Resort');
define('APP_URL', 'http://localhost:8000');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 2048 * 1024); // 2MB

// === SESSION SECURITY ===
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// === KONEKSI DATABASE ===
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Jangan tampilkan detail error ke user (security!)
    error_log("Database Error: " . $e->getMessage());
    die("Terjadi kesalahan sistem. Silakan coba lagi nanti.");
}

// === FUNGSI BANTUAN ===
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        redirect(APP_URL . '/login.php');
    }
    if ($required_role && $_SESSION['role'] !== $required_role) {
        redirect(APP_URL . '/login.php');
    }
}
?>
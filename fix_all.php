<?php
session_start();
$host = 'localhost'; $db = 'tixzone_db'; $user = 'root'; $pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Perbaiki tabel roles (ini yang sering bikin login gagal)
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (id_role INT PRIMARY KEY, nama_role VARCHAR(50))");
    $pdo->exec("INSERT IGNORE INTO roles VALUES (1, 'admin'), (2, 'petugas')");
    echo "✅ 1. Tabel roles sudah beres.<br>";

    // 2. Reset password admin menjadi 'admin123' yang pasti benar
    $hash_baru = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, id_role = 1 WHERE username = 'admin'")->execute([$hash_baru]);
    echo "✅ 2. Password admin direset menjadi: admin123<br>";

    // 3. Login Otomatis (Bypass)
    $_SESSION['user_id'] = 1;
    $_SESSION['nama'] = 'Admin Utama';
    $_SESSION['role'] = 'admin';
    
    echo "<h2 style='color:green; margin-top:20px;'>✅ 3. Login Berhasil! Kamu akan diarahkan ke Dashboard...</h2>";
    echo "<p>Jika tidak otomatis pindah, <a href='admin/dashboard.php' style='font-size:20px; font-weight:bold;'>KLIK DI SINI</a></p>";
    
    // Redirect otomatis
    header("refresh:2;url=admin/dashboard.php");

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
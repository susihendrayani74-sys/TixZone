<?php
// Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TEST KONEKSI DATABASE</h1>";
echo "<pre>";

// Cek apakah XAMPP MySQL jalan
echo "1. Mencoba koneksi ke database...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
    echo "✅ Koneksi berhasil!\n\n";
    
    // Cek tabel users
    echo "2. Mengecek tabel users...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabel users ADA!\n\n";
        
        // Cek isi tabel users
        echo "3. Melihat isi tabel users:\n";
        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "✅ Ada " . count($users) . " user di database:\n";
            //print_r($users);
        } else {
            echo "⚠️ Tabel users KOSONG! Kita buat akun admin baru...\n\n";
            
            // Buat akun admin
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, nama, jabatan, id_role, status) VALUES ('admin', ?, 'Admin Utama', 'Administrator', 1, 'aktif')")->execute([$hash]);
            echo "✅ Akun admin berhasil dibuat!\n";
            echo "Username: admin\n";
            echo "Password: admin123\n\n";
        }
    } else {
        echo "❌ Tabel users TIDAK ADA! Buat dulu tabelnya.\n";
        echo "Buka phpMyAdmin dan jalankan SQL ini:\n";
        echo "CREATE TABLE users (id_user INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), password VARCHAR(255), nama VARCHAR(100), jabatan VARCHAR(50), id_role INT, status VARCHAR(20));\n";
    }
    
    echo "\n\n<a href='login.php' style='font-size: 20px; color: blue; font-weight: bold;'> KLIK DI SINI UNTUK LOGIN</a>";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "Kemungkinan penyebab:\n";
    echo "1. XAMPP MySQL belum START\n";
    echo "2. Database 'tixzone_db' belum dibuat\n";
    echo "3. Username/password database salah\n";
}

echo "</pre>";
?>
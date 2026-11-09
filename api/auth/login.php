<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config.php';

// 1. Coba ambil dari JSON body (Postman)
$input_raw = file_get_contents("php://input");
$data = json_decode($input_raw, true);

// 2. Kalau kosong, coba dari Form data
if (empty($data)) $data = $_POST;

// 3. Kalau masih kosong, coba dari URL parameter (?email=...&password=...)
if (empty($data)) $data = $_GET;

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

// Kalau masih kosong, tampilkan cara pakai
if (empty($email) || empty($password)) {
    echo json_encode([
        "status" => "error", 
        "message" => "Email dan password wajib diisi!",
        "cara_test" => "Buka browser: http://localhost:8000/api/auth/login.php?email=test@user.com&password=123456"
    ]);
    exit;
}

// 4. Cek Customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? AND password = ? AND status = 'aktif'");
$stmt->execute([$email, $password]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($customer) {
    echo json_encode([
        "status" => "success",
        "message" => "Login Customer Berhasil",
        "data" => [
            "user_id" => $customer['id_customer'],
            "nama" => $customer['nama'],
            "email" => $customer['email'],
            "role" => "customer",
            "token" => base64_encode($customer['id_customer'] . ':' . $customer['email'])
        ]
    ]);
    exit;
}

// 5. Cek Admin/Petugas
$stmt = $pdo->prepare("SELECT u.*, p.jabatan FROM users u LEFT JOIN pegawai p ON u.id_user = p.id_user WHERE u.username = ? AND u.password = ? AND u.status = 'aktif'");
$stmt->execute([$email, $password]);
$pegawai = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pegawai) {
    echo json_encode([
        "status" => "success",
        "message" => "Login Pegawai Berhasil",
        "data" => [
            "user_id" => $pegawai['id_user'],
            "nama" => $pegawai['nama'],
            "email" => $pegawai['username'],
            "role" => strtolower($pegawai['jabatan']),
            "token" => base64_encode($pegawai['id_user'] . ':' . $pegawai['username'])
        ]
    ]);
    exit;
}

echo json_encode(["status" => "error", "message" => "Email atau password salah!"]);
?>
<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Izinkan akses dari aplikasi mobile
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$host = "localhost";
$dbname = "tixzone_db";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $e->getMessage()]);
    exit();
}
?>
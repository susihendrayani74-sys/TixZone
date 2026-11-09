<?php
require_once 'config.php';

// Ambil semua destinasi aktif
$stmt = $pdo->query("SELECT id_destinasi, nama_destinasi, deskripsi, harga_tiket, gambar FROM destinasi WHERE status = 'aktif'");
$destinasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($destinasi) {
    echo json_encode([
        "status" => "success",
        "data" => $destinasi
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Tidak ada destinasi tersedia"]);
}
?>
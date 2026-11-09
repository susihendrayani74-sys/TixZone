<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? 0;
$destinasi_id = $data['destinasi_id'] ?? 0;
$nama_pemesan = $data['nama_pemesan'] ?? '';
$tanggal = $data['tanggal'] ?? '';
$jumlah = $data['jumlah'] ?? 1;

if ($user_id == 0 || $destinasi_id == 0 || empty($nama_pemesan) || empty($tanggal)) {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap!"]);
    exit;
}

// Cek harga tiket
$stmt = $pdo->prepare("SELECT harga_tiket FROM destinasi WHERE id_destinasi = ?");
$stmt->execute([$destinasi_id]);
$dest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    echo json_encode(["status" => "error", "message" => "Destinasi tidak ditemukan!"]);
    exit;
}

$total = $dest['harga_tiket'] * $jumlah;
$kode = 'API' . date('Ymd') . rand(1000, 9999);

try {
    $pdo->beginTransaction();

    // 1. Insert Booking
    $stmt = $pdo->prepare("INSERT INTO booking (id_customer, kode_booking, nama_pemesan, tanggal_kunjungan, total_harga, status_pembayaran, tanggal_booking) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $kode, $nama_pemesan, $tanggal, $total]);
    $id_booking = $pdo->lastInsertId();

    // 2. Insert Detail Booking
    $stmt = $pdo->prepare("INSERT INTO detail_booking (id_booking, id_destinasi, jumlah) VALUES (?, ?, ?)");
    $stmt->execute([$id_booking, $destinasi_id, $jumlah]);

    // 3. Insert E-Ticket
    $qr_code = 'QR-' . $kode;
    $stmt = $pdo->prepare("INSERT INTO e_ticket (id_booking, id_pegawai, qr_code, status_checkin, tanggal_checkin) VALUES (?, NULL, ?, 'belum', NULL)");
    $stmt->execute([$id_booking, $qr_code]);

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Booking berhasil dibuat!",
        "data" => [
            "kode_booking" => $kode,
            "total_harga" => $total,
            "nama_pemesan" => $nama_pemesan
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Gagal membuat booking: " . $e->getMessage()]);
}
?>
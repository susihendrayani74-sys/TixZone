<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
$pdo = new PDO("mysql:host=localhost;dbname=tixzone_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT b.*, e.qr_code, e.status_checkin, d.nama_destinasi 
    FROM booking b 
    JOIN e_ticket e ON b.id_booking = e.id_booking 
    LEFT JOIN detail_booking db ON b.id_booking = db.id_booking 
    LEFT JOIN destinasi d ON db.id_destinasi = d.id_destinasi 
    WHERE b.id_booking = ?");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) die("<h3 class='text-center mt-5'>Data E-Ticket tidak ditemukan!</h3>");

// Generate QR Code URL menggunakan Google Charts API
// QR Code berisi: Kode Booking + Nama Pemesan + Destinasi + Tanggal
$qr_data = "EMTE|{$b['kode_booking']}|{$b['nama_pemesan']}|{$b['nama_destinasi']}|{$b['tanggal_kunjungan']}";
$qr_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . urlencode($qr_data) . "&choe=UTF-8";

// Status badge
$status = $b['status_pembayaran'] ?? 'pending';
$status_badge = 'bg-warning text-dark';
if ($status === 'terverifikasi') $status_badge = 'bg-success';
if ($status === 'ditolak') $status_badge = 'bg-danger';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - EmtE Highland Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 10px; }
        .container { max-width: 100%; padding: 0; }
        .ticket-card { 
            max-width: 600px; margin: 10px auto; background: white; 
            border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden;
        }
        .ticket-header { 
            background: #1a7a4a; color: white; padding: 20px; text-align: center; 
        }
        .ticket-header h4 { font-size: 1.1rem; margin: 0; font-weight: 700; }
        .ticket-header h4 i { margin-right: 8px; }
        .ticket-header h2 { font-size: 1.5rem; margin: 5px 0 0; font-weight: 800; letter-spacing: 2px; }
        .ticket-body { padding: 20px; }
        .info-row { margin-bottom: 15px; }
        .info-label { font-size: 0.75rem; color: #6c757d; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 1rem; font-weight: 700; margin: 0; color: #1e293b; }
        .info-value.kode { color: #1a7a4a; font-size: 1.1rem; }
        .info-value.total { font-size: 1.2rem; color: #1a7a4a; }
        .qr-section { 
            text-align: center; 
            padding: 20px; 
            border-top: 2px dashed #dee2e6; 
            margin-top: 15px;
        }
        .qr-image { 
            width: 200px; 
            height: 200px; 
            border: 2px solid #1a7a4a; 
            border-radius: 12px; 
            padding: 10px; 
            background: white; 
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .qr-text { font-size: 0.8rem; color: #6c757d; margin-top: 10px; }
        .qr-code-text { 
            font-size: 0.7rem; 
            color: #999; 
            margin-top: 5px;
            word-break: break-all;
            max-width: 250px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-download { 
            background: #1a7a4a; color: white; border: none; 
            padding: 12px 25px; font-size: 1rem; font-weight: 700; 
            border-radius: 8px; width: 100%; margin-top: 15px;
            transition: 0.3s;
        }
        .btn-download:hover { background: #145f38; color: white; }
        .btn-back { 
            background: #6c757d; color: white; border: none; 
            padding: 10px 20px; font-size: 0.9rem; border-radius: 8px; 
            text-decoration: none; display: inline-block; margin-bottom: 15px;
        }
        .btn-back:hover { background: #5a6268; color: white; }
        
        @media (max-width: 768px) {
            body { padding: 8px; }
            .ticket-header { padding: 15px; }
            .ticket-header h4 { font-size: 1rem; }
            .ticket-header h2 { font-size: 1.2rem; }
            .ticket-body { padding: 15px; }
            .qr-image { width: 180px; height: 180px; }
        }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .ticket-card { box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center no-print">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    
    <div class="ticket-card">
        <div class="ticket-header">
            <h4><i class="fas fa-mountain"></i> EmtE Highland Resort</h4>
            <h2>E-TICKET</h2>
        </div>
        <div class="ticket-body">
            <div class="row">
                <div class="col-7">
                    <div class="info-row">
                        <div class="info-label">Kode Booking</div>
                        <div class="info-value kode"><?= $b['kode_booking'] ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama Pemesan</div>
                        <div class="info-value"><?= htmlspecialchars($b['nama_pemesan']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Destinasi</div>
                        <div class="info-value"><?= htmlspecialchars($b['nama_destinasi']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Kunjungan</div>
                        <div class="info-value"><?= date('d F Y', strtotime($b['tanggal_kunjungan'])) ?></div>
                    </div>
                </div>
                <div class="col-5 text-end">
                    <div class="info-row">
                        <div class="info-label">Total Pembayaran</div>
                        <div class="info-value total">Rp <?= number_format($b['total_harga'],0,',','.') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge <?= $status_badge ?>"><?= ucfirst($status) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- QR CODE SECTION -->
            <div class="qr-section">
                <img src="<?= $qr_url ?>" alt="QR Code E-Ticket" class="qr-image" id="qrImage">
                <p class="qr-text"><i class="fas fa-qrcode"></i> Tunjukkan QR Code ini saat kunjungan</p>
                <p class="qr-code-text">
                    <small>Data QR: <?= htmlspecialchars($qr_data) ?></small>
                </p>
            </div>
            <!-- END QR CODE SECTION -->
        </div>
    </div>
    
    <div class="text-center no-print">
        <button onclick="window.print()" class="btn-download">
            <i class="fas fa-download"></i> Download PDF
        </button>
    </div>
</div>

<script>
// Pastikan QR Code ter-load dengan benar
document.getElementById('qrImage').onerror = function() {
    // Jika Google Charts API gagal, tampilkan pesan
    this.src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent('<?= $qr_data ?>');
    this.onerror = null;
};
</script>
</body>
</html>
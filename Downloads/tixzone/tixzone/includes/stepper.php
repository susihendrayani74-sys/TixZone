<?php
// File: includes/stepper.php
// Stepper Booking Terintegrasi & Responsive

// Tentukan step aktif dari GET parameter atau fallback ke 1
$step_active = max(1, min(4, (int)($_GET['step'] ?? $_SESSION['booking_step'] ?? 1)));

$steps = [
    1 => ['label' => 'Pilih Tiket', 'icon' => 'fa-ticket-alt'],
    2 => ['label' => 'Data Pemesan', 'icon' => 'fa-user-edit'],
    3 => ['label' => 'Pembayaran', 'icon' => 'fa-credit-card'],
    4 => ['label' => 'E-Ticket', 'icon' => 'fa-qrcode']
];
?>

<div class="booking-stepper">
    <?php foreach ($steps as $num => $data): 
        $status = '';
        if ($num < $step_active) $status = 'completed';
        elseif ($num == $step_active) $status = 'active';
    ?>
        <div class="step-item <?= $status ?>">
            <div class="step-circle">
                <?php if ($num < $step_active): ?>
                    <i class="fas fa-check"></i>
                <?php else: ?>
                    <span><?= $num ?></span>
                <?php endif; ?>
            </div>
            <div class="step-label"><?= htmlspecialchars($data['label']) ?></div>
        </div>
        
        <!-- Garis Penghubung (kecuali step terakhir) -->
        <?php if ($num < 4): ?>
            <div class="step-line <?= ($num < $step_active) ? 'completed' : '' ?>"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- CSS Inline untuk Stepper Modern -->
<style>
    .booking-stepper {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 30px auto 40px;
        max-width: 800px;
        padding: 0 15px;
    }
    
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }
    
    .step-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border: 3px solid transparent;
    }
    
    .step-label {
        margin-top: 10px;
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
        transition: color 0.3s ease;
    }
    
    /* State: Active */
    .step-item.active .step-circle {
        background-color: #1a7a4a;
        color: white;
        box-shadow: 0 0 0 4px rgba(26, 122, 74, 0.2);
        transform: scale(1.1);
    }
    .step-item.active .step-label {
        color: #1a7a4a;
        font-weight: 700;
    }
    
    /* State: Completed */
    .step-item.completed .step-circle {
        background-color: #1a7a4a;
        color: white;
    }
    .step-item.completed .step-label {
        color: #1a7a4a;
        font-weight: 600;
    }
    
    /* Garis Penghubung */
    .step-line {
        flex-grow: 1;
        height: 3px;
        background-color: #dee2e6;
        margin: 0 10px;
        margin-bottom: 25px; /* Agar sejajar dengan tengah circle */
        transition: background-color 0.3s ease;
    }
    .step-line.completed {
        background-color: #1a7a4a;
    }
    
    /* Responsive Mobile */
    @media (max-width: 768px) {
        .booking-stepper {
            flex-wrap: wrap;
            gap: 15px;
        }
        .step-line {
            display: none; /* Sembunyikan garis di mobile */
        }
        .step-item {
            flex: 1 1 40%; /* 2 kolom di mobile */
            flex-direction: row;
            justify-content: flex-start;
            gap: 12px;
        }
        .step-circle {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }
        .step-label {
            margin-top: 0;
            text-align: left;
            font-size: 0.8rem;
        }
    }
</style>
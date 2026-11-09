<?php
// includes/layout.php
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

function page_head($title = 'TixZone') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?></title>
        <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --primary-color: #1a7a4a; }
            body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
            .btn-emte { background-color: var(--primary-color); color: white; border: none; }
            .btn-emte:hover { background-color: #146c3e; color: white; }
            .text-emte { color: var(--primary-color); }
            /* Stepper Styles */
            .stepper-container { display: flex; justify-content: center; align-items: center; margin: 30px 0 40px; }
            .step-item { display: flex; align-items: center; }
            .step-circle { width: 40px; height: 40px; border-radius: 50%; background-color: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; margin-right: 10px; }
            .step-item.active .step-circle, .step-item.completed .step-circle { background-color: var(--primary-color); color: white; }
            .step-label { font-size: 1rem; color: #6c757d; font-weight: 500; }
            .step-item.active .step-label, .step-item.completed .step-label { color: var(--primary-color); font-weight: 700; }
            .step-line { width: 80px; height: 3px; background-color: #dee2e6; margin: 0 15px; }
            .step-line.completed { background-color: var(--primary-color); }
        </style>
    </head>
    <body>
    <?php
}

function page_footer() {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
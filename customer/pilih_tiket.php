<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// CEK LOGIN - Wajib login sebelum bisa pilih tiket
cek_login('customer');

// ... kode selanjutnya ...
?>
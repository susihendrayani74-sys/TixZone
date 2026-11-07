<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function cek_login($role_dibutuhkan = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    if ($role_dibutuhkan && $_SESSION['role'] !== $role_dibutuhkan) {
        header("Location: ../login.php");
        exit;
    }
}
?>
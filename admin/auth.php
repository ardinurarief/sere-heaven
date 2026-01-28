<?php
session_start();

// Set session timeout (30 menit)
$timeout = 1800; // 30 menit dalam detik

// Cek jika session timeout sudah lewat
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Session timeout, logout user
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Cek apakah user sudah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
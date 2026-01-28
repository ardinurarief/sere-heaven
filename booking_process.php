<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$room_id  = (int) $_POST['room_id'];
$check_in = $_POST['check_in'];
$check_out= $_POST['check_out'];
$payment  = $_POST['payment_method'];

// ambil harga kamar
$q = mysqli_query($conn, "SELECT price_per_night FROM rooms WHERE id=$room_id");
$r = mysqli_fetch_assoc($q);

// hitung jumlah malam (minimal 1)
$days = (strtotime($check_out) - strtotime($check_in)) / 86400;
$days = ($days < 1) ? 1 : $days;

$total = $days * $r['price_per_night'];

// kode booking
$code = "SH-" . rand(10000,99999);

// simpan reservasi
mysqli_query($conn, "
INSERT INTO reservations
(booking_code, user_id, room_id, check_in, check_out, total_price, payment_method)
VALUES
('$code','$user_id','$room_id','$check_in','$check_out','$total','$payment')
");

// balik ke my booking
header("Location: user/my_booking.php");
exit;

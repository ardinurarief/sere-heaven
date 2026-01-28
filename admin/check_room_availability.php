<?php
require 'auth.php';
require '../config/db.php';

header('Content-Type: application/json');

// Get POST data
$room_number = mysqli_real_escape_string($conn, $_POST['room_number'] ?? '');
$room_type = mysqli_real_escape_string($conn, $_POST['room_type'] ?? '');
$is_edit = $_POST['is_edit'] ?? 'false';
$current_id = intval($_POST['current_id'] ?? 0);

// Validate input
if (empty($room_number) || empty($room_type)) {
    echo json_encode([
        'available' => false,
        'message' => '⚠️ Nomor kamar dan tipe kamar harus diisi'
    ]);
    exit;
}

// Validate room number format
if (!preg_match('/^[A-Z0-9\-]+$/i', $room_number)) {
    echo json_encode([
        'available' => false,
        'message' => '❌ Format nomor kamar tidak valid. Gunakan huruf, angka, atau tanda hubung (-)'
    ]);
    exit;
}

// Check if room number already exists
$query = "SELECT * FROM rooms WHERE room_number = '$room_number'";

// If editing, exclude current room
if ($is_edit === 'true' && $current_id > 0) {
    $query .= " AND id != $current_id";
}

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $room = mysqli_fetch_assoc($result);
    
    $message = "❌ Nomor kamar <strong>$room_number</strong> sudah digunakan!<br>";
    $message .= "Kamar " . $room['room_type'] . " - Status: ";
    $message .= $room['status'] == 'available' ? '✅ Tersedia' : '⏸️ Tidak Aktif';
    $message .= " - Harga: Rp " . number_format($room['price_per_night']) . "/malam";
    
    echo json_encode([
        'available' => false,
        'message' => $message,
        'duplicate_room' => $room
    ]);
} else {
    echo json_encode([
        'available' => true,
        'message' => '✅ Nomor kamar tersedia untuk tipe ' . $room_type
    ]);
}

mysqli_close($conn);
?>
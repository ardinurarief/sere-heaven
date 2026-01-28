<?php
session_start();
require_once '../config/db.php'; // Pastikan ini mengembalikan $conn (mysqli)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['status'])) {
    try {
        $allowed_status = ['pending', 'confirmed', 'check_in', 'check_out', 'cancelled'];
        $status = $_POST['status'];
        $reservation_id = (int)$_POST['reservation_id'];
        $admin_note = isset($_POST['admin_note']) ? mysqli_real_escape_string($conn, $_POST['admin_note']) : '';
        
        if (!in_array($status, $allowed_status)) {
            throw new Exception("Status tidak valid");
        }
        
        // Update status (dan admin_note jika ada)
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $reservation_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Status reservasi berhasil diupdate!";
        } else {
            $_SESSION['warning'] = "Tidak ada perubahan data.";
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    // ⬇️⬇️⬇️ REDIRECT KEMBALI KE DETAIL PAGE ⬇️⬇️⬇️
    header("Location: reservation_detail.php?id=" . $reservation_id);
    exit();
}
?>
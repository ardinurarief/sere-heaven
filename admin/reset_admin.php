<?php
// reset_admin.php - Hapus setelah digunakan!
require '../config/db.php';

echo "<h3>Reset Admin Password</h3>";

// Password baru
$new_password = 'admin123';

// Hash password baru
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password admin
$sql = "UPDATE admins SET password = '$hashed_password' WHERE username = 'admin'";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✅ Password berhasil direset!</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password baru:</strong> $new_password</p>";
    echo "<p><strong>Hash baru:</strong> $hashed_password</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='login.php'>Kembali ke Login</a></p>";
?>
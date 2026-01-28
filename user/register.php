<?php
session_start();
require '../config/db.php';

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek email sudah terdaftar
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email sudah terdaftar!";
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user dengan prepared statement (TANPA phone)
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Pendaftaran berhasil! Silakan login.";
                // Tunggu 2 detik kemudian redirect ke login
                header("refresh:2;url=login.php");
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sere Heaven</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            
            // Reset setiap kali function dipanggil
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#ff6b6b';
            
            if (password.length === 0) {
                checkPasswordRequirements(password);
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Set width dan warna berdasarkan strength
            if (strength <= 2) {
                strengthBar.style.width = '33%';
            } else if (strength <= 4) {
                strengthBar.style.width = '66%';
                strengthBar.style.backgroundColor = '#ffd93d';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#51cf66';
            }
            
            // Panggil checklist requirements
            checkPasswordRequirements(password);
        }
        
        function checkPasswordRequirements(password) {
            // Check panjang password
            const reqLength = document.getElementById('req-length');
            if (password.length >= 6) {
                reqLength.classList.add('fulfilled');
                reqLength.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqLength.classList.remove('fulfilled');
                reqLength.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Check huruf kapital
            const reqUppercase = document.getElementById('req-uppercase');
            if (/[A-Z]/.test(password)) {
                reqUppercase.classList.add('fulfilled');
                reqUppercase.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqUppercase.classList.remove('fulfilled');
                reqUppercase.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Check angka
            const reqNumber = document.getElementById('req-number');
            if (/[0-9]/.test(password)) {
                reqNumber.classList.add('fulfilled');
                reqNumber.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqNumber.classList.remove('fulfilled');
                reqNumber.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Check simbol
            const reqSymbol = document.getElementById('req-symbol');
            if (/[^A-Za-z0-9]/.test(password)) {
                reqSymbol.classList.add('fulfilled');
                reqSymbol.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqSymbol.classList.remove('fulfilled');
                reqSymbol.querySelector('.requirement-icon').textContent = '❌';
            }
        }
        
        function validatePassword() {
            const password = document.getElementsByName('password')[0].value;
            const confirm = document.getElementsByName('confirm_password')[0].value;
            const errorElement = document.getElementById('password-error');
            
            if (password !== confirm) {
                errorElement.textContent = 'Password tidak cocok!';
                return false;
            } else if (password.length < 6) {
                errorElement.textContent = 'Password minimal 6 karakter!';
                return false;
            } else {
                errorElement.textContent = '';
                return true;
            }
        }
        
        function validateForm() {
            return validatePassword();
        }
    </script>
</head>
<body class="auth-page">
    <div class="auth-logo">
        <a href="../index.php">
            <img src="../assets/img/seree.png" alt="Sere Heaven Logo">
        </a>
    </div>
    <div class="auth-container">
        <div class="auth-header">
            <h2 class="elegant-title">Daftar Akun</h2>
            <p>Bergabung dengan Sere Heaven</p>
        </div>

        <?php if($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" onsubmit="return validateForm()">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-input" placeholder="Masukkan nama lengkap" 
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="email@example.com" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Minimal 6 karakter" 
                       onkeyup="checkPasswordStrength(this.value)" required>
                <div class="password-strength">
                    <div id="password-strength-bar" class="password-strength-bar"></div>
                </div>
                
                <div class="password-requirements">
                    <div class="requirement" id="req-length">
                        <span class="requirement-icon">❌</span>
                        <span class="requirement-text">Minimal 6 karakter</span>
                    </div>
                    <div class="requirement" id="req-uppercase">
                        <span class="requirement-icon">❌</span>
                        <span class="requirement-text">Huruf kapital (A-Z)</span>
                    </div>
                    <div class="requirement" id="req-number">
                        <span class="requirement-icon">❌</span>
                        <span class="requirement-text">Angka (0-9)</span>
                    </div>
                    <div class="requirement" id="req-symbol">
                        <span class="requirement-icon">❌</span>
                        <span class="requirement-text">Simbol (!@#$%^&*)</span>
                    </div>
                </div>
                
                <div id="password-error" style="color: #ff6b6b; font-size: 0.85rem; margin-top: 0.5rem;"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-input" placeholder="Ulangi password" 
                       onkeyup="validatePassword()" required>
            </div>

            <button type="submit" name="register" class="submit-btn">
                <span>Daftar Sekarang</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </form>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="login.php" class="auth-link">Login disini</a></p>
            <p><a href="../index.php" class="auth-link">← Kembali ke Beranda</a></p>
        </div>
    </div>
</body>
</html>
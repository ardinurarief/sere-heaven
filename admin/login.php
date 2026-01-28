<?php
session_start();
require '../config/db.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admins WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $admin['password'])) {
                // Regenerate session ID untuk security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['login_time'] = time();
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Username not found';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* === PREMIUM LOGIN DESIGN === */
    :root {
        --primary-gold: #ffd700;
        --primary-dark: #0d1117;
        --secondary-dark: #161b22;
        --accent-blue: #58a6ff;
        --success: #28a745;
        --danger: #ff6b6b;
        --light-text: #f0f6fc;
        --gray-text: #8b949e;
        --card-bg: rgba(22, 27, 34, 0.9);
        --hover-gold: #ffed4e;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--primary-dark);
    color: var(--light-text);
    min-height: 100vh; /* Ubah dari height: 100vh */
    display: flex;
    align-items: center; /* Hapus atau ubah ke flex-start untuk scroll */
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
    overflow-y: auto; /* Tambahkan ini */
}
    
    /* Animated Background */
    .bg-pattern {
        position: absolute;
        width: 100%;
        height: 100%;
        background: 
            radial-gradient(circle at 15% 25%, rgba(255, 215, 0, 0.08) 0%, transparent 25%),
            radial-gradient(circle at 85% 75%, rgba(88, 166, 255, 0.08) 0%, transparent 25%),
            linear-gradient(135deg, rgba(13, 17, 23, 0.9), rgba(22, 27, 34, 0.9));
        z-index: -2;
    }
    
    .floating-shapes {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: -1;
    }
    
    .shape {
        position: absolute;
        background: var(--primary-gold);
        opacity: 0.03;
        border-radius: 50%;
        animation: float 15s infinite ease-in-out;
    }
    
    .shape:nth-child(1) { width: 120px; height: 120px; top: 10%; left: 5%; animation-delay: 0s; }
    .shape:nth-child(2) { width: 80px; height: 80px; top: 70%; left: 10%; animation-delay: 3s; }
    .shape:nth-child(3) { width: 60px; height: 60px; top: 20%; right: 15%; animation-delay: 6s; }
    .shape:nth-child(4) { width: 100px; height: 100px; bottom: 20%; right: 8%; animation-delay: 9s; }
    
    @keyframes float {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(180deg); }
    }
    
    /* Login Container */
    .login-container {
        width: 100%;
        max-width: 460px;
        background: var(--card-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 215, 0, 0.2);
        padding: 50px 45px;
        box-shadow: 
            0 25px 50px rgba(0, 0, 0, 0.4),
            0 0 0 1px rgba(255, 215, 0, 0.05),
            inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, 
            transparent, 
            var(--primary-gold), 
            transparent);
        z-index: 2;
    }
    
    .login-container::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            45deg,
            transparent 30%,
            rgba(255, 215, 0, 0.03) 50%,
            transparent 70%
        );
        animation: shine 8s infinite linear;
        z-index: -1;
    }
    
    @keyframes shine {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    /* Header */
    .login-header {
        text-align: center;
        margin-bottom: 45px;
        position: relative;
    }
    
    .login-header::after {
        content: '';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--primary-gold), transparent);
        border-radius: 2px;
    }
    
    .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        margin-bottom: 25px;
    }
    
    .logo-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-size: 2.2rem;
        box-shadow: 
            0 10px 30px rgba(255, 215, 0, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }
    
    .logo-icon::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            to bottom right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.1) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: rotate(30deg);
    }
    
    .logo-text h1 {
        font-size: 2.4rem;
        background: linear-gradient(135deg, var(--primary-gold), #ffcc00);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 10px rgba(255, 215, 0, 0.2);
    }
    
    .logo-text p {
        color: var(--gray-text);
        font-size: 0.95rem;
        margin-top: 6px;
        letter-spacing: 0.3px;
    }
    
    .login-header h2 {
        font-size: 1.7rem;
        color: var(--light-text);
        margin-bottom: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .login-header p {
        color: var(--gray-text);
        font-size: 1rem;
        line-height: 1.5;
        max-width: 300px;
        margin: 0 auto;
    }
    
    /* Error Message */
    .error-message {
        padding: 18px 22px;
        border-radius: 14px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        background: linear-gradient(135deg, 
            rgba(255, 107, 107, 0.15), 
            rgba(255, 107, 107, 0.05));
        border: 1px solid rgba(255, 107, 107, 0.3);
        color: var(--danger);
        animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .error-message i {
        font-size: 1.4rem;
        color: var(--danger);
        flex-shrink: 0;
    }
    
    .error-message span {
        font-size: 0.95rem;
        line-height: 1.4;
        font-weight: 500;
    }
    
    /* Form Styles */
    .login-form {
        display: flex;
        flex-direction: column;
        gap: 28px;
    }
    
    .form-group {
        position: relative;
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        color: var(--light-text);
        font-weight: 500;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }
    
    .form-label i {
        color: var(--primary-gold);
        width: 20px;
        font-size: 1.1rem;
    }
    
    .form-input {
        width: 100%;
        padding: 18px 22px;
        background: rgba(13, 17, 23, 0.7);
        border: 2px solid rgba(48, 54, 61, 0.8);
        border-radius: 14px;
        color: var(--light-text);
        font-size: 1.05rem;
        font-family: inherit;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.3px;
    }
    
    .form-input:focus {
        outline: none;
        border-color: var(--primary-gold);
        background: rgba(13, 17, 23, 0.9);
        box-shadow: 
            0 0 0 4px rgba(255, 215, 0, 0.15),
            0 10px 20px rgba(0, 0, 0, 0.2);
        transform: translateY(-2px);
    }
    
    .form-input::placeholder {
        color: var(--gray-text);
    }
    
    /* Password Container */
    .password-container {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--gray-text);
        cursor: pointer;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .toggle-password:hover {
        color: var(--primary-gold);
        background: rgba(255, 215, 0, 0.1);
    }
    
    /* Login Button */
    .btn-login {
        width: 100%;
        padding: 19px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        color: #000;
        border: none;
        border-radius: 14px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
        margin-top: 15px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 10px 25px rgba(255, 215, 0, 0.25);
    }
    
    .btn-login:hover {
        transform: translateY(-4px);
        box-shadow: 
            0 20px 40px rgba(255, 215, 0, 0.35),
            0 5px 15px rgba(255, 215, 0, 0.15);
        background: linear-gradient(135deg, var(--hover-gold), #ffb700);
    }
    
    .btn-login:active {
        transform: translateY(-2px);
    }
    
    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.4), 
            transparent);
        transition: left 0.7s ease;
    }
    
    .btn-login:hover::before {
        left: 100%;
    }
    
    /* Footer */
    .login-footer {
        margin-top: 40px;
        padding-top: 25px;
        border-top: 1px solid rgba(48, 54, 61, 0.5);
        text-align: center;
        color: var(--gray-text);
        font-size: 0.9rem;
        line-height: 1.6;
        letter-spacing: 0.3px;
    }
    
    .footer-text {
        margin-bottom: 8px;
    }
    
    .back-link {
        color: var(--primary-gold);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        padding: 10px 18px;
        border-radius: 10px;
        background: rgba(255, 215, 0, 0.08);
        border: 1px solid rgba(255, 215, 0, 0.15);
    }
    
    .back-link:hover {
        background: rgba(255, 215, 0, 0.15);
        border-color: rgba(255, 215, 0, 0.3);
        transform: translateX(-5px);
    }
    
    /* Responsive */
    @media (max-width: 520px) {
        .login-container {
            padding: 35px 25px;
            margin: 0 15px;
        }
        
        .logo {
            flex-direction: column;
            gap: 20px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            font-size: 1.8rem;
        }
        
        .logo-text h1 {
            font-size: 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            font-size: 1.5rem;
        }
        
        .test-credential {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .test-label {
            min-width: auto;
        }
    }
    
    @media (max-width: 380px) {
        .login-container {
            padding: 30px 20px;
        }
        
        .btn-login {
            padding: 17px;
            font-size: 1rem;
        }
    }
    
    /* Input Error State */
    .form-input.error {
        border-color: var(--danger);
        background: rgba(255, 107, 107, 0.05);
    }
    
    .form-input.error:focus {
        box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.15);
    }
    
    /* Auto-dismiss error */
    .error-message.fade-out {
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
    }
    </style>
</head>
<body>
    <!-- Background -->
    <div class="bg-pattern"></div>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- Login Container -->
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="logo-text">
                    <h1>Sere Heaven</h1>
                    <p>Luxury Hotel & Resort</p>
                </div>
            </div>
            <h2>Admin Login</h2>
            <p>Access your hotel management dashboard</p>
        </div>
        
        <!-- Error Message -->
        <?php if($error): ?>
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Login Form dengan FIX AUTOFILL -->
        <form method="post" class="login-form" id="loginForm" autocomplete="off">
            <!-- DUMMY INPUTS untuk trick browser autofill -->
            <input type="text" name="prevent_autofill" style="display:none;" autocomplete="off">
            <input type="password" name="password_fake" style="display:none;" autocomplete="off">
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user-circle"></i>
                    Username
                </label>
                <input type="text" 
                       name="username" 
                       id="username"
                       class="form-input <?= $error ? 'error' : '' ?>" 
                       placeholder="Enter admin username"
                       required
                       autocomplete="off"
                       autocorrect="off"
                       autocapitalize="off"
                       spellcheck="false">
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-key"></i>
                    Password
                </label>
                <div class="password-container">
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="form-input <?= $error ? 'error' : '' ?>" 
                           placeholder="Enter admin password"
                           required
                           autocomplete="new-password"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false">
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" name="login" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login to Dashboard</span>
            </button>
        
        <!-- Footer -->
        <div class="login-footer">
            <p class="footer-text">© <?= date('Y') ?> Sere Heaven Hotel Management System</p>
            <p class="footer-text">Version 2.0 • Premium Edition</p>
            <a href="../index.php" class="back-link">
                <i class="fas fa-external-link-alt"></i>
                Visit Main Website
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clear form on load
        document.getElementById('loginForm').reset();
        
        // Clear inputs secara programatik
        setTimeout(function() {
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        }, 50);
        
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Clear button
        document.getElementById('clearBtn').addEventListener('click', function() {
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('username').focus();
        });
        
        // Force clear browser autofill
        function clearAutofill() {
            // Create fake form
            const fakeForm = document.createElement('form');
            fakeForm.style.display = 'none';
            fakeForm.autocomplete = 'off';
            
            // Add fake inputs
            const fakeUser = document.createElement('input');
            fakeUser.type = 'text';
            fakeUser.name = 'fake_username';
            fakeUser.autocomplete = 'off';
            
            const fakePass = document.createElement('input');
            fakePass.type = 'password';
            fakePass.name = 'fake_password';
            fakePass.autocomplete = 'new-password';
            
            fakeForm.appendChild(fakeUser);
            fakeForm.appendChild(fakePass);
            document.body.appendChild(fakeForm);
            
            // Remove after a while
            setTimeout(() => {
                document.body.removeChild(fakeForm);
            }, 100);
        }
        
        // Clear on page load
        clearAutofill();
        
        // Auto focus
        setTimeout(() => {
            document.getElementById('username').focus();
        }, 200);
        
        // Prevent form autocomplete
        document.getElementById('loginForm').addEventListener('submit', function() {
            // Kosongkan nilai sebelum submit (jika masih ada autofill)
            const user = document.getElementById('username');
            const pass = document.getElementById('password');
            
            if (user.value === 'admin' && !user.dataset.typed) {
                user.value = '';
            }
            if (pass.value === 'admin123' && !pass.dataset.typed) {
                pass.value = '';
            }
        });
        
        // Track user typing
        document.getElementById('username').addEventListener('input', function() {
            this.dataset.typed = 'true';
        });
        
        document.getElementById('password').addEventListener('input', function() {
            this.dataset.typed = 'true';
        });
        
        // Auto-dismiss error
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.classList.add('fade-out');
                setTimeout(() => {
                    if (errorMessage.parentNode) {
                        errorMessage.remove();
                    }
                }, 300);
            }, 5000);
        }
    });
    
    // Clear form on page refresh
    window.addEventListener('pageshow', function(event) {
        // Jika page di-load dari cache (back/forward)
        if (event.persisted) {
            document.getElementById('loginForm').reset();
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        }
    });
    
    // Clear on page unload
    window.addEventListener('beforeunload', function() {
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
    });
    </script>
</body>
</html>
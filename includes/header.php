<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan base path
$base_path = '/sere-heaven/'; // Sesuaikan dengan struktur folder Anda
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sere Heaven</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Gunakan ROOT RELATIVE PATH -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="left">
        <div class="logo"> <!-- Ganti h1 jadi div -->
            <a href="<?php echo $base_path; ?>index.php">
                <!-- Gunakan ROOT RELATIVE PATH -->
                <img src="<?php echo $base_path; ?>assets/img/seree.png" alt="Sere Heaven">
            </a>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <a href="<?php echo $base_path; ?>index.php">Home</a>
            <a href="<?php echo $base_path; ?>rooms.php">Cari Kamar</a>
            <a href="<?php echo $base_path; ?>facilities.php">Fasilitas</a>
            <a href="<?php echo $base_path; ?>contact.php">Contact</a>
            <a href="<?php echo $base_path; ?>testimonials.php">Testimonials</a>
        </nav>
    </div>

    <div class="right">
        <?php if(isset($_SESSION['user_id'])): ?>
            <nav class="user-nav">
                <a href="<?php echo $base_path; ?>user/my_booking.php" class="my-booking-btn">
                    <span>My Booking</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </a>
                <a class="logout" href="<?php echo $base_path; ?>user/logout.php">Logout</a>
            </nav>
        <?php else: ?>
            <a class="logout" href="<?php echo $base_path; ?>user/login.php">Login</a>
        <?php endif; ?>
    </div>
</header>

<main>
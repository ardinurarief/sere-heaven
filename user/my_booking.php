<?php
session_start();
require '../config/db.php';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/sere-heaven/';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Query untuk ambil booking user
$q = mysqli_query($conn, "
SELECT r.*, rm.room_number, rm.room_type, rm.image_url
FROM reservations r
JOIN rooms rm ON r.room_id = rm.id
WHERE r.user_id = $user_id
ORDER BY r.created_at DESC
");

// Hitung statistik
$total_bookings = mysqli_num_rows($q);
$total_spent = 0;
$bookings = [];

while ($row = mysqli_fetch_assoc($q)) {
    $bookings[] = $row;
    $total_spent += $row['total_price'];
}

// Reset pointer untuk loop nantiS
mysqli_data_seek($q, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booking - Sere Heaven</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <style>
    /* My Booking Page */
    .mybooking-page {
        min-height: 100vh;
        background: linear-gradient(rgba(13, 17, 23, 0.9), rgba(13, 17, 23, 0.95)), 
                    url('../assets/img/hotelgeser2.jpeg') center/cover no-repeat fixed;
        padding: 120px 2rem 4rem;
    }

    .mybooking-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .mybooking-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .mybooking-header h1 {
        color: #ffd700;
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .mybooking-header p {
        color: #b0b7c3;
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: rgba(25, 30, 40, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(255, 215, 0, 0.4);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
    }

    .stat-value {
        color: #ffd700;
        font-size: 2.2rem;
        font-weight: 700;
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #b0b7c3;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Booking List */
    .bookings-section {
        background: rgba(25, 30, 40, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 20px;
        padding: 2.5rem;
        margin-bottom: 3rem;
    }

    .section-title {
        color: #ffffff;
        font-size: 1.8rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .section-title::before {
        content: '📋';
        font-size: 1.5rem;
    }

    /* Booking Cards */
    .bookings-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .booking-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 215, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .booking-card:hover {
        transform: translateY(-3px);
        border-color: rgba(255, 215, 0, 0.3);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .booking-header {
        background: rgba(255, 215, 0, 0.05);
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .booking-code {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .code-badge {
        background: rgba(255, 215, 0, 0.1);
        color: #ffd700;
        padding: 0.5rem 1rem;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1.1rem;
        letter-spacing: 1px;
    }

    .booking-date {
        color: #b0b7c3;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .booking-date::before {
        content: '📅';
    }

    .booking-body {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        gap: 2rem;
        padding: 2rem;
    }

    .room-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .room-image {
        width: 100%;
        height: 180px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255, 215, 0, 0.2);
    }

    .room-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .booking-card:hover .room-image img {
        transform: scale(1.05);
    }

    .room-details h3 {
        color: #ffffff;
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .room-type {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .room-type.standard {
        background: rgba(108, 117, 125, 0.2);
        color: #8b949e;
    }

    .room-type.premium {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(212, 175, 55, 0.2));
        color: #ffd700;
    }

    .room-spec {
        color: #b0b7c3;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.3rem;
    }

    .booking-dates {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .date-group {
        background: rgba(255, 255, 255, 0.03);
        padding: 1.5rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .date-title {
        color: #ffd700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .date-value {
        color: #ffffff;
        font-size: 1.3rem;
        font-weight: 600;
        display: block;
        margin-bottom: 0.3rem;
    }

    .date-note {
        color: #8b949e;
        font-size: 0.85rem;
        font-style: italic;
    }

    .duration-badge {
        background: rgba(255, 215, 0, 0.1);
        color: #ffd700;
        padding: 0.8rem 1.5rem;
        border-radius: 30px;
        text-align: center;
        margin-top: 1rem;
    }

    .duration-value {
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
    }

    .duration-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .booking-summary {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .price-summary {
        background: rgba(255, 215, 0, 0.05);
        padding: 1.5rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .price-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .price-item:last-child {
        border-bottom: none;
    }

    .price-label {
        color: #b0b7c3;
        font-size: 0.95rem;
    }

    .price-amount {
        color: #ffffff;
        font-weight: 500;
    }

    .price-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        margin-top: 1rem;
        border-top: 2px solid rgba(255, 215, 0, 0.3);
    }

    .total-label {
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .total-amount {
        color: #ffd700;
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* Status Badge */
    .status-badge {
        padding: 0.6rem 1.2rem;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        margin-top: 1rem;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-confirmed {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .status-check_in {
        background: rgba(0, 123, 255, 0.1);
        color: #007bff;
        border: 1px solid rgba(0, 123, 255, 0.3);
    }

    .status-check_out {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, 0.3);
    }

    .status-cancelled {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .payment-method {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 1rem;
    }

    .payment-icon {
        font-size: 1.2rem;
    }

    .payment-text {
        color: #b0b7c3;
        font-size: 0.9rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #ffffff;
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: #b0b7c3;
        max-width: 500px;
        margin: 0 auto 2rem;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 3rem;
        justify-content: center;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        background: transparent;
        color: #ffd700;
        border: 1px solid rgba(255, 215, 0, 0.4);
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-primary:hover {
        background: rgba(255, 215, 0, 0.1);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        background: transparent;
        color: #ffd700; /* SAMA seperti .btn-primary */
        border: 1px solid rgba(255, 215, 0, 0.4); /* SAMA seperti .btn-primary */
        padding: 1rem 2.5rem;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-secondary:hover {
        background: rgba(255, 215, 0, 0.1); /* SAMA seperti .btn-primary */
        border-color: #ffd700; /* SAMA seperti .btn-primary */
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2); /* SAMA seperti .btn-primary */
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .booking-body {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .mybooking-header h1 {
            font-size: 2.5rem;
        }
    }

    @media (max-width: 768px) {
        .mybooking-page {
            padding: 100px 1rem 2rem;
        }
        
        .booking-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .bookings-section {
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .btn-primary, .btn-secondary {
            width: 100%;
            max-width: 300px;
        }
    }

    @media (max-width: 480px) {
        .mybooking-header h1 {
            font-size: 2rem;
        }
        
        .stat-value {
            font-size: 1.8rem;
        }
        
        .room-image {
            height: 150px;
        }
    }
    
    </style>
</head>
<body>
  <?php 
    // Set base_url ke session agar bisa diakses header/footer
    $_SESSION['base_url'] = $base_url;
    include '../includes/header.php'; 
    ?>

    <div class="mybooking-page">
        <div class="mybooking-container">
            <!-- Header -->
            <div class="mybooking-header">
                <h1 class="elegant-title">My Booking</h1>
                <p>Lihat dan kelola semua reservasi Anda di Sere Heaven</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📋</span>
                    <span class="stat-value"><?= $total_bookings ?></span>
                    <span class="stat-label">Total Bookings</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-icon">💰</span>
                    <span class="stat-value">Rp <?= number_format($total_spent) ?></span>
                    <span class="stat-label">Total Spent</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-icon">🏨</span>
                    <span class="stat-value"><?= count(array_unique(array_column($bookings, 'room_type'))) ?></span>
                    <span class="stat-label">Room Types</span>
                </div>
            </div>

            <!-- Booking List -->
            <div class="bookings-section">
                <h2 class="section-title">Riwayat Reservasi</h2>
                
                <?php if($total_bookings == 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Belum Ada Reservasi</h3>
                        <p>Anda belum melakukan booking kamar. Yuk, pesan kamar pertama Anda sekarang!</p>
                        <a href="../rooms.php" class="btn-primary">
                            <span>Cari Kamar</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bookings-list">
                        <?php while ($booking = mysqli_fetch_assoc($q)): 
                            // Calculate days
                            $check_in = new DateTime($booking['check_in']);
                            $check_out = new DateTime($booking['check_out']);
                            $days = $check_in->diff($check_out)->days;
                            $days = ($days < 1) ? 1 : $days;
                            
                            // Status class
                            $status_class = 'status-' . $booking['status'];
                            
                            // Payment icon
                            $payment_icons = [
                                'transfer' => '🏦',
                                'ewallet' => '📱',
                                'pay_on_site' => '💵'
                            ];
                            $payment_text = [
                                'transfer' => 'Transfer Bank',
                                'ewallet' => 'E-Wallet',
                                'pay_on_site' => 'Bayar di Tempat'
                            ];
                        ?>
                        <div class="booking-card">
                            <!-- Booking Header -->
                            <div class="booking-header">
                                <div class="booking-code">
                                    <span class="code-badge"><?= $booking['booking_code'] ?></span>
                                    <span class="booking-date"><?= date('d M Y', strtotime($booking['created_at'])) ?></span>
                                </div>
                                <div class="<?= $status_class ?> status-badge">
                                    <?= strtoupper($booking['status']) ?>
                                </div>
                            </div>
                            
                            <!-- Booking Body -->
                            <div class="booking-body">
                                <!-- Room Info -->
                                <div class="room-info">
                                    <div class="room-image">
                                        <?php 
                                        $image_path = '../assets/img/' . ($booking['image_url'] ?? 'default.jpg');
                                        if (file_exists($image_path) && ($booking['image_url'] ?? '') != 'default.jpg'): 
                                        ?>
                                            <img src="<?= $image_path ?>" alt="Kamar <?= $booking['room_number'] ?>">
                                        <?php else: ?>
                                            <div style="width:100%;height:100%;background:#1a1f2e;display:flex;align-items:center;justify-content:center;color:#ffd700;font-size:2rem;">
                                                🏨
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="room-details">
                                        <h3><?= $booking['room_type'] ?> Suite</h3>
                                        <div class="room-type <?= strtolower($booking['room_type']) ?>">
                                            <?= $booking['room_type'] ?>
                                        </div>
                                        <div class="room-spec">
                                            <span>Kamar #<?= $booking['room_number'] ?></span>
                                        </div>
                                        <div class="room-spec">
                                            <span>Kapasitas: <?= $booking['capacity'] ?> orang</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dates -->
                                <div class="booking-dates">
                                    <div class="date-group">
                                        <div class="date-title">
                                            <span>Check-in</span>
                                        </div>
                                        <span class="date-value"><?= date('d M Y', strtotime($booking['check_in'])) ?></span>
                                        <span class="date-note">Setelah 14:00 WIB</span>
                                    </div>
                                    
                                    <div class="date-group">
                                        <div class="date-title">
                                            <span>Check-out</span>
                                        </div>
                                        <span class="date-value"><?= date('d M Y', strtotime($booking['check_out'])) ?></span>
                                        <span class="date-note">Sebelum 12:00 WIB</span>
                                    </div>
                                    
                                    <div class="duration-badge">
                                        <span class="duration-value"><?= $days ?></span>
                                        <span class="duration-label">Malam</span>
                                    </div>
                                </div>
                                
                                <!-- Summary -->
                                <div class="booking-summary">
                                    <div class="price-summary">
                                        <div class="price-item">
                                            <span class="price-label">Harga per Malam</span>
                                            <span class="price-amount">Rp <?= number_format($booking['total_price'] / $days) ?></span>
                                        </div>
                                        
                                        <div class="price-item">
                                            <span class="price-label"><?= $days ?> Malam</span>
                                            <span class="price-amount">Rp <?= number_format($booking['total_price']) ?></span>
                                        </div>
                                        
                                        <div class="price-item">
                                            <span class="price-label">Pajak (10%)</span>
                                            <span class="price-amount">Rp <?= number_format($booking['total_price'] * 0.1) ?></span>
                                        </div>
                                        
                                        <div class="price-total">
                                            <span class="total-label">Total</span>
                                            <span class="total-amount">Rp <?= number_format($booking['total_price'] * 1.1) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method">
                                        <span class="payment-icon"><?= $payment_icons[$booking['payment_method']] ?></span>
                                        <span class="payment-text"><?= $payment_text[$booking['payment_method']] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="../rooms.php" class="btn-primary">
                    <span>Pesan Kamar Lagi</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
                
                <a href="../index.php" class="btn-primary" style="padding-left: 3.5rem; padding-right: 3.5rem;">
                    <span>Kembali ke Beranda</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
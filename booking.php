<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php");
    exit;
}

$id = $_GET['id'];
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

// Get room details
$q = mysqli_query($conn, "SELECT * FROM rooms WHERE id=$id");
$r = mysqli_fetch_assoc($q);

// Calculate days and total price
$total_price = 0;
$days = 0;
if ($check_in && $check_out) {
    $days = (strtotime($check_out) - strtotime($check_in)) / 86400;
    $days = ($days < 1) ? 1 : $days;
    $total_price = $days * $r['price_per_night'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="booking-page">
    <div class="booking-container">
        <div class="booking-header">
            <h1 class="elegant-title">Booking Kamar</h1>
            <p>Lengkapi data pemesanan Anda</p>
        </div>

        <div class="booking-content">
            <!-- Room Summary Card -->
            <div class="room-summary">
                <div class="summary-header">
                    <h3>Detail Kamar</h3>
                    <div class="room-type-badge <?= strtolower($r['room_type']) ?>">
                        <?= $r['room_type'] ?>
                    </div>
                </div>
                
                <div class="room-details">
                    <div class="detail-item">
                        <span class="detail-label">Tipe Kamar</span>
                        <span class="detail-value"><?= $r['room_type'] ?> Suite</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Nomor Kamar</span>
                        <span class="detail-value">#<?= $r['room_number'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kapasitas</span>
                        <span class="detail-value"><?= $r['capacity'] ?> orang</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Harga per Malam</span>
                        <span class="detail-value">Rp <?= number_format($r['price_per_night']) ?></span>
                    </div>
                </div>
                
                <?php if($check_in && $check_out): ?>
                <div class="date-summary">
                    <div class="date-item">
                        <span class="date-label">Check-in</span>
                        <span class="date-value"><?= date('d M Y', strtotime($check_in)) ?></span>
                    </div>
                    <div class="date-arrow">→</div>
                    <div class="date-item">
                        <span class="date-label">Check-out</span>
                        <span class="date-value"><?= date('d M Y', strtotime($check_out)) ?></span>
                    </div>
                    <div class="duration">
                        <span class="duration-label">Durasi</span>
                        <span class="duration-value"><?= $days ?> malam</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Booking Form -->
            <div class="booking-form-container">
                <form method="post" action="booking_process.php" class="booking-form">
                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="check_in" value="<?= $check_in ?>">
                    <input type="hidden" name="check_out" value="<?= $check_out ?>">
                    
                    <!-- Date Selection (jika belum ada dari rooms.php) -->
                    <?php if(!$check_in || !$check_out): ?>
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">📅</span> Pilih Tanggal
                        </h3>
                        <div class="date-input-group">
                            <div class="input-field">
                                <label>Check-in</label>
                                <input type="date" name="check_in" class="form-input" required 
                                       min="<?= date('Y-m-d'); ?>">
                            </div>
                            <div class="input-field">
                                <label>Check-out</label>
                                <input type="date" name="check_out" class="form-input" required 
                                       min="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">💳</span> Metode Pembayaran
                        </h3>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="transfer" checked>
                                <div class="option-content">
                                    <span class="option-icon">🏦</span>
                                    <div class="option-text">
                                        <span class="option-title">Transfer Bank</span>
                                        <span class="option-desc">BCA, Mandiri, BNI, BRI</span>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="ewallet">
                                <div class="option-content">
                                    <span class="option-icon">📱</span>
                                    <div class="option-text">
                                        <span class="option-title">E-Wallet</span>
                                        <span class="option-desc">OVO, GoPay, Dana, ShopeePay</span>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="pay_on_site">
                                <div class="option-content">
                                    <span class="option-icon">💵</span>
                                    <div class="option-text">
                                        <span class="option-title">Bayar di Tempat</span>
                                        <span class="option-desc">Tunai / Kartu Kredit di Hotel</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="price-summary">
                        <div class="summary-header">
                            <h3>Ringkasan Biaya</h3>
                        </div>
                        
                        <div class="price-details">
                            <div class="price-item">
                                <span>Harga per malam</span>
                                <span>Rp <?= number_format($r['price_per_night']) ?></span>
                            </div>
                            
                            <?php if($check_in && $check_out): ?>
                            <div class="price-item">
                                <span><?= $days ?> malam</span>
                                <span>Rp <?= number_format($total_price) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="price-item tax">
                                <span>Pajak & Layanan (10%)</span>
                                <span>
                                    <?php 
                                    $tax = $check_in && $check_out ? $total_price * 0.1 : $r['price_per_night'] * 0.1;
                                    echo 'Rp ' . number_format($tax);
                                    ?>
                                </span>
                            </div>
                            
                            <div class="price-total">
                                <span>Total Pembayaran</span>
                                <span class="total-amount">
                                    <?php 
                                    $grand_total = $check_in && $check_out ? 
                                        $total_price + $tax : 
                                        $r['price_per_night'] + ($r['price_per_night'] * 0.1);
                                    echo 'Rp ' . number_format($grand_total);
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="booking-note">
                            <span class="note-icon">ℹ️</span>
                            <span>Pembayaran dapat dilakukan setelah booking dikonfirmasi</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="rooms.php" class="cancel-btn">
                            <span>Kembali</span>
                        </a>
                        <button type="submit" class="confirm-btn">
                            <span>Konfirmasi Booking</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Booking Page */
.booking-page {
    min-height: 100vh;
    background: linear-gradient(rgba(13, 17, 23, 0.9), rgba(13, 17, 23, 0.95)), 
                url('assets/img/hotelgeser1.png') center/cover no-repeat fixed;
    padding: 120px 2rem 4rem;
}

.booking-container {
    max-width: 1200px;
    margin: 0 auto;
}

.booking-header {
    text-align: center;
    margin-bottom: 3rem;
}

.booking-header h1 {
    color: #ffd700;
    font-size: 3rem;
    margin-bottom: 1rem;
}

.booking-header p {
    color: #b0b7c3;
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
}

.booking-content {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 3rem;
}

/* Room Summary */
.room-summary {
    background: rgba(25, 30, 40, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    padding: 2rem;
    height: fit-content;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

.summary-header h3 {
    color: #ffffff;
    font-size: 1.5rem;
}

.room-type-badge {
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 600;
}

.room-type-badge.premium {
    background: linear-gradient(135deg, #ffd700, #d4af37);
    color: #000;
}

.room-type-badge.standard {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    backdrop-filter: blur(10px);
}

.room-details {
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: #b0b7c3;
    font-size: 0.95rem;
}

.detail-value {
    color: #ffffff;
    font-weight: 500;
}

.date-summary {
    background: rgba(255, 215, 0, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
}

.date-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.date-item {
    flex: 1;
}

.date-label {
    display: block;
    color: #b0b7c3;
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
}

.date-value {
    display: block;
    color: #ffd700;
    font-weight: 600;
    font-size: 1.1rem;
}

.date-arrow {
    color: #ffd700;
    font-size: 1.5rem;
}

.duration {
    text-align: center;
}

.duration-label {
    display: block;
    color: #b0b7c3;
    font-size: 0.85rem;
}

.duration-value {
    display: block;
    color: #ffffff;
    font-weight: 600;
    font-size: 1.2rem;
}

/* Booking Form */
.booking-form-container {
    background: rgba(25, 30, 40, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    padding: 2.5rem;
}

.form-section {
    margin-bottom: 2.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #ffffff;
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
}

.section-icon {
    color: #ffd700;
}

.date-input-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.input-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-field label {
    color: #d4d7dc;
    font-size: 0.95rem;
    font-weight: 500;
}

.form-input {
    padding: 1rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #ffd700;
    background: rgba(255, 215, 0, 0.05);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

/* Payment Options */
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-option {
    display: block;
    cursor: pointer;
}

.payment-option input[type="radio"] {
    display: none;
}

.option-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.2rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.payment-option input[type="radio"]:checked + .option-content {
    background: rgba(255, 215, 0, 0.1);
    border-color: #ffd700;
    box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3);
}

.option-icon {
    font-size: 1.5rem;
    color: #ffd700;
}

.option-text {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.option-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1rem;
}

.option-desc {
    color: #b0b7c3;
    font-size: 0.85rem;
}

/* Price Summary */
.price-summary {
    background: rgba(255, 215, 0, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.price-details {
    margin: 1.5rem 0;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    color: #b0b7c3;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.price-item.tax {
    color: #ff6b6b;
}

.price-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 0 0;
    color: #ffffff;
    font-size: 1.2rem;
    font-weight: 600;
    border-top: 2px solid rgba(255, 215, 0, 0.3);
    margin-top: 1rem;
}

.total-amount {
    color: #ffd700;
    font-size: 1.5rem;
}

.booking-note {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #b0b7c3;
    font-size: 0.9rem;
    padding: 0.8rem;
    background: rgba(255, 215, 0, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 215, 0, 0.1);
}

.note-icon {
    color: #ffd700;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1.5rem;
    margin-top: 2rem;
}

.cancel-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    background: transparent;
    color: #b0b7c3;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 1rem 2rem;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    flex: 1;
}

.cancel-btn:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.confirm-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    background: transparent;
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.4);
    padding: 1rem 2rem;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 2;
}

.confirm-btn:hover {
    background: rgba(255, 215, 0, 0.1);
    border-color: #ffd700;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

/* Responsive */
@media (max-width: 1024px) {
    .booking-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .booking-header h1 {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .booking-page {
        padding: 100px 1rem 2rem;
    }
    
    .booking-header h1 {
        font-size: 2rem;
    }
    
    .booking-form-container {
        padding: 1.5rem;
    }
    
    .date-input-group {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .date-summary {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .date-arrow {
        transform: rotate(90deg);
    }
}

@media (max-width: 480px) {
    .booking-header h1 {
        font-size: 1.8rem;
    }
    
    .section-title {
        font-size: 1.1rem;
    }
    
    .option-content {
        padding: 1rem;
    }
    
    .price-total {
        font-size: 1rem;
    }
    
    .total-amount {
        font-size: 1.2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
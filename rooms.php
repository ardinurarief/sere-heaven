<?php
session_start();
require 'config/db.php';
include 'includes/header.php';

$check_in  = $_GET['check_in']  ?? '';
$check_out = $_GET['check_out'] ?? '';

$sql = "
SELECT * FROM rooms
WHERE status='available'
";

if ($check_in && $check_out) {
    $sql .= "
    AND id NOT IN (
        SELECT room_id FROM reservations
        WHERE status IN ('pending','confirmed','check_in')
        AND (
            check_in < '$check_out'
            AND check_out > '$check_in'
        )
    )
    ";
}

$sql .= " ORDER BY 
    CASE room_type 
        WHEN 'Premium' THEN 1 
        WHEN 'Standard' THEN 2 
        ELSE 3 
    END,
    room_number ASC";

$q = mysqli_query($conn, $sql);

// Hitung jumlah kamar per tipe
$room_counts = [
    'Standard' => 0,
    'Premium' => 0
];

// Ambil data kamar untuk loop nanti
$rooms = [];
while ($row = mysqli_fetch_assoc($q)) {
    $rooms[] = $row;
    $room_counts[$row['room_type']]++;
}

$total_rooms = count($rooms);
?>

<div class="rooms-page">
    <div class="rooms-container">
        <div class="rooms-header">
            <h1 class="elegant-title">Cari Kamar</h1>
            <p>Temukan kamar yang sesuai dengan kebutuhan Anda</p>
            <div class="room-stats">
                <span class="stat-item">🏨 <?= $room_counts['Standard'] ?> Kamar Standard</span>
                <span class="stat-item">⭐ <?= $room_counts['Premium'] ?> Kamar Premium</span>
                <span class="stat-item">🛏️ Total <?= $total_rooms ?> Kamar</span>
            </div>
        </div>

        <!-- Filter Tanggal -->
        <div class="filter-card">
            <h3><span class="filter-icon">📅</span> Cari Berdasarkan Tanggal</h3>
            <form method="get" class="filter-form">
                <div class="date-inputs">
                    <div class="input-group">
                        <label>Check-in</label>
                        <input type="date" name="check_in" value="<?= $check_in ?>" required 
                               class="date-input" min="<?= date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="input-group">
                        <label>Check-out</label>
                        <input type="date" name="check_out" value="<?= $check_out ?>" required 
                               class="date-input" min="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
                
                <button type="submit" class="filter-btn">
                    <span>Cari Kamar Tersedia</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>
            
            <?php if($check_in && $check_out): ?>
                <div class="search-info">
                    <p>🔍 Menampilkan kamar tersedia untuk tanggal <strong><?= date('d M Y', strtotime($check_in)) ?></strong> hingga <strong><?= date('d M Y', strtotime($check_out)) ?></strong></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Daftar Kamar -->
        <div class="rooms-grid">
            <?php if ($total_rooms == 0): ?>
                <div class="no-rooms">
                    <div class="no-rooms-icon">🏨</div>
                    <h3>Maaf, tidak ada kamar tersedia</h3>
                    <p>Tidak ada kamar yang tersedia untuk tanggal yang dipilih. Silakan coba tanggal lain.</p>
                    <a href="rooms.php" class="btn-primary">Cari Tanggal Lain</a>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $r): ?>
                    <div class="room-card">
                        <!-- GANTI bagian room-image di rooms.php (frontend) -->
<!-- GANTI jadi seperti ini -->
<div class="room-image" 
     style="background: url('<?php 
        // Cek gambar dari database
        if ($r['image_url'] && $r['image_url'] != 'default.jpg') {
            echo 'assets/img/' . $r['image_url'];
        } else {
            // Fallback ke gambar default berdasarkan tipe
            echo $r['room_type'] == 'Premium' ? 'assets/img/rooms/premium1.jpg' : 'assets/img/rooms/standard1.jpg';
        }
     ?>'); background-size: cover; background-position: center;">
    
    <?php if($r['room_type'] == 'Premium'): ?>
        <div class="room-badge premium">Premium</div>
    <?php else: ?>
        <div class="room-badge standard">Standard</div>
    <?php endif; ?>
</div>
                        
                        <div class="room-content">
                            <div class="room-header">
                                <h3><?= $r['room_type']; ?> Suite</h3>
                                <div class="room-number">Kamar #<?= $r['room_number']; ?></div>
                            </div>
                            
                            <div class="room-description">
                                <p><?= $r['description'] ?: 'Kamar nyaman dengan fasilitas lengkap'; ?></p>
                            </div>
                            
                            <div class="room-features">
                                <div class="feature">
                                    <span class="feature-icon">👥</span>
                                    <span class="feature-text"><?= $r['capacity']; ?> orang</span>
                                </div>
                                <div class="feature">
                                    <span class="feature-icon">🛏️</span>
                                    <span class="feature-text">King Size Bed</span>
                                </div>
                                <div class="feature">
                                    <span class="feature-icon">🚿</span>
                                    <span class="feature-text">Private Bathroom</span>
                                </div>
                                <?php if($r['room_type'] == 'Premium'): ?>
                                    <div class="feature">
                                        <span class="feature-icon">🌅</span>
                                        <span class="feature-text">City View</span>
                                    </div>
                                    <div class="feature">
                                        <span class="feature-icon">🛁</span>
                                        <span class="feature-text">Bathtub</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="room-price">
                                <div class="price-info">
                                    <span class="price-label">Harga per malam</span>
                                    <span class="price-amount">Rp <?= number_format($r['price_per_night']); ?></span>
                                </div>
                                <?php if($r['room_type'] == 'Premium'): ?>
                                    <div class="price-note">
                                        <span class="note-icon">✨</span>
                                        <span>Termasuk breakfast untuk <?= $r['capacity']; ?> orang</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="room-action">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="booking.php?id=<?= $r['id']; ?>&check_in=<?= $check_in; ?>&check_out=<?= $check_out; ?>" 
                                       class="book-btn">
                                        <span>Pesan Sekarang</span>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <a href="user/login.php" class="book-btn login-required">
                                        <span>Login untuk Pesan</span>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if($total_rooms > 0): ?>
            <div class="rooms-footer">
                <div class="footer-content">
                    <div class="footer-item">
                        <span class="footer-icon"></span>
                        <span>FREE WIFI DI SELURUH AREA</span>
                    </div>
                    <div class="footer-item">
                        <span class="footer-icon"></span>
                        <span>PARKIR GRATIS</span>
                    </div>
                    <div class="footer-item">
                        <span class="footer-icon"></span>
                        <span>24/7 ROOM SERVICE</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Rooms Page Styling */
.rooms-page {
    min-height: 100vh;
    background: linear-gradient(rgba(13, 17, 23, 0.85), rgba(13, 17, 23, 0.9)), 
                url('assets/img/hotelbg2.webp') center/cover no-repeat fixed;
    padding: 120px 2rem 4rem;
}
.rooms-container {
    max-width: 1400px;
    margin: 0 auto;
}

.rooms-header {
    text-align: center;
    margin-bottom: 3rem;
}

.rooms-header h1 {
    color: #ffd700;
    font-size: 3rem;
    margin-bottom: 1rem;
}

.rooms-header p {
    color: #b0b7c3;
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Filter Card */
.filter-card {
    background: rgba(25, 30, 40, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 3rem;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

.filter-card h3 {
    color: #ffffff;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.filter-icon {
    color: #ffd700;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.date-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-group label {
    color: #d4d7dc;
    font-size: 0.95rem;
    font-weight: 500;
}

.date-input {
    padding: 1rem 1.2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 12px;
    color: #ffffff;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.date-input:focus {
    outline: none;
    border-color: #ffd700;
    background: rgba(255, 215, 0, 0.05);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

.filter-btn {
    background: transparent;
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.4);
    padding: 1rem 2rem;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    align-self: flex-start;
    margin-top: 1rem;
}

.filter-btn:hover {
    background: rgba(255, 215, 0, 0.1);
    border-color: #ffd700;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

.search-info {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 12px;
    color: #ffd700;
}

/* Rooms Grid */
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.room-card {
    background: rgba(25, 30, 40, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.room-card:hover {
    transform: translateY(-10px);
    border-color: rgba(255, 215, 0, 0.4);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

.room-card:hover .room-image {
    transform: scale(1.05);
    transition: transform 0.5s ease;
}

.room-image {
    position: relative;
    height: 200px;
    border-radius: 20px 20px 0 0;
    overflow: hidden;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.room-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(to bottom, 
        rgba(0, 0, 0, 0.4) 0%, 
        rgba(0, 0, 0, 0.2) 60%, 
        transparent 100%);
    z-index: 1;
}

.room-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 0.5rem 1rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    z-index: 1;
}

.room-badge.premium {
    background: linear-gradient(135deg, #ffd700, #d4af37);
    color: #000;
}

.room-badge.standard {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    backdrop-filter: blur(10px);
}


.room-content {
    padding: 2rem;
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.room-header h3 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
}

.room-number {
    color: #ffd700;
    font-size: 0.9rem;
    font-weight: 500;
    background: rgba(255, 215, 0, 0.1);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

.room-features {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #b0b7c3;
    font-size: 0.9rem;
}

.feature-icon {
    color: #ffd700;
}

.room-price {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1.5rem;
    margin-bottom: 1.5rem;
}

.price-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-label {
    color: #8b949e;
    font-size: 0.9rem;
}

.price-amount {
    color: #ffd700;
    font-size: 1.5rem;
    font-weight: 700;
}

.room-action {
    text-align: center;
}

.book-btn {
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
    text-decoration: none;
    width: 100%;
}

.book-btn:hover {
    background: rgba(255, 215, 0, 0.1);
    border-color: #ffd700;
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

.book-btn.login-required {
    background: rgba(255, 215, 0, 0.05);
}

/* No Rooms State */
.no-rooms {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(25, 30, 40, 0.8);
    border-radius: 20px;
    border: 1px solid rgba(255, 215, 0, 0.2);
}

.no-rooms-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.7;
}

.no-rooms h3 {
    color: #ffffff;
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.no-rooms p {
    color: #b0b7c3;
    max-width: 500px;
    margin: 0 auto 2rem;
}

/* Rooms Footer */
.rooms-footer {
    text-align: center;
    padding: 2rem;
    background: rgba(255, 215, 0, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.1);
    border-radius: 15px;
    color: #ffd700;
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .rooms-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
    
    .rooms-header h1 {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .rooms-page {
        padding: 100px 1rem 2rem;
    }
    
    .date-inputs {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .rooms-header h1 {
        font-size: 2rem;
    }
    
    .filter-card {
        padding: 1.5rem;
    }
    
    .room-content {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .rooms-grid {
        grid-template-columns: 1fr;
    }
    
    .rooms-header h1 {
        font-size: 1.8rem;
    }
    
    .room-header {
        flex-direction: column;
        gap: 0.5rem;
    }
}

.room-description {
    margin-bottom: 1rem;
    color: #b0b7c3;
    font-size: 0.9rem;
    line-height: 1.5;
}
</style>

<?php include 'includes/footer.php'; ?>
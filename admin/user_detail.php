<?php
require 'auth.php';
require '../config/db.php';

if (!isset($_GET['id'])) {
    die('User ID required');
}

$user_id = (int)$_GET['id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    die('User not found');
}

// Get user bookings
$bookings_query = "SELECT r.*, rm.room_number, rm.room_type 
                   FROM reservations r 
                   JOIN rooms rm ON r.room_id = rm.id 
                   WHERE r.user_id = $user_id 
                   ORDER BY r.created_at DESC 
                   LIMIT 5";
$bookings_result = mysqli_query($conn, $bookings_query);

// Get user testimonials
$testimonials_query = "SELECT * FROM testimonials 
                       WHERE user_id = $user_id 
                       ORDER BY created_at DESC 
                       LIMIT 3";
$testimonials_result = mysqli_query($conn, $testimonials_query);

// Time ago function
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    if ($diff < 2592000) return floor($diff/604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff/2592000) . ' months ago';
    return floor($diff/31536000) . ' years ago';
}
?>

<style>
.user-detail-modal {
    color: #f0f6fc;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    min-width: 80px;
    min-height: 80px;
    background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-weight: bold;
    font-size: 2rem;
    border: 3px solid rgba(255, 215, 0, 0.3);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
}

.detail-label {
    color: #8b949e;
    font-size: 0.9rem;
    margin-bottom: 8px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    color: #f0f6fc;
    font-weight: 500;
    font-size: 1rem;
    margin-bottom: 5px;
}

.detail-value.email {
    color: #ffd700;
    font-weight: 600;
}

.booking-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 3px solid #ffd700;
    border: 1px solid #30363d;
}

.testimonial-card {
    background: rgba(255, 215, 0, 0.05);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 3px solid #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}

.status-confirmed {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-check_in {
    background: rgba(23, 162, 184, 0.2);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.3);
}

.status-check_out {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.3);
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.action-button {
    flex: 1;
    text-align: center;
    padding: 12px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.btn-bookings {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.btn-bookings:hover {
    background: rgba(40, 167, 69, 0.3);
}

.btn-reviews {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.btn-reviews:hover {
    background: rgba(255, 193, 7, 0.3);
}

.star-rating {
    color: #ffc107;
    font-size: 1rem;
    letter-spacing: 2px;
}

.view-all-link {
    color: #ffd700;
    text-decoration: none;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s ease;
}

.view-all-link:hover {
    color: #e6c200;
    text-decoration: underline;
}
</style>

<div class="user-detail-modal">
    <!-- User Info -->
    <div class="user-detail-item" style="margin-bottom: 25px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="user-avatar-large">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div>
                <h3 style="color: #f0f6fc; margin: 0 0 5px 0; font-size: 1.4rem;"><?= htmlspecialchars($user['name']) ?></h3>
                <div style="color: #8b949e; font-size: 0.9rem;">User ID: #<?= $user['id'] ?></div>
            </div>
        </div>
    </div>

    <!-- Contact Info -->
    <div class="user-detail-item" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #30363d;">
        <span class="detail-label">Contact Information</span>
        <div class="detail-value email">
            <i class="fas fa-envelope" style="margin-right: 8px;"></i>
            <?= htmlspecialchars($user['email']) ?>
        </div>
        <div class="detail-value">
            <i class="fas fa-phone" style="margin-right: 8px;"></i>
            <?= $user['phone'] ? htmlspecialchars($user['phone']) : 'No phone number' ?>
        </div>
    </div>

    <!-- Account Info -->
    <div class="user-detail-item" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #30363d;">
        <span class="detail-label">Account Information</span>
        <div class="detail-value">
            <i class="fas fa-calendar-plus" style="margin-right: 8px;"></i>
            Joined: <?= date('d M Y', strtotime($user['created_at'])) ?>
        </div>
        <div style="color: #8b949e; font-size: 0.85rem; margin-top: 8px;">
            <i class="fas fa-clock" style="margin-right: 5px;"></i>
            Member for <?= time_ago($user['created_at']) ?>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="user-detail-item" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #30363d;">
        <span class="detail-label">
            <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>
            Recent Bookings (<?= mysqli_num_rows($bookings_result) ?> total)
        </span>
        
        <?php if(mysqli_num_rows($bookings_result) > 0): ?>
            <div style="margin-top: 15px;">
                <?php while($booking = mysqli_fetch_assoc($bookings_result)): 
                    $status_class = 'status-' . $booking['status'];
                ?>
                <div class="booking-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #f0f6fc; margin-bottom: 5px;">
                                <?= $booking['room_type'] ?> - Room #<?= $booking['room_number'] ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #8b949e;">
                                <i class="fas fa-calendar-day" style="margin-right: 5px;"></i>
                                <?= date('d M Y', strtotime($booking['check_in'])) ?> - <?= date('d M Y', strtotime($booking['check_out'])) ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #ffd700; margin-top: 5px;">
                                <i class="fas fa-tag" style="margin-right: 5px;"></i>
                                Rp <?= number_format($booking['total_price']) ?>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge <?= $status_class ?>">
                                <?= strtoupper($booking['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <a href="reservations.php?user_id=<?= $user['id'] ?>" class="view-all-link">
                    View all bookings <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <div style="color: #8b949e; font-style: italic; margin-top: 10px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 8px; text-align: center;">
                <i class="fas fa-calendar-times" style="font-size: 1.5rem; margin-bottom: 10px; display: block; color: #6c757d;"></i>
                No bookings yet
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Testimonials -->
    <div class="user-detail-item" style="margin-bottom: 25px;">
        <span class="detail-label">
            <i class="fas fa-star" style="margin-right: 8px;"></i>
            Recent Testimonials (<?= mysqli_num_rows($testimonials_result) ?> total)
        </span>
        
        <?php if(mysqli_num_rows($testimonials_result) > 0): ?>
            <div style="margin-top: 15px;">
                <?php while($testimonial = mysqli_fetch_assoc($testimonials_result)): 
                    $status_class = 'status-' . $testimonial['status'];
                ?>
                <div class="testimonial-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 15px;">
                        <div style="flex: 1;">
                            <div class="star-rating" style="margin-bottom: 8px;">
                                <?= str_repeat('★', $testimonial['rating']) ?><?= str_repeat('☆', 5 - $testimonial['rating']) ?>
                            </div>
                            <div style="color: #f0f6fc; font-size: 0.9rem; margin-bottom: 8px;">
                                <?= htmlspecialchars(substr($testimonial['comment'], 0, 120)) ?>
                                <?= strlen($testimonial['comment']) > 120 ? '...' : '' ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #8b949e;">
                                <i class="far fa-clock" style="margin-right: 5px;"></i>
                                <?= date('d M Y', strtotime($testimonial['created_at'])) ?>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge <?= $status_class ?>">
                                <?= strtoupper($testimonial['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <a href="testimonials.php?user_id=<?= $user['id'] ?>" class="view-all-link">
                    View all testimonials <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <div style="color: #8b949e; font-style: italic; margin-top: 10px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 8px; text-align: center;">
                <i class="far fa-star" style="font-size: 1.5rem; margin-bottom: 10px; display: block; color: #6c757d;"></i>
                No testimonials yet
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div style="display: flex; gap: 15px; margin-top: 25px; padding-top: 25px; border-top: 1px solid #30363d;">
        <a href="reservations.php?user_id=<?= $user['id'] ?>" 
           class="action-button btn-bookings">
            <i class="fas fa-calendar"></i> View Bookings
        </a>
        <a href="testimonials.php?user_id=<?= $user['id'] ?>" 
           class="action-button btn-reviews">
            <i class="fas fa-star"></i> View Reviews
        </a>
    </div>
</div>
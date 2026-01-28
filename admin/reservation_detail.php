<?php
require 'auth.php';
require '../config/db.php';

// ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get reservation details
$query = "
    SELECT r.*, u.name, u.email, u.phone, 
           rm.room_number, rm.room_type, rm.price_per_night, rm.description, rm.image_url
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.id = $id";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
}

$reservation = mysqli_fetch_assoc($result);

if (!$reservation) {
    die('Reservation not found');
}

// Calculate nights
$check_in = new DateTime($reservation['check_in']);
$check_out = new DateTime($reservation['check_out']);
$nights = $check_out->diff($check_in)->days;

// Handle status update
if (isset($_POST['update']) && isset($_POST['status'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $admin_note = isset($_POST['admin_note']) ? mysqli_real_escape_string($conn, $_POST['admin_note']) : '';
    
    // Check if updated_at column exists
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM reservations LIKE 'updated_at'");
    $has_updated_at = mysqli_num_rows($check_column) > 0;
    
    // Build update query
    if ($has_updated_at) {
        $update_query = "UPDATE reservations SET status = '$status', updated_at = NOW() WHERE id = $id";
    } else {
        $update_query = "UPDATE reservations SET status = '$status' WHERE id = $id";
    }
    
    // Execute update
    if (mysqli_query($conn, $update_query)) {
        header("Location: reservation_detail.php?id=$id&updated=1&status=" . urlencode($status));
        exit;
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-gold: #ffd700;
        --primary-dark: #0d1117;
        --secondary-dark: #161b22;
        --accent-blue: #58a6ff;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --light-text: #f0f6fc;
        --gray-text: #8b949e;
        --border-color: #30363d;
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
        line-height: 1.6;
        padding: 20px;
        min-height: 100vh;
    }

    .container {
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .page-header h1 {
        color: var(--primary-gold);
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--secondary-dark);
        color: var(--light-text);
        text-decoration: none;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--primary-gold);
        transform: translateY(-2px);
    }

    /* Error Message */
    .alert-error {
        background: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: #dc3545;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Success Message */
    .alert-success {
        background: rgba(40, 167, 69, 0.2);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: #28a745;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Booking Code Banner */
    .booking-banner {
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .booking-code {
        color: #000;
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 5px;
    }

    .booking-date {
        color: rgba(0,0,0,0.7);
        font-size: 0.9rem;
    }

    /* Status Badge */
    .status-display {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-confirmed {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
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

    /* Details Grid */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .detail-card {
        background: var(--secondary-dark);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 25px;
        transition: all 0.3s ease;
    }

    .detail-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-gold);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .card-header i {
        color: var(--primary-gold);
        font-size: 1.2rem;
    }

    .card-header h3 {
        color: var(--light-text);
        font-size: 1.2rem;
    }

    .info-item {
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-label {
        color: var(--gray-text);
        font-size: 0.9rem;
    }

    .info-value {
        color: var(--light-text);
        font-weight: 500;
        text-align: right;
    }

    .info-value.highlight {
        color: var(--primary-gold);
        font-weight: 600;
        font-size: 1.1rem;
    }

    .info-value.price {
        color: #28a745;
        font-weight: 700;
        font-size: 1.3rem;
    }

    /* Update Form */
    .update-form {
        background: var(--secondary-dark);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 30px;
        margin-top: 40px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        color: var(--light-text);
        margin-bottom: 8px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-select, .form-textarea {
        width: 100%;
        padding: 12px 15px;
        background: var(--primary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--light-text);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }

    .btn-submit {
        background: var(--primary-gold);
        color: #000;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
    }

    .btn-submit:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid var(--border-color);
    }

    .action-btn {
        flex: 1;
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .action-btn.print {
        background: rgba(111, 66, 193, 0.2);
        color: #6f42c1;
        border: 1px solid rgba(111, 66, 193, 0.3);
    }

    .action-btn.print:hover {
        background: rgba(111, 66, 193, 0.3);
        transform: translateY(-2px);
    }

    .action-btn.email {
        background: rgba(23, 162, 184, 0.2);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
    }

    .action-btn.email:hover {
        background: rgba(23, 162, 184, 0.3);
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .booking-banner {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-calendar-check"></i>
                Reservation Details
            </h1>
            <a href="reservations.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Reservations
            </a>
        </div>

        <!-- Error Message -->
        <?php if(isset($error)): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if(isset($_GET['updated'])): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Reservation status updated successfully!</span>
            <?php if(isset($_GET['status'])): ?>
            <br><small>New status: <strong><?= strtoupper($_GET['status']) ?></strong></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Booking Code Banner -->
        <div class="booking-banner">
            <div>
                <div class="booking-code"><?= $reservation['booking_code'] ?></div>
                <div class="booking-date">
                    Created: <?= date('d M Y H:i', strtotime($reservation['created_at'])) ?>
                    <?php 
                    // Tampilkan updated_at jika ada dan valid
                    if (isset($reservation['updated_at']) && 
                        !empty($reservation['updated_at']) && 
                        $reservation['updated_at'] != '0000-00-00 00:00:00' &&
                        strtotime($reservation['updated_at']) > strtotime($reservation['created_at'])) {
                        echo '<br>Updated: ' . date('d M Y H:i', strtotime($reservation['updated_at']));
                    }
                    ?>
                </div>
            </div>
            <div class="status-display status-<?= $reservation['status'] ?>" id="currentStatus">
                <i class="fas fa-circle" style="font-size: 0.7rem;"></i>
                <?= strtoupper($reservation['status']) ?>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <!-- Guest Information -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <h3>Guest Information</h3>
                </div>
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value highlight"><?= htmlspecialchars($reservation['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($reservation['email']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?= $reservation['phone'] ? htmlspecialchars($reservation['phone']) : 'Not provided' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= $reservation['user_id'] ?></span>
                </div>
            </div>

            <!-- Room Information -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-bed"></i>
                    <h3>Room Information</h3>
                </div>
                <div class="info-item">
                    <span class="info-label">Room Type</span>
                    <span class="info-value highlight"><?= $reservation['room_type'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Room Number</span>
                    <span class="info-value">#<?= $reservation['room_number'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Price per Night</span>
                    <span class="info-value">Rp <?= number_format($reservation['price_per_night']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Description</span>
                    <span class="info-value"><?= $reservation['description'] ? htmlspecialchars(substr($reservation['description'], 0, 50)) . '...' : 'No description' ?></span>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Booking Details</h3>
                </div>
                <div class="info-item">
                    <span class="info-label">Check-in</span>
                    <span class="info-value"><?= date('d M Y', strtotime($reservation['check_in'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Check-out</span>
                    <span class="info-value"><?= date('d M Y', strtotime($reservation['check_out'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Duration</span>
                    <span class="info-value highlight"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value" style="text-transform: capitalize;"><?= str_replace('_', ' ', $reservation['payment_method']) ?></span>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Payment Summary</h3>
                </div>
                <div class="info-item">
                    <span class="info-label">Room Rate</span>
                    <span class="info-value">Rp <?= number_format($reservation['price_per_night']) ?> × <?= $nights ?> nights</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Subtotal</span>
                    <span class="info-value">Rp <?= number_format($reservation['price_per_night'] * $nights) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tax & Fees</span>
                    <span class="info-value">Rp 0</span>
                </div>
                <div class="info-item" style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 10px;">
                    <span class="info-label">Total Amount</span>
                    <span class="info-value price">Rp <?= number_format($reservation['total_price']) ?></span>
                </div>
            </div>
        </div>

        <!-- Update Status Form -->
       <form method="POST" action="update_status.php">
            <div class="card-header">
                <i class="fas fa-edit"></i>
                <h3>Update Reservation Status</h3>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i>
                    Current Status: <span id="currentStatusText" style="color: var(--primary-gold);"><?= strtoupper($reservation['status']) ?></span>
                </label>
                <select name="status" class="form-select" required id="statusSelect">
                    <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="check_in" <?= $reservation['status'] == 'check_in' ? 'selected' : '' ?>>Check-in</option>
                    <option value="check_out" <?= $reservation['status'] == 'check_out' ? 'selected' : '' ?>>Check-out</option>
                    <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <input type="hidden" name="reservation_id" value="<?php echo $id; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-sticky-note"></i>
                    Admin Note (Optional)
                </label>
                <textarea name="admin_note" class="form-textarea" placeholder="Add a note about this status update..."></textarea>
            </div>

            <button type="submit" name="update" class="btn-submit" id="submitBtn">
                <i class="fas fa-save"></i> Update Status
            </button>
        </form>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="javascript:void(0)" class="action-btn print" onclick="printInvoice('<?= $reservation['booking_code'] ?>')">
                <i class="fas fa-print"></i> Print Invoice
            </a>
            <a href="mailto:<?= htmlspecialchars($reservation['email']) ?>?subject=Reservation%20Update%20-%20<?= urlencode($reservation['booking_code']) ?>" class="action-btn email">
                <i class="fas fa-envelope"></i> Send Email
            </a>
        </div>
    </div>

    <script>
    function printInvoice(bookingCode) {
        window.open(`print_invoice.php?code=${bookingCode}`, '_blank');
    }

    // Auto-refresh status badge when form changes
    document.getElementById('statusSelect').addEventListener('change', function() {
        const status = this.value;
        const statusText = status.toUpperCase();
        
        // Update current status text
        document.getElementById('currentStatusText').textContent = statusText;
        
        // Update button text
        document.getElementById('submitBtn').innerHTML = 
            `<i class="fas fa-save"></i> Update to ${statusText}`;
    });

 // Form submission confirmation
document.getElementById('statusForm').addEventListener('submit', function(e) {
    const currentStatus = '<?= $reservation['status'] ?>';
    const newStatus = document.getElementById('statusSelect').value;
    
    // Show loading
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;
    
    // Allow form to submit
    return true;
});

    // Auto-refresh page after 2 seconds if status was updated
    <?php if(isset($_GET['updated'])): ?>
    setTimeout(function() {
        window.location.href = window.location.href.replace(/&updated=1/, '');
    }, 2000);
    <?php endif; ?>
    </script>
</body>
</html>
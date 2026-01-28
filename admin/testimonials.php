<?php
session_start();
require '../config/db.php';

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter values
$status_filter = $_GET['status'] ?? 'all';
$featured_filter = $_GET['featured'] ?? 'all';
$search = $_GET['search'] ?? '';

// Process actions
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE testimonials SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Testimonial approved successfully!";
            } else {
                $_SESSION['error'] = "Failed to approve testimonial.";
            }
            break;
            
        case 'reject':
            $stmt = $conn->prepare("UPDATE testimonials SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Testimonial rejected successfully!";
            } else {
                $_SESSION['error'] = "Failed to reject testimonial.";
            }
            break;
            
        case 'feature':
            $stmt = $conn->prepare("UPDATE testimonials SET is_featured = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Testimonial marked as featured!";
            } else {
                $_SESSION['error'] = "Failed to feature testimonial.";
            }
            break;
            
        case 'unfeature':
            $stmt = $conn->prepare("UPDATE testimonials SET is_featured = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Testimonial removed from featured!";
            } else {
                $_SESSION['error'] = "Failed to unfeature testimonial.";
            }
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Testimonial deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete testimonial.";
            }
            break;
            
        case 'reply':
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_submit'])) {
                $testimonial_id = intval($_POST['testimonial_id']);
                $admin_reply = $_POST['admin_reply'];
                
                $stmt = $conn->prepare("UPDATE testimonials SET admin_reply = ? WHERE id = ?");
                $stmt->bind_param("si", $admin_reply, $testimonial_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Reply sent successfully!";
                } else {
                    $_SESSION['error'] = "Failed to send reply.";
                }
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header("Location: testimonials.php?status=$status_filter&featured=$featured_filter&search=" . urlencode($search));
    exit();
}

// Build query with filters
$query = "SELECT t.*, u.name as user_name, u.email, r.booking_code 
          FROM testimonials t 
          JOIN users u ON t.user_id = u.id 
          LEFT JOIN reservations r ON t.booking_id = r.id 
          WHERE 1=1";
    
$params = [];
$types = "";
    
if ($status_filter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
    
if ($featured_filter === 'featured') {
    $query .= " AND t.is_featured = 1";
} elseif ($featured_filter === 'not_featured') {
    $query .= " AND t.is_featured = 0";
}
    
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR t.comment LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}
    
$query .= " ORDER BY t.created_at DESC";
    
// Prepare and execute query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
    
// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured
    FROM testimonials";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - Admin Sere Heaven</title>
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
        --card-bg: rgba(255, 255, 255, 0.05);
        --hover-bg: rgba(255, 255, 255, 0.08);
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
        max-width: 1400px;
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
        font-size: 2rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-size: 1.5rem;
    }

    .header-actions {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--primary-gold);
        color: #000;
    }

    .btn-primary:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }

    .btn-secondary {
        background: var(--secondary-dark);
        color: var(--light-text);
        border: 1px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--primary-gold);
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--secondary-dark);
        border-radius: 15px;
        padding: 25px;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        text-align: center;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-gold);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.3rem;
    }

    .stat-total .stat-icon {
        background: rgba(88, 166, 255, 0.2);
        color: var(--accent-blue);
    }

    .stat-pending .stat-icon {
        background: rgba(255, 193, 7, 0.2);
        color: var(--pending);
    }

    .stat-approved .stat-icon {
        background: rgba(40, 167, 69, 0.2);
        color: var(--approved);
    }

    .stat-rejected .stat-icon {
        background: rgba(220, 53, 69, 0.2);
        color: var(--rejected);
    }

    .stat-featured .stat-icon {
        background: rgba(255, 215, 0, 0.2);
        color: var(--primary-gold);
    }

    .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stat-label {
        color: var(--gray-text);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Filter Bar */
    .filter-bar {
        background: var(--secondary-dark);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
    }

    .filter-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        color: var(--primary-gold);
        font-size: 1.1rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-label {
        color: var(--gray-text);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-select, .filter-input {
        padding: 12px 15px;
        background: var(--primary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--light-text);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .filter-select:focus, .filter-input:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }

    .btn-filter {
        padding: 12px 25px;
        background: var(--accent-blue);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        background: #0d6efd;
        transform: translateY(-2px);
    }

    .btn-reset {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid var(--border-color);
    }

    .btn-reset:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--light-text);
    }

    /* Testimonials Grid */
    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    @media (max-width: 768px) {
        .testimonials-grid {
            grid-template-columns: 1fr;
        }
    }

    .testimonial-card {
        background: var(--secondary-dark);
        border-radius: 15px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
    }

    .testimonial-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-gold);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
    }

    .testimonial-header {
        padding: 25px 25px 15px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .user-info {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
    }

    .user-details h3 {
        color: var(--light-text);
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    .user-details p {
        color: var(--gray-text);
        font-size: 0.85rem;
    }

    .booking-code {
        background: rgba(88, 166, 255, 0.1);
        color: var(--accent-blue);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .testimonial-body {
        padding: 20px 25px;
    }

    .rating {
        display: flex;
        gap: 5px;
        margin-bottom: 15px;
    }

    .star {
        color: #6c757d;
        font-size: 1.1rem;
    }

    .star.filled {
        color: var(--primary-gold);
    }

    .comment {
        color: var(--light-text);
        line-height: 1.7;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .admin-reply {
        background: rgba(255, 215, 0, 0.05);
        border-left: 3px solid var(--primary-gold);
        padding: 15px;
        border-radius: 0 8px 8px 0;
        margin-top: 20px;
    }

    .admin-reply-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        color: var(--primary-gold);
        font-size: 0.9rem;
    }

    .admin-reply-text {
        color: var(--light-text);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .testimonial-footer {
        padding: 15px 25px;
        background: rgba(0, 0, 0, 0.2);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .status-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.2);
        color: var(--pending);
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-approved {
        background: rgba(40, 167, 69, 0.2);
        color: var(--approved);
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .status-rejected {
        background: rgba(220, 53, 69, 0.2);
        color: var(--rejected);
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .featured-badge {
        background: rgba(255, 215, 0, 0.2);
        color: var(--primary-gold);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.05);
        color: var(--light-text);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .action-btn.approve {
        background: rgba(40, 167, 69, 0.1);
        color: var(--approved);
    }

    .action-btn.approve:hover {
        background: rgba(40, 167, 69, 0.2);
        border-color: var(--approved);
    }

    .action-btn.reject {
        background: rgba(220, 53, 69, 0.1);
        color: var(--rejected);
    }

    .action-btn.reject:hover {
        background: rgba(220, 53, 69, 0.2);
        border-color: var(--rejected);
    }

    .action-btn.feature {
        background: rgba(255, 215, 0, 0.1);
        color: var(--primary-gold);
    }

    .action-btn.feature:hover {
        background: rgba(255, 215, 0, 0.2);
        border-color: var(--primary-gold);
    }

    .action-btn.reply {
        background: rgba(88, 166, 255, 0.1);
        color: var(--accent-blue);
    }

    .action-btn.reply:hover {
        background: rgba(88, 166, 255, 0.2);
        border-color: var(--accent-blue);
    }

    .action-btn.delete {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger);
    }

    .action-btn.delete:hover {
        background: rgba(220, 53, 69, 0.2);
        border-color: var(--danger);
    }

    /* Reply Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: var(--secondary-dark);
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .modal-header {
        padding: 25px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        color: var(--primary-gold);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-close {
        background: transparent;
        border: none;
        color: var(--gray-text);
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .modal-close:hover {
        color: var(--light-text);
    }

    .modal-body {
        padding: 25px;
    }

    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--secondary-dark);
        border-radius: 15px;
        border: 2px dashed var(--border-color);
        margin: 40px 0;
    }

    .empty-icon {
        font-size: 4rem;
        color: var(--gray-text);
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: var(--light-text);
        margin-bottom: 10px;
    }

    .empty-state p {
        color: var(--gray-text);
        max-width: 500px;
        margin: 0 auto 25px;
    }

    /* Messages */
    .alert {
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

    .alert-success {
        background: rgba(40, 167, 69, 0.2);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: var(--approved);
    }

    .alert-error {
        background: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: var(--danger);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }

    .page-link {
        padding: 10px 18px;
        background: var(--secondary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--light-text);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .page-link:hover {
        background: rgba(255, 215, 0, 0.1);
        border-color: var(--primary-gold);
    }

    .page-link.active {
        background: var(--primary-gold);
        color: #000;
        border-color: var(--primary-gold);
    }

    /* Form Styles for Modal */
    .form-label {
        color: var(--gray-text);
        font-size: 0.9rem;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-textarea {
        width: 100%;
        padding: 12px 15px;
        background: var(--primary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--light-text);
        font-size: 1rem;
        resize: vertical;
        min-height: 120px;
    }

    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="header-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <h1>Testimonials Management</h1>
                    <p style="color: var(--gray-text); font-size: 0.9rem; margin-top: 5px;">
                        Manage guest reviews and testimonials
                    </p>
                </div>
            </div>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

       <!-- Statistics Cards -->
       <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="stat-number"><?= isset($stats['total']) ? $stats['total'] : 0 ?></div>
                <div class="stat-label">Total Testimonials</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= isset($stats['pending']) ? $stats['pending'] : 0 ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= isset($stats['approved']) ? $stats['approved'] : 0 ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card stat-rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?= isset($stats['rejected']) ? $stats['rejected'] : 0 ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card stat-featured">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?= isset($stats['featured']) ? $stats['featured'] : 0 ?></div>
                <div class="stat-label">Featured</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                <span>Filter Testimonials</span>
            </div>
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-filter-circle"></i>
                            Status
                        </label>
                        <select name="status" class="filter-select">
                            <option value="all" <?= $status_filter == 'all' || !$status_filter ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-star"></i>
                            Featured
                        </label>
                        <select name="featured" class="filter-select">
                            <option value="all" <?= $featured_filter == 'all' || !$featured_filter ? 'selected' : '' ?>>All</option>
                            <option value="featured" <?= $featured_filter == 'featured' ? 'selected' : '' ?>>Featured Only</option>
                            <option value="not_featured" <?= $featured_filter == 'not_featured' ? 'selected' : '' ?>>Not Featured</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search"></i>
                            Search
                        </label>
                        <input type="text" name="search" class="filter-input" placeholder="Search by name, email, or comment..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="testimonials.php" class="btn btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Testimonials Grid -->
        <div class="testimonials-grid">
            <?php if($result->num_rows > 0): ?>
                <?php while($testimonial = $result->fetch_assoc()): ?>
                    <div class="testimonial-card">
                        <!-- Header -->
                        <div class="testimonial-header">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($testimonial['user_name'], 0, 1)) ?>
                                </div>
                                <div class="user-details">
                                    <h3><?= htmlspecialchars($testimonial['user_name']) ?></h3>
                                    <p><?= htmlspecialchars($testimonial['email']) ?></p>
                                </div>
                            </div>
                            <?php if($testimonial['booking_code']): ?>
                                <div class="booking-code"><?= htmlspecialchars($testimonial['booking_code']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="testimonial-body">
                            <!-- Rating -->
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star star <?= $i <= $testimonial['rating'] ? 'filled' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>

                            <!-- Comment -->
                            <div class="comment">
                                <?= nl2br(htmlspecialchars($testimonial['comment'])) ?>
                            </div>

                            <!-- Admin Reply -->
                            <?php if($testimonial['admin_reply']): ?>
                                <div class="admin-reply">
                                    <div class="admin-reply-header">
                                        <i class="fas fa-reply"></i>
                                        <span>Admin Response</span>
                                    </div>
                                    <div class="admin-reply-text">
                                        <?= nl2br(htmlspecialchars($testimonial['admin_reply'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer -->
                        <div class="testimonial-footer">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="status-badge status-<?= $testimonial['status'] ?>">
                                    <?= ucfirst($testimonial['status']) ?>
                                </div>
                                <?php if($testimonial['is_featured']): ?>
                                    <div class="featured-badge">
                                        <i class="fas fa-star"></i>
                                        Featured
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="action-buttons">
                                <?php if($testimonial['status'] != 'approved'): ?>
                                    <a href="testimonials.php?action=approve&id=<?= $testimonial['id'] ?>&status=<?= $status_filter ?>&featured=<?= $featured_filter ?>&search=<?= urlencode($search) ?>" 
                                       class="action-btn approve" 
                                       title="Approve"
                                       onclick="return confirm('Approve this testimonial?')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if($testimonial['status'] != 'rejected'): ?>
                                    <a href="testimonials.php?action=reject&id=<?= $testimonial['id'] ?>&status=<?= $status_filter ?>&featured=<?= $featured_filter ?>&search=<?= urlencode($search) ?>" 
                                       class="action-btn reject"
                                       title="Reject"
                                       onclick="return confirm('Reject this testimonial?')">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if(!$testimonial['is_featured']): ?>
                                    <a href="testimonials.php?action=feature&id=<?= $testimonial['id'] ?>&status=<?= $status_filter ?>&featured=<?= $featured_filter ?>&search=<?= urlencode($search) ?>" 
                                       class="action-btn feature"
                                       title="Mark as Featured"
                                       onclick="return confirm('Mark as featured?')">
                                        <i class="fas fa-star"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="testimonials.php?action=unfeature&id=<?= $testimonial['id'] ?>&status=<?= $status_filter ?>&featured=<?= $featured_filter ?>&search=<?= urlencode($search) ?>" 
                                       class="action-btn feature"
                                       title="Remove Featured"
                                       onclick="return confirm('Remove from featured?')">
                                        <i class="fas fa-star"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="javascript:void(0)" 
                                   class="action-btn reply"
                                   title="Reply"
                                   onclick="openReplyModal(<?= $testimonial['id'] ?>, '<?= addslashes(htmlspecialchars($testimonial['user_name'])) ?>')">
                                    <i class="fas fa-reply"></i>
                                </a>
                                <a href="testimonials.php?action=delete&id=<?= $testimonial['id'] ?>&status=<?= $status_filter ?>&featured=<?= $featured_filter ?>&search=<?= urlencode($search) ?>" 
                                   class="action-btn delete"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this testimonial?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-icon">
                        <i class="fas fa-comment-slash"></i>
                    </div>
                    <h3>No testimonials found</h3>
                    <p>There are no testimonials matching your current filters.</p>
                    <a href="testimonials.php" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reply Modal -->
        <div class="modal" id="replyModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>
                        <i class="fas fa-reply"></i>
                        Reply to Testimonial
                    </h3>
                    <button class="modal-close" onclick="closeReplyModal()">&times;</button>
                </div>
                <form method="POST" action="" id="replyForm">
                    <input type="hidden" name="testimonial_id" id="replyTestimonialId">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="hidden" name="featured" value="<?= htmlspecialchars($featured_filter) ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <div class="modal-body">
                        <div style="margin-bottom: 20px; padding: 15px; background: rgba(255,215,0,0.05); border-radius: 8px;">
                            <p style="color: var(--gray-text); margin-bottom: 10px;">
                                Replying to: <strong id="replyUserName"></strong>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-comment-alt"></i>
                                Your Response
                            </label>
                            <textarea name="admin_reply" class="form-textarea" rows="6" placeholder="Type your response here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">
                            Cancel
                        </button>
                        <button type="submit" name="reply_submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Reply Modal
    function openReplyModal(id, userName) {
        document.getElementById('replyModal').classList.add('show');
        document.getElementById('replyTestimonialId').value = id;
        document.getElementById('replyUserName').textContent = userName;
        document.getElementById('replyForm').action = `testimonials.php?action=reply&id=${id}`;
        document.body.style.overflow = 'hidden';
    }

    function closeReplyModal() {
        document.getElementById('replyModal').classList.remove('show');
        document.getElementById('replyForm').reset();
        document.body.style.overflow = '';
    }

    // Close modal when clicking outside
    document.getElementById('replyModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReplyModal();
        }
    });

    // Auto-close success messages after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // Status color coding
    document.addEventListener('DOMContentLoaded', function() {
        const statusBadges = document.querySelectorAll('.status-badge');
        statusBadges.forEach(badge => {
            const status = badge.textContent.trim().toLowerCase();
            badge.className = `status-badge status-${status}`;
        });
    });

    // Star rating hover effect
    const testimonials = document.querySelectorAll('.testimonial-card');
    testimonials.forEach(card => {
        const rating = card.querySelector('.rating');
        if (rating) {
            const stars = rating.querySelectorAll('.star');
            
            stars.forEach((star, index) => {
                star.addEventListener('mouseenter', () => {
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.style.transform = 'scale(1.2)';
                            s.style.transition = 'transform 0.2s ease';
                        }
                    });
                });
                
                star.addEventListener('mouseleave', () => {
                    stars.forEach(s => {
                        s.style.transform = 'scale(1)';
                    });
                });
            });
        }
    });

    // Filter form loading
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-filter');
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                btn.disabled = true;
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }, 3000);
            }
        });
    }
    </script>
</body>
</html>
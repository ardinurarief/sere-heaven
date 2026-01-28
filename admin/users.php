<?php
require 'auth.php';
require '../config/db.php';

// Pagination settings
$limit = 10; // Users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = '';
$where = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where = "WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
}

// Get total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where";
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $limit);

// Get users with pagination
$query = "SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.phone,
            u.created_at,
            COUNT(DISTINCT r.id) as total_bookings,
            COALESCE(SUM(r.total_price), 0) as total_spent,
            COUNT(DISTINCT t.id) as total_testimonials
          FROM users u
          LEFT JOIN reservations r ON u.id = r.user_id
          LEFT JOIN testimonials t ON u.id = t.user_id
          $where
          GROUP BY u.id
          ORDER BY u.created_at DESC
          LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* === USERS DARK THEME - SAME AS DASHBOARD === */
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
        min-height: 100vh;
    }

    .admin-container {
        display: flex;
        min-height: 100vh;
    }

    /* === SIDEBAR DARK THEME (SAME AS DASHBOARD) === */
    .admin-sidebar {
        width: 280px;
        background: var(--secondary-dark);
        padding: 25px 0;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        border-right: 1px solid var(--border-color);
        z-index: 100;
    }

    .sidebar-header {
        padding: 0 25px 25px;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 25px;
    }

    .sidebar-header h2 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.4rem;
        color: var(--primary-gold);
    }

    .nav-links {
        list-style: none;
        padding: 0 15px;
    }

    .nav-links li {
        margin-bottom: 5px;
    }

    .nav-links a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 14px 20px;
        color: var(--gray-text);
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .nav-links a:hover {
        background: var(--hover-bg);
        color: var(--light-text);
    }

    .nav-links a.active {
        background: rgba(255, 215, 0, 0.15);
        color: var(--primary-gold);
        border-left: 3px solid var(--primary-gold);
    }

    .nav-links a i {
        width: 20px;
        text-align: center;
        font-size: 1.1rem;
    }

    .logout-btn {
        margin-top: 30px;
        padding: 20px 25px;
        border-top: 1px solid var(--border-color);
    }

    .logout-btn a {
        color: var(--danger) !important;
    }

    /* === MAIN CONTENT (SAME AS DASHBOARD) === */
    .admin-main {
        flex: 1;
        margin-left: 280px;
        padding: 30px;
        background: linear-gradient(135deg, rgba(255,215,0,0.03) 0%, rgba(88,166,255,0.03) 100%);
        min-height: 100vh;
    }

    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 25px;
        border-bottom: 1px solid var(--border-color);
    }

    .page-header h1 {
        color: var(--primary-gold);
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    /* === STATS CARDS DARK THEME === */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }

    .stat-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-gold);
        box-shadow: 0 10px 25px rgba(255, 215, 0, 0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, var(--primary-gold), transparent);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .stat-icon.users { background: rgba(88, 166, 255, 0.1); color: var(--accent-blue); }
    .stat-icon.guests { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .stat-icon.reviews { background: rgba(255, 215, 0, 0.1); color: var(--primary-gold); }
    .stat-icon.new-users { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--light-text);
        display: block;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-label {
        color: var(--gray-text);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* === SEARCH SECTION DARK THEME === */
    .search-section {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid var(--border-color);
        margin-bottom: 30px;
    }

    .search-form {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .search-input {
        flex: 1;
        padding: 14px 16px;
        background: var(--primary-dark);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: var(--light-text);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .search-btn, .reset-btn {
        padding: 14px 25px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .search-btn {
        background: var(--primary-gold);
        color: #000;
    }

    .search-btn:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }

    .reset-btn {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid var(--border-color);
    }

    .reset-btn:hover {
        background: var(--hover-bg);
        color: var(--light-text);
    }

    /* === USERS TABLE DARK THEME === */
    .users-table-container {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .table-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-header h3 {
        color: var(--light-text);
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .export-btn {
        padding: 12px 25px;
        background: var(--primary-gold);
        color: #000;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .export-btn:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: rgba(255, 215, 0, 0.05);
    }

    th {
        padding: 15px;
        text-align: left;
        color: var(--gray-text);
        font-weight: 600;
        border-bottom: 2px solid var(--border-color);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid var(--border-color);
        color: var(--light-text);
    }

    tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-weight: bold;
        font-size: 1.2rem;
        overflow: hidden;
        flex-shrink: 0;
    }

    .user-name {
        font-weight: 600;
        color: var(--light-text);
    }

    .user-email {
        color: var(--gray-text);
        font-size: 0.9rem;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-success {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .badge-warning {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .badge-info {
        background: rgba(23, 162, 184, 0.2);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
    }

    .action-btns {
        display: flex;
        gap: 8px;
    }

    .btn-view, .btn-bookings, .btn-testimonials {
        padding: 8px 15px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .btn-view {
        background: rgba(23, 162, 184, 0.2);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
    }

    .btn-view:hover {
        background: rgba(23, 162, 184, 0.3);
        transform: translateY(-2px);
    }

    .btn-bookings {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .btn-bookings:hover {
        background: rgba(40, 167, 69, 0.3);
        transform: translateY(-2px);
    }

    .btn-testimonials {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .btn-testimonials:hover {
        background: rgba(255, 193, 7, 0.3);
        transform: translateY(-2px);
    }

    /* === PAGINATION DARK THEME === */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }

    .page-link, .current-page {
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .page-link {
        background: var(--secondary-dark);
        color: var(--primary-gold);
        border: 1px solid var(--border-color);
    }

    .page-link:hover {
        background: rgba(255, 215, 0, 0.1);
        border-color: var(--primary-gold);
        transform: translateY(-2px);
    }

    .current-page {
        background: var(--primary-gold);
        color: #000;
    }

    /* === EMPTY STATE DARK THEME === */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-text);
    }

    .empty-icon {
        font-size: 4rem;
        color: var(--border-color);
        margin-bottom: 20px;
    }

    /* === FOOTER DARK THEME === */
    .admin-footer {
        text-align: center;
        padding: 25px;
        color: var(--gray-text);
        font-size: 0.9rem;
        border-top: 1px solid var(--border-color);
        margin-top: 30px;
    }

    /* === MODAL DARK THEME === */
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
        padding: 20px;
    }

    .modal-content {
        background: var(--secondary-dark);
        border-radius: 15px;
        border: 1px solid var(--border-color);
        max-width: 500px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        padding: 20px;
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

    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--gray-text);
        transition: color 0.3s ease;
    }

    .close-modal:hover {
        color: var(--light-text);
    }

    .modal-body {
        padding: 20px;
    }

    .user-detail-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .detail-label {
        color: var(--gray-text);
        font-size: 0.9rem;
        margin-bottom: 5px;
        display: block;
    }

    .detail-value {
        color: var(--light-text);
        font-weight: 500;
        font-size: 1rem;
    }

    /* === RESPONSIVE === */
    @media (max-width: 1024px) {
        .admin-sidebar {
            width: 80px;
            padding: 20px 0;
        }
        
        .admin-sidebar .nav-text {
            display: none;
        }
        
        .admin-main {
            margin-left: 80px;
            padding: 20px;
        }
        
        .sidebar-header {
            padding: 0 15px 15px;
        }
        
        .sidebar-header h2 span {
            display: none;
        }
        
        .nav-links {
            padding: 0 10px;
        }
        
        .nav-links a {
            padding: 12px;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .admin-main {
            padding: 15px;
        }
        
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .search-form {
            flex-direction: column;
        }
        
        .search-input, .search-btn, .reset-btn {
            width: 100%;
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .action-btns {
            flex-direction: column;
        }
        
        .table-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
    
    @media (max-width: 576px) {
        .stats-cards {
            grid-template-columns: 1fr;
        }
        
        .stat-value {
            font-size: 2rem;
        }
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>
                    <i class="fas fa-crown"></i>
                    <span class="nav-text">Sere Heaven</span>
                </h2>
            </div>
            
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> <span class="nav-text">Kamar</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> <span class="nav-text">Reservasi</span></a></li>
                <li><a href="testimonials.php"><i class="fas fa-star"></i> <span class="nav-text">Testimonials</span></a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a></li>
                <li class="logout-btn"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <div class="header-actions">
                    <button class="export-btn" onclick="exportUsers()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="stat-value"><?= $total_users ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon guests">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span class="stat-value">
                        <?php 
                        $active_bookings = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(DISTINCT user_id) as active FROM reservations WHERE status IN ('pending','confirmed','check_in')"));
                        echo $active_bookings['active'];
                        ?>
                    </span>
                    <span class="stat-label">Active Guests</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon reviews">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="stat-value">
                        <?php 
                        $testimonial_users = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(DISTINCT user_id) as total FROM testimonials"));
                        echo $testimonial_users['total'];
                        ?>
                    </span>
                    <span class="stat-label">Reviewed Users</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon new-users">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span class="stat-value">
                        <?php 
                        $today_users = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()"));
                        echo $today_users['total'];
                        ?>
                    </span>
                    <span class="stat-label">New Today</span>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <form method="get" class="search-form">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search by name or email..."
                           value="<?= htmlspecialchars($search) ?>">
                    
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <?php if($search): ?>
                    <a href="users.php" class="reset-btn">
                        <i class="fas fa-times"></i> Reset
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users Table -->
            <div class="users-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Users List</h3>
                    <div class="table-info" style="color: var(--gray-text);">
                        Showing <?= ($offset + 1) ?> - <?= min($offset + $limit, $total_users) ?> of <?= $total_users ?> users
                    </div>
                </div>
                
                <?php if(mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Bookings</th>
                            <th>Spent</th>
                            <th>Testimonials</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($result)): 
                            $initial = strtoupper(substr($user['name'], 0, 1));
                            $joined_date = date('d M Y', strtotime($user['created_at']));
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="user-avatar"><?= $initial ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                        <div class="user-id" style="color: var(--gray-text); font-size: 0.85rem;">ID: <?= $user['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                <div style="font-size: 0.9rem; color: var(--gray-text); margin-top: 5px;">
                                    <?= $user['phone'] ? htmlspecialchars($user['phone']) : 'No phone' ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $user['total_bookings'] ?> bookings</span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #28a745;">
                                    Rp <?= number_format($user['total_spent']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if($user['total_testimonials'] > 0): ?>
                                <span class="badge badge-success"><?= $user['total_testimonials'] ?> reviews</span>
                                <?php else: ?>
                                <span class="badge badge-warning">No reviews</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="color: var(--light-text);"><?= $joined_date ?></div>
                                <div style="font-size: 0.85rem; color: var(--gray-text);">
                                    <?= time_ago($user['created_at']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-view" onclick="viewUser(<?= $user['id'] ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="reservations.php?user_id=<?= $user['id'] ?>" class="btn-bookings">
                                        <i class="fas fa-calendar"></i> Bookings
                                    </a>
                                    <?php if($user['total_testimonials'] > 0): ?>
                                    <a href="testimonials.php?user_id=<?= $user['id'] ?>" class="btn-testimonials">
                                        <i class="fas fa-star"></i> Reviews
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3 style="color: var(--light-text); margin-bottom: 10px;">No Users Found</h3>
                    <p><?= $search ? 'Try a different search term' : 'No users registered yet' ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                    <span class="current-page"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="admin-footer">
                <p>Sere Heaven Hotel Management System &copy; <?= date('Y') ?></p>
                <p style="color: var(--gray-text); margin-top: 5px;">
                    Total Users: <?= $total_users ?> | Active Today: <?= $today_users['total'] ?>
                </p>
            </div>
        </main>
    </div>

    <!-- User Detail Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user"></i> User Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="userDetailContent">
                <!-- Content will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <script>
    // View User Details
    function viewUser(userId) {
        // Show loading
        document.getElementById('userDetailContent').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-gold);"></i>
                <p style="margin-top: 15px; color: var(--gray-text);">Loading user details...</p>
            </div>
        `;
        
        // Show modal
        document.getElementById('userModal').style.display = 'flex';
        
        // Fetch user details
        fetch(`user_detail.php?id=${userId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('userDetailContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('userDetailContent').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                        <p style="margin-top: 15px;">Error loading user details</p>
                    </div>
                `;
            });
    }

    // Close Modal
    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('userModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Export Users (placeholder)
    function exportUsers() {
        alert('Export feature will be implemented soon!');
        // In production: window.location.href = 'export_users.php?format=csv';
    }

    // Time ago function for PHP
    <?php
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
    </script>
</body>
</html>
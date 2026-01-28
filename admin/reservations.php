<?php
require 'auth.php';
require '../config/db.php';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Status filter
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$where = $status_filter ? "WHERE r.status = '$status_filter'" : '';

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
if ($search) {
    $where .= $where ? " AND " : "WHERE ";
    $where .= "(u.name LIKE '%$search%' OR r.booking_code LIKE '%$search%' OR rm.room_number LIKE '%$search%')";
}

// Count total
$count_query = "SELECT COUNT(*) as total 
                FROM reservations r
                JOIN users u ON r.user_id = u.id
                JOIN rooms rm ON r.room_id = rm.id
                $where";
$count_result = mysqli_query($conn, $count_query);
$total_reservations = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_reservations / $limit);

// Get reservations with pagination
$query = "
    SELECT r.*, u.name, u.email, u.phone, rm.room_number, rm.room_type, rm.price_per_night
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    $where
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

// Get stats for cards
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'check_in' THEN 1 ELSE 0 END) as check_in,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(total_price) as total_revenue
    FROM reservations";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* === RESERVATIONS DARK THEME - SAME AS DASHBOARD === */
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

    /* Stats Cards (SAME STYLE AS DASHBOARD) */
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

    .stat-icon.total { background: rgba(88, 166, 255, 0.1); color: var(--accent-blue); }
    .stat-icon.confirmed { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .stat-icon.checkin { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
    .stat-icon.revenue { background: rgba(255, 215, 0, 0.1); color: var(--primary-gold); }

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

    /* Filters */
    .filters-section {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid var(--border-color);
        margin-bottom: 30px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .filter-label {
        color: var(--light-text);
        font-weight: 500;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-select, .search-input {
        padding: 12px 15px;
        background: var(--primary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--light-text);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .filter-select:focus, .search-input:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .filter-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ffd700' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 14px;
    }

    .filter-actions {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .btn-primary, .btn-secondary {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
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
        background: transparent;
        color: var(--gray-text);
        border: 1px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--hover-bg);
        color: var(--light-text);
    }

    .export-btn {
        padding: 12px 25px;
        background: var(--primary-gold);
        color: #000;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .export-btn:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
    }

    /* Reservations Table */
    .table-container {
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

    .table-actions {
        display: flex;
        gap: 10px;
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

    /* Status Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
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

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 8px;
    }

    .btn-view, .btn-edit, .btn-print {
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
        text-decoration: none;
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

    .btn-edit {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .btn-edit:hover {
        background: rgba(255, 193, 7, 0.3);
        transform: translateY(-2px);
    }

    .btn-print {
        background: rgba(111, 66, 193, 0.2);
        color: #6f42c1;
        border: 1px solid rgba(111, 66, 193, 0.3);
    }

    .btn-print:hover {
        background: rgba(111, 66, 193, 0.3);
        transform: translateY(-2px);
    }

    /* Pagination */
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

    /* Empty State */
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

    /* Footer */
    .admin-footer {
        text-align: center;
        padding: 25px;
        color: var(--gray-text);
        font-size: 0.9rem;
        border-top: 1px solid var(--border-color);
        margin-top: 30px;
    }

    /* Responsive */
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
        
        .filter-grid {
            grid-template-columns: 1fr;
        }
        
        .table-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .action-btns {
            flex-direction: column;
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
        
        .filter-actions {
            flex-direction: column;
        }
        
        .btn-primary, .btn-secondary {
            width: 100%;
            justify-content: center;
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
                <li><a href="reservations.php" class="active"><i class="fas fa-calendar-check"></i> <span class="nav-text">Reservasi</span></a></li>
                <li><a href="testimonials.php"><i class="fas fa-star"></i> <span class="nav-text">Testimonials</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a></li>
                <li class="logout-btn"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-calendar-check"></i> Reservasi</h1>
                <div class="header-actions">
                    <button class="export-btn" onclick="exportReservations()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
                    <span class="stat-label">Total Reservasi</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon confirmed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="stat-value"><?= $stats['confirmed'] ?? 0 ?></span>
                    <span class="stat-label">Confirmed</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon checkin">
                        <i class="fas fa-bed"></i>
                    </div>
                    <span class="stat-value"><?= $stats['check_in'] ?? 0 ?></span>
                    <span class="stat-label">Check-in</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <span class="stat-value">Rp <?= number_format($stats['total_revenue'] ?? 0) ?></span>
                    <span class="stat-label">Total Revenue</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="get" class="filter-form">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-search"></i> Search
                            </label>
                            <input type="text" 
                                   name="search" 
                                   class="search-input" 
                                   placeholder="Search by guest, booking code, or room..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-filter"></i> Status Filter
                            </label>
                            <select name="status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="check_in" <?= $status_filter == 'check_in' ? 'selected' : '' ?>>Check-in</option>
                                <option value="check_out" <?= $status_filter == 'check_out' ? 'selected' : '' ?>>Check-out</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        
                        <?php if($search || $status_filter): ?>
                        <a href="reservations.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Reservations Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Daftar Reservasi</h3>
                    <div style="color: var(--gray-text);">
                        Showing <?= ($offset + 1) ?> - <?= min($offset + $limit, $total_reservations) ?> of <?= $total_reservations ?> reservations
                    </div>
                </div>
                
                <?php if(mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking Code</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check-in/out</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($reservation = mysqli_fetch_assoc($result)): 
                            $status_class = 'status-' . $reservation['status'];
                            $nights = (strtotime($reservation['check_out']) - strtotime($reservation['check_in'])) / (60 * 60 * 24);
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--primary-gold);">
                                    <?= $reservation['booking_code'] ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--gray-text);">
                                    <?= date('d M Y', strtotime($reservation['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($reservation['name']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--gray-text);">
                                    <?= htmlspecialchars($reservation['email']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= $reservation['room_type'] ?></div>
                                <div style="font-size: 0.85rem; color: var(--gray-text);">
                                    Room #<?= $reservation['room_number'] ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= date('d M', strtotime($reservation['check_in'])) ?></div>
                                <div style="font-size: 0.85rem; color: var(--gray-text);">
                                    → <?= date('d M Y', strtotime($reservation['check_out'])) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--primary-gold); margin-top: 3px;">
                                    <?= $nights ?> night<?= $nights > 1 ? 's' : '' ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #28a745;">
                                    Rp <?= number_format($reservation['total_price']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--gray-text);">
                                    Rp <?= number_format($reservation['price_per_night']) ?>/night
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= strtoupper($reservation['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="reservation_detail.php?id=<?= $reservation['id'] ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="reservation_detail.php?id=<?= $reservation['id'] ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn-print" onclick="printInvoice('<?= $reservation['booking_code'] ?>')">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 style="color: var(--light-text); margin-bottom: 10px;">No Reservations Found</h3>
                    <p><?= $search || $status_filter ? 'Try changing your filters' : 'No reservations yet' ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                    <span class="current-page"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?page=<?= $i ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page+1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="admin-footer">
                <p>Sere Heaven Hotel Management System &copy; <?= date('Y') ?></p>
                <p style="color: var(--gray-text); margin-top: 5px;">
                    Total Reservations: <?= $total_reservations ?> | Revenue: Rp <?= number_format($stats['total_revenue'] ?? 0) ?>
                </p>
            </div>
        </main>
    </div>

    <script>
    function exportReservations() {
        alert('Export feature will be implemented soon!');
    }

    function printInvoice(bookingCode) {
        window.open(`print_invoice.php?code=${bookingCode}`, '_blank');
    }
    
    // Auto-refresh page every 30 seconds for real-time updates
    setTimeout(function() {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 30000);
    </script>
</body>
</html>
<?php
require 'auth.php';
require '../config/db.php';

// Get today's date for filtering
$today = date('Y-m-d');
$current_month = date('Y-m');

// Dashboard Statistics
$stats = [];

// 1. Total Revenue
$revenue_query = "SELECT COALESCE(SUM(total_price), 0) as total FROM reservations 
                  WHERE status NOT IN ('cancelled')";
$revenue_result = mysqli_query($conn, $revenue_query);
$stats['total_revenue'] = mysqli_fetch_assoc($revenue_result)['total'];

// 2. Today's Bookings
$today_bookings_query = "SELECT COUNT(*) as total FROM reservations 
                         WHERE DATE(created_at) = '$today' 
                         AND status NOT IN ('cancelled')";
$today_bookings_result = mysqli_query($conn, $today_bookings_query);
$stats['today_bookings'] = mysqli_fetch_assoc($today_bookings_result)['total'];

// 3. Total Rooms
$rooms_query = "SELECT COUNT(*) as total FROM rooms WHERE status = 'available'";
$rooms_result = mysqli_query($conn, $rooms_query);
$stats['total_rooms'] = mysqli_fetch_assoc($rooms_result)['total'];

// 4. Occupancy Rate
$occupied_query = "SELECT COUNT(DISTINCT room_id) as occupied FROM reservations 
                   WHERE '$today' BETWEEN check_in AND check_out 
                   AND status IN ('confirmed', 'check_in')";
$occupied_result = mysqli_query($conn, $occupied_query);
$occupied = mysqli_fetch_assoc($occupied_result)['occupied'];
$stats['occupancy_rate'] = $stats['total_rooms'] > 0 ? round(($occupied / $stats['total_rooms']) * 100, 1) : 0;

// 5. Pending Testimonials
$pending_testimonials_query = "SELECT COUNT(*) as total FROM testimonials WHERE status = 'pending'";
$pending_testimonials_result = mysqli_query($conn, $pending_testimonials_query);
$stats['pending_testimonials'] = mysqli_fetch_assoc($pending_testimonials_result)['total'];

// 6. Total Users
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = mysqli_query($conn, $users_query);
$stats['total_users'] = mysqli_fetch_assoc($users_result)['total'];

// 7. Today's Revenue
$today_revenue_query = "SELECT COALESCE(SUM(total_price), 0) as total FROM reservations 
                        WHERE DATE(created_at) = '$today' 
                        AND status NOT IN ('cancelled')";
$today_revenue_result = mysqli_query($conn, $today_revenue_query);
$stats['today_revenue'] = mysqli_fetch_assoc($today_revenue_result)['total'];

// 8. New Users Today
$new_users_query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = '$today'";
$new_users_result = mysqli_query($conn, $new_users_query);
$stats['new_users'] = mysqli_fetch_assoc($new_users_result)['total'];

// 9. Monthly Revenue (for chart)
$monthly_revenue_query = "SELECT 
                            DATE_FORMAT(created_at, '%b') as month,
                            COALESCE(SUM(total_price), 0) as revenue
                          FROM reservations 
                          WHERE status NOT IN ('cancelled')
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
                          ORDER BY MIN(created_at) ASC
                          LIMIT 6";
$monthly_revenue_result = mysqli_query($conn, $monthly_revenue_query);
$monthly_revenue = [];
while($row = mysqli_fetch_assoc($monthly_revenue_result)) {
    $monthly_revenue[] = $row;
}

// 10. Recent Bookings
$recent_bookings_query = "SELECT 
                            r.*, 
                            u.name as user_name,
                            rm.room_number,
                            rm.room_type
                          FROM reservations r
                          JOIN users u ON r.user_id = u.id
                          JOIN rooms rm ON r.room_id = rm.id
                          ORDER BY r.created_at DESC 
                          LIMIT 6";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);

// 11. Room Type Distribution
$room_distribution_query = "SELECT 
                              room_type,
                              COUNT(*) as count
                            FROM rooms 
                            WHERE status = 'available'
                            GROUP BY room_type";
$room_distribution_result = mysqli_query($conn, $room_distribution_query);
$room_distribution = [];
while($row = mysqli_fetch_assoc($room_distribution_result)) {
    $room_distribution[] = $row;
}

// 12. Get top performing rooms
$top_rooms_query = "SELECT 
                      r.room_number,
                      r.room_type,
                      COUNT(res.id) as booking_count,
                      COALESCE(SUM(res.total_price), 0) as total_revenue
                    FROM rooms r
                    LEFT JOIN reservations res ON r.id = res.room_id 
                      AND res.status NOT IN ('cancelled')
                      AND res.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY r.id
                    ORDER BY booking_count DESC, total_revenue DESC
                    LIMIT 5";
$top_rooms_result = mysqli_query($conn, $top_rooms_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* === DASHBOARD REDESIGN - SAME THEME AS ROOMS === */
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
    
    /* Sidebar */
    .admin-sidebar {
        width: 280px;
        background: var(--secondary-dark);
        padding: 25px 0;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        border-right: 1px solid #30363d;
        z-index: 100;
    }
    
    .sidebar-header {
        padding: 0 25px 25px;
        border-bottom: 1px solid #30363d;
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
        border-top: 1px solid #30363d;
    }
    
    .logout-btn a {
        color: var(--danger) !important;
    }
    
    /* Main Content */
    .admin-main {
        flex: 1;
        margin-left: 280px;
        padding: 30px;
        background: linear-gradient(135deg, rgba(255,215,0,0.03) 0%, rgba(88,166,255,0.03) 100%);
        min-height: 100vh;
    }
    
    /* Welcome Header */
    .welcome-header {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 30px;
        border-radius: 15px;
        border: 1px solid #30363d;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(88,166,255,0.1) 100%);
        opacity: 0.5;
    }
    
    .welcome-text {
        position: relative;
        z-index: 1;
    }
    
    .welcome-text h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        color: var(--primary-gold);
    }
    
    .welcome-text p {
        color: var(--gray-text);
        font-size: 1rem;
    }
    
    .date-time {
        text-align: right;
        position: relative;
        z-index: 1;
    }
    
    .current-date {
        color: var(--light-text);
        font-size: 1.1rem;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .current-time {
        color: var(--primary-gold);
        font-size: 2.2rem;
        font-weight: 700;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #30363d;
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
    
    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    
    .stat-icon.revenue { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .stat-icon.bookings { background: rgba(0, 123, 255, 0.1); color: var(--accent-blue); }
    .stat-icon.rooms { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
    .stat-icon.occupancy { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
    .stat-icon.testimonials { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
    .stat-icon.users { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
    .stat-icon.new-users { background: rgba(255, 215, 0, 0.1); color: var(--primary-gold); }
    
    .stat-trend {
        font-size: 0.85rem;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .trend-up { background: rgba(40, 167, 69, 0.15); color: var(--success); }
    .trend-down { background: rgba(220, 53, 69, 0.15); color: var(--danger); }
    .trend-neutral { background: rgba(108, 117, 125, 0.15); color: var(--gray-text); }
    
    .stat-value {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--light-text);
        margin-bottom: 5px;
        line-height: 1;
    }
    
    .stat-label {
        color: var(--gray-text);
        font-size: 0.95rem;
        margin-bottom: 10px;
        display: block;
    }
    
    .stat-subtext {
        color: var(--gray-text);
        font-size: 0.85rem;
        opacity: 0.8;
    }
    
    /* Charts Section */
    .charts-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 1200px) {
        .charts-section {
            grid-template-columns: 1fr;
        }
    }
    
    .chart-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #30363d;
    }
    
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .chart-header h3 {
        color: var(--light-text);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .chart-period {
        padding: 8px 15px;
        background: var(--primary-dark);
        border: 1px solid #30363d;
        border-radius: 8px;
        color: var(--light-text);
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .chart-container {
        height: 250px;
        position: relative;
    }
    
    /* Activity Section */
    .activity-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 1024px) {
        .activity-section {
            grid-template-columns: 1fr;
        }
    }
    
    .activity-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #30363d;
    }
    
    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #30363d;
    }
    
    .activity-header h3 {
        color: var(--light-text);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .view-all {
        color: var(--primary-gold);
        text-decoration: none;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s ease;
    }
    
    .view-all:hover {
        color: #ffc800;
    }
    
    .activity-list {
        list-style: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid #30363d;
        transition: background 0.3s ease;
    }
    
    .activity-item:hover {
        background: var(--hover-bg);
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    
    .activity-icon.booking { background: rgba(0, 123, 255, 0.1); color: var(--accent-blue); }
    .activity-icon.checkin { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .activity-icon.testimonial { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
    .activity-icon.user { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--light-text);
        margin-bottom: 3px;
        font-size: 0.95rem;
    }
    
    .activity-desc {
        color: var(--gray-text);
        font-size: 0.85rem;
        margin-bottom: 5px;
    }
    
    .activity-time {
        color: var(--gray-text);
        font-size: 0.8rem;
        opacity: 0.7;
    }
    
    .activity-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    /* Top Rooms */
    .top-room-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        border-bottom: 1px solid #30363d;
        transition: background 0.3s ease;
    }
    
    .top-room-item:hover {
        background: var(--hover-bg);
    }
    
    .top-room-item:last-child {
        border-bottom: none;
    }
    
    .room-rank {
        width: 30px;
        height: 30px;
        background: var(--primary-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #000;
        font-size: 0.9rem;
    }
    
    .room-rank.rank-1 { background: #ffd700; }
    .room-rank.rank-2 { background: #c0c0c0; }
    .room-rank.rank-3 { background: #cd7f32; }
    .room-rank.rank-4,
    .room-rank.rank-5 { background: var(--secondary-dark); color: var(--light-text); }
    
    .room-info {
        flex: 1;
    }
    
    .room-name {
        font-weight: 600;
        color: var(--light-text);
        font-size: 0.95rem;
    }
    
    .room-stats {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        color: var(--gray-text);
        margin-top: 3px;
    }
    
    .room-revenue {
        color: var(--success);
        font-weight: 600;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .action-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #30363d;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-gold);
        box-shadow: 0 10px 25px rgba(255, 215, 0, 0.1);
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        color: #000;
        font-size: 1.5rem;
    }
    
    .action-card h4 {
        color: var(--light-text);
        margin-bottom: 8px;
        font-size: 1.1rem;
    }
    
    .action-card p {
        color: var(--gray-text);
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.4;
    }
    
    .action-btn {
        display: inline-block;
        padding: 10px 25px;
        background: var(--primary-gold);
        color: #000;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        width: 100%;
        max-width: 200px;
    }
    
    .action-btn:hover {
        background: #e6c200;
        transform: scale(1.05);
    }
    
    /* Footer */
    .admin-footer {
        text-align: center;
        padding: 25px;
        color: var(--gray-text);
        font-size: 0.9rem;
        border-top: 1px solid #30363d;
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
        .welcome-header {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
        
        .date-time {
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
        
        .activity-item,
        .top-room-item {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
        
        .activity-badge {
            align-self: center;
        }
        
        .room-stats {
            justify-content: center;
        }
    }
    
    @media (max-width: 576px) {
        .admin-main {
            padding: 15px;
        }
        
        .current-time {
            font-size: 1.8rem;
        }
        
        .stat-value {
            font-size: 1.8rem;
        }
    }
    
    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: var(--primary-dark);
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary-gold);
        border-radius: 3px;
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in {
        animation: fadeInUp 0.5s ease-out;
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span class="nav-text">Dashboard</span></a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> <span class="nav-text">Kamar</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> <span class="nav-text">Reservasi</span></a></li>
                <li><a href="testimonials.php"><i class="fas fa-star"></i> <span class="nav-text">Testimonials</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a></li>
                <li class="logout-btn"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Welcome Header -->
            <div class="welcome-header fade-in">
                <div class="welcome-text">
                    <h1><i class="fas fa-crown"></i> Welcome to Sere Heaven Admin</h1>
                    <p>Manage your hotel operations efficiently and effectively</p>
                </div>
                <div class="date-time">
                    <div class="current-date"><?= date('l, F j, Y') ?></div>
                    <div class="current-time" id="currentTime"><?= date('H:i:s') ?></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid fade-in">
                <!-- Total Revenue -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon revenue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <span class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 12.5%
                        </span>
                    </div>
                    <div class="stat-value">Rp <?= number_format($stats['total_revenue']) ?></div>
                    <span class="stat-label">Total Revenue</span>
                    <div class="stat-subtext">
                        Today: Rp <?= number_format($stats['today_revenue']) ?>
                    </div>
                </div>

                <!-- Today's Bookings -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon bookings">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="stat-trend <?= $stats['today_bookings'] > 0 ? 'trend-up' : 'trend-neutral' ?>">
                            <i class="fas <?= $stats['today_bookings'] > 0 ? 'fa-arrow-up' : 'fa-minus' ?>"></i>
                            <?= $stats['today_bookings'] > 0 ? $stats['today_bookings'] : '0' ?>
                        </span>
                    </div>
                    <div class="stat-value"><?= $stats['today_bookings'] ?></div>
                    <span class="stat-label">Today's Bookings</span>
                    <div class="stat-subtext">
                        <?= $stats['today_bookings'] > 0 ? 'Active reservations today' : 'No bookings today' ?>
                    </div>
                </div>

                <!-- Total Rooms -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon rooms">
                            <i class="fas fa-bed"></i>
                        </div>
                        <span class="stat-trend trend-neutral">
                            <i class="fas fa-check"></i> Available
                        </span>
                    </div>
                    <div class="stat-value"><?= $stats['total_rooms'] ?></div>
                    <span class="stat-label">Total Rooms</span>
                    <div class="stat-subtext">
                        <?= count($room_distribution) > 0 ? $room_distribution[0]['room_type'] . ': ' . $room_distribution[0]['count'] : 'No rooms' ?>
                    </div>
                </div>

                <!-- Occupancy Rate -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon occupancy">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="stat-trend <?= $stats['occupancy_rate'] > 50 ? 'trend-up' : ($stats['occupancy_rate'] > 20 ? 'trend-neutral' : 'trend-down') ?>">
                            <?= $stats['occupancy_rate'] ?>%
                        </span>
                    </div>
                    <div class="stat-value"><?= $stats['occupancy_rate'] ?>%</div>
                    <span class="stat-label">Occupancy Rate</span>
                    <div class="stat-subtext">
                        <?= $occupied ?> rooms occupied today
                    </div>
                </div>

                <!-- Pending Testimonials -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon testimonials">
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="stat-trend <?= $stats['pending_testimonials'] > 0 ? 'trend-down' : 'trend-up' ?>">
                            <i class="fas <?= $stats['pending_testimonials'] > 0 ? 'fa-clock' : 'fa-check' ?>"></i>
                            <?= $stats['pending_testimonials'] > 0 ? $stats['pending_testimonials'] : 'All clear' ?>
                        </span>
                    </div>
                    <div class="stat-value"><?= $stats['pending_testimonials'] ?></div>
                    <span class="stat-label">Pending Reviews</span>
                    <div class="stat-subtext">
                        <?= $stats['pending_testimonials'] > 0 ? 'Need approval' : 'All reviews approved' ?>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="stat-trend trend-up">
                            <i class="fas fa-user-plus"></i> +<?= $stats['new_users'] ?>
                        </span>
                    </div>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                    <span class="stat-label">Total Users</span>
                    <div class="stat-subtext">
                        <?= $stats['new_users'] ?> new users today
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section fade-in">
                <!-- Revenue Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Revenue Overview</h3>
                        <select class="chart-period" id="chartPeriod">
                            <option value="6">Last 6 Months</option>
                            <option value="3">Last 3 Months</option>
                            <option value="12">Last 12 Months</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Room Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-pie-chart"></i> Room Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="roomChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activity Section -->
            <div class="activity-section fade-in">
                <!-- Recent Bookings -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h3><i class="fas fa-history"></i> Recent Bookings</h3>
                        <a href="reservations.php" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php if(mysqli_num_rows($recent_bookings_result) > 0): ?>
                            <?php 
                            $counter = 0;
                            mysqli_data_seek($recent_bookings_result, 0); // Reset pointer
                            ?>
                            <?php while($booking = mysqli_fetch_assoc($recent_bookings_result)): 
                                $time_ago = time_ago($booking['created_at']);
                                $status_color = [
                                    'pending' => '#ffc107',
                                    'confirmed' => '#28a745', 
                                    'check_in' => '#17a2b8',
                                    'check_out' => '#6c757d',
                                    'cancelled' => '#dc3545'
                                ][$booking['status']];
                            ?>
                            <li class="activity-item">
                                <div class="activity-icon booking">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?= htmlspecialchars($booking['user_name']) ?>
                                    </div>
                                    <div class="activity-desc">
                                        <?= $booking['room_type'] ?> - Room #<?= $booking['room_number'] ?>
                                    </div>
                                    <div class="activity-time">
                                        <?= date('d M H:i', strtotime($booking['created_at'])) ?> • <?= $time_ago ?>
                                    </div>
                                </div>
                                <div class="activity-badge" style="background: <?= $status_color ?>20; color: <?= $status_color ?>;">
                                    <?= strtoupper($booking['status']) ?>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="activity-item" style="justify-content: center; padding: 40px; color: var(--gray-text);">
                                <i class="fas fa-inbox fa-2x"></i>
                                <div style="margin-top: 10px;">No recent bookings</div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Top Performing Rooms -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h3><i class="fas fa-trophy"></i> Top Performing Rooms</h3>
                    </div>
                    
                    <ul class="activity-list">
                        <?php if(mysqli_num_rows($top_rooms_result) > 0): ?>
                            <?php 
                            $rank = 1;
                            while($room = mysqli_fetch_assoc($top_rooms_result)):
                            ?>
                            <li class="top-room-item">
                                <div class="room-rank rank-<?= $rank ?>">
                                    <?= $rank ?>
                                </div>
                                <div class="room-info">
                                    <div class="room-name">
                                        <?= $room['room_type'] ?> - Room #<?= $room['room_number'] ?>
                                    </div>
                                    <div class="room-stats">
                                        <span><?= $room['booking_count'] ?> bookings</span>
                                        <span class="room-revenue">Rp <?= number_format($room['total_revenue']) ?></span>
                                    </div>
                                </div>
                            </li>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <li class="activity-item" style="justify-content: center; padding: 40px; color: var(--gray-text);">
                                <i class="fas fa-chart-line fa-2x"></i>
                                <div style="margin-top: 10px;">No room performance data</div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions fade-in">
                <div class="action-card" onclick="window.location.href='rooms_form.php'">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h4>Add New Room</h4>
                    <p>Add a new room to your inventory with all amenities</p>
                    <div class="action-btn">Add Room</div>
                </div>

                <div class="action-card" onclick="window.location.href='reservations.php'">
                    <div class="action-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h4>Manage Bookings</h4>
                    <p>View, confirm, and manage all hotel reservations</p>
                    <div class="action-btn">View Bookings</div>
                </div>

                <div class="action-card" onclick="window.location.href='testimonials.php'">
                    <div class="action-icon">
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <h4>Approve Reviews</h4>
                    <p>Manage guest testimonials and feature top reviews</p>
                    <div class="action-btn">
                        <?= $stats['pending_testimonials'] > 0 ? 'Approve (' . $stats['pending_testimonials'] . ')' : 'View All' ?>
                    </div>
                </div>

                <div class="action-card" onclick="window.location.href='users.php'">
                    <div class="action-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h4>Manage Users</h4>
                    <p>View registered guests and manage user accounts</p>
                    <div class="action-btn">View Users</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="admin-footer">
                <p>Sere Heaven Hotel Management System &copy; <?= date('Y') ?></p>
                <p>System Status: <span style="color: var(--success);">●</span> Operational • Last Updated: <?= date('H:i:s') ?></p>
            </div>
        </main>
    </div>

    <script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { 
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('currentTime').textContent = timeString;
    }
    setInterval(updateTime, 1000);

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = {
        labels: <?= json_encode(array_column($monthly_revenue, 'month')) ?>,
        datasets: [{
            label: 'Revenue (Rp)',
            data: <?= json_encode(array_column($monthly_revenue, 'revenue')) ?>,
            backgroundColor: 'rgba(255, 215, 0, 0.2)',
            borderColor: 'rgba(255, 215, 0, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#ffd700',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    };

    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: revenueData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(22, 27, 34, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#f0f6fc',
                    borderColor: '#30363d',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#30363d'
                    },
                    ticks: {
                        color: '#8b949e',
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    grid: {
                        color: '#30363d'
                    },
                    ticks: {
                        color: '#8b949e'
                    }
                }
            }
        }
    });

    // Room Distribution Chart
    const roomCtx = document.getElementById('roomChart').getContext('2d');
    const roomData = {
        labels: <?= json_encode(array_column($room_distribution, 'room_type')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($room_distribution, 'count')) ?>,
            backgroundColor: [
                'rgba(255, 215, 0, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(0, 123, 255, 0.8)',
                'rgba(108, 117, 125, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#0d1117'
        }]
    };

    const roomChart = new Chart(roomCtx, {
        type: 'doughnut',
        data: roomData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#f0f6fc',
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(22, 27, 34, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#f0f6fc',
                    borderColor: '#30363d',
                    borderWidth: 1
                }
            }
        }
    });

    // Time ago function
    function time_ago(datetime) {
        const time = new Date(datetime).getTime();
        const now = new Date().getTime();
        const diff = now - time;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff/60000) + ' minutes ago';
        if (diff < 86400000) return Math.floor(diff/3600000) + ' hours ago';
        if (diff < 604800000) return Math.floor(diff/86400000) + ' days ago';
        if (diff < 2592000000) return Math.floor(diff/604800000) + ' weeks ago';
        if (diff < 31536000000) return Math.floor(diff/2592000000) + ' months ago';
        return Math.floor(diff/31536000000) + ' years ago';
    }

    // Update time ago for all activity items
    document.addEventListener('DOMContentLoaded', function() {
        const timeElements = document.querySelectorAll('.activity-time');
        timeElements.forEach(el => {
            const datetime = el.getAttribute('data-datetime');
            if (datetime) {
                setInterval(() => {
                    el.textContent = time_ago(datetime);
                }, 60000); // Update every minute
            }
        });
    });

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
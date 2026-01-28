<?php
require 'auth.php';
require '../config/db.php';

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_rooms'])) {
    $action = $_POST['bulk_action'];
    $room_ids = implode(',', array_map('intval', $_POST['selected_rooms']));
    
    switch($action) {
        case 'activate':
            mysqli_query($conn, "UPDATE rooms SET status='available' WHERE id IN ($room_ids)");
            $msg = 'bulk_activated';
            break;
        case 'deactivate':
            mysqli_query($conn, "UPDATE rooms SET status='inactive' WHERE id IN ($room_ids)");
            $msg = 'bulk_deactivated';
            break;
        case 'delete':
            // Delete images first
            $images_query = mysqli_query($conn, "SELECT image_url FROM rooms WHERE id IN ($room_ids)");
            while($image = mysqli_fetch_assoc($images_query)) {
                if ($image['image_url'] && $image['image_url'] != 'default.jpg') {
                    $image_path = '../assets/img/' . $image['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            mysqli_query($conn, "DELETE FROM rooms WHERE id IN ($room_ids)");
            $msg = 'bulk_deleted';
            break;
    }
    
    if (isset($msg)) {
        header("Location: rooms.php?msg=$msg");
        exit;
    }
}

// Handle single delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete image if exists
    $result = mysqli_query($conn, "SELECT image_url FROM rooms WHERE id = $id");
    $room = mysqli_fetch_assoc($result);
    
    if ($room && $room['image_url'] && $room['image_url'] != 'default.jpg') {
        $image_path = '../assets/img/' . $room['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    mysqli_query($conn, "DELETE FROM rooms WHERE id = $id");
    header("Location: rooms.php?msg=deleted");
    exit;
}

// Handle filters
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_search = $_GET['search'] ?? '';

// Build query with filters
$where = [];
$params = [];

if ($filter_type) {
    $where[] = "room_type = '$filter_type'";
}

if ($filter_status) {
    $where[] = "status = '$filter_status'";
}

if ($filter_search) {
    $search = mysqli_real_escape_string($conn, $filter_search);
    $where[] = "(room_number LIKE '%$search%' OR room_type LIKE '%$search%')";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get room counts for filters
$total_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms"))['total'];
$available_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status='available'"))['total'];
$premium_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE room_type='Premium'"))['total'];
$standard_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE room_type='Standard'"))['total'];

// Get rooms with filters
$query = "SELECT * FROM rooms $where_clause ORDER BY 
          CASE room_type 
            WHEN 'Premium' THEN 1 
            WHEN 'Standard' THEN 2 
            ELSE 3 
          END,
          CAST(room_number AS UNSIGNED) ASC";

$q = mysqli_query($conn, $query);
$total_rows = mysqli_num_rows($q);

// Message handling
$messages = [
    'added' => '✅ Kamar berhasil ditambahkan!',
    'updated' => '✅ Kamar berhasil diperbarui!',
    'deleted' => '❌ Kamar berhasil dihapus!',
    'bulk_activated' => '✅ Kamar berhasil diaktifkan!',
    'bulk_deactivated' => '✅ Kamar berhasil dinonaktifkan!',
    'bulk_deleted' => '❌ Kamar berhasil dihapus!'
];
$msg = isset($_GET['msg']) ? ($messages[$_GET['msg']] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* === ROOMS DASHBOARD REDESIGN === */
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
    
    .sidebar-header h2 i {
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
    
    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 25px;
        border-bottom: 1px solid #30363d;
    }
    
    .page-header h1 {
        color: var(--light-text);
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .page-header h1 i {
        color: var(--primary-gold);
    }
    
    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .btn-add {
        display: flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, var(--primary-gold), #ffaa00);
        color: #000;
        padding: 12px 25px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
    }
    
    /* Stats Cards */
    .stats-grid {
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
    .stat-icon.available { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .stat-icon.premium { background: rgba(255, 215, 0, 0.1); color: var(--primary-gold); }
    .stat-icon.standard { background: rgba(108, 117, 125, 0.1); color: var(--gray-text); }
    
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
    
    /* Filters Section */
    .filters-section {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 15px;
        border: 1px solid #30363d;
        margin-bottom: 30px;
    }
    
    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .filters-header h3 {
        color: var(--light-text);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .clear-filters {
        color: var(--danger);
        text-decoration: none;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s ease;
    }
    
    .clear-filters:hover {
        color: #ff6b6b;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
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
    
    .filter-select, .filter-input {
        padding: 12px 15px;
        background: var(--primary-dark);
        border: 1px solid #30363d;
        border-radius: 8px;
        color: var(--light-text);
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }
    
    .filter-select:focus, .filter-input:focus {
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
        align-items: flex-end;
    }
    
    .btn-filter, .btn-reset {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }
    
    .btn-filter {
        background: var(--primary-gold);
        color: #000;
    }
    
    .btn-filter:hover {
        background: #e6c200;
        transform: translateY(-2px);
    }
    
    .btn-reset {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid #30363d;
    }
    
    .btn-reset:hover {
        background: var(--hover-bg);
        color: var(--light-text);
    }
    
    /* Bulk Actions */
    .bulk-actions {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 15px;
        border: 1px solid #30363d;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 25px;
        flex-wrap: wrap;
    }
    
    .bulk-select {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .select-all {
        padding: 10px 15px;
        background: var(--primary-dark);
        border: 1px solid #30363d;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        color: var(--light-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .bulk-options {
        display: flex;
        gap: 15px;
        flex: 1;
        align-items: center;
    }
    
    .bulk-select select {
        padding: 10px 15px;
        background: var(--primary-dark);
        border: 1px solid #30363d;
        border-radius: 8px;
        color: var(--light-text);
        flex: 1;
        max-width: 250px;
        font-size: 0.95rem;
    }
    
    .btn-bulk {
        padding: 10px 25px;
        background: var(--accent-blue);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-bulk:hover {
        background: #0d6efd;
    }
    
    .results-count {
        color: var(--gray-text);
        font-size: 0.9rem;
        margin-left: auto;
    }
    
    /* Rooms Grid */
    .rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .room-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        border: 1px solid #30363d;
        overflow: hidden;
        transition: all 0.4s ease;
        position: relative;
    }
    
    .room-card:hover {
        transform: translateY(-10px);
        border-color: var(--primary-gold);
        box-shadow: 0 15px 35px rgba(255, 215, 0, 0.15);
    }
    
    .room-checkbox {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 2;
        width: 22px;
        height: 22px;
        cursor: pointer;
        accent-color: var(--primary-gold);
    }
    
    .room-image {
        position: relative;
        height: 220px;
        overflow: hidden;
    }
    
    .room-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .room-card:hover .room-image img {
        transform: scale(1.08);
    }
    
    .room-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 8px 15px;
        border-radius: 25px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        z-index: 1;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .badge-premium {
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 170, 0, 0.2));
        color: var(--primary-gold);
        border-color: rgba(255, 215, 0, 0.3);
    }
    
    .badge-standard {
        background: rgba(255, 255, 255, 0.1);
        color: var(--light-text);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .room-content {
        padding: 25px;
    }
    
    .room-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    
    .room-title h3 {
        color: var(--light-text);
        font-size: 1.4rem;
        margin-bottom: 5px;
    }
    
    .room-number {
        color: var(--gray-text);
        font-size: 0.9rem;
    }
    
    .room-price {
        text-align: right;
    }
    
    .price-label {
        color: var(--gray-text);
        font-size: 0.85rem;
        display: block;
    }
    
    .price-amount {
        color: var(--primary-gold);
        font-size: 1.8rem;
        font-weight: 700;
        display: block;
        line-height: 1;
    }
    
    .room-details {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #30363d;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-text);
        font-size: 0.9rem;
    }
    
    .detail-item i {
        color: var(--primary-gold);
    }
    
    .room-description {
        color: var(--gray-text);
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .room-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .room-status {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .status-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-available {
        background: rgba(40, 167, 69, 0.15);
        color: var(--success);
        border: 1px solid rgba(40, 167, 69, 0.3);
    }
    
    .status-inactive {
        background: rgba(220, 53, 69, 0.15);
        color: var(--danger);
        border: 1px solid rgba(220, 53, 69, 0.3);
    }
    
    .room-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-action {
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        font-size: 1rem;
    }
    
    .btn-edit {
        background: rgba(0, 123, 255, 0.1);
        color: var(--accent-blue);
        border: 1px solid rgba(0, 123, 255, 0.2);
    }
    
    .btn-edit:hover {
        background: rgba(0, 123, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger);
        border: 1px solid rgba(220, 53, 69, 0.2);
    }
    
    .btn-delete:hover {
        background: rgba(220, 53, 69, 0.2);
        transform: translateY(-2px);
    }
    
    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 40px;
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        border: 1px solid #30363d;
        margin: 30px 0;
    }
    
    .empty-icon {
        font-size: 4rem;
        color: var(--gray-text);
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        color: var(--light-text);
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: var(--gray-text);
        max-width: 500px;
        margin: 0 auto 30px;
        line-height: 1.6;
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
    
    /* Message Alert */
    .message-alert {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success);
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid rgba(40, 167, 69, 0.3);
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Modal Styles */
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
        max-width: 400px;
        width: 100%;
        padding: 30px;
        text-align: center;
        animation: modalFadeIn 0.3s ease;
        border: 1px solid #30363d;
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .modal-icon {
        font-size: 3rem;
        color: var(--danger);
        margin-bottom: 20px;
    }
    
    .modal-buttons {
        display: flex;
        gap: 15px;
        margin-top: 25px;
    }
    
    .btn-cancel, .btn-confirm {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .btn-cancel {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid #30363d;
    }
    
    .btn-cancel:hover {
        background: var(--hover-bg);
    }
    
    .btn-confirm {
        background: var(--danger);
        color: white;
    }
    
    .btn-confirm:hover {
        background: #c82333;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .rooms-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }
    
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
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .bulk-actions {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .bulk-options {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }
        
        .bulk-select select {
            max-width: 100%;
        }
        
        .btn-bulk {
            width: 100%;
            justify-content: center;
        }
        
        .results-count {
            margin-left: 0;
            width: 100%;
            text-align: center;
        }
        
        .rooms-grid {
            grid-template-columns: 1fr;
        }
        
        .room-footer {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        
        .room-actions {
            justify-content: center;
        }
    }
    
    @media (max-width: 576px) {
        .admin-main {
            padding: 15px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .header-actions {
            width: 100%;
            justify-content: space-between;
        }
        
        .stat-value {
            font-size: 2rem;
        }
        
        .empty-state {
            padding: 40px 20px;
        }
    }
    
    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: var(--primary-dark);
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary-gold);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #ffc800;
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
                <li><a href="rooms.php" class="active"><i class="fas fa-bed"></i> <span class="nav-text">Kamar</span></a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> <span class="nav-text">Reservasi</span></a></li>
                <li><a href="testimonials.php"><i class="fas fa-star"></i> <span class="nav-text">Testimonials</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a></li>
                <li class="logout-btn"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-bed"></i> Manage Rooms</h1>
                <div class="header-actions">
                    <a href="rooms_form.php" class="btn-add">
                        <i class="fas fa-plus"></i> Add New Room
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <span class="stat-value"><?= $total_rooms ?></span>
                    <span class="stat-label">Total Rooms</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon available">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="stat-value"><?= $available_rooms ?></span>
                    <span class="stat-label">Available Rooms</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon premium">
                        <i class="fas fa-crown"></i>
                    </div>
                    <span class="stat-value"><?= $premium_rooms ?></span>
                    <span class="stat-label">Premium Rooms</span>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon standard">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="stat-value"><?= $standard_rooms ?></span>
                    <span class="stat-label">Standard Rooms</span>
                </div>
            </div>

            <!-- Message Alert -->
            <?php if($msg): ?>
            <div class="message-alert">
                <i class="fas fa-check-circle"></i>
                <span><?= $msg ?></span>
            </div>
            <?php endif; ?>

            <!-- Filters Section -->
            <div class="filters-section">
                <div class="filters-header">
                    <h3><i class="fas fa-filter"></i> Filter Rooms</h3>
                    <?php if($filter_type || $filter_status || $filter_search): ?>
                    <a href="rooms.php" class="clear-filters">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </div>
                
                <form method="get" class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-tag"></i>
                            Room Type
                        </label>
                        <select name="type" class="filter-select">
                            <option value="">All Types</option>
                            <option value="Premium" <?= $filter_type == 'Premium' ? 'selected' : '' ?>>Premium</option>
                            <option value="Standard" <?= $filter_type == 'Standard' ? 'selected' : '' ?>>Standard</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-toggle-on"></i>
                            Status
                        </label>
                        <select name="status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="available" <?= $filter_status == 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="inactive" <?= $filter_status == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search"></i>
                            Search
                        </label>
                        <input type="text" 
                               name="search" 
                               class="filter-input" 
                               placeholder="Room number or type..."
                               value="<?= htmlspecialchars($filter_search) ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="rooms.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="post" id="bulkForm" class="bulk-actions">
                <div class="bulk-select">
                    <input type="checkbox" id="selectAll" class="select-all">
                    <label for="selectAll" class="select-all">Select All</label>
                </div>
                
                <div class="bulk-options">
                    <select name="bulk_action" class="bulk-select" required>
                        <option value="">Bulk Actions</option>
                        <option value="activate">Activate Selected</option>
                        <option value="deactivate">Deactivate Selected</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    
                    <button type="submit" class="btn-bulk" onclick="return confirmBulkAction()">
                        <i class="fas fa-play"></i> Apply
                    </button>
                </div>
                
                <div class="results-count">
                    <i class="fas fa-info-circle"></i>
                    <?= $total_rows ?> rooms found
                </div>
            </form>

            <!-- Rooms Grid -->
            <div class="rooms-grid">
                <?php if($total_rows == 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏨</div>
                        <h3>No Rooms Found</h3>
                        <p><?= ($filter_type || $filter_status || $filter_search) ? 'Try changing your filters or add new rooms' : 'No rooms added yet. Add your first room to get started!' ?></p>
                        <a href="rooms_form.php" class="btn-add" style="display: inline-flex;">
                            <i class="fas fa-plus"></i> Add First Room
                        </a>
                    </div>
                <?php else: ?>
                    <?php while($r = mysqli_fetch_assoc($q)): 
                        $image_path = '../assets/img/' . ($r['image_url'] ?? 'default.jpg');
                        $has_image = file_exists($image_path) && ($r['image_url'] ?? '') != 'default.jpg';
                    ?>
                    <div class="room-card">
                        <!-- Checkbox for bulk selection -->
                        <input type="checkbox" 
                               name="selected_rooms[]" 
                               value="<?= $r['id'] ?>" 
                               class="room-checkbox"
                               form="bulkForm">
                        
                        <!-- Room Image -->
                        <div class="room-image">
                            <?php if($has_image): ?>
                                <img src="<?= $image_path ?>" alt="Kamar <?= $r['room_number'] ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:linear-gradient(135deg, rgba(255,215,0,0.1), rgba(88,166,255,0.1));display:flex;align-items:center;justify-content:center;color:var(--primary-gold);font-size:4rem;">
                                    <i class="fas fa-bed"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Room Type Badge -->
                            <div class="room-badge <?= $r['room_type'] == 'Premium' ? 'badge-premium' : 'badge-standard' ?>">
                                <?= $r['room_type'] ?>
                            </div>
                        </div>
                        
                        <!-- Room Content -->
                        <div class="room-content">
                            <!-- Header -->
                            <div class="room-header">
                                <div class="room-title">
                                    <h3><?= $r['room_type'] ?> Suite</h3>
                                    <div class="room-number">Room #<?= $r['room_number'] ?></div>
                                </div>
                                <div class="room-price">
                                    <span class="price-label">Per Night</span>
                                    <span class="price-amount">Rp <?= number_format($r['price_per_night']) ?></span>
                                </div>
                            </div>
                            
                            <!-- Details -->
                            <div class="room-details">
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?= $r['capacity'] ?> Person</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-ruler-combined"></i>
                                    <span><?= $r['room_type'] == 'Premium' ? '48' : '24' ?> m²</span>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <?php if($r['description']): ?>
                            <div class="room-description">
                                <?= htmlspecialchars(substr($r['description'], 0, 100)) . (strlen($r['description']) > 100 ? '...' : '') ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Footer -->
                            <div class="room-footer">
                                <div class="room-status">
                                    <div class="status-badge <?= $r['status'] == 'available' ? 'status-available' : 'status-inactive' ?>">
                                        <?= $r['status'] == 'available' ? 'Available' : 'Inactive' ?>
                                    </div>
                                </div>
                                <div class="room-actions">
                                    <a href="rooms_form.php?id=<?= $r['id'] ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn-action btn-delete" 
                                            title="Delete"
                                            onclick="confirmDelete(<?= $r['id'] ?>, '<?= addslashes($r['room_number']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="admin-footer">
                <p>Sere Heaven Hotel Management System &copy; <?= date('Y') ?></p>
                <p>Room Management | Last Updated: <?= date('H:i:s') ?></p>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="color: var(--light-text); margin-bottom: 10px;">Confirm Delete</h3>
            <p style="color: var(--gray-text); margin-bottom: 20px;" id="deleteMessage">
                Are you sure you want to delete this room?
            </p>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-confirm" onclick="deleteRoom()">Delete</button>
            </div>
        </div>
    </div>

    <script>
    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    const roomCheckboxes = document.querySelectorAll('.room-checkbox');
    
    selectAll.addEventListener('change', function() {
        roomCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Individual checkbox state
    roomCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(roomCheckboxes).every(cb => cb.checked);
            selectAll.checked = allChecked;
        });
    });
    
    // Delete confirmation
    let roomIdToDelete = null;
    let roomNumberToDelete = '';
    
    function confirmDelete(id, roomNumber) {
        roomIdToDelete = id;
        roomNumberToDelete = roomNumber;
        document.getElementById('deleteMessage').textContent = 
            `Are you sure you want to delete Room #${roomNumber}? This action cannot be undone.`;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
        roomIdToDelete = null;
    }
    
    function deleteRoom() {
        if (roomIdToDelete) {
            window.location.href = `rooms.php?delete=${roomIdToDelete}`;
        }
    }
    
    // Bulk action confirmation
    function confirmBulkAction() {
        const bulkForm = document.getElementById('bulkForm');
        const bulkSelect = bulkForm.querySelector('select[name="bulk_action"]');
        const selectedCheckboxes = Array.from(roomCheckboxes).filter(cb => cb.checked);
        
        if (!bulkSelect.value) {
            alert('Please select a bulk action');
            return false;
        }
        
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one room');
            return false;
        }
        
        const action = bulkSelect.value;
        const roomCount = selectedCheckboxes.length;
        let message = '';
        
        switch(action) {
            case 'delete':
                message = `Are you sure you want to delete ${roomCount} room(s)? This action cannot be undone.`;
                break;
            case 'activate':
                message = `Are you sure you want to activate ${roomCount} room(s)?`;
                break;
            case 'deactivate':
                message = `Are you sure you want to deactivate ${roomCount} room(s)?`;
                break;
        }
        
        return confirm(message);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Room card hover effects
    const roomCards = document.querySelectorAll('.room-card');
    roomCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '1';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '0';
        });
    });
    </script>
</body>
</html>
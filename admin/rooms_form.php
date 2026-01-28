<?php

require 'auth.php';
require '../config/db.php';

// Initialize variables
$is_edit = false;
$room_data = [];
$error = '';
$success = '';
$duplicate_room = null;

// Check if editing existing room
if (isset($_GET['id'])) {
    $is_edit = true;
    $room_id = (int)$_GET['id'];
    
    // Fetch room data
    $query = "SELECT * FROM rooms WHERE id = $room_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $room_data = mysqli_fetch_assoc($result);
    } else {
        header("Location: rooms.php?msg=notfound");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    // Get form data
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $room_type = mysqli_real_escape_string($conn, $_POST['room_type']);
    $capacity = (int)$_POST['capacity'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price_str = $_POST['price'];
    $price = (int)str_replace('.', '', $price_str); 
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate required fields
    if (empty($room_number) || empty($room_type) || empty($price)) {
        $error = 'Semua field wajib diisi!';
    } else {
        // ✅ CEK DUPLIKASI ROOM NUMBER
        $check_query = "SELECT * FROM rooms WHERE room_number = '$room_number'";
        if ($is_edit) {
            $check_query .= " AND id != $room_id";
        }
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $duplicate_room = mysqli_fetch_assoc($check_result);
            $error = '❌ Nomor kamar <strong>' . htmlspecialchars($room_number) . '</strong> sudah digunakan!';
            $error .= '<br><small style="color: #ff6b6b;">Kamar ' . $duplicate_room['room_type'] . ' - Status: ' . 
                     ($duplicate_room['status'] == 'available' ? '✅ Tersedia' : '⏸️ Tidak Aktif') . '</small>';
        } else {
            // Handle file upload
            $image_url = $room_data['image_url'] ?? 'default.jpg';
            
            if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] == 0) {
                $file = $_FILES['room_image'];
                $file_name = $file['name'];
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];
                $file_error = $file['error'];
                
                // Check file extension
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($file_ext, $allowed_ext)) {
                    if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                        // Generate unique filename
                        $new_filename = 'rooms/room_' . $room_number . '_' . time() . '.' . $file_ext;
                        $upload_path = '../assets/img/' . $new_filename;
                        
                        // Create directories if they don't exist
                        if (!file_exists('../assets/img/rooms')) {
                            mkdir('../assets/img/rooms', 0777, true);
                        }
                        
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            // Delete old image if exists and not default
                            if ($is_edit && $image_url != 'default.jpg' && $image_url != $new_filename) {
                                $old_image_path = '../assets/img/' . $image_url;
                                if (file_exists($old_image_path)) {
                                    unlink($old_image_path);
                                }
                            }
                            $image_url = $new_filename;
                        } else {
                            $error = 'Gagal mengupload gambar!';
                        }
                    } else {
                        $error = 'Ukuran file terlalu besar (max 5MB)!';
                    }
                } else {
                    $error = 'Format file tidak didukung!';
                }
            }
            
            // If no error, proceed with save/update
            if (empty($error)) {
                if ($is_edit) {
                    // Update existing room
                    $query = "UPDATE rooms SET 
                              room_number = '$room_number',
                              room_type = '$room_type',
                              capacity = $capacity,
                              description = '$description',
                              price_per_night = $price,
                              image_url = '$image_url',
                              status = '$status'
                              WHERE id = $room_id";
                } else {
                    // Insert new room
                    $query = "INSERT INTO rooms 
                              (room_number, room_type, capacity, description, price_per_night, image_url, status) 
                              VALUES 
                              ('$room_number', '$room_type', $capacity, '$description', $price, '$image_url', '$status')";
                }
                
                if (mysqli_query($conn, $query)) {
                    $msg = $is_edit ? 'updated' : 'added';
                    header("Location: rooms.php?msg=$msg");
                    exit;
                } else {
                    $error = 'Terjadi kesalahan database: ' . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Kamar - Admin Sere Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* === ROOM FORM REDESIGN === */
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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .form-container {
        width: 100%;
        max-width: 900px;
        background: var(--secondary-dark);
        border-radius: 20px;
        border: 1px solid #30363d;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .form-header {
        padding: 30px 40px;
        background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(88, 166, 255, 0.1));
        border-bottom: 1px solid #30363d;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .form-header h1 {
        font-size: 1.8rem;
        color: var(--primary-gold);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .form-header-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-gold);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-size: 1.3rem;
    }
    
    .form-nav {
        display: flex;
        gap: 10px;
    }
    
    .nav-btn {
        padding: 10px 20px;
        background: transparent;
        border: 1px solid #30363d;
        border-radius: 8px;
        color: var(--gray-text);
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .nav-btn:hover {
        background: #21262d;
        color: var(--light-text);
    }
    
    /* Form Steps */
    .form-steps {
        display: flex;
        padding: 0 40px;
        margin-top: 10px;
    }
    
    .step {
        flex: 1;
        text-align: center;
        padding: 15px 0;
        position: relative;
        cursor: pointer;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        background: #30363d;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        color: var(--gray-text);
        font-weight: 600;
    }
    
    .step.active .step-number {
        background: var(--primary-gold);
        color: #000;
    }
    
    .step.completed .step-number {
        background: var(--success);
        color: #fff;
    }
    
    .step-label {
        font-size: 0.85rem;
        color: var(--gray-text);
    }
    
    .step.active .step-label {
        color: var(--primary-gold);
        font-weight: 500;
    }
    
    .step::after {
        content: '';
        position: absolute;
        top: 35px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #30363d;
        z-index: -1;
    }
    
    .step:last-child::after {
        display: none;
    }
    
    .step.completed::after {
        background: var(--success);
    }
    
    /* Form Body */
    .form-body {
        padding: 40px;
    }
    
    .step-content {
        display: none;
    }
    
    .step-content.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .step-title {
        font-size: 1.3rem;
        margin-bottom: 25px;
        color: var(--light-text);
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
    }
    
    .step-icon {
        color: var(--primary-gold);
    }
    
    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--light-text);
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    .form-label i {
        color: var(--primary-gold);
        width: 20px;
    }
    
    .form-input, .form-select, .form-textarea {
        padding: 14px 16px;
        background: var(--primary-dark);
        border: 1px solid #30363d;
        border-radius: 10px;
        color: var(--light-text);
        font-size: 1rem;
        font-family: inherit;
        transition: all 0.3s ease;
    }
    
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }
    
    /* Input Validation States */
    .form-input.valid {
        border-color: var(--success) !important;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1) !important;
    }
    
    .form-input.invalid {
        border-color: var(--danger) !important;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
    }
    
    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ffd700' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
    }
    
    .input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .currency {
        color: var(--primary-gold);
        font-weight: 600;
    }
    
    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 10px;
    }
    
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .radio-option input[type="radio"] {
        display: none;
    }
    
    .radio-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #30363d;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .radio-custom::after {
        content: '';
        width: 10px;
        height: 10px;
        background: var(--primary-gold);
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .radio-option input[type="radio"]:checked + .radio-custom {
        border-color: var(--primary-gold);
    }
    
    .radio-option input[type="radio"]:checked + .radio-custom::after {
        opacity: 1;
    }
    
    .radio-label {
        color: var(--gray-text);
        transition: color 0.3s ease;
    }
    
    .radio-option input[type="radio"]:checked ~ .radio-label {
        color: var(--primary-gold);
        font-weight: 500;
    }
    
    /* Image Upload */
    .image-upload {
        grid-column: 1 / -1;
    }
    
    .upload-area {
        border: 2px dashed #30363d;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--primary-dark);
    }
    
    .upload-area:hover {
        border-color: var(--primary-gold);
        background: rgba(255, 215, 0, 0.05);
    }
    
    .upload-icon {
        font-size: 3rem;
        color: var(--gray-text);
        margin-bottom: 15px;
        transition: color 0.3s ease;
    }
    
    .upload-area:hover .upload-icon {
        color: var(--primary-gold);
    }
    
    .upload-text h4 {
        color: var(--light-text);
        margin-bottom: 8px;
    }
    
    .upload-text p {
        color: var(--gray-text);
        font-size: 0.9rem;
    }
    
    .upload-input {
        display: none;
    }
    
    .image-preview {
        margin-top: 20px;
        text-align: center;
    }
    
    .preview-title {
        color: var(--gray-text);
        margin-bottom: 10px;
        font-size: 0.9rem;
    }
    
    .preview-image {
        max-width: 300px;
        max-height: 200px;
        border-radius: 10px;
        border: 1px solid #30363d;
        object-fit: cover;
        margin: 0 auto;
    }
    
    /* Current Image */
    .current-image {
        margin-top: 20px;
        padding: 20px;
        background: rgba(255, 215, 0, 0.05);
        border-radius: 10px;
        border: 1px solid rgba(255, 215, 0, 0.2);
    }
    
    .current-image h4 {
        color: var(--primary-gold);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Availability Check */
    .availability-check {
        margin-top: 5px;
        font-size: 0.9rem;
        display: none;
    }
    
    .availability-check i {
        margin-right: 5px;
    }
    
    /* Form Footer */
    .form-footer {
        padding: 30px 40px;
        background: var(--primary-dark);
        border-top: 1px solid #30363d;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn {
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-secondary {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid #30363d;
    }
    
    .btn-secondary:hover {
        background: #21262d;
        color: var(--light-text);
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
    
    .step-buttons {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }
    
    .btn-next {
        background: var(--accent-blue);
        color: white;
    }
    
    .btn-prev {
        background: transparent;
        color: var(--gray-text);
        border: 1px solid #30363d;
    }
    
    .btn-next:hover {
        background: #0d6efd;
    }
    
    .btn-prev:hover {
        background: #21262d;
        color: var(--light-text);
    }
        
    /* Responsive */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-header, .form-body, .form-footer {
            padding: 20px;
        }
        
        .step-label {
            font-size: 0.8rem;
        }
        
        .radio-group {
            flex-direction: column;
            gap: 10px;
        }
        
        .step-buttons, .form-footer {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .form-steps {
            padding: 0 20px;
        }
        
        .step::after {
            display: none;
        }
    }
    
    @media (max-width: 480px) {
        .form-header h1 {
            font-size: 1.4rem;
        }
        
        .form-header-icon {
            width: 40px;
            height: 40px;
        }
        
        .upload-area {
            padding: 20px;
        }
    }
    
    /* Error/Message Styles */
    .message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
   
    
    .message.info {
        background: rgba(88, 166, 255, 0.1);
        border: 1px solid rgba(88, 166, 255, 0.3);
        color: var(--accent-blue);
    }
    
    .duplicate-info {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        padding: 10px 15px;
        border-radius: 8px;
        margin-top: 10px;
        font-size: 0.9rem;
        color: var(--warning);
    }
    
    .duplicate-info strong {
        color: var(--light-text);
    }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="form-header-icon">
                    <?php if($is_edit): ?>
                    <i class="fas fa-edit"></i>
                    <?php else: ?>
                    <i class="fas fa-plus"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h1><?= $is_edit ? 'Edit Kamar' : 'Tambah Kamar Baru' ?></h1>
                    <p style="color: var(--gray-text); font-size: 0.9rem; margin-top: 5px;">
                        <?= $is_edit ? 'Perbarui informasi kamar' : 'Tambahkan kamar baru ke inventaris hotel' ?>
                    </p>
                </div>
            </div>
            
            <div class="form-nav">
                <a href="rooms.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- Form Steps -->
        <div class="form-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Informasi Dasar</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Fasilitas & Harga</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Gambar & Status</div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($error)): ?>
        
        <?php if($duplicate_room): ?>
        <div class="duplicate-info">
            <i class="fas fa-info-circle"></i>
            <span>
                <strong>Detail kamar yang sudah ada:</strong><br>
                • Tipe: <?= $duplicate_room['room_type'] ?><br>
                • Status: <?= $duplicate_room['status'] == 'available' ? '✅ Tersedia' : '⏸️ Tidak Aktif' ?><br>
                • Harga: Rp <?= number_format($duplicate_room['price_per_night']) ?>/malam<br>
                <?php if($duplicate_room['description']): ?>
                • Deskripsi: <?= substr($duplicate_room['description'], 0, 50) ?>...
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
        <?php endif; ?>
        
        <!-- Form Body -->
        <form method="post" enctype="multipart/form-data" class="form-body">
            <!-- Hidden fields for AJAX -->
            <input type="hidden" id="is_edit" value="<?= $is_edit ? 'true' : 'false' ?>">
            <input type="hidden" id="current_room_id" value="<?= $room_data['id'] ?? 0 ?>">

               <button type="submit" name="save" class="btn btn-primary" id="submitBtn">
            
            <!-- Step 1: Basic Information -->
            <div class="step-content active" id="step1">
                <h3 class="step-title">
                    <i class="fas fa-info-circle step-icon"></i>
                    Informasi Dasar Kamar
                </h3>
                
                <div class="form-grid">
                    <div class="form-group room-number-validation">
                        <label class="form-label">
                            <i class="fas fa-hashtag"></i>
                            Nomor Kamar
                        </label>
                        <input type="text" name="room_number" id="room_number" 
                               class="form-input" 
                               value="<?= htmlspecialchars($room_data['room_number'] ?? '') ?>" 
                               required 
                               placeholder="Contoh: 101, 201, 301">
                        <div class="availability-check" id="availabilityCheck">
                            <!-- AJAX response will appear here -->
                        </div>
                        <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 5px;">
                            Nomor unik untuk identifikasi kamar
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-bed"></i>
                            Tipe Kamar
                        </label>
                        <select name="room_type" id="room_type" class="form-select" required>
                            <option value="">Pilih Tipe Kamar</option>
                            <?php
                            $room_types = ['Standard' => 'Standard', 'Premium' => 'Premium'];
                            $current_type = $room_data['room_type'] ?? '';
                            foreach ($room_types as $value => $label):
                            ?>
                            <option value="<?= $value ?>" <?= $current_type == $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 5px;">
                            Pilih kategori kamar
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-users"></i>
                            Kapasitas
                        </label>
                        <div class="radio-group">
                            <?php
                            $capacities = [1 => '1 Orang', 2 => '2 Orang', 3 => '3 Orang', 4 => '4 Orang'];
                            $current_capacity = $room_data['capacity'] ?? 2;
                            
                            foreach ($capacities as $value => $label):
                            ?>
                            <label class="radio-option">
                                <input type="radio" name="capacity" value="<?= $value ?>" 
                                       <?= $current_capacity == $value ? 'checked' : '' ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-label"><?= $label ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Deskripsi Kamar
                        </label>
                        <textarea name="description" id="description" 
                                  class="form-textarea" 
                                  placeholder="Deskripsikan kamar, fasilitas, view, dan keunikan lainnya..."
                                  rows="4"><?= htmlspecialchars($room_data['description'] ?? '') ?></textarea>
                        <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 5px;">
                            Deskripsi akan ditampilkan di halaman pemesanan
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Facilities & Price -->
            <div class="step-content" id="step2">
                <h3 class="step-title">
                    <i class="fas fa-money-bill-wave step-icon"></i>
                    Harga & Fasilitas
                </h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tags"></i>
                            Harga per Malam
                        </label>
                      <div class="input-group">
    <span class="currency">Rp</span>
    <input type="text" name="price" id="price" 
           class="form-input"
           value="<?= $room_data['price_per_night'] ?? '350000' ?>"
           placeholder="350000"
           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
</div>
                        <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 5px;">
                            Harga sudah termasuk fasilitas dasar
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-wifi"></i>
                            Fasilitas Inklusif
                        </label>
                        <div style="background: var(--primary-dark); padding: 15px; border-radius: 10px; border: 1px solid #30363d;">
                            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                                <?php
                                $facilities = [
                                    'wifi' => ['icon' => 'fa-wifi', 'label' => 'Free WiFi'],
                                    'ac' => ['icon' => 'fa-snowflake', 'label' => 'AC'],
                                    'tv' => ['icon' => 'fa-tv', 'label' => 'TV'],
                                    'bathroom' => ['icon' => 'fa-shower', 'label' => 'Private Bathroom'],
                                    'breakfast' => ['icon' => 'fa-coffee', 'label' => 'Breakfast'],
                                    'parking' => ['icon' => 'fa-car', 'label' => 'Free Parking']
                                ];
                                
                                foreach ($facilities as $key => $facility):
                                ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas <?= $facility['icon'] ?>" style="color: var(--primary-gold);"></i>
                                    <span style="color: var(--light-text); font-size: 0.9rem;">
                                        <?= $facility['label'] ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 10px; display: block;">
                                *Semua fasilitas ini termasuk dalam harga
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-crown"></i>
                            Fasilitas Premium (Opsional)
                        </label>
                        <div style="background: rgba(255, 215, 0, 0.05); padding: 15px; border-radius: 10px; border: 1px solid rgba(255, 215, 0, 0.2); margin-bottom: 10px;">
                            <p style="color: var(--gray-text); margin-bottom: 15px;">
                                Fasilitas berikut hanya untuk kamar Premium:
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-bath" style="color: var(--primary-gold);"></i>
                                    <span style="color: var(--light-text); font-size: 0.9rem;">Bathtub</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-umbrella-beach" style="color: var(--primary-gold);"></i>
                                    <span style="color: var(--light-text); font-size: 0.9rem;">Balcony</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-couch" style="color: var(--primary-gold);"></i>
                                    <span style="color: var(--light-text); font-size: 0.9rem;">Living Area</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-wine-bottle" style="color: var(--primary-gold);"></i>
                                    <span style="color: var(--light-text); font-size: 0.9rem;">Mini Bar</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Image & Status -->
            <div class="step-content" id="step3">
                <h3 class="step-title">
                    <i class="fas fa-image step-icon"></i>
                    Gambar & Status Kamar
                </h3>
                
                <div class="form-grid">
                    <div class="form-group full-width image-upload">
                        <label class="form-label">
                            <i class="fas fa-camera"></i>
                            Gambar Kamar
                        </label>
                        
                        <!-- Upload Area -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">
                                <h4>Upload Gambar Kamar</h4>
                                <p>Drag & drop atau klik untuk memilih file</p>
                                <p style="font-size: 0.8rem; margin-top: 5px;">
                                    Format: JPG, PNG, WebP | Maks: 5MB
                                </p>
                            </div>
                            <input type="file" name="room_image" id="room_image" 
                                   class="upload-input" accept="image/*">
                        </div>
                        
                        <!-- Preview -->
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <div class="preview-title">Preview Gambar:</div>
                            <img id="previewImage" class="preview-image" src="" alt="Preview">
                        </div>
                        
                        <!-- Current Image (for edit mode) -->
                        <?php if($is_edit && isset($room_data['image_url']) && $room_data['image_url'] != 'default.jpg'): 
                            $image_path = '../assets/img/' . $room_data['image_url'];
                            $image_exists = file_exists($image_path);
                        ?>
                        <div class="current-image">
                            <h4>
                                <i class="fas fa-image"></i>
                                Gambar Saat Ini
                            </h4>
                            <?php if($image_exists): ?>
                            <img src="<?= $image_path ?>" 
                                 alt="Kamar <?= $room_data['room_number'] ?>" 
                                 class="preview-image">
                            <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: var(--gray-text);">
                                <i class="fas fa-image fa-2x" style="margin-bottom: 10px;"></i>
                                <p>Gambar tidak ditemukan</p>
                            </div>
                            <?php endif; ?>
                            <p style="color: var(--gray-text); font-size: 0.85rem; margin-top: 10px;">
                                Kosongkan upload jika tidak ingin mengubah gambar
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-toggle-on"></i>
                            Status Kamar
                        </label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="available" 
                                       <?= ($room_data['status'] ?? 'available') == 'available' ? 'checked' : '' ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-label">Tersedia</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="inactive"
                                       <?= ($room_data['status'] ?? '') == 'inactive' ? 'checked' : '' ?>>
                                <span class="radio-custom"></span>
                                <span class="radio-label">Tidak Aktif</span>
                            </label>
                        </div>
                        <small style="color: var(--gray-text); font-size: 0.85rem; margin-top: 5px;">
                            Kamar tidak aktif tidak akan ditampilkan di pemesanan
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-check"></i>
                            Ketersediaan
                        </label>
                        <div style="background: var(--primary-dark); padding: 15px; border-radius: 10px; border: 1px solid #30363d; margin-bottom: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                <span style="color: var(--light-text);">Siap dipesan</span>
                            </div>
                            <p style="color: var(--gray-text); font-size: 0.85rem;">
                                Kamar akan langsung tersedia untuk reservasi setelah disimpan
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="step-buttons" id="stepButtons">
                <button type="button" class="btn btn-prev" id="prevBtn" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Sebelumnya
                </button>
                <button type="button" class="btn btn-next" id="nextBtn">
                    Selanjutnya <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" name="save" class="btn btn-primary" id="submitBtn" style="display: none;">
                    <?php if($is_edit): ?>
                    <i class="fas fa-save"></i> Update Kamar
                    <?php else: ?>
                    <i class="fas fa-plus"></i> Simpan Kamar
                    <?php endif; ?>
                </button>
            </div>
        </form>
        
        <!-- Form Footer -->
        <div class="form-footer">
            <div style="color: var(--gray-text); font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i>
                Semua informasi kamar dapat diubah nanti
            </div>
            <div style="display: flex; gap: 15px;">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='rooms.php'">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Step Navigation
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const roomNumberInput = document.getElementById('room_number');
    const roomTypeSelect = document.getElementById('room_type');
    const priceInput = document.getElementById('price');
    const availabilityCheck = document.getElementById('availabilityCheck');
    let currentStep = 1;
    
    // Update step indicators
    function updateSteps() {
        steps.forEach((step, index) => {
            const stepNum = parseInt(step.dataset.step);
            
            step.classList.remove('active', 'completed');
            
            if (stepNum < currentStep) {
                step.classList.add('completed');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });
        
        // Show/hide step contents
        stepContents.forEach(content => {
            content.classList.remove('active');
            if (content.id === `step${currentStep}`) {
                content.classList.add('active');
            }
        });
        
        // Update buttons
        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = currentStep === 3 ? 'none' : 'inline-flex';
        submitBtn.style.display = currentStep === 3 ? 'inline-flex' : 'none';
        
        // Update step buttons text
        nextBtn.innerHTML = currentStep === 2 ? 
            'Langkah Terakhir <i class="fas fa-arrow-right"></i>' : 
            'Selanjutnya <i class="fas fa-arrow-right"></i>';
    }
    
    // Step click navigation
    steps.forEach(step => {
        step.addEventListener('click', function() {
            const stepNum = parseInt(this.dataset.step);
            if (stepNum <= currentStep) {
                currentStep = stepNum;
                updateSteps();
            }
        });
    });
    
    // Next button
    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep)) {
            currentStep++;
            updateSteps();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    
    // Previous button
    prevBtn.addEventListener('click', function() {
        currentStep--;
        updateSteps();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Step validation
    function validateStep(step) {
        let isValid = true;
        let errorMessage = '';
        
        switch(step) {
            case 1:
                const roomNumber = roomNumberInput.value.trim();
                const roomType = roomTypeSelect.value;
                
                if (!roomNumber) {
                    errorMessage = 'Nomor kamar harus diisi';
                    isValid = false;
                } else if (roomNumber.length > 10) {
                    errorMessage = 'Nomor kamar maksimal 10 karakter';
                    isValid = false;
                } else if (!roomType) {
                    errorMessage = 'Tipe kamar harus dipilih';
                    isValid = false;
                }
                break;
                
            case 2:
                const priceValue = priceInput.value.replace(/[^0-9]/g, '');
                
                if (!priceValue) {
                    errorMessage = 'Harga harus diisi';
                    isValid = false;
                } else if (parseInt(priceValue) < 100000) {
                    errorMessage = 'Harga minimal Rp 100.000';
                    isValid = false;
                } else if (parseInt(priceValue) > 10000000) {
                    errorMessage = 'Harga maksimal Rp 10.000.000';
                    isValid = false;
                }
                break;
                
            case 3:
                // Optional validation for step 3
                break;
        }
        
        if (!isValid && errorMessage) {
            alert('❌ ' + errorMessage);
        }
        
        return isValid;
    }
    
    // Initialize
    updateSteps();
    
    // ✅ FIX: AJAX untuk check room number availability
    function checkRoomNumberAvailability() {
        const roomNumber = roomNumberInput.value.trim();
        const roomType = roomTypeSelect.value;
        const isEdit = document.getElementById('is_edit').value;
        const currentId = document.getElementById('current_room_id').value;
        
        if (roomNumber.length < 1) {
            hideAvailabilityCheck();
            return;
        }
        
        if (!roomType) {
            showAvailabilityCheck('⚠️ Pilih tipe kamar terlebih dahulu', 'warning');
            return;
        }
        
        showAvailabilityCheck('<i class="fas fa-spinner fa-spin"></i> Memeriksa ketersediaan...', 'loading');
        
        // AJAX request
        const formData = new FormData();
        formData.append('room_number', roomNumber);
        formData.append('room_type', roomType);
        formData.append('is_edit', isEdit);
        formData.append('current_id', currentId);
        
        fetch('check_room_availability.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.available) {
                showAvailabilityCheck(data.message, 'success');
                roomNumberInput.classList.remove('invalid');
                roomNumberInput.classList.add('valid');
            } else {
                showAvailabilityCheck(data.message, 'error');
                roomNumberInput.classList.remove('valid');
                roomNumberInput.classList.add('invalid');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAvailabilityCheck('Gagal memeriksa ketersediaan', 'error');
        });
    }
    
    function showAvailabilityCheck(message, type) {
        if (!availabilityCheck) {
            // Create if not exists
            const checkDiv = document.createElement('div');
            checkDiv.id = 'availabilityCheck';
            checkDiv.style.marginTop = '5px';
            checkDiv.style.fontSize = '0.9rem';
            roomNumberInput.parentNode.appendChild(checkDiv);
            availabilityCheck = checkDiv;
        }
        
        let color = '';
        switch(type) {
            case 'loading': color = '#ffd700'; break;
            case 'success': color = '#28a745'; break;
            case 'error': color = '#dc3545'; break;
            case 'warning': color = '#ffc107'; break;
        }
        
        availabilityCheck.innerHTML = message;
        availabilityCheck.style.color = color;
        availabilityCheck.style.display = 'block';
    }
    
    function hideAvailabilityCheck() {
        if (availabilityCheck) {
            availabilityCheck.style.display = 'none';
            roomNumberInput.classList.remove('valid', 'invalid');
        }
    }
    
    // Event listeners for real-time checking
    if (roomNumberInput) {
        let timeout;
        roomNumberInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(checkRoomNumberAvailability, 800);
        });
    }
    
    if (roomTypeSelect) {
        roomTypeSelect.addEventListener('change', function() {
            checkRoomNumberAvailability();
        });
    }
    
    // Image Upload Preview
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('room_image');
    const previewContainer = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    
    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--primary-gold)';
            this.style.background = 'rgba(255, 215, 0, 0.1)';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#30363d';
            this.style.background = 'var(--primary-dark)';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#30363d';
            this.style.background = 'var(--primary-dark)';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewFile(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                previewFile(this.files[0]);
            }
        });
        
        function previewFile(file) {
            // Validate file type and size
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!validTypes.includes(file.type.toLowerCase())) {
                alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.');
                fileInput.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('Ukuran file maksimal 5MB.');
                fileInput.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    }
    
    // Auto-format price input
    if (priceInput) {
        // Format initial value
        if (priceInput.value) {
            const numericValue = priceInput.value.replace(/[^0-9]/g, '');
            if (numericValue) {
                priceInput.value = parseInt(numericValue).toLocaleString('id-ID');
            }
        }
        
        priceInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value) {
                e.target.value = parseInt(value).toLocaleString('id-ID');
            } else {
                e.target.value = '';
            }
        });
        
        priceInput.addEventListener('blur', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value && parseInt(value) < 100000) {
                e.target.value = '100.000';
                alert('Harga minimal Rp 100.000');
            }
        });
    }
    
    // Room type change effect on price
    if (roomTypeSelect && priceInput) {
        roomTypeSelect.addEventListener('change', function() {
            // Get current price value
            const currentValue = priceInput.value.replace(/[^0-9]/g, '');
            let minPrice;
            
            if (this.value === 'Premium') {
                minPrice = 750000;
            } else {
                minPrice = 350000;
            }
            
            // If price is empty or too low, set to minimum
            if (!currentValue || parseInt(currentValue) < minPrice) {
                priceInput.value = minPrice.toLocaleString('id-ID');
            }
        });
        
        // Trigger on page load if room type is already selected
        if (roomTypeSelect.value) {
            roomTypeSelect.dispatchEvent(new Event('change'));
        }
    }
    

// GANTI fungsi form submission di line ~1143:
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        // Hentikan submit default
        e.preventDefault();
        
        // Validasi dasar
        let hasErrors = false;
        
        // 1. Validasi Step 1
        if (!roomNumberInput.value.trim()) {
            alert('❌ Nomor kamar harus diisi!');
            roomNumberInput.focus();
            hasErrors = true;
        } 
        else if (!roomTypeSelect.value) {
            alert('❌ Tipe kamar harus dipilih!');
            roomTypeSelect.focus();
            hasErrors = true;
        }
        
        // 2. Validasi Step 2
        else if (!priceInput.value.trim()) {
            alert('❌ Harga harus diisi!');
            priceInput.focus();
            hasErrors = true;
        }
        
        // 3. Validasi duplikasi (jika masih invalid)
        else if (roomNumberInput.classList.contains('invalid')) {
            alert('❌ Nomor kamar sudah digunakan. Silakan gunakan nomor lain.');
            roomNumberInput.focus();
            hasErrors = true;
        }
        
        // Jika ada error, stop
        if (hasErrors) {
            // Kembalikan ke step 1
            currentStep = 1;
            updateSteps();
            return false;
        }
        
        // Jika semua valid, submit form
        console.log('Form submitted successfully');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        submitBtn.disabled = true;
        
        // Submit form secara manual
        setTimeout(() => {
            form.submit();
        }, 500);
    });
}
    
    // Initialize room number check if editing
    if (roomNumberInput.value && roomTypeSelect.value) {
        setTimeout(() => {
            checkRoomNumberAvailability();
        }, 500);
    }
});
    </script>
</body>
</html>
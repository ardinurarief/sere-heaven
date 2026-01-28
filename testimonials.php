<?php 
// START: Pindahkan semua PHP processing ke ATAS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config/db.php';

// Handle form submission - HARUS di ATAS sebelum output apapun
$message = '';

if (isset($_POST['submit_testimonial']) && isset($_SESSION['user_id'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = intval($_SESSION['user_id']);
    
    // Cek apakah user sudah kirim testimonial hari ini
    $check_today = mysqli_prepare($conn, 
        "SELECT id FROM testimonials WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    
    if ($check_today) {
        mysqli_stmt_bind_param($check_today, "i", $user_id);
        mysqli_stmt_execute($check_today);
        mysqli_stmt_store_result($check_today);
        
        $today_count = mysqli_stmt_num_rows($check_today);
        
        if ($today_count > 0) {
            $message = 'error_today';
        } elseif ($rating < 1 || $rating > 5) {
            $message = 'error_rating';
        } elseif (strlen($comment) < 20) {
            $message = 'error_length';
        } else {
            // Insert testimonial
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO testimonials (user_id, rating, comment, status) VALUES (?, ?, ?, 'pending')");
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iis", $user_id, $rating, $comment);
                
                if (mysqli_stmt_execute($stmt)) {
                    // REDIRECT LANGSUNG - tidak boleh ada output sebelumnya
                    header("Location: testimonials.php?success=1");
                    exit(); // ⚠️ PENTING: exit setelah redirect
                } else {
                    $message = 'error_insert';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'error_prepare';
            }
        }
        mysqli_stmt_close($check_today);
    }
}

// Jika redirect dengan success (setelah header.php)
if (isset($_GET['success'])) {
    $message = 'success';
}

// Ambil 3 testimonial yang approved & featured
$sql = "SELECT t.*, u.name 
        FROM testimonials t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.status = 'approved' 
        AND t.is_featured = 1 
        ORDER BY t.created_at DESC 
        LIMIT 3";
$result = mysqli_query($conn, $sql);
$testimonials = [];
if ($result) {
    $testimonials = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// SEKARANG baru include header.php
include 'includes/header.php';
?>

<div class="testimonials-page">
    <div class="testimonials-container">
        <div class="testimonials-header">
            <h1 class="elegant-title">Apa Kata Tamu Kami</h1>
            <p>Pengalaman nyata dari tamu yang pernah menginap di Sere Heaven</p>
        </div>

        <?php 
        // Tampilkan message SETELAH header
        switch($message):
            case 'error_today': ?>
                <div class="error-message">❌ Anda sudah mengirim testimonial hari ini. Coba lagi besok.</div>
                <?php break;
            case 'error_rating': ?>
                <div class="error-message">❌ Rating harus antara 1-5.</div>
                <?php break;
            case 'error_length': ?>
                <div class="error-message">❌ Komentar minimal 20 karakter.</div>
                <?php break;
            case 'error_insert': ?>
                <div class="error-message">❌ Gagal mengirim testimonial. Silakan coba lagi.</div>
                <?php break;
            case 'error_prepare': ?>
                <div class="error-message">❌ Error database. Silakan coba lagi.</div>
                <?php break;
            case 'success': ?>
                <div class="success-message">✅ Testimonial berhasil dikirim! Menunggu approval admin.</div>
                <?php break;
        endswitch; 
        ?>

        <div class="testimonials-grid">
            <?php if(empty($testimonials)): ?>
                <div class="no-testimonials">
                    <p>Belum ada testimonial yang ditampilkan.</p>
                </div>
            <?php else: ?>
                <?php foreach($testimonials as $testimonial): 
                    $stars = str_repeat('★', $testimonial['rating']) . str_repeat('☆', 5 - $testimonial['rating']);
                    $rating_class = $testimonial['rating'] <= 2 ? 'low-rating' : '';
                ?>
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="guest-avatar"><?= strtoupper(substr($testimonial['name'], 0, 1)) ?></div>
                        <div class="guest-info">
                            <h4><?= htmlspecialchars($testimonial['name']) ?></h4>
                            <p class="guest-stay">Verified Guest</p>
                        </div>
                        <div class="rating <?= $rating_class ?>"><?= $stars ?></div>
                    </div>
                    <div class="testimonial-content">
                        <p>"<?= htmlspecialchars($testimonial['comment']) ?>"</p>
                    </div>
                    
                    <?php if(!empty($testimonial['admin_reply'])): ?>
                    <div class="hotel-response">
                        <div class="response-header">
                            <span class="response-label">Respon dari Sere Heaven:</span>
                        </div>
                        <p class="response-text">"<?= htmlspecialchars($testimonial['admin_reply']) ?>"</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="testimonial-footer">
                        <span class="stay-date"><?= date('M Y', strtotime($testimonial['created_at'])) ?></span>
                        <span class="verified-badge">✅ Verified Stay</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add Testimonial Form -->
        <div class="add-testimonial">
            <h3>Bagikan Pengalaman Anda</h3>
            <p>Sudah menginap di Sere Heaven? Ceritakan pengalaman Anda</p>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php 
                // Cek apakah sudah kirim testimonial hari ini
                $user_id = intval($_SESSION['user_id']);
                $check_today = mysqli_prepare($conn, 
                    "SELECT id FROM testimonials WHERE user_id = ? AND DATE(created_at) = CURDATE()");
                mysqli_stmt_bind_param($check_today, "i", $user_id);
                mysqli_stmt_execute($check_today);
                mysqli_stmt_store_result($check_today);
                $already_submitted = mysqli_stmt_num_rows($check_today) > 0;
                mysqli_stmt_close($check_today);
                ?>
                
                <?php if(!$already_submitted): ?>
                <form method="post" class="testimonial-form" id="testimonial-form" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label>Rating (1-5 bintang)</label>
                        <div class="rating-input">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-rating="<?= $i ?>">☆</span>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating-value" value="0" required>
                        </div>
                        <div class="rating-hint">
                            <span class="hint-text hint-1">😞 Sangat Buruk</span>
                            <span class="hint-text hint-2">😐 Cukup</span>
                            <span class="hint-text hint-3">🙂 Baik</span>
                            <span class="hint-text hint-4">😊 Sangat Baik</span>
                            <span class="hint-text hint-5">🤩 Luar Biasa</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea name="comment" placeholder="Ceritakan pengalaman menginap Anda... (Minimal 20 karakter)" 
                                  rows="4" minlength="20" required id="comment-field"></textarea>
                        <div id="char-count" style="font-size: 0.85rem; color: #8b949e; text-align: right; margin-top: 5px;">
                            Minimal 20 karakter
                        </div>
                    </div>
                    <div class="form-note">
                        <span class="note-icon">ℹ️</span>
                        <span>Testimonial akan ditinjau admin sebelum ditampilkan</span>
                    </div>
                    <button type="submit" name="submit_testimonial" class="submit-btn" id="submit-btn">Kirim Testimonial</button>
                </form>
                
                <script>
                // Character counter
                document.getElementById('comment-field').addEventListener('input', function(e) {
                    const count = e.target.value.length;
                    const charCount = document.getElementById('char-count');
                    charCount.textContent = count + '/20 karakter' + (count < 20 ? ' (minimal 20)' : ' ✓');
                    charCount.style.color = count >= 20 ? '#51cf66' : count >= 10 ? '#ffd93d' : '#ff6b6b';
                });
                
                // Form validation
                function validateForm() {
                    const rating = document.getElementById('rating-value').value;
                    const comment = document.getElementById('comment-field').value;
                    
                    if (rating == 0) {
                        alert('Mohon berikan rating terlebih dahulu');
                        return false;
                    }
                    
                    if (comment.length < 20) {
                        alert('Review minimal 20 karakter');
                        return false;
                    }
                    
                    return true;
                }
                </script>
                
                <?php else: ?>
                <div class="info-prompt">
                    <p>📝 Anda sudah mengirim testimonial hari ini. Coba lagi besok.</p>
                    <p><small>Admin akan meninjau testimonial Anda sebelum ditampilkan.</small></p>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="login-prompt">
                    <p>Login untuk membagikan pengalaman Anda</p>
                    <a href="user/login.php" class="btn-primary">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Testimonials Page */
.testimonials-page {
    min-height: 100vh;
    background: linear-gradient(rgba(13, 17, 23, 0.9), rgba(13, 17, 23, 0.95)), 
                url('assets/img/hotelbg2.webp') center/cover no-repeat fixed;
    padding: 120px 2rem 4rem;
}

.testimonials-container {
    max-width: 1200px;
    margin: 0 auto;
}

.testimonials-header {
    text-align: center;
    margin-bottom: 4rem;
}

.testimonials-header h1 {
    color: #ffd700;
    font-size: 3rem;
    margin-bottom: 1rem;
}

.testimonials-header p {
    color: #b0b7c3;
    font-size: 1.2rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Messages */
.success-message {
    background: rgba(81, 207, 102, 0.1);
    border: 1px solid rgba(81, 207, 102, 0.3);
    color: #51cf66;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
}

.error-message {
    background: rgba(255, 107, 107, 0.1);
    border: 1px solid rgba(255, 107, 107, 0.3);
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
}

/* Testimonials Grid */
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 4rem;
}

.testimonial-card {
    background: rgba(25, 30, 40, 0.8);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 215, 0, 0.4);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.testimonial-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

.guest-avatar {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 215, 0, 0.1);
    border-radius: 50%;
    font-size: 1.8rem;
    font-weight: bold;
    color: #ffd700;
}

.guest-info h4 {
    color: #ffffff;
    margin-bottom: 0.3rem;
    font-size: 1.1rem;
}

.guest-stay {
    color: #b0b7c3;
    font-size: 0.9rem;
}

.rating {
    font-size: 1.2rem;
    margin-left: auto;
    color: #ffd700;
}

.low-rating {
    color: #ff6b6b;
}

.testimonial-content {
    color: #b0b7c3;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-style: italic;
}

/* Hotel Response */
.hotel-response {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(255, 215, 0, 0.05);
    border-radius: 10px;
    border: 1px solid rgba(255, 215, 0, 0.1);
}

.response-header {
    margin-bottom: 0.5rem;
}

.response-label {
    color: #ffd700;
    font-weight: 600;
    font-size: 0.9rem;
}

.response-text {
    color: #b0b7c3;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

.testimonial-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #8b949e;
    font-size: 0.85rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 1rem;
}

.verified-badge {
    color: #51cf66;
    background: rgba(81, 207, 102, 0.1);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
}

/* Add Testimonial Section */
.add-testimonial {
    background: rgba(25, 30, 40, 0.7);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
}

.add-testimonial h3 {
    color: #ffd700;
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.add-testimonial p {
    color: #b0b7c3;
    margin-bottom: 2rem;
}

.testimonial-form {
    max-width: 600px;
    margin: 0 auto;
    text-align: left;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    color: #ffffff;
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.rating-input {
    display: flex;
    gap: 0.5rem;
    font-size: 2rem;
    color: #8b949e;
    margin-bottom: 0.5rem;
}

.rating-input .star {
    cursor: pointer;
    transition: all 0.3s ease;
}

.rating-input .star:hover,
.rating-input .star.active {
    color: #ffd700;
    transform: scale(1.2);
}

.rating-hint {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
}

.hint-text {
    font-size: 0.85rem;
    color: #8b949e;
    display: none;
}

.hint-text.active {
    display: block;
    color: #ffd700;
    font-weight: 500;
}

.testimonial-form textarea {
    width: 100%;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 10px;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    resize: vertical;
    font-size: 1rem;
}

.testimonial-form textarea:focus {
    outline: none;
    border-color: #ffd700;
    background: rgba(255, 215, 0, 0.05);
}

.form-note {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #b0b7c3;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
    padding: 0.8rem;
    background: rgba(255, 215, 0, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 215, 0, 0.1);
}

.note-icon {
    color: #ffd700;
}

.submit-btn {
    background: transparent;
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.4);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 1rem;
}

.submit-btn:hover {
    background: rgba(255, 215, 0, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

/* Prompts */
.login-prompt,
.info-prompt,
.booking-prompt {
    padding: 2rem;
    background: rgba(255, 215, 0, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(255, 215, 0, 0.1);
    text-align: center;
}

.login-prompt p,
.info-prompt p,
.booking-prompt p {
    color: #b0b7c3;
    margin-bottom: 1.5rem;
}

.booking-prompt p {
    color: #ff6b6b;
}

.info-prompt {
    background: rgba(13, 110, 253, 0.1);
    border-color: rgba(13, 110, 253, 0.2);
}

.info-prompt p {
    color: #0d6efd;
}

.btn-primary {
    display: inline-block;
    background: transparent;
    color: #ffd700;
    border: 1px solid rgba(255, 215, 0, 0.4);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: rgba(255, 215, 0, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

.no-testimonials {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(255, 215, 0, 0.1);
    color: #b0b7c3;
}

/* Responsive */
@media (max-width: 1024px) {
    .testimonials-header h1 {
        font-size: 2.5rem;
    }
    
    .testimonials-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .testimonials-page {
        padding: 100px 1rem 2rem;
    }
    
    .testimonials-header h1 {
        font-size: 2rem;
    }
    
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .add-testimonial {
        padding: 1.5rem;
    }
    
    .rating-input {
        font-size: 1.8rem;
    }
    
    .rating-hint {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .hint-text {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .testimonials-header h1 {
        font-size: 1.8rem;
    }
    
    .testimonial-card {
        padding: 1.5rem;
    }
    
    .guest-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .testimonial-footer {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .rating-input {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Star rating script
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingValue = document.getElementById('rating-value');
    const hintTexts = document.querySelectorAll('.hint-text');
    
    // Set default hint untuk rating 3
    if (hintTexts.length > 2) {
        hintTexts[2].classList.add('active');
    }
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            ratingValue.value = rating;
            
            // Update stars
            stars.forEach(s => {
                s.classList.remove('active', 'hover');
                if (parseInt(s.getAttribute('data-rating')) <= rating) {
                    s.classList.add('active');
                }
            });
            
            // Update hint text
            hintTexts.forEach(hint => hint.classList.remove('active'));
            const hintIndex = rating - 1;
            if (hintTexts[hintIndex]) {
                hintTexts[hintIndex].classList.add('active');
            }
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            stars.forEach(s => {
                s.classList.remove('hover');
                if (parseInt(s.getAttribute('data-rating')) <= rating) {
                    s.classList.add('hover');
                }
            });
        });
        
        star.addEventListener('mouseout', function() {
            stars.forEach(s => s.classList.remove('hover'));
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
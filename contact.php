<?php include 'includes/header.php'; ?>

<div class="contact-page">
    <div class="contact-container">
        <div class="contact-header">
            <h1 class="elegant-title">Contact Us</h1>
            <p>Hubungi kami untuk informasi lebih lanjut</p>
        </div>

        <div class="contact-content">
            <!-- Informasi Kontak -->
          <div class="info-item">
    <div class="info-icon">
    <img src="https://cdn-icons-png.flaticon.com/128/1865/1865269.png" alt="Telepon Icon" style="width: 45px; height: 45px;">
    </div>
    <div class="info-text">
        <h3>Lokasi</h3>
        <p>Victoria Dockside, 18 Salisbury Rd</p>
        <p>Tsim Sha Tsui, Hong Kong</p>
    </div>
</div>

                <div class="info-item">
                    <div class="info-icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/3670/3670228.png" alt="Telepon Icon" style="width: 45px; height: 45px;">
                    </div>
                <div class="info-text">
                    <h3>Telepon</h3>
                    <p>+62 812-3456-7890</p>
                </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/15047/15047587.png" alt="Email Icon" style="width: 45px; height: 45px;">
                    </div>
                <div class="info-text">
                    <h3>Email</h3>
                    <p>info@sereheaven.com</p>
                    <p>reservation@sereheaven.com</p>
                </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                    <img src="https://cdn-icons-png.flaticon.com/128/1040/1040227.png" alt="Jam Icon" style="width: 45px; height: 45px;">
                    </div>
                <div class="info-text">
                    <h3>Jam Operasional</h3>
                        <p>24/7 Front Desk</p>
                </div>
                </div>
            </div>

           <!-- Google Maps -->
            <!-- Google Maps -->
<div class="contact-map">
    <h3>Lokasi Rosewood Hong Kong</h3>
    <div class="map-container">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3691.176648180929!2d114.16319457499695!3d22.304089579684894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x340400c7df4d242d%3A0xccf5c246d963c004!2sRosewood%20Hong%20Kong!5e0!3m2!1sen!2sid!4v1704679200000!5m2!1sen!2sid" 
            width="100%" 
            height="400" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Rosewood Hong Kong Location">
        </iframe>
    </div>
    <p style="text-align: center; margin-top: 1rem; color: #b0b7c3;">
        Victoria Dockside, 18 Salisbury Rd, Tsim Sha Tsui, Hong Kong
    </p>
</div>
        </div>
    </div>
</div>

<style>
.contact-page {
    min-height: 100vh;
    background: linear-gradient(rgba(13, 17, 23, 0.9), rgba(13, 17, 23, 0.95)), 
                url('assets/img/cs1.jpg') center/cover no-repeat fixed;
    padding: 120px 2rem 4rem;
}

.contact-container {
    max-width: 1200px;
    margin: 0 auto;
    background: rgba(25, 30, 40, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.contact-header {
    text-align: center;
    margin-bottom: 3rem;
}

.contact-header h1 {
    color: #ffd700;
    font-size: 3rem;
    margin-bottom: 1rem;
}

.contact-header p {
    color: #b0b7c3;
    font-size: 1.1rem;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 215, 0, 0.1);
    transition: all 0.3s ease;
}

.info-item:hover {
    border-color: rgba(255, 215, 0, 0.3);
    transform: translateY(-5px);
    background: rgba(255, 215, 0, 0.05);
}

.info-icon {
    font-size: 2rem;
    color: #ffd700;
}

.info-text h3 {
    color: #ffffff;
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
}

.info-text p {
    color: #b0b7c3;
    margin-bottom: 0.3rem;
    font-size: 1rem;
}

.contact-map h3 {
    color: #ffffff;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    text-align: center;
    padding-top: 20px;
}

.map-container {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255, 215, 0, 0.3);
}

/* Responsive */
@media (max-width: 1024px) {
    .contact-content {
        grid-template-columns: 1fr;
    }
    
    .contact-header h1 {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .contact-container {
        padding: 2rem;
    }
    
    .contact-page {
        padding: 100px 1rem 2rem;
    }
    
    .info-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .contact-header h1 {
        font-size: 2rem;
    }
    
    .contact-container {
        padding: 1.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
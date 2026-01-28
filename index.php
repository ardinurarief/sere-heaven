<?php include 'includes/header.php'; ?>

<!-- SECTION 1 : HERO / SLIDER -->
<section class="home">
    <div class="hero reveal">
        <h2>Selamat Datang di <span>Sere Heaven</span></h2>
        <p>
            Hotel modern dengan sistem reservasi mudah dan cepat.
            Nikmati kenyamanan terbaik bersama kami.
        </p>

        <a href="rooms.php" class="btn-primary">Cari Kamar</a>
    </div>
</section>

<!-- SECTION 2 -->
<section class="section light">
    <div class="content reveal reveal-left">
        <h2>Kenyamanan Premium</h2>
        <p>
            Setiap kamar dirancang untuk memberikan kenyamanan maksimal
            bagi setiap tamu yang menginap.
        </p>
    </div>
</section>

<!-- SECTION 3 -->
<section class="section dark">
    <div class="content reveal reveal-right">
        <h2>Lokasi Strategis</h2>
        <p>
            Terletak di lokasi strategis dengan akses mudah ke berbagai
            fasilitas kota.
        </p>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ========================= */
    /* HERO BACKGROUND SLIDER */
    /* ========================= */
    const hero = document.querySelector(".home");

    const images = [
        "assets/img/hotelgeser1.png",
        "assets/img/hotelgeser2.jpeg",
        "assets/img/hotelgeser3.png"
    ];

    let index = 0;
    let loadedCount = 0;

    // Preload semua gambar dengan callback
    images.forEach(src => {
        const img = new Image();
        img.onload = () => {
            loadedCount++;
            // Jika SEMUA gambar sudah loaded, mulai slider
            if (loadedCount === images.length) {
                startSlider();
            }
        };
        img.onerror = () => {
            console.error("Gagal load gambar:", src);
            loadedCount++;
            if (loadedCount === images.length) {
                startSlider();
            }
        };
        img.src = src;
    });

    function startSlider() {
        // Set gambar pertama secara EKSPLISIT
        hero.style.backgroundImage = 
            `linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.7)), url(${images[0]})`;
        
        // Set opacity 1 (kalau perlu)
        hero.style.opacity = "1";
        
        // Mulai interval setelah 5 detik
        setTimeout(() => {
            setInterval(() => {
                index = (index + 1) % images.length;
                hero.style.backgroundImage = 
                    `linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.7)), url(${images[index]})`;
            }, 5000);
        }, 5000);
    }

    // Jika tidak ada gambar yang load dalam 3 detik, tetap mulai
    setTimeout(() => {
        if (loadedCount < images.length) {
            console.warn("Gambar belum semua load, tapi slider tetap dimulai");
            startSlider();
        }
    }, 3000);

    /* ========================= */
    /* SCROLL REVEAL */
    /* ========================= */
    const reveals = document.querySelectorAll(".reveal");

    function revealOnScroll() {
        reveals.forEach(el => {
            const windowHeight = window.innerHeight;
            const elementTop = el.getBoundingClientRect().top;
            const offset = 120;

            if (elementTop < windowHeight - offset) {
                el.classList.add("active");
            }
        });
    }

    window.addEventListener("scroll", revealOnScroll);
    revealOnScroll();

});
</script>


<?php include 'includes/footer.php'; ?>

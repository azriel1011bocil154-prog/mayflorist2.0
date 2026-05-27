<?php
// tentang.php

$page_title = 'Tentang Kami — MayFlorist';
$active_nav = 'tentang';

include 'includes/header.php';
?>

<section class="about-hero">
  <div class="page-wrapper reveal-up">
    <span class="about-label">Tentang Kami</span>

    <h1>
      Merangkai bunga dengan penuh makna
      untuk setiap momen spesial.
    </h1>

    <p>
      MayFlorist hadir untuk memberikan pengalaman terbaik dalam
      mengirim kebahagiaan melalui rangkaian bunga yang elegan,
      segar, dan penuh arti.
    </p>
  </div>
</section>

<section class="about-section">
  <div class="page-wrapper">

    <div class="about-grid">

      <div class="about-image reveal-left">
        <img src="assets/images/about-florist.jpeg" alt="Tentang MayFlorist">
      </div>

      <div class="about-content reveal-right">

        <span class="section-mini-title">Cerita Kami</span>

        <h2>
          Bukan sekadar bunga,<br>
          tapi bentuk perhatian.
        </h2>

        <p>
          MayFlorist dimulai dari kecintaan terhadap seni merangkai bunga
          dan keinginan untuk membantu setiap orang menyampaikan
          perasaan mereka melalui hadiah yang indah.
        </p>

        <p>
          Kami percaya bahwa setiap bunga memiliki cerita,
          dan setiap rangkaian dibuat dengan perhatian penuh
          untuk menghadirkan kesan yang hangat dan berkesan.
        </p>

        <div class="about-stats">

          <div class="stat-box reveal-up" style="transition-delay: 0ms;">
            <h3>1.200+</h3>
            <span>Pesanan Selesai</span>
          </div>

          <div class="stat-box reveal-up" style="transition-delay: 100ms;">
            <h3>500+</h3>
            <span>Pelanggan Puas</span>
          </div>

          <div class="stat-box reveal-up" style="transition-delay: 200ms;">
            <h3>4.9★</h3>
            <span>Rating Pelanggan</span>
          </div>

        </div>

      </div>

    </div>

  </div>
</section>

<section class="about-features-section">
  <div class="page-wrapper">

    <div class="section-header center reveal-up">
      <h2>Kenapa Memilih MayFlorist?</h2>
      <p>
        Kami memberikan kualitas terbaik untuk setiap rangkaian bunga.
      </p>
    </div>

    <div class="features-grid">

      <div class="feature-card reveal-up" style="transition-delay: 0ms;">
        <div class="feature-icon">🌸</div>
        <h3>Bunga Segar</h3>
        <p>
          Menggunakan bunga pilihan yang segar
          dan berkualitas setiap hari.
        </p>
      </div>

      <div class="feature-card reveal-up" style="transition-delay: 100ms;">
        <div class="feature-icon">🚚</div>
        <h3>Pengiriman Cepat</h3>
        <p>
          Tersedia layanan same-day delivery
          untuk area tertentu.
        </p>
      </div>

      <div class="feature-card reveal-up" style="transition-delay: 200ms;">
        <div class="feature-icon">🎀</div>
        <h3>Rangkaian Elegan</h3>
        <p>
          Dirangkai langsung oleh florist
          berpengalaman dan profesional.
        </p>
      </div>

    </div>

  </div>
</section>

<section class="about-cta">
  <div class="page-wrapper reveal-zoom">

    <h2>
      Temukan rangkaian bunga terbaik<br>
      untuk orang tersayang.
    </h2>

    <p>
      Jadikan setiap momen terasa lebih spesial bersama MayFlorist.
    </p>

    <a href="katalog.php" class="btn btn-primary">
      Lihat Koleksi
    </a>

  </div>
</section>

<?php include 'includes/footer.php'; ?>


<script>
document.addEventListener("DOMContentLoaded", function() {
  const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.1 // Animasi terpicu saat 10% bagian elemen masuk layar
  };

  const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        observer.unobserve(entry.target); // Hanya jalankan animasi satu kali
      }
    });
  }, observerOptions);

  // Ambil semua elemen dengan class efek reveal
  const elementsToReveal = document.querySelectorAll(".reveal-up, .reveal-zoom, .reveal-left, .reveal-right");
  elementsToReveal.forEach(el => observer.observe(el));
});
</script>


<style>
/* Proteksi agar pergeseran sumbu X/Y saat transisi tidak merusak layout browser */
body {
  overflow-x: hidden;
}

/* ─────────────────────────────────────────
   CSS MAGIC SCROLL REVEAL ANIMATION ENGINE
   ───────────────────────────────────────── */
.reveal-up {
  opacity: 0;
  transform: translateY(45px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-zoom {
  opacity: 0;
  transform: scale(0.94) translateY(10px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-left {
  opacity: 0;
  transform: translateX(-50px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-right {
  opacity: 0;
  transform: translateX(50px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* Saat elemen masuk ke dalam viewport */
.reveal-up.in-view,
.reveal-zoom.in-view,
.reveal-left.in-view,
.reveal-right.in-view {
  opacity: 1;
  transform: translate(0) scale(1);
}

/* Isolasikan properti transisi hover bawaan agar tidak bertabrakan dengan base reveal */
.stat-box {
  transition-property: transform, box-shadow;
  transition-duration: 0.3s;
  transition-timing-function: ease;
}
.feature-card {
  transition-property: transform, box-shadow;
  transition-duration: 0.3s;
  transition-timing-function: ease;
}


/* ─────────────────────────
    HERO
───────────────────────── */
.about-hero {
  padding: 90px 0;
  background:
    linear-gradient(
      rgba(249,241,238,.88),
      rgba(249,241,238,.88)
    ),
    url('assets/images/about-bg.jpg') center/cover;
  text-align: center;
}

.about-label {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 999px;
  background: var(--rose-light);
  color: var(--rose-dark);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 18px;
}

.about-hero h1 {
  max-width: 760px;
  margin: 0 auto 18px;
  font-size: 46px;
  line-height: 1.2;
  color: var(--bark);
}

.about-hero p {
  max-width: 620px;
  margin: auto;
  color: var(--muted);
  font-size: 15px;
  line-height: 1.8;
}

/* ─────────────────────────
    SECTION STORY
───────────────────────── */
.about-section {
  padding: 80px 0;
}

.about-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 50px;
  align-items: center;
}

.about-image img {
  width: 100%;
  border-radius: 16px;
  object-fit: cover;
  box-shadow: 0 12px 36px rgba(61,44,40,.12);
}

.section-mini-title {
  display: inline-block;
  color: var(--rose);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.about-content h2 {
  font-size: 34px;
  line-height: 1.3;
  margin-bottom: 18px;
}

.about-content p {
  color: var(--muted);
  line-height: 1.9;
  margin-bottom: 14px;
  font-size: 14px;
}

/* ─────────────────────────
    STATS
───────────────────────── */
.about-stats {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 16px;
  margin-top: 28px;
}

.stat-box {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 22px 16px;
  text-align: center;
}

.stat-box.in-view:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 28px rgba(61,44,40,.1);
}

.stat-box h3 {
  color: var(--rose);
  font-size: 26px;
  margin-bottom: 6px;
}

.stat-box span {
  font-size: 13px;
  color: var(--muted);
}

/* ─────────────────────────
    FEATURES
───────────────────────── */
.about-features-section {
  padding: 80px 0;
  background: var(--white);
}

.section-header.center {
  text-align: center;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 24px;
  margin-top: 40px;
}

.feature-card {
  background: var(--cream);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 32px 26px;
  text-align: center;
}

.feature-card.in-view:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 36px rgba(61,44,40,.1);
}

.feature-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 18px;
  border-radius: 50%;
  background: var(--rose-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
}

.feature-card h3 {
  margin-bottom: 10px;
  font-size: 20px;
}

.feature-card p {
  color: var(--muted);
  font-size: 14px;
  line-height: 1.7;
}

/* ─────────────────────────
    CTA
───────────────────────── */
.about-cta {
  padding: 90px 0;
  text-align: center;
}

.about-cta h2 {
  max-width: 700px;
  margin: auto;
  font-size: 38px;
  line-height: 1.3;
  margin-bottom: 14px;
}

.about-cta p {
  color: var(--muted);
  margin-bottom: 28px;
}

/* ─────────────────────────
    RESPONSIVE MEDIA QUERIES
───────────────────────── */
@media (max-width: 900px) {
  .about-grid { grid-template-columns: 1fr; gap: 36px; }
  .features-grid { grid-template-columns: 1fr; gap: 20px; }
  .about-stats { grid-template-columns: 1fr; gap: 12px; }
  
  /* Di Tablet/HP, matikan delay agar elemen yang menumpuk vertikal langsung meluncur mulus */
  .reveal-up, .reveal-zoom, .reveal-left, .reveal-right {
    transition-delay: 0ms !important;
    transform: translateY(30px) !important; /* Standarisasi arah gerak saat di HP */
  }
}

@media (max-width: 768px) {
  .about-hero { padding: 70px 0; }
  .about-hero h1 { font-size: 34px; }
  .about-content h2 { font-size: 28px; }
  .about-cta h2 { font-size: 30px; }
}
</style>
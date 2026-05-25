<?php
// tentang.php

$page_title = 'Tentang Kami — MayFlorist';
$active_nav = 'tentang';

include 'includes/header.php';
?>

<!-- ── HERO ── -->
<section class="about-hero">
  <div class="page-wrapper">
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

<!-- ── STORY ── -->
<section class="about-section">
  <div class="page-wrapper">

    <div class="about-grid">

      <!-- Image -->
      <div class="about-image">
        <img src="assets/images/about-florist.jpeg" alt="Tentang MayFlorist">
      </div>

      <!-- Text -->
      <div class="about-content">

        <span class="section-mini-title">Cerita Kami</span>

        <h2>
          Bukan sekadar bunga,
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

          <div class="stat-box">
            <h3>1.200+</h3>
            <span>Pesanan Selesai</span>
          </div>

          <div class="stat-box">
            <h3>500+</h3>
            <span>Pelanggan Puas</span>
          </div>

          <div class="stat-box">
            <h3>4.9★</h3>
            <span>Rating Pelanggan</span>
          </div>

        </div>

      </div>

    </div>

  </div>
</section>

<!-- ── FEATURES ── -->
<section class="about-features-section">
  <div class="page-wrapper">

    <div class="section-header center">
      <h2>Kenapa Memilih MayFlorist?</h2>
      <p>
        Kami memberikan kualitas terbaik untuk setiap rangkaian bunga.
      </p>
    </div>

    <div class="features-grid">

      <div class="feature-card">
        <div class="feature-icon">🌸</div>

        <h3>Bunga Segar</h3>

        <p>
          Menggunakan bunga pilihan yang segar
          dan berkualitas setiap hari.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon">🚚</div>

        <h3>Pengiriman Cepat</h3>

        <p>
          Tersedia layanan same-day delivery
          untuk area tertentu.
        </p>
      </div>

      <div class="feature-card">
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

<!-- ── CTA ── -->
<section class="about-cta">
  <div class="page-wrapper">

    <h2>
      Temukan rangkaian bunga terbaik
      untuk orang tersayang.
    </h2>

    <p>
      Jadikan setiap momen terasa lebih spesial bersama Fleuriste.
    </p>

    <a href="katalog.php" class="btn btn-primary">
      Lihat Koleksi
    </a>

  </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>

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
   SECTION
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
  transition: .25s;
}

.stat-box:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 30px rgba(61,44,40,.08);
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
  transition: .25s;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 14px 34px rgba(61,44,40,.08);
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
   RESPONSIVE
───────────────────────── */

@media (max-width: 900px) {

  .about-grid {
    grid-template-columns: 1fr;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .about-stats {
    grid-template-columns: 1fr;
  }

}

@media (max-width: 768px) {

  .about-hero {
    padding: 70px 0;
  }

  .about-hero h1 {
    font-size: 34px;
  }

  .about-content h2 {
    font-size: 28px;
  }

  .about-cta h2 {
    font-size: 30px;
  }

}

</style>
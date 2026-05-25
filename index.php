<?php
// index.php — Halaman Home

$page_title = 'MayFlorist — Buket Bunga Terbaik untuk Setiap Momen';
$active_nav = 'home';

include 'includes/header.php';
include 'includes/products.php';

/* ── AMANIN DATA ── */
$products = $products ?? [];

/* ── SORT BESTSELLER (AMAN) ── */
usort($products, function ($a, $b) {
  return ($b['reviews'] ?? 0) <=> ($a['reviews'] ?? 0);
});

/* ── AMBIL 4 PRODUK TERATAS ── */
$bestsellers = array_slice($products, 0, 4);

/* ── TESTIMONI ── */
$testimonials = [
  ['name' => 'Dewi Anggraini', 'stars' => 5, 'review' => 'Buket mawarnya cantik banget! Suami saya terkejut dan langsung suka. Pengiriman tepat waktu, bunga masih sangat segar.'],
  ['name' => 'Rizky Pratama', 'stars' => 5, 'review' => 'Pesan buket wisuda untuk adek, hasilnya melebihi ekspektasi! Bunganya besar, fresh banget, dan packingnya rapi.'],
  ['name' => 'Sari Maulida', 'stars' => 5, 'review' => 'Saya pesan custom bouquet untuk pernikahan teman, dan hasilnya luar biasa! Tim Fleuriste sangat responsif dan profesional.'],
];
?>

<!-- ── HERO SLIDER ── -->
<section class="hero-slider">

  <div class="slide active" style="background-image: url('assets/images/hero/hero1.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">RANGKAIAN BUNGA ISTIMEWA</span>
      <h1>Kejutan Spesial,<br>Untuk Momen Berharga</h1>
      <p>Buket bunga eksklusif yang dirangkai dengan kehangatan dan ketulusan, sempurna untuk menyampaikan rasa sayang yang tak terucap.</p>

      <div class="hero-features">
        <span>🌸 Bunga Segar Pilihan</span>
        <span>🚚 Pengiriman Cepat & Aman</span>
        <span>❤️ Dirangkai dengan Cinta</span>
      </div>

      <a href="katalog.php" class="btn btn-primary">Lihat Produk</a>
    </div>
  </div>

  <div class="slide" style="background-image: url('assets/images/hero/hero2.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">KOLEKSI TERBARU</span>
      <h1>Pesona Keindahan Di Setiap Kelopak</h1>
      <p>Temukan berbagai pilihan desain buket modern untuk merayakan hari kelulusan, ulang tahun, dan anniversary.</p>
      <a href="katalog.php" class="btn btn-primary">Lihat Katalog</a>
    </div>
  </div>

  <div class="slide" style="background-image: url('assets/images/hero/hero3.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">CUSTOM BOUQUET</span>
      <h1>Ekspresikan Perasaan Anda Sendiri</h1>
      <p>Pilih tipe bunga, warna kertas pembungkus, dan kartu ucapan sesuai dengan keinginan unik Anda.</p>
      <a href="katalog.php" class="btn btn-primary">Pesan Custom</a>
    </div>
  </div>

  <button class="prev" onclick="changeSlide(-1)">❮</button>
  <button class="next" onclick="changeSlide(1)">❯</button>

</section>

<div class="divider" style="margin:0"></div>

<!-- ── PRODUK TERLARIS ── -->
<section class="home-section">
  <div class="page-wrapper">
    <div class="section-header">
      <h2>Produk Terlaris</h2>
    </div>

    <div class="products-grid-4">
      <?php foreach ($bestsellers as $p): ?>

        <?php
          $name   = $p['name'] ?? 'Produk';
          $price  = $p['price'] ?? 0;
          $slug   = $p['slug'] ?? '#';
          $image  = $p['image'] ?? '';
          $badge  = $p['badge'] ?? '';
          $rating = $p['rating'] ?? 0;
        ?>

        <div class="product-card">
          <a href="detail.php?slug=<?= urlencode($p['slug'] ?? '') ?>">
            <div class="card-img">

              <?php if (!empty($image) && file_exists($image)): ?>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>">
              <?php else: ?>
                <div class="img-placeholder">🌸</div>
              <?php endif; ?>

              <?php if (!empty($badge)): ?>
                <span class="card-badge"><?= htmlspecialchars($badge) ?></span>
              <?php endif; ?>

            </div>
          </a>

          <div class="card-body">
            <div class="card-name"><?= htmlspecialchars($name) ?></div>
            <div class="card-price"><?= formatRupiah($price) ?></div>

            <div class="card-rating">
              <span class="stars"><?= str_repeat('★', floor($rating)) ?></span>
              <span><?= $rating ?></span>
            </div>

            <a href="detail.php?slug=<?= urlencode($p['slug'] ?? '') ?>" class="btn btn-primary btn-sm btn-full">
              Lihat Detail
            </a>
          </div>
        </div>

      <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:28px;">
      <a href="katalog.php" class="btn btn-outline" style="padding:10px 36px;">
        Lihat Semua Produk
      </a>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ── TESTIMONI ── -->
<section class="home-section" style="padding-top:0">
  <div class="page-wrapper">
    <div class="section-header">
      <h2>Testimoni Pelanggan</h2>
    </div>

    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-header">
          <div class="testi-avatar">👤</div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
            <div class="stars"><?= str_repeat('★', $t['stars']) ?></div>
          </div>
        </div>
        <p class="testi-text"><?= htmlspecialchars($t['review']) ?></p>
        <div class="testi-product-link">
          <a href="#">🛍 Lihat Produk</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="testi-actions">
      <button class="btn btn-outline" onclick="openTestimonials()">
        Lihat Semua Testimoni
      </button>
    </div>

  </div>
</section>

<!-- MODAL TESTIMONI -->
<div id="testiModal" class="testi-modal">
  <div class="testi-modal-content">

    <span class="close-modal" onclick="closeTestimonials()">&times;</span>

    <h2>Semua Testimoni Pelanggan</h2>

    <div class="all-testi-list">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card">

        <div class="testi-header">
          <div class="testi-avatar">👤</div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
            <div class="stars"><?= str_repeat('★', $t['stars']) ?></div>
          </div>
        </div>

        <p class="testi-text"><?= htmlspecialchars($t['review']) ?></p>

        <div class="testi-product-link">
          <a href="#">🛍 Lihat Produk</a>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll(".slide");

function showSlide(index) {
  slides.forEach(s => s.classList.remove("active"));
  slides[index].classList.add("active");
}

function changeSlide(step) {
  currentSlide += step;

  if (currentSlide >= slides.length) currentSlide = 0;
  if (currentSlide < 0) currentSlide = slides.length - 1;

  showSlide(currentSlide);
}

setInterval(() => {
  changeSlide(1);
}, 5000);

function openTestimonials() {
  document.getElementById("testiModal").style.display = "flex";
}

function closeTestimonials() {
  document.getElementById("testiModal").style.display = "none";
}

window.onclick = function(e) {
  const modal = document.getElementById("testiModal");
  if (e.target == modal) modal.style.display = "none";
}
</script>

<style>
/* ── HOME SPECIFIC ── */
.hero-slider {
  position: relative;
  height: 85vh; /* Mengurangi tinggi sedikit agar lebih proporsional dari 100vh */
  overflow: hidden;
  background-color: #f0f8ff;
}

/* Setiap slide jadi background full */
.slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: right center; /* Fokus gambar buket bunga digeser ke kanan */
  display: flex;
  align-items: center;
  padding: 0 10%; /* Menggunakan persentase agar lebih simetris di berbagai layar */
  opacity: 0;
  transform: scale(1.02);
  transition: all 0.8s ease-in-out;
}

/* Aktif slide */
.slide.active {
  opacity: 1;
  transform: scale(1);
}

/* 🔥 SOLUSI PUDAR: Overlay gradien gelap tipis di kiri untuk menaikkan kontras teks */
/* 🔥 SOLUSI BARU: Gambar tetap cerah maksimal, teks di kiri tetap kontras */
.overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.85) 0%,   /* Putih tebal di ujung kiri untuk teks */
    rgba(255, 255, 255, 0.4) 35%,   /* Mulai menipis di area tengah */
    rgba(255, 255, 255, 0) 60%      /* BENAR-BENAR TRANSPARAN dari tengah ke kanan */
  );
  z-index: 1;
}

/* Memastikan gambar latar belakang fokus di kanan dan cerah */
.slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: right center; /* Menggeser fokus buket ke kanan agar tidak tertutup teks */
  display: flex;
  align-items: center;
  padding: 0 10%;
  opacity: 0;
  transform: scale(1.02);
  transition: all 0.8s ease-in-out;
  
  /* Hapus atau komentari baris filter di bawah ini jika sebelumnya ada */
  /* filter: brightness(0.9) saturate(0.85); <- HAPUS INI AGAR GAMBAR TIDAK REDUP */
}


/* Isi text di atas overlay */
.hero-content {
  position: relative;
  z-index: 2;
  max-width: 550px;
  text-align: left;
}

/* Subtitle atas */
.hero-subtitle {
  display: block;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 2px;
  color: #5b7c99; /* Warna teks biru gelap solid */
  margin-bottom: 12px;
}

/* 🔥 SOLUSI KONTRAST: Mengubah warna font teks utama menjadi gelap */
.hero-content h1 {
  font-size: 46px;
  line-height: 1.2;
  color: #2c3e50; /* Ganti dari putih ke navy gelap agar terbaca tajam */
  margin-bottom: 16px;
  font-weight: 700;
}

.hero-content p {
  font-size: 15px;
  color: #555555; /* Ganti dari putih pudar ke abu-abu gelap */
  margin-bottom: 24px;
  line-height: 1.6;
}

/* Fitur ikon pelengkap di bawah deskripsi */
.hero-features {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 30px;
  font-size: 13px;
  color: #4a5568;
  font-weight: 500;
}

/* 🔥 SOLUSI TABRAKAN NAVIGASI: Tombol slider dipinggirkan menggunakan flex container */
.prev, .next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10; /* Berada di paling atas */
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(0, 0, 0, 0.1);
  color: #333;
  font-size: 20px;
  width: 46px;
  height: 46px;
  cursor: pointer;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.prev { left: 24px; }
.next { right: 24px; }

.prev:hover, .next:hover {
  background: #2c3e50;
  color: #fff;
  transform: translateY(-50%) scale(1.05);
}

/* CSS Halaman Utama Lainnya */
.home-section { padding: 48px 0; }

.products-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.card-badge {
  position: absolute; top: 10px; left: 10px;
  background: var(--rose); color: white;
  font-size: 11px; font-weight: 600;
  padding: 3px 10px; border-radius: 100px;
}

.testimonials-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.testi-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; padding: 20px;
}
.testi-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.testi-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: var(--rose-light);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; flex-shrink: 0;
}
.testi-name { font-weight: 600; font-size: 14px; color: var(--bark); }
.testi-text { font-size: 13px; color: var(--muted); line-height: 1.65; font-style: italic; }

/* Responsive Breakpoints */
@media (max-width: 900px) {
  .hero-content h1 { font-size: 36px; }
  .slide { padding: 0 8%; }
  .products-grid-4 { grid-template-columns: repeat(2, 1fr); }
  .testimonials-grid { grid-template-columns: 1fr; }
  .prev { left: 10px; }
  .next { right: 10px; }
}
@media (max-width: 500px) {
  .hero-content h1 { font-size: 28px; }
  .hero-slider { height: 70vh; }
  .products-grid-4 { grid-template-columns: 1fr; }
  .prev, .next { display: none; } /* Sembunyikan panah di mobile, andalkan auto-slide */
}

/* ── TESTIMONI MODAL ── */

.testi-actions {
  text-align: center;
  margin-top: 28px;
}

.testi-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);

  display: none;
  align-items: center;
  justify-content: center;

  z-index: 99999;
  padding: 20px;
}

.testi-modal-content {
  background: white;
  width: 100%;
  max-width: 900px;
  max-height: 85vh;

  overflow-y: auto;

  border-radius: 18px;
  padding: 30px;
  position: relative;
}

.testi-modal-content h2 {
  margin-bottom: 24px;
  color: var(--bark);
}

.close-modal {
  position: absolute;
  top: 14px;
  right: 20px;

  font-size: 34px;
  cursor: pointer;
  color: #555;
}

.close-modal:hover {
  color: black;
}

.all-testi-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (max-width: 700px) {
  .all-testi-list {
    grid-template-columns: 1fr;
  }
}

.testi-product-link {
  margin-top: 14px;
}

.testi-product-link a {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  font-size: 13px;
  font-weight: 600;

  color: var(--rose);
  text-decoration: none;

  transition: 0.2s ease;
}

.testi-product-link a:hover {
  opacity: 0.7;
}

.footer-social {
  display: flex;
  gap: 14px;
}

.footer-social a {
  width: 42px;
  height: 42px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: rgba(255,255,255,0.08);

  color: white;
  text-decoration: none;

  font-size: 18px;

  transition: 0.3s ease;
}

.footer-social a:hover {
  background: var(--rose);
  transform: translateY(-3px);
}
</style>

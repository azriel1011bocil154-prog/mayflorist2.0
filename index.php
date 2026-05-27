<?php
// index.php — Halaman Home

$page_title = 'MayFlorist — Buket Bunga Terbaik untuk Setiap Momen';
$active_nav = 'home';

// Include file pendukung bawaan
include 'includes/header.php';
include 'includes/products.php'; 

/* ── 1. AMBIL PRODUK TERLARIS DARI DATABASE ── */
$bestsellers = [];
if (isset($conn)) {
    // Logika: Ambil dari tabel produk, hitung total terjual dari pesanan (kecuali yg batal), urutkan dari terjual terbanyak & rating tertinggi
    $query_produk = "SELECT p.*, 
                    (SELECT IFNULL(SUM(dp.jumlah_produk), 0) 
                     FROM detail_pesanan dp 
                     JOIN pesanan pes ON dp.id_pesanan = pes.id_pesanan 
                     WHERE dp.id_produk = p.id_produk AND pes.status_pesanan != 'dibatalkan') AS total_terjual
                     FROM produk p
                     ORDER BY total_terjual DESC, p.rating DESC
                     LIMIT 6";
              
    $result_produk = mysqli_query($conn, $query_produk);
    if ($result_produk) {
        while ($row = mysqli_fetch_assoc($result_produk)) {
            // Validasi file gambar yang aman
            $gambar_nama = $row['gambar_produk'] ?? '';
            $gambar_path = 'assets/images/' . $gambar_nama;
            $final_image = (!empty($gambar_nama) && is_file($gambar_path)) ? $gambar_path : '';

            $bestsellers[] = [
                'name'     => $row['nama_produk'] ?? 'Produk Tanpa Nama',
                'price'    => $row['harga_produk'] ?? 0,
                'slug'     => $row['id_produk'] ?? 0, 
                'image'    => $final_image, 
                'rating'   => $row['rating'] ?? 0,
                'badge'    => 'Terlaris',
                'terjual'  => (int)($row['total_terjual'] ?? 0) // <-- SEKARANG SUDAH DISIMPAN DI SINI
            ];
        }
    }
}

/* ── 2. INTEGRASI TESTIMONI DARI DATABASE ── */
$testimonials = [];
if (isset($conn)) {
    $query_review = "SELECT r.*, u.nama_user, p.nama_produk, p.id_produk
                     FROM review r
                     JOIN user u ON r.id_user = u.id_user
                     JOIN produk p ON r.id_produk = p.id_produk
                     ORDER BY r.rating DESC, r.tanggal_review DESC
                     LIMIT 12";
              
    $result_review = mysqli_query($conn, $query_review);
    if ($result_review) {
        while ($row = mysqli_fetch_assoc($result_review)) {
            $testimonials[] = [
                'name'        => $row['nama_user'] ?? 'Pelanggan MayFlorist',
                'stars'       => isset($row['rating']) ? (int)$row['rating'] : 5,
                'review'      => $row['komentar'] ?? '',
                'nama_produk' => $row['nama_produk'] ?? 'Produk',
                'id_produk'   => $row['id_produk'] ?? 0
            ];
        }
    }
}
?>

<section class="hero-slider" id="heroSlider">
  <div class="slide active" style="background-image: url('assets/images/hero/hero1.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">RANGKAIAN BUNGA ISTIMEWA</span>
      <h1>Kejutan Spesial,<br>Untuk Momen Berharga</h1>
      <p>Buket bunga eksklusif yang dirangkai dengan kehangatan dan ketulusan, sempurna untuk menyampaikan rasa sayang yang tak utarakan.</p>

      <div class="hero-features">
        <span>🌸 Bunga Segar Pilihan</span>
        <span>🚚 Pengiriman Cepat & Aman</span>
        <span>❤️ Dirangkai dengan Cinta</span>
      </div>

      <a href="katalog.php" class="btn btn-primary hero-btn">Lihat Produk</a>
    </div>
  </div>

  <div class="slide" style="background-image: url('assets/images/hero/hero2.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">KOLEKSI TERBARU</span>
      <h1>Pesona Keindahan Di Setiap Kelopak</h1>
      <p>Temukan berbagai pilihan desain buket modern untuk merayakan hari kelulusan, ulang tahun, dan anniversary.</p>
      <a href="katalog.php" class="btn btn-primary hero-btn">Lihat Katalog</a>
    </div>
  </div>

  <div class="slide" style="background-image: url('assets/images/hero/hero3.jpeg');">
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="hero-subtitle">CUSTOM BOUQUET</span>
      <h1>Ekspresikan Perasaan Anda Sendiri</h1>
      <p>Pilih tipe bunga, warna kertas pembungkus, dan kartu ucapan sesuai dengan keinginan unik Anda.</p>
      <a href="katalog.php" class="btn btn-primary hero-btn">Pesan Custom</a>
    </div>
  </div>

  <button class="prev" onclick="changeSlide(-1)" aria-label="Previous Slide">❮</button>
  <button class="next" onclick="changeSlide(1)" aria-label="Next Slide">❯</button>
  
  <div class="slide-indicators" id="slideIndicators"></div>
</section>

<section class="home-section">
  <div class="page-wrapper container">
    <div class="section-header reveal-up">
      <h2>Produk Terlaris</h2>
    </div>

    <div class="products-grid">
      <?php foreach ($bestsellers as $index => $p): ?>
        <?php
          $name      = $p['name'];
          $price     = $p['price'];
          $id_produk = $p['slug']; 
          $image     = $p['image'];
          $badge     = $p['badge'];
          $rating    = $p['rating'];
          $terjual   = $p['terjual']; // <-- AMBIL VARIABELNYA
          $delay     = $index * 150; 
        ?>

        <div class="product-card reveal-up" style="transition-delay: <?= $delay ?>ms;">
          <a href="detail.php?id=<?= $id_produk ?>">
            <div class="card-img">
              <?php if (!empty($image)): ?>
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
            <h3 class="card-name"><?= htmlspecialchars($name) ?></h3>
            <div class="card-price">
                <?= function_exists('formatRupiah') ? formatRupiah($price) : 'Rp ' . number_format($price, 0, ',', '.'); ?>
            </div>
            
            <div class="rating-container">
                <div class="stars">
                    <?php
                    $fullStars = floor($rating);
                    $halfStar = (($rating - $fullStars) >= 0.25 && ($rating - $fullStars) < 0.75) ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    ?>
                    <?php for ($i = 1; $i <= $fullStars; $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                    <?php if ($halfStar): ?>
                        <i class="fas fa-star-half-alt"></i>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $emptyStars; $i++): ?>
                        <i class="far fa-star"></i>
                    <?php endfor; ?>
                </div>
                <span class="rating-score">(<?= number_format($rating, 1) ?>)</span>
                
                <?php if ($terjual > 0): ?>
                    <span class="sold-count">• Terjual <?= $terjual ?></span>
                <?php endif; ?>
            </div>

            <a href="detail.php?id=<?= $id_produk ?>" class="btn btn-primary btn-sm btn-full">
              Lihat Detail
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="center-action reveal-up" style="transition-delay: 600ms;">
      <a href="katalog.php" class="btn btn-outline">Lihat Semua Produk</a>
    </div>
  </div>
</section>

<section class="home-section alt-bg">
  <div class="page-wrapper container">
    <div class="section-header reveal-up">
      <h2>Testimoni Pelanggan</h2>
    </div>

    <div class="testimonials-grid">
      <?php foreach (array_slice($testimonials, 0, 3) as $index => $t): ?>
      <?php $delay = $index * 200; ?>
      <div class="testi-card reveal-zoom" style="transition-delay: <?= $delay ?>ms;">
        <div class="testi-header">
          <div class="testi-avatar">👤</div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
            <div class="stars">
                <?= str_repeat('★', $t['stars']) ?><span style="color:#e0e0e0;"><?= str_repeat('★', 5 - $t['stars']) ?></span>
            </div>
          </div>
        </div>
        <p class="testi-text">"<?= htmlspecialchars($t['review']) ?>"</p>
        <div class="testi-product-link">
          <a href="detail.php?id=<?= (int)$t['id_produk'] ?>">
            🛍 <?= htmlspecialchars($t['nama_produk']) ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="center-action reveal-up" style="transition-delay: 500ms;">
      <button class="btn btn-outline" onclick="openTestimonials()">
        Lihat Semua Testimoni
      </button>
    </div>
  </div>
</section>

<div id="testiModal" class="testi-modal">
  <div class="testi-modal-content">
    <span class="close-modal" onclick="closeTestimonials()">&times;</span>
    <h2 class="modal-title">Semua Testimoni Pelanggan</h2>

    <div class="all-testi-list">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-header">
          <div class="testi-avatar">👤</div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
            <div class="stars">
                <?= str_repeat('★', $t['stars']) ?><span style="color:#e0e0e0;"><?= str_repeat('★', 5 - $t['stars']) ?></span>
            </div>
          </div>
        </div>
        <p class="testi-text">"<?= htmlspecialchars($t['review']) ?>"</p>
        <div class="testi-product-link">
          <a href="detail.php?id=<?= (int)$t['id_produk'] ?>">
            🛍 <?= htmlspecialchars($t['nama_produk']) ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const observerOptions = { root: null, rootMargin: "0px", threshold: 0.15 };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        observer.unobserve(entry.target); 
      }
    });
  }, observerOptions);

  const revealElements = document.querySelectorAll(".reveal-up, .reveal-zoom");
  revealElements.forEach(el => observer.observe(el));
});

let currentSlide = 0;
const slides = document.querySelectorAll(".slide");
let autoSlideInterval;

function showSlide(index) {
  slides.forEach((slide) => slide.classList.remove("active"));
  if(slides[index]) slides[index].classList.add("active");
}

function changeSlide(step) {
  currentSlide += step;
  if (currentSlide >= slides.length) currentSlide = 0;
  if (currentSlide < 0) currentSlide = slides.length - 1;
  showSlide(currentSlide);
  resetInterval();
}

function resetInterval() {
  clearInterval(autoSlideInterval);
  autoSlideInterval = setInterval(() => { changeSlide(1); }, 6000);
}

if(slides.length > 0) {
    showSlide(currentSlide);
    resetInterval();
}

let touchStartX = 0;
let touchEndX = 0;
const heroSlider = document.getElementById('heroSlider');

if (heroSlider) {
    heroSlider.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
    heroSlider.addEventListener('touchend', e => { touchEndX = e.changedTouches[0].screenX; handleSwipe(); });
}

function handleSwipe() {
  if (touchStartX - touchEndX > 50) changeSlide(1); 
  if (touchEndX - touchStartX > 50) changeSlide(-1); 
}

const modal = document.getElementById("testiModal");
function openTestimonials() { if (modal) { modal.classList.add("show"); document.body.style.overflow = "hidden"; } }
function closeTestimonials() { if (modal) { modal.classList.remove("show"); document.body.style.overflow = "auto"; } }
window.onclick = function(e) { if (e.target == modal) closeTestimonials(); }
</script>

<style>
:root {
  --primary-color: #b76e79;
  --primary-dark: #9c5c66;
  --text-dark: #2d3436;
  --text-gray: #636e72;
  --bg-light: #fdfbfb;
  --border-color: #eee;
}

body {
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #fff; margin: 0; padding: 0; overflow-x: hidden;
}

.container { width: 90%; max-width: 1200px; margin: 0 auto; }
.home-section { padding: 80px 0; overflow: hidden; }
.alt-bg { background-color: var(--bg-light); }
.center-action { text-align: center; margin-top: 40px; }

.reveal-up { opacity: 0; transform: translateY(60px); transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.reveal-zoom { opacity: 0; transform: scale(0.85) translateY(30px); transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.reveal-up.in-view { opacity: 1; transform: translateY(0); }
.reveal-zoom.in-view { opacity: 1; transform: scale(1) translateY(0); }

/* HERO SECTION */
.hero-slider { position: relative; height: 90vh; min-height: 600px; overflow: hidden; background: var(--bg-light); }
.slide { position: absolute; inset: 0; background-size: cover; background-position: center; display: flex; align-items: center; padding: 0 10%; opacity: 0; visibility: hidden; transition: opacity 1s ease, transform 1s ease; transform: scale(1.05); }
.slide.active { opacity: 1; visibility: visible; z-index: 2; transform: scale(1); }
.overlay { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.7) 50%, rgba(255,255,255,0) 100%); z-index: 1; }
.hero-content { position: relative; z-index: 10; max-width: 600px; animation: fadeUp 1s ease forwards; }
.hero-subtitle { display: inline-block; padding: 6px 16px; border-radius: 50px; background: rgba(255,255,255,0.8); backdrop-filter: blur(8px); font-size: 13px; font-weight: 700; letter-spacing: 1.5px; color: var(--primary-color); margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.hero-content h1 { font-size: 3.5rem; line-height: 1.2; font-weight: 800; color: var(--text-dark); margin: 0 0 20px 0; }
.hero-content p { font-size: 1.1rem; line-height: 1.6; color: var(--text-gray); margin-bottom: 30px; }
.hero-features { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 35px; }
.hero-features span { background: rgba(255,255,255,0.9); padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; color: #444; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 6px; }
.prev, .next { position: absolute; top: 50%; transform: translateY(-50%); z-index: 20; width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,0.8); backdrop-filter: blur(5px); color: var(--text-dark); font-size: 20px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.prev:hover, .next:hover { background: var(--primary-color); color: white; }
.prev { left: 30px; }
.next { right: 30px; }

/* BUTTONS */
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 32px; border-radius: 50px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; cursor: pointer; border: 2px solid transparent; }
.btn-primary { background: var(--primary-color); color: white; box-shadow: 0 8px 20px rgba(183,110,121,0.3); }
.btn-primary:hover { background: var(--primary-dark); transform: translateY(-3px); box-shadow: 0 12px 25px rgba(183,110,121,0.4); }
.btn-outline { background: transparent; color: var(--primary-color); border-color: var(--primary-color); }
.btn-outline:hover { background: var(--primary-color); color: white; }
.btn-full { width: 100%; box-sizing: border-box; border-radius: 12px; }

/* HEADINGS */
.section-header { text-align: center; margin-bottom: 50px; }
.section-header h2 { position: relative; display: inline-block; font-size: 2.2rem; color: var(--text-dark); padding-bottom: 15px; margin: 0; }
.section-header h2::after { content: ""; position: absolute; width: 50px; height: 4px; left: 50%; transform: translateX(-50%); bottom: 0; border-radius: 2px; background: var(--primary-color); }

/* PRODUCT GRID */
.products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 30px; }
.product-card { background: white; border-radius: 20px; overflow: hidden; border: 1px solid var(--border-color); transition-property: transform, box-shadow; transition-duration: 0.4s; display: flex; flex-direction: column; }
.product-card.in-view:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(183,110,121,0.12); border-color: rgba(183,110,121,0.3); }
.card-img { position: relative; overflow: hidden; aspect-ratio: 4/5; }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
.product-card:hover .card-img img { transform: scale(1.1); }
.card-badge { position: absolute; top: 15px; left: 15px; background: var(--primary-color); color: white; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; z-index: 5; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.card-body { padding: 24px; display: flex; flex-direction: column; flex-grow: 1; }
.card-name { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin: 0 0 10px 0; }
.card-price { font-size: 1.2rem; font-weight: 800; color: var(--primary-color); margin-bottom: 12px; }
.rating-container { display: flex; align-items: center; gap: 6px; font-size: 14px; margin-bottom: 20px; flex-wrap: wrap; }
.stars { color: #f39c12; display: flex; align-items: center; gap: 2px; }
.rating-score { color: var(--text-gray); font-size: 13px; }

/* SEKSI TERJUAL */
.sold-count { color: var(--text-gray); font-size: 13px; font-weight: 600; }

/* TESTIMONIALS */
.testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
.testi-card { background: white; border-radius: 16px; padding: 30px; border: 1px solid var(--border-color); transition-property: transform, box-shadow; transition-duration: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
.testi-card.in-view:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 12px 30px rgba(183,110,121,0.1); }
.testi-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.testi-avatar { width: 50px; height: 50px; border-radius: 50%; background: #fff0f2; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.testi-name { font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
.testi-text { font-size: 14px; line-height: 1.7; color: var(--text-gray); font-style: italic; flex-grow: 1; }
.testi-product-link { margin-top: 15px; border-top: 1px dashed rgba(183,110,121,0.3); padding-top: 15px; }
.testi-product-link a { color: var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.2s; }
.testi-product-link a:hover { color: var(--primary-dark); }

/* MODAL */
.testi-modal { position: fixed; inset: 0; background: rgba(61,44,40,0.6); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px; opacity: 0; visibility: hidden; transition: opacity 0.4s ease, visibility 0.4s ease; }
.testi-modal.show { opacity: 1; visibility: visible; }
.testi-modal-content { background: white; width: 100%; max-width: 900px; max-height: 85vh; overflow-y: auto; border-radius: 20px; padding: 40px; position: relative; transform: scale(0.9); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.testi-modal.show .testi-modal-content { transform: scale(1); }
.modal-title { margin-top: 0; color: var(--text-dark); margin-bottom: 30px; text-align: center; }
.close-modal { position: absolute; top: 15px; right: 25px; font-size: 32px; cursor: pointer; color: var(--text-gray); transition: color 0.2s, transform 0.2s; }
.close-modal:hover { color: var(--primary-color); transform: rotate(90deg); }
.all-testi-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
  .hero-slider { height: 80vh; min-height: 500px; }
  .slide { padding: 0 5%; }
  .overlay { background: linear-gradient(180deg, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.95) 70%); }
  .hero-content { text-align: center; margin: 0 auto; margin-top: 20%; }
  .hero-features { justify-content: center; gap: 8px; }
  .hero-features span { font-size: 11px; padding: 6px 12px; }
  .hero-content h1 { font-size: 2.2rem; }
  .hero-content p { font-size: 1rem; }
  .prev, .next { display: none; }
  .section-header h2 { font-size: 1.8rem; }
  .home-section { padding: 50px 0; }
  .testi-modal-content { padding: 25px 15px; }
  .reveal-up, .reveal-zoom { transition-delay: 0ms !important; }
}
</style>
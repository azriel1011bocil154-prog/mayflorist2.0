<?php
// kontak.php

$page_title = 'Kontak — MayFlorist';
$active_nav = 'kontak';

include 'includes/header.php';
require 'koneksi.php'; // Menghubungkan ke file koneksi di folder utama

$koneksi = $conn; // Menyamakan variabel koneksi database

// ── 1. AMBIL DATA TOKO DARI DATABASE ──
$query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
$data_toko  = mysqli_fetch_assoc($query_toko);

// Mengambil data, jika kosong akan menggunakan nilai alternatif (fallback)
$nama_toko   = htmlspecialchars($data_toko['nama_toko'] ?? 'MayFlorist');
$alamat_toko = nl2br(htmlspecialchars($data_toko['alamat_toko'] ?? 'Bandar Lampung, Indonesia'));
$whatsapp    = htmlspecialchars($data_toko['nomor_whatsapp'] ?? '+62 812-3456-7890');
$email_toko  = htmlspecialchars($data_toko['email_toko'] ?? 'hello@mayflorist.com');
$jam_ops     = htmlspecialchars($data_toko['jam_operasional'] ?? 'Senin - Sabtu (08.00 — 21.00 WIB)');

// Hilangkan karakter non-angka pada nomor WA untuk link chat otomatis
$wa_link     = preg_replace('/[^0-9]/', '', $whatsapp);
if (substr($wa_link, 0, 1) === '0') {
    $wa_link = '62' . substr($wa_link, 1);
}

// ── 2. LOGIKA PILIHAN KIRIM FORM (OPSIONAL) ──
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email_user']);
    $telp  = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $pesan = mysqli_real_escape_string($koneksi, $_POST['pesan_user']);

    // Opsi A: Jika ingin langsung diarahkan ke chat WhatsApp Admin
    $teks_wa = urlencode("Halo *{$nama_toko}*,\nSaya *{$nama}* ({$telp})\nEmail: {$email}\n\nPesan:\n{$pesan}");
    echo "<script>window.open('https://api.whatsapp.com/send?phone={$wa_link}&text={$teks_wa}', '_blank');</script>";
    $alert = '<div class="alert alert-success" style="padding:15px; border-radius:10px; margin-bottom:20px; background:#e1f5fe; color:#0288d1;">&#10003; Menghubungkan ke WhatsApp Admin...</div>';
}
?>

<section class="contact-hero">
  <div class="page-wrapper">

    <span class="contact-label">Hubungi Kami</span>

    <h1>
      Kami siap membantu<br>
      setiap momen spesial Anda.
    </h1>

    <p>
      Punya pertanyaan, permintaan khusus, atau ingin memesan bunga custom? 
      Tim <?= $nama_toko ?> siap membantu dengan senang hati.
    </p>

  </div>
</section>

<section class="contact-section">
  <div class="page-wrapper">
    
    <?php if(!empty($alert)) echo $alert; ?>

    <div class="contact-grid">

      <div class="contact-info-wrap">

        <div class="contact-card">
          <div class="contact-icon">📍</div>
          <div>
            <h3>Alamat Toko</h3>
            <p><?= $alamat_toko ?></p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">📞</div>
          <div>
            <h3>WhatsApp</h3>
            <p><a href="https://api.whatsapp.com/send?phone=<?= $wa_link ?>" target="_blank" style="text-decoration:none; color:inherit;"><?= $whatsapp ?></a></p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">✉️</div>
          <div>
            <h3>Email</h3>
            <p><?= $email_toko ?></p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">🕒</div>
          <div>
            <h3>Jam Operasional</h3>
            <p><?= $jam_ops ?></p>
          </div>
        </div>

      </div>

      <div class="contact-form-box">

        <h2>Kirim Pesan</h2>

        <p class="form-desc">
          Isi form berikut dan sistem akan meneruskan pesan Anda langsung ke WhatsApp Admin kami.
        </p>

        <form method="POST" action="kontak.php">

          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email_user" placeholder="Masukkan email" required>
          </div>

          <div class="form-group">
            <label>Nomor Telepon / WhatsApp</label>
            <input type="tel" name="no_telp" placeholder="Masukkan nomor telepon" required>
          </div>

          <div class="form-group">
            <label>Pesan</label>
            <textarea name="pesan_user" rows="5" placeholder="Tulis pesan Anda..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Kirim ke WhatsApp
          </button>

        </form>

      </div>

    </div>

  </div>
</section>

<section class="map-section">
  <iframe
    src="https://maps.google.com/maps?q=<?= urlencode(strip_tags($data_toko['alamat_toko'] ?? 'Bandar Lampung')) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed"
    loading="lazy"
    allowfullscreen>
  </iframe>
</section>

<section class="contact-cta">
  <div class="page-wrapper">

    <h2>
      Temukan bunga terbaik<br>
      untuk orang tersayang.
    </h2>

    <p>
      Jadikan setiap momen lebih indah bersama <?= $nama_toko ?>.
    </p>

    <a href="katalog.php" class="btn btn-primary">
      Belanja Sekarang
    </a>

  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
/* ==========================================
   SCROLL REVEAL ENGINE
========================================== */

const reveals = document.querySelectorAll(`
  .contact-card,
  .contact-form-box,
  .contact-hero h1,
  .contact-hero p,
  .contact-label,
  .contact-cta,
  .map-section
`);

reveals.forEach(el => {
  el.classList.add('reveal');
});

const observer = new IntersectionObserver((entries)=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      entry.target.classList.add('active');
    }
  });
},{
  threshold:0.15
});

reveals.forEach(el=>{
  observer.observe(el);
});

/* ==========================================
   BUTTON RIPPLE EFFECT
========================================== */

document.querySelectorAll('.btn-primary').forEach(btn=>{

  btn.addEventListener('click', function(e){

    const ripple = document.createElement('span');

    const rect = this.getBoundingClientRect();

    ripple.style.width =
    ripple.style.height = Math.max(rect.width, rect.height) + 'px';

    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'rgba(255,255,255,.45)';
    ripple.style.left = e.clientX - rect.left - 50 + 'px';
    ripple.style.top = e.clientY - rect.top - 50 + 'px';
    ripple.style.pointerEvents = 'none';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'ripple .7s ease-out forwards';

    this.appendChild(ripple);

    setTimeout(()=>{
      ripple.remove();
    },700);

  });

});

/* Ripple Keyframe Inject */
const style = document.createElement('style');

style.innerHTML = `
@keyframes ripple{
  to{
    transform:scale(4);
    opacity:0;
  }
}
`;

document.head.appendChild(style);
</script>

<style>
/* Menjaga kestabilan horizontal agar tidak muncul scrollbar liar saat komponen bergeser */
body {
  overflow-x: hidden;
  background-color: #fcf9f8; /* Warna krem lembut untuk background halaman */
}

/* ==========================================
   CSS SCROLL REVEAL ENGINE
   ========================================== */
.reveal-up {
  opacity: 0;
  transform: translateY(45px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-zoom {
  opacity: 0;
  transform: scale(0.95) translateY(15px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-left {
  opacity: 0;
  transform: translateX(-45px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.reveal-right {
  opacity: 0;
  transform: translateX(45px);
  transition: opacity 0.8s ease-out, transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* Triggered class saat masuk area viewport */
.reveal-up.in-view,
.reveal-zoom.in-view,
.reveal-left.in-view,
.reveal-right.in-view {
  opacity: 1;
  transform: translate(0) scale(1);
}

/* Isolasi efek hover pada kartu kontak */
.contact-card {
  transition-property: transform, box-shadow, border-color;
  transition-duration: 0.3s;
  transition-timing-function: ease;
}

.contact-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 26px rgba(183, 110, 121, 0.12);
  border-color: rgba(183, 110, 121, 0.25);
}

/* Animasi kemunculan notifikasi sukses */
.animate-alert {
  animation: fadeInDown 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-15px); }
  to { opacity: 1; transform: translateY(0); }
}


/* ==========================================
   KONTAK STYLING BASE
   ========================================== */
.contact-hero {
  padding: 90px 0;
  text-align: center;
  background:
    linear-gradient(
      rgba(252,249,248, 0.90),
      rgba(252,249,248, 0.90)
    ),
    url('assets/images/contact-bg.jpg') center/cover;
}

.contact-label {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 999px;
  background: var(--bloom-petal);
  color: var(--bloom-rose);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 18px;
}

.contact-hero h1 {
  font-family: var(--font-romantic);
  color: var(--bloom-dark);
  max-width: 760px;
  margin: auto;
  font-size: 46px;
  line-height: 1.2;
  margin-bottom: 18px;
}

.contact-hero p {
  max-width: 620px;
  margin: auto;
  color: var(--bloom-sage); /* Menggunakan warna sage dari header */
  line-height: 1.8;
}

/* Membatasi lebar konten */
.page-wrapper {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px;
}

.contact-section {
  padding: 80px 0;
}

.contact-grid {
  display: grid;
  grid-template-columns: .9fr 1.1fr;
  gap: 40px;
}

.contact-info-wrap {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.contact-card {
  display: flex;
  gap: 18px;
  align-items: flex-start;
  background: var(--bloom-white);
  border: 1px solid rgba(183, 110, 121, 0.15);
  border-radius: 14px;
  padding: 22px;
}

.contact-icon {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--bloom-petal);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.contact-card h3 {
  font-family: var(--font-clean);
  font-size: 18px;
  margin-bottom: 6px;
  color: var(--bloom-dark);
}

.contact-card p {
  color: #786b68; /* Warna teks pudar kustom */
  font-size: 14px;
  line-height: 1.7;
}

.contact-form-box {
  background: var(--bloom-white);
  border: 1px solid rgba(183, 110, 121, 0.15);
  border-radius: 16px;
  padding: 34px;
  box-shadow: 0 10px 30px rgba(61,44,40,.05);
}

.contact-form-box h2 {
  font-family: var(--font-romantic);
  color: var(--bloom-dark);
  font-size: 30px;
  margin-bottom: 10px;
}

.form-desc {
  color: #786b68;
  margin-bottom: 28px;
  font-size: 14px;
}

.contact-form-box .form-group {
  margin-bottom: 18px;
}

.contact-form-box label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: var(--bloom-dark);
}

.contact-form-box input,
.contact-form-box textarea {
  width: 100%;
  border: 1px solid rgba(183, 110, 121, 0.2);
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 14px;
  font-family: var(--font-clean);
  background: white;
  transition: .2s;
}

.contact-form-box input:focus,
.contact-form-box textarea:focus {
  border-color: var(--bloom-rose);
  outline: none;
  box-shadow: 0 0 0 4px rgba(183,110,121,.10);
}

.contact-form-box textarea {
  resize: vertical;
}

/* Tombol Submit */
.btn {
  display: inline-block;
  padding: 14px 28px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: 0.3s;
  text-align: center;
  font-family: var(--font-clean);
}

.btn-primary {
  background-color: var(--bloom-rose);
  color: white;
}

.btn-primary:hover {
  background-color: #a35b64;
  transform: translateY(-2px);
}

.btn-full {
  width: 100%;
}

.map-section iframe {
  width: 100%;
  height: 420px;
  border: none;
  display: block;
}

.contact-cta {
  padding: 90px 0;
  text-align: center;
}

.contact-cta h2 {
  font-family: var(--font-romantic);
  color: var(--bloom-dark);
  font-size: 40px;
  line-height: 1.3;
  max-width: 700px;
  margin: auto auto 14px;
}

.contact-cta p {
  color: #786b68;
  margin-bottom: 28px;
}



/* ==========================================
   RESPONSIVE MEDIA QUERIES
   ========================================== */
@media (max-width: 900px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  
  /* Hilangkan delay dan ubah sumbu X menjadi Y murni di layar kecil untuk mencegah layout patah */
  .reveal-up, .reveal-zoom, .reveal-left, .reveal-right {
    transition-delay: 0ms !important;
    transform: translateY(30px) !important;
  }
}

@media (max-width: 768px) {
  .contact-hero { padding: 70px 0; }
  .contact-hero h1 { font-size: 34px; }
  .contact-form-box { padding: 24px; }
  .contact-form-box h2 { font-size: 24px; }
  .contact-cta h2 { font-size: 30px; }
  .map-section iframe { height: 320px; }
}

/* ==========================================
   ADVANCED UI ANIMATION PACK
========================================== */

/* Smooth Scroll */
html {
  scroll-behavior: smooth;
}

/* Floating Hero */
.contact-hero {
  position: relative;
  overflow: hidden;
}

.contact-hero::before,
.contact-hero::after {
  content: '';
  position: absolute;
  width: 320px;
  height: 320px;
  border-radius: 50%;
  filter: blur(90px);
  opacity: .18;
  z-index: 0;
}

.contact-hero::before {
  background: #f7a8b8;
  top: -120px;
  left: -100px;
  animation: floatBlob 8s ease-in-out infinite;
}

.contact-hero::after {
  background: #d8b4fe;
  bottom: -140px;
  right: -100px;
  animation: floatBlob 10s ease-in-out infinite reverse;
}

.contact-hero .page-wrapper {
  position: relative;
  z-index: 2;
}

@keyframes floatBlob {
  0%,100% {
    transform: translateY(0) translateX(0);
  }
  50% {
    transform: translateY(20px) translateX(10px);
  }
}

/* Reveal Animation */
.reveal {
  opacity: 0;
  transform: translateY(45px);
  transition: all .9s cubic-bezier(.17,.84,.44,1);
}

.reveal.active {
  opacity: 1;
  transform: translateY(0);
}

/* Glass Contact Cards */
.contact-card {
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(14px);
  background: rgba(255,255,255,0.72);
}

.contact-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(255,255,255,.35),
    transparent
  );
  opacity: 0;
  transition: .4s;
}

.contact-card:hover::before {
  opacity: 1;
}

.contact-card:hover .contact-icon {
  transform: rotate(10deg) scale(1.08);
}

.contact-icon {
  transition: .35s ease;
}

/* Fancy Form */
.contact-form-box {
  position: relative;
  overflow: hidden;
}

.contact-form-box::before {
  content: '';
  position: absolute;
  width: 240px;
  height: 240px;
  background: rgba(183,110,121,.08);
  border-radius: 50%;
  top: -120px;
  right: -120px;
  filter: blur(20px);
}

.contact-form-box input,
.contact-form-box textarea {
  transition:
    border-color .3s,
    transform .25s,
    box-shadow .3s,
    background .3s;
}

.contact-form-box input:focus,
.contact-form-box textarea:focus {
  transform: translateY(-2px);
  background: #fffdfd;
}

/* Gradient Button */
.btn-primary {
  position: relative;
  overflow: hidden;
  background:
    linear-gradient(
      135deg,
      #b76e79,
      #d98d9b
    );
  box-shadow:
    0 10px 24px rgba(183,110,121,.25);
}

.btn-primary::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      120deg,
      transparent,
      rgba(255,255,255,.35),
      transparent
    );
  transform: translateX(-120%);
}

.btn-primary:hover::before {
  animation: shine 1s forwards;
}

.btn-primary:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow:
    0 18px 35px rgba(183,110,121,.35);
}

@keyframes shine {
  100% {
    transform: translateX(120%);
  }
}

/* CTA Cinematic */
.contact-cta {
  position: relative;
  overflow: hidden;
}

.contact-cta::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(
      circle at top,
      rgba(183,110,121,.12),
      transparent 70%
    );
  pointer-events: none;
}

/* Floating Map */
.map-section {
  overflow: hidden;
}

.map-section iframe {
  transition: transform 1.2s ease;
}

.map-section:hover iframe {
  transform: scale(1.03);
}

/* Mouse Glow */
.contact-form-box:hover {
  box-shadow:
    0 18px 40px rgba(183,110,121,.14);
}

/* Mobile Smooth */
@media (max-width:768px){

  .contact-card:hover {
    transform: none;
  }

  .btn-primary:hover {
    transform: scale(1.01);
  }

}
</style>
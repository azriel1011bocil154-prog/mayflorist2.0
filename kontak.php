<?php
// kontak.php

$page_title = 'Kontak — MayFlorist';
$active_nav = 'kontak';

include 'includes/header.php';
?>

<!-- ── HERO ── -->
<section class="contact-hero">
  <div class="page-wrapper">

    <span class="contact-label">Hubungi Kami</span>

    <h1>
      Kami siap membantu
      setiap momen spesial Anda.
    </h1>

    <p>
      Punya pertanyaan, permintaan khusus,
      atau ingin memesan bunga custom?
      Tim Fleuriste siap membantu dengan senang hati.
    </p>

  </div>
</section>

<!-- ── CONTACT SECTION ── -->
<section class="contact-section">
  <div class="page-wrapper">

    <div class="contact-grid">

      <!-- ───────────────────── -->
      <!-- LEFT -->
      <!-- ───────────────────── -->

      <div class="contact-info-wrap">

        <div class="contact-card">
          <div class="contact-icon">📍</div>

          <div>
            <h3>Alamat Toko</h3>
            <p>
              Jl. Mawar Indah No. 12<br>
              Bandar Lampung, Indonesia
            </p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">📞</div>

          <div>
            <h3>Telepon</h3>
            <p>+62 812-3456-7890</p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">✉️</div>

          <div>
            <h3>Email</h3>
            <p>hello@fleuriste.com</p>
          </div>
        </div>

        <div class="contact-card">
          <div class="contact-icon">🕒</div>

          <div>
            <h3>Jam Operasional</h3>
            <p>
              Senin - Sabtu<br>
              08.00 — 21.00 WIB
            </p>
          </div>
        </div>

      </div>

      <!-- ───────────────────── -->
      <!-- RIGHT -->
      <!-- ───────────────────── -->

      <div class="contact-form-box">

        <h2>Kirim Pesan</h2>

        <p class="form-desc">
          Isi form berikut dan kami akan
          menghubungi Anda secepat mungkin.
        </p>

        <form>

          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" placeholder="Masukkan nama lengkap">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" placeholder="Masukkan email">
          </div>

          <div class="form-group">
            <label>Nomor Telepon</label>
            <input type="tel" placeholder="Masukkan nomor telepon">
          </div>

          <div class="form-group">
            <label>Pesan</label>

            <textarea
              rows="5"
              placeholder="Tulis pesan Anda..."
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            Kirim Pesan
          </button>

        </form>

      </div>

    </div>

  </div>
</section>

<!-- ── MAP ── -->
<section class="map-section">

  <iframe
    src="https://www.google.com/maps?q=Bandar+Lampung&output=embed"
    loading="lazy"
    allowfullscreen>
  </iframe>

</section>

<!-- ── CTA ── -->
<section class="contact-cta">
  <div class="page-wrapper">

    <h2>
      Temukan bunga terbaik
      untuk orang tersayang.
    </h2>

    <p>
      Jadikan setiap momen lebih indah bersama Fleuriste.
    </p>

    <a href="katalog.php" class="btn btn-primary">
      Belanja Sekarang
    </a>

  </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>

/* ─────────────────────────
   HERO
───────────────────────── */

.contact-hero {
  padding: 90px 0;
  text-align: center;
  background:
    linear-gradient(
      rgba(249,241,238,.90),
      rgba(249,241,238,.90)
    ),
    url('assets/images/contact-bg.jpg') center/cover;
}

.contact-label {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 999px;
  background: var(--rose-light);
  color: var(--rose-dark);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 18px;
}

.contact-hero h1 {
  max-width: 760px;
  margin: auto;
  font-size: 46px;
  line-height: 1.2;
  margin-bottom: 18px;
}

.contact-hero p {
  max-width: 620px;
  margin: auto;
  color: var(--muted);
  line-height: 1.8;
}

/* ─────────────────────────
   CONTACT SECTION
───────────────────────── */

.contact-section {
  padding: 80px 0;
}

.contact-grid {
  display: grid;
  grid-template-columns: .9fr 1.1fr;
  gap: 40px;
}

/* ─────────────────────────
   LEFT INFO
───────────────────────── */

.contact-info-wrap {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.contact-card {
  display: flex;
  gap: 18px;
  align-items: flex-start;

  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;

  padding: 22px;

  transition: .25s;
}

.contact-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(61,44,40,.08);
}

.contact-icon {
  width: 56px;
  height: 56px;

  flex-shrink: 0;

  border-radius: 50%;
  background: var(--rose-light);

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 24px;
}

.contact-card h3 {
  font-size: 18px;
  margin-bottom: 6px;
}

.contact-card p {
  color: var(--muted);
  font-size: 14px;
  line-height: 1.7;
}

/* ─────────────────────────
   FORM
───────────────────────── */

.contact-form-box {
  background: var(--white);

  border: 1px solid var(--border);
  border-radius: 16px;

  padding: 34px;

  box-shadow: 0 10px 30px rgba(61,44,40,.05);
}

.contact-form-box h2 {
  font-size: 30px;
  margin-bottom: 10px;
}

.form-desc {
  color: var(--muted);
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
  font-weight: 500;

  color: var(--bark);
}

.contact-form-box input,
.contact-form-box textarea {
  width: 100%;

  border: 1px solid var(--border);
  border-radius: 10px;

  padding: 12px 14px;

  font-size: 14px;
  font-family: 'DM Sans', sans-serif;

  background: white;

  transition: .2s;
}

.contact-form-box input:focus,
.contact-form-box textarea:focus {
  border-color: var(--rose);

  outline: none;

  box-shadow: 0 0 0 4px rgba(201,115,106,.10);
}

.contact-form-box textarea {
  resize: vertical;
}

/* ─────────────────────────
   MAP
───────────────────────── */

.map-section iframe {
  width: 100%;
  height: 420px;
  border: none;
}

/* ─────────────────────────
   CTA
───────────────────────── */

.contact-cta {
  padding: 90px 0;
  text-align: center;
}

.contact-cta h2 {
  font-size: 40px;
  line-height: 1.3;

  max-width: 700px;
  margin: auto auto 14px;
}

.contact-cta p {
  color: var(--muted);
  margin-bottom: 28px;
}

/* ─────────────────────────
   RESPONSIVE
───────────────────────── */

@media (max-width: 900px) {

  .contact-grid {
    grid-template-columns: 1fr;
  }

}

@media (max-width: 768px) {

  .contact-hero {
    padding: 70px 0;
  }

  .contact-hero h1 {
    font-size: 34px;
  }

  .contact-form-box {
    padding: 24px;
  }

  .contact-form-box h2 {
    font-size: 24px;
  }

  .contact-cta h2 {
    font-size: 30px;
  }

}

</style>
<?php // includes/footer.php ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<footer>
  <div class="footer-grid">
    <div>
      <a class="footer-logo" href="<?= $base_path ?? '' ?>index.php">May<span>Florist</span></a>
      <p class="footer-desc">Toko bunga terbaik untuk setiap momen spesial Anda.<br>Melayani seluruh wilayah Jabodetabek.</p>
      <div class="footer-social">
        <a href="#" title="WhatsApp">
          <i class="bi bi-whatsapp"></i>
        </a>

        <a href="#" title="TikTok">
          <i class="bi bi-tiktok"></i>
        </a>

        <a href="#" title="Instagram">
          <i class="bi bi-instagram"></i>
        </a>
      </div>
    </div>
    <div class="footer-links">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= $base_path ?? '' ?>index.php">Home</a></li>
        <li><a href="<?= $base_path ?? '' ?>katalog.php">Produk</a></li>
        <li><a href="<?= $base_path ?? '' ?>tentang.php">Tentang</a></li>
        <li><a href="<?= $base_path ?? '' ?>kontak.php">Kontak</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> MayFlorist. Semua hak dilindungi.</span>
    <span>&#127800; Dibuat dengan cinta dari Subang</span>
  </div>
</footer>

</body>
<style>
  
</style>
</html>

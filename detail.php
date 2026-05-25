<?php
// detail.php — Halaman Detail Produk

include 'includes/products.php';

$slug    = $_GET['slug'] ?? '';
$product = getProductBySlug($slug, $products);

if (!$product) {
    header('Location: katalog.php');
    exit;
}

// Handle tambah ke keranjang
session_start();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $id  = (int)$product['id'];
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] === $id) { $item['qty'] += $qty; $found = true; break; }
    }
    if (!$found) {
        $_SESSION['cart'][] = [
            'id'    => $id,
            'name'  => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'] ?? '',
            'qty'   => $qty,
        ];
    }
    if ($_POST['action'] === 'beli_sekarang') {
        header('Location: keranjang.php');
        exit;
    }
    $success_msg = 'Produk berhasil ditambahkan ke keranjang!';
}

$page_title = htmlspecialchars($product['name']) . ' — Fleuriste';
$active_nav = 'produk';

// Dummy ulasan
$ulasan = [
  ['name' => 'Rina Susanti',   'stars' => 5, 'comment' => 'Bunganya sangat segar dan cantik!',                'date' => '4 hari yang lalu'],
  ['name' => 'Budi Santoso',   'stars' => 5, 'comment' => 'Pengiriman cepat, bunga sampai dalam kondisi baik.', 'date' => '1 minggu yang lalu'],
  ['name' => 'Maya Fitria',    'stars' => 4, 'comment' => 'Sesuai ekspektasi, pasti order lagi.',               'date' => '2 minggu yang lalu'],
];

// Related products (same category, exclude current)
$related = array_filter($products, fn($p) => $p['category'] === $product['category'] && $p['id'] !== $product['id']);
$related = array_slice(array_values($related), 0, 4);

include 'includes/header.php';
?>

<!-- ── BREADCRUMB ── -->
<div class="breadcrumb">
  <a href="index.php">Home</a>
  <span>&rsaquo;</span>
  <a href="katalog.php">Katalog Produk</a>
  <span>&rsaquo;</span>
  <?= htmlspecialchars($product['name']) ?>
</div>

<div class="page-wrapper">

  <?php if ($success_msg): ?>
  <div class="alert alert-success" style="margin-top:20px;">
    &#10003; <?= htmlspecialchars($success_msg) ?>
    <a href="keranjang.php" style="font-weight:600;color:var(--rose);margin-left:8px;">Lihat Keranjang &rarr;</a>
  </div>
  <?php endif; ?>

  <!-- ── PRODUCT DETAIL MAIN ── -->
  <div class="detail-layout">

    <!-- Left: Image -->
    <div class="detail-image-wrap">
      <div class="detail-main-img">
        <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
          <img src="<?= htmlspecialchars($product['image']) ?>"
               alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
          <div class="img-placeholder-lg">&#127800;</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: Info -->
    <div class="detail-info">
      <h1 class="detail-name"><?= htmlspecialchars($product['name']) ?></h1>

      <div class="detail-price">
        <?= formatRupiah($product['price']) ?>
        <?php if (!empty($product['old_price'] ?? '')): ?>
          <span class="detail-old-price"><?= formatRupiah($product['old_price']) ?></span>
        <?php endif; ?>
      </div>

      <div class="detail-rating">
        <span class="stars"><?= str_repeat('★', floor($product['rating'])) ?></span>
        <span class="rating-val"><?= $product['rating'] ?></span>
        <span class="rating-sep">|</span>
        <span class="review-count"><?= count($ulasan) ?> ulasan</span>
      </div>

      <div class="divider"></div>

      <p class="detail-desc"><?= htmlspecialchars($product['desc']) ?></p>

      <div class="detail-meta">
        <span class="meta-label">Kategori:</span>
        <a href="katalog.php?kategori=<?= urlencode($product['category']) ?>"
           class="meta-value category-link">
          <?= htmlspecialchars($product['category']) ?>
        </a>
      </div>

      <div class="divider"></div>

      <!-- Add to Cart Form -->
      <form method="POST" action="detail.php?slug=<?= urlencode($slug) ?>">
        <div class="qty-row">
          <label class="qty-label">Jumlah</label>
          <div class="qty-control">
            <button type="button" onclick="changeQty(-1)">&#8722;</button>
            <input type="number" name="qty" id="qtyInput" value="1" min="1"
                   max="<?= $product['stock'] ?>">
            <button type="button" onclick="changeQty(1)">&#43;</button>
          </div>
          <span class="stock-info">Stok: <?= $product['stock'] ?></span>
        </div>

        <div class="detail-actions">
          <button type="submit" name="action" value="tambah_keranjang"
                  class="btn btn-secondary" style="flex:1;">
            Tambah ke Keranjang
          </button>
          <button type="submit" name="action" value="beli_sekarang"
                  class="btn btn-primary" style="flex:1;">
            Beli Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── TABS ── -->
  <div class="tabs" id="productTabs">
    <button class="tab-btn" onclick="switchTab('deskripsi', this)">Deskripsi Produk</button>
    <button class="tab-btn active" onclick="switchTab('ulasan', this)">
      Ulasan (<?= count($ulasan) ?>)
    </button>
    <button class="tab-btn" onclick="switchTab('pengiriman', this)">Pengiriman</button>
  </div>

  <!-- Tab: Deskripsi -->
  <div id="tab-deskripsi" class="tab-content">
    <div class="tab-body">
      <p><?= nl2br(htmlspecialchars($product['desc'])) ?></p>
      <ul style="margin-top:14px;padding-left:18px;color:var(--muted);font-size:14px;line-height:1.9;">
        <li>Bunga segar pilihan terbaik</li>
        <li>Dirangkai oleh florist berpengalaman</li>
        <li>Tersedia same-day delivery area Jabodetabek</li>
        <li>Packing aman dengan lapisan pelindung ekstra</li>
      </ul>
    </div>
  </div>

  <!-- Tab: Ulasan -->
  <div id="tab-ulasan" class="tab-content active">

    <!-- Ringkasan Rating -->
    <div class="rating-summary">
      <div class="rating-big">
        <div class="rating-big-num"><?= $product['rating'] ?></div>
        <div class="stars" style="font-size:22px;"><?= str_repeat('★', floor($product['rating'])) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;"><?= count($ulasan) ?> ulasan</div>
      </div>
      <div class="rating-bars">
        <?php
        // Hitung distribusi bintang dari $ulasan
        $dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
        foreach ($ulasan as $u) $dist[$u['stars']]++;
        $total_u = count($ulasan) ?: 1;
        for ($s = 5; $s >= 1; $s--):
          $pct = round($dist[$s] / $total_u * 100);
        ?>
        <div class="rating-bar-row">
          <span style="font-size:12px;color:var(--muted);min-width:14px;"><?= $s ?></span>
          <span style="font-size:12px;color:var(--gold);">★</span>
          <div class="rating-bar-track">
            <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
          </div>
          <span style="font-size:12px;color:var(--muted);min-width:28px;"><?= $dist[$s] ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <div style="height:1px;background:var(--border);margin:20px 0;"></div>

    <!-- Daftar Ulasan -->
    <?php if (empty($ulasan)): ?>
      <div style="text-align:center;padding:32px;color:var(--muted);">
        <div style="font-size:36px;margin-bottom:8px;">&#11088;</div>
        <p>Belum ada ulasan untuk produk ini.</p>
        <p style="font-size:13px;margin-top:4px;">Jadilah yang pertama memberi ulasan!</p>
      </div>
    <?php else: ?>
    <?php foreach ($ulasan as $u): ?>
    <div class="review-item">
      <div class="review-header">
        <div class="review-avatar">&#128100;</div>
        <div class="review-meta">
          <div class="review-name"><?= htmlspecialchars($u['name']) ?></div>
          <div class="stars" style="font-size:15px;"><?= str_repeat('★', $u['stars']) ?><?= str_repeat('☆', 5 - $u['stars']) ?></div>
        </div>
        <div class="review-date"><?= htmlspecialchars($u['date']) ?></div>
      </div>
      <p class="review-text"><?= htmlspecialchars($u['comment']) ?></p>
      <span class="verified-badge">&#10003; Pembeli Terverifikasi</span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- CTA login untuk beri ulasan -->
    <?php if (empty($_SESSION['user'])): ?>
    <div style="background:var(--petal);border-radius:8px;padding:14px 18px;margin-top:16px;
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <span style="font-size:14px;color:var(--muted);">Sudah beli produk ini? Bagikan pengalamanmu!</span>
      <a href="login.php" class="btn btn-primary btn-sm">Login untuk Ulasan</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Tab: Pengiriman -->
  <div id="tab-pengiriman" class="tab-content">
    <div class="tab-body">
      <table class="delivery-table">
        <tr><td>Jabodetabek</td><td>Same-day delivery (pesan sebelum pukul 14.00 WIB)</td></tr>
        <tr><td>Jawa (luar Jabodetabek)</td><td>1–2 hari kerja</td></tr>
        <tr><td>Luar Jawa</td><td>2–4 hari kerja</td></tr>
        <tr><td>Ongkos kirim</td><td>Mulai Rp 15.000 (Jabodetabek), sesuai jarak</td></tr>
      </table>
      <p style="margin-top:14px;font-size:13px;color:var(--muted);">
        * Gratis ongkos kirim untuk pembelian minimal Rp 150.000 dalam area Jabodetabek.
      </p>
    </div>
  </div>

  <div class="divider"></div>

  <!-- ── RELATED PRODUCTS (footer style per wireframe) ── -->
  <?php if (!empty($related)): ?>
  <div style="margin-bottom:48px;">
    <h3 style="font-size:18px;margin-bottom:16px;">Produk Serupa</h3>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
      <div style="text-align:center;">
        <a href="detail.php?slug=<?= urlencode($r['slug']) ?>">
          <div class="related-img">
            <?php if (!empty($r['image']) && file_exists($r['image'])): ?>
              <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
            <?php else: ?>
              <span>&#127800;</span>
            <?php endif; ?>
          </div>
        </a>
        <div style="font-size:13px;font-weight:500;color:var(--bark);margin-top:6px;">
          <?= htmlspecialchars($r['name']) ?>
        </div>
        <div style="font-size:13px;color:var(--rose);font-weight:700;margin:2px 0;">
          <?= formatRupiah($r['price']) ?>
        </div>
        <a href="detail.php?slug=<?= urlencode($r['slug']) ?>"
           class="btn btn-outline btn-sm" style="margin-top:6px;width:100%;">
          Lihat Detail
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.detail-layout {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 40px; padding: 36px 0 32px;
}
.detail-main-img {
  background: var(--petal); border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
  aspect-ratio: 4/3;
  display: flex; align-items: center; justify-content: center;
}
.detail-main-img img { width: 100%; height: 100%; object-fit: cover; }
.img-placeholder-lg { font-size: 120px; opacity: .5; }

.detail-name { font-size: 26px; margin-bottom: 10px; }
.detail-price { font-size: 24px; font-weight: 700; color: var(--rose); margin-bottom: 8px; }
.detail-old-price { font-size: 16px; color: var(--muted); text-decoration: line-through; margin-left: 10px; }
.detail-rating { display: flex; align-items: center; gap: 6px; font-size: 14px; margin-bottom: 4px; }
.rating-val { font-weight: 600; color: var(--bark); }
.rating-sep, .review-count { color: var(--muted); }
.detail-desc { font-size: 14px; color: var(--muted); line-height: 1.7; margin-bottom: 12px; }
.detail-meta { font-size: 14px; margin-bottom: 4px; }
.meta-label { color: var(--muted); }
.meta-value { font-weight: 500; margin-left: 4px; }
.category-link:hover { color: var(--rose); }

.qty-row { display: flex; align-items: center; gap: 16px; margin-bottom: 18px; }
.qty-label { font-size: 14px; font-weight: 500; color: var(--bark); min-width: 56px; }
.stock-info { font-size: 13px; color: var(--muted); }

.detail-actions { display: flex; gap: 12px; }

/* Reviews */
.review-item {
  padding: 20px 0; border-bottom: 1px solid var(--border);
}
.review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.review-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: var(--petal); display: flex; align-items: center;
  justify-content: center; font-size: 22px; flex-shrink: 0;
}
.review-meta { flex: 1; }
.review-name { font-weight: 600; font-size: 14px; color: var(--bark); }
.review-date { font-size: 12px; color: var(--muted); }
.review-text { font-size: 14px; color: var(--muted); margin-bottom: 8px; }
.verified-badge {
  font-size: 11px; color: var(--moss); font-weight: 500;
  background: #eaf4e4; padding: 2px 8px; border-radius: 100px;
}

/* Tab body */
.tab-body { font-size: 14px; color: var(--muted); line-height: 1.75; padding: 4px 0 24px; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.delivery-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); color: var(--text); }
.delivery-table tr td:first-child { font-weight: 500; color: var(--bark); width: 200px; }

/* Related */
/* Rating Summary */
.rating-summary {
  display: flex; gap: 32px; align-items: center;
  padding: 20px 0;
}
.rating-big { text-align: center; min-width: 80px; }
.rating-big-num {
  font-family: 'Playfair Display', serif;
  font-size: 48px; font-weight: 600; color: var(--bark); line-height: 1;
}
.rating-bars { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.rating-bar-row { display: flex; align-items: center; gap: 8px; }
.rating-bar-track {
  flex: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;
}
.rating-bar-fill {
  height: 100%; background: var(--gold); border-radius: 4px; transition: width .4s;
}

.related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.related-img {
  background: var(--petal); border: 1px solid var(--border);
  border-radius: 8px; aspect-ratio: 4/3;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.related-img img { width: 100%; height: 100%; object-fit: cover; }
.related-img span { font-size: 48px; opacity: .5; }

@media (max-width: 768px) {
  .detail-layout { grid-template-columns: 1fr; gap: 24px; }
  .detail-actions { flex-direction: column; }
  .related-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<script>
function changeQty(delta) {
  const inp = document.getElementById('qtyInput');
  const max = parseInt(inp.max) || 99;
  inp.value = Math.max(1, Math.min(max, parseInt(inp.value) + delta));
}

function switchTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
}
// default active tab on load
document.querySelector('.tab-btn').click();
</script>

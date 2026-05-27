<?php
// detail.php — Halaman Detail Produk
include 'includes/products.php';

// Ambil ID dari URL jika ada
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Ambil Slug dari URL jika ada
$slug = $_GET['slug'] ?? '';

// Cari produk berdasarkan ID atau Slug
$product = null;

if ($id > 0) {
    // Cari di array $products berdasarkan ID
    foreach ($products as $p) {
        if ((int)$p['id'] === $id) {
            $product = $p;
            break;
        }
    }
} elseif (!empty($slug)) {
    // Tetap gunakan fungsi lama jika yang dikirim adalah slug
    $product = getProductBySlug($slug, $products);
}

// Jika tidak ketemu, lempar ke katalog
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

$id_produk = $product['id']; 

// 1. Ambil data ulasan dari database
$query_ulasan = mysqli_query($conn, "
    SELECT r.*, u.nama_user 
    FROM review r
    JOIN user u ON r.id_user = u.id_user
    WHERE r.id_produk = '$id_produk'
    ORDER BY r.tanggal_review DESC
");

$ulasan = [];
while ($row = mysqli_fetch_assoc($query_ulasan)) {
    $ulasan[] = [
        'name'    => $row['nama_user'], 
        'stars'   => (int)$row['rating'],
        'comment' => $row['komentar'],
        'date'    => date('d M Y', strtotime($row['tanggal_review']))
    ];
}

// 2. Hitung Rating Rata-rata untuk ditampilkan di atas
$query_avg = mysqli_query($conn, "SELECT AVG(rating) as rata_rata FROM review WHERE id_produk = '$id_produk'");
$data_avg = mysqli_fetch_assoc($query_avg);
$avg_rating = $data_avg['rata_rata'] ? round($data_avg['rata_rata'], 1) : 0;

// 3. Hitung Total Produk Terjual (Hanya yang berstatus 'selesai')
$query_terjual = mysqli_query($conn, "
    SELECT COALESCE(SUM(dp.jumlah_produk), 0) as total_terjual 
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE dp.id_produk = '$id_produk' AND p.status_pesanan = 'selesai'
");
$data_terjual = mysqli_fetch_assoc($query_terjual);
$total_terjual = $data_terjual['total_terjual'];

// Related products (same category, exclude current)
$related = array_filter($products, fn($p) => $p['category'] === $product['category'] && $p['id'] !== $product['id']);
$related = array_slice(array_values($related), 0, 4);

include 'includes/header.php';
?>

<div class="breadcrumb reveal-up">
  <a href="index.php">Home</a>
  <span>&rsaquo;</span>
  <a href="katalog.php">Katalog Produk</a>
  <span>&rsaquo;</span>
  <span style="color: var(--bark); font-weight: 500;"><?= htmlspecialchars($product['name']) ?></span>
</div>

<div class="page-wrapper">

  <?php if ($success_msg): ?>
  <div class="alert alert-success animate-alert" style="margin-top:20px; border-left: 4px solid #0288d1;">
    &#10003; <?= htmlspecialchars($success_msg) ?>
    <a href="keranjang.php" style="font-weight:600;color:var(--rose);margin-left:8px; text-decoration: none;">Lihat Keranjang &rarr;</a>
  </div>
  <?php endif; ?>

  <div class="detail-layout">

    <div class="detail-image-wrap reveal-left">
      <div class="detail-main-img">
        <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
          <img src="<?= htmlspecialchars($product['image']) ?>"
               alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
          <div class="img-placeholder-lg">&#127800;</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-info reveal-right">
      <h1 class="detail-name"><?= htmlspecialchars($product['name']) ?></h1>

      <div class="detail-price">
        <?= formatRupiah($product['price']) ?>
        <?php if (!empty($product['old_price'] ?? '')): ?>
          <span class="detail-old-price"><?= formatRupiah($product['old_price']) ?></span>
        <?php endif; ?>
      </div>

      <?php
      $fullStars = floor($avg_rating);
      $halfStar = (($avg_rating - $fullStars) >= 0.25 && ($avg_rating - $fullStars) < 0.75) ? 1 : 0;
      ?>
      <div class="detail-rating" style="display:flex; align-items:center; gap:8px; font-size:14px;">
          <div class="stars" style="color:#ffc107; font-size:20px;">
              <?php for ($i = 1; $i <= $fullStars; $i++): ?>
                  <i class="fa fa-star"></i>
              <?php endfor; ?>
              <?php if ($halfStar): ?>
                  <i class="fa fa-star-half-alt"></i>
              <?php endif; ?>
              <?php for ($i = 1; $i <= (5 - $fullStars - $halfStar); $i++): ?>
                  <i class="fa fa-star-o"></i>
              <?php endfor; ?>
          </div>
          <span class="rating-val"><?= $avg_rating ?></span>
          <span class="rating-sep">|</span>
          <span class="review-count"><?= count($ulasan) ?> ulasan</span>
          <span class="rating-sep">|</span>
          <span class="sold-count" style="color: var(--muted); font-weight: 500;">Terjual <?= $total_terjual ?></span>
      </div>

      <div class="divider"></div>

      <p class="detail-desc"><?= htmlspecialchars($product['desc']) ?></p>

      <div class="detail-meta">
        <span class="meta-label">Kategori:</span>
        <a href="katalog.php?kategori=<?= urlencode($product['category']) ?>"
           class="meta-value category-link" style="color: var(--rose); text-decoration: none; font-weight: 600;">
          <?= htmlspecialchars($product['category']) ?>
        </a>
      </div>

      <div class="divider"></div>

      <form method="POST" action="detail.php?slug=<?= urlencode($slug) ?>">
        <div class="qty-row">
          <label class="qty-label">Jumlah</label>
          <div class="qty-control">
            <button type="button" onclick="changeQty(-1)">&#8722;</button>
            <input type="number" name="qty" id="qtyInput" value="1" min="1"
                   max="<?= $product['stock'] ?>" onchange="validateQty(this)">
            <button type="button" onclick="changeQty(1)">&#43;</button>
          </div>
          <span class="stock-info">Stok tersedia: <strong><?= $product['stock'] ?></strong></span>
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

  <div class="tabs reveal-up" id="productTabs">
    <button class="tab-btn active" onclick="switchTab('deskripsi', this)">Deskripsi Produk</button>
    <button class="tab-btn" onclick="switchTab('ulasan', this)">
      Ulasan (<?= count($ulasan) ?>)
    </button>
    <button class="tab-btn" onclick="switchTab('pengiriman', this)">Pengiriman</button>
  </div>

  <div id="tab-deskripsi" class="tab-content active reveal-up">
    <div class="tab-body">
      <p><?= nl2br(htmlspecialchars($product['desc'])) ?></p>
    </div>
  </div>

  <div id="tab-ulasan" class="tab-content reveal-up">

    <div class="rating-summary">
      <div class="rating-big">
        <div class="rating-big-num"><?= $avg_rating ?></div>
        <div class="stars" style="font-size:20px; color:#ffc107;"><?= str_repeat('★', floor($avg_rating)) ?><?= str_repeat('☆', 5 - floor($avg_rating)) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px;"><?= count($ulasan) ?> ulasan</div>
      </div>
      <div class="rating-bars">
        <?php
        $dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
        foreach ($ulasan as $u) $dist[$u['stars']]++;
        $total_u = count($ulasan) ?: 1;
        for ($s = 5; $s >= 1; $s--):
          $pct = round($dist[$s] / $total_u * 100);
        ?>
        <div class="rating-bar-row">
          <span style="font-size:12px;color:var(--muted);min-width:14px;"><?= $s ?></span>
          <span style="font-size:12px;color:#ffc107;">★</span>
          <div class="rating-bar-track">
            <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
          </div>
          <span style="font-size:12px;color:var(--muted);min-width:28px;"><?= $dist[$s] ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <div style="height:1px;background:var(--border);margin:20px 0;"></div>

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
          <div class="stars" style="font-size:14px; color:#ffc107;"><?= str_repeat('★', $u['stars']) ?><?= str_repeat('☆', 5 - $u['stars']) ?></div>
        </div>
        <div class="review-date"><?= htmlspecialchars($u['date']) ?></div>
      </div>
      <p class="review-text"><?= htmlspecialchars($u['comment']) ?></p>
      <span class="verified-badge">&#10003; Pembeli Terverifikasi</span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($_SESSION['user'])): ?>
    <div style="background:var(--petal);border-radius:8px;padding:14px 18px;margin-top:16px;
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <span style="font-size:14px;color:var(--muted);">Sudah beli produk ini? Bagikan pengalamanmu!</span>
      <a href="login.php" class="btn btn-primary btn-sm" style="padding: 8px 16px; border-radius: 20px; font-size: 13px;">Login untuk Ulasan</a>
    </div>
    <?php endif; ?>
  </div>

  <div id="tab-pengiriman" class="tab-content reveal-up">
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

  <?php if (!empty($related)): ?>
  <div style="margin-bottom:60px;" class="reveal-up">
    <h3 style="font-size:20px; font-weight:700; color:var(--bark); margin-bottom:24px; border-left: 4px solid var(--rose); padding-left: 12px;">
      Produk Serupa
    </h3>
    
    <div class="related-grid">
      <?php foreach ($related as $index => $r): ?>
      <?php
        $r_id = $r['id'];
        // Ambil data terjual untuk produk serupa
        $q_r_terjual = mysqli_query($conn, "
            SELECT COALESCE(SUM(dp.jumlah_produk), 0) as total_terjual 
            FROM detail_pesanan dp
            JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
            WHERE dp.id_produk = '$r_id' AND p.status_pesanan = 'selesai'
        ");
        $d_r_terjual = mysqli_fetch_assoc($q_r_terjual);
        $r_terjual = $d_r_terjual['total_terjual'];

        // Kalkulasi Bintang
        $r_rating = $r['rating'] ?? 0;
        $r_fullStars = floor($r_rating);
        $r_halfStar = (($r_rating - $r_fullStars) >= 0.25 && ($r_rating - $r_fullStars) < 0.75) ? 1 : 0;
        $r_emptyStars = 5 - $r_fullStars - $r_halfStar;
      ?>
      <div class="related-card reveal-up" style="transition-delay: <?= $index * 100 ?>ms; <?= $r['stock'] <= 0 ? 'opacity: 0.75;' : '' ?>">
        <a href="detail.php?slug=<?= urlencode($r['slug'] ?? '') ?>">
          <div class="related-img">
            <?php if (!empty($r['image']) && file_exists($r['image'])): ?>
              <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
            <?php else: ?>
              <div class="img-placeholder">&#127800;</div>
            <?php endif; ?>

            <?php if (!empty($r['badge'])): ?>
              <span class="card-badge" style="position: absolute; top: 8px; left: 8px; background: var(--rose); color: white; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 100px; z-index: 2;"><?= htmlspecialchars($r['badge']) ?></span>
            <?php elseif ($r['stock'] <= 0): ?>
              <span class="card-badge" style="position: absolute; top: 8px; left: 8px; background: #718096; color: white; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 100px; z-index: 2;">Habis</span>
            <?php endif; ?>
          </div>
        </a>
        
        <div class="related-info">
          <div class="related-name"><?= htmlspecialchars($r['name']) ?></div>
          <div class="related-price" style="color: var(--rose); font-weight: 700; margin-bottom: 6px;"><?= formatRupiah($r['price']) ?></div>
          
          <div class="card-meta" style="font-size: 12px; color: var(--muted); margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px;">
              <div style="display: flex; align-items: center; gap: 6px;">
                  <div class="stars" style="color: #ffc107; display: flex; align-items: center; font-size: 13px;">
                      <?php for ($i = 1; $i <= $r_fullStars; $i++): ?>
                          <i class="fa fa-star"></i>
                      <?php endfor; ?>
                      <?php if ($r_halfStar): ?>
                          <i class="fa fa-star-half-alt"></i>
                      <?php endif; ?>
                      <?php for ($i = 1; $i <= $r_emptyStars; $i++): ?>
                          <i class="fa fa-star-o"></i>
                      <?php endfor; ?>
                  </div>
                  <span>(<?= number_format($r_rating, 1) ?>)</span>
              </div>
              
              <div style="display: flex; align-items: center; justify-content: space-between;">
                  <div>
                      <span>Stok: </span>
                      <span style="font-weight: 600; color: <?= ($r['stock'] > 0) ? 'var(--moss)' : 'var(--rose)' ?>;">
                          <?= $r['stock'] > 0 ? $r['stock'] : 'Habis' ?>
                      </span>
                  </div>
                  <span style="color: var(--muted); font-size: 11px; font-weight: 500;">Terjual <?= $r_terjual ?></span>
              </div>
          </div>

          <a href="<?= $r['stock'] > 0 ? 'detail.php?slug=' . urlencode($r['slug'] ?? '') : '#' ?>" 
             class="btn btn-outline btn-sm related-btn" style="<?= $r['stock'] <= 0 ? 'background:#718096; color:white; border-color:#718096; cursor:not-allowed;' : '' ?>">
             <?= $r['stock'] > 0 ? 'Lihat Detail' : 'Stok Habis' ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const observerOptions = { root: null, rootMargin: "0px", threshold: 0.05 };
  const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  const elementsToReveal = document.querySelectorAll(".reveal-up, .reveal-zoom, .reveal-left, .reveal-right");
  elementsToReveal.forEach(el => observer.observe(el));
});

function changeQty(delta) {
  const inp = document.getElementById('qtyInput');
  const max = parseInt(inp.max) || 99;
  let currentVal = parseInt(inp.value) || 1;
  inp.value = Math.max(1, Math.min(max, currentVal + delta));
}

function validateQty(input) {
  const max = parseInt(input.max) || 99;
  if (parseInt(input.value) > max) input.value = max;
  if (parseInt(input.value) < 1 || isNaN(parseInt(input.value))) input.value = 1;
}

function switchTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  const targetTab = document.getElementById('tab-' + id);
  targetTab.classList.add('active');
  btn.classList.add('active');
}
</script>

<style>
body { overflow-x: hidden; }
.reveal-up { opacity: 0; transform: translateY(35px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-zoom { opacity: 0; transform: scale(0.96) translateY(10px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-left { opacity: 0; transform: translateX(-35px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-right { opacity: 0; transform: translateX(35px); transition: opacity 0.7s ease-out, transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-up.in-view, .reveal-zoom.in-view, .reveal-left.in-view, .reveal-right.in-view { opacity: 1; transform: translate(0) scale(1); }
.animate-alert { animation: fadeInDown 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1) forwards; }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.detail-main-img { background: var(--petal); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
.detail-main-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
.detail-main-img:hover img { transform: scale(1.06); }

.qty-control { display: inline-flex; align-items: center; border: 1px solid rgba(183, 110, 121, 0.25); border-radius: 30px; background: #fff; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
.qty-control button { background: none; border: none; width: 36px; height: 36px; font-size: 18px; cursor: pointer; color: var(--bark); transition: background 0.2s, color 0.2s; }
.qty-control button:hover { background: var(--petal); color: var(--rose); }
.qty-control input { width: 44px; height: 36px; border: none; text-align: center; font-weight: 600; font-size: 14px; color: var(--bark); background: transparent; -moz-appearance: textfield; }
.qty-control input::-webkit-outer-spin-button, .qty-control input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.detail-actions .btn { padding: 14px 24px; border-radius: 30px; font-weight: 600; font-size: 14px; transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.25s, background-color 0.25s; }
.detail-actions .btn:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(183, 110, 121, 0.2); }
.detail-actions .btn:active { transform: translateY(-1px); }
.btn-secondary { background: #fff; border: 1px solid var(--rose); color: var(--rose); }
.btn-secondary:hover { background: var(--petal); }

.tabs { display: flex; gap: 12px; border-bottom: 2px solid var(--border); margin-top: 40px; margin-bottom: 20px; }
.tab-btn { background: none; border: none; padding: 12px 20px; font-size: 15px; font-weight: 600; color: var(--muted); cursor: pointer; position: relative; transition: color 0.3s; }
.tab-btn:hover { color: var(--rose); }
.tab-btn.active { color: var(--rose); }
.tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: var(--rose); animation: scaleInX 0.3s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; }
@keyframes scaleInX { from { transform: scaleX(0); } to { transform: scaleX(1); } }
.tab-content { display: none; }
.tab-content.active { display: block; animation: tabFadeIn 0.45s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; }
@keyframes tabFadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

.detail-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding: 24px 0 32px; }
.img-placeholder-lg { font-size: 120px; opacity: .5; }
.detail-name { font-size: 28px; margin-bottom: 10px; color: var(--bark); font-family: 'Playfair Display', serif; }
.detail-price { font-size: 24px; font-weight: 700; color: var(--rose); margin-bottom: 12px; }
.detail-old-price { font-size: 16px; color: var(--muted); text-decoration: line-through; margin-left: 10px; }
.rating-val { font-weight: 600; color: var(--bark); }
.rating-sep, .review-count { color: var(--muted); }
.detail-desc { font-size: 14px; color: var(--muted); line-height: 1.7; margin-bottom: 16px; }
.detail-meta { font-size: 14px; margin-bottom: 4px; }
.meta-label { color: var(--muted); }
.qty-row { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.qty-label { font-size: 14px; font-weight: 500; color: var(--bark); min-width: 56px; }
.stock-info { font-size: 13px; color: var(--muted); }
.detail-actions { display: flex; gap: 12px; }

.review-item { padding: 20px 0; border-bottom: 1px solid var(--border); }
.review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.review-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--petal); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.review-meta { flex: 1; }
.review-name { font-weight: 600; font-size: 14px; color: var(--bark); }
.review-date { font-size: 12px; color: var(--muted); }
.review-text { font-size: 14px; color: var(--muted); margin-bottom: 8px; }
.verified-badge { font-size: 11px; color: #557c3e; font-weight: 500; background: #eaf4e4; padding: 2px 8px; border-radius: 100px; }
.tab-body { font-size: 14px; color: var(--muted); line-height: 1.75; padding: 4px 0 24px; }
.delivery-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.delivery-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); color: var(--text); }
.delivery-table tr td:first-child { font-weight: 500; color: var(--bark); width: 200px; }

.rating-summary { display: flex; gap: 32px; align-items: center; padding: 20px 0; }
.rating-big { text-align: center; min-width: 80px; }
.rating-big-num { font-size: 48px; font-weight: 600; color: var(--bark); line-height: 1; }
.rating-bars { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.rating-bar-row { display: flex; align-items: center; gap: 8px; }
.rating-bar-track { flex: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; }
.rating-bar-fill { height: 100%; background: #ffc107; border-radius: 4px; }

.related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.related-card { background: #fff; padding: 12px; border-radius: 12px; border: 1px solid var(--border); transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease; display: flex; flex-direction: column; height: 100%; }
.related-card:hover { transform: translateY(-6px); box-shadow: 0 10px 24px rgba(183, 110, 121, 0.08); }
.related-img { border-radius: 8px; margin-bottom: 12px; overflow: hidden; aspect-ratio: 4/5; position: relative; background: #fafafa; }
.related-img img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.related-card:hover .related-img img { transform: scale(1.04); }
.related-info { text-align: left; padding: 0 4px; display: flex; flex-direction: column; flex: 1; }
.related-name { font-size: 14px; font-weight: 600; color: var(--bark); margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.related-price { font-size: 14px; color: var(--rose); font-weight: 700; margin-bottom: 10px; }
.related-btn { margin-top: auto; width: 100%; border-radius: 20px; font-size: 12px; padding: 8px; background: transparent; border: 1px solid var(--border); color: var(--muted); text-decoration: none; text-align: center; display: block; box-sizing: border-box; }
.related-btn:hover { background: var(--rose); color: #fff; border-color: var(--rose); }

/* ==========================================================================
   PERBAIKAN RESPONSIVE (Ganti @media lama kamu dengan ini)
   ========================================================================== */

@media (max-width: 768px) {
  /* 1. Ubah layout utama dari 2 kolom (kiri-kanan) menjadi 1 kolom (atas-bawah) */
  .detail-layout { 
    grid-template-columns: 1fr; 
    gap: 24px; 
    padding: 10px 0;
  }

  /* 2. Sesuaikan proporsi gambar di mobile agar tidak terlalu memanjang */
  .detail-main-img {
    aspect-ratio: 1/1; /* Menjadi kotak proporsional di layar HP */
  }
  
  /* 3. Teks judul produk disesuaikan ukurannya */
  .detail-name {
    font-size: 22px;
  }

  /* 4. Tombol aksi (Keranjang & Beli) ditumpuk vertikal agar teks tidak terpotong */
  /* 4. Tombol aksi (Keranjang & Beli) ditumpuk vertikal dengan jarak aman */
  .detail-actions {
    flex-direction: column;
    gap: 14px; /* Ditambah jaraknya agar tidak terlalu mepet */
  }
  .detail-actions .btn {
    width: 100%;
    padding: 15px 20px;    /* Dipertebal agar pas dengan standar ukuran jari di layar sentuh */
    flex: none !important; /* WAJIB: Mematikan flex:1 bawaan inline HTML agar tinggi tombol akurat */
    box-sizing: border-box;
  }
  /* 5. Ringkasan rating bintang diubah jadi rata tengah vertikal */
  .rating-summary {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
    text-align: center;
  }
  .rating-bars {
    width: 100%;
  }

  /* 6. Amankan Tab Navigasi agar bisa di-swipe/scroll ke samping jika layar terlalu sempit */
  .tabs {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 4px;
    gap: 4px;
    -webkit-overflow-scrolling: touch; /* Scroll mulus di iOS */
  }
  .tab-btn {
    padding: 10px 12px;
    font-size: 14px;
    flex-shrink: 0; /* Mencegah tab menciut berantakan */
  }

  /* 7. Ubah tabel pengiriman menjadi baris vertikal agar tidak overflow */
  .delivery-table tr {
    display: flex;
    flex-direction: column;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
  }
  .delivery-table td {
    padding: 2px 0 !important;
    border: none !important;
  }
  .delivery-table tr td:first-child {
    width: 100%;
    font-weight: 600;
    margin-bottom: 2px;
  }

  /* 8. Grid Produk Serupa tetap rapi 2 kolom */
  .related-grid { 
    grid-template-columns: repeat(2, 1fr); 
    gap: 16px; 
  }
}

/* Tambahan optimasi untuk layar smartphone yang sangat kecil (< 480px) */
@media (max-width: 480px) {
  .qty-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .qty-label {
    min-width: unset;
  }
  .related-grid {
    grid-template-columns: 1fr; /* Di HP sangat kecil, produk serupa jadi 1 kolom saja */
  }
}
</style>
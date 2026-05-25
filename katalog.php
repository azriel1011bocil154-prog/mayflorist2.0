<?php
// katalog.php — Halaman Katalog Produk

$page_title = 'Katalog Produk — MayFlorist';
$active_nav = 'produk';

include 'includes/header.php';
include 'includes/products.php';

// FIX: pastikan categories selalu ada
$categories = $categories ?? [];

// kalau belum ada, ambil dari products
if (empty($categories)) {
    $categories = array_unique(array_column($products, 'category'));
    $categories = array_values($categories);
    sort($categories); // FIX kecil biar rapi
    array_unshift($categories, 'Semua Produk');
}

// ── Filter logic ──
$selected_cat    = $_GET['kategori']  ?? 'Semua Produk';
$min_price       = (int)($_GET['harga_min'] ?? 0);
$max_price       = (int)($_GET['harga_max'] ?? 999999);
$selected_rating = (int)($_GET['rating']    ?? 0);
$search_q        = trim($_GET['q'] ?? '');
$per_page        = 8;
$current_page    = max(1, (int)($_GET['page'] ?? 1));

// Apply filters
$filtered = array_filter($products, function($p) use ($selected_cat, $min_price, $max_price, $selected_rating, $search_q) {

    // FIX: safety guard biar tidak error kalau key kosong
    $p['category'] = $p['category'] ?? '';
    $p['price'] = $p['price'] ?? 0;
    $p['rating'] = $p['rating'] ?? 0;
    $p['name'] = $p['name'] ?? '';

    if ($selected_cat !== 'Semua Produk' && $p['category'] !== $selected_cat) return false;
    if ($p['price'] < $min_price || $p['price'] > $max_price) return false;
    if ($selected_rating > 0 && floor($p['rating']) != $selected_rating) return false;
    if ($search_q && stripos($p['name'], $search_q) === false) return false;

    return true;
});

$filtered = array_values($filtered);

$total_items = count($filtered);
$total_pages = max(1, ceil($total_items / $per_page));
$current_page = min($current_page, $total_pages);
$offset  = ($current_page - 1) * $per_page;
$paged   = array_slice($filtered, $offset, $per_page);

// FIX helper
function buildQuery($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}
?>

<!-- ── PAGE HEADING ── -->
<div class="katalog-heading">
  <div class="page-wrapper">
    <h1>Katalog Produk</h1>
    <?php if ($search_q): ?>
      <p>Menampilkan hasil untuk: <strong>"<?= htmlspecialchars($search_q) ?>"</strong></p>
    <?php endif; ?>
  </div>
</div>

<div class="page-wrapper">
  <div class="katalog-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="katalog-sidebar">
      <form method="GET" action="katalog.php" id="filterForm">

        <?php if ($search_q): ?>
          <input type="hidden" name="q" value="<?= htmlspecialchars($search_q) ?>">
        <?php endif; ?>

        <!-- Kategori -->
        <div class="sidebar-box">
          <h4>Kategori</h4>
          <ul class="cat-list">
            <?php foreach ($categories as $cat): ?>
            <li>
              <label class="cat-option <?= $selected_cat === $cat ? 'active' : '' ?>">
                <input type="radio" name="kategori" value="<?= htmlspecialchars($cat) ?>"
                       <?= $selected_cat === $cat ? 'checked' : '' ?>
                       onchange="document.getElementById('filterForm').submit()">
                <?= htmlspecialchars($cat) ?>
              </label>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Filter Harga -->
        <div class="sidebar-box">
          <h4>Filter Harga</h4>
          <div class="price-display">
            <span id="priceMin"><?= formatRupiah(max(0, $min_price)) ?></span>
            &nbsp;—&nbsp;
            <span id="priceMax"><?= formatRupiah($max_price ?: 500000) ?></span>
          </div>

          <input type="range" name="harga_min" id="rangeMin"
                 min="0" max="500000" step="10000"
                 value="<?= $min_price ?>"
                 oninput="updatePrice()"
                 style="width:100%;margin:8px 0 4px">

          <input type="range" name="harga_max" id="rangeMax"
                 min="0" max="500000" step="10000"
                 value="<?= $max_price ?: 500000 ?>"
                 oninput="updatePrice()"
                 style="width:100%">

          <button type="submit" class="btn btn-primary btn-sm btn-full" style="margin-top:10px;">
            Terapkan
          </button>
        </div>

        <!-- Rating -->
        <div class="sidebar-box">
          <h4>Rating</h4>
          <p class="sidebar-sublabel">Pilih Rating:</p>

          <?php for ($r = 5; $r >= 1; $r--): ?>
          <label class="radio-option" style="margin-bottom:8px;">
            <input type="radio" name="rating" value="<?= $r ?>"
                   <?= $selected_rating === $r ? 'checked' : '' ?>
                   onchange="document.getElementById('filterForm').submit()">

            <span class="stars"><?= str_repeat('★', $r) ?><?= str_repeat('☆', 5 - $r) ?></span>
            <?= $r ?> bintang
          </label>
          <?php endfor; ?>

        </div>

      </form>
    </aside>

    <!-- ── MAIN CONTENT ── -->
    <main class="katalog-main">

      <div class="katalog-main-header">
        <h2>Katalog Produk</h2>
        <span class="result-count"><?= $total_items ?> produk ditemukan</span>
      </div>

      <div style="text-align:center;margin-bottom:20px;">
        <a href="katalog.php" class="btn btn-rose" style="padding:10px 32px;">
          Belanja Sekarang
        </a>
      </div>

      <?php if (empty($paged)): ?>
        <div class="empty-state">
          <div style="font-size:56px;margin-bottom:12px;">&#127800;</div>
          <p>Tidak ada produk yang sesuai dengan filter.</p>
        </div>
      <?php else: ?>

      <div class="products-grid-catalog">
        <?php foreach ($paged as $p): ?>
        <div class="product-card">

          <a href="detail.php?slug=<?= urlencode($p['slug'] ?? '') ?>">
            <div class="card-img">

              <?php if (!empty($p['image']) && file_exists($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              <?php else: ?>
                <div class="img-placeholder">&#127800;</div>
              <?php endif; ?>

              <?php if (!empty($p['badge'])): ?>
                <span class="card-badge"><?= htmlspecialchars($p['badge']) ?></span>
              <?php endif; ?>

            </div>
          </a>

          <div class="card-body">
            <div class="card-name"><?= htmlspecialchars($p['name']) ?></div>

            <div class="card-price">
              <?= formatRupiah($p['price']) ?>

              <?php if (!empty($p['old_price'])): ?>
                <span class="card-old-price">
                  <?= formatRupiah($p['old_price']) ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="card-rating">
              <span class="stars"><?= str_repeat('★', floor($p['rating'])) ?></span>
              <span><?= $p['rating'] ?></span>
            </div>

            <a href="detail.php?slug=<?= urlencode($p['slug'] ?? '') ?>"
               class="btn btn-primary btn-sm btn-full">
               Lihat Detail
            </a>

          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>

    </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.katalog-heading {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 28px 0 20px;
  text-align: center;
}
.katalog-heading h1 { font-size: 28px; margin-bottom: 4px; }
.katalog-heading p { font-size: 14px; color: var(--muted); }

.katalog-layout {
  display: grid; grid-template-columns: var(--sidebar-w) 1fr;
  gap: 32px; padding: 32px 0 48px;
}
.katalog-sidebar { align-self: start; }
.sidebar-box {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; padding: 18px 16px; margin-bottom: 16px;
}
.sidebar-box h4 {
  font-size: 14px; font-weight: 600; color: var(--bark);
  margin-bottom: 12px; padding-bottom: 8px;
  border-bottom: 1px solid var(--border);
}
.sidebar-sublabel { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.cat-list {
  display: flex;
  flex-direction: column;
  gap: 4px;

  max-height: 150px;
  overflow-y: auto;
  padding-right: 4px;
}
.cat-list::-webkit-scrollbar {
  width: 6px;
}

.cat-list::-webkit-scrollbar-thumb {
  background: var(--rose-light);
  border-radius: 100px;
}

.cat-list::-webkit-scrollbar-thumb:hover {
  background: var(--rose);
}
.cat-option {
  display: flex; align-items: center; gap: 8px;
  cursor: pointer; padding: 5px 8px; border-radius: 6px;
  font-size: 14px; color: var(--text); transition: background .15s;
}
.cat-option:hover { background: var(--petal); }
.cat-option.active { background: var(--rose-light); color: var(--rose-dark); font-weight: 500; }
.cat-option input[type="radio"] { display: none; }
.price-display { font-size: 13px; color: var(--muted); }

.katalog-main-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 16px;
}
.katalog-main-header h2 { font-size: 20px; }
.result-count { font-size: 13px; color: var(--muted); }

.products-grid-catalog {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.card-old-price { font-size: 12px; color: var(--muted); text-decoration: line-through; margin-left: 6px; }
.card-badge {
  position: absolute; top: 8px; left: 8px;
  background: var(--rose); color: white;
  font-size: 10px; font-weight: 600;
  padding: 2px 8px; border-radius: 100px;
}
.empty-state {
  text-align: center; padding: 60px 24px; color: var(--muted);
}

@media (max-width: 900px) {
  .katalog-layout { grid-template-columns: 1fr; }
  .katalog-sidebar { display: none; }
  .products-grid-catalog { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 500px) {
  .products-grid-catalog { grid-template-columns: 1fr; }
}
</style>

<script>
function updatePrice() {
  const mn = document.getElementById('rangeMin').value;
  const mx = document.getElementById('rangeMax').value;
  document.getElementById('priceMin').textContent = 'Rp ' + parseInt(mn).toLocaleString('id-ID');
  document.getElementById('priceMax').textContent = 'Rp ' + parseInt(mx).toLocaleString('id-ID');
}
</script>

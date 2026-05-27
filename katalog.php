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
    sort($categories); 
    array_unshift($categories, 'Semua Produk');
}

// ── Filter logic ──
$selected_cat    = $_GET['kategori']  ?? 'Semua Produk';
$min_price       = (int)($_GET['harga_min'] ?? 0);
$max_price       = (int)($_GET['harga_max'] ?? 500000); 
$selected_rating = (int)($_GET['rating']    ?? 0);
$search_q        = trim($_GET['q'] ?? '');

// SETTING BATASAN HALAMAN
$per_page        = 8; 
$current_page    = max(1, (int)($_GET['page'] ?? 1));

// Apply filters
$filtered = array_filter($products, function($p) use ($selected_cat, $min_price, $max_price, $selected_rating, $search_q) {
    if ($selected_cat !== 'Semua Produk' && $p['category'] !== $selected_cat) {
        return false;
    }
    if ($p['price'] < $min_price || $p['price'] > $max_price) {
        return false;
    }
    
    // FIX DISINI: Diubah menjadi pencarian spesifik (Exact Match)
    // Jika user memilih rating tertentu, saring produk yang nilai bulat bawahnya tidak sama dengan pilihan
    if ($selected_rating > 0 && floor($p['rating']) != $selected_rating) {
        return false;
    }
    
    if ($search_q !== '' && stripos($p['name'], $search_q) === false) {
        return false;
    }
    return true;
});

$filtered = array_values($filtered);

// Logika Sortir Stok
usort($filtered, function($a, $b) {
    $a_ada_stok = $a['stock'] > 0 ? 1 : 0;
    $b_ada_stok = $b['stock'] > 0 ? 1 : 0;
    if ($a_ada_stok !== $b_ada_stok) {
        return $b_ada_stok - $a_ada_stok; 
    }
    return 0; 
});

$total_items = count($filtered);
$total_pages = max(1, ceil($total_items / $per_page));
$current_page = min($current_page, $total_pages);
$offset  = ($current_page - 1) * $per_page;
$paged   = array_slice($filtered, $offset, $per_page); 

function buildQuery($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}
?>

<div class="katalog-heading reveal-up">
  <div class="page-wrapper">
    <h1>Katalog Produk</h1>
    <?php if ($search_q): ?>
      <p>Menampilkan hasil untuk: <strong>"<?= htmlspecialchars($search_q) ?>"</strong></p>
    <?php endif; ?>
  </div>
</div>

<div class="filter-mobile-overlay" id="filterOverlay" onclick="toggleMobileFilter()"></div>

<div class="page-wrapper">
  <div class="katalog-layout">

    <aside class="katalog-sidebar reveal-zoom" id="katalogSidebar">
      <form method="GET" action="katalog.php" id="filterForm">

        <div class="sidebar-mobile-header">
          <h3>Filter Pencarian</h3>
          <button type="button" class="close-sidebar-btn" onclick="toggleMobileFilter()">&times;</button>
        </div>

        <?php if ($search_q): ?>
          <input type="hidden" name="q" value="<?= htmlspecialchars($search_q) ?>">
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 0 4px;">
          <h3 class="sidebar-title-desktop" style="font-size: 16px; font-weight: 600; color: var(--bark); margin: 0;">Filter</h3>
          
          <?php 
          $has_filter = isset($_GET['kategori']) || isset($_GET['harga_min']) || isset($_GET['harga_max']) || isset($_GET['rating']);
          if ($has_filter || $search_q): 
            $reset_url = $search_q ? 'katalog.php?q=' . urlencode($search_q) : 'katalog.php';
          ?>
            <a href="<?= $reset_url ?>" class="btn-reset-filter">
              &#x21BB; Reset Filter
            </a>
          <?php endif; ?>
        </div>

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

        <div class="sidebar-box">
          <h4>Filter Harga</h4>
          <div class="price-display">
            <span id="priceMin">Rp <?= number_format($min_price, 0, ',', '.') ?></span>
            &nbsp;—&nbsp;
            <span id="priceMax">Rp <?= number_format($max_price, 0, ',', '.') ?></span>
          </div>

          <input type="range" name="harga_min" id="rangeMin"
                 min="0" max="500000" step="10000"
                 value="<?= $min_price ?>"
                 oninput="updatePrice()"
                 style="width:100%; margin: 8px 0 4px;">

          <input type="range" name="harga_max" id="rangeMax"
                 min="0" max="500000" step="10000"
                 value="<?= $max_price ?>"
                 oninput="updatePrice()"
                 style="width:100%;">

          <button type="submit" class="btn btn-primary btn-sm btn-full" style="margin-top:12px;">
            Terapkan Harga
          </button>
        </div>

        <div class="sidebar-box">
          <h4>Rating</h4>
          <p class="sidebar-sublabel">Pilih Rating Spesifik:</p>
          <?php for ($r = 5; $r >= 1; $r--): ?>
          <label class="radio-option" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 14px;">
            <input type="radio" name="rating" value="<?= $r ?>"
                   <?= $selected_rating === $r ? 'checked' : '' ?>
                   onchange="document.getElementById('filterForm').submit()">
            <span class="stars" style="color: #ffc107;"><?= str_repeat('★', $r) ?><?= str_repeat('☆', 5 - $r) ?></span>
            <?= $r ?> Bintang
          </label>
          <?php endfor; ?>
        </div>

      </form>
    </aside>

    <main class="katalog-main">

      <div class="katalog-main-header reveal-up">
        <h2>Katalog Produk</h2>
        
        <div class="header-controls">
          <span class="result-count"><?= $total_items ?> produk ditemukan</span>
          
          <button type="button" class="btn-trigger-filter-mobile" onclick="toggleMobileFilter()">
            <i class="fas fa-sliders-h"></i> Filter Toko
          </button>
        </div>
      </div>

      <div style="text-align:center;margin-bottom:20px;" class="reveal-up">
        <a href="katalog.php" class="btn btn-rose" style="padding:10px 32px;">
          Belanja Sekarang
        </a>
      </div>

      <?php if (empty($paged)): ?>
        <div class="empty-state reveal-zoom">
          <div style="font-size:56px;margin-bottom:12px;">&#127800;</div>
          <p>Tidak ada produk yang sesuai dengan filter.</p>
        </div>
      <?php else: ?>

      <div class="products-grid-catalog">
        <?php foreach ($paged as $index => $p): ?>
        <?php
          $rating_bintang = round($p['rating']);
          $delay = ($index % 4) * 100;
          
          $id_p = $p['id'];
          $query_p_terjual = mysqli_query($conn, "
              SELECT COALESCE(SUM(dp.jumlah_produk), 0) as total_terjual 
              FROM detail_pesanan dp
              JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
              WHERE dp.id_produk = '$id_p' AND p.status_pesanan = 'selesai'
          ");
          $data_p_terjual = mysqli_fetch_assoc($query_p_terjual);
          $p_terjual = $data_p_terjual['total_terjual'] ?? 0;
        ?>
        
        <div class="product-card reveal-up" style="position: relative; background: var(--white); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; transition-delay: <?= $delay ?>ms; <?= $p['stock'] <= 0 ? 'opacity: 0.75;' : '' ?>">

          <a href="detail.php?slug=<?= urlencode($p['slug'] ?? '') ?>">
            <div class="card-img" style="position: relative; height: 200px; background: #fafafa; display: flex; align-items: center; justify-content: center;">

              <?php if (!empty($p['image']) && file_exists($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; <?= $p['stock'] <= 0 ? 'filter: grayscale(40%);' : '' ?>">
              <?php else: ?>
                <div class="img-placeholder" style="font-size: 48px;">&#127800;</div>
              <?php endif; ?>

              <?php if (!empty($p['badge'])): ?>
                <span class="card-badge"><?= htmlspecialchars($p['badge']) ?></span>
              <?php elseif ($p['stock'] <= 0): ?>
                <span class="card-badge" style="background: #718096;">Habis</span>
              <?php endif; ?>

            </div>
          </a>

          <div class="card-body" style="padding: 14px;">
            <div class="card-name" style="font-weight: 600; font-size: 15px; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($p['name']) ?></div>

            <div class="card-price" style="font-size: 14px; font-weight: 600; color: var(--rose); margin-bottom: 6px;">
              Rp <?= number_format($p['price'], 0, ',', '.') ?>

              <?php if (!empty($p['old_price'])): ?>
                <span class="card-old-price">
                  Rp <?= number_format($p['old_price'], 0, ',', '.') ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="card-meta" style="font-size: 12px; color: var(--muted); margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <?php
                    $rating = $p['rating'];
                    $fullStars = floor($rating);
                    $halfStar = (($rating - $fullStars) >= 0.25 && ($rating - $fullStars) < 0.75) ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    ?>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <div class="stars" style="color: #ffc107; display: flex; align-items: center;">
                            <?php for ($i = 1; $i <= $fullStars; $i++): ?>
                                <i class="fa fa-star"></i>
                            <?php endfor; ?>
                            <?php if ($halfStar): ?>
                                <i class="fa fa-star-half-alt"></i>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $emptyStars; $i++): ?>
                                <i class="fa fa-star-o"></i>
                            <?php endfor; ?>
                        </div>
                        <span>(<?= number_format($rating, 1) ?>)</span>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span>Stok: </span>
                        <span style="font-weight: 600; color: <?= ($p['stock'] > 0) ? 'var(--moss)' : 'var(--rose)' ?>;">
                            <?= $p['stock'] > 0 ? $p['stock'] : 'Habis' ?>
                        </span>
                    </div>
                    <span style="color: var(--muted); font-size: 11px; font-weight: 500;">Terjual <?= $p_terjual ?></span>
                </div>
            </div>

            <a href="<?= $p['stock'] > 0 ? 'detail.php?slug=' . urlencode($p['slug']) : '#' ?>"
              class="btn btn-primary btn-sm btn-full" 
              style="display: block; text-align: center; text-decoration: none; <?= $p['stock'] <= 0 ? 'background:#718096; border-color:#718096; cursor:not-allowed;' : '' ?>">
              <?= $p['stock'] > 0 ? 'Lihat Detail' : 'Stok Habis' ?>
            </a>

          </div>
        </div>
        <?php endforeach; ?>
      </div> 

      <?php if ($total_pages > 1): ?>
      <div class="pagination reveal-up" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 40px; margin-bottom: 20px;">
          
          <?php if ($current_page > 1): ?>
              <a href="katalog.php?<?= buildQuery(['page' => $current_page - 1]) ?>" class="page-link">&laquo; Prev</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="katalog.php?<?= buildQuery(['page' => $i]) ?>" class="page-link <?= $current_page === $i ? 'active' : '' ?>">
                  <?= $i ?>
              </a>
          <?php endfor; ?>

          <?php if ($current_page < $total_pages): ?>
              <a href="katalog.php?<?= buildQuery(['page' => $current_page + 1]) ?>" class="page-link">Next &raquo;</a>
          <?php endif; ?>
          
      </div>  
      <?php endif; ?>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const observerOptions = { root: null, rootMargin: "0px", threshold: 0.1 };
  const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        observer.unobserve(entry.target); 
      }
    });
  }, observerOptions);
  const elementsToReveal = document.querySelectorAll(".reveal-up, .reveal-zoom");
  elementsToReveal.forEach(el => observer.observe(el));
});

function updatePrice() {
  const mn = document.getElementById('rangeMin').value;
  const mx = document.getElementById('rangeMax').value;
  document.getElementById('priceMin').textContent = 'Rp ' + parseInt(mn).toLocaleString('id-ID');
  document.getElementById('priceMax').textContent = 'Rp ' + parseInt(mx).toLocaleString('id-ID');
}

// FUNGSI BARU: Buka / Tutup Sidebar Filter di Handphone
function toggleMobileFilter() {
  const sidebar = document.getElementById('katalogSidebar');
  const overlay = document.getElementById('filterOverlay');
  
  if(sidebar && overlay) {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
    
    // Cegah body scrolling saat filter terbuka
    if(sidebar.classList.contains('open')) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }
}
</script>

<style>
  
body { overflow-x: hidden; }
.reveal-up { opacity: 0; transform: translateY(50px); transition: opacity 0.75s ease-out, transform 0.75s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.reveal-zoom { opacity: 0; transform: scale(0.92) translateY(15px); transition: opacity 0.75s ease-out, transform 0.75s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.reveal-up.in-view { opacity: 1; transform: translateY(0); }
.reveal-zoom.in-view { opacity: 1; transform: scale(1) translateY(0); }

.katalog-heading { background: var(--white); border-bottom: 1px solid var(--border); padding: 28px 0 20px; text-align: center; }
.katalog-heading h1 { font-size: 28px; margin-bottom: 4px; }
.katalog-heading p { font-size: 14px; color: var(--muted); }

.katalog-layout { display: grid; grid-template-columns: var(--sidebar-w, 240px) 1fr; gap: 32px; padding: 32px 0 48px; }
.katalog-sidebar { align-self: start; z-index: 100; }
.sidebar-box { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 18px 16px; margin-bottom: 16px; }
.sidebar-box h4 { font-size: 14px; font-weight: 600; color: var(--bark); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
.sidebar-sublabel { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.cat-list { display: flex; flex-direction: column; gap: 4px; max-height: 150px; overflow-y: auto; padding-right: 4px; list-style: none; padding-left: 0; margin: 0; }
.cat-list::-webkit-scrollbar { width: 6px; }
.cat-list::-webkit-scrollbar-thumb { background: var(--rose-light); border-radius: 100px; }
.cat-list::-webkit-scrollbar-thumb:hover { background: var(--rose); }
.cat-option { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 5px 8px; border-radius: 6px; font-size: 14px; color: var(--text); transition: background .15s; }
.cat-option:hover { background: var(--petal); }
.cat-option.active { background: var(--rose-light); color: var(--rose-dark); font-weight: 500; }
.cat-option input[type="radio"] { display: none; }
.price-display { font-size: 13px; color: var(--muted); margin-bottom: 8px; font-weight: 500; }

.katalog-main-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.katalog-main-header h2 { font-size: 20px; margin: 0; }
.header-controls { display: flex; align-items: center; gap: 16px; }
.result-count { font-size: 13px; color: var(--muted); }

/* ELEMEN BARU KHUSUS MOBILE DRAWER & TOGGLE BUTTON */
.btn-trigger-filter-mobile { display: none; }
.sidebar-mobile-header { display: none; }
.filter-mobile-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(3px); opacity: 0; visibility: hidden; transition: all 0.3s ease; z-index: 10000; }
.filter-mobile-overlay.open { opacity: 1; visibility: visible; }

.products-grid-catalog { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.product-card { transition-property: transform, box-shadow, border-color, opacity; transition-duration: 0.4s; }
.product-card.in-view:hover { transform: translateY(-8px); box-shadow: 0 12px 24px rgba(183, 110, 121, 0.15); border-color: rgba(183, 110, 121, 0.3) !important; }
.card-old-price { font-size: 11px; color: var(--muted); text-decoration: line-through; margin-left: 6px; }
.card-badge { position: absolute; top: 8px; left: 8px; background: var(--rose); color: white; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 100px; z-index: 2; }
.empty-state { text-align: center; padding: 60px 24px; color: var(--muted); }
.btn-reset-filter { font-size: 12px; color: var(--rose); text-decoration: none; font-weight: 500; padding: 4px 10px; border: 1px solid var(--rose-light); border-radius: 6px; background: var(--white); transition: all 0.2s ease; }
.btn-reset-filter:hover { background: var(--petal); border-color: var(--rose); box-shadow: 0 2px 4px rgba(201,115,106,0.1); }

.pagination .page-link { display: flex; align-items: center; justify-content: center; min-width: 42px; height: 42px; padding: 0 16px; border: 1px solid var(--border); border-radius: 10px; text-decoration: none; color: var(--text); font-size: 14px; font-weight: 500; background: var(--white); transition: all 0.2s ease; white-space: nowrap; line-height: 1; }
.pagination .page-link:hover { background: var(--petal); border-color: var(--rose); color: var(--rose); }
.pagination .page-link.active { background: var(--rose); border-color: var(--rose); color: #fff; font-weight: 600; }

/* MEDIA QUERIES RESPONSIVE MOBILE */
@media (max-width: 900px) {
  .katalog-layout { grid-template-columns: 1fr; padding-top: 16px; }
  
  /* Sembunyikan Text Tulisan Filter Bawaan Desktop */
  .sidebar-title-desktop { display: none; }
  
  /* TOMBOL STRATEGIS UNTUK MEMBUKA FILTER DI MOBILE */
  .btn-trigger-filter-mobile {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bloom-rose, #b76e79);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(183,110,121,0.25);
    transition: all 0.2s;
  }
  .btn-trigger-filter-mobile:hover { background: var(--bloom-dark, #3d2c28); }

  /* HEADER DRAWER DI HP */
  .sidebar-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f5f5f5;
  }
  .sidebar-mobile-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: var(--bloom-dark); }
  .close-sidebar-btn { background: none; border: none; font-size: 30px; cursor: pointer; color: #aaa; line-height: 1; }
  .close-sidebar-btn:hover { color: var(--bloom-rose); }

  /* UBAH SIDEBAR MENJADI DRAWER/MENU SAMPING YANG TERSEMBUNYI */
  .katalog-sidebar {
    position: fixed;
    top: 0;
    left: -320px; /* Tersembunyi di kiri layar luar */
    width: 300px;
    height: 100vh;
    background: white;
    z-index: 100001; /* Di atas segalanya */
    box-shadow: 5px 0 25px rgba(0,0,0,0.15);
    padding: 24px 20px;
    box-sizing: border-box;
    overflow-y: auto;
    transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  }
  /* Saat Class .open Ditambahkan oleh JS */
  .katalog-sidebar.open {
    left: 0;
  }

  .products-grid-catalog { grid-template-columns: repeat(2, 1fr); }
  .products-grid-catalog .product-card { transition-delay: 0ms !important; }
}

@media (max-width: 500px) {
  .products-grid-catalog { grid-template-columns: 1fr; }
  .katalog-main-header { flex-direction: column; align-items: flex-start; gap: 10px; }
  .header-controls { width: 100%; justify-content: space-between; }
  .reveal-up, .reveal-zoom { transition-delay: 0ms !important; }
}
</style>
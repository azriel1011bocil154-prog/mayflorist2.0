<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// keranjang.php — Halaman Keranjang Belanja

include 'includes/products.php';

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'update') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) { $item['qty'] = $qty; break; }
        }
    } elseif ($action === 'hapus') {
        $_SESSION['cart'] = array_values(
            array_filter($_SESSION['cart'], fn($i) => $i['id'] !== $id)
        );
    }
    header('Location: keranjang.php');
    exit;
}
include 'includes/header.php';
$cart = $_SESSION['cart'] ?? [];

$total = array_sum(
    array_map(fn($i) => $i['price'] * $i['qty'], $cart)
);

// Recommended products (not in cart)
$cart_ids  = array_column($cart, 'id');

$recommended = array_values(
    array_filter($products, fn($p) => !in_array($p['id'], $cart_ids))
);

$recommended = array_slice($recommended, 0, 3);
?>

<div class="breadcrumb reveal-down">
  <a href="index.php">Home</a>
  <span>&rsaquo;</span>
  <span style="color: var(--bark); font-weight: 500;">Keranjang Belanja</span>
</div>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:60px;">
  <h1 class="page-heading reveal-down">Keranjang Belanja</h1>

  <?php if (empty($cart)): ?>

    <div class="empty-cart reveal-zoom">
      <div class="floating-icon" style="font-size:72px;margin-bottom:16px;">&#127800;</div>
      <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Keranjang Kamu Kosong</h3>
      <p style="color:var(--muted);margin-bottom:24px;">Yuk, temukan buket bunga terbaik untuk kamu!</p>
      <a href="katalog.php" class="btn btn-primary cta-pulse" style="padding:12px 36px; border-radius:30px;">Mulai Belanja</a>
    </div>

  <?php else: ?>

  <div class="keranjang-layout">
    <div class="keranjang-main reveal-up">
      <div class="table-responsive">
        <table class="cart-table">
          <thead>
            <tr>
              <th colspan="2">Produk</th>
              <th>Harga</th>
              <th>Jumlah</th>
              <th>Subtotal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart as $index => $item): ?>
            <tr class="cart-row" style="animation-delay: <?= $index * 0.1 ?>s;">
              <td class="td-img">
                <div class="cart-item-img">
                  <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="">
                  <?php else: ?>
                    <span>&#127800;</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="td-name">
                <div class="cart-name"><?= htmlspecialchars($item['name']) ?></div>
              </td>

              <td class="td-price"><?= formatRupiah($item['price']) ?></td>

              <td class="td-qty">
                <form method="POST" action="keranjang.php" class="qty-form" style="display:flex;align-items:center;gap:6px;">
                  
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">

                  <div class="qty-control">
                    <button type="button" onclick="changeQty(this, -1)">&#8722;</button>

                    <input 
                      type="number"
                      name="qty"
                      value="<?= $item['qty'] ?>"
                      min="1"
                      max="99"
                      onchange="this.form.submit()"
                    >

                    <button type="button" onclick="changeQty(this, 1)">&#43;</button>
                  </div>

                </form>
              </td>

              <td class="td-subtotal"><?= formatRupiah($item['price'] * $item['qty']) ?></td>

              <td class="td-aksi">
                <form method="POST" action="keranjang.php">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm action-delete">
                    <i class="fa fa-trash"></i> Hapus
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <aside class="ringkasan-box reveal-left sticky-aside">
      <h3>Ringkasan Belanja</h3>
      <div class="divider" style="margin:16px 0;"></div>

      <?php foreach ($cart as $item): ?>
      <div class="ringkasan-row">
        <span class="ringkasan-item-name"><?= htmlspecialchars($item['name']) ?> <span style="color:var(--rose); font-weight:600;">(×<?= $item['qty'] ?>)</span></span>
        <span class="ringkasan-item-price"><?= formatRupiah($item['price'] * $item['qty']) ?></span>
      </div>
      <?php endforeach; ?>

      <div class="divider" style="margin:16px 0;"></div>
      <div class="ringkasan-total">
        <span>Total Belanja:</span>
        <span class="total-amount"><?= formatRupiah($total) ?></span>
      </div>

      <a href="checkout.php" class="btn btn-primary btn-full checkout-btn" style="margin-top:24px;padding:14px; border-radius:30px; font-weight:600;">
        Lanjut ke Checkout
      </a>
      <a href="katalog.php" class="btn btn-outline btn-full back-btn" style="margin-top:12px;padding:12px; border-radius:30px; border-width: 2px;">
        Lanjut Belanja
      </a>
    </aside>
  </div>

  <?php endif; ?>

  <?php if (!empty($recommended)): ?>
<div style="margin-top:60px;" class="reveal-up">
  <div class="divider"></div>
  <h2 style="font-size:22px;margin-bottom:24px; font-family:'Playfair Display', serif; color:var(--bark);">
    Mungkin Anda Juga Suka
  </h2>

  <div class="rekomendasi-grid">

    <?php foreach ($recommended as $index => $p): ?>

      <?php
        $name   = $p['name'] ?? 'Produk';
        $price  = $p['price'] ?? 0;
        $slug   = $p['slug'] ?? '';
        $image  = $p['image'] ?? '';
        $rating = $p['rating'] ?? 0;
      ?>

      <div class="rekomendasi-card reveal-up" style="transition-delay: <?= $index * 100 ?>ms;" onclick="window.location.href='detail.php?slug=<?= urlencode($slug) ?>'">
        
        <div class="rek-img-wrapper">
            <?php if (!empty($image) && file_exists($image)): ?>
              <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>">
            <?php else: ?>
              <span style="font-size: 24px;">🌸</span>
            <?php endif; ?>
        </div>

        <div class="rek-info">
          <div class="rek-name"><?= htmlspecialchars($name) ?></div>
          <div class="rek-price"><?= formatRupiah($price) ?></div>
          <div class="rek-rating">
            <span style="color:#ffc107; font-size:12px;"><?= str_repeat('★', floor($rating)) ?></span>
          </div>
          <a href="detail.php?slug=<?= urlencode($slug) ?>" class="rek-btn">
            Lihat Produk
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
  const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.05
  };

  const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in-view");
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  const elementsToReveal = document.querySelectorAll(".reveal-up, .reveal-down, .reveal-zoom, .reveal-left, .reveal-right");
  elementsToReveal.forEach(el => observer.observe(el));
});

function changeQty(btn, delta) {
  const form = btn.closest('.qty-form');
  const inp  = form.querySelector('input[name="qty"]');
  let value = parseInt(inp.value) || 1;
  
  value += delta;
  if (value < 1) {
    value = 1;
  }
  
  inp.value = value;
  
  form.style.opacity = '0.5';
  form.submit();
}
</script>

<style>
/* ==========================================
   CSS SCROLL REVEAL ENGINE
   ========================================== */
.reveal-up { opacity: 0; transform: translateY(35px); transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-down { opacity: 0; transform: translateY(-20px); transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-zoom { opacity: 0; transform: scale(0.96) translateY(10px); transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-left { opacity: 0; transform: translateX(35px); transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }
.reveal-right { opacity: 0; transform: translateX(-35px); transition: all 0.7s cubic-bezier(0.165, 0.84, 0.44, 1); }

.in-view { opacity: 1; transform: translate(0) scale(1); }

/* Floating Icon Animation */
.floating-icon {
  display: inline-block;
  animation: floatIcon 3s ease-in-out infinite;
}
@keyframes floatIcon {
  0% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0); }
}

/* Call to action pulse */
.cta-pulse { transition: transform 0.3s; }
.cta-pulse:hover {
  transform: scale(1.05);
  box-shadow: 0 10px 20px rgba(183, 110, 121, 0.3);
}

/* ==========================================
   LAYOUT & TYPOGRAPHY
   ========================================== */
.page-heading {
  font-size: 32px; text-align: center; font-family: 'Playfair Display', serif; color: var(--bark);
  margin-bottom: 32px; padding-bottom: 20px;
  border-bottom: 2px solid var(--border);
}
.empty-cart { text-align: center; padding: 60px 0 80px; }
.keranjang-layout {
  display: grid; grid-template-columns: 1fr 340px;
  gap: 32px; align-items: start;
}

/* Table Design */
.table-responsive { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
.cart-table { width: 100%; border-collapse: collapse; }
.cart-table th {
  text-align: left; padding: 14px 16px;
  font-size: 14px; font-weight: 700; color: var(--bark);
  background: var(--petal); border-bottom: 2px solid var(--border);
}
.cart-table td {
  padding: 16px; border-bottom: 1px solid var(--border);
  vertical-align: middle; font-size: 14px; color: var(--text);
  transition: background-color 0.3s ease;
}
.cart-row { animation: fadeInRow 0.6s ease-out forwards; opacity: 0; transform: translateY(10px); position: relative; }
.cart-row:hover td { background-color: #faf7f8; }

@keyframes fadeInRow {
  to { opacity: 1; transform: translateY(0); }
}

.td-img { width: 90px; }
.cart-item-img {
  width: 72px; height: 72px; border-radius: 10px;
  background: var(--petal); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; overflow: hidden;
}
.cart-item-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.cart-row:hover .cart-item-img img { transform: scale(1.1); }
.cart-name { font-weight: 600; color: var(--bark); font-size: 15px; }
.td-price { white-space: nowrap; font-weight: 500; }
.td-subtotal { white-space: nowrap; font-weight: 700; color: var(--rose); font-size: 16px; }

/* Desktop Label Hide */
.td-subtotal::before { display: none; }

/* Qty Control UI */
.qty-control {
  display: inline-flex; align-items: center;
  border: 1px solid rgba(183, 110, 121, 0.25);
  border-radius: 30px; background: #fff; overflow: hidden;
}
.qty-control button {
  background: none; border: none;
  width: 30px; height: 32px; font-size: 16px;
  cursor: pointer; color: var(--bark);
  transition: background 0.2s, color 0.2s;
}
.qty-control button:hover { background: var(--petal); color: var(--rose); }
.qty-control input {
  width: 36px; height: 32px; border: none;
  text-align: center; font-weight: 600; font-size: 13px;
  color: var(--bark); background: transparent;
  -moz-appearance: textfield;
}
.qty-control input::-webkit-outer-spin-button,
.qty-control input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

/* Buttons */
.action-delete {
  background: transparent; border: 1px solid #ff4d4f; color: #ff4d4f;
  border-radius: 20px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.action-delete:hover {
  background: #ff4d4f; color: #fff; transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(255, 77, 79, 0.2);
}

/* Ringkasan Box (Sticky) */
.sticky-aside { position: sticky; top: 100px; }
.ringkasan-box {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; padding: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
  box-sizing: border-box;
}
.ringkasan-box h3 { font-size: 18px; font-weight: 700; color: var(--bark); }
.ringkasan-row { display: flex; justify-content: space-between; font-size: 14px; color: var(--muted); margin-bottom: 10px; gap: 10px; }
.ringkasan-item-name { text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }
.ringkasan-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 600; color: var(--bark); align-items: center; }
.total-amount { font-family: 'Playfair Display', serif; font-size: 24px; color: var(--rose); font-weight: 700; }
.btn-full { display: block; width: 100%; text-align: center; box-sizing: border-box; }

.checkout-btn { transition: transform 0.3s, box-shadow 0.3s; }
.checkout-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(183, 110, 121, 0.25); }
.back-btn { transition: all 0.3s; border-color: var(--border); color: var(--muted); }
.back-btn:hover { border-color: var(--rose); color: var(--rose); background: #fff; }

/* Rekomendasi Grid */
.rekomendasi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.rekomendasi-card {
  border: 1px solid var(--border); border-radius: 12px;
  background: #fff; padding: 14px;
  display: flex; gap: 14px; align-items: center;
  transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease;
  cursor: pointer; box-sizing: border-box;
}
.rekomendasi-card:hover { transform: translateY(-5px); box-shadow: 0 10px 24px rgba(0,0,0,0.06); }
.rek-img-wrapper { width: 75px; height: 75px; border-radius: 10px; overflow: hidden; background: var(--petal); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rek-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.rekomendasi-card:hover .rek-img-wrapper img { transform: scale(1.1); }
.rek-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.rek-name { font-size: 14px; font-weight: 600; color: var(--bark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;}
.rek-price { font-size: 13px; color: var(--rose); font-weight: 700; margin-bottom: 2px; }
.rek-rating { margin-bottom: 8px; }
.rek-btn { display: inline-block; align-self: flex-start; font-size: 11px; font-weight: 600; padding: 6px 12px; background: var(--petal); color: var(--rose); border-radius: 20px; text-decoration: none; transition: 0.3s ease; }
.rekomendasi-card:hover .rek-btn { background: var(--rose); color: #fff; }


/* ==========================================================================
   BREAKPOINTS RESPONSIVE TERBARU (ANTI-ANAEH & REFLOW CARD MODERN)
   ========================================================================== */
@media (max-width: 991px) {
  .keranjang-layout { grid-template-columns: 1fr; gap: 24px; }
  .sticky-aside { position: static; width: 100%; }
  .rekomendasi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 680px) {
  /* Ubah table menjadi tumpukan card */
  .table-responsive { background: transparent; box-shadow: none; overflow: hidden; }
  .cart-table, .cart-table thead, .cart-table tbody, .cart-table tr, .cart-table td { display: block; }
  .cart-table thead { display: none; } /* Sembunyikan header tabel bawaan */
  
  .cart-row {
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 4px 14px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 16px;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.01);
  }
  .cart-row:hover td { background-color: transparent; }
  
  /* Reset padding default tabel agar rapi */
  .cart-table td { padding: 0 !important; border: none !important; background: transparent !important; }
  
  /* Mapping Letak Koordinat Elemen di Grid Card */
  .td-img { grid-column: 1; grid-row: 1 / span 4; width: 80px !important; }
  .td-name { grid-column: 2; grid-row: 1; padding-right: 75px !important; } /* Beri ruang aman agar nama tidak menabrak tombol hapus */
  .td-price { grid-column: 2; grid-row: 2; color: var(--muted); font-size: 13px; margin-bottom: 4px; }
  .td-qty { grid-column: 2; grid-row: 3; display: flex; align-items: center; margin-bottom: 4px; }
  
  /* Subtotal bergaya modern dengan teks awalan */
  .td-subtotal { grid-column: 2; grid-row: 4; font-size: 14px; font-weight: 700; color: var(--rose); margin-top: 2px; }
  .td-subtotal::before { display: inline; content: "Subtotal: "; font-weight: 500; color: var(--text); font-size: 13px; margin-right: 4px; }
  
  /* Tombol hapus diletakkan absolut di pojok kanan atas tiap kartu produk */
  .td-aksi { position: absolute; top: 16px; right: 16px; }
  .action-delete { padding: 6px 12px !important; font-size: 12px; }
}

@media (max-width: 480px) {
  .rekomendasi-grid { grid-template-columns: 1fr; gap: 14px; }
  .total-amount { font-size: 20px; }
  .page-heading { font-size: 26px; }
}
</style>
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

<!-- ── BREADCRUMB ── -->
<div class="breadcrumb">
  <a href="index.php">Home</a>
  <span>&rsaquo;</span>
  Keranjang Belanja
</div>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:60px;">
  <h1 class="page-heading">Keranjang Belanja</h1>

  <?php if (empty($cart)): ?>

    <div class="empty-cart">
      <div style="font-size:72px;margin-bottom:16px;">&#127800;</div>
      <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Keranjang Kamu Kosong</h3>
      <p style="color:var(--muted);margin-bottom:24px;">Yuk, temukan buket bunga terbaik untuk kamu!</p>
      <a href="katalog.php" class="btn btn-primary" style="padding:12px 36px;">Mulai Belanja</a>
    </div>

  <?php else: ?>

  <div class="keranjang-layout">
    <!-- ── LEFT: Cart Table ── -->
    <div class="keranjang-main">
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
          <?php foreach ($cart as $item): ?>
          <tr>
            <!-- Produk -->
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

            <!-- Harga -->
            <td class="td-price"><?= formatRupiah($item['price']) ?></td>

            <!-- Jumlah + Update -->
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

            <!-- Subtotal -->
            <td class="td-subtotal"><?= formatRupiah($item['price'] * $item['qty']) ?></td>

            <!-- Hapus -->
            <td class="td-aksi">
              <form method="POST" action="keranjang.php">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ── RIGHT: Ringkasan ── -->
    <aside class="ringkasan-box">
      <h3>Ringkasan Belanja</h3>
      <div class="divider" style="margin:12px 0;"></div>

      <?php foreach ($cart as $item): ?>
      <div class="ringkasan-row">
        <span class="ringkasan-item-name"><?= htmlspecialchars($item['name']) ?> (×<?= $item['qty'] ?>)</span>
        <span><?= formatRupiah($item['price'] * $item['qty']) ?></span>
      </div>
      <?php endforeach; ?>

      <div class="divider" style="margin:12px 0;"></div>
      <div class="ringkasan-total">
        <span>Total:</span>
        <span class="total-amount"><?= formatRupiah($total) ?></span>
      </div>

      <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top:16px;padding:12px;">
        Lanjut ke Checkout
      </a>
      <a href="katalog.php" class="btn btn-outline btn-full" style="margin-top:10px;padding:11px;">
        Lanjut Belanja
      </a>
    </aside>
  </div>

  <?php endif; ?>

  <!-- ── REKOMENDASI PRODUK ── -->
  <!-- ── REKOMENDASI PRODUK (INTERACTIVE MINI CARD) ── -->
<?php if (!empty($recommended)): ?>
<div style="margin-top:48px;">
  <div class="divider"></div>
  <h2 style="font-size:20px;margin-bottom:20px;">Rekomendasi Produk</h2>

  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">

    <?php foreach ($recommended as $p): ?>

      <?php
        $name   = $p['name'] ?? 'Produk';
        $price  = $p['price'] ?? 0;
        $slug   = $p['slug'] ?? '';
        $image  = $p['image'] ?? '';
        $rating = $p['rating'] ?? 0;
      ?>

      <div style="
        border:1px solid var(--border);
        border-radius:12px;
        background:#fff;
        padding:12px;
        display:flex;
        gap:12px;
        align-items:center;
        transition:0.25s ease;
        cursor:pointer;
      "
      onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,0.08)'"
      onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">

        <!-- IMAGE -->
        <a href="detail.php?slug=<?= urlencode($slug) ?>" style="flex-shrink:0;">
          <div style="
            width:70px;
            height:70px;
            border-radius:10px;
            overflow:hidden;
            background:var(--petal);
            display:flex;
            align-items:center;
            justify-content:center;
          ">
            <?php if (!empty($image) && file_exists($image)): ?>
              <img src="<?= htmlspecialchars($image) ?>"
                   style="width:100%;height:100%;object-fit:cover;transition:0.3s ease;">
            <?php else: ?>
              🌸
            <?php endif; ?>
          </div>
        </a>

        <!-- INFO -->
        <div style="flex:1;min-width:0;">

          <div style="
            font-size:13px;
            font-weight:600;
            color:var(--bark);
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
          ">
            <?= htmlspecialchars($name) ?>
          </div>

          <div style="font-size:12px;color:var(--muted);margin-top:2px;">
            <?= formatRupiah($price) ?>
          </div>

          <div style="font-size:11px;color:var(--gold);margin-top:2px;">
            <?= str_repeat('★', floor($rating)) ?>
          </div>

          <!-- BUTTON -->
          <a href="detail.php?slug=<?= urlencode($slug) ?>"
             style="
              display:inline-block;
              margin-top:6px;
              font-size:11px;
              padding:5px 10px;
              background:var(--rose);
              color:#fff;
              border-radius:6px;
              text-decoration:none;
              transition:0.2s ease;
             "
             onmouseover="this.style.opacity='0.8'"
             onmouseout="this.style.opacity='1'">
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

<style>
.page-heading {
  font-size: 28px; text-align: center;
  margin-bottom: 28px; padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}
.empty-cart {
  text-align: center; padding: 60px 0 80px;
}
.keranjang-layout {
  display: grid; grid-template-columns: 1fr 300px;
  gap: 28px; align-items: start;
}
/* Cart Table */
.cart-table { width: 100%; border-collapse: collapse; }
.cart-table th {
  text-align: left; padding: 10px 12px;
  font-size: 14px; font-weight: 600; color: var(--bark);
  background: var(--petal); border: 1px solid var(--border);
}
.cart-table td {
  padding: 14px 12px; border-bottom: 1px solid var(--border);
  vertical-align: middle; font-size: 14px; color: var(--text);
}
.td-img { width: 80px; }
.cart-item-img {
  width: 68px; height: 68px; border-radius: 8px;
  background: var(--petal); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; overflow: hidden;
}
.cart-item-img img { width: 100%; height: 100%; object-fit: cover; }
.cart-name { font-weight: 500; color: var(--bark); }
.td-price { white-space: nowrap; font-weight: 500; }
.td-subtotal { white-space: nowrap; font-weight: 700; color: var(--rose); }
.td-aksi { white-space: nowrap; }

/* Ringkasan */
.ringkasan-box {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; padding: 20px;
}
.ringkasan-box h3 {
  font-size: 17px; font-family: 'Playfair Display', serif;
  color: var(--bark);
}
.ringkasan-row {
  display: flex; justify-content: space-between;
  font-size: 13px; color: var(--muted); margin-bottom: 6px;
}
.ringkasan-item-name { flex: 1; margin-right: 8px; }
.ringkasan-total {
  display: flex; justify-content: space-between;
  font-size: 15px; font-weight: 600; color: var(--bark);
}
.total-amount { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--rose); }

.rekomendasi-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
}

@media (max-width: 900px) {
  .keranjang-layout { grid-template-columns: 1fr; }
  .rekomendasi-grid { grid-template-columns: 1fr; }
  .cart-table th:nth-child(3),
  .cart-table td.td-price { display: none; }
}
</style>

<script>
function changeQty(btn, delta) {

  const form = btn.closest('.qty-form');
  const inp  = form.querySelector('input[name="qty"]');

  let value = parseInt(inp.value) || 1;

  value += delta;

  if (value < 1) {
    value = 1;
  }

  inp.value = value;

  // otomatis submit
  form.submit();
}
</script>

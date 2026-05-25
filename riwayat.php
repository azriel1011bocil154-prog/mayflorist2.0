<?php
// riwayat.php — Riwayat Pesanan + Rating Produk

session_start();
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'riwayat.php';
    header('Location: login.php'); exit;
}

include 'includes/products.php';

$alert = '';

// Handle POST rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'beri_rating') {
    $no_order  = $_POST['no_order'] ?? '';
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $rating    = (int)($_POST['rating'] ?? 0);
    $komentar  = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $alert = ['type'=>'error','msg'=>'Pilih rating 1–5 bintang.'];
    } else {
        // Nanti: INSERT INTO ratings (id_user, id_produk, no_order, rating, komentar, tgl)
        //        UPDATE order_items SET sudah_rating = 1 WHERE no_order = ? AND id_produk = ?
        $alert = ['type'=>'success','msg'=>'Rating berhasil dikirim! Terima kasih atas ulasanmu.'];
    }
}

// ── Dummy riwayat (nanti: SELECT dari DB WHERE id_user = ? AND status IN ('Selesai','Dibatalkan')) ──
$riwayat = [
  [
    'no_order'   => 'FLR-OLD00001',
    'tgl'        => '02 Mei 2026',
    'status'     => 'Selesai',
    'total'      => 185000,
    'jenis_kirim'=> 'Dikirim',
    'items'      => [
      ['id'=>1, 'name'=>'Buket Mawar Merah Premium', 'slug'=>'buket-mawar-merah-premium', 'qty'=>1, 'price'=>185000, 'sudah_rating'=>true, 'rating'=>5],
    ],
  ],
  [
    'no_order'   => 'FLR-OLD00002',
    'tgl'        => '25 Apr 2026',
    'status'     => 'Selesai',
    'total'      => 355000,
    'jenis_kirim'=> 'Dikirim',
    'items'      => [
      ['id'=>2, 'name'=>'Rainbow Tulip Bouquet',     'slug'=>'rainbow-tulip-bouquet',     'qty'=>1, 'price'=>210000, 'sudah_rating'=>false, 'rating'=>0],
      ['id'=>4, 'name'=>'Sunflower Happiness',       'slug'=>'sunflower-happiness',       'qty'=>1, 'price'=>145000, 'sudah_rating'=>false, 'rating'=>0],
    ],
  ],
  [
    'no_order'   => 'FLR-OLD00003',
    'tgl'        => '10 Apr 2026',
    'status'     => 'Dibatalkan',
    'total'      => 295000,
    'jenis_kirim'=> 'Ambil Sendiri',
    'items'      => [
      ['id'=>5, 'name'=>'Pink Hydrangea Box', 'slug'=>'pink-hydrangea-box', 'qty'=>1, 'price'=>295000, 'sudah_rating'=>false, 'rating'=>0],
    ],
  ],
];

$page_title = 'Riwayat Pesanan — Fleuriste';
$active_nav = '';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <h1 style="font-size:24px;">Riwayat Pesanan</h1>
    <a href="pesanan.php" style="font-size:14px;color:var(--rose);">&larr; Pesanan Aktif</a>
  </div>

  <?php if ($alert): ?>
  <div style="border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;
    <?= $alert['type']==='success' ? 'background:#eaf7ee;color:#256d3f;border:1px solid #b3e4c2;' : 'background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;' ?>">
    <?= $alert['type']==='success' ? '&#10003;' : '&#9888;' ?> <?= htmlspecialchars($alert['msg']) ?>
  </div>
  <?php endif; ?>

  <?php if (empty($riwayat)): ?>
  <div style="text-align:center;padding:60px 0;">
    <div style="font-size:64px;margin-bottom:16px;">&#128196;</div>
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Belum Ada Riwayat</h3>
    <p style="color:var(--muted);">Riwayat pesanan yang sudah selesai akan muncul di sini.</p>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:20px;">
    <?php foreach ($riwayat as $p): ?>
    <div class="riwayat-card">

      <!-- Header -->
      <div class="riwayat-header">
        <div>
          <span style="font-family:monospace;font-weight:700;color:var(--bark);"><?= $p['no_order'] ?></span>
          <span style="font-size:12px;color:var(--muted);margin-left:10px;">&#128197; <?= $p['tgl'] ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="<?= $p['status']==='Selesai' ? 'background:#eaf7ee;color:#256d3f;' : 'background:#fdeaea;color:#9b2020;' ?>
                font-size:12px;font-weight:600;padding:4px 12px;border-radius:100px;">
            <?= $p['status']==='Selesai' ? '&#127800;' : '&#10006;' ?> <?= $p['status'] ?>
          </span>
          <span style="font-size:12px;color:var(--muted);">
            <?= $p['jenis_kirim'] ?>
          </span>
        </div>
      </div>

      <!-- Items + Rating -->
      <div style="padding:16px 20px;">
        <?php foreach ($p['items'] as $item): ?>
        <div class="riwayat-item">
          <div class="order-item-img">&#127800;</div>
          <div style="flex:1;">
            <div style="font-weight:500;font-size:14px;color:var(--bark);">
              <?= htmlspecialchars($item['name']) ?>
            </div>
            <div style="font-size:13px;color:var(--muted);">
              <?= $item['qty'] ?> pcs · <?= formatRupiah($item['price']) ?>
            </div>

            <!-- Tampil rating jika sudah diberi -->
            <?php if ($item['sudah_rating']): ?>
            <div style="margin-top:5px;display:flex;align-items:center;gap:4px;">
              <span style="color:var(--gold);font-size:14px;">
                <?= str_repeat('★', $item['rating']) ?><?= str_repeat('☆', 5 - $item['rating']) ?>
              </span>
              <span style="font-size:12px;color:var(--muted);">Ulasanmu</span>
            </div>
            <?php endif; ?>
          </div>

          <!-- Tombol Beri Rating (hanya untuk status Selesai dan belum rating) -->
          <?php if ($p['status'] === 'Selesai' && !$item['sudah_rating']): ?>
          <button class="btn btn-outline btn-sm"
                  onclick="openRating('<?= $p['no_order'] ?>',<?= $item['id'] ?>,'<?= htmlspecialchars(addslashes($item['name'])) ?>')">
            &#11088; Beri Rating
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Footer -->
      <div class="riwayat-footer">
        <div style="font-weight:700;font-size:15px;">
          Total: <span style="color:var(--rose);"><?= formatRupiah($p['total']) ?></span>
        </div>
        <div style="display:flex;gap:8px;">
          <?php if ($p['status'] === 'Selesai'): ?>
          <a href="katalog.php" class="btn btn-outline btn-sm">Beli Lagi</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── MODAL RATING ── -->
<div id="modal-rating" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:14px;width:440px;max-width:95vw;overflow:hidden;box-shadow:0 12px 48px rgba(0,0,0,0.15);">
    <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-family:'Playfair Display',serif;font-size:17px;">Beri Rating Produk</h3>
      <button onclick="closeRating()" style="background:var(--petal);border:none;cursor:pointer;width:28px;height:28px;border-radius:5px;font-size:16px;">&#10005;</button>
    </div>

    <form method="POST" action="riwayat.php">
      <input type="hidden" name="action" value="beri_rating">
      <input type="hidden" name="no_order" id="r-no-order">
      <input type="hidden" name="id_produk" id="r-id-produk">
      <input type="hidden" name="rating" id="r-rating-value" value="0">

      <div style="padding:20px 22px;">
        <p id="r-produk-name" style="font-weight:600;color:var(--bark);margin-bottom:16px;font-size:15px;"></p>

        <!-- Bintang interaktif -->
        <div style="margin-bottom:16px;">
          <label style="font-size:13px;font-weight:500;color:var(--bark);display:block;margin-bottom:8px;">
            Rating <span style="color:var(--rose)">*</span>
          </label>
          <div class="star-picker" id="starPicker">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star-pick" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)"
                  onmouseover="hoverRating(<?= $i ?>)" onmouseout="unhoverRating()">
              &#9734;
            </span>
            <?php endfor; ?>
          </div>
          <div id="r-label" style="font-size:12px;color:var(--muted);margin-top:4px;"></div>
        </div>

        <!-- Komentar -->
        <div>
          <label style="font-size:13px;font-weight:500;color:var(--bark);display:block;margin-bottom:5px;">
            Komentar (Opsional)
          </label>
          <textarea name="komentar"
                    style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:7px;font-family:'DM Sans',sans-serif;font-size:14px;resize:vertical;min-height:80px;outline:none;"
                    placeholder="Ceritakan pengalamanmu dengan produk ini..."
                    onfocus="this.style.borderColor='var(--rose)'"
                    onblur="this.style.borderColor='var(--border)'"></textarea>
        </div>
      </div>

      <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" onclick="closeRating()" class="btn btn-outline">Batal</button>
        <button type="submit" class="btn btn-primary">Kirim Rating</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.riwayat-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
}
.riwayat-header {
  padding: 13px 20px;
  background: var(--petal); border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px;
}
.riwayat-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 0; border-bottom: 1px solid var(--border);
}
.riwayat-item:last-child { border-bottom: none; }
.riwayat-footer {
  padding: 13px 20px; border-top: 1px solid var(--border);
  background: var(--petal);
  display: flex; align-items: center; justify-content: space-between;
}
.order-item-img {
  width: 48px; height: 48px; border-radius: 8px;
  background: var(--petal); display: flex; align-items: center;
  justify-content: center; font-size: 24px; flex-shrink: 0;
  border: 1px solid var(--border);
}

/* Star Picker */
.star-picker { display: flex; gap: 6px; }
.star-pick {
  font-size: 28px; cursor: pointer; color: var(--border);
  transition: color .15s, transform .1s;
}
.star-pick:hover, .star-pick.hovered, .star-pick.selected { color: var(--gold); }
.star-pick:hover { transform: scale(1.2); }
</style>

<script>
let selectedRating = 0;
const labels = ['','Sangat Buruk','Kurang Baik','Cukup','Bagus','Sangat Bagus &#127800;'];

function openRating(no, id, name) {
  document.getElementById('r-no-order').value  = no;
  document.getElementById('r-id-produk').value = id;
  document.getElementById('r-produk-name').textContent = name;
  selectedRating = 0;
  document.getElementById('r-rating-value').value = 0;
  document.getElementById('r-label').innerHTML = '';
  document.querySelectorAll('.star-pick').forEach(s => s.classList.remove('selected','hovered'));
  document.getElementById('modal-rating').style.display = 'flex';
}
function closeRating() {
  document.getElementById('modal-rating').style.display = 'none';
}
function setRating(val) {
  selectedRating = val;
  document.getElementById('r-rating-value').value = val;
  document.getElementById('r-label').innerHTML = labels[val];
  document.querySelectorAll('.star-pick').forEach((s,i) => {
    s.classList.toggle('selected', i < val);
  });
}
function hoverRating(val) {
  document.querySelectorAll('.star-pick').forEach((s,i) => {
    s.classList.toggle('hovered', i < val);
  });
}
function unhoverRating() {
  document.querySelectorAll('.star-pick').forEach(s => s.classList.remove('hovered'));
}
</script>

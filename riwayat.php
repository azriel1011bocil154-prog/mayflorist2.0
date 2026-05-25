<?php
// riwayat.php — Riwayat Pesanan + Rating Produk

session_start();
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'riwayat.php';
    header('Location: login.php'); 
    exit;
}

include 'koneksi.php';
include 'includes/products.php'; 

$id_user = $_SESSION['user']['id_user'];
$alert = '';

// =========================
// HANDLE POST RATING
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'beri_rating') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $id_produk  = (int)($_POST['id_produk'] ?? 0);
    $rating     = (int)($_POST['rating'] ?? 0);
    $komentar   = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $alert = ['type' => 'error', 'msg' => 'Pilih rating 1–5 bintang.'];
    } else {
        // 1. Simpan ke tabel review milikmu
        // Karena tidak ada id_pesanan di tabel review, kita hilangkan dari query ini
        $stmtRating = $conn->prepare("
            INSERT INTO review (id_user, id_produk, rating, komentar, tanggal_review) 
            VALUES (?, ?, ?, ?, CURDATE())
        ");
        $stmtRating->bind_param("iiis", $id_user, $id_produk, $rating, $komentar);
        
        if ($stmtRating->execute()) {
            // 2. Update status sudah_rating di detail_pesanan agar tombol hilang
            $stmtUpdate = $conn->prepare("
                UPDATE detail_pesanan 
                SET sudah_rating = 1 
                WHERE id_pesanan = ? AND id_produk = ?
            ");
            $stmtUpdate->bind_param("ii", $id_pesanan, $id_produk);
            $stmtUpdate->execute();

            $alert = ['type' => 'success', 'msg' => 'Rating berhasil dikirim! Terima kasih atas ulasanmu.'];
        } else {
            $alert = ['type' => 'error', 'msg' => 'Gagal menyimpan ulasan. Silakan coba lagi.'];
        }
    }
}

// =========================
// AMBIL DATA RIWAYAT (DB)
// =========================
$riwayat = [];

$stmtPesanan = $conn->prepare("
    SELECT id_pesanan, tanggal_pesanan, total_harga, status_pesanan, metode_pengiriman 
    FROM pesanan 
    WHERE id_user = ? 
    ORDER BY tanggal_pesanan DESC
");
$stmtPesanan->bind_param("i", $id_user);
$stmtPesanan->execute();
$resPesanan = $stmtPesanan->get_result();

while ($row = $resPesanan->fetch_assoc()) {
    $id_pes = $row['id_pesanan'];
    $tgl = date('d M Y', strtotime($row['tanggal_pesanan']));
    $status_rapi = ucwords(str_replace('_', ' ', $row['status_pesanan']));
    $kirim_rapi  = ucwords(str_replace('_', ' ', $row['metode_pengiriman']));

    $order_data = [
        'id_pesanan'  => $id_pes,
        'no_order'    => '#ORD-' . $id_pes,
        'tgl'         => $tgl,
        'status'      => $status_rapi,
        'total'       => $row['total_harga'],
        'jenis_kirim' => $kirim_rapi,
        'items'       => []
    ];

    // Ambil detail produk & cek rating dari tabel review
    // Di sini sudah disesuaikan: jumlah_produk dan harga_produk
    $stmtItems = $conn->prepare("
        SELECT 
            dp.id_produk, 
            dp.jumlah_produk, 
            dp.harga_produk, 
            pr.nama_produk, 
            dp.sudah_rating,
            (SELECT rating FROM review WHERE id_user = ? AND id_produk = dp.id_produk LIMIT 1) AS nilai_rating
        FROM detail_pesanan dp
        JOIN produk pr ON dp.id_produk = pr.id_produk
        WHERE dp.id_pesanan = ?
    ");
    $stmtItems->bind_param("ii", $id_user, $id_pes);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();

    while ($item = $resItems->fetch_assoc()) {
        $order_data['items'][] = [
            'id'           => $item['id_produk'],
            'name'         => $item['nama_produk'],
            'qty'          => $item['jumlah_produk'], // Menyesuaikan kolom jumlah_produk
            'price'        => $item['harga_produk'],  // Menyesuaikan kolom harga_produk
            'sudah_rating' => (bool)$item['sudah_rating'],
            'rating'       => $item['nilai_rating'] ? (int)$item['nilai_rating'] : 0
        ];
    }
    
    $riwayat[] = $order_data;
}

$page_title = 'Riwayat Pesanan — MayFlorist';
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
    <p style="color:var(--muted);">Riwayat pesanan kamu akan muncul di sini.</p>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:20px;">
    <?php foreach ($riwayat as $p): ?>
    <div class="riwayat-card">

      <div class="riwayat-header">
        <div>
          <span style="font-family:monospace;font-weight:700;color:var(--bark);"><?= $p['no_order'] ?></span>
          <span style="font-size:12px;color:var(--muted);margin-left:10px;">&#128197; <?= $p['tgl'] ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="<?= strtolower($p['status']) === 'selesai' ? 'background:#eaf7ee;color:#256d3f;' : (strtolower($p['status']) === 'dibatalkan' ? 'background:#fdeaea;color:#9b2020;' : 'background:#fff3cd;color:#856404;') ?>
                font-size:12px;font-weight:600;padding:4px 12px;border-radius:100px;">
            <?= strtolower($p['status']) === 'selesai' ? '&#127800;' : (strtolower($p['status']) === 'dibatalkan' ? '&#10006;' : '&#8987;') ?> <?= $p['status'] ?>
          </span>
          <span style="font-size:12px;color:var(--muted);">
            <?= $p['jenis_kirim'] ?>
          </span>
        </div>
      </div>

      <div style="padding:16px 20px;">
        <?php foreach ($p['items'] as $item): ?>
        <div class="riwayat-item">
          <div class="order-item-img">&#127800;</div>
          <div style="flex:1;">
            <div style="font-weight:500;font-size:14px;color:var(--bark);">
              <?= htmlspecialchars($item['name']) ?>
            </div>
            <div style="font-size:13px;color:var(--muted);">
              <?= $item['qty'] ?> pcs · Rp <?= number_format($item['price'], 0, ',', '.') ?>
            </div>

            <?php if ($item['sudah_rating']): ?>
            <div style="margin-top:5px;display:flex;align-items:center;gap:4px;">
              <span style="color:var(--gold);font-size:14px;">
                <?= str_repeat('★', $item['rating']) ?><?= str_repeat('☆', 5 - $item['rating']) ?>
              </span>
              <span style="font-size:12px;color:var(--muted);">Ulasanmu</span>
            </div>
            <?php endif; ?>
          </div>

          <?php if (strtolower($p['status']) === 'selesai' && !$item['sudah_rating']): ?>
          <button class="btn btn-outline btn-sm"
                  onclick="openRating(<?= $p['id_pesanan'] ?>, <?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')">
            &#11088; Beri Rating
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="riwayat-footer">
        <div style="font-weight:700;font-size:15px;">
          Total: <span style="color:var(--rose);">Rp <?= number_format($p['total'], 0, ',', '.') ?></span>
        </div>
        <div style="display:flex;gap:8px;">
          <?php if (strtolower($p['status']) === 'selesai'): ?>
          <a href="katalog.php" class="btn btn-outline btn-sm">Beli Lagi</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div id="modal-rating" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:14px;width:440px;max-width:95vw;overflow:hidden;box-shadow:0 12px 48px rgba(0,0,0,0.15);">
    <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-family:'Playfair Display',serif;font-size:17px;">Beri Rating Produk</h3>
      <button onclick="closeRating()" style="background:var(--petal);border:none;cursor:pointer;width:28px;height:28px;border-radius:5px;font-size:16px;">&#10005;</button>
    </div>

    <form method="POST" action="riwayat.php">
      <input type="hidden" name="action" value="beri_rating">
      <input type="hidden" name="id_pesanan" id="r-id-pesanan">
      <input type="hidden" name="id_produk" id="r-id-produk">
      <input type="hidden" name="rating" id="r-rating-value" value="0">

      <div style="padding:20px 22px;">
        <p id="r-produk-name" style="font-weight:600;color:var(--bark);margin-bottom:16px;font-size:15px;"></p>

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

function openRating(id_pesanan, id_produk, name) {
  document.getElementById('r-id-pesanan').value  = id_pesanan;
  document.getElementById('r-id-produk').value = id_produk;
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
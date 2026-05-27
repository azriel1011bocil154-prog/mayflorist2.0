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
        // Cek Double Guard: Pastikan memang terdaftar di detail_pesanan dan belum di-rating
        $stmtCheck = $conn->prepare("
            SELECT sudah_rating FROM detail_pesanan 
            WHERE id_pesanan = ? AND id_produk = ?
        ");
        $stmtCheck->bind_param("ii", $id_pesanan, $id_produk);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result()->fetch_assoc();

        if ($resCheck && (int)$resCheck['sudah_rating'] === 0) {
            // 1. Simpan ke tabel review
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
        } else {
            $alert = ['type' => 'error', 'msg' => 'Produk ini sudah pernah kamu berikan ulasan.'];
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
    ORDER BY id_pesanan DESC
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

    // Ambil detail produk & nilai rating dari tabel review berdasarkan id_user & id_produk
    $stmtItems = $conn->prepare("
        SELECT 
            dp.id_produk, 
            dp.jumlah_produk, 
            dp.harga_produk, 
            pr.nama_produk, 
            dp.sudah_rating,
            (SELECT rating FROM review WHERE id_user = ? AND id_produk = dp.id_produk ORDER BY id_review DESC LIMIT 1) AS nilai_rating
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
            'qty'          => $item['jumlah_produk'], 
            'price'        => $item['harga_produk'],  
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
    <a href="pesanan.php" style="font-size:14px;color:var(--rose); text-decoration:none;">&larr; Pesanan Aktif</a>
  </div>

  <?php if ($alert): ?>
  <div style="border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px; font-weight:500;
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
          <span class="status-badge" style="<?= strtolower($p['status']) === 'selesai' ? 'background:#eaf7ee;color:#256d3f;' : (strtolower($p['status']) === 'dibatalkan' ? 'background:#fdeaea;color:#9b2020;' : 'background:#fff3cd;color:#856404;') ?>
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

            <?php if ($item['sudah_rating'] && $item['rating'] > 0): ?>
            <div style="margin-top:5px;display:flex;align-items:center;gap:6px;">
              <span style="color:#ffc107;font-size:14px; letter-spacing: 2px;">
                <?= str_repeat('★', $item['rating']) ?><?= str_repeat('☆', 5 - $item['rating']) ?>
              </span>
              <span style="font-size:12px;color:var(--muted);font-weight:500;">Ulasanmu</span>
            </div>
            <?php endif; ?>
          </div>

          <?php if (strtolower($p['status']) === 'selesai' && !$item['sudah_rating']): ?>
          <button class="btn btn-outline btn-sm btn-ulas"
                  onclick="openRating(<?= $p['id_pesanan'] ?>, <?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>')">
            &#11088; Beri Rating
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="riwayat-footer">
        <div style="font-weight:700;font-size:15px; color: var(--bark);">
          Total: <span style="color:var(--rose);">Rp <?= number_format($p['total'], 0, ',', '.') ?></span>
        </div>
        <div style="display:flex;gap:8px;">
          <?php if (strtolower($p['status']) === 'selesai'): ?>
          <a href="katalog.php" class="btn btn-outline btn-sm" style="text-decoration:none;">Beli Lagi</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div id="modal-rating" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:14px;width:440px;max-width:95vw;overflow:hidden;box-shadow:0 12px 48px rgba(0,0,0,0.2); animation: scaleUp 0.2s ease-out;">
    <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-family:'Playfair Display',serif;font-size:17px; margin:0;">Beri Rating Produk</h3>
      <button onclick="closeRating()" style="background:var(--petal);border:none;cursor:pointer;width:28px;height:28px;border-radius:5px;font-size:14px; color:var(--muted); font-weight:bold;">&#10005;</button>
    </div>

    <form method="POST" action="riwayat.php">
      <input type="hidden" name="action" value="beri_rating">
      <input type="hidden" name="id_pesanan" id="r-id-pesanan">
      <input type="hidden" name="id_produk" id="r-id-produk">
      <input type="hidden" name="rating" id="r-rating-value" value="0">

      <div style="padding:20px 22px;">
        <p id="r-produk-name" style="font-weight:600;color:var(--bark);margin-top:0;margin-bottom:16px;font-size:15px;"></p>

        <div style="margin-bottom:18px;">
          <label style="font-size:13px;font-weight:600;color:var(--bark);display:block;margin-bottom:8px;">
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
          <div id="r-label" style="font-size:13px; font-weight:600; color:var(--rose); margin-top:6px; min-height:18px;"></div>
        </div>

        <div>
          <label style="font-size:13px;font-weight:600;color:var(--bark);display:block;margin-bottom:6px;">
            Komentar (Opsional)
          </label>
          <textarea name="komentar" id="r-komentar"
                    style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:14px;resize:none;min-height:90px;outline:none;box-sizing:border-box;"
                    placeholder="Ceritakan pengalamanmu dengan produk ini..."
                    onfocus="this.style.borderColor='var(--rose)'"
                    onblur="this.style.borderColor='var(--border)'"></textarea>
        </div>
      </div>

      <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
        <button type="button" onclick="closeRating()" class="btn btn-outline" style="padding:8px 16px; font-size:13px;">Batal</button>
        <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px; font-weight:600;">Kirim Rating</button>
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
  padding: 12px 0; border-bottom: 1px solid var(--border);
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
.btn-ulas {
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-ulas:hover {
  background: var(--rose) !important;
  color: #fff !important;
  border-color: var(--rose) !important;
}

/* Modal Animation */
@keyframes scaleUp { 
  from { transform: scale(0.95); opacity: 0; } 
  to { transform: scale(1); opacity: 1; } 
}

/* Star Picker Style */
.star-picker { display: flex; gap: 4px; }
.star-pick {
  font-size: 32px; cursor: pointer; color: #ddd;
  transition: color .15s, transform .1s;
  line-height: 1;
  user-select: none;
}
.star-pick.hovered, .star-pick.selected { color: #ffc107; }
.star-pick:hover { transform: scale(1.15); }
</style>

<script>
let selectedRating = 0;
const labels = ['', 'Sangat Buruk 😞', 'Kurang Baik 🙁', 'Cukup Ok 😐', 'Bagus 🙂', 'Sangat Bagus 🥰 &#127800;'];

function openRating(id_pesanan, id_produk, name) {
  document.getElementById('r-id-pesanan').value  = id_pesanan;
  document.getElementById('r-id-produk').value = id_produk;
  document.getElementById('r-produk-name').textContent = name;
  
  // Reset fields
  selectedRating = 0;
  document.getElementById('r-rating-value').value = 0;
  document.getElementById('r-label').innerHTML = '';
  document.getElementById('r-komentar').value = '';
  
  document.querySelectorAll('.star-pick').forEach(s => {
      s.classList.remove('selected','hovered');
      s.innerHTML = '&#9734;'; // Reset ke bintang kosong
  });
  
  const modal = document.getElementById('modal-rating');
  modal.style.display = 'flex';
}

function closeRating() {
  document.getElementById('modal-rating').style.display = 'none';
}

function setRating(val) {
  selectedRating = val;
  document.getElementById('r-rating-value').value = val;
  document.getElementById('r-label').innerHTML = labels[val];
  
  document.querySelectorAll('.star-pick').forEach((s, i) => {
    if (i < val) {
      s.classList.add('selected');
      s.innerHTML = '&#9733;'; // Ubah jadi bintang penuh
    } else {
      s.classList.remove('selected');
      s.innerHTML = '&#9734;'; // Balik ke bintang kosong
    }
  });
}

function hoverRating(val) {
  document.querySelectorAll('.star-pick').forEach((s, i) => {
    if (i < val) {
      s.classList.add('hovered');
      s.innerHTML = '&#9733;'; // Preview bintang penuh sewaktu di-hover
    } else {
      s.classList.remove('hovered');
    }
  });
}

function unhoverRating() {
  document.querySelectorAll('.star-pick').forEach((s, i) => {
    s.classList.remove('hovered');
    // Kembalikan ke state asli sesuai yang diklik sebelumnya
    if (i < selectedRating) {
      s.innerHTML = '&#9733;';
    } else {
      s.innerHTML = '&#9734;';
    }
  });
}

// Menutup modal sewaktu area gelap di luar modal diklik
window.onclick = function(event) {
  const modal = document.getElementById('modal-rating');
  if (event.target === modal) {
    closeRating();
  }
}
</script>
<?php
// bayar.php

session_start();

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

include 'koneksi.php';
include 'includes/products.php';

// =========================
// AMBIL ID PESANAN
// =========================
if (isset($_GET['id'])) {
    $id_pesanan = (int)$_GET['id'];
} elseif (isset($_GET['no'])) {
    $id_pesanan = (int)$_GET['no'];
} else {
    $id_pesanan = 0;
}

if ($id_pesanan <= 0) {
    header('Location: pesanan.php');
    exit;
}

// =========================
// AMBIL DATA PESANAN
// =========================
$stmt = $conn->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? LIMIT 1");
$stmt->bind_param("i", $id_pesanan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: pesanan.php');
    exit;
}
$data = $result->fetch_assoc();

// =========================
// HITUNG TRANSAKSI TERVERIFIKASI SEBELUMNYA (DETERMINE SISA PELUNASAN DP)
// =========================
$id_pesanan_cek = $data['id_pesanan'];
$q_trans_cek = mysqli_query($conn, "SELECT total_pembayaran FROM transaksi WHERE id_pesanan = '$id_pesanan_cek' AND status_pembayaran = 'diterima'");
$total_dibayar_sebelumnya = 0;
while ($t_cek = mysqli_fetch_assoc($q_trans_cek)) {
    $total_dibayar_sebelumnya += $t_cek['total_pembayaran'];
}

// Deteksi apakah user datang dari tombol pelunasan di pesanan.php
$is_pelunasan_step = false;
if (isset($_GET['pelunasan']) && $_GET['pelunasan'] == 1) {
    $jenis_bayar = 'lunas'; // Diset lunas karena ini transaksi penutup tagihan
    $is_pelunasan_step = true;
} else {
    $jenis_bayar = $_GET['jenis'] ?? 'lunas';
}

// =========================
// INTEGRASI DATA TABEL pengaturan_toko
// =========================
$res_toko = $conn->query("SELECT * FROM pengaturan_toko LIMIT 1");
$toko = $res_toko->fetch_assoc();

$aktif_qris     = (int)($toko['pembayaran_qris'] ?? 1);
$aktif_tunai    = (int)($toko['pembayaran_tunai'] ?? 1);
$aktif_transfer = (int)($toko['pembayaran_transfer'] ?? 1);
$alamat_toko    = $toko['alamat_toko'] ?? '';

// Ambil Data Bank 1
$b1_nama = $toko['nama_bank_1'] ?? '';
$b1_norek = $toko['nomor_rekening_1'] ?? '';
$b1_pemilik = $toko['nama_pemilik_rekening_1'] ?? '';

// Ambil Data Bank 2
$b2_nama = $toko['nama_bank_2'] ?? '';
$b2_norek = $toko['nomor_rekening_2'] ?? '';
$b2_pemilik = $toko['nama_pemilik_rekening_2'] ?? '';

// Ambil Data Bank 3
$b3_nama = $toko['nama_bank_3'] ?? '';
$b3_norek = $toko['nomor_rekening_3'] ?? '';
$b3_pemilik = $toko['nama_pemilik_rekening_3'] ?? '';

// =========================
// AMBIL DETAIL ITEM DARI DATABASE
// =========================
$items_pesanan = [];
$stmt_items = $conn->prepare("
    SELECT dp.*, p.nama_produk 
    FROM detail_pesanan dp 
    JOIN produk p ON dp.id_produk = p.id_produk 
    WHERE dp.id_pesanan = ?
");
$stmt_items->bind_param("i", $id_pesanan);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
while ($row = $res_items->fetch_assoc()) {
    $items_pesanan[] = [
        'name'  => $row['nama_produk'],
        'qty'   => $row['jumlah_produk'],
        'price' => $row['harga_produk']
    ];
}

$subtotal_riil = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $items_pesanan));
$order = [
  'no_order'  => '#ORD-' . $data['id_pesanan'],
  'subtotal'  => $subtotal_riil > 0 ? $subtotal_riil : $data['total_harga'],
  'ongkir'    => $data['total_harga'] - $subtotal_riil > 0 ? $data['total_harga'] - $subtotal_riil : 0,
  'total'     => $data['total_harga'],
  'dp_persen' => 50,
  'items'     => $items_pesanan
];

$dp_amount = round($order['total'] * $order['dp_persen'] / 100);

// LOGIKA PENENTU NOMINAL TAGIHAN YANG HARUS DIBAYAR SEKARANG
if ($is_pelunasan_step) {
    $bayar_amount = $order['total'] - $total_dibayar_sebelumnya;
} else {
    $bayar_amount = ($jenis_bayar === 'dp') ? $dp_amount : $order['total'];
}

$errors = [];
$success = false;

// =========================
// HANDLE SUBMIT
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = $_POST['metode_bayar'] ?? '';
    $file   = $_FILES['bukti_transfer'] ?? null;

    if (!$metode) {
        $errors[] = 'Pilih metode pembayaran.';
    }

    $nama_file = '';

    if (in_array($metode, ['transfer', 'qris'])) {
        if (!$file || empty($file['name'])) {
            $errors[] = 'Bukti pembayaran wajib diupload.';
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!in_array($file['type'], $allowed)) { $errors[] = 'Format file tidak didukung.'; }
            if ($file['size'] > 2 * 1024 * 1024) { $errors[] = 'Ukuran file maksimal 2MB.'; }
            
            if (empty($errors)) {
                $upload_dir = 'assets/uploads/bukti/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nama_file = 'bukti_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file);
            }
        }
    }

    if (empty($errors)) {
        $stmt2 = $conn->prepare("
            INSERT INTO transaksi (
                id_pesanan, tanggal_transaksi, jenis_pembayaran, metode_pembayaran, total_pembayaran, status_pembayaran, bukti_pembayaran
            ) VALUES (?, CURDATE(), ?, ?, ?, 'menunggu', ?)
        ");
        $stmt2->bind_param("issds", $id_pesanan, $jenis_bayar, $metode, $bayar_amount, $nama_file);
        $insert = $stmt2->execute();

        if ($insert) {
            $stmt3 = $conn->prepare("UPDATE pesanan SET status_pesanan = 'pending' WHERE id_pesanan = ?");
            $stmt3->bind_param("i", $id_pesanan);
            $stmt3->execute();
            $success = true;
        } else {
            $errors[] = 'Gagal menyimpan transaksi.';
        }
    }
}

$page_title = 'Pembayaran — MayFlorist';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">
  <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
    <a href="pesanan.php" style="color:var(--muted);">Pesanan Saya</a> <span style="margin:0 6px;">&rsaquo;</span> Pembayaran
  </div>

  <?php if ($success): ?>
  <div style="max-width:480px;margin:0 auto;text-align:center; background:var(--white);border:1px solid var(--border); border-radius:16px;padding:40px 32px;">
    <div style="font-size:64px;margin-bottom:16px;">&#10003;</div>
    <h2 style="font-size:22px;margin-bottom:8px;">Bukti Diterima!</h2>
    <p style="color:var(--muted);font-size:14px;line-height:1.7;margin-bottom:24px;">Terima kasih! Konfirmasi dalam 1×24 jam.</p>
    <a href="pesanan.php" class="btn btn-primary" style="width:100%;padding:12px;">Lihat Status Pesanan</a>
  </div>

  <?php else: ?>
  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:26px;">
      <h1 style="font-size:20px;margin-bottom:4px;"><?= $is_pelunasan_step ? 'Pelunasan Sisa Tagihan' : ($jenis_bayar === 'dp' ? 'Pembayaran DP (50%)' : 'Pembayaran Lunas') ?></h1>
      <p style="font-size:13px;color:var(--muted);margin-bottom:22px;">Pesanan <strong><?= htmlspecialchars($order['no_order']) ?></strong></p>

      <?php if (!empty($errors)): ?>
      <div style="background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;">
        <ul style="margin-left:16px;line-height:1.8;"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>

      <form method="POST" action="bayar.php?id=<?= $id_pesanan ?><?= $is_pelunasan_step ? '&pelunasan=1' : '&jenis='.$jenis_bayar ?>" enctype="multipart/form-data">
        <div style="margin-bottom:20px;">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:10px;">Metode Pembayaran</label>
          <div style="display:flex;flex-direction:column;gap:10px;">

            <?php if ($aktif_transfer === 1): ?>
            <label class="metode-card" onclick="selectMetode(this,'transfer')">
              <input type="radio" name="metode_bayar" value="transfer">
              <span style="font-size:20px;">&#127981;</span>
              <div>
                <div style="font-weight:600;">Transfer Bank</div>
                <div style="font-size:12px;color:var(--muted);">Tersedia via BCA, BRI, atau BNI</div>
              </div>
            </label>
            <?php endif; ?>

            <?php if ($aktif_qris === 1): ?>
            <label class="metode-card" onclick="selectMetode(this,'qris')">
              <input type="radio" name="metode_bayar" value="qris">
              <span style="font-size:20px;">&#128248;</span>
              <div><div style="font-weight:600;">QRIS</div><div style="font-size:12px;color:var(--muted);">E-Wallet / Digital Pay</div></div>
            </label>
            <?php endif; ?>

            <?php if ($aktif_tunai === 1): ?>
            <label class="metode-card" onclick="selectMetode(this,'cash')">
              <input type="radio" name="metode_bayar" value="cash">
              <span style="font-size:20px;">&#128181;</span>
              <div><div style="font-weight:600;">Cash di Toko</div><div style="font-size:12px;color:var(--muted);">Ambil langsung ke gerai</div></div>
            </label>
            <?php endif; ?>

          </div>
        </div>

        <div id="detail-pembayaran-box" style="display:block; background:#fbfbfb; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:20px; font-size:13px; text-align:center; color:#777;">
            💡 Silakan pilih salah satu metode pembayaran di atas untuk melihat instruksi pembayaran.
        </div>

        <div id="upload-section" style="display:none; margin-bottom:18px;">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:6px;">Upload Bukti Pembayaran <span style="color:var(--rose)">*</span></label>
          <div class="upload-area" id="uploadArea" onclick="document.getElementById('bukti_file').click()">
            <div id="upload-placeholder">
              <span style="font-size:36px;">&#128247;</span>
              <p style="margin-top:8px;font-size:14px;color:var(--muted);">Klik untuk pilih foto</p>
            </div>
            <img id="preview-img" src="" alt="" style="display:none;max-height:200px;border-radius:6px;margin:0 auto;">
          </div>
          <input type="file" id="bukti_file" name="bukti_transfer" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewFile(this)">
        </div>

        <div style="margin-bottom:20px;">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:5px;">Catatan (Opsional)</label>
          <textarea name="catatan" class="form-control" rows="2" placeholder="Masukkan catatan tambahan..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px;">Konfirmasi Pembayaran &#8594;</button>
      </form>
    </div>

    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:22px;">
      <h3 style="font-size:16px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">Ringkasan Pesanan</h3>
      <div style="max-height:180px; overflow-y:auto; margin-bottom:10px;">
          <?php foreach ($order['items'] as $item): ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:8px;">
            <span><?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></span>
            <span><?= formatRupiah($item['price'] * $item['qty']) ?></span>
          </div>
          <?php endforeach; ?>
      </div>
      <div style="height:1px;background:var(--border);margin:10px 0;"></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:6px;"><span>Subtotal</span><span><?= formatRupiah($order['subtotal']) ?></span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:10px;"><span>Ongkos Kirim</span><span><?= formatRupiah($order['ongkir']) ?></span></div>
      
      <?php if($total_dibayar_sebelumnya > 0): ?>
        <div style="display:flex;justify-content:space-between;font-size:13px;color:#2b6cb0;margin-bottom:10px;"><span>Telah Dibayar (DP)</span><span>- <?= formatRupiah($total_dibayar_sebelumnya) ?></span></div>
      <?php endif; ?>

      <div style="height:1px;background:var(--border);margin-bottom:10px;"></div>
      <div style="display:flex;justify-content:space-between;font-weight:700;font-size:17px;">
        <span>Total Bayar</span><span style="color:var(--rose);"><?= formatRupiah($bayar_amount) ?></span>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.metode-card { display: flex; align-items: center; gap: 14px; border: 1.5px solid var(--border); border-radius: 8px; padding: 12px 16px; cursor: pointer; transition: all .2s; text-align: left; }
.metode-card:hover { border-color: var(--rose); }
.metode-card.selected { border-color: var(--rose); background: var(--rose-light); }
.metode-card input[type="radio"] { display: none; }
.upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; background: var(--petal); transition: background 0.2s; }
.upload-area:hover { background: #f0f0f0; }
.form-control { width: 100%; padding: 10px 13px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; background: white; }
</style>

<script>
// 1. FUNGSI MEMILIH METODE PEMBAYARAN
function selectMetode(card, val) {
  document.querySelectorAll('.metode-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  card.querySelector('input').checked = true;

  const infoBox = document.getElementById('detail-pembayaran-box');
  const uploadSec = document.getElementById('upload-section');

  infoBox.style.display = 'block';
  infoBox.style.textAlign = 'left';

  if (val === 'transfer') {
      let listBankHTML = '';

      if ('<?= $b1_norek ?>' !== '') {
          listBankHTML += `
            <div style="padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff; margin-bottom: 8px;">
                <span style="background: #118eea; color: #fff; padding: 2px 6px; font-size: 11px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; display: inline-block;"><?= htmlspecialchars($b1_nama) ?></span>
                <div style="font-size: 14px; font-weight: bold; color: var(--rose); font-family: monospace; letter-spacing: 0.5px; margin: 2px 0;">
                    <?= htmlspecialchars($b1_norek) ?>
                </div>
                <div style="color: #666; font-size: 12px;">a.n. <?= htmlspecialchars($b1_pemilik) ?></div>
            </div>
          `;
      }

      if ('<?= $b2_norek ?>' !== '') {
          listBankHTML += `
            <div style="padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff; margin-bottom: 8px;">
                <span style="background: #0f72b3; color: #fff; padding: 2px 6px; font-size: 11px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; display: inline-block;"><?= htmlspecialchars($b2_nama) ?></span>
                <div style="font-size: 14px; font-weight: bold; color: var(--rose); font-family: monospace; letter-spacing: 0.5px; margin: 2px 0;">
                    <?= htmlspecialchars($b2_norek) ?>
                </div>
                <div style="color: #666; font-size: 12px;">a.n. <?= htmlspecialchars($b2_pemilik) ?></div>
            </div>
          `;
      }

      if ('<?= $b3_norek ?>' !== '') {
          listBankHTML += `
            <div style="padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff; margin-bottom: 8px;">
                <span style="background: #e05e26; color: #fff; padding: 2px 6px; font-size: 11px; font-weight: bold; border-radius: 4px; margin-bottom: 4px; display: inline-block;"><?= htmlspecialchars($b3_nama) ?></span>
                <div style="font-size: 14px; font-weight: bold; color: var(--rose); font-family: monospace; letter-spacing: 0.5px; margin: 2px 0;">
                    <?= htmlspecialchars($b3_norek) ?>
                </div>
                <div style="color: #666; font-size: 12px;">a.n. <?= htmlspecialchars($b3_pemilik) ?></div>
            </div>
          `;
      }

      infoBox.innerHTML = `
        <div style="font-weight: 600; color: var(--bark); margin-bottom: 10px;">📋 Silakan Transfer ke Salah Satu Rekening Berikut:</div>
        <div style="display: flex; flex-direction: column;">
            ${listBankHTML}
        </div>
        <div style="margin-top: 6px; font-size: 12px; color: var(--muted);">*Kamu bisa pilih salah satu bank di atas yang paling mudah digunakan, kemudian unggah struknya di bawah ini.</div>
      `;
      uploadSec.style.display = 'block';

  } else if (val === 'qris') {
      infoBox.innerHTML = `
        <div style="font-weight: 600; color: var(--bark); margin-bottom: 8px;">📸 Scan Barcode QRIS:</div>
        <div style="text-align: center; padding: 10px 0;">
            <img src="assets/uploads/pengaturan/<?= htmlspecialchars($toko['qris_image'] ?? 'default_qris.png') ?>" 
                 alt="Barcode QRIS Toko" 
                 style="max-width: 180px; border: 1px solid #ddd; border-radius: 8px; padding: 4px; background: #fff;">
        </div>
        <div style="text-align: center; font-size: 12px; color: var(--muted); margin-top: 4px;">Pindai menggunakan aplikasi e-wallet pilihanmu.</div>
      `;
      uploadSec.style.display = 'block';

  } else if (val === 'cash') {
      infoBox.innerHTML = `
        <div style="font-weight: 600; color: var(--bark); margin-bottom: 6px;">📍 Ketentuan Cash di Toko:</div>
        <div style="line-height: 1.6; color: #555;">
            Pembayaran langsung secara tunai saat mengambil pesanan atau saat kurir mengantar barang.<br>
            <span style="color:var(--muted);">Alamat Toko:</span> <strong><?= htmlspecialchars($alamat_toko) ?></strong>
        </div>
      `;
      uploadSec.style.display = 'none';
      resetUploadFile();
  }
}

// 2. FUNGSI UNTUK PRATINJAU (PREVIEW) GAMBAR BUKTI
function previewFile(input) {
  const file = input.files[0];
  const placeholder = document.getElementById('upload-placeholder');
  const previewImg = document.getElementById('preview-img');

  if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
          placeholder.style.display = 'none';
      }
      reader.readAsDataURL(file);
  } else {
      resetUploadFile();
  }
}

// 3. FUNGSI MERESET FORM UPLOAD
function resetUploadFile() {
  document.getElementById('bukti_file').value = '';
  document.getElementById('preview-img').src = '';
  document.getElementById('preview-img').style.display = 'none';
  document.getElementById('upload-placeholder').style.display = 'block';
}
</script>
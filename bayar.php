<?php
// bayar.php — Upload Bukti Pembayaran (DP atau Lunas)

session_start();

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

include 'includes/products.php';
include 'koneksi.php';



// Ambil data order dari parameter (nanti dari DB)
$no_order    = $_GET['no'] ?? '';
$jenis_bayar = $_GET['jenis'] ?? 'lunas'; // 'dp' atau 'lunas'

if (!$no_order) { header('Location: pesanan.php'); exit; }

// ── Dummy order data (nanti: SELECT dari DB WHERE no_order = ?) ──
$order = [
    'no_order'  => $no_order,
    'nama'      => $_SESSION['user']['nama_user'],
    'subtotal'  => 295000,
    'ongkir'    => 15000,
    'total'     => 310000,
    'dp_persen' => 50, // TBD dari mitra — sementara 50%
    'status'    => 'Menunggu Pembayaran',
    'items'     => [
        ['name' => 'Pink Hydrangea Box', 'qty' => 1, 'price' => 295000],
    ],
];

$dp_amount    = round($order['total'] * $order['dp_persen'] / 100);
$sisa_amount  = $order['total'] - $dp_amount;
$bayar_amount = $jenis_bayar === 'dp' ? $dp_amount : $order['total'];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $metode = $_POST['metode_bayar'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    $file = $_FILES['bukti_transfer'] ?? null;

    if (!$metode) {
        $errors[] = 'Pilih metode pembayaran.';
    }

    $nama_file = '';

    // =========================
    // VALIDASI & UPLOAD FILE
    // =========================
    if (in_array($metode, ['transfer', 'qris'])) {

        if (empty($file['name'])) {

            $errors[] = 'Bukti pembayaran wajib diupload.';

        } else {

            $allowed = ['image/jpeg','image/png','image/jpg','image/webp'];
            $max_size = 2 * 1024 * 1024;

            if (!in_array($file['type'], $allowed)) {

                $errors[] = 'Format file harus JPG, PNG, atau WEBP.';

            } elseif ($file['size'] > $max_size) {

                $errors[] = 'Ukuran file maksimal 2MB.';

            } else {

                // folder upload
                $upload_dir = 'uploads/bukti/';

                // buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // nama file unik
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

                $nama_file = 'bukti_' . time() . '_' . rand(1000,9999) . '.' . $ext;

                $target = $upload_dir . $nama_file;

                // upload file
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    $errors[] = 'Gagal upload file.';
                }
            }
        }
    }

    // =========================
    // SIMPAN KE DATABASE
    // =========================
    if (empty($errors)) {

    // ambil id pesanan
    $id_pesanan = (int) $no_order;

    // cek apakah pesanan ada
    $cek = mysqli_query($conn, "
        SELECT id_pesanan
        FROM pesanan
        WHERE id_pesanan = '$id_pesanan'
        LIMIT 1
    ");

    if (mysqli_num_rows($cek) == 0) {

        $errors[] = 'Pesanan tidak ditemukan.';

    } else {

        $insert = mysqli_query($conn, "
            INSERT INTO transaksi (
                id_pesanan,
                tanggal_transaksi,
                jenis_pembayaran,
                metode_pembayaran,
                total_pembayaran,
                status_pembayaran,
                bukti_pembayaran
            ) VALUES (
                '$id_pesanan',
                NOW(),
                '$jenis_bayar',
                '$metode',
                '$bayar_amount',
                'menunggu',
                '$nama_file'
            )
        ");

        if ($insert) {

            // update status pesanan
            mysqli_query($conn, "
                UPDATE pesanan
                SET status_pesanan = 'pending'
                WHERE id_pesanan = '$id_pesanan'
            ");

            $success = true;

        } else {

            $errors[] = 'Gagal menyimpan transaksi.';
        }
    }
}
}

$page_title = 'Pembayaran — Fleuriste';
$active_nav = '';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">

  <!-- Breadcrumb -->
  <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
    <a href="pesanan.php" style="color:var(--muted);">Pesanan Saya</a>
    <span style="margin:0 6px;">&rsaquo;</span>
    Pembayaran
  </div>

  <?php if ($success): ?>
  <!-- ── SUKSES ── -->
  <div style="max-width:480px;margin:0 auto;text-align:center;
              background:var(--white);border:1px solid var(--border);
              border-radius:16px;padding:40px 32px;">
    <div style="font-size:64px;margin-bottom:16px;">&#10003;</div>
    <h2 style="font-size:22px;margin-bottom:8px;">Bukti Diterima!</h2>
    <p style="color:var(--muted);font-size:14px;line-height:1.7;margin-bottom:24px;">
      Terima kasih! Bukti pembayaran kamu sedang diverifikasi oleh admin.<br>
      Biasanya konfirmasi dalam <strong>1×24 jam</strong>.
    </p>
    <div style="background:var(--petal);border-radius:8px;padding:14px 18px;text-align:left;margin-bottom:24px;">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
        <span style="color:var(--muted);">No. Pesanan</span>
        <strong style="font-family:monospace;"><?= htmlspecialchars($no_order) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:13px;">
        <span style="color:var(--muted);">Nominal Dibayar</span>
        <strong style="color:var(--rose);"><?= formatRupiah($bayar_amount) ?></strong>
      </div>
    </div>
    <a href="pesanan.php" class="btn btn-primary" style="width:100%;padding:12px;">
      Lihat Status Pesanan
    </a>
  </div>

  <?php else: ?>
  <!-- ── FORM PEMBAYARAN ── -->
  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

    <!-- Kiri: Form -->
    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:26px;">
      <h1 style="font-size:20px;margin-bottom:4px;">
        <?= $jenis_bayar === 'dp' ? 'Pembayaran DP' : 'Pembayaran Lunas' ?>
      </h1>
      <p style="font-size:13px;color:var(--muted);margin-bottom:22px;">
        Pesanan <strong style="color:var(--bark);"><?= htmlspecialchars($no_order) ?></strong>
      </p>

      <?php if ($jenis_bayar === 'dp'): ?>
      <div style="background:var(--rose-light);border:1px solid #f5c8b8;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:13px;">
        &#9432; Kamu memilih <strong>Bayar DP <?= $order['dp_persen'] ?>%</strong>.
        Sisa <strong><?= formatRupiah($sisa_amount) ?></strong> akan dibayar sebelum pengiriman.
      </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
      <div style="background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;">
        <ul style="margin-left:16px;line-height:1.8;">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <form method="POST" action="bayar.php?no=<?= urlencode($no_order) ?>&jenis=<?= $jenis_bayar ?>"
            enctype="multipart/form-data">

        <!-- Pilih Metode -->
        <div style="margin-bottom:20px;">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:10px;">
            Metode Pembayaran
          </label>
          <div style="display:flex;flex-direction:column;gap:10px;">

            <label class="metode-card <?= ($_POST['metode_bayar'] ?? '') === 'transfer' ? 'selected' : '' ?>"
                   onclick="selectMetode(this,'transfer')">
              <input type="radio" name="metode_bayar" value="transfer"
                     <?= ($_POST['metode_bayar'] ?? '') === 'transfer' ? 'checked' : '' ?>>
              <span style="font-size:20px;">&#127981;</span>
              <div>
                <div style="font-weight:600;">Transfer Bank</div>
                <div style="font-size:12px;color:var(--muted);">BCA · 1234-5678-90 a.n. Fleuriste Store</div>
              </div>
            </label>

            <label class="metode-card <?= ($_POST['metode_bayar'] ?? '') === 'qris' ? 'selected' : '' ?>"
                   onclick="selectMetode(this,'qris')">
              <input type="radio" name="metode_bayar" value="qris"
                     <?= ($_POST['metode_bayar'] ?? '') === 'qris' ? 'checked' : '' ?>>
              <span style="font-size:20px;">&#128248;</span>
              <div>
                <div style="font-weight:600;">QRIS</div>
                <div style="font-size:12px;color:var(--muted);">Bayar via aplikasi dompet digital</div>
              </div>
            </label>

            <label class="metode-card <?= ($_POST['metode_bayar'] ?? '') === 'cash' ? 'selected' : '' ?>"
                   onclick="selectMetode(this,'bayar_ditempat')">
              <input type="radio" name="metode_bayar" value="bayar_ditempat"
                     <?= ($_POST['metode_bayar'] ?? '') === 'bayar_ditempat' ? 'checked' : '' ?>>
              <span style="font-size:20px;">&#128181;</span>
              <div>
                <div style="font-weight:600;">Cash di Toko</div>
                <div style="font-size:12px;color:var(--muted);">Bayar langsung saat ambil / diantar</div>
              </div>
            </label>

          </div>
        </div>

        <!-- Upload Bukti (tampil hanya untuk transfer/QRIS) -->
        <div id="upload-section" style="margin-bottom:18px;<?= !in_array($_POST['metode_bayar']??'',['transfer','qris']) ? 'display:none;' : '' ?>">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:6px;">
            Upload Bukti Transfer / Screenshot QRIS <span style="color:var(--rose)">*</span>
          </label>

          <div class="upload-area" id="uploadArea" onclick="document.getElementById('bukti_file').click()">
            <div id="upload-placeholder">
              <span style="font-size:36px;">&#128247;</span>
              <p style="margin-top:8px;font-size:14px;color:var(--muted);">
                Klik untuk pilih gambar<br>
                <small>JPG, PNG, WEBP · Maks. 2MB</small>
              </p>
            </div>
            <img id="preview-img" src="" alt="" style="display:none;max-height:200px;border-radius:6px;margin:0 auto;">
          </div>
          <input type="file" id="bukti_file" name="bukti_transfer"
                 accept="image/jpeg,image/png,image/webp" style="display:none;"
                 onchange="previewFile(this)">
        </div>

        <!-- Catatan -->
        <div style="margin-bottom:20px;">
          <label style="display:block;font-size:13px;font-weight:500;color:var(--bark);margin-bottom:5px;">
            Catatan (Opsional)
          </label>
          <textarea name="catatan" class="form-control" rows="2"
                    placeholder="Contoh: sudah transfer jam 14.30..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px;">
          Konfirmasi Pembayaran &#8594;
        </button>
      </form>
    </div>

    <!-- Kanan: Ringkasan -->
    <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:22px;">
      <h3 style="font-size:16px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border);">
        Ringkasan Pesanan
      </h3>

      <?php foreach ($order['items'] as $item): ?>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:8px;">
        <span><?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></span>
        <span><?= formatRupiah($item['price'] * $item['qty']) ?></span>
      </div>
      <?php endforeach; ?>

      <div style="height:1px;background:var(--border);margin:10px 0;"></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:6px;">
        <span>Subtotal</span><span><?= formatRupiah($order['subtotal']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:10px;">
        <span>Ongkos Kirim</span><span><?= formatRupiah($order['ongkir']) ?></span>
      </div>
      <div style="height:1px;background:var(--border);margin-bottom:10px;"></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:6px;">
        <span>Total Pesanan</span><span style="font-weight:600;color:var(--bark);"><?= formatRupiah($order['total']) ?></span>
      </div>

      <?php if ($jenis_bayar === 'dp'): ?>
      <div style="background:var(--rose-light);border-radius:8px;padding:12px;margin-top:10px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
          <span style="color:var(--muted);">DP <?= $order['dp_persen'] ?>%</span>
          <strong style="color:var(--rose);"><?= formatRupiah($dp_amount) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;">
          <span style="color:var(--muted);">Sisa Pelunasan</span>
          <span style="color:var(--muted);"><?= formatRupiah($sisa_amount) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <div style="height:1px;background:var(--border);margin:14px 0;"></div>
      <div style="display:flex;justify-content:space-between;font-weight:700;font-size:17px;">
        <span style="color:var(--bark);">
          <?= $jenis_bayar === 'dp' ? 'Bayar Sekarang' : 'Total Bayar' ?>
        </span>
        <span style="color:var(--rose);"><?= formatRupiah($bayar_amount) ?></span>
      </div>
    </div>

  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.metode-card {
  display: flex; align-items: center; gap: 14px;
  border: 1.5px solid var(--border); border-radius: 8px;
  padding: 12px 16px; cursor: pointer;
  transition: border-color .2s, background .2s;
}
.metode-card:hover { border-color: var(--rose); }
.metode-card.selected { border-color: var(--rose); background: var(--rose-light); }
.metode-card input[type="radio"] { display: none; }

.upload-area {
  border: 2px dashed var(--border); border-radius: 10px;
  padding: 28px 20px; text-align: center; cursor: pointer;
  transition: border-color .2s, background .2s;
  background: var(--petal);
}
.upload-area:hover { border-color: var(--rose); background: var(--rose-light); }

.form-control {
  width: 100%; padding: 10px 13px;
  border: 1px solid var(--border); border-radius: 6px;
  font-family: 'DM Sans', sans-serif; font-size: 14px;
  color: var(--text); background: white; outline: none;
  transition: border-color .2s;
}
.form-control:focus { border-color: var(--rose); }
textarea.form-control { resize: vertical; }
</style>

<script>
function selectMetode(card, val) {
  document.querySelectorAll('.metode-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  card.querySelector('input').checked = true;
  const uploadSec = document.getElementById('upload-section');
  uploadSec.style.display = (val === 'transfer' || val === 'qris') ? 'block' : 'none';
}

function previewFile(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file melebihi 2MB!'); input.value = ''; return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('upload-placeholder').style.display = 'none';
    const img = document.getElementById('preview-img');
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
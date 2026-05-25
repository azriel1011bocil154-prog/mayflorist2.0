<?php
// checkout.php — Checkout (wajib login, pilih DP atau Lunas)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Proteksi: wajib login ──
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

include 'includes/products.php'; // formatRupiah() diambil dari sini
include 'koneksi.php'; // Hubungkan ke database

$cart     = $_SESSION['cart'];
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$errors   = [];

// ── DP config (Sementara 50%) ──
$dp_persen  = 50;

// ── Ambil data user secara real-time berdasarkan struktur tabel user kamu ──
$id_user = $_SESSION['user']['id_user'] ?? 0;
$query_user = $conn->prepare("SELECT id_user, nama_user, telepon_user, alamat_user FROM user WHERE id_user = ?");
$query_user->bind_param("i", $id_user);
$query_user->execute();
$result_user = $query_user->get_result();
$user_db = $result_user->fetch_assoc();

// Gabungkan data session dan database ter-update
$user = array_merge($_SESSION['user'], $user_db ? $user_db : []);

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama']        ?? '');
    $telepon     = trim($_POST['telepon']     ?? '');
    $alamat      = trim($_POST['alamat']      ?? '');
    $jenis_kirim = $_POST['jenis_kirim']      ?? 'dikirim';
    $catatan     = trim($_POST['catatan']     ?? '');
    $jenis_bayar = $_POST['jenis_bayar']      ?? 'lunas'; // 'dp' atau 'lunas'

    // Validasi
    if (!$nama)                                     $errors[] = 'Nama lengkap wajib diisi.';
    if (!$telepon)                                  $errors[] = 'Nomor telepon wajib diisi.';
    if ($jenis_kirim === 'dikirim' && !$alamat)     $errors[] = 'Alamat wajib diisi untuk pengiriman.';
    if (empty($cart))                               $errors[] = 'Keranjang belanja kosong.';

    if (empty($errors)) {
        $ongkir   = ($jenis_kirim === 'dikirim') ? 15000 : 0;
        $total    = $subtotal + $ongkir;
        $tanggal  = date('Y-m-d'); // Menggunakan format tipe data 'date' (YYYY-MM-DD)

        // Hitung total produk
        $total_produk = array_sum(array_column($cart, 'qty'));

        // INSERT ke tabel pesanan (status_pesanan default 'belum_bayar' otomatis dari DB)
        $stmt = $conn->prepare("
        INSERT INTO pesanan (
            id_user,
            tanggal_pesanan,
            alamat_pesanan,
            total_produk,
            total_harga,
            metode_pengiriman,
            catatan
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issiiss",
            $id_user,
            $tanggal,
            $alamat,
            $total_produk,
            $total,
            $jenis_kirim,
            $catatan
        );

        $stmt->execute();
        $id_pesanan = $stmt->insert_id;

        // SIMPAN DETAIL PRODUK (Ke tabel detail_pesanan, sudah_rating diisi default 0)
        foreach ($cart as $item) {
            $stmt2 = $conn->prepare("
                INSERT INTO detail_pesanan
                (id_pesanan, id_produk, jumlah_produk, harga_produk, total_harga, sudah_rating)
                VALUES (?, ?, ?, ?, ?, 0)
            ");

            $total_item = $item['price'] * $item['qty'];

            $stmt2->bind_param(
                "iiiii",
                $id_pesanan,
                $item['id'],
                $item['qty'],
                $item['price'],
                $total_item
            );

            $stmt2->execute();
        } 

        // Format nomor order unik
        $no_order = 'FLR-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT);

        // Simpan ke session order sementara
        $_SESSION['pending_order'] = [
            'no_order'    => $no_order,
            'cart'        => $cart,
            'nama'        => $nama,
            'telepon'     => $telepon,
            'alamat'      => $alamat,
            'jenis_kirim' => $jenis_kirim,
            'catatan'     => $catatan,
            'subtotal'    => $subtotal,
            'ongkir'      => $ongkir,
            'total'       => $total,
        ];

        $_SESSION['cart'] = [];

        header("Location: bayar.php?no=$id_pesanan&jenis=$jenis_bayar");
        exit;
    }
}

$page_title = 'Checkout — Fleuriste';
$active_nav = '';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">

  <h1 style="font-size:28px;text-align:center;margin-bottom:20px;
             padding-bottom:20px;border-bottom:1px solid var(--border);">
    Checkout
  </h1>

  <?php if (empty($cart) && empty($_POST)): ?>
  <div style="text-align:center;padding:60px 0;">
    <div style="font-size:56px;margin-bottom:12px;">&#127800;</div>
    <p style="color:var(--muted);margin-bottom:20px;">Keranjangmu masih kosong.</p>
    <a href="katalog.php" class="btn btn-primary" style="padding:12px 32px;">Mulai Belanja</a>
  </div>
  <?php else: ?>

  <?php if (!empty($errors)): ?>
  <div style="background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;
              border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;">
    <strong style="display:block;margin-bottom:4px;">&#9888; Harap perbaiki:</strong>
    <ul style="margin-left:18px;line-height:1.8;">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" action="checkout.php" id="checkoutForm">
    <div class="checkout-layout">

      <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="checkout-box">
          <h2 class="checkout-box-title">Informasi Pengiriman</h2>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label>Nama Lengkap <span style="color:var(--rose)">*</span></label>
              <input type="text" name="nama" class="form-control"
                     value="<?= htmlspecialchars($_POST['nama'] ?? $user['nama_user'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Nomor Telepon <span style="color:var(--rose)">*</span></label>
              <input type="tel" name="telepon" class="form-control" placeholder="08xx-xxxx-xxxx"
                     value="<?= htmlspecialchars($_POST['telepon'] ?? $user['telepon_user'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Metode Pengambilan</label>
            <div style="display:flex;gap:16px;margin-top:6px;">
              <label class="radio-opt">
                <input type="radio" name="jenis_kirim" value="dikirim"
                       <?= ($_POST['jenis_kirim'] ?? 'dikirim') === 'dikirim' ? 'checked' : '' ?>
                       onchange="toggleAlamat('dikirim')">
                <span>&#128665; Dikirim</span>
              </label>
              <label class="radio-opt">
                <input type="radio" name="jenis_kirim" value="ambil_sendiri"
                       <?= ($_POST['jenis_kirim'] ?? '') === 'ambil_sendiri' ? 'checked' : '' ?>
                       onchange="toggleAlamat('ambil_sendiri')">
                <span>&#127981; Ambil Sendiri</span>
              </label>
            </div>
          </div>

          <div class="form-group" id="alamat-group">
            <label>Alamat Lengkap <span style="color:var(--rose)">*</span></label>
            <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. Nama Jalan No. X, Kota..."><?= htmlspecialchars($_POST['alamat'] ?? $user['alamat_user'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label>Catatan (Opsional)</label>
            <input type="text" name="catatan" class="form-control"
                   placeholder="Catatan khusus untuk florist..."
                   value="<?= htmlspecialchars($_POST['catatan'] ?? '') ?>">
          </div>
        </div>

        <div class="checkout-box" id="jenis-bayar-box">
          <h2 class="checkout-box-title">Jenis Pembayaran</h2>
          <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
            Pilih bayar lunas sekarang atau bayar DP terlebih dahulu.
          </p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <label class="jenis-card <?= ($_POST['jenis_bayar'] ?? 'lunas') === 'lunas' ? 'selected' : '' ?>"
                   onclick="selectJenis(this,'lunas')">
              <input type="radio" name="jenis_bayar" value="lunas"
                     <?= ($_POST['jenis_bayar'] ?? 'lunas') === 'lunas' ? 'checked' : '' ?>>
              <div style="font-size:22px;">&#128178;</div>
              <div style="font-weight:600;font-size:14px;margin-top:4px;">Bayar Lunas</div>
              <div id="label-lunas" style="font-size:13px;color:var(--rose);font-weight:700;margin-top:2px;">
                Rp <?= number_format($subtotal + 15000, 0, ',', '.') ?>
              </div>
            </label>
            <label class="jenis-card <?= ($_POST['jenis_bayar'] ?? '') === 'dp' ? 'selected' : '' ?>"
                   onclick="selectJenis(this,'dp')" id="jenis-dp-card">
              <input type="radio" name="jenis_bayar" value="dp"
                     <?= ($_POST['jenis_bayar'] ?? '') === 'dp' ? 'checked' : '' ?>>
              <div style="font-size:22px;">&#128176;</div>
              <div style="font-weight:600;font-size:14px;margin-top:4px;">Bayar DP <?= $dp_persen ?>%</div>
              <div id="label-dp" style="font-size:13px;color:var(--rose);font-weight:700;margin-top:2px;">
                Rp <?= number_format(round(($subtotal + 15000) * $dp_persen / 100), 0, ',', '.') ?>
              </div>
              <div style="font-size:11px;color:var(--muted);margin-top:2px;">Sisa dibayar sebelum kirim</div>
            </label>
          </div>
        </div>

      </div>

      <aside class="checkout-box" style="align-self:start;position:sticky;top:76px;">
        <h2 class="checkout-box-title">Ringkasan Pesanan</h2>

        <?php foreach ($cart as $item): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
          <div style="width:44px;height:44px;border-radius:7px;background:var(--petal);
                      display:flex;align-items:center;justify-content:center;font-size:22px;
                      border:1px solid var(--border);flex-shrink:0;">&#127800;</div>
          <div style="flex:1;font-size:13px;">
            <div style="font-weight:500;color:var(--bark);"><?= htmlspecialchars($item['name']) ?></div>
            <div style="color:var(--muted);">×<?= $item['qty'] ?> · <?= formatRupiah($item['price']) ?></div>
          </div>
          <div style="font-weight:600;font-size:13px;color:var(--bark);">
            <?= formatRupiah($item['price'] * $item['qty']) ?>
          </div>
        </div>
        <?php endforeach; ?>

        <div style="height:1px;background:var(--border);margin:12px 0;"></div>

        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:6px;">
          <span>Subtotal</span><span><?= formatRupiah($subtotal) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:10px;">
          <span>Ongkos Kirim</span><span id="ongkir-txt">Rp 15.000</span>
        </div>
        <div style="height:1px;background:var(--border);margin-bottom:10px;"></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;">
          <span style="color:var(--bark);">Total</span>
          <span style="color:var(--rose);" id="total-txt"><?= formatRupiah($subtotal + 15000) ?></span>
        </div>

        <div style="margin-top:14px;padding:12px;background:var(--petal);border-radius:8px;
                    display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:13px;color:var(--muted);">Bayar Sekarang</span>
          <strong style="color:var(--rose);font-size:16px;" id="bayar-sekarang-txt">
            <?= formatRupiah($subtotal + 15000) ?>
          </strong>
        </div>

        <button type="submit" class="btn btn-primary"
                style="width:100%;padding:13px;font-size:15px;margin-top:14px;">
          Buat Pesanan &#8594;
        </button>
        <p style="font-size:12px;color:var(--muted);text-align:center;margin-top:8px;">
          &#128274; Pesananmu aman &amp; terlindungi
        </p>
      </aside>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.checkout-layout {
  display: grid; grid-template-columns: 1fr 320px;
  gap: 24px; align-items: start;
}
.checkout-box {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; padding: 22px 24px;
}
.checkout-box-title {
  font-size: 16px; margin-bottom: 18px;
  padding-bottom: 12px; border-bottom: 1px solid var(--border);
}
.form-group { margin-bottom: 14px; }
.form-group label {
  display: block; font-size: 13px; font-weight: 500;
  color: var(--bark); margin-bottom: 5px;
}
.form-control {
  width: 100%; padding: 10px 13px;
  border: 1px solid var(--border); border-radius: 6px;
  font-family: 'DM Sans', sans-serif; font-size: 14px;
  color: var(--text); background: white; outline: none;
  transition: border-color .2s;
}
.form-control:focus { border-color: var(--rose); }
textarea.form-control { resize: vertical; min-height: 72px; }

.radio-opt {
  display: flex; align-items: center; gap: 7px;
  cursor: pointer; font-size: 14px; padding: 8px 14px;
  border: 1px solid var(--border); border-radius: 7px;
  transition: all .15s;
}
.radio-opt:has(input:checked) { border-color: var(--rose); background: var(--rose-light); }
.radio-opt input { accent-color: var(--rose); }

.jenis-card {
  display: flex; flex-direction: column; align-items: center;
  text-align: center; padding: 16px 12px;
  border: 2px solid var(--border); border-radius: 10px;
  cursor: pointer; transition: all .15s;
}
.jenis-card:hover { border-color: var(--rose); }
.jenis-card.selected { border-color: var(--rose); background: var(--rose-light); }
.jenis-card input[type="radio"] { display: none; }

@media (max-width: 768px) {
  .checkout-layout { grid-template-columns: 1fr; }
}
</style>

<script>
const SUBTOTAL = <?= $subtotal ?>;
const DP_PERSEN = <?= $dp_persen ?>;
let curOngkir = 15000;
let curJenis  = '<?= htmlspecialchars($_POST['jenis_bayar'] ?? 'lunas') ?>';

function toggleAlamat(val) {
  document.getElementById('alamat-group').style.display = val === 'dikirim' ? 'block' : 'none';
  curOngkir = val === 'dikirim' ? 15000 : 0;
  document.getElementById('ongkir-txt').textContent = curOngkir > 0 ? 'Rp ' + curOngkir.toLocaleString('id-ID') : 'Gratis';
  updateTotals();
}

function selectJenis(card, val) {
  document.querySelectorAll('.jenis-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  card.querySelector('input').checked = true;
  curJenis = val;
  updateTotals();
}

function updateTotals() {
  const total = SUBTOTAL + curOngkir;
  const dp    = Math.round(total * DP_PERSEN / 100);
  document.getElementById('total-txt').textContent = 'Rp ' + total.toLocaleString('id-ID');
  document.getElementById('label-lunas').textContent = 'Rp ' + total.toLocaleString('id-ID');
  document.getElementById('label-dp').textContent    = 'Rp ' + dp.toLocaleString('id-ID');
  const bayar = curJenis === 'dp' ? dp : total;
  document.getElementById('bayar-sekarang-txt').textContent = 'Rp ' + bayar.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', () => {
  const jenis = document.querySelector('input[name="jenis_kirim"]:checked');
  if (jenis) toggleAlamat(jenis.value);
  updateTotals();
});
</script> 
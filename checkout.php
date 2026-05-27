<?php
// checkout.php — Checkout Terintegrasi Database Sistem

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
$koneksi = $conn;

$cart     = $_SESSION['cart'];
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$errors   = [];

// ── DP config (Sementara 50%) ──
$dp_persen  = 50;

// ── 1. AMBIL DATA PENGATURAN SISTEM SECARA REAL-TIME ──
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
$setting       = mysqli_fetch_assoc($query_setting);

// Setup variabel ongkir dinamis dari database
$ongkir_dasar    = (int)($setting['ongkir_dasar'] ?? 0);
$jarak_dasar     = (int)($setting['jarak_dasar_meter'] ?? 0);
$biaya_per_meter = (int)($setting['biaya_per_meter'] ?? 0);
$min_gratis      = (int)($setting['minimal_belanja_gratis_ongkir'] ?? 0);
$max_jarak_free  = (int)($setting['maksimal_jarak_gratis_ongkir'] ?? 0);

// Hitung ongkir awal statis berdasarkan aturan minimal belanja gratis ongkir
$ongkir_statis = $ongkir_dasar;
if ($min_gratis > 0 && $subtotal >= $min_gratis) {
    $ongkir_statis = 0; // Gratis ongkir karena memenuhi minimal belanja
}

// ── Ambil data user secara real-time ──
$id_user = $_SESSION['user']['id_user'] ?? 0;
$query_user = $koneksi->prepare("SELECT id_user, nama_user, telepon_user, alamat_user FROM user WHERE id_user = ?");
$query_user->bind_param("i", $id_user);
$query_user->execute();
$result_user = $query_user->get_result();
$user_db = $result_user->fetch_assoc();

// Gabungkan data session dan database ter-update
$user = array_merge($_SESSION['user'], $user_db ? $user_db : []);

// Menentukan status kirim default untuk hitungan awal interface HTML
$jenis_kirim_default = $_POST['jenis_kirim'] ?? 'dikirim';
$ongkir_awal         = ($jenis_kirim_default === 'dikirim') ? $ongkir_statis : 0;

// ── LOGIKA TUNGGAL PROSES CHECKOUT (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_pesanan'])) {
    
    // Ambil data input dari form HTML secara aman
    $nama        = trim($_POST['nama'] ?? '');
    $telepon     = trim($_POST['telepon'] ?? '');
    $jenis_kirim = $_POST['jenis_kirim'] ?? 'dikirim';
    $alamat      = ($jenis_kirim === 'dikirim') ? trim($_POST['alamat'] ?? '') : 'Ambil di Toko';
    $catatan     = trim($_POST['catatan'] ?? '');
    $jenis_bayar = $_POST['jenis_bayar'] ?? 'lunas';
    
    // Ambil nilai ongkir yang dikirimkan secara tersembunyi oleh JavaScript terhitung
    $ongkir_final = ($jenis_kirim === 'dikirim') ? (int)($_POST['ongkir_hidden'] ?? $ongkir_statis) : 0;

    // Validasi input wajib
    if (empty($nama)) $errors[] = "Nama lengkap wajib diisi.";
    if (empty($telepon)) $errors[] = "Nomor telepon wajib diisi.";
    if ($jenis_kirim === 'dikirim' && empty($_POST['alamat'])) $errors[] = "Alamat pengiriman wajib diisi jika memilih metode Dikirim.";
    if (empty($cart)) $errors[] = "Keranjang belanja Anda kosong.";

    // Jika lolos validasi form, jalankan eksekusi database
    if (empty($errors)) {
        $total        = $subtotal + $ongkir_final;
        $tanggal      = date('Y-m-d H:i:s'); 
        $total_produk = array_sum(array_column($cart, 'qty'));

        // Mulai Database Transaction agar sinkron & aman dari crash
        $koneksi->begin_transaction();

        try {
            // A. VALIDASI CEK STOK TERLEBIH DAHULU SEBELUM MEMPROSES PEMESANAN
            $stmt_cek_stok = $koneksi->prepare("SELECT nama_produk, stok_produk FROM produk WHERE id_produk = ?");
            foreach ($cart as $item) {
                $stmt_cek_stok->bind_param("i", $item['id']);
                $stmt_cek_stok->execute();
                $res_stok = $stmt_cek_stok->get_result()->fetch_assoc();
                
                if ($res_stok['stok_produk'] < $item['qty']) {
                    throw new Exception("Stok untuk produk '" . $res_stok['nama_produk'] . "' tidak mencukupi (Sisa stok: " . $res_stok['stok_produk'] . ").");
                }
            }

            // 1. INSERT ke tabel pesanan utama
            $stmt = $koneksi->prepare("
                INSERT INTO pesanan (
                    id_user, tanggal_pesanan, alamat_pesanan, total_produk, total_harga, metode_pengiriman, catatan, status_pesanan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'belum_bayar')
            ");
            $stmt->bind_param("issiiss", $id_user, $tanggal, $alamat, $total_produk, $total, $jenis_kirim, $catatan);
            $stmt->execute();
            
            // Ambil ID pesanan murni auto_increment dari database
            $id_pesanan = $stmt->insert_id;

            // 2. LOOPING ISI KERANJANG (SIMPAN DETAIL & POTONG STOK)
            $stmt_detail = $koneksi->prepare("
                INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah_produk, harga_produk, total_harga, sudah_rating)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt_potong = $koneksi->prepare("UPDATE produk SET stok_produk = stok_produk - ? WHERE id_produk = ?");

            foreach ($cart as $item) {
                $id_produk  = $item['id'];
                $qty        = $item['qty'];
                $price      = $item['price'];
                $total_item = $price * $qty;

                // A. Simpan data detail_pesanan
                $stmt_detail->bind_param("iiiii", $id_pesanan, $id_produk, $qty, $price, $total_item);
                $stmt_detail->execute();

                // B. Potong stok_produk di database
                $stmt_potong->bind_param("ii", $qty, $id_produk);
                $stmt_potong->execute();
            } 

            // Jika semua kueri sukses tanpa eror, simpan permanen ke database
            $koneksi->commit();

            // Format nomor order unik untuk disimpan di session temporary
            $no_order = 'FLR-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT);

            $_SESSION['pending_order'] = [
                'no_order'    => $no_order,
                'cart'        => $cart,
                'nama'        => $nama,
                'telepon'     => $telepon,
                'alamat'      => $alamat,
                'jenis_kirim' => $jenis_kirim,
                'catatan'     => $catatan,
                'subtotal'    => $subtotal,
                'ongkir'      => $ongkir_final,
                'total'       => $total,
            ];

            // Kosongkan keranjang belanja asli toko setelah sukses checkout
            $_SESSION['cart'] = [];

            // Arahkan pelanggan langsung ke halaman bayar membawa ID pesanan & jenis bayar yang dipilih
            header("Location: bayar.php?no=$id_pesanan&jenis=$jenis_bayar");
            exit;

        } catch (Exception $e) {
            // Batalkan semua kueri jika di tengah jalan ada eror
            $koneksi->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Batas kode pembersihan penanganan POST
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
    <div style="font-size:56px;margin-bottom:12px;">🌸</div>
    <p style="color:var(--muted);margin-bottom:20px;">Keranjangmu masih kosong.</p>
    <a href="katalog.php" class="btn btn-primary" style="padding:12px 32px;">Mulai Belanja</a>
  </div>
  <?php else: ?>

  <?php if (!empty($errors)): ?>
  <div style="background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;
              border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;">
    <strong style="display:block;margin-bottom:4px;">⚠️ Harap perbaiki:</strong>
    <ul style="margin-left:18px;line-height:1.8;">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" action="checkout.php" id="checkoutForm">
    <input type="hidden" name="ongkir_hidden" id="ongkir_hidden" value="<?= $ongkir_awal ?>">

    <div class="checkout-layout">

      <div class="checkout-main-content">

        <div class="checkout-box">
          <h2 class="checkout-box-title">Informasi Pengiriman</h2>

          <div class="responsive-grid-2">
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
            <div class="methods-flex-row">
              <label class="radio-opt">
                <input type="radio" name="jenis_kirim" value="dikirim"
                       <?= $jenis_kirim_default === 'dikirim' ? 'checked' : '' ?>
                       onchange="toggleAlamat('dikirim')">
                <span>🚗 Dikirim</span>
              </label>
              <label class="radio-opt">
                <input type="radio" name="jenis_kirim" value="ambil_sendiri"
                       <?= $jenis_kirim_default === 'ambil_sendiri' ? 'checked' : '' ?>
                       onchange="toggleAlamat('ambil_sendiri')">
                <span>🏪 Ambil Sendiri</span>
              </label>
            </div>
          </div>

          <div class="form-group" id="jarak-group" style="display: <?= $jenis_kirim_default === 'dikirim' ? 'block' : 'none' ?>;">
             <label>Simulasi Jarak Pengantaran</label>
             <select id="simulasi_jarak" class="form-control" onchange="hitungOngkirDinamis()">
                 <option value="1000" selected>Dekat Rumah Toko (1 KM)</option>
                 <option value="3000">Dalam Area Wilayah (3 KM)</option>
                 <option value="5000">Luar Area Terdekat (5 KM)</option>
                 <option value="10000">Pinggiran Kota Luar (10 KM)</option>
                 <option value="15000">Luar Jangkauan Jauh (15 KM)</option>
             </select>
             <small style="color: var(--muted); display:block; margin-top: 4px;">
                *Aturan Ongkir: <?= formatRupiah($ongkir_dasar) ?> di <?= number_format($jarak_dasar/1000, 1) ?> KM awal, berikutnya +<?= formatRupiah($biaya_per_meter) ?>/meter.
                <?php if($min_gratis > 0): ?> <br><b>Subsidi Gratis Ongkir aktif jika belanja min. <?= formatRupiah($min_gratis) ?> (Maksimal <?= number_format($max_jarak_free/1000, 1) ?> KM).</b> <?php endif; ?>
             </small>
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
          
          <div class="responsive-grid-2" style="gap:10px;">
            <label class="jenis-card <?= ($_POST['jenis_bayar'] ?? 'lunas') === 'lunas' ? 'selected' : '' ?>"
                   onclick="selectJenis(this,'lunas')">
              <input type="radio" name="jenis_bayar" value="lunas"
                     <?= ($_POST['jenis_bayar'] ?? 'lunas') === 'lunas' ? 'checked' : '' ?>>
              <div style="font-size:22px;">💵</div>
              <div style="font-weight:600;font-size:14px;margin-top:4px;">Bayar Lunas</div>
              <div id="label-lunas" style="font-size:13px;color:var(--rose);font-weight:700;margin-top:2px;">
                Rp 0
              </div>
            </label>
            <label class="jenis-card <?= ($_POST['jenis_bayar'] ?? '') === 'dp' ? 'selected' : '' ?>"
                   onclick="selectJenis(this,'dp')" id="jenis-dp-card">
              <input type="radio" name="jenis_bayar" value="dp"
                     <?= ($_POST['jenis_bayar'] ?? '') === 'dp' ? 'checked' : '' ?>>
              <div style="font-size:22px;">💰</div>
              <div style="font-weight:600;font-size:14px;margin-top:4px;">Bayar DP <?= $dp_persen ?>%</div>
              <div id="label-dp" style="font-size:13px;color:var(--rose);font-weight:700;margin-top:2px;">
                Rp 0
              </div>
              <div style="font-size:11px;color:var(--muted);margin-top:2px;">Sisa dibayar sebelum kirim</div>
            </label>
          </div>
        </div>

      </div>

      <aside class="checkout-sidebar">
        <h2 class="checkout-box-title">Ringkasan Pesanan</h2>

        <div class="checkout-cart-items">
          <?php foreach ($cart as $item): ?>
          <div class="checkout-item-row">
            <div class="checkout-item-thumb">🌸</div>
            <div style="flex:1;font-size:13px; min-width: 0;">
              <div class="checkout-item-name"><?= htmlspecialchars($item['name']) ?></div>
              <div style="color:var(--muted);">×<?= $item['qty'] ?> · <?= formatRupiah($item['price']) ?></div>
            </div>
            <div style="font-weight:600;font-size:13px;color:var(--bark); flex-shrink: 0;">
              <?= formatRupiah($item['price'] * $item['qty']) ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="height:1px;background:var(--border);margin:12px 0;"></div>

        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:6px;">
          <span>Subtotal</span><span><?= formatRupiah($subtotal) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:10px;">
          <span>Ongkos Kirim</span><span id="ongkir-txt">Rp 0</span>
        </div>
        <div style="height:1px;background:var(--border);margin-bottom:10px;"></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;">
          <span style="color:var(--bark);">Total</span>
          <span style="color:var(--rose);" id="total-txt">Rp 0</span>
        </div>

        <div style="margin-top:14px;padding:12px;background:var(--petal);border-radius:8px;
                    display:flex;justify-content:space-between;align-items:center;">
          <span style="font-size:13px;color:var(--muted);">Bayar Sekarang</span>
          <strong style="color:var(--rose);font-size:16px;" id="bayar-sekarang-txt">
            Rp 0
          </strong>
        </div>

        <button type="submit" name="buat_pesanan" class="btn btn-primary btn-checkout-submit">
          Buat Pesanan →
        </button>
        <p style="font-size:12px;color:var(--muted);text-align:center;margin-top:8px;">
          🔒 Pesananmu aman &amp; terlindungi
        </p>
      </aside>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
/* CSS Styling bawaan & Optimasi Responsivitas Maksimalkan Layar HP */
.checkout-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.checkout-main-content { display: flex; flex-direction: column; gap: 18px; }
.checkout-box { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 22px 24px; }
.checkout-box-title { font-size: 16px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border); font-weight: 600; color: var(--bark); }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--bark); margin-bottom: 5px; }
.form-control { width: 100%; padding: 10px 13px; border: 1px solid var(--border); border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text); background: white; outline: none; transition: border-color .2s; box-sizing: border-box; }
.form-control:focus { border-color: var(--rose); }
textarea.form-control { resize: vertical; min-height: 72px; }

/* Komponen Flex & Grid Pengganti Inline */
.responsive-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.methods-flex-row { display: flex; gap: 16px; margin-top: 6px; flex-wrap: wrap; }

.radio-opt { display: flex; align-items: center; gap: 7px; cursor: pointer; font-size: 14px; padding: 8px 14px; border: 1px solid var(--border); border-radius: 7px; transition: all .15s; background: #fff; }
.radio-opt:has(input:checked) { border-color: var(--rose); background: var(--rose-light); }
.radio-opt input { accent-color: var(--rose); }

.jenis-card { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 16px 12px; border: 2px solid var(--border); border-radius: 10px; cursor: pointer; transition: all .15s; background: #fff; box-sizing: border-box; }
.jenis-card:hover { border-color: var(--rose); }
.jenis-card.selected { border-color: var(--rose); background: var(--rose-light); }
.jenis-card input[type="radio"] { display: none; }

/* Sidebar Ringkasan Pesanan */
.checkout-sidebar { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 22px 24px; position: sticky; top: 76px; box-sizing: border-box; }
.checkout-cart-items { max-height: 240px; overflow-y: auto; padding-right: 4px; }
.checkout-item-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.checkout-item-thumb { width: 44px; height: 44px; border-radius: 7px; background: var(--petal); display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid var(--border); flex-shrink: 0; }
.checkout-item-name { font-weight: 500; color: var(--bark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.btn-checkout-submit { width: 100%; padding: 13px; font-size: 15px; margin-top: 14px; box-sizing: border-box; font-weight: 600; border-radius: 30px; }

/* ==========================================================================
   BREAKPOINT RESPONSIVE (AMANKAN MOBILE & TABLET)
   ========================================================================== */
@media (max-width: 768px) {
  .checkout-layout { 
    grid-template-columns: 1fr; /* Kolom utama pecah jadi atas-bawah */
    gap: 18px;
  }
  .checkout-sidebar { 
    position: static; /* Matikan efek sticky di mobile agar mengalir natural */
    width: 100%; 
  }
}

@media (max-width: 480px) {
  .checkout-box {
    padding: 16px 18px; /* Perkecil padding box agar menghemat ruang layar HP */
  }
  .responsive-grid-2 { 
    grid-template-columns: 1fr; /* Nama/Telepon & Jenis Bayar otomatis menumpuk vertikal */
    gap: 12px; 
  }
  .methods-flex-row .radio-opt {
    flex: 1; /* Pilihan metode "Kirim" & "Ambil" melebar rata kiri-kanan */
    justify-content: center;
  }
}
</style>

<script>
// Sinkronisasi variabel javascript langsung dari database MySQL
const SUBTOTAL = <?= $subtotal ?>;
const DP_PERSEN = <?= $dp_persen ?>;

const ONGKIR_DASAR    = <?= $ongkir_dasar ?>;
const JARAK_DASAR     = <?= $jarak_dasar ?>;
const BIAYA_PER_METER = <?= $biaya_per_meter ?>;
const MIN_GRATS       = <?= $min_gratis ?>;
const MAX_JARAK_FREE  = <?= $max_jarak_free ?>;

let curOngkir = 0;
let curJenis  = '<?= htmlspecialchars($_POST['jenis_bayar'] ?? 'lunas') ?>';
defineStatusKirim = '<?= $jenis_kirim_default ?>';

function toggleAlamat(val) {
  defineStatusKirim = val;
  document.getElementById('alamat-group').style.display = val === 'dikirim' ? 'block' : 'none';
  document.getElementById('jarak-group').style.display = val === 'dikirim' ? 'block' : 'none';
  
  hitungOngkirDinamis();
}

function hitungOngkirDinamis() {
  if (defineStatusKirim === 'ambil_sendiri') {
      curOngkir = 0;
  } else {
      let jarakPilihan = parseInt(document.getElementById('simulasi_jarak').value);
      
      // Logika Evaluasi Apakah masuk kriteria Gratis Ongkir
      if (MIN_GRATS > 0 && SUBTOTAL >= MIN_GRATS && jarakPilihan <= MAX_JARAK_FREE) {
          curOngkir = 0;
      } else {
          // Logika Hitung Sesuai Aturan Jarak Proposional Database
          if (jarakPilihan <= JARAK_DASAR) {
              curOngkir = ONGKIR_DASAR;
          } else {
              let sisaJarak = jarakPilihan - JARAK_DASAR;
              curOngkir = ONGKIR_DASAR + (sisaJarak * BIAYA_PER_METER);
          }
      }
  }
  
  // Sinkronisasikan harga ongkir ke input hidden form untuk diproses PHP POST
  document.getElementById('ongkir_hidden').value = curOngkir;
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
  toggleAlamat(defineStatusKirim);
});
</script>
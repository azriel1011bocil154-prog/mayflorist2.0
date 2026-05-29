<?php
// pesanan.php — Halaman Pesanan Aktif (User/Customer)

session_start();

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'pesanan.php';
    header('Location: login.php');
    exit;
}

include 'koneksi.php';

// Fungsi helper format rupiah jika belum didefinisikan di file include lain
if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

// Ambil ID user dari session (Amankan dengan type casting integer)
$id_user = (int)$_SESSION['user']['id_user'];

// Ambil data pesanan aktif milik user (bukan yang selesai atau dibatalkan)
$query = mysqli_query($conn, "
    SELECT *
    FROM pesanan
    WHERE id_user = '$id_user'
    AND status_pesanan != 'selesai'
    AND status_pesanan != 'dibatalkan'
    ORDER BY id_pesanan DESC
");

$pesanan_aktif = [];
while ($row = mysqli_fetch_assoc($query)) {
    $pesanan_aktif[] = $row;
}

// Konfigurasi visual status pesanan default
$status_config = [
    'belum_bayar' => ['color' => '#8C7570', 'bg' => '#F9F1EE', 'icon' => '&#128179;', 'step' => 1],
    'pending'     => ['color' => '#7d5a00', 'bg' => '#fff8e1', 'icon' => '&#128336;', 'step' => 2],
    'diproses'    => ['color' => '#0d5c8c', 'bg' => '#e8f4fd', 'icon' => '&#9986;',   'step' => 3],
    'dikirim'     => ['color' => '#256d3f', 'bg' => '#eaf7ee', 'icon' => '&#128665;', 'step' => 4], // Akan dimodif dinamis di bawah jika Ambil Sendiri
    'selesai'     => ['color' => '#256d3f', 'bg' => '#eaf7ee', 'icon' => '&#127800;', 'step' => 5],
    'dibatalkan'  => ['color' => '#9b2020', 'bg' => '#fdeaea', 'icon' => '&#10006;',  'step' => 0]
];

$page_title = 'Pesanan Saya — MayFlorist';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px; max-width:1200px; margin:0 auto; padding-left:15px; padding-right:15px;">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <h1 style="font-size:24px; font-family:'Playfair Display', serif;">Pesanan Saya</h1>
    <a href="riwayat.php" style="font-size:14px;color:var(--rose, #e05275); text-decoration:none; font-weight:600;">
      Lihat Riwayat &rarr;
    </a>
  </div>

  <?php if (empty($pesanan_aktif)): ?>
    <div style="text-align:center;padding:64px 0; background:white; border-radius:12px; border:1px solid #eee;">
      <div style="font-size:64px;margin-bottom:16px;">&#127800;</div>
      <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Belum Ada Pesanan Aktif</h3>
      <p style="color:#777;margin-bottom:20px;">Yuk, buat pesanan pertamamu!</p>
      <a href="katalog.php" class="btn-primary-custom" style="padding:12px 32px; background:var(--rose, #e05275); color:white; text-decoration:none; border-radius:6px; display:inline-block; font-weight:600;">
        Belanja Sekarang
      </a>
    </div>
  <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:20px;">
      <?php foreach ($pesanan_aktif as $p):
        // Copy konfigurasi default agar aman diubah-ubah tiap iterasi
        $sc = $status_config[$p['status_pesanan']] ?? $status_config['pending'];

        // ── LOGIKA DETEKSI AMBIL SENDIRI (PICKUP) ──
        $is_pickup = false;
        $alamat_lower = strtolower(trim($p['alamat_pesanan']));
        // Jika alamat berisi kata "ambil", "toko", kosong, atau sekadar "-", anggap ambil sendiri
        if (strpos($alamat_lower, 'ambil') !== false || strpos($alamat_lower, 'toko') !== false || $alamat_lower === '' || $alamat_lower === '-') {
            $is_pickup = true;
        }

        // Ubah Label Status khusus untuk yang diambil sendiri saat proses selesai dirangkai
        $status_label = ucfirst(str_replace('_', ' ', $p['status_pesanan']));
        if ($is_pickup && $p['status_pesanan'] === 'dikirim') {
            $status_label = 'Siap Diambil';
            $sc['icon'] = '🏬'; // Icon toko
        }

        // ── LOGIKA HITUNG PEMBAYARAN & DETEKSI DP ──
        $id_pesanan_cek = (int)$p['id_pesanan']; 
        $q_trans = mysqli_query($conn, "SELECT jenis_pembayaran, total_pembayaran, status_pembayaran FROM transaksi WHERE id_pesanan = '$id_pesanan_cek'");
        
        $is_dp = false;
        $total_dibayar = 0;
        $total_menunggu = 0;
        $ada_transaksi_menunggu = false;

        while ($t = mysqli_fetch_assoc($q_trans)) {
            if ($t['jenis_pembayaran'] === 'dp') {
                $is_dp = true;
            }
            if ($t['status_pembayaran'] === 'diterima') {
                $total_dibayar += $t['total_pembayaran'];
            }
            if ($t['status_pembayaran'] === 'menunggu') {
                $ada_transaksi_menunggu = true;
                $total_menunggu += $t['total_pembayaran'];
            }
        }

        $sisa_tagihan = $p['total_harga'] - $total_dibayar;
        $sisa_belum_ditransfer = $p['total_harga'] - ($total_dibayar + $total_menunggu);
        if ($sisa_belum_ditransfer < 0) $sisa_belum_ditransfer = 0;

        // ── LOGIKA ESTIMASI WAKTU PROSES ──
        $waktu_pesan = strtotime($p['tanggal_pesanan']);
        $estimasi_selesai = $waktu_pesan + 14400; // 4 Jam

        $info_estimasi = "";
        $style_estimasi = "background:#edf2f7; color:#4a5568; border:1px solid #cbd5e1;";

        if ($p['status_pesanan'] === 'belum_bayar') {
            $info_estimasi = "⏳ Waktu pengerjaan akan dijadwalkan setelah Anda melakukan pembayaran.";
            $style_estimasi = "background:#fff5f5; color:#c53030; border:1px solid #fed7d7;";
        } elseif ($p['status_pesanan'] === 'pending') {
            $info_estimasi = "💐 Pembayaran diverifikasi. Estimasi bunga selesai dirangkai pukul " . date('H:i', $estimasi_selesai) . " WIB";
            $style_estimasi = "background:#fffaf0; color:#dd6b20; border:1px solid #feebc8;";
        } elseif ($p['status_pesanan'] === 'diproses') {
            $info_estimasi = "✂️ Sedang dirangkai oleh florist kami. Estimasi siap ambil/kirim pukul " . date('H:i', $estimasi_selesai) . " WIB";
            $style_estimasi = "background:#e6fffa; color:#234e52; border:1px solid #b2f5ea;";
        } elseif ($p['status_pesanan'] === 'dikirim') {
            // Cek jika Ambil Sendiri vs Dikirim Kurir
            if ($is_pickup) {
                $info_estimasi = "🏬 Bunga sudah selesai dirangkai! Silakan datang ke toko untuk mengambil pesanan Anda.";
                $style_estimasi = "background:#eaf7ee; color:#256d3f; border:1px solid #c6f6d5;";
            } else {
                $info_estimasi = "🛵 Paket bunga dibawa kurir. Estimasi tiba di lokasi dalam 30 - 60 menit.";
                $style_estimasi = "background:#eaf7ee; color:#256d3f; border:1px solid #c6f6d5;";
            }
        }
      ?>

      <div class="order-card">
        <div class="order-card-header">
          <div>
            <span style="font-family:monospace;font-weight:700;color:#333; font-size:15px;">
              #ORD-<?= $p['id_pesanan'] ?>
            </span>
            <span style="font-size:12px;color:#777;margin-left:10px;">
              &#128197; <?= date('d M Y', strtotime($p['tanggal_pesanan'])) ?>
            </span>
            
            <?php if ($is_dp): ?>
              <span style="font-size:11px;font-weight:bold;margin-left:8px;padding:3px 8px;background:#fff8e1;color:#b7791f;border-radius:4px; border:1px solid #ffecc0;">
                Tipe: DP (Uang Muka)
              </span>
            <?php endif; ?>
          </div>

          <span class="status-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
            <?= $sc['icon'] ?> <?= $status_label ?>
          </span>
        </div>

        <div class="order-progress">
          <?php
          // Sesuaikan text progress bar jika diambil sendiri
          $langkah_pengiriman = $is_pickup ? 'Siap Diambil' : 'Dikirim';
          $steps = ['Dibuat', 'Bayar', 'Diproses', $langkah_pengiriman, 'Selesai'];
          $cur   = $sc['step'];

          foreach ($steps as $i => $s):
            $snum = $i + 1;
            $done   = $snum < $cur;
            $active = $snum === $cur;
          ?>
          <div class="progress-step <?= $done ? 'done' : ($active ? 'active' : '') ?>">
            <div class="step-dot"><?= $done ? '&#10003;' : $snum ?></div>
            <div class="step-label"><?= $s ?></div>
          </div>
          <?php if ($i < count($steps)-1): ?>
            <div class="step-line <?= $done ? 'done' : '' ?>"></div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <div class="order-items">
          <div class="order-item-row">
            <div class="order-item-img">&#127800;</div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:15px;color:#333; margin-bottom:4px;">
                Total Produk: <?= $p['total_produk'] ?> Barang
              </div>
              <div style="font-size:13px;color:#666;">
                Total Harga Pesanan: <?= formatRupiah($p['total_harga']) ?>
              </div>
              
              <?php if (!empty($p['alamat_pesanan']) && $p['alamat_pesanan'] !== '-'): ?>
                <div style="font-size:12px;color:#777;margin-top:6px; background:#f9f9f9; padding:6px 10px; border-radius:6px; display:inline-block;">
                  <?= $is_pickup ? '🏬 Metode: ' : '📍 Alamat: ' ?><?= htmlspecialchars($p['alamat_pesanan']) ?>
                </div>
              <?php else: ?>
                <div style="font-size:12px;color:#2b6cb0;margin-top:6px; background:#ebf8ff; padding:6px 10px; border-radius:6px; display:inline-block;">
                  🏬 Metode: Ambil Sendiri di Toko
                </div>
              <?php endif; ?>

              <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px; align-items: flex-start;">
                <?php if (!empty($info_estimasi)): ?>
                  <div style="font-size:12px; padding:6px 10px; border-radius:6px; display:inline-block; font-weight: 500; <?= $style_estimasi ?>">
                    <?= $info_estimasi ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($p['catatan']) && $p['catatan'] !== '-'): ?>
                  <div style="font-size:11px; color:#555; background:#f8fafc; padding:4px 8px; border-radius:6px; border-left:3px solid #cbd5e1; display:inline-block; font-style: italic;">
                    💬 Catatan Anda: "<?= htmlspecialchars($p['catatan']) ?>"
                  </div>
                <?php endif; ?>
              </div>

            </div>
            
            <?php if ($is_dp && $total_dibayar > 0): ?>
            <div style="text-align:right; font-size:13px; background:#f4f9f5; padding:8px 12px; border-radius:8px; border:1px solid #e1f0e5;">
              <div style="color:#2b6cb0; margin-bottom:4px;">
                Sudah Masuk: <strong><?= formatRupiah($total_dibayar) ?></strong>
              </div>
              
              <?php if ($sisa_belum_ditransfer > 0): ?>
                <div style="color:#c53030; font-weight:600;">
                  Sisa Harus Dilunasi: <strong><?= formatRupiah($sisa_belum_ditransfer) ?></strong>
                </div>
              <?php elseif ($total_menunggu > 0 && $sisa_tagihan > 0): ?>
                <div style="color:#b7791f; font-weight:600;">
                  Sisa Harus Dilunasi: <strong style="text-decoration: line-through; color: #aaa;"><?= formatRupiah($sisa_tagihan) ?></strong> <br>
                  <span style="font-size:11px; font-weight:normal; color:#b7791f;">⏳ Pelunasan sedang dicek admin</span>
                </div>
              <?php else: ?>
                <div style="color:#2f855a; font-weight:bold;">
                  ✓ LUNAS TOTAL
                </div>
              <?php endif; ?>

            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="order-card-footer">
          <div>
            <div style="font-size:14px; color:#555;">
              Tagihan Saat Ini:
            </div>
            <div style="font-size:18px; font-weight:800; color:var(--rose, #e05275);">
              <?php 
                if ($sisa_belum_ditransfer <= 0) {
                    if ($total_menunggu > 0 && $sisa_tagihan > 0) {
                        echo "Rp 0 <span style='font-size:12px; color:#b7791f; font-weight:bold;'>(Menunggu Verifikasi)</span>";
                    } else {
                        echo "Rp 0 <span style='font-size:12px; color:#2f855a; font-weight:bold;'>(Lunas)</span>";
                    }
                } else {
                    echo formatRupiah($sisa_belum_ditransfer);
                }
              ?>
            </div>
          </div>

          <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; align-items:center;">

            <?php if ($ada_transaksi_menunggu): ?>
              
              <span style="font-size:13px; color:#b7791f; font-weight:600; padding:8px 16px; background:#fff8e1; border:1px solid #ffecc0; border-radius:6px; display:inline-block;">
                ⏳ Menunggu Konfirmasi Bukti oleh Admin
              </span>

            <?php elseif ($p['status_pesanan'] === 'belum_bayar'): ?>

              <a href="bayar.php?id=<?= $p['id_pesanan'] ?>" class="btn-action btn-rose-solid">
                Bayar Sekarang
              </a>

            <?php elseif ($is_dp && $sisa_belum_ditransfer > 0 && ($p['status_pesanan'] === 'diproses' || $p['status_pesanan'] === 'dikirim')): ?>

              <a href="bayar.php?id=<?= $p['id_pesanan'] ?>&pelunasan=1" class="btn-action btn-warning-solid">
                ⚠️ Lunasi Sisa Pembayaran
              </a>

            <?php elseif ($p['status_pesanan'] === 'dikirim'): ?>
              
              <button
                class="btn-action btn-success-solid"
                onclick="openConfirmModal(<?= $p['id_pesanan'] ?>, <?= $is_pickup ? 'true' : 'false' ?>)">
                <?= $is_pickup ? '✓ Bunga Sudah Saya Ambil' : '✓ Pesanan Diterima' ?>
              </button>

            <?php else: ?>
              
              <a href="detail.php?id=<?= $p['id_pesanan'] ?>" class="btn-action btn-outline-gray">
                Lihat Detail
              </a>

            <?php endif; ?>

          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</div>

<div class="confirm-modal-overlay" id="confirmModal">
  <div class="confirm-modal">
    <div class="confirm-icon" id="modalIcon">📦</div>
    <h3 id="modalTitle">Pesanan Sudah Diterima?</h3>
    <p id="modalDesc">Pastikan bunga sudah sampai dengan baik sebelum menyelesaikan pesanan.</p>
    <div class="confirm-actions">
      <button class="btn-cancel" onclick="closeConfirmModal()">Batal</button>
      <a href="#" id="confirmLink" class="btn-confirm">Ya, Sudah Diterima</a>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
/* CSS Sama persis, tidak ada yang diubah */
.order-card {
  background: white;
  border: 1px solid #e3e3e3;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.order-card-header {
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  background: #fafafa;
}
.status-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 5px 14px;
  border-radius: 100px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.order-progress {
  display: flex;
  align-items: center;
  padding: 20px;
  background: #fff;
  border-bottom: 1px solid #f5f5f5;
  overflow-x: auto;
}
.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.step-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e0e0e0;
  color: #777;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
}
.progress-step.done .step-dot {
  background: #38a169;
  color: white;
}
.progress-step.active .step-dot {
  background: #e05275;
  color: white;
  box-shadow: 0 0 0 4px rgba(224, 82, 117, 0.2);
}
.step-label {
  font-size: 11px;
  color: #888;
  font-weight: 500;
}
.progress-step.active .step-label {
  color: #e05275;
  font-weight: 700;
}
.step-line {
  flex: 1;
  height: 3px;
  background: #e0e0e0;
  min-width: 30px;
  margin-bottom: 16px;
}
.step-line.done {
  background: #38a169;
}
.order-items {
  padding: 20px;
}
.order-item-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.order-item-img {
  width: 56px;
  height: 56px;
  border-radius: 8px;
  background: #fff0f3;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  flex-shrink: 0;
  border: 1px solid #ffe3e7;
}
.order-card-footer {
  padding: 16px 20px;
  border-top: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: #fafafa;
}
.btn-action {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  transition: all 0.2s;
}
.btn-rose-solid {
  background: #e05275;
  color: white;
}
.btn-rose-solid:hover {
  background: #c93d5f;
}
.btn-warning-solid {
  background: #dd6b20;
  color: white;
}
.btn-warning-solid:hover {
  background: #c05621;
}
.btn-success-solid {
  background: #38a169;
  color: white;
}
.btn-success-solid:hover {
  background: #2f855a;
}
.btn-outline-gray {
  background: white;
  border: 1px solid #cbd5e0;
  color: #4a5568;
}
.btn-outline-gray:hover {
  background: #f7fafc;
}

.confirm-modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.45);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:9999;
  opacity:0;
  visibility:hidden;
  transition:.25s;
}
.confirm-modal-overlay.show{
  opacity:1;
  visibility:visible;
}
.confirm-modal{
  width:90%;
  max-width:420px;
  background:white;
  border-radius:22px;
  padding:32px 28px;
  text-align:center;
  transform:translateY(30px) scale(.95);
  transition:.25s ease;
  box-shadow: 0 20px 50px rgba(0,0,0,.15);
}
.confirm-modal-overlay.show .confirm-modal{
  transform:translateY(0) scale(1);
}
.confirm-icon{
  width:82px;
  height:82px;
  margin:auto;
  margin-bottom:18px;
  border-radius:50%;
  background:#eaf7ee;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:42px;
}
.confirm-modal h3{
  font-size:24px;
  margin-bottom:10px;
  color:#2d3748;
}
.confirm-modal p{
  font-size:14px;
  line-height:1.6;
  color:#718096;
  margin-bottom:28px;
}
.confirm-actions{
  display:flex;
  gap:12px;
}
.confirm-actions button,
.confirm-actions a{
  flex:1;
  padding:12px;
  border-radius:10px;
  font-weight:600;
  font-size:14px;
  text-decoration:none;
  transition:.2s;
  cursor:pointer;
}
.btn-cancel{
  border:none;
  background:#edf2f7;
  color:#4a5568;
}
.btn-cancel:hover{
  background:#e2e8f0;
}
.btn-confirm{
  background:#38a169;
  color:white;
}
.btn-confirm:hover{
  background:#2f855a;
}
</style>

<script>
// Ditambah parameter isPickup supaya tulisan di dalam modal berubah otomatis
function openConfirmModal(id, isPickup) {
  document.getElementById('confirmModal').classList.add('show');
  document.getElementById('confirmLink').href = 'konfirmasi_terima.php?no=' + id;
  
  // Ubah konten modal berdasarkan tipe pengiriman
  if (isPickup) {
      document.getElementById('modalIcon').innerHTML = '🏬';
      document.getElementById('modalTitle').innerText = 'Bunga Sudah Diambil?';
      document.getElementById('modalDesc').innerText = 'Pastikan Anda sudah mengambil dan mengecek pesanan bunga di toko sebelum menyelesaikannya.';
      document.getElementById('confirmLink').innerText = 'Ya, Sudah Saya Ambil';
  } else {
      document.getElementById('modalIcon').innerHTML = '📦';
      document.getElementById('modalTitle').innerText = 'Pesanan Sudah Diterima?';
      document.getElementById('modalDesc').innerText = 'Pastikan bunga sudah sampai di lokasi dengan baik sebelum menyelesaikan pesanan.';
      document.getElementById('confirmLink').innerText = 'Ya, Sudah Diterima';
  }
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.remove('show');
}

document.getElementById('confirmModal').addEventListener('click', function(e){
    if(e.target === this){
      closeConfirmModal();
    }
});
</script>
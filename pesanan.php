<?php
// pesanan.php — Status Pesanan Aktif

session_start();
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'pesanan.php';
    header('Location: login.php'); exit;
}

include 'includes/products.php';

// ── Status flow lengkap dengan DP ──
// Menunggu Pembayaran → Menunggu Konfirmasi DP → DP Dikonfirmasi
// → Diproses → Menunggu Pelunasan → Menunggu Konfirmasi Lunas → Dikirim → Selesai
// atau: Menunggu Pembayaran → Menunggu Konfirmasi Lunas → Diproses → Dikirim → Selesai

// ── Dummy pesanan aktif (nanti: SELECT dari DB WHERE id_user = ? AND status != 'Selesai/Dibatalkan') ──
$pesanan_aktif = [
  [
    'no_order'   => 'FLR-A1B2C3D4',
    'tgl'        => '10 Mei 2026',
    'items'      => [['name'=>'Pink Hydrangea Box','qty'=>1,'price'=>295000]],
    'total'      => 310000,
    'jenis_bayar'=> 'dp',
    'dp_persen'  => 50,
    'status'     => 'Menunggu Pembayaran',
    'jenis_kirim'=> 'Dikirim',
  ],
  [
    'no_order'   => 'FLR-E5F6G7H8',
    'tgl'        => '09 Mei 2026',
    'items'      => [['name'=>'Buket Mawar Merah','qty'=>1,'price'=>185000],
                     ['name'=>'Sunflower Happiness','qty'=>1,'price'=>145000]],
    'total'      => 345000,
    'jenis_bayar'=> 'lunas',
    'dp_persen'  => 0,
    'status'     => 'Diproses',
    'jenis_kirim'=> 'Dikirim',
  ],
  [
    'no_order'   => 'FLR-I9J0K1L2',
    'tgl'        => '08 Mei 2026',
    'items'      => [['name'=>'Graduation Mega Bouquet','qty'=>1,'price'=>325000]],
    'total'      => 340000,
    'jenis_bayar'=> 'dp',
    'dp_persen'  => 50,
    'status'     => 'Menunggu Pelunasan',
    'jenis_kirim'=> 'Dikirim',
  ],
];

// ── Status config: label, warna, icon, step ──
$status_config = [
  'Menunggu Pembayaran'       => ['color'=>'#8C7570','bg'=>'#F9F1EE','icon'=>'&#128179;','step'=>1],
  'Menunggu Konfirmasi DP'    => ['color'=>'#7d5a00','bg'=>'#fff8e1','icon'=>'&#128336;','step'=>2],
  'DP Dikonfirmasi'           => ['color'=>'#0d5c8c','bg'=>'#e8f4fd','icon'=>'&#10003;', 'step'=>3],
  'Diproses'                  => ['color'=>'#7d5a00','bg'=>'#fff8e1','icon'=>'&#9986;',  'step'=>4],
  'Menunggu Pelunasan'        => ['color'=>'#9B4D46','bg'=>'#F5E6E0','icon'=>'&#128178;','step'=>5],
  'Menunggu Konfirmasi Lunas' => ['color'=>'#7d5a00','bg'=>'#fff8e1','icon'=>'&#128336;','step'=>6],
  'Dikirim'                   => ['color'=>'#0d5c8c','bg'=>'#e8f4fd','icon'=>'&#128665;','step'=>7],
  'Selesai'                   => ['color'=>'#256d3f','bg'=>'#eaf7ee','icon'=>'&#127800;','step'=>8],
  'Dibatalkan'                => ['color'=>'#9b2020','bg'=>'#fdeaea','icon'=>'&#10006;', 'step'=>0],
];

$steps_label = ['Pesanan Dibuat','Pembayaran','Konfirmasi','Diproses','Dikirim','Selesai'];

$page_title = 'Pesanan Saya — MayFlorist';
$active_nav = '';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <h1 style="font-size:24px;">Pesanan Saya</h1>
    <a href="riwayat.php" style="font-size:14px;color:var(--rose);">Lihat Riwayat &rarr;</a>
  </div>

  <?php if (empty($pesanan_aktif)): ?>
  <div style="text-align:center;padding:64px 0;">
    <div style="font-size:64px;margin-bottom:16px;">&#127800;</div>
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Belum Ada Pesanan Aktif</h3>
    <p style="color:var(--muted);margin-bottom:20px;">Yuk, buat pesanan pertamamu!</p>
    <a href="katalog.php" class="btn btn-primary" style="padding:12px 32px;">Belanja Sekarang</a>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:20px;">
    <?php foreach ($pesanan_aktif as $p):
      $sc  = $status_config[$p['status']] ?? $status_config['Diproses'];
      $dp  = round($p['total'] * ($p['dp_persen'] / 100));
      $sisa= $p['total'] - $dp;
    ?>
    <div class="order-card">
      <!-- Header -->
      <div class="order-card-header">
        <div>
          <span style="font-family:monospace;font-weight:700;color:var(--bark);"><?= $p['no_order'] ?></span>
          <span style="font-size:12px;color:var(--muted);margin-left:10px;">&#128197; <?= $p['tgl'] ?></span>
        </div>
        <span class="status-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
          <?= $sc['icon'] ?> <?= $p['status'] ?>
        </span>
      </div>

      <!-- Progress Steps -->
      <div class="order-progress">
        <?php
        $steps = ['Dibuat','Bayar','Konfirmasi','Diproses','Dikirim','Selesai'];
        $cur   = $sc['step'];
        foreach ($steps as $i => $s):
          $snum = $i + 1;
          $done = $snum < $cur;
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

      <!-- Items -->
      <div class="order-items">
        <?php foreach ($p['items'] as $item): ?>
        <div class="order-item-row">
          <div class="order-item-img">&#127800;</div>
          <div style="flex:1;">
            <div style="font-weight:500;font-size:14px;color:var(--bark);"><?= htmlspecialchars($item['name']) ?></div>
            <div style="font-size:13px;color:var(--muted);"><?= $item['qty'] ?> pcs · <?= formatRupiah($item['price']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Footer -->
      <div class="order-card-footer">
        <div>
          <?php if ($p['jenis_bayar'] === 'dp'): ?>
            <div style="font-size:13px;color:var(--muted);">
              DP <?= $p['dp_persen'] ?>% · Sisa pelunasan:
              <strong style="color:var(--rose);"><?= formatRupiah($sisa) ?></strong>
            </div>
          <?php endif; ?>
          <div style="font-size:15px;font-weight:700;color:var(--bark);margin-top:3px;">
            Total: <span style="color:var(--rose);"><?= formatRupiah($p['total']) ?></span>
          </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
          <?php if ($p['status'] === 'Menunggu Pembayaran'): ?>
            <?php if ($p['jenis_bayar'] === 'dp'): ?>
              <a href="bayar.php?no=<?= urlencode($p['no_order']) ?>&jenis=dp"
                 class="btn btn-rose btn-sm">Bayar DP <?= $p['dp_persen'] ?>%</a>
              <a href="bayar.php?no=<?= urlencode($p['no_order']) ?>&jenis=lunas"
                 class="btn btn-outline btn-sm">Bayar Lunas</a>
            <?php else: ?>
              <a href="bayar.php?no=<?= urlencode($p['no_order']) ?>&jenis=lunas"
                 class="btn btn-rose btn-sm">Bayar Sekarang</a>
            <?php endif; ?>
            <button class="btn btn-outline btn-sm" style="color:#c0392b;border-color:#f5c6c6;"
                    onclick="confirmCancel('<?= $p['no_order'] ?>')">Batalkan</button>

          <?php elseif ($p['status'] === 'Menunggu Pelunasan'): ?>
            <a href="bayar.php?no=<?= urlencode($p['no_order']) ?>&jenis=lunas"
               class="btn btn-rose btn-sm">Bayar Pelunasan</a>

          <?php elseif ($p['status'] === 'Dikirim'): ?>
            <button class="btn btn-primary btn-sm"
                    onclick="confirmTerima('<?= $p['no_order'] ?>')">
              &#10003; Pesanan Diterima
            </button>

          <?php else: ?>
            <a href="detail.php?slug=<?= urlencode($p['items'][0]['name'] ?? '') ?>"
               class="btn btn-outline btn-sm">Lihat Produk</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Konfirmasi Cancel -->
<div id="modal-cancel" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:12px;padding:28px;max-width:360px;width:90%;text-align:center;">
    <div style="font-size:40px;margin-bottom:12px;">&#9888;</div>
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Batalkan Pesanan?</h3>
    <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6;">
      Pesanan <strong id="cancel-no"></strong> akan dibatalkan.<br>
      Kebijakan pembatalan mengikuti ketentuan mitra.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="closeModal()" class="btn btn-outline">Kembali</button>
      <button onclick="submitCancel()" class="btn" style="background:#c0392b;color:white;">Ya, Batalkan</button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Terima -->
<div id="modal-terima" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;align-items:center;justify-content:center;">
  <div style="background:white;border-radius:12px;padding:28px;max-width:360px;width:90%;text-align:center;">
    <div style="font-size:40px;margin-bottom:12px;">&#127800;</div>
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">Pesanan Sudah Diterima?</h3>
    <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6;">
      Pastikan semua item sudah kamu terima dalam kondisi baik sebelum konfirmasi.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="closeModal()" class="btn btn-outline">Belum</button>
      <button onclick="submitTerima()" class="btn btn-primary">Ya, Sudah Diterima</button>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.order-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
}
.order-card-header {
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px;
  background: var(--petal);
}
.status-badge {
  font-size: 12px; font-weight: 600;
  padding: 4px 12px; border-radius: 100px;
}

/* Progress Steps */
.order-progress {
  display: flex; align-items: center;
  padding: 18px 20px; gap: 0;
  overflow-x: auto;
}
.progress-step {
  display: flex; flex-direction: column; align-items: center;
  gap: 6px; flex-shrink: 0;
}
.step-dot {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--border); color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
  transition: all .2s;
}
.progress-step.done .step-dot { background: var(--moss); color: white; }
.progress-step.active .step-dot { background: var(--rose); color: white; box-shadow: 0 0 0 3px var(--rose-light); }
.step-label { font-size: 11px; color: var(--muted); white-space: nowrap; }
.progress-step.done .step-label, .progress-step.active .step-label { color: var(--bark); font-weight: 500; }

.step-line {
  flex: 1; height: 2px; background: var(--border);
  min-width: 20px; margin: 0 4px; margin-bottom: 18px;
  transition: background .2s;
}
.step-line.done { background: var(--moss); }

.order-items { padding: 14px 20px; border-top: 1px solid var(--border); }
.order-item-row {
  display: flex; align-items: center; gap: 12px;
  padding: 8px 0; border-bottom: 1px solid var(--border);
}
.order-item-row:last-child { border-bottom: none; }
.order-item-img {
  width: 48px; height: 48px; border-radius: 8px;
  background: var(--petal); display: flex; align-items: center;
  justify-content: center; font-size: 24px; flex-shrink: 0;
}
.order-card-footer {
  padding: 14px 20px;
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px;
  background: var(--petal);
}
</style>

<script>
let cancelNo = '', terimaNo = '';

function confirmCancel(no) {
  cancelNo = no;
  document.getElementById('cancel-no').textContent = no;
  document.getElementById('modal-cancel').style.display = 'flex';
}
function confirmTerima(no) {
  terimaNo = no;
  document.getElementById('modal-terima').style.display = 'flex';
}
function closeModal() {
  document.getElementById('modal-cancel').style.display = 'none';
  document.getElementById('modal-terima').style.display = 'none';
}
function submitCancel() {
  // Nanti: AJAX POST atau form submit ke cancel-pesanan.php
  alert('Pesanan ' + cancelNo + ' dibatalkan. (Sambungkan ke backend)');
  closeModal();
}
function submitTerima() {
  // Nanti: POST ke konfirmasi-terima.php, update status = 'Selesai'
  alert('Pesanan ' + terimaNo + ' dikonfirmasi diterima! (Sambungkan ke backend)');
  closeModal();
}
</script>

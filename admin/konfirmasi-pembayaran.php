

<?php
// admin/konfirmasi-pembayaran.php

$page_title  = 'Konfirmasi Pembayaran — Admin Fleuriste';
$active_menu = 'konfirmasi';
include 'includes/header.php';

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $no_order = $_POST['no_order'] ?? '';
    $id_bayar = $_POST['id_bayar'] ?? '';
    if ($action === 'konfirmasi') {
        // Nanti: UPDATE pembayaran SET status='dikonfirmasi' WHERE id = ?
        //        UPDATE orders SET status = (jika dp → 'DP Dikonfirmasi', jika lunas → next status) WHERE no_order = ?
        $alert = ['type'=>'success', 'msg'=>"Pembayaran $no_order berhasil dikonfirmasi!"];
    } elseif ($action === 'tolak') {
        // Nanti: UPDATE pembayaran SET status='ditolak', UPDATE orders SET status='Menunggu Pembayaran'
        $alert = ['type'=>'error', 'msg'=>"Pembayaran $no_order ditolak."];
    }
}

// ── Dummy data pembayaran masuk (nanti: SELECT dari DB WHERE status = 'pending_konfirmasi') ──
$pembayaran = [
  [
    'id'          => 1,
    'no_order'    => 'FLR-A1B2C3D4',
    'tgl_bayar'   => '10 Mei 2026 14:22',
    'nama_user'   => 'Dewi Anggraini',
    'jenis'       => 'dp',
    'nominal'     => 155000,
    'total_order' => 310000,
    'metode'      => 'Transfer Bank',
    'bukti'       => 'assets/images/bukti-dummy.jpg', // nanti path file upload
    'catatan'     => 'Sudah transfer jam 14.00',
    'status'      => 'pending_konfirmasi',
    'items'       => [['name'=>'Pink Hydrangea Box','qty'=>1]],
  ],
  [
    'id'          => 2,
    'no_order'    => 'FLR-E5F6G7H8',
    'tgl_bayar'   => '09 Mei 2026 10:05',
    'nama_user'   => 'Budi Santoso',
    'jenis'       => 'lunas',
    'nominal'     => 345000,
    'total_order' => 345000,
    'metode'      => 'QRIS',
    'bukti'       => '',
    'catatan'     => '',
    'status'      => 'pending_konfirmasi',
    'items'       => [['name'=>'Buket Mawar Merah','qty'=>1],['name'=>'Sunflower Happiness','qty'=>1]],
  ],
  [
    'id'          => 3,
    'no_order'    => 'FLR-M3N4O5P6',
    'tgl_bayar'   => '08 Mei 2026 09:30',
    'nama_user'   => 'Rina Susanti',
    'jenis'       => 'pelunasan',
    'nominal'     => 170000,
    'total_order' => 340000,
    'metode'      => 'Transfer Bank',
    'bukti'       => '',
    'catatan'     => 'Pelunasan pesanan wisuda',
    'status'      => 'pending_konfirmasi',
    'items'       => [['name'=>'Graduation Mega Bouquet','qty'=>1]],
  ],
];

$jenis_label = ['dp'=>'DP','lunas'=>'Lunas','pelunasan'=>'Pelunasan'];
$jenis_color = ['dp'=>'var(--rose)','lunas'=>'var(--moss)','pelunasan'=>'var(--gold)'];
?>

<div class="page-body">
  <?php if ($alert): ?>
  <div class="alert <?= $alert['type']==='success'?'alert-success':'alert-danger' ?>">
    <?= $alert['type']==='success'?'&#10003;':'&#10006;' ?> <?= htmlspecialchars($alert['msg']) ?>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <h1>Konfirmasi Pembayaran</h1>
    <span style="font-size:13px;color:var(--muted);"><?= count($pembayaran) ?> menunggu konfirmasi</span>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($pembayaran as $b): ?>
    <div class="card">
      <div class="card-header" style="background:var(--petal);">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <strong style="font-family:monospace;color:var(--bark);"><?= $b['no_order'] ?></strong>
          <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:100px;
                       background:white;color:<?= $jenis_color[$b['jenis']] ?>;">
            <?= $jenis_label[$b['jenis']] ?>
          </span>
          <span style="font-size:12px;color:var(--muted);">&#128197; <?= $b['tgl_bayar'] ?></span>
        </div>
        <span style="font-size:12px;background:#fff8e1;color:#7d5a00;padding:3px 10px;border-radius:100px;font-weight:600;">
          &#128336; Menunggu Konfirmasi
        </span>
      </div>

      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 220px;gap:20px;align-items:start;">

        <!-- Info Pembayaran -->
        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
            Info Pembayaran
          </h4>
          <table style="font-size:13px;width:100%;">
            <tr><td style="color:var(--muted);padding-bottom:6px;width:120px;">Nama</td>
                <td style="font-weight:500;"><?= htmlspecialchars($b['nama_user']) ?></td></tr>
            <tr><td style="color:var(--muted);padding-bottom:6px;">Metode</td>
                <td><?= $b['metode'] ?></td></tr>
            <tr><td style="color:var(--muted);padding-bottom:6px;">Nominal</td>
                <td style="font-weight:700;color:var(--rose);font-size:15px;"><?= formatRupiah($b['nominal']) ?></td></tr>
            <tr><td style="color:var(--muted);padding-bottom:6px;">Total Order</td>
                <td><?= formatRupiah($b['total_order']) ?></td></tr>
            <?php if ($b['catatan']): ?>
            <tr><td style="color:var(--muted);vertical-align:top;">Catatan</td>
                <td style="font-style:italic;color:var(--muted);">"<?= htmlspecialchars($b['catatan']) ?>"</td></tr>
            <?php endif; ?>
          </table>

          <div style="margin-top:14px;">
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px;">Item Pesanan:</div>
            <?php foreach ($b['items'] as $item): ?>
            <div style="font-size:13px;color:var(--bark);">&#127800; <?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Bukti Transfer -->
        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
            Bukti Pembayaran
          </h4>
          <?php if ($b['bukti'] && file_exists($b['bukti'])): ?>
            <img src="<?= htmlspecialchars($b['bukti']) ?>"
                 style="width:100%;max-width:200px;border-radius:8px;border:1px solid var(--border);cursor:pointer;"
                 onclick="openBukti('<?= htmlspecialchars($b['bukti']) ?>')"
                 alt="Bukti Transfer">
            <div style="font-size:12px;color:var(--rose);margin-top:4px;cursor:pointer;"
                 onclick="openBukti('<?= htmlspecialchars($b['bukti']) ?>')">
              &#128269; Lihat Penuh
            </div>
          <?php else: ?>
            <div style="width:100%;max-width:200px;height:120px;background:var(--petal);
                        border:2px dashed var(--border);border-radius:8px;
                        display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;">
              <span style="font-size:28px;opacity:.4;">&#128247;</span>
              <span style="font-size:12px;color:var(--muted);">
                <?= $b['metode'] === 'Cash di Toko' ? 'Cash — Tidak ada bukti' : 'Belum diupload' ?>
              </span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Aksi -->
        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
            Tindakan
          </h4>

          <form method="POST" action="konfirmasi-pembayaran.php" style="display:flex;flex-direction:column;gap:8px;">
            <input type="hidden" name="no_order" value="<?= $b['no_order'] ?>">
            <input type="hidden" name="id_bayar" value="<?= $b['id'] ?>">

            <div style="margin-bottom:6px;">
              <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px;">Catatan Admin (Opsional)</label>
              <textarea name="catatan_admin" style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:5px;font-family:'DM Sans',sans-serif;font-size:13px;resize:none;height:60px;outline:none;"
                        placeholder="Catatan untuk user..."></textarea>
            </div>

            <button type="submit" name="action" value="konfirmasi"
                    class="btn btn-success btn-full" style="background:#256d3f;color:white;">
              &#10003; Konfirmasi Pembayaran
            </button>
            <button type="submit" name="action" value="tolak"
                    class="btn btn-danger btn-full"
                    onclick="return confirm('Yakin ingin menolak pembayaran ini?')">
              &#10006; Tolak Pembayaran
            </button>
          </form>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($pembayaran)): ?>
  <div style="text-align:center;padding:60px 0;">
    <div style="font-size:56px;margin-bottom:14px;">&#10003;</div>
    <p style="color:var(--muted);">Tidak ada pembayaran yang menunggu konfirmasi.</p>
  </div>
  <?php endif; ?>
</div>

<!-- Modal preview bukti -->
<div id="modal-bukti" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:300;align-items:center;justify-content:center;"
     onclick="this.style.display='none'">
  <img id="bukti-full" src="" style="max-width:90vw;max-height:90vh;border-radius:8px;">
</div>

<?php include 'includes/footer.php'; ?>

<script>
function openBukti(src) {
  document.getElementById('bukti-full').src = src;
  document.getElementById('modal-bukti').style.display = 'flex';
}
</script>

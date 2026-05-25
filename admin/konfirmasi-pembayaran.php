

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
include '../koneksi.php';

$pembayaran = [];

$query = mysqli_query($conn, "
    SELECT 
        p.id_pesanan,
        p.id_user,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.metode_pengiriman,
        p.catatan,
        u.nama_user
    FROM pesanan p
    LEFT JOIN user u ON p.id_user = u.id_user
    ORDER BY p.id_pesanan DESC
");

while ($row = mysqli_fetch_assoc($query)) {

    // ambil detail item
    $items = [];

    $detail = mysqli_query($conn, "
        SELECT dp.jumlah_produk, pr.nama_produk
        FROM detail_pesanan dp
        LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
        WHERE dp.id_pesanan = '{$row['id_pesanan']}'
    ");

    while ($d = mysqli_fetch_assoc($detail)) {
        $items[] = [
            'name' => $d['nama_produk'],
            'qty'  => $d['jumlah_produk']
        ];
    }

    $pembayaran[] = [
        'id'          => $row['id_pesanan'],
        'no_order'    => 'FLR-' . str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT),
        'tgl_bayar'   => date('d M Y H:i', strtotime($row['tanggal_pesanan'])),
        'nama_user'   => $row['nama_user'],
        'jenis'       => 'lunas',
        'nominal'     => $row['total_harga'],
        'total_order' => $row['total_harga'],
        'metode'      => $row['metode_pengiriman'],
        'bukti'       => '',
        'catatan'     => $row['catatan'],
        'status'      => $row['status_pesanan'],
        'items'       => $items
    ];
}

$jenis_label = [
    'dp' => 'DP',
    'lunas' => 'Lunas',
    'pelunasan' => 'Pelunasan'
];

$jenis_color = [
    'dp' => 'var(--rose)',
    'lunas' => 'var(--moss)',
    'pelunasan' => 'var(--gold)'
];

if (!$insert) {
    die("ERROR INSERT: " . $conn->error);
} 
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
          &#128336; <?= htmlspecialchars($b['status']) ?>
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

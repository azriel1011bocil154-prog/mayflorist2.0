
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

// ── Ambil data transaksi yang menunggu konfirmasi dari DB ──
$query_bayar = "
    SELECT 
        t.id_transaksi                                    AS id,
        CONCAT('#ORD-', LPAD(p.id_pesanan, 3, '0'))      AS no_order,
        DATE_FORMAT(t.tanggal_transaksi, '%d %M %Y')     AS tgl_bayar,
        u.nama_user,
        t.jenis_pembayaran                                AS jenis,
        t.total_pembayaran                                AS nominal,
        p.total_harga                                     AS total_order,
        t.metode_pembayaran                               AS metode,
        t.bukti_pembayaran                                AS bukti,
        p.catatan,
        t.status_pembayaran                               AS status,
        p.id_pesanan
    FROM transaksi t
    JOIN pesanan p ON t.id_pesanan = p.id_pesanan
    JOIN user u    ON p.id_user    = u.id_user
    WHERE t.status_pembayaran = 'menunggu'
    ORDER BY t.tanggal_transaksi ASC
";

$res_bayar  = mysqli_query($conn, $query_bayar);
if (!$res_bayar) {
    die('Query error: ' . mysqli_error($conn));
}

$pembayaran = [];
while ($row = mysqli_fetch_assoc($res_bayar)) {
    // Ambil item pesanan untuk tiap transaksi
    $id_pesanan = $row['id_pesanan'];
    $q_items = mysqli_prepare($conn,
        "SELECT pr.nama_produk AS name, dp.jumlah_produk AS qty
         FROM detail_pesanan dp
         JOIN produk pr ON dp.id_produk = pr.id_produk
         WHERE dp.id_pesanan = ?"
    );
    mysqli_stmt_bind_param($q_items, 'i', $id_pesanan);
    mysqli_stmt_execute($q_items);
    $res_items = mysqli_stmt_get_result($q_items);
    $items = [];
    while ($item = mysqli_fetch_assoc($res_items)) {
        $items[] = $item;
    }
    mysqli_stmt_close($q_items);

    $row['items'] = $items;
    $pembayaran[] = $row;
}

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

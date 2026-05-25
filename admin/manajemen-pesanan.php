<?php
// admin/manajemen-pesanan.php — Manajemen Pesanan

$page_title  = 'Manajemen Pesanan — Admin Fleuriste';
$active_menu = 'pesanan';
include 'includes/header.php';

// ── Handle status update ──
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status']   ?? '';
    $no         = $_POST['no']       ?? '';
    $catatan    = $_POST['catatan']  ?? '';

    // Nanti: UPDATE orders SET status = ?, catatan_admin = ? WHERE no_order = ?
    // Kalau status = 'Menunggu Pelunasan' → kirim notifikasi ke user (WA/email)
    $alert = '<div class="alert alert-success">&#10003; Status pesanan <strong>' . htmlspecialchars($no) . '</strong> diperbarui ke <strong>' . htmlspecialchars($new_status) . '</strong>!</div>';
}

// ── Dummy orders ──
$all_orders = [
  ['no'=>'#ORD-001','tgl_pesan'=>'10/05/2026','tgl_bayar'=>'10/05/2026','nama'=>'Siti Nurjanah', 'total'=>'Rp 185.000','status'=>'Selesai',                  'jenis_bayar'=>'lunas'],
  ['no'=>'#ORD-002','tgl_pesan'=>'10/05/2026','tgl_bayar'=>'10/05/2026','nama'=>'Budi Santoso',  'total'=>'Rp 210.000','status'=>'Dikirim',                  'jenis_bayar'=>'lunas'],
  ['no'=>'#ORD-003','tgl_pesan'=>'10/05/2026','tgl_bayar'=>'10/05/2026','nama'=>'Dewi Lestari',  'total'=>'Rp 325.000','status'=>'Diproses',                 'jenis_bayar'=>'dp'],
  ['no'=>'#ORD-004','tgl_pesan'=>'10/05/2026','tgl_bayar'=>'10/05/2026','nama'=>'Andi Pratama',  'total'=>'Rp 175.000','status'=>'Selesai',                  'jenis_bayar'=>'lunas'],
  ['no'=>'#ORD-005','tgl_pesan'=>'09/05/2026','tgl_bayar'=>'09/05/2026','nama'=>'Rina Susanti',  'total'=>'Rp 295.000','status'=>'DP Dikonfirmasi',          'jenis_bayar'=>'dp'],
  ['no'=>'#ORD-006','tgl_pesan'=>'09/05/2026','tgl_bayar'=>'09/05/2026','nama'=>'Hendra Wijaya', 'total'=>'Rp 145.000','status'=>'Menunggu Pelunasan',       'jenis_bayar'=>'dp'],
  ['no'=>'#ORD-007','tgl_pesan'=>'08/05/2026','tgl_bayar'=>'08/05/2026','nama'=>'Maya Putri',    'total'=>'Rp 380.000','status'=>'Menunggu Konfirmasi Lunas','jenis_bayar'=>'dp'],
  ['no'=>'#ORD-008','tgl_pesan'=>'08/05/2026','tgl_bayar'=>'08/05/2026','nama'=>'Farhan Adilah', 'total'=>'Rp 210.000','status'=>'Pending',                  'jenis_bayar'=>'lunas'],
];

// ── Filter by status tab ──
$status_filter = $_GET['status'] ?? 'Semua';
$filtered = $status_filter === 'Semua'
    ? $all_orders
    : array_values(array_filter($all_orders, fn($o) => $o['status'] === $status_filter));

// ── Count per status ──
$counts = ['Semua' => count($all_orders)];
foreach (['Pending','Diproses','DP Dikonfirmasi','Menunggu Pelunasan','Menunggu Konfirmasi Lunas','Dikirim','Selesai'] as $s) {
  $counts[$s] = count(array_filter($all_orders, fn($o) => $o['status'] === $s));
}

// ── Pagination ──
$per_page = 5;
$total    = count($filtered);
$pages    = max(1, ceil($total / $per_page));
$current  = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$paged    = array_slice($filtered, ($current-1)*$per_page, $per_page);

function badgeClass($s) {
  return match($s) {
    'Selesai'  => 'badge-selesai',
    'Dikirim'  => 'badge-dikirim',
    'Diproses' => 'badge-diproses',
    default    => 'badge-pending',
  };
}
?>

<div class="page-body">
  <?= $alert ?>

  <div class="card">
    <div class="card-header">
      <h1 style="font-size:20px;">Manajemen Pesanan</h1>
      <span style="font-size:13px;color:var(--muted);"><?= $total ?> pesanan ditemukan</span>
    </div>

    <!-- Status Tabs -->
    <div style="padding:14px 20px 0;border-bottom:1px solid var(--border);">
      <div class="filter-tabs">
        <?php foreach ($counts as $tab => $cnt): ?>
        <a href="?status=<?= urlencode($tab) ?>&page=1"
           class="filter-tab <?= $status_filter === $tab ? 'active' : '' ?>">
          <?= $tab ?>
          <span style="font-size:11px;margin-left:4px;opacity:.7;">(<?= $cnt ?>)</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto;">
      <?php if (empty($paged)): ?>
        <div style="padding:48px;text-align:center;color:var(--muted);">
          <div style="font-size:48px;margin-bottom:12px;">&#128230;</div>
          <p>Tidak ada pesanan dengan status "<?= htmlspecialchars($status_filter) ?>".</p>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>&#9998;</th>
            <th>No. Pesanan</th>
            <th>Tanggal</th>
            <th>Nama Pelanggan</th>
            <th>Total</th>
            <th>Jenis Bayar</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paged as $o): ?>
          <tr>
            <td><input type="checkbox" style="accent-color:var(--rose);"></td>
            <td><strong><?= htmlspecialchars($o['no']) ?></strong></td>
            <td><?= $o['tgl_pesan'] ?></td>
            <td><?= htmlspecialchars($o['nama']) ?></td>
            <td style="font-weight:600;color:var(--rose);"><?= $o['total'] ?></td>
            <td>
              <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;
                <?= ($o['jenis_bayar']??'lunas')==='dp' ? 'background:var(--rose-light);color:var(--rose-dark);' : 'background:#eaf7ee;color:#256d3f;' ?>">
                <?= strtoupper($o['jenis_bayar'] ?? 'LUNAS') ?>
              </span>
            </td>
            <td><span class="badge <?= badgeClass($o['status']) ?>"><?= $o['status'] ?></span></td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <?php if ($o['status'] === 'DP Dikonfirmasi'): ?>
                <button class="btn btn-rose btn-sm"
                        onclick="siapDikirim('<?= htmlspecialchars($o['no']) ?>')">
                  &#128230; Siap Dikirim
                </button>
                <?php endif; ?>
                <button class="btn btn-outline btn-sm"
                        onclick="openDetailModal(<?= htmlspecialchars(json_encode($o)) ?>)">
                  Detail
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $i ?>"
           class="<?= $i === $current ? 'pg-active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($current < $pages): ?>
        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $current+1 ?>"
           style="padding:0 10px;">&#8250;</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>

<!-- ── MODAL DETAIL PESANAN ── -->
<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="width:500px;">
    <div class="modal-header">
      <h3>Detail Pesanan <span id="detail-no" style="color:var(--rose);"></span></h3>
      <button class="modal-close" onclick="closeModal('modal-detail')">&#10005;</button>
    </div>
    <div class="modal-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div>
          <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;">Pelanggan</div>
          <div id="detail-nama" style="font-weight:600;color:var(--bark);"></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;">Tanggal</div>
          <div id="detail-tgl"></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;">Total</div>
          <div id="detail-total" style="font-weight:700;color:var(--rose);font-size:16px;"></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;">Status</div>
          <div id="detail-status"></div>
        </div>
      </div>
      <div class="divider"></div>
      <form method="POST" action="manajemen-pesanan.php" style="margin-top:8px;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="no" id="detail-no-input">
        <div class="form-group">
          <label>Ubah Status Pesanan</label>
          <select name="status" id="detail-status-select" class="form-control">
            <option>Pending</option>
            <option>Diproses</option>
            <option>Dikirim</option>
            <option>Selesai</option>
          </select>
        </div>
        <div class="form-group">
          <label>Catatan (Opsional)</label>
          <textarea name="catatan" class="form-control" placeholder="Catatan pengiriman..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
      </form>
    </div>
  </div>
</div>

<!-- ── MODAL SIAP DIKIRIM ── -->
<div class="modal-overlay" id="modal-siap">
  <div class="modal" style="width:400px;">
    <div class="modal-header">
      <h3>Konfirmasi Siap Dikirim</h3>
      <button class="modal-close" onclick="closeModal('modal-siap')">&#10005;</button>
    </div>
    <form method="POST" action="manajemen-pesanan.php">
      <input type="hidden" name="update_status" value="1">
      <input type="hidden" name="no" id="siap-no">
      <input type="hidden" name="status" value="Menunggu Pelunasan">
      <div class="modal-body">
        <div style="background:var(--rose-light);border-radius:8px;padding:12px 14px;font-size:13px;margin-bottom:14px;">
          &#9432; Pesanan <strong id="siap-no-label"></strong> akan diubah ke status
          <strong>Menunggu Pelunasan</strong>.<br><br>
          User akan mendapat notifikasi untuk melunasi sisa pembayaran sebelum barang dikirim.
        </div>
        <div class="form-group">
          <label>Catatan untuk User (Opsional)</label>
          <textarea name="catatan" class="form-control"
                    placeholder="Contoh: Pesanan sudah siap, silakan lunasi sebelum 3x24 jam..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-siap')">Batal</button>
        <button type="submit" class="btn btn-rose">&#128230; Konfirmasi Siap Dikirim</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function siapDikirim(no) {
  document.getElementById('siap-no').value       = no;
  document.getElementById('siap-no-label').textContent = no;
  openModal('modal-siap');
}

function openDetailModal(o) {
  document.getElementById('detail-no').textContent       = o.no;
  document.getElementById('detail-no-input').value       = o.no;
  document.getElementById('detail-nama').textContent     = o.nama;
  document.getElementById('detail-tgl').textContent      = o.tgl_pesan;
  document.getElementById('detail-total').textContent    = o.total;
  const sel = document.getElementById('detail-status-select');
  sel.value = o.status;
  const bdg = document.getElementById('detail-status');
  const cls = {
    'Selesai':'badge-selesai','Dikirim':'badge-dikirim',
    'Diproses':'badge-diproses','Pending':'badge-pending',
    'DP Dikonfirmasi':'badge-dikirim','Menunggu Pelunasan':'badge-diproses',
    'Menunggu Konfirmasi Lunas':'badge-diproses'
  }[o.status] ?? 'badge-pending';
  bdg.innerHTML = `<span class="badge ${cls}">${o.status}</span>`;
  openModal('modal-detail');
}

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
</script>

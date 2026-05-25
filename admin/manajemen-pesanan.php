<?php
// admin/manajemen-pesanan.php — Manajemen Pesanan

$page_title  = 'Manajemen Pesanan — Admin Fleuriste';
$active_menu = 'pesanan';
include 'includes/header.php';
require '../koneksi.php';

// ── HANDLE UPDATE STATUS PESANAN (LOGIKA OTOMATIS) ──
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status']   ?? ''; // Diambil dari input hidden otomatis
    $no_order   = $_POST['no']       ?? ''; // Format string: #ORD-001
    $catatan    = trim($_POST['catatan'] ?? '');

    // Ambil ID angka saja dari string #ORD-001
    $id_pesanan = (int) filter_var($no_order, FILTER_SANITIZE_NUMBER_INT);

    if ($id_pesanan > 0 && $new_status) {
        // Jalankan Query UPDATE ke tabel pesanan
        $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = ?, catatan = ? WHERE id_pesanan = ?");
        $stmt->bind_param("ssi", $new_status, $catatan, $id_pesanan);
        
        if ($stmt->execute()) {
            $status_clean = str_replace('_', ' ', $new_status);
            $alert = '<div class="alert alert-success">&#10003; Status pesanan <strong>' . htmlspecialchars($no_order) . '</strong> berhasil diperbarui ke status <strong>' . htmlspecialchars(ucwords($status_clean)) . '</strong>!</div>';
        } else {
            $alert = '<div class="alert alert-danger">&#10005; Gagal memperbarui status pesanan.</div>';
        }
    }
}

// ── AMBIL DATA PESANAN DARI DATABASE REAL-TIME ──
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

$all_orders = [];

while ($row = mysqli_fetch_assoc($query)) {
    // Sinkronisasi status ENUM DB ke nama Label Tab Tampilan
    $status_label = match($row['status_pesanan']) {
        'belum_bayar' => 'Belum Bayar',
        'pending'     => 'Pending',
        'diproses'    => 'Diproses',
        'dikirim'     => 'Dikirim',
        'selesai'     => 'Selesai',
        'dibatalkan'  => 'Dibatalkan',
        default       => ucfirst($row['status_pesanan'])
    };

    $all_orders[] = [
        'id_pesanan'  => $row['id_pesanan'],
        'no'          => '#ORD-' . str_pad($row['id_pesanan'], 3, '0', STR_PAD_LEFT),
        'tgl_pesan'   => date('d/m/Y', strtotime($row['tanggal_pesanan'])),
        'nama'        => $row['nama_user'] ?? 'Guest/User Dihapus',
        'total'       => 'Rp ' . number_format($row['total_harga'], 0, ',', '.'),
        'status_raw'  => $row['status_pesanan'], // status asli database (huruf kecil)
        'status'      => $status_label,          // status rapi untuk tab filter
        'jenis_bayar' => 'LUNAS',
        'catatan'     => $row['catatan'] ?? ''
    ];
}

// ── FILTER BY STATUS TAB ──
$status_filter = $_GET['status'] ?? 'Semua';
$filtered = $status_filter === 'Semua'
    ? $all_orders
    : array_values(array_filter($all_orders, fn($o) => $o['status'] === $status_filter));

// ── COUNT PER STATUS TAB ──
$counts = ['Semua' => count($all_orders)];
foreach (['Belum Bayar', 'Pending', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'] as $s) {
    $counts[$s] = count(array_filter($all_orders, fn($o) => $o['status'] === $s));
}

// ── PAGINATION ──
$per_page = 5;
$total    = count($filtered);
$pages    = max(1, ceil($total / $per_page));
$current  = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$paged    = array_slice($filtered, ($current-1)*$per_page, $per_page);

// Class warna badge CSS dinamis
function badgeClass($s) {
    return match($s) {
        'Selesai'     => 'badge-selesai',
        'Dikirim'     => 'badge-dikirim',
        'Diproses'    => 'badge-diproses',
        'Belum Bayar' => 'badge-pending',
        'Dibatalkan'  => 'badge-batal',
        default       => 'badge-pending',
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

    <div style="padding:14px 20px 0;border-bottom:1px solid var(--border);">
      <div class="filter-tabs" style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php foreach ($counts as $tab => $cnt): ?>
        <a href="?status=<?= urlencode($tab) ?>&page=1"
           class="filter-tab <?= $status_filter === $tab ? 'active' : '' ?>" 
           style="text-decoration:none;padding:8px 12px;display:inline-block;">
          <?= $tab ?>
          <span style="font-size:11px;margin-left:4px;opacity:.7;">(<?= $cnt ?>)</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="overflow-x:auto;">
      <?php if (empty($paged)): ?>
        <div style="padding:48px;text-align:center;color:var(--muted);">
          <div style="font-size:48px;margin-bottom:12px;">&#128230;</div>
          <p>Tidak ada pesanan dengan status "<?= htmlspecialchars($status_filter) ?>".</p>
        </div>
      <?php else: ?>
      <table class="data-table" style="width:100%;border-collapse:collapse;text-align:left;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);background:#fafafa;">
            <th style="padding:12px 15px;">No. Pesanan</th>
            <th>Tanggal</th>
            <th>Nama Pelanggan</th>
            <th>Total</th>
            <th>Jenis Bayar</th>
            <th>Status</th>
            <th style="text-align:center;">Aksi Cepat</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paged as $o): ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:15px 12px;"><strong><?= htmlspecialchars($o['no']) ?></strong></td>
            <td><?= $o['tgl_pesan'] ?></td>
            <td><?= htmlspecialchars($o['nama']) ?></td>
            <td style="font-weight:600;color:var(--rose);"><?= $o['total'] ?></td>
            <td>
              <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:100px;background:#eaf7ee;color:#256d3f;">
                <?= $o['jenis_bayar'] ?>
              </span>
            </td>
            <td><span class="badge <?= badgeClass($o['status']) ?>"><?= $o['status'] ?></span></td>
            <td style="text-align:center; padding:10px;">
              <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                
                <?php if ($o['status_raw'] === 'pending'): ?>
                <button class="btn btn-rose btn-sm"
                        onclick="konfirmasiAksi('<?= htmlspecialchars($o['no']) ?>', 'diproses', 'Menyetujui Pembayaran', 'Pembayaran valid, pesanan akan diteruskan untuk dirangkai.')">
                  ✓ Setujui Pembayaran
                </button>
                
                <?php elseif ($o['status_raw'] === 'diproses'): ?>
                <button class="btn btn-blue btn-sm"
                        onclick="konfirmasiAksi('<?= htmlspecialchars($o['no']) ?>', 'dikirim', 'Kirim Pesanan', 'Rangkaian bunga selesai dibuat dan siap diserahkan ke kurir / siap diambil.')">
                  🚚 Kirim Pesanan
                </button>
                
                <?php elseif ($o['status_raw'] === 'dikirim'): ?>
                <button class="btn btn-success btn-sm"
                        onclick="konfirmasiAksi('<?= htmlspecialchars($o['no']) ?>', 'selesai', 'Selesaikan Pesanan', 'Konfirmasi bahwa pesanan ini telah sukses diterima oleh pelanggan.')">
                  🏁 Selesaikan Pesanan
                </button>
                
                <?php else: ?>
                <span style="font-size:12px;color:var(--muted); font-style:italic;">Tidak ada aksi</span>
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

    <?php if ($pages > 1): ?>
    <nav class="pagination" style="padding:20px;display:flex;gap:5px;">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $i ?>"
           class="<?= $i === $current ? 'pg-active' : '' ?>" style="padding:5px 10px;text-decoration:none;border:1px solid var(--border);"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="width:500px;background:#fff;margin:10% auto;padding:20px;border-radius:8px;">
    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
      <h3>Detail Data <span id="detail-no" style="color:var(--rose);"></span></h3>
      <button class="modal-close" onclick="closeModal('modal-detail')">&#10005;</button>
    </div>
    <div class="modal-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div>
          <div style="font-size:11px;color:var(--muted);">Pelanggan</div>
          <div id="detail-nama" style="font-weight:600;"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);">Tanggal</div>
          <div id="detail-tgl"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);">Total Transaksi</div>
          <div id="detail-total" style="font-weight:700;color:var(--rose);"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);">Status Saat Ini</div>
          <div id="detail-status"></div>
        </div>
      </div>
      <div style="border-top:1px solid #eee;padding-top:10px;">
         <div style="font-size:11px;color:var(--muted);">Catatan Terakhir:</div>
         <p id="detail-catatan-teks" style="font-size:13px;background:#fafafa;padding:8px;border-radius:4px;margin-top:4px;color:#333;"></p>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-aksi-otomatis">
  <div class="modal" style="width:400px;background:#fff;margin:10% auto;padding:20px;border-radius:8px;">
    <div class="modal-header" style="display:flex;justify-content:space-between;margin-bottom:15px;">
      <h3 id="modal-aksi-title">Konfirmasi Aksi</h3>
      <button class="modal-close" onclick="closeModal('modal-aksi-otomatis')">&#10005;</button>
    </div>
    <form method="POST" action="manajemen-pesanan.php">
      <input type="hidden" name="update_status" value="1">
      <input type="hidden" name="no" id="aksi-no">
      <input type="hidden" name="status" id="aksi-status-target">
      <div class="modal-body">
        <div id="aksi-keterangan-box" style="background:#eef6ff;color:#1e40af;border-radius:8px;padding:12px;font-size:13px;margin-bottom:14px;">
          Keterangan aksi...
        </div>
        <div class="form-group">
          <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:600;">Catatan Admin (Opsional)</label>
          <textarea name="catatan" id="aksi-catatan" class="form-control" style="width:100%;padding:8px;" placeholder="Contoh: Bukti transfer sudah sesuai..."></textarea>
        </div>
      </div>
      <div class="modal-footer" style="display:flex;justify-content:end;gap:10px;margin-top:15px;">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-aksi-otomatis')">Batal</button>
        <button type="submit" class="btn btn-rose" style="padding:8px 15px;" id="modal-aksi-submit-btn">Konfirmasi</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function openModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Mengatur isi modal konfirmasi aksi otomatis secara fleksibel
function konfirmasiAksi(no, statusTarget, titleText, infoText) {
  document.getElementById('aksi-no').value = no;
  document.getElementById('aksi-status-target').value = statusTarget;
  document.getElementById('modal-aksi-title').textContent = titleText;
  document.getElementById('aksi-keterangan-box').textContent = infoText;
  document.getElementById('aksi-catatan').value = '';
  
  // Mengatur variasi warna box keterangan berdasarkan status agar visualnya bagus
  const box = document.getElementById('aksi-keterangan-box');
  if(statusTarget === 'diproses') {
      box.style.background = '#eef6ff'; box.style.color = '#1e40af';
  } else if(statusTarget === 'dikirim') {
      box.style.background = '#fff8e1'; box.style.color = '#b7791f';
  } else if(statusTarget === 'selesai') {
      box.style.background = '#eaf7ee'; box.style.color = '#256d3f';
  }

  openModal('modal-aksi-otomatis');
}

function openDetailModal(o) {
  document.getElementById('detail-no').textContent = o.no;
  document.getElementById('detail-nama').textContent = o.nama;
  document.getElementById('detail-tgl').textContent = o.tgl_pesan;
  document.getElementById('detail-total').textContent = o.total;
  document.getElementById('detail-catatan-teks').textContent = o.catatan ? o.catatan : 'Tidak ada catatan.';
  
  const bdg = document.getElementById('detail-status');
  const cls = {
    'Selesai':'badge-selesai','Dikirim':'badge-dikirim',
    'Diproses':'badge-diproses','Pending':'badge-pending','Belum Bayar':'badge-pending','Dibatalkan':'badge-batal'
  }[o.status] ?? 'badge-pending';
  bdg.innerHTML = `<span class="badge ${cls}">${o.status}</span>`;
  openModal('modal-detail');
}
</script>

<style>
.modal-overlay {
  display: none; position: fixed; z-index: 999; left: 0; top: 0;
  width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); overflow: auto;
}
.badge-selesai { background: #eaf7ee; color: #256d3f; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.badge-dikirim { background: #eef6ff; color: #1e40af; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.badge-diproses { background: #fff8e1; color: #b7791f; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.badge-pending { background: #fdeaea; color: #9b2020; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.badge-batal { background: #eee; color: #666; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.btn-sm { padding: 6px 12px; font-size: 12px; cursor: pointer; border-radius: 4px; font-weight: 500; border: none; }
.btn-rose { background: #e11d48; color: white; }
.btn-blue { background: #2563eb; color: white; }
.btn-success { background: #16a34a; color: white; }
.btn-outline { background: white; border: 1px solid #ccc; color: #333; }
.form-control { border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
</style>
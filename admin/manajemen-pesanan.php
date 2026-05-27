<?php
// admin/manajemen-pesanan.php — Manajemen Pesanan

// Hubungkan session di paling atas agar flash message alert via redirect berfungsi murni
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title  = 'Manajemen Pesanan — Admin Fleuriste';
$active_menu = 'pesanan';
include 'includes/header.php';
require '../koneksi.php';

// Atur timezone agar pencatatan waktu PHP selaras dengan waktu server database Anda
date_default_timezone_set('Asia/Jakarta');

// Ambil pesan alert dari session (jika ada), lalu hapus dari session agar tidak muncul terus-menerus
$alert = $_SESSION['alert'] ?? '';
unset($_SESSION['alert']);

// ── LOGIKA OTOMATIS: PEMBATALAN PESANAN (ANTI-BUG DUPLIKASI STOK) ──

// 1. Ambil ID pesanan terakhir untuk batas aman pembatalan
$query_max = mysqli_query($conn, "SELECT MAX(id_pesanan) AS id_terakhir FROM pesanan");
$row_max = mysqli_fetch_assoc($query_max);
$id_terakhir = $row_max['id_terakhir'] ?? 0;

if ($id_terakhir > 0) {
    // Ambil pesanan lama yang belum bayar/pending
    $query_expired = $conn->prepare("
        SELECT id_pesanan, tanggal_pesanan 
        FROM pesanan 
        WHERE (status_pesanan = 'belum_bayar' OR status_pesanan = 'pending') 
          AND id_pesanan < ?
    ");
    $query_expired->bind_param("i", $id_terakhir);
    $query_expired->execute();
    $res_expired = $query_expired->get_result();

    while ($expired_order = $res_expired->fetch_assoc()) {
        $id_batal_otomatis = $expired_order['id_pesanan'];
        
        $waktu_pesanan  = strtotime($expired_order['tanggal_pesanan']);
        $waktu_sekarang = time(); 
        $selisih_detik  = $waktu_sekarang - $waktu_pesanan;
        $batas_1_jam    = 3600; // 1 jam = 3600 detik

        // Hanya batalkan jika benar-benar sudah melewati 1 jam
        if ($waktu_pesanan > 0 && $selisih_detik >= $batas_1_jam) {
            
            $conn->begin_transaction();
            try {
                // LANGKAH 1: Kunci dan Update status pesanan dulu menjadi 'dibatalkan' 
                $stmt_up = $conn->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan', catatan = 'Dibatalkan otomatis oleh sistem (Batas waktu 1 jam habis).' WHERE id_pesanan = ?");
                $stmt_up->bind_param("i", $id_batal_otomatis);
                $stmt_up->execute();

                // LANGKAH 2: Ambil detail produk yang dibeli
                $stmt_det = $conn->prepare("SELECT id_produk, jumlah_produk FROM detail_pesanan WHERE id_pesanan = ?");
                $stmt_det->bind_param("i", $id_batal_otomatis);
                $stmt_det->execute();
                $res_det = $stmt_det->get_result();

                // LANGKAH 3: Kembalikan stok ke database berdasarkan data barusan
                $stmt_rc = $conn->prepare("UPDATE produk SET stok_produk = stok_produk + ? WHERE id_produk = ?");
                while ($row_det = $res_det->fetch_assoc()) {
                    $stmt_rc->bind_param("ii", $row_det['jumlah_produk'], $row_det['id_produk']);
                    $stmt_rc->execute();
                }

                // Jika semua langkah sukses tanpa error, simpan permanen ke database
                $conn->commit();
            } catch (Exception $e) {
                // Jika gagal di tengah jalan, batalkan semua perubahan agar stok tidak rusak
                $conn->rollback();
            }
        }
    }
}
  
// ── HANDLE UPDATE STATUS PESANAN (LOGIKA MANUAL ADMIN — ANTI REFRESH BUG & RESUBMISSION) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status']   ?? ''; 
    $no_order   = $_POST['no']       ?? ''; // Contoh: "#ORD-001"
    $catatan    = trim($_POST['catatan'] ?? '');

    // Potong string berdasarkan tanda '-' untuk mendapatkan angka murni di sebelah kanan
    $id_pesanan = 0;
    if (!empty($no_order) && strpos($no_order, '-') !== false) {
        $parts = explode('-', $no_order);
        $id_pesanan = (int) end($parts); // Mengambil bagian paling akhir ("001" -> 1)
    }

    // Pastikan ID pesanan valid dan di atas angka 0 sebelum update database
    if ($id_pesanan > 0 && $new_status) {
        
        // Mulai Database Transaction agar proses restock dan update status sinkron
        $conn->begin_transaction();

        try {
            // LANGKAH AMAN: Ambil dan kunci baris pesanan saat ini untuk membaca status aslinya di database
            $stmt_cek = $conn->prepare("SELECT status_pesanan FROM pesanan WHERE id_pesanan = ? FOR UPDATE");
            $stmt_cek->bind_param("i", $id_pesanan);
            $stmt_cek->execute();
            $res_cek = $stmt_cek->get_result();
            $pesanan_sekarang = $res_cek->fetch_assoc();
            
            $status_lama = $pesanan_sekarang['status_pesanan'] ?? '';

            // Jika status berubah menjadi 'dibatalkan', kembalikan stok produk
            if ($new_status === 'dibatalkan') {
                // STOK HANYA KEMBALI jika status sebelumnya adalah 'belum_bayar' atau 'pending'
                // Pengaman krusial agar ketika form di-resubmit secara paksa/di-refresh, bagian ini di-skip
                if ($status_lama === 'belum_bayar' || $status_lama === 'pending') {
                    
                    // 1. Ambil detail produk yang dibeli pada pesanan tersebut
                    $stmt_detail = $conn->prepare("SELECT id_produk, jumlah_produk FROM detail_pesanan WHERE id_pesanan = ?");
                    $stmt_detail->bind_param("i", $id_pesanan);
                    $stmt_detail->execute();
                    $result_detail = $stmt_detail->get_result();

                    // 2. Lakukan looping untuk menambah kembali stok masing-masing produk
                    $stmt_restock = $conn->prepare("UPDATE produk SET stok_produk = stok_produk + ? WHERE id_produk = ?");
                    while ($row = $result_detail->fetch_assoc()) {
                        $stmt_restock->bind_param("ii", $row['jumlah_produk'], $row['id_produk']);
                        $stmt_restock->execute();
                    }
                }
            }

            // Jalankan Query UPDATE utama ke tabel pesanan
            $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = ?, catatan = ? WHERE id_pesanan = ?");
            $stmt->bind_param("ssi", $new_status, $catatan, $id_pesanan);
            $stmt->execute();

            // ── LOGIKA BARU UNTUK SINKRONISASI STATUS TRANSAKSI DP / LUNAS ──
            if ($new_status === 'diproses') {
                // Jika admin memproses pesanan, artinya pembayaran yang berstatus 'menunggu' (baik itu DP atau Lunas awal) disetujui
                $stmt_transaksi = $conn->prepare("UPDATE transaksi SET status_pembayaran = 'diterima' WHERE id_pesanan = ? AND status_pembayaran = 'menunggu'");
                $stmt_transaksi->bind_param("i", $id_pesanan);
                $stmt_transaksi->execute();
            } 
            // Tambahan: Jika admin memilih menyelesaikan pesanan, pastikan transaksi pelunasannya juga otomatis disetujui
            elseif ($new_status === 'selesai') {
                $stmt_transaksi = $conn->prepare("UPDATE transaksi SET status_pembayaran = 'diterima' WHERE id_pesanan = ? AND status_pembayaran = 'menunggu'");
                $stmt_transaksi->bind_param("i", $id_pesanan);
                $stmt_transaksi->execute();
            }
            // Tambahan: Jika dibatalkan, otomatis tolak transaksi yang masih menggantung
            elseif ($new_status === 'dibatalkan') {
                $stmt_transaksi = $conn->prepare("UPDATE transaksi SET status_pembayaran = 'ditolak' WHERE id_pesanan = ? AND status_pembayaran = 'menunggu'");
                $stmt_transaksi->bind_param("i", $id_pesanan);
                $stmt_transaksi->execute();
            }

            // Jika semua query aman, simpan permanen ke database
            $conn->commit();

            $status_clean = str_replace('_', ' ', $new_status);
            $_SESSION['alert'] = '<div class="alert alert-success">&#10003; Status pesanan <strong>' . htmlspecialchars($no_order) . '</strong> berhasil diperbarui ke status <strong>' . htmlspecialchars(ucwords($status_clean)) . '</strong>!</div>';

        } catch (Exception $e) {
            // Jika crash di tengah jalan, batalkan perubahan
            $conn->rollback();
            $_SESSION['alert'] = '<div class="alert alert-danger">&#10005; Gagal memperbarui status pesanan: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

    } else {
        $_SESSION['alert'] = '<div class="alert alert-danger">&#10005; Gagal memproses data. ID Pesanan tidak valid (' . htmlspecialchars($no_order) . ').</div>';
    }

    // KUNCI UTAMA PRG: Redirect kembali ke halaman ini menggunakan metode GET agar popup resubmission hilang
    $status_param = $_GET['status'] ?? 'Semua';
    $page_param = $_GET['page'] ?? '1';
    header("Location: manajemen-pesanan.php?status=" . urlencode($status_param) . "&page=" . $page_param);
    exit();
}

// ── AMBIL DATA PESANAN (TANPA JOIN KE TRANSAKSI AGAR TIDAK DOBEL) ──
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
    $id_pesanan_loop = $row['id_pesanan'];

    // Cari riwayat transaksi untuk pesanan ini
    $q_trans = mysqli_query($conn, "SELECT jenis_pembayaran, status_pembayaran, bukti_pembayaran FROM transaksi WHERE id_pesanan = '$id_pesanan_loop' ORDER BY id_transaksi ASC");
    
    $is_dp = false;
    $pelunasan_menunggu = false;
    $pelunasan_lunas = false;
    $bukti_foto_arr = [];
    $jenis_bayar_label = 'LUNAS';

    while ($t = mysqli_fetch_assoc($q_trans)) {
        if (!empty($t['bukti_pembayaran'])) {
            $bukti_foto_arr[] = $t['bukti_pembayaran'];
        }
        if ($t['jenis_pembayaran'] === 'dp') {
            $is_dp = true;
            $jenis_bayar_label = 'DP';
        }
        if ($t['jenis_pembayaran'] === 'lunas') {
            if ($t['status_pembayaran'] === 'menunggu') {
                $pelunasan_menunggu = true;
            } elseif ($t['status_pembayaran'] === 'diterima') {
                $pelunasan_lunas = true;
            }
        }
    }

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
        'id_pesanan'         => $row['id_pesanan'],
        'no'                 => '#ORD-' . str_pad($row['id_pesanan'], 3, '0', STR_PAD_LEFT),
        'tgl_pesan'          => date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])),
        'nama'               => $row['nama_user'] ?? 'Guest/User Dihapus',
        'total'              => 'Rp ' . number_format($row['total_harga'], 0, ',', '.'),
        'status_raw'         => $row['status_pesanan'], 
        'status'             => $status_label,          
        'jenis_bayar'        => $jenis_bayar_label,
        'is_dp'              => $is_dp,
        'pelunasan_menunggu' => $pelunasan_menunggu,
        'pelunasan_lunas'    => $pelunasan_lunas,
        'bukti_foto'         => $bukti_foto_arr, 
        'catatan'            => $row['catatan'] ?? ''
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

$get_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// Pastikan $current tidak keluar dari batas halaman yang ada
$current  = ($get_page > $pages) ? $pages : max(1, $get_page);

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
            <th>Tanggal Pemesanan</th>
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
              <?php if($o['is_dp']): ?>
                <div style="display: flex; flex-direction: column; gap: 4px; align-items:flex-start;">
                  <span style="font-size:11px; font-weight:600; padding:3px 8px; border-radius:100px; background:#fff8e1; color:#b7791f; text-align:center;">
                    DP (Uang Muka)
                  </span>
                  <?php if($o['pelunasan_lunas']): ?>
                    <small style="font-size: 10px; color: #16a34a; font-weight: 700;">✓ Pelunasan Lunas</small>
                  <?php elseif($o['pelunasan_menunggu']): ?>
                    <small style="font-size: 10px; color: #d97706; font-weight: 700;">⏳ Cek Pelunasan</small>
                  <?php else: ?>
                    <small style="font-size: 10px; color: #dc2626; font-weight: 700;">⚠️ Menunggu Pelunasan</small>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span style="font-size:11px; font-weight:600; padding:3px 8px; border-radius:100px; background:#eaf7ee; color:#256d3f; display:inline-block; text-align:center;">
                  LUNAS TOTAL
                </span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= badgeClass($o['status']) ?>"><?= $o['status'] ?></span></td>
            <td style="text-align:center; padding:10px;">
              <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                
                <?php if ($o['status_raw'] === 'pending' || $o['status_raw'] === 'belum_bayar'): ?>
                    <button class="btn btn-outline btn-sm" style="color:#dc2626; border-color:#fca5a5;"
                            onclick="konfirmasiAksi('<?= htmlspecialchars($o['no']) ?>', 'dibatalkan', 'Batalkan Pesanan', 'Membatalkan pesanan ini akan secara otomatis mengembalikan jumlah stok barang kembali ke database toko.')">
                      ✕ Batalkan
                    </button>
                    <?php if($o['status_raw'] === 'pending'): ?>
                    
                      
                    <?php endif; ?>
                
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
      <?php for ($i = 1; $i <= $pages; $i++): 
        // Menentukan apakah tombol ini adalah halaman yang sedang aktif
        $isActive = ($i === $current);
      ?>
        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $i ?>"
          class="<?= $isActive ? 'pg-active' : '' ?>" 
          style="padding:5px 10px; text-decoration:none; border:1px solid var(--border); 
                  <?= $isActive ? 'background:var(--rose); color:#fff;' : 'background:#fff; color:#333;' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="width:500px;background:#fff;margin:5% auto;padding:20px;border-radius:8px;max-height:85vh;overflow-y:auto;">
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
          <div style="font-size:11px;color:var(--muted);">Metode Pembayaran</div>
          <div id="detail-jenis-bayar" style="font-weight:600;color:var(--bark);"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);">Status Saat Ini</div>
          <div id="detail-status"></div>
        </div>
      </div>

      <div style="border-top:1px solid #eee;padding-top:12px;margin-bottom:12px;">
         <div style="font-size:11px;color:var(--muted);margin-bottom:6px;">Bukti Transfer Pelanggan:</div>
         <div id="bukti-container" style="text-align:center;background:#f5f5f5;padding:10px;border-radius:6px;border:1px dashed #ccc;">
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
    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
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

function konfirmasiAksi(no, statusTarget, titleText, infoText) {
  document.getElementById('aksi-no').value = no;
  document.getElementById('aksi-status-target').value = statusTarget;
  document.getElementById('modal-aksi-title').textContent = titleText;
  document.getElementById('aksi-keterangan-box').textContent = infoText;
  document.getElementById('aksi-catatan').value = '';
  
  const box = document.getElementById('aksi-keterangan-box');
  const btnSubmit = document.getElementById('modal-aksi-submit-btn');

  if(statusTarget === 'diproses') {
      box.style.background = '#eaf7ee'; box.style.color = '#256d3f';
      btnSubmit.className = "btn btn-success";
  } else if(statusTarget === 'dikirim') {
      box.style.background = '#fff8e1'; box.style.color = '#b7791f';
      btnSubmit.className = "btn btn-blue";
  } else if(statusTarget === 'selesai') {
      box.style.background = '#eef6ff'; box.style.color = '#1e40af';
      btnSubmit.className = "btn btn-blue";
  } else if(statusTarget === 'dibatalkan') {
      box.style.background = '#fdeaea'; box.style.color = '#9b2020';
      btnSubmit.className = "btn btn-rose";
  }

  openModal('modal-aksi-otomatis');
}

function openDetailModal(o) {
  document.getElementById('detail-no').textContent = o.no;
  document.getElementById('detail-nama').textContent = o.nama;
  document.getElementById('detail-tgl').textContent = o.tgl_pesan;
  document.getElementById('detail-total').textContent = o.total;
  document.getElementById('detail-jenis-bayar').textContent = o.jenis_bayar;
  document.getElementById('detail-catatan-teks').textContent = o.catatan ? o.catatan : 'Tidak ada catatan.';
  
  const buktiContainer = document.getElementById('bukti-container');
  // MODIFIKASI: Loop untuk memunculkan semua foto bukti (DP & Pelunasan)
  if (o.bukti_foto && o.bukti_foto.length > 0) {
      let imagesHtml = '';
      o.bukti_foto.forEach((img, index) => {
          let title = o.bukti_foto.length > 1 ? `Bukti Transfer ke-${index+1}` : `Bukti Transfer`;
          imagesHtml += `
            <div style="margin-bottom: 10px;">
                <span style="font-size:10px; font-weight:bold; color:#555;">${title}:</span><br>
                <img src="../assets/uploads/bukti/${img}" alt="Bukti" style="max-width:100%; max-height:250px; border-radius:4px; border:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.05); margin-top:4px;">
            </div>`;
      });
      buktiContainer.innerHTML = imagesHtml;
  } else {
      buktiContainer.innerHTML = `<span style="color:#999; font-size:12px; font-style:italic;">Pelanggan belum mengunggah foto bukti pembayaran.</span>`;
  }

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
.modal-overlay { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); overflow: auto; }
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
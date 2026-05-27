<?php
// admin/konfirmasi-pembayaran.php

// Hubungkan session di paling atas agar flash message via redirect (PRG) berfungsi murni
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title  = 'Konfirmasi Pembayaran — Admin Fleuriste';
$active_menu = 'konfirmasi';
include 'includes/header.php';
include '../koneksi.php'; 

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

// Ambil data alert dari session (jika ada), lalu hapus dari session agar tidak duplikat saat di-refresh
$alert = $_SESSION['alert'] ?? '';
unset($_SESSION['alert']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action        = $_POST['action']        ?? '';
    $no_order      = $_POST['no_order']      ?? '';
    $id_pesanan    = (int)($_POST['id_bayar'] ?? 0); 
    $id_transaksi  = (int)($_POST['id_transaksi'] ?? 0); 
    $catatan_admin = trim($_POST['catatan_admin'] ?? ''); 

    if ($id_transaksi > 0 && $id_pesanan > 0) {
        if ($action === 'konfirmasi') {
            // ── PROSES TERIMA PEMBAYARAN ──
            $conn->begin_transaction();
            try {
                // 1. Update HANYA transaksi yang sedang ditinjau berdasarkan id_transaksi (Anti-ketimpa)
                $stmt1 = $conn->prepare("UPDATE transaksi SET status_pembayaran = 'diterima' WHERE id_transaksi = ?");
                $stmt1->bind_param("i", $id_transaksi);
                $stmt1->execute();

                // 2. Update status_pesanan di tabel pesanan menjadi 'diproses' dan simpan catatan admin jika ada
                $stmt2 = $conn->prepare("UPDATE pesanan SET status_pesanan = 'diproses', catatan = ? WHERE id_pesanan = ?");
                $stmt2->bind_param("si", $catatan_admin, $id_pesanan);
                $stmt2->execute();

                $conn->commit();
                $_SESSION['alert'] = ['type' => 'success', 'msg' => "Pembayaran untuk $no_order berhasil dikonfirmasi!"];
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['alert'] = ['type' => 'error', 'msg' => "Gagal memproses konfirmasi pembayaran: " . $e->getMessage()];
            }

        } elseif ($action === 'tolak') {
            // ── PROSES TOLAK PEMBAYARAN ──
            if (empty($catatan_admin)) {
                $catatan_admin = "Pembayaran ditolak oleh admin. Bukti transfer tidak valid atau tidak sesuai.";
            }

            $conn->begin_transaction();
            try {
                // DETEKSI APAKAH INI PELUNASAN: Cek apakah sudah ada transaksi berstatus 'diterima' sebelumnya untuk pesanan ini
                $stmt_cek_dp = $conn->prepare("SELECT COUNT(*) as jml FROM transaksi WHERE id_pesanan = ? AND status_pembayaran = 'diterima'");
                $stmt_check_dp = $conn->prepare("SELECT COUNT(*) as jml FROM transaksi WHERE id_pesanan = ? AND status_pembayaran = 'diterima' AND id_transaksi != ?");
                $stmt_check_dp->bind_param("ii", $id_pesanan, $id_transaksi);
                $stmt_check_dp->execute();
                $is_pelunasan = ($stmt_check_dp->get_result()->fetch_assoc()['jml'] > 0);

                // 1. Update status_pembayaran khusus transaksi ini menjadi 'ditolak'
                $stmt1 = $conn->prepare("UPDATE transaksi SET status_pembayaran = 'ditolak' WHERE id_transaksi = ?");
                $stmt1->bind_param("i", $id_transaksi);
                $stmt1->execute();

                if ($is_pelunasan) {
                    // JIKA PELUNASAN DITOLAK: Kembalikan status pesanan ke 'diproses' agar user bisa upload ulang bukti valid (Stok aman)
                    $stmt2 = $conn->prepare("UPDATE pesanan SET status_pesanan = 'diproses', catatan = ? WHERE id_pesanan = ?");
                    $stmt2->bind_param("si", $catatan_admin, $id_pesanan);
                    $stmt2->execute();

                    $_SESSION['alert'] = ['type' => 'error', 'msg' => "Bukti pelunasan $no_order ditolak. Status pesanan dikembalikan ke 'Diproses' (Stok aman karena DP awal sudah sah)."];
                } else {
                    // JIKA PEMBAYARAN UTAMA (DP / LUNAS AWAL) DITOLAK: Batalkan pesanan & kembalikan stok barang
                    $stmt_cek = $conn->prepare("SELECT status_pesanan FROM pesanan WHERE id_pesanan = ? FOR UPDATE");
                    $stmt_cek->bind_param("i", $id_pesanan);
                    $stmt_cek->execute();
                    $status_lama = $stmt_cek->get_result()->fetch_assoc()['status_pesanan'] ?? '';

                    if ($status_lama !== 'dibatalkan') {
                        $stmt_detail = $conn->prepare("SELECT id_produk, jumlah_produk FROM detail_pesanan WHERE id_pesanan = ?");
                        $stmt_detail->bind_param("i", $id_pesanan);
                        $stmt_detail->execute();
                        $result_detail = $stmt_detail->get_result();

                        $stmt_restock = $conn->prepare("UPDATE produk SET stok_produk = stok_produk + ? WHERE id_produk = ?");
                        while ($row = $result_detail->fetch_assoc()) {
                            $stmt_restock->bind_param("ii", $row['jumlah_produk'], $row['id_produk']);
                            $stmt_restock->execute();
                        }
                    }

                    $stmt2 = $conn->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan', catatan = ? WHERE id_pesanan = ?");
                    $stmt2->bind_param("si", $catatan_admin, $id_pesanan);
                    $stmt2->execute();

                    $_SESSION['alert'] = ['type' => 'error', 'msg' => "Pembayaran utama $no_order ditolak. Status otomatis menjadi 'Dibatalkan' & stok produk telah dikembalikan."];
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['alert'] = ['type' => 'error', 'msg' => "Gagal menolak pembayaran: " . $e->getMessage()];
            }
        }
    }

    header("Location: konfirmasi-pembayaran.php");
    exit();
}

// ── Perbaikan Filter Query: Fokus mencari transaksi yang berstatus 'menunggu' ──
$pembayaran = [];
$query = mysqli_query($conn, "
    SELECT 
        t.id_transaksi,
        t.id_pesanan,
        t.total_pembayaran,
        t.bukti_pembayaran,
        t.status_pembayaran,
        t.metode_pembayaran,
        t.jenis_pembayaran,
        t.tanggal_transaksi,
        p.total_harga,
        p.status_pesanan,
        p.metode_pengiriman,
        p.catatan,
        u.nama_user
    FROM transaksi t
    JOIN pesanan p ON t.id_pesanan = p.id_pesanan
    LEFT JOIN user u ON p.id_user = u.id_user
    WHERE t.status_pembayaran = 'menunggu'
    ORDER BY t.id_transaksi DESC
");

while ($row = mysqli_fetch_assoc($query)) {
    $id_pesanan_loop = $row['id_pesanan'];
    $items = [];
    $detail = mysqli_query($conn, "
        SELECT dp.jumlah_produk, pr.nama_produk
        FROM detail_pesanan dp
        LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
        WHERE dp.id_pesanan = '$id_pesanan_loop'
    ");

    while ($d = mysqli_fetch_assoc($detail)) {
        $items[] = [
            'name' => $d['nama_produk'],
            'qty'  => $d['jumlah_produk']
        ];
    }

    $path_bukti = '../assets/uploads/bukti/' . $row['bukti_pembayaran']; 
    $jenis_pembayaran_raw = strtolower($row['jenis_pembayaran'] ?? 'lunas');

    // Deteksi real-time apakah ini pelunasan (jika ada transaksi berstatus 'diterima' sebelumnya)
    $stmt_cek_prev = mysqli_query($conn, "SELECT COUNT(*) as jml FROM transaksi WHERE id_pesanan = '$id_pesanan_loop' AND status_pembayaran = 'diterima'");
    $prev_data = mysqli_fetch_assoc($stmt_cek_prev);
    if ($prev_data['jml'] > 0 && $jenis_pembayaran_raw === 'lunas') {
        $jenis_pembayaran_raw = 'pelunasan';
    }

    $pembayaran[] = [
        'id_transaksi'=> $row['id_transaksi'],
        'id_pesanan'  => $row['id_pesanan'],
        'no_order'    => 'FLR-' . str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT),
        'tgl_bayar'   => date('d M Y H:i', strtotime($row['tanggal_transaksi'])),
        'nama_user'   => $row['nama_user'] ?? 'Guest',
        'jenis'       => $jenis_pembayaran_raw, 
        'nominal'     => $row['total_pembayaran'], // Mengambil nominal riil dari DB tanpa tebak-tebakan rumus
        'total_order' => $row['total_harga'],
        'metode'      => $row['metode_pembayaran'] ?? $row['metode_pengiriman'],
        'bukti'       => (!empty($row['bukti_pembayaran'])) ? $path_bukti : '',
        'bukti_raw'   => $row['bukti_pembayaran'] ?? '',
        'catatan'     => $row['catatan'],
        'status'      => $row['status_pembayaran'],
        'items'       => $items
    ];
}

$jenis_label = ['dp' => 'DP 50%', 'lunas' => 'Lunas Penuh', 'pelunasan' => 'Pelunasan Sisa DP'];
$jenis_color = ['dp' => '#b7791f', 'lunas' => '#256d3f', 'pelunasan' => '#1e40af'];
$jenis_bg    = ['dp' => '#fff8e1', 'lunas' => '#eaf7ee', 'pelunasan' => '#eef6ff'];
?>

<div class="page-body">
  <?php if ($alert): ?>
  <div class="alert <?= $alert['type']==='success'?'alert-success':'alert-danger' ?>" style="padding:12px; margin-bottom:15px; border-radius:6px; <?= $alert['type']==='success'?'background:#eaf7ee;color:#256d3f;':'background:#fdeaea;color:#9b2020;' ?>">
    <?= $alert['type']==='success'?'&#10003;':'&#10006;' ?> <?= htmlspecialchars($alert['msg']) ?>
  </div>
  <?php endif; ?>

  <div class="page-header" style="margin-bottom: 20px;">
    <h1 style="font-size: 24px;">Konfirmasi Pembayaran</h1>
    <span style="font-size:13px;color:var(--muted);"><?= count($pembayaran) ?> menunggu konfirmasi</span>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($pembayaran as $b): ?>
    <div class="card" style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: #fff;">
      <div class="card-header" style="background:#fcf8f6; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <strong style="font-family:monospace;color:var(--bark); font-size:15px;"><?= $b['no_order'] ?></strong>
          <span style="font-size:11px;font-weight:700;padding:3px 12px;border-radius:100px; background:<?= $jenis_bg[$b['jenis']] ?? '#eee' ?>; color:<?= $jenis_color[$b['jenis']] ?? '#333' ?>; border: 1px solid rgba(0,0,0,0.05);">
            <?= $jenis_label[$b['jenis']] ?? strtoupper($b['jenis']) ?>
          </span>
          <span style="font-size:12px;color:var(--muted);">&#128197; <?= $b['tgl_bayar'] ?></span>
        </div>
        <span style="font-size:12px;background:#fff8e1;color:#7d5a00;padding:3px 10px;border-radius:100px;font-weight:600;">
          &#128336; <?= htmlspecialchars(ucfirst($b['status'])) ?>
        </span>
      </div>

      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 220px;gap:20px;align-items:start; padding: 20px;">
        
        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Info Pembayaran</h4>
          <table style="font-size:13px;width:100%;">
            <tr><td style="color:var(--muted);padding-bottom:6px;width:100px;">Nama</td><td style="font-weight:500;"><?= htmlspecialchars($b['nama_user']) ?></td></tr>
            <tr><td style="color:var(--muted);padding-bottom:6px;">Metode</td><td><?= htmlspecialchars($b['metode']) ?></td></tr>
            <tr>
                <td style="color:var(--muted);padding-bottom:6px;">Nominal Transfer</td>
                <td style="font-weight:700;color:var(--rose);font-size:15px;">
                    <?= formatRupiah($b['nominal']) ?> 
                </td>
            </tr>
            <tr><td style="color:var(--muted);padding-bottom:6px;">Total Order</td><td style="font-weight:600; color:#444;"><?= formatRupiah($b['total_order']) ?></td></tr>
          </table>
          <div style="margin-top:14px; border-top: 1px dashed #eee; padding-top: 10px;">
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px; font-weight: 600;">Item Pesanan:</div>
            <?php foreach ($b['items'] as $item): ?>
            <div style="font-size:13px;color:var(--bark); margin-bottom: 2px;">&#127800; <?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Bukti Pembayaran</h4>
          <?php if (!empty($b['bukti_raw']) && (file_exists($b['bukti']) || !empty($b['bukti']))): ?>
            <img src="<?= htmlspecialchars($b['bukti']) ?>" style="width:100%;max-width:180px;max-height:220px;object-fit:cover;border-radius:8px;border:1px solid var(--border);cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onclick="openBukti('<?= htmlspecialchars($b['bukti']) ?>')" alt="Bukti Transfer" onerror="this.onerror=null; this.nextElementSibling.style.display='none'; this.src='../assets/uploads/bukti/default.png';">
            <div style="font-size:12px;color:var(--rose);margin-top:6px;cursor:pointer; font-weight: 600;" onclick="openBukti('<?= htmlspecialchars($b['bukti']) ?>')">&#128269; Lihat Ukuran Penuh</div>
          <?php else: ?>
            <div style="width:100%;max-width:180px;height:120px;background:#fafafa; border:2px dashed var(--border);border-radius:8px; display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;">
              <span style="font-size:28px;opacity:.4;">&#128247;</span>
              <span style="font-size:11px;color:var(--muted); text-align:center; padding:0 8px; line-height: 1.3;"><?= (in_array(strtolower($b['metode']), ['bayar_ditempat', 'cash di toko', 'cod'])) ? 'COD / Cash — Tanpa Bukti' : 'Bukti belum diunggah / file tidak ditemukan' ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Tindakan</h4>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <button type="button" class="btn-aksi" style="background:#256d3f;" onclick="bukaModalProses('<?= $b['id_pesanan'] ?>', '<?= $b['id_transaksi'] ?>', '<?= $b['no_order'] ?>')">
              &#10003; Terima Pembayaran
            </button>
            <button type="button" class="btn-aksi" style="background:#9b2020;" onclick="bukaModalTolak('<?= $b['id_pesanan'] ?>', '<?= $b['id_transaksi'] ?>', '<?= $b['no_order'] ?>')">
              &#10006; Tolak Pembayaran
            </button>
          </div>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($pembayaran)): ?>
  <div style="text-align:center;padding:60px 0;">
    <div style="font-size:56px;margin-bottom:14px;color:#256d3f;">&#10003;</div>
    <p style="color:var(--muted); font-weight:500;">Tidak ada pembayaran yang menunggu konfirmasi.</p>
  </div>
  <?php endif; ?>
</div>

<form id="form-submisi-utama" method="POST" action="konfirmasi-pembayaran.php" style="display:none;">
  <input type="hidden" name="action" id="main-action">
  <input type="hidden" name="id_bayar" id="main-id-bayar">
  <input type="hidden" name="id_transaksi" id="main-id-transaksi">
  <input type="hidden" name="no_order" id="main-no-order">
  <input type="hidden" name="catatan_admin" id="main-catatan-admin">
</form>

<div id="modal-proses" class="popup-overlay">
  <div class="popup-box">
    <div class="popup-header">
      <h3>Konfirmasi Setujui Pembayaran</h3>
      <button type="button" class="popup-close" onclick="tutupModal('modal-proses')">&#10005;</button>
    </div>
    <div class="popup-body">
      <p style="font-size:14px; margin-bottom:12px;">Anda akan menyetujui pembayaran untuk pesanan <strong id="proses-label-order" style="color:var(--rose);"></strong>. Status pesanan otomatis menjadi <strong>Diproses</strong>.</p>
      <div class="form-group">
        <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Catatan Admin (Opsional)</label>
        <textarea id="proses-catatan-input" class="popup-textarea" placeholder="Contoh: Pembayaran lunas diterima, pesanan segera dibuat..."></textarea>
      </div>
    </div>
    <div class="popup-footer">
      <button type="button" class="btn-batal" onclick="tutupModal('modal-proses')">Batal</button>
      <button type="button" class="btn-submit" style="background:#256d3f;" onclick="mintaYakin('konfirmasi')">Setujui &amp; Proses</button>
    </div>
  </div>
</div>

<div id="modal-tolak" class="popup-overlay">
  <div class="popup-box">
    <div class="popup-header">
      <h3 style="color:#9b2020;">Alasan Penolakan Pembayaran</h3>
      <button type="button" class="popup-close" onclick="tutupModal('modal-tolak')">&#10005;</button>
    </div>
    <div class="popup-body">
      <p style="font-size:14px; margin-bottom:12px;">Bukti pembayaran pesanan <strong id="tolak-label-order" style="color:#9b2020;"></strong> akan ditolak.</p>
      <div class="form-group">
        <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px; color:#333;">Tulis Alasan Penolakan (Wajib untuk Informasi User)</label>
        <textarea id="tolak-alasan-input" class="popup-textarea" style="border-color:#cc8888;" placeholder="Contoh: Bukti transfer buram, mohon lakukan order ulang..." oninput="hilangkanError()"></textarea>
        
        <div id="error-catatan-wajib" class="error-msg-box">
          <span style="font-size:16px; margin-right:4px;">⚠️</span> Alasan penolakan wajib diisi agar pelanggan tahu kendala pembayaran mereka!
        </div>
      </div>
    </div>
    <div class="popup-footer">
      <button type="button" class="btn-batal" onclick="tutupModal('modal-tolak')">Batal</button>
      <button type="button" class="btn-submit" style="background:#9b2020;" onclick="mintaYakin('tolak')">Konfirmasi Tolak</button>
    </div>
  </div>
</div>

<div id="modal-yakin" class="popup-overlay" style="background: rgba(0,0,0,0.6); z-index: 10000;">
  <div class="popup-box" style="max-width: 380px; text-align: center;">
    <div class="popup-body" style="padding: 30px 20px 20px;">
      <div id="yakin-icon" style="font-size: 50px; margin-bottom: 15px;">⚠️</div>
      <h3 id="yakin-title" style="margin: 0 0 10px; font-size: 18px; color:#333;">Apakah Anda Yakin?</h3>
      <p id="yakin-text" style="font-size: 13px; color: #666; margin: 0 0 20px; line-height: 1.5;"></p>
      
      <div style="display: flex; gap: 10px; justify-content: center;">
        <button type="button" class="btn-batal" style="width: 100px;" onclick="tutupModal('modal-yakin')">Tidak</button>
        <button type="button" id="yakin-submit-btn" class="btn-submit" style="width: 130px;" onclick="eksekusiFormFinal()">Ya, Eksekusi!</button>
      </div>
    </div>
  </div>
</div>

<div id="modal-bukti" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:99999;align-items:center;justify-content:center;flex-direction:column;" onclick="this.style.display='none'">
  <div style="position:absolute;top:20px;right:20px;color:#fff;font-size:30px;cursor:pointer;font-weight:bold;" onclick="tutupModal('modal-bukti')">&times;</div>
  <img id="bukti-full" src="" style="max-width:90vw;max-height:80vh;border-radius:8px;box-shadow:0 5px 25px rgba(0,0,0,0.8);border:3px solid #fff;object-fit:contain;">
  <div style="color:#fff;margin-top:15px;font-size:14px;background:rgba(0,0,0,0.6);padding:6px 16px;border-radius:20px;">Klik di mana saja untuk menutup gambar</div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
let currentIdPesanan = '';
let currentIdTransaksi = '';
let currentNoOrder = '';
let currentAction = '';
let currentCatatan = '';

function openBukti(src) {
  document.getElementById('bukti-full').src = src;
  document.getElementById('modal-bukti').style.display = 'flex';
}

function bukaModalProses(idPesanan, idTransaksi, noOrder) {
  currentIdPesanan = idPesanan;
  currentIdTransaksi = idTransaksi;
  currentNoOrder = noOrder;
  document.getElementById('proses-label-order').textContent = noOrder;
  document.getElementById('proses-catatan-input').value = '';
  document.getElementById('modal-proses').style.display = 'flex';
}

function bukaModalTolak(idPesanan, idTransaksi, noOrder) {
  currentIdPesanan = idPesanan;
  currentIdTransaksi = idTransaksi;
  currentNoOrder = noOrder;
  document.getElementById('tolak-label-order').textContent = noOrder;
  hilangkanError(); 
  document.getElementById('tolak-alasan-input').value = '';
  document.getElementById('modal-tolak').style.display = 'flex';
}

function tutupModal(id) {
  document.getElementById(id).style.display = 'none';
}

function hilangkanError() {
  const inputEl = document.getElementById('tolak-alasan-input');
  const errorEl = document.getElementById('error-catatan-wajib');
  if(inputEl) inputEl.classList.remove('input-error-shake');
  if(errorEl) errorEl.classList.remove('show');
}

function mintaYakin(actionType) {
  currentAction = actionType;
  
  if (actionType === 'konfirmasi') {
    currentCatatan = document.getElementById('proses-catatan-input').value.trim();
    
    document.getElementById('yakin-icon').textContent = '💡';
    document.getElementById('yakin-title').textContent = 'Setujui Pembayaran?';
    document.getElementById('yakin-text').textContent = 'Anda yakin ingin menerima pembayaran untuk ' + currentNoOrder + '? Data pesanan akan segera diproses.';
    document.getElementById('yakin-submit-btn').style.background = '#256d3f';
    
  } else if (actionType === 'tolak') {
    currentCatatan = document.getElementById('tolak-alasan-input').value.trim();
    
    if (currentCatatan === '') {
      const inputEl = document.getElementById('tolak-alasan-input');
      const errorEl = document.getElementById('error-catatan-wajib');
      
      inputEl.classList.remove('input-error-shake');
      void inputEl.offsetWidth; 
      inputEl.classList.add('input-error-shake');
      
      errorEl.classList.add('show');
      return;
    }
    
    document.getElementById('yakin-icon').textContent = '🛑';
    document.getElementById('yakin-title').textContent = 'Tolak Pembayaran?';
    document.getElementById('yakin-text').textContent = 'Tindakan ini akan menolak bukti transfer ' + currentNoOrder + '. Jika ini transaksi pelunasan, pesanan tidak dibatalkan agar pelanggan bisa upload ulang.';
    document.getElementById('yakin-submit-btn').style.background = '#9b2020';
  }
  
  document.getElementById('modal-yakin').style.display = 'flex';
}

function eksekusiFormFinal() {
  document.getElementById('main-action').value = currentAction;
  document.getElementById('main-id-bayar').value = currentIdPesanan;
  document.getElementById('main-id-transaksi').value = currentIdTransaksi;
  document.getElementById('main-no-order').value = currentNoOrder;
  document.getElementById('main-catatan-admin').value = currentCatatan;
  document.getElementById('form-submisi-utama').submit();
}
</script>

<style>
.popup-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center; }
.popup-box { background: #fff; width: 100%; max-width: 450px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); overflow: hidden; animation: popupShow 0.2s ease-out; }
@keyframes popupShow { from { transform: scale(0.92); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.popup-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.popup-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #333; }
.popup-close { background: none; border: none; font-size: 18px; cursor: pointer; color: #aaa; }
.popup-close:hover { color: #333; }
.popup-body { padding: 20px; color: #555; text-align: left; }
.popup-textarea { width: 100%; height: 80px; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 13px; resize: none; outline: none; margin-top: 5px; transition: all 0.2s; }
.popup-textarea:focus { border-color: var(--rose); box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.1); }
.popup-footer { padding: 12px 20px; background: #fafafa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
.btn-aksi { color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; width: 100%; text-align: center; display: inline-block; text-decoration: none; transition: background 0.2s; }
.btn-aksi:hover { opacity: 0.9; }
.btn-batal { background: white; border: 1px solid #ccc; padding: 8px 16px; border-radius: 6px; cursor: pointer; color: #333; font-weight: 500; font-size: 13px; }
.btn-batal:hover { background: #f5f5f5; }
.btn-submit { color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; }
.error-msg-box { max-height: 0; opacity: 0; overflow: hidden; background: #fff0f0; color: #c92a2a; border-left: 4px solid #e03131; padding: 0 10px; margin-top: 8px; font-size: 12px; line-height: 1.4; border-radius: 4px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.error-msg-box.show { max-height: 60px; opacity: 1; padding: 8px 10px; }
.input-error-shake { border-color: #e03131 !important; background-color: #fff5f5; animation: shakeEffect 0.4s ease-in-out; }
@keyframes shakeEffect { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
</style>
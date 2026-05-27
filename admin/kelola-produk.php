<?php
// admin/kelola-produk.php — Kelola Produk

$page_title  = 'Kelola Produk — Admin MayFlorist';
$active_menu = 'produk';

include 'includes/header.php';
include '../koneksi.php';

// =========================
// HANDLE POST
// =========================

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = $_POST['action'] ?? '';

  // =========================
  // TAMBAH PRODUK
  // =========================
  if ($action === 'tambah') {

    $nama       = trim($_POST['name']);
    $harga      = (int) $_POST['price'];
    $stok       = (int) $_POST['stok'];
    $kategori   = (int) $_POST['kategori'];
    $deskripsi  = trim($_POST['desc']);
    $rating     = 5.0;

    $gambar = '';

    if (!empty($_FILES['foto']['name'])) {
      $gambar = time() . '-' . basename($_FILES['foto']['name']);
      move_uploaded_file($_FILES['foto']['tmp_name'], '../assets/images/' . $gambar);
    }

    mysqli_query($conn, "
      INSERT INTO produk (id_kategori, nama_produk, harga_produk, stok_produk, rating, deskripsi_produk, gambar_produk)
      VALUES ('$kategori', '$nama', '$harga', '$stok', '$rating', '$deskripsi', '$gambar')
    ");

    $alert = '<div class="alert alert-success">✓ Produk <strong>' . htmlspecialchars($nama) . '</strong> berhasil ditambahkan!</div>';
  }

  // =========================
  // EDIT PRODUK
  // =========================
  elseif ($action === 'edit') {

    $id         = (int) $_POST['id'];
    $nama       = trim($_POST['name']);
    $harga      = (int) $_POST['price'];
    $stok       = (int) $_POST['stok'];
    $deskripsi  = trim($_POST['desc']);
    $kategori   = (int) $_POST['kategori'];

    if ($kategori <= 0) {
        $alert = '<div class="alert alert-danger">⚠ Kategori tidak valid!</div>';
    } else {
        $update = "
          UPDATE produk
          SET
            id_kategori = $kategori,  
            nama_produk = '" . mysqli_real_escape_string($conn, $nama) . "',
            harga_produk = $harga,
            stok_produk = $stok,
            deskripsi_produk = '" . mysqli_real_escape_string($conn, $deskripsi) . "'
        ";

        if (!empty($_FILES['foto']['name'])) {
            $gambar = time() . '-' . basename($_FILES['foto']['name']);
            move_uploaded_file($_FILES['foto']['tmp_name'], '../assets/images/' . $gambar);
            $update .= ", gambar_produk = '$gambar'";
        }

        $update .= " WHERE id_produk = $id";

        if (mysqli_query($conn, $update)) {
            $alert = '<div class="alert alert-success">✓ Produk <strong>' . htmlspecialchars($nama) . '</strong> berhasil diperbarui!</div>';
        } else {
            $alert = '<div class="alert alert-danger">⚠ Gagal memperbarui: ' . mysqli_error($conn) . '</div>';
        }
    }
  }

  // =========================
  // HAPUS PRODUK
  // =========================
  elseif ($action === 'hapus') {
    $id = (int) $_POST['id'];
    mysqli_query($conn, "DELETE FROM produk WHERE id_produk='$id'");
    $alert = '<div class="alert alert-danger">✕ Produk berhasil dihapus secara permanen.</div>';
  }

  // =========================
  // TAMBAH KATEGORI
  // =========================
  elseif ($action === 'tambah_kategori') {
    $namaKategori = trim($_POST['nama_kategori']);
    mysqli_query($conn, "INSERT INTO kategori (nama_kategori) VALUES ('$namaKategori')");
    $alert = '<div class="alert alert-success">✓ Kategori <strong>' . htmlspecialchars($namaKategori) . '</strong> berhasil ditambahkan!</div>';
  }

  // =========================
  // HAPUS KATEGORI
  // =========================
  elseif ($action === 'hapus_kategori') {
    $idKategori = (int) $_POST['id_kategori'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id_kategori='$idKategori'");
    $alert = '<div class="alert alert-danger">✕ Kategori berhasil dihapus.</div>';
  }
}

// =========================
// AMBIL DATA PRODUK
// =========================

$q = trim($_GET['q'] ?? '');
$where = $q ? "WHERE nama_produk LIKE '%$q%'" : '';

// Pagination configuration
$per_page = 6;
$current  = max(1, (int)($_GET['page'] ?? 1));
$start    = ($current - 1) * $per_page;

$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM produk $where");
$totalData  = mysqli_fetch_assoc($totalQuery);
$total      = $totalData['total'];
$pages      = max(1, ceil($total / $per_page));

// Query Utama: Stok Kosong (stok_produk = 0) ditaruh paling atas secara otomatis
$query = mysqli_query($conn, "
  SELECT 
    produk.*,
    kategori.nama_kategori,
    COALESCE(SUM(detail_pesanan.jumlah_produk), 0) AS total_terjual
  FROM produk
  LEFT JOIN kategori
    ON produk.id_kategori = kategori.id_kategori
  LEFT JOIN detail_pesanan
    ON produk.id_produk = detail_pesanan.id_produk
  LEFT JOIN pesanan
    ON detail_pesanan.id_pesanan = pesanan.id_pesanan AND pesanan.status_pesanan = 'selesai'
  $where
  GROUP BY produk.id_produk
  ORDER BY (produk.stok_produk = 0) DESC, produk.id_produk DESC
  LIMIT $start, $per_page
");

$paged = [];
while ($row = mysqli_fetch_assoc($query)) {
  $paged[] = $row;
}

function formatRupiah($n) {
  return 'Rp ' . number_format($n, 0, ',', '.');
}
?>

<style>
  :root {
    --bg-surface: #f8fafc;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    --radius-md: 12px;
    --radius-lg: 16px;
    --primary-color: #4f46e5;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --success-color: #10b981;
  }

  .page-body {
    padding: 24px;
    background-color: var(--bg-surface);
    font-family: 'Inter', sans-serif;
  }

  .alert {
    padding: 14px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    font-weight: 500;
    box-shadow: var(--card-shadow);
    animation: slideDown 0.3s ease;
  }
  .alert-success { background: #ecfdf5; color: #065f46; border-left: 5px solid var(--success-color); }
  .alert-danger { background: #fef2f2; color: #991b1b; border-left: 5px solid var(--danger-color); }

  .card {
    background: #ffffff;
    border-radius: var(--radius-lg);
    box-shadow: var(--card-shadow);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 28px;
  }

  .card-header {
    padding: 20px 24px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
  }

  .data-table th {
    background: #f8fafc;
    padding: 16px 20px;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #edf2f7;
  }

  .data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 14px;
    vertical-align: middle;
    transition: background 0.2s ease;
  }

  .data-table tr:hover td {
    background: #f8fafc;
  }

  /* Baris khusus untuk stok kosong */
  .row-out-of-stock td {
    background: #fff5f5;
  }
  .row-out-of-stock:hover td {
    background: #fff0f0;
  }

  .badge-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
  }
  .badge-danger { background: #fee2e2; color: #991b1b; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .badge-success { background: #d1fae5; color: #065f46; }

  /* Modals Layout & Animation */
  .modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .modal-overlay.open {
    opacity: 1;
    visibility: visible;
  }

  .modal {
    background: #ffffff;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 550px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    transform: scale(0.95) translateY(-20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
  }

  .modal-overlay.open .modal {
    transform: scale(1) translateY(0);
  }

  .form-control {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 14px;
    transition: all 0.2s ease;
    box-sizing: border-box;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }
  .btn-primary { background: var(--primary-color); color: white; }
  .btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
  .btn-danger { background: var(--danger-color); color: white; }
  .btn-danger:hover { background: #dc2626; }
  .btn-outline { background: transparent; border-color: #cbd5e1; color: #475569; }
  .btn-outline:hover { background: #f1f5f9; }
  .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 6px; }

  @keyframes slideDown {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
</style>

<div class="page-body">

  <?= $alert ?>

  <div class="card">
    <div class="card-header">
      <h1 style="font-size:22px; font-weight:700; color:#1e293b; margin:0;">Kelola Inventory Produk</h1>
      <button class="btn btn-primary" onclick="openModal('modal-tambah')">
         <span style="margin-right:6px; font-size:16px;">+</span> Tambah Produk Baru
      </button>
    </div>

    <div style="padding:16px 24px; background:#fafafa; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
      <form method="GET" style="display:flex; gap:10px; flex:1; max-width:450px;">
        <input type="text" name="q" class="form-control" placeholder="Cari nama bouquet atau bunga..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0 20px;">Cari</button>
        <?php if ($q): ?>
          <a href="kelola-produk.php" class="btn btn-outline btn-sm">✕ Reset</a>
        <?php endif; ?>
      </form>
      <span style="font-size:14px; font-weight:500; color:#64748b; background:#f1f5f9; padding:6px 14px; border-radius:100px;">
        Total: <strong><?= $total ?></strong> Produk Terdata
      </span>
    </div>

    <div style="overflow-x:auto;">
      <?php if (empty($paged)): ?>
        <div style="padding:64px; text-align:center; color:#94a3b8;">
          <div style="font-size:56px; margin-bottom:16px;">🌸</div>
          <p style="font-size:16px; font-weight:500; margin:0;">Tidak ada produk yang cocok dengan pencarian Anda.</p>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:60px; text-align:center;">No</th>
            <th style="width:100px; text-align:center;">Gambar</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga Unit</th>
            <th style="width:140px;">Status Stok</th>
            <th style="text-align:center;">Total Terjual</th>
            <th style="width:160px; text-align:center;">Aksi Manajerial</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($paged as $i => $p): 
          // Penentuan kelas baris & badge jika stok habis
          $is_out = ($p['stok_produk'] == 0);
          $row_class = $is_out ? 'class="row-out-of-stock"' : '';
          
          if ($is_out) {
            $stock_badge = '<span class="badge-status badge-danger">● Habis (0)</span>';
          } elseif ($p['stok_produk'] <= 5) {
            $stock_badge = '<span class="badge-status badge-warning">● Tipis (' . $p['stok_produk'] . ')</span>';
          } else {
            $stock_badge = '<span class="badge-status badge-success">✓ Aman (' . $p['stok_produk'] . ')</span>';
          }
        ?>
          <tr <?= $row_class ?>>
            <td style="text-align:center; color:#94a3b8; font-weight:600;">
              <?= ($current-1)*$per_page + $i + 1 ?>
            </td>
            <td style="text-align:center;">
              <img src="../assets/images/<?= htmlspecialchars($p['gambar_produk']) ?>" 
                   alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                   style="width:52px; height:52px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0; display:inline-block; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
            </td>
            <td>
              <div style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($p['nama_produk']) ?></div>
              <span style="font-size:11px; color:#94a3b8;">ID: #PROD-<?= $p['id_produk'] ?></span>
            </td>
            <td>
              <span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:500;">
                <?= htmlspecialchars($p['nama_kategori'] ?? 'Tanpa Kategori') ?>
              </span>
            </td>
            <td style="font-weight:600; color:#0f172a;">
              <?= formatRupiah($p['harga_produk']) ?>
            </td>
            <td>
              <?= $stock_badge ?>
            </td>
            <td style="text-align:center; font-weight:700; color:#10b981;">
              <?= (int)$p['total_terjual'] ?> pcs
            </td>
            <td>
              <div style="display:flex; gap:8px; justify-content:center;">
                <button class="btn btn-outline btn-sm" style="padding:6px 12px; color:#4f46e5; border-color:#e0e7ff; background:#f5f7ff;"
                        onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                  Edit
                </button>
                <button class="btn btn-danger btn-sm" style="padding:6px 12px;"
                        onclick="confirmHapus(<?= $p['id_produk'] ?>, '<?= htmlspecialchars(addslashes($p['nama_produk'])) ?>')">
                  Hapus
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
    <div style="padding:16px 24px; background:#ffffff; border-top:1px solid #f1f5f9; display:flex; justify-content:center;">
      <nav style="display:inline-flex; gap:6px;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="?<?= http_build_query(['q'=>$q,'page'=>$i]) ?>"
             style="text-decoration:none; padding:8px 14px; border-radius:8px; font-size:14px; font-weight:600; transition:all 0.2s;
                    <?= $i === $current ? 'background:var(--primary-color); color:white;' : 'background:#f1f5f9; color:#475569;' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </nav>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h1 style="font-size:18px; font-weight:700; color:#1e293b; margin:0;">Kelola Kategori Florist</h1>
    </div>
    <div style="padding:20px 24px; background:#fafafa; border-bottom:1px solid #f1f5f9;">
      <form method="POST" style="display:flex; gap:12px; max-width:500px;">
        <input type="hidden" name="action" value="tambah_kategori">
        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Bouquet Mawar, Wedding Setup..." required>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;">+ Tambah Kategori</button>
      </form>
    </div>
    <div style="padding:0; overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:80px;">No</th>
            <th>Nama Kategori Berjalan</th>
            <th style="width:160px; text-align:center;">Tindakan</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $kategoriQuery = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id_kategori DESC");
        $no = 1;
        while($k = mysqli_fetch_assoc($kategoriQuery)):
        ?>
          <tr>
            <td style="color:#94a3b8; font-weight:600;"><?= $no++ ?></td>
            <td style="font-weight:600; color:#334155;"><?= htmlspecialchars($k['nama_kategori']) ?></td>
            <td style="text-align:center;">
              <button class="btn btn-danger btn-sm" style="padding:4px 12px;"
                      onclick="confirmHapusKategori(<?= $k['id_kategori'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')">
                Hapus
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-tambah">
  <div class="modal">
    <div class="card-header" style="background:#f8fafc;">
      <h3 style="margin:0; font-weight:700; color:#0f172a;">Tambah Produk Baru</h3>
      <button onclick="closeModal('modal-tambah')" style="background:none; border:none; font-size:20px; cursor:pointer; color:#94a3b8;">✕</button>
    </div>
    <form method="POST" action="kelola-produk.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="tambah">
      <div style="padding:24px; display:flex; flex-direction:column; gap:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Nama Produk / Bouquet</label>
          <input type="text" name="name" class="form-control" placeholder="Masukkan nama item..." required>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Harga Jual (Rp)</label>
            <input type="number" name="price" class="form-control" placeholder="Contoh: 150000" required>
          </div>
          <div>
            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Jumlah Unit (Stok)</label>
            <input type="number" name="stok" class="form-control" placeholder="Contoh: 25" required>
          </div>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Kategori Klasifikasi</label>
          <?php $qKategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC"); ?>
          <select name="kategori" class="form-control">
            <?php while($k = mysqli_fetch_assoc($qKategori)): ?>
              <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Deskripsi Item</label>
          <textarea name="desc" class="form-control" rows="3" placeholder="Tuliskan spesifikasi produk bunga..."></textarea>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Foto Produk Unggulan</label>
          <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
      </div>
      <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:12px;">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-tambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Ke Database</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="card-header" style="background:#f8fafc;">
      <h3 style="margin:0; font-weight:700; color:#0f172a;">Perbarui Informasi Produk</h3>
      <button onclick="closeModal('modal-edit')" style="background:none; border:none; font-size:20px; cursor:pointer; color:#94a3b8;">✕</button>
    </div>
    <form method="POST" action="kelola-produk.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div style="padding:24px; display:flex; flex-direction:column; gap:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Nama Produk</label>
          <input type="text" name="name" id="edit-name" class="form-control" required>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div>
            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Harga Unit (Rp)</label>
            <input type="number" name="price" id="edit-price" class="form-control" required>
          </div>
          <div>
            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Stok Sistem Saat Ini</label>
            <input type="number" name="stok" id="edit-stok" class="form-control" required>
          </div>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Kategori Berjalan</label>
          <?php $qKatEdit = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC"); ?>
          <select name="kategori" id="edit-kategori" class="form-control">
            <?php while($k = mysqli_fetch_assoc($qKatEdit)): ?>
              <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Deskripsi Detil</label>
          <textarea name="desc" id="edit-desc" class="form-control" rows="3"></textarea>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Ganti Foto (Biarkan kosong jika tetap)</label>
          <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
      </div>
      <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:12px;">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-hapus">
  <div class="modal" style="max-width:400px;">
    <div style="padding:24px; text-align:center;">
      <div style="font-size:48px; color:var(--danger-color); margin-bottom:12px;">⚠</div>
      <h3 style="margin:0 0 8px 0; font-weight:700; color:#0f172a;">Hapus Item Permanen?</h3>
      <p style="color:#64748b; font-size:14px; margin-bottom:24px; line-height:1.5;">
        Apakah Anda yakin ingin melenyapkan produk <strong id="hapus-name" style="color:#0f172a;"></strong> dari katalog MayFlorist? Data yang hilang tidak bisa dikembalikan.
      </p>
      <form method="POST" action="kelola-produk.php">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="id" id="hapus-id">
        <div style="display:flex; gap:12px;">
          <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeModal('modal-hapus')">Batal</button>
          <button type="submit" class="btn btn-danger" style="flex:1;">Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-hapus-kategori">
  <div class="modal" style="max-width:400px;">
    <div style="padding:24px; text-align:center;">
      <div style="font-size:48px; color:var(--danger-color); margin-bottom:12px;">⚠</div>
      <h3 style="margin:0 0 8px 0; font-weight:700; color:#0f172a;">Hapus Kelompok Kategori?</h3>
      <p style="color:#64748b; font-size:14px; margin-bottom:24px; line-height:1.5;">
        Anda akan menghapus kelompok kategori <strong id="hapus-kategori-name" style="color:#0f172a;"></strong>. Pastikan tidak ada produk aktif yang memakai kategori ini.
      </p>
      <form method="POST" action="kelola-produk.php">
        <input type="hidden" name="action" value="hapus_kategori">
        <input type="hidden" name="id_kategori" id="hapus-kategori-id">
        <div style="display:flex; gap:12px;">
          <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeModal('modal-hapus-kategori')">Batal</button>
          <button type="submit" class="btn btn-danger" style="flex:1;">Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function openModal(id) {
  const overlay = document.getElementById(id);
  overlay.classList.add('open');
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  overlay.classList.remove('open');
}

function openEditModal(p) {
  document.getElementById('edit-id').value = p.id_produk;
  document.getElementById('edit-name').value = p.nama_produk;
  document.getElementById('edit-price').value = p.harga_produk;
  document.getElementById('edit-stok').value = p.stok_produk;
  document.getElementById('edit-desc').value = p.deskripsi_produk; 
  document.getElementById('edit-kategori').value = p.id_kategori;
  openModal('modal-edit');
}

function confirmHapus(id, name) {
  document.getElementById('hapus-id').value = id;
  document.getElementById('hapus-name').textContent = name;
  openModal('modal-hapus');
}

function confirmHapusKategori(id, name) {
  document.getElementById('hapus-kategori-id').value = id;
  document.getElementById('hapus-kategori-name').textContent = name;
  openModal('modal-hapus-kategori');
}

// Tutup modal secara natural ketika mengklik area luar jendela modal (Backdrop Click)
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => {
    if (e.target === el) {
      el.classList.remove('open');
    }
  });
});
</script>
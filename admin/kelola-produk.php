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

      move_uploaded_file(
        $_FILES['foto']['tmp_name'],
        '../assets/images/' . $gambar
      );
    }

    mysqli_query($conn, "
      INSERT INTO produk (
        id_kategori,
        nama_produk,
        harga_produk,
        stok_produk,
        rating,
        deskripsi_produk,
        gambar_produk
      )
      VALUES (
        '$kategori',
        '$nama',
        '$harga',
        '$stok',
        '$rating',
        '$deskripsi',
        '$gambar'
      )
    ");

    $alert = '
    <div class="alert alert-success">
      &#10003; Produk berhasil ditambahkan!
    </div>';
  }

  // =========================
  // EDIT PRODUK
  // =========================
  elseif ($action === 'edit') {

    $id         = (int) $_POST['id'];
    $nama       = trim($_POST['name']);
    $harga      = (int) $_POST['price'];
    $stok       = (int) $_POST['stok'];

    $update = "
      UPDATE produk
      SET
        nama_produk='$nama',
        harga_produk='$harga',
        stok_produk='$stok'
    ";

    if (!empty($_FILES['foto']['name'])) {

      $gambar = time() . '-' . basename($_FILES['foto']['name']);

      move_uploaded_file(
        $_FILES['foto']['tmp_name'],
        '../assets/images/' . $gambar
      );

      $update .= ", gambar_produk='$gambar'";
    }

    $update .= " WHERE id_produk='$id'";

    mysqli_query($conn, $update);

    $alert = '
    <div class="alert alert-success">
      &#10003; Produk berhasil diperbarui!
    </div>';
  }

  // =========================
  // HAPUS PRODUK
  // =========================
  elseif ($action === 'hapus') {

    $id = (int) $_POST['id'];

    mysqli_query($conn, "
      DELETE FROM produk
      WHERE id_produk='$id'
    ");

    $alert = '
    <div class="alert alert-danger">
      &#10006; Produk berhasil dihapus.
    </div>';
  }

  // =========================
  // TAMBAH KATEGORI
  // =========================
  elseif ($action === 'tambah_kategori') {

    $namaKategori = trim($_POST['nama_kategori']);

    mysqli_query($conn, "
      INSERT INTO kategori (nama_kategori)
      VALUES ('$namaKategori')
    ");

    $alert = '
    <div class="alert alert-success">
      &#10003; Kategori berhasil ditambahkan!
    </div>';
  }

  // =========================
  // HAPUS KATEGORI
  // =========================
  elseif ($action === 'hapus_kategori') {

    $idKategori = (int) $_POST['id_kategori'];

    mysqli_query($conn, "
      DELETE FROM kategori
      WHERE id_kategori='$idKategori'
    ");

    $alert = '
    <div class="alert alert-danger">
      &#10006; Kategori berhasil dihapus.
    </div>';
  }
}

// =========================
// AMBIL DATA PRODUK
// =========================

$q = trim($_GET['q'] ?? '');

$where = '';

if ($q) {
  $where = "WHERE nama_produk LIKE '%$q%'";
}

// pagination
$per_page = 6;
$current  = max(1, (int)($_GET['page'] ?? 1));
$start    = ($current - 1) * $per_page;

// total produk
$totalQuery = mysqli_query($conn, "
  SELECT COUNT(*) as total
  FROM produk
  $where
");

$totalData = mysqli_fetch_assoc($totalQuery);

$total = $totalData['total'];

$pages = max(1, ceil($total / $per_page));

// query produk
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
  $where
  GROUP BY produk.id_produk
  ORDER BY produk.id_produk DESC
  LIMIT $start, $per_page
");

$products = [];

while ($row = mysqli_fetch_assoc($query)) {
  $products[] = $row;
}

$paged = $products;

function formatRupiah($n) {
  return 'Rp ' . number_format($n, 0, ',', '.');
}
?>

<div class="page-body">

  <?= $alert ?>

  <!-- ========================= -->
  <!-- KELOLA PRODUK -->
  <!-- ========================= -->

  <div class="card">

    <div class="card-header">
      <h1 style="font-size:20px;">Kelola Produk</h1>

      <button class="btn btn-primary"
              onclick="openModal('modal-tambah')">
        + Tambah Produk
      </button>
    </div>

    <!-- Search -->
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;">

      <form method="GET"
            style="display:flex;gap:8px;flex:1;">

        <input type="text"
               name="q"
               class="form-control"
               placeholder="Cari nama produk..."
               value="<?= htmlspecialchars($q) ?>"
               style="max-width:280px;">

        <button type="submit"
                class="btn btn-primary btn-sm">
          Cari
        </button>

        <?php if ($q): ?>

          <a href="kelola-produk.php"
             class="btn btn-outline btn-sm">
            &#10006; Reset
          </a>

        <?php endif; ?>

      </form>

      <span style="font-size:12px;color:var(--muted);">
        <?= $total ?> produk
      </span>

    </div>

    <!-- TABLE -->
    <div style="overflow-x:auto;">

      <?php if (empty($paged)): ?>

        <div style="padding:48px;text-align:center;color:var(--muted);">

          <div style="font-size:48px;margin-bottom:12px;">
            &#127800;
          </div>

          <p>Tidak ada produk yang cocok.</p>

        </div>

      <?php else: ?>

      <table class="data-table">

        <thead>
          <tr>
            <th style="width:50px;">Id</th>

            <th style="width:80px;text-align:center;">
              Gambar
            </th>

            <th>Nama Produk</th>

            <th>Harga</th>

            <th>Stok</th>

            <th>Terjual</th>

            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>

        <?php foreach ($paged as $i => $p): ?>

          <tr>

            <td style="color:var(--muted);">
              <?= ($current-1)*$per_page + $i + 1 ?>
            </td>

            <td style="width:70px;">

              <img
                src="../assets/images/<?= htmlspecialchars($p['gambar_produk']) ?>"
                alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                style="
                  width:56px;
                  height:56px;
                  object-fit:cover;
                  border-radius:12px;
                  display:block;
                "
              >

            </td>

            <td>

              <strong>
                <?= htmlspecialchars($p['nama_produk']) ?>
              </strong>

            </td>

            <td>

              <?= formatRupiah($p['harga_produk']) ?>

            </td>

            <td>

              <span style="font-weight:600;<?= $p['stok_produk'] <= 5 ? 'color:var(--danger);' : '' ?>">

                <?= $p['stok_produk'] ?>

              </span>
              

            </td>

            <td>
              <span style="font-weight:600;color:#28a745;">
                <?= (int)$p['total_terjual'] ?>
              </span>
            </td>

            <td>

              <div class="td-actions">

                <button class="btn btn-primary btn-sm"
                        onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                  Edit
                </button>

                <button class="btn btn-danger btn-sm"
                        onclick="confirmHapus(
                          <?= $p['id_produk'] ?>,
                          '<?= htmlspecialchars(addslashes($p['nama_produk'])) ?>'
                        )">
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

    <!-- PAGINATION -->
    <?php if ($pages > 1): ?>

    <nav class="pagination">

      <?php for ($i = 1; $i <= $pages; $i++): ?>

        <a href="?<?= http_build_query(['q'=>$q,'page'=>$i]) ?>"
           class="<?= $i === $current ? 'pg-active' : '' ?>">

          <?= $i ?>

        </a>

      <?php endfor; ?>

    </nav>

    <?php endif; ?>

  </div>

  <!-- ========================= -->
  <!-- KELOLA KATEGORI -->
  <!-- ========================= -->

  <div class="card" style="margin-top:20px;">

    <div class="card-header">
      <h1 style="font-size:20px;">Kelola Kategori</h1>
    </div>

    <div style="padding:20px;border-bottom:1px solid var(--border);">

      <form method="POST"
            style="display:flex;gap:10px;">

        <input type="hidden"
               name="action"
               value="tambah_kategori">

        <input type="text"
               name="nama_kategori"
               class="form-control"
               placeholder="Nama kategori..."
               required>

        <button type="submit"
                class="btn btn-primary">
          Tambah
        </button>

      </form>

    </div>

    <div style="padding:20px;overflow-x:auto;">

      <table class="data-table">

        <thead>
          <tr>
            <th>Id</th>
            <th>Nama Kategori</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>

        <?php

        $kategoriQuery = mysqli_query($conn, "
          SELECT *
          FROM kategori
          ORDER BY id_kategori DESC
        ");

        $no = 1;

        while($k = mysqli_fetch_assoc($kategoriQuery)):

        ?>

          <tr>

            <td><?= $no++ ?></td>

            <td>
              <?= htmlspecialchars($k['nama_kategori']) ?>
            </td>

            <td>

              <button
                class="btn btn-danger btn-sm"
                onclick="confirmHapusKategori(
                  <?= $k['id_kategori'] ?>,
                  '<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>'
                )"
              >
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

<!-- ========================= -->
<!-- MODAL TAMBAH -->
<!-- ========================= -->

<div class="modal-overlay" id="modal-tambah">

  <div class="modal">

    <div class="modal-header">

      <h3>Tambah Produk Baru</h3>

      <button class="modal-close"
              onclick="closeModal('modal-tambah')">

        &#10005;

      </button>

    </div>

    <form method="POST"
          action="kelola-produk.php"
          enctype="multipart/form-data">

      <input type="hidden"
             name="action"
             value="tambah">

      <div class="modal-body">

        <div class="form-group">

          <label>Nama Produk</label>

          <input type="text"
                 name="name"
                 class="form-control"
                 placeholder="Nama produk..."
                 required>

        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

          <div class="form-group">

            <label>Harga (Rp)</label>

            <input type="number"
                   name="price"
                   class="form-control"
                   required>

          </div>

          <div class="form-group">

            <label>Stok</label>

            <input type="number"
                   name="stok"
                   class="form-control"
                   required>

          </div>

        </div>

        <div class="form-group">

          <label>Kategori</label>

          <?php
          $qKategori = mysqli_query($conn, "
            SELECT *
            FROM kategori
            ORDER BY nama_kategori ASC
          ");
          ?>

          <select name="kategori"
                  class="form-control">

          <?php while($k = mysqli_fetch_assoc($qKategori)): ?>

            <option value="<?= $k['id_kategori'] ?>">

              <?= htmlspecialchars($k['nama_kategori']) ?>

            </option>

          <?php endwhile; ?>

          </select>

        </div>

        <div class="form-group">

          <label>Deskripsi</label>

          <textarea name="desc"
                    class="form-control"
                    placeholder="Deskripsi produk..."></textarea>

        </div>

        <div class="form-group">

          <label>Foto Produk</label>

          <input type="file"
                 name="foto"
                 class="form-control"
                 accept="image/*">

        </div>

      </div>

      <div class="modal-footer">

        <button type="button"
                class="btn btn-outline"
                onclick="closeModal('modal-tambah')">

          Batal

        </button>

        <button type="submit"
                class="btn btn-primary">

          Simpan Produk

        </button>

      </div>

    </form>

  </div>

</div>

<!-- ========================= -->
<!-- MODAL EDIT -->
<!-- ========================= -->

<div class="modal-overlay" id="modal-edit">

  <div class="modal">

    <div class="modal-header">

      <h3>Edit Produk</h3>

      <button class="modal-close"
              onclick="closeModal('modal-edit')">

        &#10005;

      </button>

    </div>

    <form method="POST"
          action="kelola-produk.php"
          enctype="multipart/form-data">

      <input type="hidden"
             name="action"
             value="edit">

      <input type="hidden"
             name="id"
             id="edit-id">

      <div class="modal-body">

        <div class="form-group">

          <label>Nama Produk</label>

          <input type="text"
                 name="name"
                 id="edit-name"
                 class="form-control"
                 required>

        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

          <div class="form-group">

            <label>Harga (Rp)</label>

            <input type="number"
                   name="price"
                   id="edit-price"
                   class="form-control"
                   required>

          </div>

          <div class="form-group">

            <label>Stok</label>

            <input type="number"
                   name="stok"
                   id="edit-stok"
                   class="form-control"
                   required>

          </div>

        </div>

        <div class="form-group">

          <label>Foto Produk (kosongkan jika tidak diganti)</label>

          <input type="file"
                 name="foto"
                 class="form-control"
                 accept="image/*">

        </div>

      </div>

      <div class="modal-footer">

        <button type="button"
                class="btn btn-outline"
                onclick="closeModal('modal-edit')">

          Batal

        </button>

        <button type="submit"
                class="btn btn-primary">

          Simpan Perubahan

        </button>

      </div>

    </form>

  </div>

</div>

<!-- ========================= -->
<!-- MODAL HAPUS -->
<!-- ========================= -->

<div class="modal-overlay" id="modal-hapus">

  <div class="modal" style="width:360px;">

    <div class="modal-header">

      <h3>Hapus Produk?</h3>

      <button class="modal-close"
              onclick="closeModal('modal-hapus')">

        &#10005;

      </button>

    </div>

    <form method="POST"
          action="kelola-produk.php">

      <input type="hidden"
             name="action"
             value="hapus">

      <input type="hidden"
             name="id"
             id="hapus-id">

      <div class="modal-body">

        <p style="color:var(--muted);font-size:14px;">

          Apakah kamu yakin ingin menghapus produk
          <strong id="hapus-name"
                  style="color:var(--bark);"></strong>?

          Tindakan ini tidak dapat dibatalkan.

        </p>

      </div>

      <div class="modal-footer">

        <button type="button"
                class="btn btn-outline"
                onclick="closeModal('modal-hapus')">

          Batal

        </button>

        <button type="submit"
                class="btn btn-danger">

          Ya, Hapus

        </button>

      </div>

    </form>

  </div>

</div>

<!-- ========================= -->
<!-- MODAL HAPUS KATEGORI -->
<!-- ========================= -->

<div class="modal-overlay" id="modal-hapus-kategori">

  <div class="modal" style="width:360px;">

    <div class="modal-header">

      <h3>Hapus Kategori?</h3>

      <button class="modal-close"
              onclick="closeModal('modal-hapus-kategori')">

        &#10005;

      </button>

    </div>

    <form method="POST"
          action="kelola-produk.php">

      <input type="hidden"
             name="action"
             value="hapus_kategori">

      <input type="hidden"
             name="id_kategori"
             id="hapus-kategori-id">

      <div class="modal-body">

        <p style="color:var(--muted);font-size:14px;">

          Apakah kamu yakin ingin menghapus kategori
          <strong id="hapus-kategori-name"
                  style="color:var(--bark);"></strong>?

          Tindakan ini tidak dapat dibatalkan.

        </p>

      </div>

      <div class="modal-footer">

        <button type="button"
                class="btn btn-outline"
                onclick="closeModal('modal-hapus-kategori')">

          Batal

        </button>

        <button type="submit"
                class="btn btn-danger">

          Ya, Hapus

        </button>

      </div>

    </form>

  </div>

</div>

<?php include 'includes/footer.php'; ?>

<script>

function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

function openEditModal(p) {

  document.getElementById('edit-id').value =
    p.id_produk;

  document.getElementById('edit-name').value =
    p.nama_produk;

  document.getElementById('edit-price').value =
    p.harga_produk;

  document.getElementById('edit-stok').value =
    p.stok_produk;

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

document.querySelectorAll('.modal-overlay').forEach(el => {

  el.addEventListener('click', e => {

    if (e.target === el) {
      el.classList.remove('open');
    }

  });

});

</script>
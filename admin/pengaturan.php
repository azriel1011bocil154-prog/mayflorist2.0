<?php
// admin/pengaturan.php — Pengaturan Akun & Toko

$page_title  = 'Pengaturan — Admin MayFlorist';
$active_menu = 'pengaturan';
include 'includes/header.php';
require '../koneksi.php';

// TAMBAHKAN BARIS INI:
$koneksi = $conn; 

// Simulasi ID Admin yang sedang login (ambil dari session)
$id_admin_login = $_SESSION['id_user'] ?? 3;

// ── 1. AMBIL DATA TERBARU DARI DATABASE ──
// Mengambil data pengaturan toko (Selalu baris ID = 1)
$query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
$data_toko  = mysqli_fetch_assoc($query_toko);

// Mengambil data kata sandi admin dari tabel user
$query_admin = mysqli_query($koneksi, "SELECT password_user FROM user WHERE id_user = '$id_admin_login'");
$data_admin  = mysqli_fetch_assoc($query_admin);


// ── 2. HANDLE PROSES SIMPAN FORM (POST) ──
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['action'] ?? '';

    // A. LOGIKA UBAH KATA SANDI
    if ($aksi === 'ubah_password') {
        $pass_lama    = $_POST['pass_lama'];
        $pass_baru    = $_POST['pass_baru'];
        $pass_konfirm = $_POST['pass_konfirm'];

        // Validasi kata sandi lama sesuai di database (gambar menunjukkan teks polos biasa/tanpa hash)
        if ($pass_lama !== $data_admin['password_user']) {
            $alert = '<div class="alert alert-danger">&#10006; Kata sandi lama salah!</div>';
        } elseif ($pass_baru !== $pass_konfirm) {
            $alert = '<div class="alert alert-danger">&#10006; Konfirmasi kata sandi baru tidak cocok!</div>';
        } else {
            // Update tabel user sesuai struktur punyamu
            $update_pass = mysqli_query($koneksi, "UPDATE user SET password_user = '$pass_baru' WHERE id_user = '$id_admin_login'");
            if ($update_pass) {
                $alert = '<div class="alert alert-success">&#10003; Kata sandi berhasil diubah!</div>';
            }
        }
    }

    // B. LOGIKA SIMPAN INFORMASI TOKO
    if ($aksi === 'simpan_toko') {
        $nama_toko  = $_POST['nama_toko'];
        $whatsapp   = $_POST['nomor_whatsapp'];
        $email_toko = $_POST['email_toko'];
        $alamat     = $_POST['alamat_toko'];
        $jam_ops    = $_POST['jam_operasional'];

        $update_toko = mysqli_query($koneksi, "UPDATE pengaturan_toko SET 
            nama_toko = '$nama_toko', 
            nomor_whatsapp = '$whatsapp', 
            email_toko = '$email_toko', 
            alamat_toko = '$alamat', 
            jam_operasional = '$jam_ops' 
            WHERE id = 1");

        if ($update_toko) {
            $alert = '<div class="alert alert-success">&#10003; Informasi toko berhasil diperbarui!</div>';
            // Refresh data di layar
            $query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
            $data_toko  = mysqli_fetch_assoc($query_toko);
        }
    }

    // C. LOGIKA SIMPAN PENGIRIMAN
    if ($aksi === 'simpan_pengiriman') {
        $ongkir_dasar     = $_POST['ongkir_dasar'];
        $jarak_dasar      = $_POST['jarak_dasar_meter'];
        $biaya_per_meter  = $_POST['biaya_per_meter'];
        $min_belanja      = $_POST['minimal_belanja_gratis_ongkir'];
        $max_jarak_gratis = $_POST['maksimal_jarak_gratis_ongkir'];

        $update_kirim = mysqli_query($koneksi, "UPDATE pengaturan_toko SET 
            ongkir_dasar = '$ongkir_dasar',
            jarak_dasar_meter = '$jarak_dasar',
            biaya_per_meter = '$biaya_per_meter',
            minimal_belanja_gratis_ongkir = '$min_belanja',
            maksimal_jarak_gratis_ongkir = '$max_jarak_gratis'
            WHERE id = 1");

        if ($update_kirim) {
            $alert = '<div class="alert alert-success">&#10003; Pengaturan pengiriman berhasil disimpan!</div>';
            $query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
            $data_toko  = mysqli_fetch_assoc($query_toko);
        }
    }

    // D. LOGIKA SIMPAN METODE PEMBAYARAN
    if ($aksi === 'simpan_pembayaran') {
        $qris     = isset($_POST['pembayaran_qris']) ? 1 : 0;
        $tunai    = isset($_POST['pembayaran_tunai']) ? 1 : 0;
        $transfer = isset($_POST['pembayaran_transfer']) ? 1 : 0;

        $b_nama1  = $_POST['nama_bank_1'];
        $b_rek1   = $_POST['nomor_rekening_1'];
        $b_an1    = $_POST['nama_pemilik_rekening_1'];

        $b_nama2  = $_POST['nama_bank_2'];
        $b_rek2   = $_POST['nomor_rekening_2'];
        $b_an2    = $_POST['nama_pemilik_rekening_2'];

        $b_nama3  = $_POST['nama_bank_3'];
        $b_rek3   = $_POST['nomor_rekening_3'];
        $b_an3    = $_POST['nama_pemilik_rekening_3'];

        $update_bayar = mysqli_query($koneksi, "UPDATE pengaturan_toko SET 
            pembayaran_qris = '$qris',
            pembayaran_tunai = '$tunai',
            pembayaran_transfer = '$transfer',
            nama_bank_1 = '$b_nama1', nomor_rekening_1 = '$b_rek1', nama_pemilik_rekening_1 = '$b_an1',
            nama_bank_2 = '$b_nama2', nomor_rekening_2 = '$b_rek2', nama_pemilik_rekening_2 = '$b_an2',
            nama_bank_3 = '$b_nama3', nomor_rekening_3 = '$b_rek3', nama_pemilik_rekening_3 = '$b_an3'
            WHERE id = 1");

        if ($update_bayar) {
            $alert = '<div class="alert alert-success">&#10003; Metode pembayaran berhasil disimpan!</div>';
            $query_toko = mysqli_query($koneksi, "SELECT * FROM pengaturan_toko WHERE id = 1");
            $data_toko  = mysqli_fetch_assoc($query_toko);
        }
    }
}
?>

<div class="page-body">
  <?= $alert ?>

  <div class="page-header">
    <h1>Pengaturan Sistem</h1>
  </div>

  <div class="pengaturan-layout">

    <div style="display:flex;flex-direction:column;gap:20px;">

      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128274; Ubah Kata Sandi Admin</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="ubah_password">
            <div class="form-group">
              <label>Kata Sandi Lama</label>
              <input type="password" name="pass_lama" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label>Kata Sandi Baru</label>
              <input type="password" name="pass_baru" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label>Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="pass_konfirm" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#127981; Informasi Toko</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_toko">
            <div class="form-group">
              <label>Nama Toko</label>
              <input type="text" name="nama_toko" class="form-control" value="<?= htmlspecialchars($data_toko['nama_toko'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Nomor WhatsApp</label>
              <input type="tel" name="nomor_whatsapp" class="form-control" value="<?= htmlspecialchars($data_toko['nomor_whatsapp'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Email Toko</label>
              <input type="email" name="email_toko" class="form-control" value="<?= htmlspecialchars($data_toko['email_toko'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Alamat Toko</label>
              <textarea name="alamat_toko" class="form-control" rows="2" required><?= htmlspecialchars($data_toko['alamat_toko'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Jam Operasional</label>
              <input type="text" name="jam_operasional" class="form-control" value="<?= htmlspecialchars($data_toko['jam_operasional'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Simpan Informasi</button>
          </form>
        </div>
      </div>

    </div><div style="display:flex;flex-direction:column;gap:20px;">

      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128230; Pengaturan Ongkos Kirim (Jarak)</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_pengiriman">

            <div class="form-group">
              <label>Biaya Ongkir Dasar (Rp)</label>
              <input type="number" name="ongkir_dasar" class="form-control" value="<?= $data_toko['ongkir_dasar'] ?? 0 ?>">
            </div>

            <div class="form-group">
              <label>Batas Jarak Ongkir Dasar (Meter)</label>
              <input type="number" name="jarak_dasar_meter" class="form-control" value="<?= $data_toko['jarak_dasar_meter'] ?? 0 ?>" placeholder="Contoh: 1000 untuk 1 KM">
            </div>

            <div class="form-group">
              <label>Biaya Tambahan Per Meter (Rp)</label>
              <input type="number" name="biaya_per_meter" class="form-control" value="<?= $data_toko['biaya_per_meter'] ?? 0 ?>" placeholder="Contoh: 10 (Sama dengan Rp10.000 / KM)">
            </div>

            <div style="height:1px;background:var(--border);margin:14px 0;"></div>

            <div class="form-group">
              <label>Minimal Total Belanja Gratis Ongkir (Rp)</label>
              <input type="number" name="minimal_belanja_gratis_ongkir" class="form-control" value="<?= $data_toko['minimal_belanja_gratis_ongkir'] ?? 0 ?>">
            </div>

            <div class="form-group">
              <label>Maksimal Jarak Gratis Ongkir (Meter)</label>
              <input type="number" name="maksimal_jarak_gratis_ongkir" class="form-control" value="<?= $data_toko['maksimal_jarak_gratis_ongkir'] ?? 0 ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Simpan Pengiriman</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128179; Metode Pembayaran & Rekening</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_pembayaran">
            
            <div class="form-group">
              <label>Pembayaran Aktif</label>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" name="pembayaran_qris" value="1" <?= ($data_toko['pembayaran_qris'] ?? 0) == 1 ? 'checked' : '' ?>>
                  &#128248; QRIS
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="pembayaran_tunai" value="1" <?= ($data_toko['pembayaran_tunai'] ?? 0) == 1 ? 'checked' : '' ?>>
                  &#128181; Cash / Bayar di Toko
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="pembayaran_transfer" value="1" <?= ($data_toko['pembayaran_transfer'] ?? 0) == 1 ? 'checked' : '' ?>>
                  &#127981; Transfer Bank
                </label>
              </div>
            </div>

            <div style="height:1px;background:var(--border);margin:14px 0;"></div>
            <label style="font-weight: bold; display:block; margin-bottom: 10px;">Daftar Pilihan Rekening Bank</label>

            <div class="form-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
              <input type="text" name="nama_bank_1" class="form-control" style="width: 30%;" placeholder="Nama Bank 1" value="<?= htmlspecialchars($data_toko['nama_bank_1'] ?? '') ?>">
              <input type="text" name="nomor_rekening_1" class="form-control" style="width: 35%;" placeholder="No. Rekening" value="<?= htmlspecialchars($data_toko['nomor_rekening_1'] ?? '') ?>">
              <input type="text" name="nama_pemilik_rekening_1" class="form-control" style="width: 35%;" placeholder="Atas Nama (A.n)" value="<?= htmlspecialchars($data_toko['nama_pemilik_rekening_1'] ?? '') ?>">
            </div>

            <div class="form-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
              <input type="text" name="nama_bank_2" class="form-control" style="width: 30%;" placeholder="Nama Bank 2" value="<?= htmlspecialchars($data_toko['nama_bank_2'] ?? '') ?>">
              <input type="text" name="nomor_rekening_2" class="form-control" style="width: 35%;" placeholder="No. Rekening" value="<?= htmlspecialchars($data_toko['nomor_rekening_2'] ?? '') ?>">
              <input type="text" name="nama_pemilik_rekening_2" class="form-control" style="width: 35%;" placeholder="Atas Nama (A.n)" value="<?= htmlspecialchars($data_toko['nama_pemilik_rekening_2'] ?? '') ?>">
            </div>

            <div class="form-row" style="display: flex; gap: 10px; margin-bottom: 15px;">
              <input type="text" name="nama_bank_3" class="form-control" style="width: 30%;" placeholder="Nama Bank 3" value="<?= htmlspecialchars($data_toko['nama_bank_3'] ?? '') ?>">
              <input type="text" name="nomor_rekening_3" class="form-control" style="width: 35%;" placeholder="No. Rekening" value="<?= htmlspecialchars($data_toko['nomor_rekening_3'] ?? '') ?>">
              <input type="text" name="nama_pemilik_rekening_3" class="form-control" style="width: 35%;" placeholder="Atas Nama (A.n)" value="<?= htmlspecialchars($data_toko['nama_pemilik_rekening_3'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Simpan Pembayaran</button>
          </form>
        </div>
      </div>

    </div></div></div><?php include 'includes/footer.php'; ?>

<style>
.pengaturan-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: start;
}
@media (max-width: 768px) {
  .pengaturan-layout { grid-template-columns: 1fr; }
}
</style>
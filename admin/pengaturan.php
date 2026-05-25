<?php
// admin/pengaturan.php — Pengaturan Akun & Toko

$page_title  = 'Pengaturan — Admin Fleuriste';
$active_menu = 'pengaturan';
include 'includes/header.php';

// ── Handle POST ──
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $alert = match($act) {
        'ubah_password'     => '<div class="alert alert-success">&#10003; Kata sandi berhasil diubah!</div>',
        'simpan_pengiriman' => '<div class="alert alert-success">&#10003; Pengaturan pengiriman berhasil disimpan!</div>',
        'simpan_toko'       => '<div class="alert alert-success">&#10003; Informasi toko berhasil diperbarui!</div>',
        default             => '',
    };
}
?>

<div class="page-body">
  <?= $alert ?>

  <div class="page-header">
    <h1>Pengaturan</h1>
  </div>

  <div class="pengaturan-layout">

    <!-- ── KOLOM KIRI ── -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Ubah Kata Sandi -->
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128274; Ubah Kata Sandi Admin</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="ubah_password">
            <div class="form-group">
              <label>Kata Sandi Lama</label>
              <input type="password" name="pass_lama" class="form-control"
                     placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label>Kata Sandi Baru</label>
              <input type="password" name="pass_baru" class="form-control"
                     placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label>Konfirmasi Kata Sandi Baru</label>
              <input type="password" name="pass_konfirm" class="form-control"
                     placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
          </form>
        </div>
      </div>

      <!-- Informasi Toko -->
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#127981; Informasi Toko</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_toko">
            <div class="form-group">
              <label>Nama Toko</label>
              <input type="text" name="nama_toko" class="form-control"
                     value="Fleuriste" required>
            </div>
            <div class="form-group">
              <label>Nomor WhatsApp</label>
              <input type="tel" name="whatsapp" class="form-control"
                     placeholder="0812-3456-7890" value="0812-3456-7890">
            </div>
            <div class="form-group">
              <label>Email Toko</label>
              <input type="email" name="email" class="form-control"
                     placeholder="hello@fleuriste.id" value="hello@fleuriste.id">
            </div>
            <div class="form-group">
              <label>Alamat Toko</label>
              <textarea name="alamat" class="form-control" rows="2"
                        placeholder="Alamat lengkap toko...">Jakarta Selatan, DKI Jakarta</textarea>
            </div>
            <div class="form-group">
              <label>Jam Operasional</label>
              <input type="text" name="jam_ops" class="form-control"
                     placeholder="08.00 – 22.00 WIB" value="08.00 – 22.00 WIB">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Simpan Informasi</button>
          </form>
        </div>
      </div>

    </div><!-- /kolom kiri -->

    <!-- ── KOLOM KANAN ── -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Pengaturan Pengiriman -->
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128230; Pengaturan Pengiriman</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_pengiriman">

            <div class="form-group">
              <label>Layanan Pengiriman Aktif</label>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" name="layanan[]" value="same_day" checked>
                  Same-day Delivery (pesan sebelum 14.00)
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="layanan[]" value="reguler" checked>
                  Reguler (1–2 hari kerja)
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="layanan[]" value="luar_kota">
                  Luar Kota (2–4 hari kerja)
                </label>
              </div>
            </div>

            <div style="height:1px;background:var(--border);margin:14px 0;"></div>

            <div class="form-group">
              <label>Biaya Ongkir Dasar (Rp)</label>
              <input type="number" name="ongkir_dasar" class="form-control"
                     placeholder="15000" value="15000">
              <small style="font-size:11px;color:var(--muted);display:block;margin-top:4px;">
                * Biaya dasar untuk area Jabodetabek
              </small>
            </div>

            <div class="form-group">
              <label>Minimum Gratis Ongkir (Rp)</label>
              <input type="number" name="min_gratis" class="form-control"
                     placeholder="150000" value="150000">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Simpan</button>
          </form>
        </div>
      </div>

      <!-- Metode Pembayaran -->
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:16px;">&#128179; Metode Pembayaran</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="pengaturan.php">
            <input type="hidden" name="action" value="simpan_pengiriman">
            <div class="form-group">
              <label>Pembayaran yang Diterima</label>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" name="metode[]" value="qris" checked>
                  &#128248; QRIS
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="metode[]" value="cash" checked>
                  &#128181; Cash di Toko
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="metode[]" value="transfer">
                  &#127981; Transfer Bank
                </label>
              </div>
            </div>

            <div style="height:1px;background:var(--border);margin:14px 0;"></div>

            <div class="form-group">
              <label>Nomor Rekening (untuk Transfer)</label>
              <input type="text" name="norek" class="form-control"
                     placeholder="1234-5678-9012 (BCA a.n. Fleuriste)">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Simpan</button>
          </form>
        </div>
      </div>

    </div><!-- /kolom kanan -->

  </div><!-- /.pengaturan-layout -->
</div><!-- /.page-body -->

<?php include 'includes/footer.php'; ?>

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

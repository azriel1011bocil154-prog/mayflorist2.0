<?php
// admin/laporan-penjualan.php

require '../koneksi.php';

$page_title  = 'Laporan Penjualan — Admin Fleuriste';
$active_menu = 'laporan';

include 'includes/header.php';

$period = $_GET['period'] ?? 'hari';

/*
|--------------------------------------------------------------------------
| FILTER TANGGAL
|--------------------------------------------------------------------------
*/

$where = "";

if ($period == 'hari') {
  $where = "DATE(tanggal_transaksi) = CURDATE()";
} elseif ($period == 'minggu') {
  $where = "YEARWEEK(tanggal_transaksi, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($period == 'bulan') {
  $where = "MONTH(tanggal_transaksi) = MONTH(CURDATE())
            AND YEAR(tanggal_transaksi) = YEAR(CURDATE())";
} elseif ($period == 'kustom') {
  $dari   = $_GET['dari'] ?? date('Y-m-01');
  $sampai = $_GET['sampai'] ?? date('Y-m-d');

  $where = "DATE(tanggal_transaksi) BETWEEN '$dari' AND '$sampai'";
}

/*
|--------------------------------------------------------------------------
| TOTAL LAPORAN (Hanya Menghitung Arus Uang Masuk yang 'diterima')
|--------------------------------------------------------------------------
*/
$query_total = mysqli_query($conn, "
  SELECT
    COUNT(id_transaksi) AS jumlah_transaksi,
    SUM(total_pembayaran) AS total_pendapatan,
    AVG(total_pembayaran) AS rata_transaksi
  FROM transaksi
  WHERE $where AND status_pembayaran = 'diterima'
");

if (!$query_total) {
  die(mysqli_error($conn));
}

$data_total = mysqli_fetch_assoc($query_total);

$total_pendapatan = $data_total['total_pendapatan'] ?? 0;
$jumlah_transaksi = $data_total['jumlah_transaksi'] ?? 0;
$rata_transaksi   = $data_total['rata_transaksi'] ?? 0;

/*
|--------------------------------------------------------------------------
| DETAIL TRANSAKSI (Menampilkan Semua Status untuk Keperluan Audit Log)
|--------------------------------------------------------------------------
*/
$query_invoice = mysqli_query($conn, "
  SELECT
    id_transaksi,
    tanggal_transaksi,
    jenis_pembayaran,
    metode_pembayaran,
    total_pembayaran,
    status_pembayaran
  FROM transaksi
  WHERE $where
  ORDER BY id_transaksi DESC
");

if (!$query_invoice) {
  die(mysqli_error($conn));
}
?>

<div class="page-body">

  <div class="card">

    <div class="card-header">
      <h1 style="font-size:20px;">Laporan Penjualan</h1>
    </div>

    <div style="padding:14px 20px 0;">
      <div class="filter-tabs">

        <?php foreach ([
          'hari'=>'Hari Ini',
          'minggu'=>'Minggu Ini',
          'bulan'=>'Bulan Ini',
          'kustom'=>'Kustom'
        ] as $k => $v): ?>

          <a href="?period=<?= $k ?>"
             class="filter-tab <?= $period === $k ? 'active' : '' ?>">
            <?= $v ?>
          </a>

        <?php endforeach; ?>

      </div>
    </div>

    <div class="card-body">

      <div class="report-summary">
        <table>

          <tr>
            <td>Total Pendapatan (Uang Masuk)</td>
            <td style="font-weight:700; color:#2e7d32;">
              Rp <?= number_format($total_pendapatan,0,',','.') ?>
            </td>
          </tr>

          <tr>
            <td>Jumlah Transaksi Sukses</td>
            <td>
              <?= $jumlah_transaksi ?> Transaksi
            </td>
          </tr>

          <tr>
            <td>Rata-rata Pendapatan / Transaksi</td>
            <td>
              Rp <?= number_format($rata_transaksi,0,',','.') ?>
            </td>
          </tr>

        </table>
      </div>

      <?php if ($period === 'kustom'): ?>

      <form method="GET"
            style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">

        <input type="hidden" name="period" value="kustom">

        <div>
          <label>Dari</label>
          <input type="date"
                 name="dari"
                 class="form-control"
                 value="<?= $_GET['dari'] ?? date('Y-m-01') ?>">
        </div>

        <div>
          <label>Sampai</label>
          <input type="date"
                 name="sampai"
                 class="form-control"
                 value="<?= $_GET['sampai'] ?? date('Y-m-d') ?>">
        </div>

        <div style="align-self:end;">
          <button type="submit" class="btn btn-primary">
            Terapkan
          </button>
        </div>

      </form>

      <?php endif; ?>

      <h3 style="font-size:15px;margin-bottom:12px;">
        Detail Transaksi
      </h3>

      <div style="overflow-x:auto;">

        <table class="data-table">

          <thead>
            <tr>
              <th>Tanggal</th>
              <th>ID Transaksi</th>
              <th>Jenis</th>
              <th>Metode</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>

            <?php if (mysqli_num_rows($query_invoice) == 0): ?>
            <tr>
              <td colspan="6" style="text-align:center;color:var(--muted);padding:20px;">Tidak ada data transaksi pada periode ini.</td>
            </tr>
            <?php endif; ?>

            <?php while($inv = mysqli_fetch_assoc($query_invoice)) : ?>

            <tr>

              <td>
                <?= date('d/m/Y', strtotime($inv['tanggal_transaksi'])) ?>
              </td>

              <td style="font-family:monospace;">
                #TRX-<?= $inv['id_transaksi'] ?>
              </td>

              <td>
                <?= strtoupper($inv['jenis_pembayaran']) ?>
              </td>

              <td>
                <?= ucfirst(str_replace('_', ' ', $inv['metode_pembayaran'])) ?>
              </td>

              <td style="font-weight:600;color:var(--rose);">
                Rp <?= number_format($inv['total_pembayaran'],0,',','.') ?>
              </td>

              <td>

                <?php
                $badge = 'badge-pending'; // Default untuk 'menunggu'

                if ($inv['status_pembayaran'] == 'diterima') {
                  $badge = 'badge-selesai';
                }

                if ($inv['status_pembayaran'] == 'ditolak') {
                  $badge = 'badge-danger';
                }
                ?>

                <span class="badge <?= $badge ?>">
                  <?= ucfirst($inv['status_pembayaran']) ?>
                </span>

              </td>

            </tr>

            <?php endwhile; ?>

          </tbody>

        </table>

      </div>

    </div>

  </div>

</div>

<?php include 'includes/footer.php'; ?>
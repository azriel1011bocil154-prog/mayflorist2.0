<?php
// admin/laporan-penjualan.php — Laporan Penjualan & Pengaturan Akun

$page_title  = 'Laporan Penjualan — Admin Fleuriste';
$active_menu = 'laporan';
include 'includes/header.php';

$alert = '';

// ── Period filter ──
$period = $_GET['period'] ?? 'hari';

// ── Dummy report data ──
$report = [
  'hari'   => ['total'=>'Rp 3.750.000', 'transaksi'=>'12 Transaksi', 'rata'=>'Rp 312.500'],
  'minggu' => ['total'=>'Rp 21.400.000','transaksi'=>'78 Transaksi', 'rata'=>'Rp 274.358'],
  'bulan'  => ['total'=>'Rp 87.500.000','transaksi'=>'312 Transaksi','rata'=>'Rp 280.448'],
  'kustom' => ['total'=>'Rp 12.600.000','transaksi'=>'46 Transaksi', 'rata'=>'Rp 273.913'],
];
$current_report = $report[$period] ?? $report['hari'];

$invoices = [
  ['tgl'=>'10/05/2026','no'=>'INV-20260510-001','total'=>'Rp 1.250.000','status'=>'Selesai'],
  ['tgl'=>'10/05/2026','no'=>'INV-20260510-002','total'=>'Rp 875.000',  'status'=>'Selesai'],
  ['tgl'=>'09/05/2026','no'=>'INV-20260509-001','total'=>'Rp 435.000',  'status'=>'Selesai'],
  ['tgl'=>'09/05/2026','no'=>'INV-20260509-002','total'=>'Rp 290.000',  'status'=>'Dikirim'],
  ['tgl'=>'08/05/2026','no'=>'INV-20260508-001','total'=>'Rp 1.050.000','status'=>'Selesai'],
];
?>

<div class="page-body">
  <?= $alert ?>

  <div>

    <!-- ══ LAPORAN PENJUALAN ══ -->
    <div>
      <div class="card">
        <div class="card-header">
          <h1 style="font-size:20px;">Laporan Penjualan</h1>
        </div>

        <!-- Period Tabs -->
        <div style="padding:14px 20px 0;">
          <div class="filter-tabs">
            <?php foreach (['hari'=>'Hari ini','minggu'=>'Minggu Ini','bulan'=>'Bulan Ini','kustom'=>'Kustom'] as $k=>$v): ?>
            <a href="?period=<?= $k ?>"
               class="filter-tab <?= $period === $k ? 'active' : '' ?>">
              <?= $v ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Summary -->
        <div class="card-body">
          <div class="report-summary">
            <table>
              <tr>
                <td>Total Pendapatan</td>
                <td><?= $current_report['total'] ?></td>
              </tr>
              <tr>
                <td>Jumlah Transaksi</td>
                <td><?= $current_report['transaksi'] ?></td>
              </tr>
              <tr>
                <td>Rata-rata Transaksi</td>
                <td><?= $current_report['rata'] ?></td>
              </tr>
            </table>
          </div>

          <!-- Kustom date range -->
          <?php if ($period === 'kustom'): ?>
          <form method="GET" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap;">
            <input type="hidden" name="period" value="kustom">
            <div>
              <label style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:4px;">Dari</label>
              <input type="date" name="dari" class="form-control" style="width:160px;"
                     value="<?= htmlspecialchars($_GET['dari'] ?? date('Y-m-01')) ?>">
            </div>
            <div>
              <label style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:4px;">Sampai</label>
              <input type="date" name="sampai" class="form-control" style="width:160px;"
                     value="<?= htmlspecialchars($_GET['sampai'] ?? date('Y-m-d')) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Terapkan</button>
          </form>
          <?php endif; ?>

          <!-- Detail Laporan Table -->
          <h3 style="font-size:15px;margin-bottom:12px;">Detail Laporan</h3>
          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>No. Invoice</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                  <td><?= $inv['tgl'] ?></td>
                  <td style="font-weight:500;font-family:monospace;font-size:12px;"><?= $inv['no'] ?></td>
                  <td style="font-weight:600;color:var(--rose);"><?= $inv['total'] ?></td>
                  <td>
                    <span class="badge <?= $inv['status'] === 'Selesai' ? 'badge-selesai' : 'badge-dikirim' ?>">
                      <?= $inv['status'] ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Export Buttons -->
          <div style="display:flex;gap:10px;margin-top:18px;">
            <a href="#" class="btn btn-secondary" onclick="alert('Export PDF — sambungkan ke backend')">
              &#128196; Export PDF
            </a>
            <a href="#" class="btn btn-success" onclick="alert('Export Excel — sambungkan ke backend')">
              &#128202; Export Excel
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

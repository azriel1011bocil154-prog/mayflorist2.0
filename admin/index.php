<?php
// admin/index.php — Dashboard Dinamis

$page_title  = 'Dashboard — Admin MayFlorist';
$active_menu = 'dashboard';
include 'includes/header.php';

// Hubungkan ke file koneksi databasemu
include '../koneksi.php'; 

// ==========================================
// 1. QUERY AMBIL DATA KARTU STATISTIK (STAT CARDS)
// ==========================================

// A. Hitung Total Penjualan Hari Ini (Dari transaksi yang sudah 'diterima' admin)
$q_hari_ini = mysqli_query($conn, "SELECT SUM(total_pembayaran) AS total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE() AND status_pembayaran = 'diterima'");
$data_hari_ini = mysqli_fetch_assoc($q_hari_ini);
$total_hari_ini = $data_hari_ini['total'] ?? 0;

// B. Hitung Total Pesanan Masuk Bulan Ini (Semua pesanan yang dibuat bulan berjalan)
$q_bulan_ini = mysqli_query($conn, "SELECT COUNT(id_pesanan) AS total FROM pesanan WHERE MONTH(tanggal_pesanan) = MONTH(CURDATE()) AND YEAR(tanggal_pesanan) = YEAR(CURDATE())");
$data_bulan_ini = mysqli_fetch_assoc($q_bulan_ini);
$pesanan_bulan_ini = $data_bulan_ini['total'] ?? 0;

// C. Hitung Pesanan Baru Masuk yang Perlu Tindakan (Status Pembayaran masih 'menunggu' verifikasi admin)
$q_pesanan_baru = mysqli_query($conn, "SELECT COUNT(id_transaksi) AS total FROM transaksi WHERE status_pembayaran = 'menunggu'");
$data_pesanan_baru = mysqli_fetch_assoc($q_pesanan_baru);
$pesanan_baru = $data_pesanan_baru['total'] ?? 0;

// Array Statistik untuk UI
$stats = [
  ['label' => 'Total Penjualan Hari Ini', 'value' => 'Rp ' . number_format($total_hari_ini, 0, ',', '.'), 'sub' => 'Uang Masuk Valid',  'sub_icon' => '&#128200;'],
  ['label' => 'Total Pesanan Bulan Ini',  'value' => $pesanan_bulan_ini . ' Pesanan',   'sub' => 'Periode Bulan Ini',    'sub_icon' => '&#128230;'],
  ['label' => 'Verifikasi Pembayaran',    'value' => $pesanan_baru . ' Menunggu',       'sub' => 'Perlu Validasi Struk', 'sub_icon' => '&#128276;'],
];


// ==========================================
// 2. QUERY GENERATE DATA GRAFIK BULANAN (TAHUN BERJALAN)
// ==========================================
$chart_labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$chart_data   = [];

for ($m = 1; $m <= 12; $m++) {
    $q_chart = mysqli_query($conn, "
        SELECT SUM(total_pembayaran) AS total 
        FROM transaksi 
        WHERE MONTH(tanggal_transaksi) = $m 
          AND YEAR(tanggal_transaksi) = YEAR(CURDATE()) 
          AND status_pembayaran = 'diterima'
    ");
    $dt_chart = mysqli_fetch_assoc($q_chart);
    $chart_data[] = (int)($dt_chart['total'] ?? 0);
}
?>

<div class="page-body">

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2>Ringkasan Toko</h2>
      <button class="btn btn-outline btn-sm">+ Kelola Banner</button>
    </div>

    <div class="card-body" style="padding:20px;">
      <div class="stat-cards" style="margin-bottom:0;">
        <?php foreach ($stats as $s): ?>
        <div class="stat-card">
          <div class="stat-label"><?= $s['label'] ?></div>
          <div class="stat-value"><?= $s['value'] ?></div>
          <div class="stat-sub">
            <?= $s['sub_icon'] ?> <?= $s['sub'] ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2>Grafik Penjualan</h2>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-outline btn-sm" onclick="setChartPeriod('minggu',this)">Minggu</button>
        <button class="btn btn-primary btn-sm" onclick="setChartPeriod('bulan',this)">Bulan</button>
        <button class="btn btn-outline btn-sm" onclick="setChartPeriod('tahun',this)">Tahun</button>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-wrap">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2>Pesanan Terbaru</h2>
      <a href="manajemen-pesanan.php" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>No. Pesanan</th>
            <th>Tanggal</th>
            <th>Nama Pelanggan</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Ambil 5 data pesanan terbaru disesuaikan dengan struktur JOIN database kamu
          $query_orders = mysqli_query($conn, "
              SELECT p.id_pesanan, p.tanggal_pesanan, p.total_harga, p.status_pesanan, u.nama_user 
              FROM pesanan p 
              LEFT JOIN user u ON p.id_user = u.id_user 
              ORDER BY p.id_pesanan DESC 
              LIMIT 5
          ");

          if (mysqli_num_rows($query_orders) == 0):
          ?>
          <tr>
            <td colspan="6" style="text-align:center;color:var(--muted);padding:20px;">Belum ada data pesanan masuk.</td>
          </tr>
          <?php 
          else:
            while ($o = mysqli_fetch_assoc($query_orders)):
              // Mapping class badge CSS sesuai ENUM status_pesanan di database kamu
              $badge = match($o['status_pesanan']) {
                'selesai'       => 'badge-selesai',
                'dikirim'       => 'badge-dikirim',
                'diproses'      => 'badge-diproses',
                'pending'       => 'badge-pending',
                'belum_bayar'   => 'badge-pending', // Bisa disamakan warnanya dengan pending
                'dibatalkan'    => 'badge-ditolak',
                default         => 'badge-pending',
              };
          ?>
          <tr>
            <td><strong>#ORD-<?= sprintf("%03d", $o['id_pesanan']) ?></strong></td>
            <td><?= date('d/m/Y', strtotime($o['tanggal_pesanan'])) ?></td>
            <td><?= htmlspecialchars($o['nama_user'] ?? 'Guest / Umum') ?></td>
            <td style="font-weight:600;color:var(--rose);">Rp <?= number_format($o['total_harga'], 0, ',', '.') ?></td>
            <td><span class="badge <?= $badge ?>"><?= str_replace('_', ' ', ucfirst($o['status_pesanan'])) ?></span></td>
            <td>
              <a href="manajemen-pesanan.php?detail=<?= $o['id_pesanan'] ?>" class="btn btn-primary btn-sm">Detail</a>
            </td>
          </tr>
          <?php 
            endwhile; 
          endif;
          ?>
        </tbody>
      </table>
    </div>
  </div>

</div><?php include 'includes/footer.php'; ?>

<script>
// ── Sales Chart (Chart.js Integrasi Data Riil PHP) ──
const ctx = document.getElementById('salesChart');
if (ctx) {
  const labels = <?= json_encode($chart_labels) ?>;
  const data   = <?= json_encode($chart_data) ?>;

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Penjualan (Rp)',
        data,
        borderColor: '#C9736A',
        backgroundColor: 'rgba(201,115,106,0.08)',
        borderWidth: 2,
        pointBackgroundColor: '#C9736A',
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.35,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ' Rp ' + ctx.raw.toLocaleString('id-ID')
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (v) => 'Rp ' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'jt' : v.toLocaleString('id-ID')),
            font: { size: 11 }
          },
          grid: { color: 'rgba(0,0,0,0.05)' }
        },
        x: {
          ticks: { font: { size: 11 } },
          grid: { display: false }
        }
      }
    }
  });

  window.setChartPeriod = function(period, btn) {
    document.querySelectorAll('.card-header .btn').forEach(b => {
      b.className = b === btn ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
    });
    
    // Switch filter data dinamis/simulasi periodik
    const demo = {
      minggu: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], data: [1200000,980000,1450000,870000,1600000,2100000,1800000] },
      bulan:  { labels: <?= json_encode($chart_labels) ?>, data: <?= json_encode($chart_data) ?> },
      tahun:  { labels: ['2023','2024','2025','2026'], data: [78000000,95000000,110000000,88000000] },
    };
    chart.data.labels = demo[period].labels;
    chart.data.datasets[0].data = demo[period].data;
    chart.update();
  };
}
</script>
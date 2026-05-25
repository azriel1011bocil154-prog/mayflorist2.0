<?php
// admin/index.php — Dashboard

$page_title  = 'Dashboard — Admin MayFlorist';
$active_menu = 'dashboard';
include 'includes/header.php';

// ── Dummy data ──
$stats = [
  ['label' => 'Total Penjualan Hari Ini', 'value' => 'Rp 3.750.000', 'sub' => 'Rp 3.750.000',  'sub_icon' => '&#128200;'],
  ['label' => 'Total Pesanan Bulan Ini',  'value' => '123 Pesanan',  'sub' => '123 Pesanan',    'sub_icon' => '&#128230;'],
  ['label' => 'Pesanan Baru',             'value' => '5 Pesanan',    'sub' => 'Lihat Semua ▾',  'sub_icon' => '&#128276;'],
];

$chart_labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$chart_data   = [4200000, 5100000, 3800000, 6200000, 5500000, 7100000, 6300000, 8400000, 7200000, 9100000, 8700000, 10200000];
?>

<div class="page-body">

  <!-- ── PROMO BANNER HEADER ── -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2>Promo Banner</h2>
      <button class="btn btn-outline btn-sm">+ Tambah Banner</button>
    </div>

    <!-- Stat Cards inside banner card -->
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

  <!-- ── GRAFIK PENJUALAN ── -->
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

  <!-- ── PESANAN TERBARU ── -->
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
          $orders = [
            ['no'=>'#ORD-001','tgl'=>'10/05/2026','nama'=>'Siti Nurjanah', 'total'=>'Rp 185.000','status'=>'Selesai'],
            ['no'=>'#ORD-002','tgl'=>'10/05/2026','nama'=>'Budi Santoso',  'total'=>'Rp 210.000','status'=>'Dikirim'],
            ['no'=>'#ORD-003','tgl'=>'10/05/2026','nama'=>'Dewi Lestari',  'total'=>'Rp 325.000','status'=>'Diproses'],
            ['no'=>'#ORD-004','tgl'=>'10/05/2026','nama'=>'Andi Pratama',  'total'=>'Rp 175.000','status'=>'Selesai'],
          ];
          foreach ($orders as $o):
            $badge = match($o['status']) {
              'Selesai'  => 'badge-selesai',
              'Dikirim'  => 'badge-dikirim',
              'Diproses' => 'badge-diproses',
              default    => 'badge-pending',
            };
          ?>
          <tr>
            <td><strong><?= $o['no'] ?></strong></td>
            <td><?= $o['tgl'] ?></td>
            <td><?= htmlspecialchars($o['nama']) ?></td>
            <td style="font-weight:600;color:var(--rose);"><?= $o['total'] ?></td>
            <td><span class="badge <?= $badge ?>"><?= $o['status'] ?></span></td>
            <td>
              <a href="manajemen-pesanan.php?detail=<?= urlencode($o['no']) ?>"
                 class="btn btn-primary btn-sm">Detail</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <a class="pg-active">1</a>
      <a href="manajemen-pesanan.php">2</a>
      <a href="manajemen-pesanan.php">3</a>
      <span class="pg-dots">...</span>
      <a href="manajemen-pesanan.php">6</a>
      <a href="manajemen-pesanan.php" style="padding:0 10px;">&#8250;</a>
    </div>
  </div>

</div><!-- /.page-body -->

<?php include 'includes/footer.php'; ?>

<script>
// ── Sales Chart ──
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
          beginAtZero: false,
          ticks: {
            callback: (v) => 'Rp ' + (v/1000000).toFixed(1) + 'jt',
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
    // Regenerate dummy data per period for demo
    const demo = {
      minggu: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], data: [1200000,980000,1450000,870000,1600000,2100000,1800000] },
      bulan:  { labels: <?= json_encode($chart_labels) ?>, data: <?= json_encode($chart_data) ?> },
      tahun:  { labels: ['2021','2022','2023','2024','2025','2026'], data: [45000000,62000000,78000000,95000000,110000000,88000000] },
    };
    chart.data.labels = demo[period].labels;
    chart.data.datasets[0].data = demo[period].data;
    chart.update();
  };
}
</script>

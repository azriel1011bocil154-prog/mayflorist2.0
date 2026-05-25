<?php
// pesanan.php — Status Pesanan Aktif

session_start();

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'pesanan.php';
    header('Location: login.php');
    exit;
}

include 'koneksi.php';
include 'includes/products.php';

// ambil id user login
$id_user = $_SESSION['user']['id_user'];

// ambil data pesanan dari database
$query = mysqli_query($conn, "
    SELECT *
    FROM pesanan
    WHERE id_user = '$id_user'
    AND status_pesanan != 'selesai'
    AND status_pesanan != 'dibatalkan'
    ORDER BY id_pesanan DESC
");

$pesanan_aktif = [];

while ($row = mysqli_fetch_assoc($query)) {
    $pesanan_aktif[] = $row;
}

// status config
$status_config = [

    'belum_bayar' => [
        'color' => '#8C7570',
        'bg'    => '#F9F1EE',
        'icon'  => '&#128179;',
        'step'  => 1
    ],

    'pending' => [
        'color' => '#7d5a00',
        'bg'    => '#fff8e1',
        'icon'  => '&#128336;',
        'step'  => 2
    ],

    'diproses' => [
        'color' => '#0d5c8c',
        'bg'    => '#e8f4fd',
        'icon'  => '&#9986;',
        'step'  => 3
    ],

    'dikirim' => [
        'color' => '#256d3f',
        'bg'    => '#eaf7ee',
        'icon'  => '&#128665;',
        'step'  => 4
    ],

    'selesai' => [
        'color' => '#256d3f',
        'bg'    => '#eaf7ee',
        'icon'  => '&#127800;',
        'step'  => 5
    ],

    'dibatalkan' => [
        'color' => '#9b2020',
        'bg'    => '#fdeaea',
        'icon'  => '&#10006;',
        'step'  => 0
    ]
];

$page_title = 'Pesanan Saya — MayFlorist';
$active_nav = '';

include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <h1 style="font-size:24px;">Pesanan Saya</h1>

    <a href="riwayat.php"
       style="font-size:14px;color:var(--rose);">
      Lihat Riwayat &rarr;
    </a>
  </div>

  <?php if (empty($pesanan_aktif)): ?>

    <div style="text-align:center;padding:64px 0;">

      <div style="font-size:64px;margin-bottom:16px;">
        &#127800;
      </div>

      <h3 style="font-family:'Playfair Display',serif;margin-bottom:8px;">
        Belum Ada Pesanan Aktif
      </h3>

      <p style="color:var(--muted);margin-bottom:20px;">
        Yuk, buat pesanan pertamamu!
      </p>

      <a href="katalog.php"
         class="btn btn-primary"
         style="padding:12px 32px;">
        Belanja Sekarang
      </a>

    </div>

  <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:20px;">

      <?php foreach ($pesanan_aktif as $p):

        $sc = $status_config[$p['status_pesanan']] ?? $status_config['pending'];

      ?>

      <div class="order-card">

        <!-- Header -->
        <div class="order-card-header">

          <div>

            <span style="font-family:monospace;font-weight:700;color:var(--bark);">
              #ORD-<?= $p['id_pesanan'] ?>
            </span>

            <span style="font-size:12px;color:var(--muted);margin-left:10px;">
              &#128197;
              <?= date('d M Y', strtotime($p['tanggal_pesanan'])) ?>
            </span>

          </div>

          <span class="status-badge"
                style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">

            <?= $sc['icon'] ?>
            <?= ucfirst(str_replace('_', ' ', $p['status_pesanan'])) ?>

          </span>

        </div>

        <!-- Progress -->
        <div class="order-progress">

          <?php
          $steps = ['Dibuat', 'Bayar', 'Diproses', 'Dikirim', 'Selesai'];
          $cur   = $sc['step'];

          foreach ($steps as $i => $s):

            $snum = $i + 1;

            $done   = $snum < $cur;
            $active = $snum === $cur;
          ?>

          <div class="progress-step <?= $done ? 'done' : ($active ? 'active' : '') ?>">

            <div class="step-dot">
              <?= $done ? '&#10003;' : $snum ?>
            </div>

            <div class="step-label">
              <?= $s ?>
            </div>

          </div>

          <?php if ($i < count($steps)-1): ?>

          <div class="step-line <?= $done ? 'done' : '' ?>"></div>

          <?php endif; ?>
          <?php endforeach; ?>

        </div>

        <!-- Isi -->
        <div class="order-items">

          <div class="order-item-row">

            <div class="order-item-img">
              &#127800;
            </div>

            <div style="flex:1;">

              <div style="font-weight:500;font-size:14px;color:var(--bark);">
                Total Produk: <?= $p['total_produk'] ?>
              </div>

              <div style="font-size:13px;color:var(--muted);">
                Total Harga:
                <?= formatRupiah($p['total_harga']) ?>
              </div>

              <?php if (!empty($p['alamat_pesanan'])): ?>

              <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                📍 <?= htmlspecialchars($p['alamat_pesanan']) ?>
              </div>

              <?php endif; ?>

            </div>

          </div>

        </div>

        <!-- Footer -->
        <div class="order-card-footer">

          <div>

            <div style="font-size:15px;font-weight:700;color:var(--bark);">
              Total:
              <span style="color:var(--rose);">
                <?= formatRupiah($p['total_harga']) ?>
              </span>
            </div>

          </div>

          <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">

            <?php if ($p['status_pesanan'] === 'belum_bayar'): ?>

              <a href="bayar.php?id=<?= $p['id_pesanan'] ?>"
                 class="btn btn-rose btn-sm">
                Bayar Sekarang
              </a>

            <?php elseif ($p['status_pesanan'] === 'dikirim'): ?>

              <a href="konfirmasi-terima.php?no=<?= $p['id_pesanan'] ?>"
                 class="btn btn-primary btn-sm">
                &#10003; Pesanan Diterima
              </a>

            <?php endif; ?>

          </div>

        </div>

      </div>

      <?php endforeach; ?>

    </div>

  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<style>

.order-card{
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}

.order-card-header{
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);

  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
  gap:8px;

  background: var(--petal);
}

.status-badge{
  font-size:12px;
  font-weight:600;
  padding:4px 12px;
  border-radius:100px;
}

/* Progress */

.order-progress{
  display:flex;
  align-items:center;

  padding:18px 20px;

  overflow-x:auto;
}

.progress-step{
  display:flex;
  flex-direction:column;
  align-items:center;

  gap:6px;

  flex-shrink:0;
}

.step-dot{
  width:28px;
  height:28px;
  border-radius:50%;

  background: var(--border);
  color: var(--muted);

  display:flex;
  align-items:center;
  justify-content:center;

  font-size:12px;
  font-weight:700;
}

.progress-step.done .step-dot{
  background: var(--moss);
  color:white;
}

.progress-step.active .step-dot{
  background: var(--rose);
  color:white;
}

.step-label{
  font-size:11px;
  color:var(--muted);
  white-space:nowrap;
}

.step-line{
  flex:1;
  height:2px;

  background: var(--border);

  min-width:20px;

  margin:0 4px;
  margin-bottom:18px;
}

.step-line.done{
  background: var(--moss);
}

/* Items */

.order-items{
  padding:14px 20px;
  border-top:1px solid var(--border);
}

.order-item-row{
  display:flex;
  align-items:center;
  gap:12px;
}

.order-item-img{
  width:48px;
  height:48px;
  border-radius:8px;

  background: var(--petal);

  display:flex;
  align-items:center;
  justify-content:center;

  font-size:24px;
  flex-shrink:0;
}

.order-card-footer{
  padding:14px 20px;

  border-top:1px solid var(--border);

  display:flex;
  align-items:center;
  justify-content:space-between;

  flex-wrap:wrap;
  gap:12px;

  background: var(--petal);
}

</style>
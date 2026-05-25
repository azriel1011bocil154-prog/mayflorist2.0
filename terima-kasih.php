<?php
// terima-kasih.php — Halaman Konfirmasi Pesanan
session_start();
$order = $_SESSION['last_order'] ?? null;
if (!$order) { header('Location: index.php'); exit; }

include 'includes/products.php';
$page_title = 'Pesanan Berhasil — MayFlorist';
$active_nav = '';
include 'includes/header.php';

$no_order = 'FLR-' . strtoupper(substr(md5(time()), 0, 8));
?>

<div class="page-wrapper" style="padding:60px 0;text-align:center;">
  <div style="max-width:480px;margin:0 auto;background:var(--white);border:1px solid var(--border);border-radius:16px;padding:40px 32px;">
    <div style="font-size:64px;margin-bottom:16px;">&#127800;</div>
    <h1 style="font-size:26px;margin-bottom:8px;">Pesanan Berhasil!</h1>
    <p style="color:var(--muted);font-size:15px;margin-bottom:24px;">
      Terima kasih, <strong style="color:var(--bark);"><?= htmlspecialchars($order['nama']) ?></strong>!<br>
      Pesananmu sedang kami proses.
    </p>

    <div style="background:var(--petal);border-radius:10px;padding:16px 20px;text-align:left;margin-bottom:24px;">
      <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
        <span style="color:var(--muted);">No. Pesanan</span>
        <strong style="color:var(--bark);font-family:monospace;"><?= $no_order ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
        <span style="color:var(--muted);">Metode</span>
        <span><?= strtoupper(htmlspecialchars($order['bayar'])) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:14px;">
        <span style="color:var(--muted);">Total</span>
        <strong style="color:var(--rose);"><?= formatRupiah($order['total']) ?></strong>
      </div>
    </div>

    <a href="index.php" class="btn btn-primary" style="width:100%;padding:13px;font-size:15px;">
      Kembali ke Beranda
    </a>
    <a href="katalog.php" class="btn btn-outline" style="width:100%;padding:12px;margin-top:10px;font-size:15px;">
      Belanja Lagi
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

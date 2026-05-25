<?php
// admin/includes/header.php
// Set $active_menu before including: 'dashboard' | 'produk' | 'pesanan' | 'laporan' | 'pengaturan'

$active_menu = $active_menu ?? 'dashboard';
$page_title  = $page_title  ?? 'Admin — MayFlorist';

session_start();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css">
  <!-- Chart.js for dashboard -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
<div class="admin-layout">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
  <a class="sidebar-logo" href="index.php">
    May<span>Florist</span>
    <small>Admin Panel</small>
  </a>

  <nav class="sidebar-nav">
    <a href="index.php"              class="<?= $active_menu === 'dashboard'  ? 'active' : '' ?>">
      <span class="nav-icon">&#128202;</span> Dashboard
    </a>
    <a href="kelola-produk.php"      class="<?= $active_menu === 'produk'     ? 'active' : '' ?>">
      <span class="nav-icon">&#127800;</span> Kelola Produk
    </a>
    <a href="manajemen-pesanan.php"  class="<?= $active_menu === 'pesanan'    ? 'active' : '' ?>">
      <span class="nav-icon">&#128230;</span> Manajemen Pesanan
    </a>
    <a href="konfirmasi-pembayaran.php" class="<?= $active_menu === 'konfirmasi' ? 'active' : '' ?>">
      <span class="nav-icon">&#128179;</span> Konfirmasi Pembayaran
    </a>
    <a href="laporan-penjualan.php"  class="<?= $active_menu === 'laporan'    ? 'active' : '' ?>">
      <span class="nav-icon">&#128196;</span> Laporan Penjualan
    </a>
    <a href="pengaturan.php"         class="<?= $active_menu === 'pengaturan' ? 'active' : '' ?>">
      <span class="nav-icon">&#9881;</span> Pengaturan
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="footer-logo-box">&#127800;</div>
    <p>Toko bunga terbaik untuk setiap momen spesial Anda.</p>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main-content">

  <!-- TOP BAR -->
  <header class="topbar">
    <span class="topbar-logo">May<span>Florist</span></span>

    <nav class="topbar-nav">
      <a href="index.php"             class="<?= $active_menu === 'dashboard'  ? 'active' : '' ?>">Home</a>
      <a href="kelola-produk.php"     class="<?= $active_menu === 'produk'     ? 'active' : '' ?>">Kelola Produk</a>
      <a href="manajemen-pesanan.php" class="<?= $active_menu === 'pesanan'    ? 'active' : '' ?>">Manajemen Pesanan</a>
      <a href="konfirmasi-pembayaran.php" class="<?= $active_menu === 'konfirmasi' ? 'active' : '' ?>">Konfirmasi Pembayaran</a>
      <a href="laporan-penjualan.php" class="<?= $active_menu === 'laporan'    ? 'active' : '' ?>">Laporan Penjualan</a>
    </nav>

    <div class="topbar-spacer"></div>

    <form class="topbar-search" action="kelola-produk.php" method="GET">
      <input type="text" name="q" placeholder="Cari produk..."
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      <button type="submit">&#128269;</button>
    </form>
    <a href="logout.php" class="btn-logout"
      onclick="return confirm('Yakin ingin logout?')">
      &#128682; Logout
    </a>
  </header>

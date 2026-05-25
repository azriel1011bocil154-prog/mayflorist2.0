<?php
// includes/header.php
// Usage: include 'includes/header.php';
// Set $page_title and $active_nav before including

$page_title   = $page_title   ?? 'Fleuriste — Toko Buket Bunga';
$active_nav   = $active_nav   ?? 'home';

// Simple cart count from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="<?= $base_path ?? '' ?>assets/css/style.css">
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <a class="logo" href="<?= $base_path ?? '' ?>index.php">
    <img src="<?= $base_path ?? '' ?>assets/images/logo.jpeg" alt="MayFlorist Logo">
    May<span>Florist</span></a>

  <ul class="nav-links">
    <li><a href="<?= $base_path ?? '' ?>index.php"    class="<?= $active_nav === 'home'    ? 'active' : '' ?>">Home</a></li>
    <li><a href="<?= $base_path ?? '' ?>katalog.php"  class="<?= $active_nav === 'produk'  ? 'active' : '' ?>">Produk</a></li>
    <li><a href="<?= $base_path ?? '' ?>tentang.php"  class="<?= $active_nav === 'tentang' ? 'active' : '' ?>">Tentang</a></li>
    <li><a href="<?= $base_path ?? '' ?>kontak.php"   class="<?= $active_nav === 'kontak'  ? 'active' : '' ?>">Kontak</a></li>
  </ul>

  <div class="nav-spacer"></div>

  <form class="nav-search" action="<?= $base_path ?? '' ?>katalog.php" method="GET">
    <input type="text" name="q" placeholder="Cari produk..."
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button type="submit">&#128269;</button>
  </form>

  <a href="<?= $base_path ?? '' ?>keranjang.php" class="nav-icon-btn" title="Keranjang">
    &#128722;
    <?php if ($cart_count > 0): ?>
      <span class="badge"><?= $cart_count ?></span>
    <?php endif; ?>
  </a>

  <?php if (!empty($_SESSION['user'])): ?>
  <!-- User sudah login: tampilkan dropdown -->
  <div class="nav-user-wrap" id="navUserWrap">
    <button class="nav-user-btn" onclick="toggleUserMenu()">
      <span class="nav-avatar"><?= strtoupper(mb_substr($_SESSION['user']['nama_user'], 0, 1)) ?></span>
      <span class="nav-user-name"><?= htmlspecialchars(explode(' ', $_SESSION['user']['nama_user'])[0]) ?></span>
      <span style="font-size:10px;">&#9660;</span>
    </button>
    <div class="nav-user-dropdown" id="navUserDropdown">
      <div class="dropdown-header">
        <div class="dropdown-avatar"><?= strtoupper(mb_substr($_SESSION['user']['nama_user'], 0, 1)) ?></div>
        <div>
          <div style="font-weight:600;font-size:13px;color:var(--bark);"><?= htmlspecialchars($_SESSION['user']['nama_user']) ?></div>
          <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($_SESSION['user']['email_user']) ?></div>
        </div>
      </div>
      <div class="dropdown-divider"></div>
      <a href="<?= $base_path ?? '' ?>profil.php"   class="dropdown-item">&#128100; Profil Saya</a>
      <a href="<?= $base_path ?? '' ?>pesanan.php"  class="dropdown-item">&#128230; Pesanan Saya</a>
      <a href="<?= $base_path ?? '' ?>riwayat.php"  class="dropdown-item">&#128196; Riwayat Pesanan</a>
      <?php if ($_SESSION['user']['role'] === 'admin'): ?>
      <div class="dropdown-divider"></div>
      <a href="<?= $base_path ?? '' ?>admin/index.php" class="dropdown-item" style="color:var(--rose);">&#9881; Panel Admin</a>
      <?php endif; ?>
      <div class="dropdown-divider"></div>
      <a href="<?= $base_path ?? '' ?>logout.php" class="dropdown-item" style="color:#c0392b;">&#128682; Keluar</a>
    </div>
  </div>
  <?php else: ?>
  <!-- Belum login -->
  <div class="guest-dropdown">
  <button class="guest-btn" onclick="toggleGuestMenu()">
    &#128100;
  </button>

  <div class="guest-menu" id="guestMenu">
    <a href="<?= $base_path ?? '' ?>login.php">
      &#128274; Masuk
    </a>

    <a href="<?= $base_path ?? '' ?>register.php">
      &#10024; Daftar
    </a>
  </div>
  </div>
  <?php endif; ?>
</nav>

<style>
/* ── NAV USER DROPDOWN ── */
/* ── MODERN LOGO ── */
.modern-logo{
  display:flex;
  align-items:center;
  gap:10px;
  text-decoration:none;
}

.modern-logo img{
  width:42px;
  height:42px;
  object-fit:contain;
}

.modern-logo span{
  font-size:24px;
  font-weight:700;
  color:var(--bark);
  letter-spacing:.3px;
}

/* ── GUEST MENU ── */
.guest-dropdown{
  position:relative;
}

.guest-btn{
  width:42px;
  height:42px;
  border-radius:50%;
  border:1px solid var(--border);
  background:var(--white);
  cursor:pointer;

  display:flex;
  align-items:center;
  justify-content:center;

  font-size:18px;
  transition:.2s;
}

.guest-btn:hover{
  border-color:var(--rose);
  transform:translateY(-2px);
}

.guest-menu{
  position:absolute;
  top:52px;
  right:0;

  width:180px;
  background:white;

  border:1px solid var(--border);
  border-radius:12px;

  box-shadow:0 10px 30px rgba(0,0,0,.08);

  overflow:hidden;

  opacity:0;
  visibility:hidden;
  transform:translateY(10px);

  transition:.25s;
  z-index:300;
}

.guest-menu.open{
  opacity:1;
  visibility:visible;
  transform:translateY(0);
}

.guest-menu a{
  display:flex;
  align-items:center;
  gap:10px;

  padding:14px 16px;

  text-decoration:none;
  color:var(--text);
  font-size:14px;

  transition:.2s;
}

.guest-menu a:hover{
  background:var(--petal);
  color:var(--rose);
}
.nav-user-wrap { position: relative; }
.nav-user-btn {
  display: flex; align-items: center; gap: 8px;
  background: var(--petal); border: 1px solid var(--border);
  border-radius: 100px; padding: 5px 14px 5px 6px;
  cursor: pointer; font-family: 'DM Sans', sans-serif;
  font-size: 13px; font-weight: 500; color: var(--bark);
  transition: background .15s;
}
.nav-user-btn:hover { background: var(--rose-light); }
.nav-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--rose); color: white;
  font-size: 13px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.nav-user-name { max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.nav-user-dropdown {
  display: none;
  position: absolute; top: calc(100% + 8px); right: 0;
  background: white; border: 1px solid var(--border);
  border-radius: 12px; min-width: 210px;
  box-shadow: 0 8px 32px rgba(61,44,40,0.12);
  z-index: 200; overflow: hidden;
  animation: fadeDown .18s ease;
}
.nav-user-dropdown.open { display: block; }
@keyframes fadeDown {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.dropdown-header {
  padding: 14px 16px; display: flex; gap: 10px; align-items: center;
  background: var(--petal);
}
.dropdown-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--rose); color: white;
  font-size: 16px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dropdown-divider { height: 1px; background: var(--border); }
.dropdown-item {
  display: flex; align-items: center; gap: 9px;
  padding: 10px 16px; font-size: 13px; color: var(--text);
  transition: background .12s;
}
.dropdown-item:hover { background: var(--petal); }

.btn-login-nav {
  padding: 7px 18px; border-radius: 6px;
  border: 1.5px solid var(--bark); color: var(--bark);
  font-size: 13px; font-weight: 500;
  transition: all .15s;
}
.btn-login-nav:hover { background: var(--bark); color: white; }
.btn-register-nav {
  padding: 7px 18px; border-radius: 6px;
  background: var(--rose); color: white;
  font-size: 13px; font-weight: 500;
  transition: background .15s;
}
.btn-register-nav:hover { background: var(--rose-dark); }
</style>

<script>
function toggleUserMenu() {
  document.getElementById('navUserDropdown').classList.toggle('open');
}
// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('navUserWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('navUserDropdown')?.classList.remove('open');
  }
});

function toggleGuestMenu() {
  document.getElementById('guestMenu').classList.toggle('open');
}

document.addEventListener('click', function(e) {
  const guest = document.querySelector('.guest-dropdown');

  if (guest && !guest.contains(e.target)) {
    document.getElementById('guestMenu')?.classList.remove('open');
  }
});

</script>

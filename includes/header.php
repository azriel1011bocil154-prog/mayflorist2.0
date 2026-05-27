<?php
// includes/header.php
// Usage: include 'includes/header.php';
// Set $page_title and $active_nav before including

$page_title   = $page_title   ?? 'MayFlorist — Merangkai Senyum Lewat Bunga';
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
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="flower-burst-layer" id="burstLayer"></div>

<nav class="navbar">
  <button class="menu-toggle" id="menuToggle" aria-label="Buka Rangkaian Menu">
    <div class="hamburger-bar"></div>
    <div class="hamburger-bar"></div>
    <div class="hamburger-bar"></div>
  </button>

  <a class="logo" href="<?= $base_path ?? '' ?>index.php">
    <div class="logo-img-wrapper">
      <img src="<?= $base_path ?? '' ?>assets/images/logo.jpeg" alt="MayFlorist Logo">
    </div>
    <span>May<span class="brand-accent">Florist</span></span>
  </a>

  <div class="nav-menu-container" id="navMenuContainer">
    <form class="nav-search" action="<?= $base_path ?? '' ?>katalog.php" method="GET">
      <input type="text" name="q" placeholder="Cari buket impianmu di sini..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      <button type="submit" class="search-bloom-btn"><i class="fas fa-search"></i></button>
    </form>

    <ul class="nav-links">
      <li><a href="<?= $base_path ?? '' ?>index.php"    class="<?= $active_nav === 'home'    ? 'active' : '' ?>">Home</a></li>
      <li><a href="<?= $base_path ?? '' ?>katalog.php"  class="<?= $active_nav === 'produk'  ? 'active' : '' ?>">Katalog</a></li>
      <li><a href="<?= $base_path ?? '' ?>tentang.php"  class="<?= $active_nav === 'tentang' ? 'active' : '' ?>">Tentang</a></li>
      <li><a href="<?= $base_path ?? '' ?>kontak.php"   class="<?= $active_nav === 'kontak'  ? 'active' : '' ?>">Kontak</a></li>
    </ul>
  </div>

  <div class="nav-actions">
    <a href="<?= $base_path ?? '' ?>keranjang.php" class="nav-icon-btn cart-btn-bloom" title="Keranjang Belanjaanmu">
      <i class="fa-solid fa-basket-shopping"></i>
      <?php if ($cart_count > 0): ?>
        <span class="badge-petal"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>

    <?php if (!empty($_SESSION['user'])): ?>
    <div class="nav-user-wrap" id="navUserWrap">
      <button class="nav-user-btn" onclick="toggleUserMenu(event)">
        <span class="nav-avatar-flower"><?= strtoupper(mb_substr($_SESSION['user']['nama_user'], 0, 1)) ?></span>
        <span class="nav-user-name"><?= htmlspecialchars(explode(' ', $_SESSION['user']['nama_user'])[0]) ?></span>
        <span class="arrow-bloom">&#9662;</span>
      </button>
      <div class="nav-user-dropdown" id="navUserDropdown">
        <div class="dropdown-header-bloom">
          <div class="dropdown-avatar-bloom"><?= strtoupper(mb_substr($_SESSION['user']['nama_user'], 0, 1)) ?></div>
          <div>
            <div class="user-fullname"><?= htmlspecialchars($_SESSION['user']['nama_user']) ?></div>
            <div class="user-email">Pemberi Kebahagiaan 🌸</div>
          </div>
        </div>
        <div class="dropdown-divider-line"></div>
        <a href="<?= $base_path ?? '' ?>profil.php"   class="dropdown-item-bloom"><i class="far fa-user"></i> Taman Profil</a>
        <a href="<?= $base_path ?? '' ?>pesanan.php"  class="dropdown-item-bloom"><i class="fas fa-box"></i> Rangkaian Pesanan</a>
        <a href="<?= $base_path ?? '' ?>riwayat.php"  class="dropdown-item-bloom"><i class="fas fa-history"></i> Memori Belanja</a>
        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
        <div class="dropdown-divider-line"></div>
        <a href="<?= $base_path ?? '' ?>admin/index.php" class="dropdown-item-bloom admin-color"><i class="fas fa-gavel"></i> Ruang Florist Utama</a>
        <?php endif; ?>
        <div class="dropdown-divider-line"></div>
        <a href="<?= $base_path ?? '' ?>logout.php" class="dropdown-item-bloom logout-color"><i class="fas fa-sign-out-alt"></i> Pamit Keluar</a>
      </div>
    </div>
    <?php else: ?>
    <div class="guest-dropdown">
      <button class="guest-btn-bloom" onclick="toggleGuestMenu(event)" aria-label="Pintu Masuk">
        <i class="fa-solid fa-user"></i>
      </button>

      <div class="guest-menu-bloom" id="guestMenu">
        <a href="<?= $base_path ?? '' ?>login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="<?= $base_path ?? '' ?>register.php"><i class="fas fa-user-plus"></i> Daftar</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</nav>

<div class="nav-overlay" id="navOverlay"></div>

<style>
:root {
  --bloom-rose: #b76e79;
  --bloom-petal: #fff0f2;
  --bloom-dark: #3d2c28;
  --bloom-sage: #8fa89b;
  --bloom-white: #ffffff;
  --font-romantic: 'Playfair Display', serif;
  --font-clean: 'Plus Jakarta Sans', sans-serif;
}

body {
  font-family: var(--font-clean);
  color: var(--bloom-dark);
  margin: 0;
}

/* Base Navbar */
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 5%;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(15px);
  border-bottom: 1px solid rgba(183, 110, 121, 0.12);
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 80px;
  box-shadow: 0 4px 20px rgba(61, 44, 40, 0.03);
  box-sizing: border-box;
}

/* Logo */
.navbar .logo {
  display: flex;
  align-items: center;
  text-decoration: none;
  font-family: var(--font-romantic);
  font-size: 24px;
  font-weight: 600;
  color: var(--bloom-dark);
  transition: transform 0.3s ease;
}
.navbar .logo:hover {
  transform: scale(1.02);
}
.logo-img-wrapper {
  width: 80px;
  height: 80px;
}
.navbar .logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.brand-accent {
  color: var(--bloom-rose);
  font-style: italic;
  font-weight: 400;
}

/* Nav Menu Desktop */
.nav-menu-container {
  display: flex;
  align-items: center;
  gap: 40px;
  flex-grow: 1;
  justify-content: space-between;
  margin: 0 40px;
}
.nav-links {
  display: flex;
  list-style: none;
  gap: 28px;
  margin: 0;
  padding: 0;
}
.nav-links a {
  text-decoration: none;
  color: var(--bloom-dark);
  font-size: 15px;
  font-weight: 500;
  position: relative;
  padding: 8px 0;
  transition: color 0.3s;
}
.nav-links a::after {
  content: '🌸';
  position: absolute;
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%) scale(0);
  font-size: 8px;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.nav-links a:hover, .nav-links a.active {
  color: var(--bloom-rose);
}
.nav-links a:hover::after, .nav-links a.active::after {
  transform: translateX(-50%) scale(1.3);
}

/* Search Bar */
.nav-search {
  display: flex;
  align-items: center;
  position: relative;
  max-width: 300px;
  width: 100%;
  height: 40px;
}
.nav-search input {
  width: 100%;
  height: 100%;
  padding: 0 45px 0 16px;
  border: none;
  border-radius: 50px;
  font-size: 13px;
  outline: none;
  background: #f8f4f4;
  color: var(--bloom-dark);
  box-sizing: border-box;
}
.nav-search input:focus {
  background: white;
  box-shadow: 0 4px 16px rgba(183, 110, 121, 0.15);
}
.search-bloom-btn {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: var(--bloom-dark);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Actions */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}
.cart-btn-bloom {
  font-size: 20px;
  color: var(--bloom-dark);
  position: relative;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.3s;
  text-decoration: none;
}
.cart-btn-bloom:hover {
  background: var(--bloom-petal);
  color: var(--bloom-rose);
}
.badge-petal {
  position: absolute;
  top: 0;
  right: 0;
  background: var(--bloom-rose);
  color: white;
  font-size: 9px;
  font-weight: 700;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--bloom-white);
}

.nav-user-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bloom-petal);
  border: 1px solid rgba(183, 110, 121, 0.15);
  border-radius: 50px;
  padding: 4px 12px 4px 4px;
  cursor: pointer;
  color: var(--bloom-dark);
}
.nav-avatar-flower, .dropdown-avatar-bloom {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--bloom-rose), #db939f);
  color: white;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}
.nav-user-name { max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }

/* Dropdowns */
.guest-menu-bloom, .nav-user-dropdown {
  position: absolute;
  top: 70px;
  right: 5%;
  width: 220px;
  background: var(--bloom-white);
  border: 1px solid rgba(183, 110, 121, 0.1);
  border-radius: 14px;
  box-shadow: 0 15px 35px rgba(61, 44, 40, 0.08);
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: all 0.3s ease;
  z-index: 1100;
  overflow: hidden;
}
.guest-menu-bloom.open, .nav-user-dropdown.open {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.dropdown-header-bloom { padding: 12px; display: flex; gap: 10px; align-items: center; background: #fffafd; }
.dropdown-header-bloom .user-fullname { font-weight: 600; font-size: 13px; }
.dropdown-header-bloom .user-email { font-size: 11px; color: var(--bloom-sage); }
.dropdown-divider-line { height: 1px; background: rgba(183, 110, 121, 0.08); }
.dropdown-item-bloom, .guest-menu-bloom a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  text-decoration: none;
  color: var(--bloom-dark);
  font-size: 13.5px;
  transition: background 0.2s;
}
.dropdown-item-bloom:hover, .guest-menu-bloom a:hover { background: var(--bloom-petal); color: var(--bloom-rose); }
.logout-color { color: #c0392b; }

.guest-btn-bloom {
  width: 40px; height: 40px;
  border-radius: 50%;
  border: 1px solid rgba(183, 110, 121, 0.2);
  background: var(--bloom-white);
  color: var(--bloom-rose);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
}

/* Hamburger Menu Toggle */
.menu-toggle {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  z-index: 1300;
}
.hamburger-bar {
  width: 20px;
  height: 2px;
  background-color: var(--bloom-dark);
  border-radius: 2px;
  transition: 0.3s;
}

.flower-burst-layer { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }

/* Overlay */
.nav-overlay {
  position: fixed;
  background: rgba(61, 44, 40, 0.4);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0;
  visibility: hidden;
  transition: 0.3s ease;
  z-index: 1150;
}
.nav-overlay.open { opacity: 1; visibility: visible; }

/* ===================================================
    RESPONSIVE MOBILE DISINI (ANTI TABRAKAN & SETENGAH LAYAR)
=================================================== */
@media (max-width: 992px) {
  .menu-toggle { display: flex; order: 1; }
  
  /* FIX UTAMA: Logo digeser ke kiri berdampingan dengan hamburger, aman dari tabrakan */
  .navbar .logo {
    
    position: static;
    transform: none;
    order: 2;
    margin-left: 8px;
    margin-right: auto; /* Mendorong keranjang & user ke paling kanan secara aman */
  }
  .navbar .logo span { font-size: 18px; }
  .logo-img-wrapper { width: 60px; height: 60px; }

  .nav-actions { order: 3; gap: 8px; }
  .nav-user-name, .arrow-bloom { display: none; }
  .nav-user-btn { padding: 0; border: none; background: transparent; }
  
  /* FIX DRAWER: Setengah Layar (50%) & Bersih di atas blur */
  .nav-menu-container {
    display: flex;
    position: fixed;
    top: 0;
    left: -100%;
    width: 50%;
    min-width: 270px; /* Batas minimal aman di HP super kecil */
    height: 100vh;
    background: var(--bloom-white);
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 90px 20px 30px 20px;
    margin: 0;
    gap: 25px;
    box-shadow: 10px 0 30px rgba(61, 44, 40, 0.15);
    transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1200; 
    border-radius: 0 20px 20px 0;
    box-sizing: border-box;
  }
  .nav-menu-container.open { left: 0; }

  .nav-links { flex-direction: column; width: 100%; gap: 10px; }
  .nav-links a {
    font-size: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    background: #fdfafb;
    width: 100%;
    box-sizing: border-box;
    display: block;
  }
  .nav-links a.active { background: var(--bloom-petal); color: var(--bloom-rose); font-weight: 600; }
  .nav-links a::after { display: none; }

  .nav-search { max-width: 100%; order: -1; margin-bottom: 5px; }

  /* Animasi Hamburger */
  .menu-toggle.active .hamburger-bar:nth-child(1) { transform: translateY(7px) rotate(45deg); }
  .menu-toggle.active .hamburger-bar:nth-child(2) { opacity: 0; }
  .menu-toggle.active .hamburger-bar:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenuContainer = document.getElementById('navMenuContainer');
    const navOverlay = document.getElementById('navOverlay');

    if (menuToggle && navMenuContainer) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            menuToggle.classList.toggle('active');
            navMenuContainer.classList.toggle('open');
            if(navOverlay) navOverlay.classList.toggle('open');
        });
    }

    // Tutup menu otomatis jika area luar atau overlay diklik
    window.addEventListener('click', function(e) {
        const userDropdown = document.getElementById('navUserDropdown');
        const guestMenu = document.getElementById('guestMenu');
        
        if (userDropdown && !userDropdown.contains(e.target) && !e.target.closest('.nav-user-btn')) {
            userDropdown.classList.remove('open');
        }
        
        if (guestMenu && !guestMenu.contains(e.target) && !e.target.closest('.guest-btn-bloom')) {
            guestMenu.classList.remove('open');
        }

        if (navMenuContainer && navMenuContainer.classList.contains('open')) {
            if (!navMenuContainer.contains(e.target) && !e.target.closest('#menuToggle')) {
                navMenuContainer.classList.remove('open');
                if(menuToggle) menuToggle.classList.remove('active');
                if(navOverlay) navOverlay.classList.remove('open');
            }
        }
    });
});

function toggleUserMenu(event) {
    event.stopPropagation();
    const userDropdown = document.getElementById('navUserDropdown');
    const guestMenu = document.getElementById('guestMenu');
    if(userDropdown) userDropdown.classList.toggle('open');
    if(guestMenu) guestMenu.classList.remove('open');
}

function toggleGuestMenu(event) {
    event.stopPropagation();
    const guestMenu = document.getElementById('guestMenu');
    const userDropdown = document.getElementById('navUserDropdown');
    if(guestMenu) guestMenu.classList.toggle('open');
    if(userDropdown) userDropdown.classList.remove('open');
}

// 🌸 Efek Kejutan Klik Kelopak Bunga
document.querySelectorAll('.nav-links a, .nav-icon-btn, .nav-user-btn, .guest-btn-bloom').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const layer = document.getElementById('burstLayer');
    const flowers = ['🌸', '✨', '🍃'];
    for (let i = 0; i < 8; i++) {
      const p = document.createElement('span');
      p.innerText = flowers[Math.floor(Math.random() * flowers.length)];
      p.style.position = 'fixed';
      p.style.left = e.clientX + 'px';
      p.style.top = e.clientY + 'px';
      p.style.fontSize = Math.random() * (15 - 10) + 10 + 'px';
      p.style.transition = 'all 0.8s cubic-bezier(0.1, 0.8, 0.3, 1)';
      p.style.pointerEvents = 'none';
      layer.appendChild(p);
      
      const angle = Math.random() * Math.PI * 2;
      const distance = Math.random() * 50 + 20;
      const x = Math.cos(angle) * distance;
      const y = Math.sin(angle) * distance;
      
      setTimeout(() => {
        p.style.transform = `translate(${x}px, ${y}px) scale(0) rotate(${Math.random() * 180}deg)`;
        p.style.opacity = '0';
      }, 10);
      setTimeout(() => p.remove(), 800);
    }
  });
});
</script>
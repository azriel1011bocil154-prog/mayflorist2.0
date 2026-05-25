<?php
// register.php

session_start();
include 'koneksi.php';

// Jika sudah login
if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama      = trim($_POST['nama_user'] ?? '');
    $jk        = trim($_POST['jenis_kelamin'] ?? '');
    $alamat    = trim($_POST['alamat_user'] ?? '');
    $telepon   = trim($_POST['telepon_user'] ?? '');
    $email     = trim($_POST['email_user'] ?? '');
    $password  = trim($_POST['password_user'] ?? '');
    $konfirmasi= trim($_POST['konfirmasi_password'] ?? '');

    // Validasi
    if (
        empty($nama) ||
        empty($jk) ||
        empty($alamat) ||
        empty($telepon) ||
        empty($email) ||
        empty($password) ||
        empty($konfirmasi)
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak cocok.";

    } else {

        // Cek email sudah ada atau belum
        $check = mysqli_prepare(
            $conn,
            "SELECT id_user FROM user WHERE email_user = ?"
        );

        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {

            $error = "Email sudah digunakan.";

        } else {

            // Simpan user baru
            // sementara plain text
            // nanti bisa pakai password_hash()

            $role = 'customer';

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO user
                (
                    nama_user,
                    jenis_kelamin,
                    alamat_user,
                    telepon_user,
                    email_user,
                    password_user,
                    role
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $insert,
                "sssssss",
                $nama,
                $jk,
                $alamat,
                $telepon,
                $email,
                $password,
                $role
            );

            if (mysqli_stmt_execute($insert)) {

                $success = "Registrasi berhasil. Silakan login.";

            } else {

                $error = "Registrasi gagal.";
            }
        }
    }
} 

$page_title = 'Daftar Akun — MayFlorist';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: var(--petal); }

    .auth-wrapper {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 40px 16px;
    }

    .auth-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: 16px;
      width: 100%; max-width: 480px;
      padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(61,44,40,0.08);
    }

    .auth-logo {
      text-align: center; margin-bottom: 28px;
    }
    .auth-logo a {
      font-family: 'Playfair Display', serif;
      font-size: 28px; color: var(--bark); text-decoration: none;
    }
    .auth-logo a span { color: var(--rose); font-style: italic; }
    .auth-logo p { font-size: 14px; color: var(--muted); margin-top: 4px; }

    .form-group { margin-bottom: 15px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 500;
      color: var(--bark); margin-bottom: 5px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border); border-radius: 7px;
      font-family: 'DM Sans', sans-serif; font-size: 14px;
      color: var(--text); outline: none; background: white;
      transition: border-color .2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--rose); }
    .form-group textarea { resize: vertical; min-height: 76px; }
    .form-group select { appearance: auto; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 44px; }
    .toggle-pass {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      font-size: 17px; color: var(--muted); padding: 4px;
    }

    .strength-bar {
      height: 4px; border-radius: 2px; margin-top: 6px;
      background: var(--border); overflow: hidden;
    }
    .strength-fill {
      height: 100%; border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }
    .strength-label { font-size: 11px; color: var(--muted); margin-top: 3px; }

    .btn-auth {
      width: 100%; padding: 13px;
      background: var(--bark); color: white;
      border: none; border-radius: 7px; cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px; font-weight: 600;
      transition: background .2s, transform .15s;
      margin-top: 4px;
    }
    .btn-auth:hover { background: var(--rose-dark); transform: translateY(-1px); }

    .auth-error {
      background: #fdeaea; color: #9b2020;
      border: 1px solid #f5c6c6; border-radius: 7px;
      padding: 10px 14px; font-size: 13px;
      margin-bottom: 16px;
    }
    .auth-error ul { margin-left: 16px; line-height: 1.8; }

    .auth-success {
      background: #eaf7ee; color: #256d3f;
      border: 1px solid #b3e4c2; border-radius: 7px;
      padding: 16px; font-size: 14px; text-align: center;
    }
    .auth-success .success-icon { font-size: 36px; margin-bottom: 8px; }

    .auth-footer {
      text-align: center; margin-top: 20px;
      font-size: 14px; color: var(--muted);
    }
    .auth-footer a { color: var(--rose); font-weight: 500; }
    .auth-footer a:hover { text-decoration: underline; }

    .required-note {
      font-size: 12px; color: var(--muted);
      margin-bottom: 16px;
    }
    .required-note span { color: var(--rose); }

    @media (max-width: 480px) {
      .auth-card { padding: 28px 20px; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="auth-wrapper">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <a href="index.php">May<span>Florist</span></a>
      <p>Buat akun baru</p>
    </div>

    <?php if ($success): ?>
    <!-- ── SUKSES ── -->
    <div class="auth-success">
      <div class="success-icon">&#127800;</div>
      <strong>Pendaftaran Berhasil!</strong>
      <p style="margin-top:6px;color:#3a7d52;">
        Akun kamu sudah dibuat. Silakan masuk untuk mulai belanja.
      </p>
      <a href="login.php"
         style="display:inline-block;margin-top:14px;padding:10px 28px;background:#256d3f;color:white;border-radius:7px;font-weight:600;font-size:14px;text-decoration:none;">
        Masuk Sekarang
      </a>
    </div>

    <?php else: ?>
    <!-- ── FORM ── -->

    <?php if (!empty($error)): ?>
    <div class="auth-error">
      <strong>&#9888; Harap perbaiki kesalahan berikut:</strong>
      <ul>
        <li><?= htmlspecialchars($error) ?></li>
      </ul>
    </div>
    <?php endif; ?>

    <p class="required-note">Kolom bertanda <span>*</span> wajib diisi.</p>

    <form method="POST" novalidate>

      <!-- Nama -->
      <div class="form-group">
        <label>Nama Lengkap <span style="color:var(--rose)">*</span></label>
        <input type="text" name="nama_user"
               placeholder="Nama lengkap sesuai identitas"
               value="<?= htmlspecialchars($old['nama_user'] ?? '') ?>"
               required>
      </div>

      <!-- JK + Telepon -->
      <div class="form-row">
        <div class="form-group">
          <label>Jenis Kelamin <span style="color:var(--rose)">*</span></label>
          <select name="jenis_kelamin" required>
            <option value="" disabled <?= empty($old['jenis_kelamin']) ? 'selected' : '' ?>>Pilih...</option>
            <option value="Laki-laki"  <?= ($old['jenis_kelamin'] ?? '') === 'Laki-laki'  ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan"  <?= ($old['jenis_kelamin'] ?? '') === 'Perempuan'  ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nomor Telepon <span style="color:var(--rose)">*</span></label>
          <input type="tel" name="telepon_user"
                 placeholder="08xx-xxxx-xxxx"
                 value="<?= htmlspecialchars($old['telepon_user'] ?? '') ?>"
                 required>
        </div>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label>Email <span style="color:var(--rose)">*</span></label>
        <input type="email" name="email_user"
               placeholder="contoh@email.com"
               value="<?= htmlspecialchars($old['email_user'] ?? '') ?>"
               autocomplete="email" required>
      </div>

      <!-- Alamat -->
      <div class="form-group">
        <label>Alamat</label>
        <textarea name="alamat_user"
                  placeholder="Alamat lengkap (opsional, bisa diisi nanti)"><?= htmlspecialchars($old['alamat_user'] ?? '') ?></textarea>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label>Kata Sandi <span style="color:var(--rose)">*</span></label>
        <div class="password-wrap">
          <input type="password" id="password_user" name="password_user"
                 placeholder="Minimal 8 karakter"
                 oninput="checkStrength(this.value)"
                 autocomplete="new-password" required>
          <button type="button" class="toggle-pass"
                  onclick="togglePassword('password_user', this)">&#128065;</button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-label" id="strengthLabel"></div>
      </div>

      <!-- Konfirmasi Password -->
      <div class="form-group">
        <label>Konfirmasi Kata Sandi <span style="color:var(--rose)">*</span></label>
        <div class="password-wrap">
          <input type="password" id="konfirm_password" name="konfirmasi_password"
                 placeholder="Ulangi kata sandi"
                 autocomplete="new-password" required>
          <button type="button" class="toggle-pass"
                  onclick="togglePassword('konfirm_password', this)">&#128065;</button>
        </div>
        <div id="matchMsg" style="font-size:12px;margin-top:4px;"></div>
      </div>

      <button type="submit" class="btn-auth">Daftar Sekarang</button>
    </form>

    <div class="auth-footer">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </div>

    <div style="text-align:center;margin-top:12px;">
      <a href="index.php" style="font-size:13px;color:var(--muted);">
        &#8592; Kembali ke Beranda
      </a>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
function togglePassword(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.innerHTML = inp.type === 'password' ? '&#128065;' : '&#128064;';
}

function checkStrength(val) {
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8)              score++;
  if (/[A-Z]/.test(val))           score++;
  if (/[0-9]/.test(val))           score++;
  if (/[^A-Za-z0-9]/.test(val))   score++;

  const levels = [
    { w: '0%',   bg: 'transparent', text: '' },
    { w: '25%',  bg: '#e74c3c',     text: 'Sangat Lemah' },
    { w: '50%',  bg: '#e67e22',     text: 'Lemah' },
    { w: '75%',  bg: '#f1c40f',     text: 'Cukup' },
    { w: '100%', bg: '#27ae60',     text: 'Kuat' },
  ];
  fill.style.width      = levels[score].w;
  fill.style.background = levels[score].bg;
  label.textContent     = levels[score].text;
  label.style.color     = levels[score].bg;
}

// Real-time konfirmasi password
document.getElementById('konfirm_password').addEventListener('input', function() {
  const pass = document.getElementById('password_user').value;
  const msg  = document.getElementById('matchMsg');
  if (!this.value) { msg.textContent = ''; return; }
  if (this.value === pass) {
    msg.textContent = '✓ Kata sandi cocok';
    msg.style.color = '#27ae60';
  } else {
    msg.textContent = '✗ Kata sandi tidak cocok';
    msg.style.color = '#e74c3c';
  }
});
</script>

</body>
</html>

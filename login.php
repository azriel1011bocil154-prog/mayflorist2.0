<?php
// login.php

session_start();
include 'koneksi.php';

// Jika sudah login
if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi input
    if (empty($email) || empty($password)) {

        $error = "Email dan password wajib diisi.";

    } else {

        // Query ambil user
        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM user WHERE email_user = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        // Jika user ditemukan
        if (mysqli_num_rows($result) > 0) {

            $user = mysqli_fetch_assoc($result);

            // Cek password
            // sementara plain text
            if ($password === $user['password_user']) {

                // Simpan session
                $_SESSION['login'] = true;

                $_SESSION['user'] = [
                    'id_user'    => $user['id_user'],
                    'nama_user'  => $user['nama_user'],
                    'email_user' => $user['email_user'],
                    'role'       => $user['role']
                ];

                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {

                    header("Location: admin/index.php");

                } elseif ($user['role'] === 'owner') {

                    header("Location: owner/index.php");

                } else {

                    header("Location: index.php");
                }

                exit;

            } else {

                $error = "Password salah.";
            }

        } else {

            $error = "Email tidak ditemukan.";
        }
    }
}
$page_title = 'Login — MayFlorist';
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
      width: 100%; max-width: 420px;
      padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(61,44,40,0.08);
    }

    .auth-logo {
      text-align: center; margin-bottom: 28px;
    }
    .auth-logo a {
      font-family: 'Playfair Display', serif;
      font-size: 28px; color: var(--bark);
      text-decoration: none;
    }
    .auth-logo a span { color: var(--rose); font-style: italic; }
    .auth-logo p {
      font-size: 14px; color: var(--muted); margin-top: 4px;
    }

    .auth-title {
      font-size: 20px; color: var(--bark);
      margin-bottom: 22px; text-align: center;
    }

    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 500;
      color: var(--bark); margin-bottom: 5px;
    }
    .form-group input {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border); border-radius: 7px;
      font-family: 'DM Sans', sans-serif; font-size: 14px;
      color: var(--text); outline: none; background: white;
      transition: border-color .2s;
    }
    .form-group input:focus { border-color: var(--rose); }

    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 44px; }
    .toggle-pass {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      font-size: 17px; color: var(--muted); padding: 4px;
    }

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
      margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }

    .auth-footer {
      text-align: center; margin-top: 20px;
      font-size: 14px; color: var(--muted);
    }
    .auth-footer a { color: var(--rose); font-weight: 500; }
    .auth-footer a:hover { text-decoration: underline; }

    .divider-text {
      display: flex; align-items: center; gap: 10px;
      margin: 18px 0; color: var(--muted); font-size: 13px;
    }
    .divider-text::before, .divider-text::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }
  </style>
</head>
<body>

<div class="auth-wrapper">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <a href="index.php">May<span>Florist</span></a>
      <p>Masuk ke akun Anda</p>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="auth-error">&#9888; <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="login.php">

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               placeholder="contoh@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               autocomplete="email" required>
      </div>

      <div class="form-group">
        <label for="password">Kata Sandi</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password"
                 placeholder="••••••••"
                 autocomplete="current-password" required>
          <button type="button" class="toggle-pass" onclick="togglePassword('password', this)">
            &#128065;
          </button>
        </div>
      </div>

      <div style="text-align:right;margin:-8px 0 16px;">
        <a href="lupa-password.php" style="font-size:13px;color:var(--rose);">Lupa kata sandi?</a>
      </div>

      <button type="submit" class="btn-auth">Masuk</button>
    </form>

    <div class="divider-text">atau</div>

    <div class="auth-footer">
      Belum punya akun?
      <a href="register.php">Daftar sekarang</a>
    </div>

    <div style="text-align:center;margin-top:16px;">
      <a href="index.php" style="font-size:13px;color:var(--muted);">
        &#8592; Kembali ke Beranda
      </a>
    </div>

  </div>
</div>


<script>
function togglePassword(id, btn) {

  const inp = document.getElementById(id);

  if (inp.type === 'password') {

    inp.type = 'text';
    btn.innerHTML = '🙈';

  } else {

    inp.type = 'password';
    btn.innerHTML = '👁';
  }
}

</script>

</body>
</html>

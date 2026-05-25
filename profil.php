<?php
// profil.php — Halaman Profil User

session_start();

// Proteksi halaman — wajib login
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'profil.php';
    header('Location: login.php');
    exit;
}

$user  = $_SESSION['user'];
$alert = '';

// ── Dummy data user lengkap (nanti dari DB: SELECT * FROM users WHERE id_user = ?) ──
$user_data = [
    'id_user'       => $user['id_user'],
    'nama_user'     => $user['nama_user'],
    'jenis_kelamin' => 'Perempuan',
    'alamat_user'   => 'Jl. Melati No. 12, Jakarta Selatan',
    'telepon_user'  => '0812-3456-7890',
    'email_user'    => $user['email_user'],
    'role'          => $user['role'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama    = trim($_POST['nama_user']    ?? '');
        $jk      = $_POST['jenis_kelamin']     ?? '';
        $alamat  = trim($_POST['alamat_user']  ?? '');
        $telepon = trim($_POST['telepon_user'] ?? '');
        $email   = trim($_POST['email_user']   ?? '');

        if (!$nama || !$email) {
            $alert = ['type'=>'error', 'msg'=>'Nama dan email wajib diisi.'];
        } else {
            // Nanti: UPDATE users SET ... WHERE id_user = ?
            $_SESSION['user']['nama_user']  = $nama;
            $_SESSION['user']['email_user'] = $email;
            $user_data = array_merge($user_data, compact('nama','jk','alamat','telepon','email'));
            $user_data['nama_user']     = $nama;
            $user_data['email_user']    = $email;
            $user_data['jenis_kelamin'] = $jk;
            $user_data['alamat_user']   = $alamat;
            $user_data['telepon_user']  = $telepon;
            $alert = ['type'=>'success', 'msg'=>'Profil berhasil diperbarui!'];
        }
    }

    if ($action === 'ubah_password') {
        $lama    = $_POST['pass_lama']   ?? '';
        $baru    = $_POST['pass_baru']   ?? '';
        $konfirm = $_POST['pass_konfirm']?? '';

        if (!$lama || !$baru || !$konfirm) {
            $alert = ['type'=>'error', 'msg'=>'Semua kolom kata sandi wajib diisi.'];
        } elseif (strlen($baru) < 8) {
            $alert = ['type'=>'error', 'msg'=>'Kata sandi baru minimal 8 karakter.'];
        } elseif ($baru !== $konfirm) {
            $alert = ['type'=>'error', 'msg'=>'Konfirmasi kata sandi tidak cocok.'];
        } else {
            // Nanti: verifikasi pass_lama, lalu UPDATE password_user WHERE id_user = ?
            $alert = ['type'=>'success', 'msg'=>'Kata sandi berhasil diubah!'];
        }
    }
}

include 'includes/products.php';
$page_title = 'Profil Saya — Fleuriste';
$active_nav = '';
include 'includes/header.php';
?>

<div class="page-wrapper" style="padding-top:36px;padding-bottom:64px;">
  <h1 style="font-size:26px;margin-bottom:28px;">Profil Saya</h1>

  <?php if ($alert): ?>
  <div class="alert <?= $alert['type'] === 'success' ? 'alert-success' : '' ?>"
       style="<?= $alert['type'] === 'error' ? 'background:#fdeaea;color:#9b2020;border:1px solid #f5c6c6;' : '' ?>
              border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;">
    <?= $alert['type'] === 'success' ? '&#10003;' : '&#9888;' ?>
    <?= htmlspecialchars($alert['msg']) ?>
  </div>
  <?php endif; ?>

  <div class="profil-layout">

    <!-- ── KIRI: Avatar + Navigasi Tab ── -->
    <aside class="profil-sidebar">
      <div class="profil-avatar-box">
        <div class="profil-avatar">
          <?= strtoupper(mb_substr($user_data['nama_user'], 0, 1)) ?>
        </div>
        <div class="profil-name"><?= htmlspecialchars($user_data['nama_user']) ?></div>
        <div class="profil-email"><?= htmlspecialchars($user_data['email_user']) ?></div>
        <span class="profil-role-badge">
          <?= $user_data['role'] === 'admin' ? '&#9881; Admin' : '&#128100; Pelanggan' ?>
        </span>
      </div>

      <nav class="profil-nav">
        <a href="#data-diri"    class="profil-nav-item active" onclick="switchTab('data-diri', this)">
          &#128100; Data Diri
        </a>
        <a href="#ubah-password" class="profil-nav-item" onclick="switchTab('ubah-password', this)">
          &#128274; Ubah Kata Sandi
        </a>
        <a href="pesanan.php"   class="profil-nav-item">
          &#128230; Pesanan Saya
        </a>
        <a href="riwayat.php"   class="profil-nav-item">
          &#128196; Riwayat Pesanan
        </a>
        <a href="logout.php"    class="profil-nav-item profil-nav-logout">
          &#128682; Keluar
        </a>
      </nav>
    </aside>

    <!-- ── KANAN: Konten Tab ── -->
    <div class="profil-content">

      <!-- Tab: Data Diri -->
      <div id="tab-data-diri" class="profil-tab active">
        <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:28px;">
          <h2 style="font-size:18px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
            Data Diri
          </h2>
          <form method="POST" action="profil.php">
            <input type="hidden" name="action" value="update_profil">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label>Nama Lengkap <span style="color:var(--rose)">*</span></label>
                <input type="text" name="nama_user" class="form-control"
                       value="<?= htmlspecialchars($user_data['nama_user']) ?>" required>
              </div>
              <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                  <option value="Laki-laki"  <?= $user_data['jenis_kelamin'] === 'Laki-laki'  ? 'selected' : '' ?>>Laki-laki</option>
                  <option value="Perempuan"  <?= $user_data['jenis_kelamin'] === 'Perempuan'  ? 'selected' : '' ?>>Perempuan</option>
                </select>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label>Email <span style="color:var(--rose)">*</span></label>
                <input type="email" name="email_user" class="form-control"
                       value="<?= htmlspecialchars($user_data['email_user']) ?>" required>
              </div>
              <div class="form-group">
                <label>Nomor Telepon</label>
                <input type="tel" name="telepon_user" class="form-control"
                       value="<?= htmlspecialchars($user_data['telepon_user']) ?>"
                       placeholder="08xx-xxxx-xxxx">
              </div>
            </div>

            <div class="form-group">
              <label>Alamat</label>
              <textarea name="alamat_user" class="form-control" rows="3"><?= htmlspecialchars($user_data['alamat_user']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:11px 32px;">
              Simpan Perubahan
            </button>
          </form>
        </div>
      </div>

      <!-- Tab: Ubah Password -->
      <div id="tab-ubah-password" class="profil-tab">
        <div style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:28px;max-width:420px;">
          <h2 style="font-size:18px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">
            Ubah Kata Sandi
          </h2>
          <form method="POST" action="profil.php">
            <input type="hidden" name="action" value="ubah_password">

            <div class="form-group">
              <label>Kata Sandi Lama</label>
              <div style="position:relative;">
                <input type="password" name="pass_lama" id="pass_lama" class="form-control"
                       placeholder="••••••••" style="padding-right:44px;" required>
                <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--muted);"
                        onclick="togglePass('pass_lama')">&#128065;</button>
              </div>
            </div>

            <div class="form-group">
              <label>Kata Sandi Baru</label>
              <div style="position:relative;">
                <input type="password" name="pass_baru" id="pass_baru" class="form-control"
                       placeholder="Minimal 8 karakter" style="padding-right:44px;" required>
                <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--muted);"
                        onclick="togglePass('pass_baru')">&#128065;</button>
              </div>
            </div>

            <div class="form-group">
              <label>Konfirmasi Kata Sandi Baru</label>
              <div style="position:relative;">
                <input type="password" name="pass_konfirm" id="pass_konfirm" class="form-control"
                       placeholder="Ulangi kata sandi baru" style="padding-right:44px;" required>
                <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--muted);"
                        onclick="togglePass('pass_konfirm')">&#128065;</button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:11px 32px;">
              Ubah Kata Sandi
            </button>
          </form>
        </div>
      </div>

    </div><!-- /profil-content -->
  </div><!-- /profil-layout -->
</div>

<?php include 'includes/footer.php'; ?>

<style>
.profil-layout {
  display: grid; grid-template-columns: 240px 1fr;
  gap: 24px; align-items: start;
}
.profil-sidebar {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 10px; overflow: hidden;
}
.profil-avatar-box {
  background: var(--petal); padding: 24px 20px;
  text-align: center; border-bottom: 1px solid var(--border);
}
.profil-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  background: var(--rose); color: white;
  font-size: 28px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 12px;
}
.profil-name { font-weight: 600; font-size: 15px; color: var(--bark); margin-bottom: 2px; }
.profil-email { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.profil-role-badge {
  display: inline-block; font-size: 11px; font-weight: 600;
  padding: 3px 10px; border-radius: 100px;
  background: var(--rose-light); color: var(--rose-dark);
}
.profil-nav { padding: 8px 0; }
.profil-nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 20px; font-size: 14px; color: var(--muted);
  border-left: 3px solid transparent;
  transition: all .15s; cursor: pointer;
}
.profil-nav-item:hover { background: var(--petal); color: var(--bark); }
.profil-nav-item.active { color: var(--rose); border-left-color: var(--rose); background: var(--rose-light); font-weight:500; }
.profil-nav-logout { color: #c0392b !important; }
.profil-nav-logout:hover { background: #fdeaea !important; }

.profil-tab { display: none; }
.profil-tab.active { display: block; }

.form-control {
  width: 100%; padding: 10px 13px;
  border: 1px solid var(--border); border-radius: 6px;
  font-family: 'DM Sans', sans-serif; font-size: 14px;
  color: var(--text); background: white; outline: none;
  transition: border-color .2s;
}
.form-control:focus { border-color: var(--rose); }
textarea.form-control { resize: vertical; min-height: 80px; }
.form-group { margin-bottom: 16px; }
.form-group label {
  display: block; font-size: 13px; font-weight: 500;
  color: var(--bark); margin-bottom: 5px;
}

@media (max-width: 768px) {
  .profil-layout { grid-template-columns: 1fr; }
}
</style>

<script>
function switchTab(id, el) {
  event.preventDefault();
  document.querySelectorAll('.profil-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.profil-nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  el.classList.add('active');
}
function togglePass(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>

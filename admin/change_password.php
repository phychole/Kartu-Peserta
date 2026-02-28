<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$pdo = db();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';

    if ($new === '' || strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new !== $new2) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($old, $u['password_hash'])) {
            $error = 'Password lama salah.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->execute([$hash, $u['id']]);
            $msg = 'Password berhasil diubah.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ganti Password - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Ganti Password</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Beranda</a>
      <a class="danger" href="<?= url('/auth/logout.php') ?>">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2>Ubah Password</h2>
      <?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" autocomplete="off">
        <label>Password Lama</label>
        <input type="password" name="old_password" required>

        <label>Password Baru</label>
        <input type="password" name="new_password" required>

        <label>Ulangi Password Baru</label>
        <input type="password" name="new_password2" required>

        <button type="submit">Simpan Password</button>
      </form>
    </section>
  </main>
</body>
</html>

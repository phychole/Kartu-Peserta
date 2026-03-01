<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$msg=''; $error='';

$nama_ujian = get_setting('nama_ujian', 'Penilaian Sumatif Akhir Jenjang');
$tahun_pelajaran = get_setting('tahun_pelajaran', '2025 - 2026');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_ujian = trim($_POST['nama_ujian'] ?? '');
  $tahun_pelajaran = trim($_POST['tahun_pelajaran'] ?? '');

  if ($nama_ujian === '' || $tahun_pelajaran === '') {
    $error = 'Nama ujian dan tahun pelajaran wajib diisi.';
  } else {
    set_setting('nama_ujian', $nama_ujian);
    set_setting('tahun_pelajaran', $tahun_pelajaran);
    $msg = 'Pengaturan ujian berhasil disimpan.';
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengaturan Ujian - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
<header class="topbar">
  <div>
    <strong>PSAJ - Pengaturan Ujian</strong>
    <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
  </div>
  <nav>
    <a href="<?= url('/admin/dashboard.php') ?>">Beranda</a>
    <a class="danger" href="<?= url('/auth/logout.php') ?>">Logout</a>
  </nav>
</header>

<main class="container">
  <section class="panel">
    <h2>Pengaturan Nama Ujian & Tahun Pelajaran</h2>

    <?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <label>Nama Ujian</label>
      <input type="text" name="nama_ujian" value="<?= e($nama_ujian) ?>" placeholder="contoh: Penilaian Tengah Semester" required>

      <label>Tahun Pelajaran</label>
      <input type="text" name="tahun_pelajaran" value="<?= e($tahun_pelajaran) ?>" placeholder="contoh: 2025 - 2026" required>

      <button type="submit">Simpan</button>
    </form>

    <p class="muted" style="margin-top:10px;">
      Semua halaman cetak akan otomatis menyesuaikan pengaturan ini.
    </p>
  </section>
</main>
</body>
</html>
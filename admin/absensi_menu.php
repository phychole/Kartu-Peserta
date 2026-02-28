<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$kelasList = db()->query("SELECT DISTINCT kelas FROM peserta ORDER BY kelas")->fetchAll();
$ruangList = db()->query("SELECT DISTINCT ruang FROM peserta ORDER BY ruang")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cetak Absensi - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Cetak Absensi</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
     <!-- <a href="<?= url('/admin/peserta.php') ?>">Data Peserta</a> -->
      <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2>Cetak Daftar Hadir</h2>

      <h3 style="margin-top:10px;">1) Per Ruang (1 lembar)</h3>
      <form class="form-row" method="get" action="<?= url('/admin/print_absensi.php') ?>" target="_blank">
        <label>Ruang</label>
        <select name="ruang" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
          <option value="">-- pilih ruang --</option>
          <?php foreach ($ruangList as $r): ?>
            <option value="<?= e($r['ruang']) ?>"><?= e($r['ruang']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Kelas</label>
        <select name="kelas" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
          <option value="">(opsional) semua kelas</option>
          <?php foreach ($kelasList as $k): ?>
            <option value="<?= e($k['kelas']) ?>"><?= e($k['kelas']) ?></option>
          <?php endforeach; ?>
        </select>

        <button type="submit">Cetak</button>
      </form>

      <h3 style="margin-top:18px;">2) Semua Ruang (tiap ruang 1 lembar)</h3>
      <form class="form-row" method="get" action="<?= url('/admin/print_absensi_all.php') ?>" target="_blank">
        <label>Kelas</label>
        <select name="kelas" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
          <option value="">(opsional) semua kelas</option>
          <?php foreach ($kelasList as $k): ?>
            <option value="<?= e($k['kelas']) ?>"><?= e($k['kelas']) ?></option>
          <?php endforeach; ?>
        </select>

        <button type="submit">Cetak Semua Ruang</button>
      </form>
    </section>
  </main>
</body>
</html>
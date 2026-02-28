<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$ruangList = db()->query("SELECT DISTINCT ruang FROM peserta ORDER BY ruang")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Denah Duduk - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
  <style>
    .denah-form{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 18px;
      margin-top: 20px;
    }
    .denah-form .field{ display:flex; flex-direction:column; gap:6px; }
    .denah-form label{ font-weight:600; font-size:14px; }
    .denah-form input, .denah-form select{
      padding:10px 12px;
      border:1px solid var(--border);
      border-radius:12px;
      font-size:14px;
      width:100%;
    }
    .denah-form .full{ grid-column: 1 / -1; }
    .denah-form button{
      padding:12px;
      border-radius:14px;
      border:none;
      background:#111827;
      color:white;
      font-weight:700;
      cursor:pointer;
    }
  </style>
</head>
<body class="page">
<header class="topbar">
  <div>
    <strong>PSAJ - Denah Duduk</strong>
    <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
  </div>
  <nav>
    <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>

    <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
  </nav>
</header>

<main class="container">
  <section class="panel">
    <h2>Cetak Denah Duduk</h2>
    <p class="muted">Pilih ruang, jumlah meja, dan posisi pintu. Pola duduk zigzag dengan gang setiap 2 kolom.</p>

    <form method="get" action="<?= url('/admin/print_denah.php') ?>" target="_blank" class="denah-form">

      <div class="field">
        <label>Ruang</label>
        <select name="ruang" required>
          <option value="">-- pilih ruang --</option>
          <?php foreach ($ruangList as $r): ?>
            <option value="<?= e($r['ruang']) ?>"><?= e($r['ruang']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Meja ke samping (kolom)</label>
        <input type="number" name="cols" min="1" max="20" value="6" required>
      </div>

      <div class="field">
        <label>Meja ke belakang (baris)</label>
        <input type="number" name="rows" min="1" max="30" value="6" required>
      </div>

      <div class="field">
        <label>Posisi Pintu</label>
        <select name="door" required>
          <option value="left" selected>Pintu di Kiri</option>
          <option value="right">Pintu di Kanan</option>
        </select>
      </div>

      <div class="field full">
        <button type="submit">Cetak Denah</button>
      </div>

    </form>
  </section>
</main>
</body>
</html>
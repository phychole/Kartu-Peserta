<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$kelas = trim($_GET['kelas'] ?? '');
$ruang = trim($_GET['ruang'] ?? '');
$q     = trim($_GET['q'] ?? ''); // cari nama/nomor

$sql = "SELECT id, nomor_peserta, nama, kelas, ruang FROM peserta WHERE 1=1";
$params = [];

if ($kelas !== '') { $sql .= " AND kelas = ?"; $params[] = $kelas; }
if ($ruang !== '') { $sql .= " AND ruang = ?"; $params[] = $ruang; }
if ($q !== '') {
  $sql .= " AND (nomor_peserta LIKE ? OR nama LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
$sql .= " ORDER BY nomor_peserta";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// untuk dropdown kelas/ruang
$kelasList = db()->query("SELECT DISTINCT kelas FROM peserta ORDER BY kelas")->fetchAll();
$ruangList = db()->query("SELECT DISTINCT ruang FROM peserta ORDER BY ruang")->fetchAll();

$total = (int)db()->query("SELECT COUNT(*) AS c FROM peserta")->fetch()['c'];
$filtered = count($rows);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Data Peserta - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Data Peserta</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
      
      <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <div style="display:flex; gap:10px; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div>
          <h2 style="margin-bottom:6px;">Daftar Peserta</h2>
          <div class="muted small">Total: <b><?= $total ?></b> | Tampil: <b><?= $filtered ?></b></div>
        </div>
        <div>

        <a href="<?= url('/admin/export.php?kelas='.urlencode($kelas).'&ruang='.urlencode($ruang).'&q='.urlencode($q)) ?>"
   style="display:inline-block;padding:10px 12px;border-radius:12px;border:1px solid var(--border);background:#fff;font-weight:700;">
  Export XLSX
</a>
          <a href="<?= url('/admin/peserta_form.php') ?>" style="display:inline-block;padding:10px 12px;border-radius:12px;border:1px solid #111;background:#111;color:#fff;font-weight:700;">
            + Tambah Peserta
          </a>
        </div>
      </div>

      <form method="get" class="form-row" style="grid-template-columns: 70px 1fr 70px 1fr 70px 1fr;">
        <label>Kelas</label>
        <select name="kelas" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
          <option value="">Semua</option>
          <?php foreach ($kelasList as $k): ?>
            <option value="<?= e($k['kelas']) ?>" <?= $kelas===$k['kelas']?'selected':'' ?>><?= e($k['kelas']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Ruang</label>
        <select name="ruang" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
          <option value="">Semua</option>
          <?php foreach ($ruangList as $r): ?>
            <option value="<?= e($r['ruang']) ?>" <?= $ruang===$r['ruang']?'selected':'' ?>><?= e($r['ruang']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Cari</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="nama / nomor peserta">
        <button type="submit">Filter</button>
      </form>

      <div style="overflow:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">No</th>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">Nomor Peserta</th>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">Nama</th>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">Kelas</th>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">Ruang</th>
              <th style="text-align:left;border-bottom:1px solid var(--border);padding:10px 8px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="6" class="muted" style="padding:12px 8px;">Tidak ada data.</td></tr>
            <?php endif; ?>

            <?php $no=1; foreach ($rows as $row): ?>
              <tr>
                <td style="padding:8px;border-bottom:1px solid var(--border);"><?= $no++ ?></td>
                <td style="padding:8px;border-bottom:1px solid var(--border);font-weight:700;"><?= e($row['nomor_peserta']) ?></td>
                <td style="padding:8px;border-bottom:1px solid var(--border);"><?= e($row['nama']) ?></td>
                <td style="padding:8px;border-bottom:1px solid var(--border);"><?= e($row['kelas']) ?></td>
                <td style="padding:8px;border-bottom:1px solid var(--border);"><?= e($row['ruang']) ?></td>
                <td style="padding:8px;border-bottom:1px solid var(--border);white-space:nowrap;">
                  <a href="<?= url('/admin/peserta_form.php?id='.(int)$row['id']) ?>" style="padding:6px 10px;border:1px solid var(--border);border-radius:10px;">Edit</a>
                  <a href="<?= url('/admin/peserta_delete.php?id='.(int)$row['id']) ?>"
                     onclick="return confirm('Hapus peserta ini?')"
                     style="padding:6px 10px;border:1px solid #fecaca;border-radius:10px;color:#b91c1c;">
                     Hapus
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
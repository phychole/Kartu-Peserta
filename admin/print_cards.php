<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$type = $_GET['type'] ?? 'kartu';
if (!in_array($type, ['kartu', 'bangku'], true)) $type = 'kartu';

$kelas = trim($_GET['kelas'] ?? '');
$ruang = trim($_GET['ruang'] ?? '');

$sql = "SELECT nomor_peserta, nama, kelas, ruang FROM peserta WHERE 1=1";
$params = [];
if ($kelas !== '') { $sql .= " AND kelas = ?"; $params[] = $kelas; }
if ($ruang !== '') { $sql .= " AND ruang = ?"; $params[] = $ruang; }
$sql .= " ORDER BY nomor_peserta";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$title = ($type === 'kartu')
    ? "Kartu Peserta PSAJ Tahun Pelajaran " . TAHUN_PELAJARAN
    : "Nomor Peserta PSAJ Tahun Pelajaran " . TAHUN_PELAJARAN;

$subtitle_red = "Kartu peserta wajib dibawa saat memasuki ruang ujian";
$school = school_settings();
$logoW = (int)($school['logo_width_mm'] ?? 16);
if ($logoW < 10) $logoW = 10;
if ($logoW > 30) $logoW = 30;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cetak - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/print.css') ?>">
  <style>
    .logo{ width: <?= (int)$logoW ?>mm; flex: 0 0 <?= (int)$logoW ?>mm; }
    .logo img{ width: <?= (int)$logoW ?>mm; }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <div>
      <strong><?= e($title) ?></strong>
      <?php if ($kelas || $ruang): ?>
        <span class="muted">| Filter: <?= $kelas ? "Kelas ".e($kelas) : "" ?> <?= $ruang ? "Ruang ".e($ruang) : "" ?></span>
      <?php endif; ?>
    </div>
    <button onclick="window.print()">Print</button>
  </div>

  <div class="sheet">
    <?php foreach ($rows as $r): ?>
      <div class="card">
        <div class="kop">
          <div class="logo">
            <img src="<?= school_logo_url() ?>" alt="Logo">
          </div>
          <div class="kop-text">
            <div class="kop-line kop-big"><?= e($school['line1'] ?? '') ?></div>
            <div class="kop-line kop-big"><?= e($school['line2'] ?? '') ?></div>
            <div class="kop-line kop-mid"><?= e($school['line3'] ?? '') ?></div>
            <div class="kop-line kop-small kop-one-line"><?= e($school['line4'] ?? '') ?></div>
            <div class="kop-line kop-small kop-one-line"><?= e($school['line5'] ?? '') ?></div>
            <div class="kop-line kop-mid"><?= e($school['line6'] ?? '') ?></div>
          </div>
        </div>
        <div class="sep"></div>

        <div class="judul"><?= e($title) ?></div>

        <table class="fields">
          <tr><td class="lbl">Nomor Peserta</td><td class="col">:</td><td class="val"><?= e($r['nomor_peserta']) ?></td></tr>
          <tr><td class="lbl">Nama Peserta</td><td class="col">:</td><td class="val"><?= e($r['nama']) ?></td></tr>
          <tr><td class="lbl">Kelas</td><td class="col">:</td><td class="val"><?= e($r['kelas']) ?></td></tr>
          <tr><td class="lbl">Ruang</td><td class="col">:</td><td class="val"><?= e($r['ruang']) ?></td></tr>
        </table>

        <?php if ($type === 'kartu'): ?>
          <div class="note-red"><?= e($subtitle_red) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($rows) === 0): ?>
    <p class="no-data">Tidak ada data peserta untuk dicetak.</p>
  <?php endif; ?>
</body>
</html>

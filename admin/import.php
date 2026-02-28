<?php
require_once __DIR__ . '/../config/db.php';
require_login();

/* =========================
   HELPERS
========================= */
function detect_delimiter(string $line): string {
  $comma = substr_count($line, ',');
  $semi  = substr_count($line, ';');
  return ($semi > $comma) ? ';' : ',';
}
function strip_bom(string $s): string { return preg_replace('/^\xEF\xBB\xBF/', '', $s); }
function normalize_header(string $h): string {
  $h = strip_bom($h);
  $h = strtolower(trim($h));
  $h = preg_replace('/\s+/', '_', $h);
  $h = str_replace(['-', '.'], '_', $h);
  return $h;
}
function clean_field(string $s): string {
  $s = strip_bom($s);
  $s = trim($s);
  $s = str_replace("\xC2\xA0", ' ', $s);
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}
function get_setting(string $key, string $default=''): string {
  $st = db()->prepare("SELECT value FROM app_settings WHERE `key`=? LIMIT 1");
  $st->execute([$key]);
  $row = $st->fetch();
  return $row ? (string)$row['value'] : $default;
}

/**
 * CEK DIGIT SESUAI POLA:
 * 001-9
 * 002-8,003-7,004-6,005-5,006-4,007-3,008-2
 * 009-8,010-7,011-6,... (ulang 7 angka)
 */
function cek_digit_from_urut(int $urut): int {
  $urut = abs($urut);
  if ($urut === 1) return 9;
  $cycle = [8,7,6,5,4,3,2];
  $idx = ($urut - 2) % 7; // urut 2 => 0
  return $cycle[$idx];
}
function generate_nomor_peserta(string $kab, string $sekolah, int $urut): string {
  $urut3 = str_pad((string)$urut, 3, '0', STR_PAD_LEFT);
  $cek = cek_digit_from_urut($urut);
  return $kab . '-' . $sekolah . '-' . $urut3 . '-' . $cek;
}

/**
 * Parsing CSV -> return [headersDetected, map, rows]
 * rows = array of raw arrays (fgetcsv)
 */
function parse_csv_rows(string $tmpPath, string &$delimiter, bool &$hasHeader, array &$map, string &$error): array {
  $rows = [];
  $fh = fopen($tmpPath, 'r');
  if (!$fh) { $error = 'Tidak bisa membaca file.'; return []; }

  $firstLine = fgets($fh);
  if ($firstLine === false) { fclose($fh); $error = 'File kosong.'; return []; }

  $delimiter = detect_delimiter($firstLine);
  rewind($fh);

  $row1 = fgetcsv($fh, 0, $delimiter);
  if ($row1 === false) { fclose($fh); $error = 'CSV tidak valid.'; return []; }

  $headers = array_map('normalize_header', $row1);

  $hasHeader = in_array('nomor_peserta', $headers, true)
            || in_array('no_urut', $headers, true)
            || in_array('nomor_urut', $headers, true)
            || in_array('urut', $headers, true)
            || in_array('nama', $headers, true);

  $map = ['nomor_peserta'=>null,'no_urut'=>null,'nama'=>null,'kelas'=>null,'ruang'=>null];

  if ($hasHeader) {
    foreach ($headers as $i => $h) {
      if (in_array($h, ['nomor_peserta','no_peserta','nomor'])) $map['nomor_peserta'] = $i;
      if (in_array($h, ['no_urut','nomor_urut','urut','no_urut_peserta'])) $map['no_urut'] = $i;
      if (in_array($h, ['nama','nama_peserta'])) $map['nama'] = $i;
      if ($h === 'kelas') $map['kelas'] = $i;
      if ($h === 'ruang') $map['ruang'] = $i;
    }
  } else {
    // tanpa header, deteksi kolom pertama apakah nomor format lama (mengandung '-')
    $first = clean_field((string)($row1[0] ?? ''));
    if (strpos($first, '-') !== false) {
      $map = ['nomor_peserta'=>0,'no_urut'=>null,'nama'=>1,'kelas'=>2,'ruang'=>3];
      $rows[] = $row1; // row1 adalah data
    } else {
      $map = ['nomor_peserta'=>null,'no_urut'=>0,'nama'=>1,'kelas'=>2,'ruang'=>3];
      $rows[] = $row1; // row1 adalah data
    }
  }

  // baca sisa baris
  while (($r = fgetcsv($fh, 0, $delimiter)) !== false) {
    $rows[] = $r;
  }
  fclose($fh);

  return $rows;
}

/* =========================
   CONTROLLER
========================= */
$msg = '';
$error = '';
$preview = []; // array of rows for preview
$previewMode = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = $_POST['action'] ?? 'preview'; // preview | import

  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Upload CSV gagal.';
  } else {

    $tmp = $_FILES['csv']['tmp_name'];

    $delimiter=''; $hasHeader=false; $map=[];
    $rows = parse_csv_rows($tmp, $delimiter, $hasHeader, $map, $error);

    if (!$error) {

      // validasi minimal
      if ($map['nama'] === null || $map['kelas'] === null) {
        $error = 'Kolom wajib minimal: nama, kelas. (ruang disarankan / default ruang).';
      } else {

        $needGenerate = ($map['nomor_peserta'] === null); // jika tidak ada nomor_peserta, maka generate
        $defaultRuang = trim($_POST['default_ruang'] ?? '');

        $kab = trim($_POST['kode_kabupaten'] ?? '');
        $sekolah = trim($_POST['kode_sekolah'] ?? '');

        if ($needGenerate) {
          if ($kab === '') $kab = get_setting('kode_kabupaten');
          if ($sekolah === '') $sekolah = get_setting('kode_sekolah');

          if ($kab === '' || $sekolah === '') {
            $error = 'Kode kabupaten & kode sekolah belum di-set. Isi di form atau set di menu "Setting Nomor".';
          }
          if ($map['no_urut'] === null) {
            $error = 'CSV mode generate harus punya kolom no_urut/urut/nomor_urut.';
          }
        }

        if (!$error) {
          // ===== PREVIEW (selalu dibuat) =====
          $maxPrev = 10;
          $idx = 0;

          foreach ($rows as $r) {
            if ($idx >= $maxPrev) break;

            $nama  = clean_field((string)($r[$map['nama']] ?? ''));
            $kelas = clean_field((string)($r[$map['kelas']] ?? ''));
            $ruang = '';

            if ($map['ruang'] !== null) $ruang = clean_field((string)($r[$map['ruang']] ?? ''));
            if ($ruang === '') $ruang = $defaultRuang;

            $nomor = '';
            $urut = null;

            if (!$needGenerate) {
              $nomor = clean_field((string)($r[$map['nomor_peserta']] ?? ''));
            } else {
              $urutStr = clean_field((string)($r[$map['no_urut']] ?? ''));
              if ($urutStr !== '' && ctype_digit($urutStr)) {
                $urut = (int)$urutStr;
                $nomor = generate_nomor_peserta($kab, $sekolah, $urut);
              }
            }

            $status = 'OK';
            $reason = '';

            if ($nomor === '' || $nama === '' || $kelas === '') {
              $status = 'SKIP'; $reason = 'Data wajib kosong (nomor/nama/kelas).';
            } elseif ($ruang === '') {
              $status = 'SKIP'; $reason = 'Ruang kosong (isi kolom ruang atau Default Ruang).';
            } elseif ($needGenerate && ($urut === null || $urut <= 0)) {
              $status = 'SKIP'; $reason = 'no_urut tidak valid.';
            }

            $preview[] = [
              'nomor' => $nomor,
              'nama' => $nama,
              'kelas' => $kelas,
              'ruang' => $ruang,
              'status' => $status,
              'reason' => $reason,
            ];
            $idx++;
          }

          // ===== Jika action import, baru insert DB =====
          if ($action === 'import') {
            $pdo = db();
            $pdo->beginTransaction();

            $inserted=0; $updated=0; $skipped=0;

            $stmt = $pdo->prepare(
              "INSERT INTO peserta (nomor_peserta, nama, kelas, ruang)
               VALUES (?,?,?,?)
               ON DUPLICATE KEY UPDATE
                 nama=VALUES(nama),
                 kelas=VALUES(kelas),
                 ruang=VALUES(ruang)"
            );

            foreach ($rows as $r) {
              $nama  = clean_field((string)($r[$map['nama']] ?? ''));
              $kelas = clean_field((string)($r[$map['kelas']] ?? ''));
              $ruang = '';

              if ($map['ruang'] !== null) $ruang = clean_field((string)($r[$map['ruang']] ?? ''));
              if ($ruang === '') $ruang = $defaultRuang;

              $nomor = '';
              if (!$needGenerate) {
                $nomor = clean_field((string)($r[$map['nomor_peserta']] ?? ''));
              } else {
                $urutStr = clean_field((string)($r[$map['no_urut']] ?? ''));
                if ($urutStr === '' || !ctype_digit($urutStr)) { $skipped++; continue; }
                $urut = (int)$urutStr;
                if ($urut <= 0) { $skipped++; continue; }
                $nomor = generate_nomor_peserta($kab, $sekolah, $urut);
              }

              if ($nomor === '' || $nama === '' || $kelas === '' || $ruang === '') { $skipped++; continue; }

              $stmt->execute([$nomor, $nama, $kelas, $ruang]);
              if ($stmt->rowCount() === 1) $inserted++; else $updated++;
            }

            $pdo->commit();

            $msg = "Import selesai. Insert: $inserted, Update: $updated, Skip: $skipped. Delimiter: <strong>".e($delimiter)."</strong>";
            $previewMode = false;
          } else {
            // preview mode
            $previewMode = true;
            $msg = "Preview 10 data pertama (belum masuk database). Delimiter: <strong>".e($delimiter)."</strong>";
          }
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Import CSV - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
  <style>
    table{ width:100%; border-collapse:collapse; }
    th,td{ border-bottom:1px solid var(--border); padding:8px 6px; text-align:left; font-size: 14px; }
    .ok{ color:#166534; font-weight:700; }
    .skip{ color:#b91c1c; font-weight:700; }
    .small{ font-size:12px; color:#555; }
  </style>
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Import CSV</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
      
      <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2>Upload CSV</h2>

      <?php if ($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <label>CSV</label>
        <input type="file" name="csv" accept=".csv,text/csv" required>

        <label>Default Ruang (wajib jika CSV tidak punya kolom ruang)</label>
        <input type="text" name="default_ruang" placeholder="contoh: 1">

        <label>Kode Kabupaten (opsional, ambil dari setting jika dikosongkan)</label>
        <input type="text" name="kode_kabupaten" placeholder="contoh: 30">

        <label>Kode Sekolah (opsional, ambil dari setting jika dikosongkan)</label>
        <input type="text" name="kode_sekolah" placeholder="contoh: 0104">

        <input type="hidden" name="action" value="preview">
        <button type="submit">Preview</button>
      </form>

      <?php if ($previewMode && $preview): ?>
        <h3 style="margin-top:16px;">Preview (10 data pertama)</h3>
        <div class="small">Jika sudah benar, klik tombol “Proses Import” di bawah.</div>

        <div style="overflow:auto;margin-top:8px;">
          <table>
            <thead>
              <tr>
                <th>Nomor Peserta</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Ruang</th>
                <th>Status</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($preview as $p): ?>
                <tr>
                  <td><?= e($p['nomor']) ?></td>
                  <td><?= e($p['nama']) ?></td>
                  <td><?= e($p['kelas']) ?></td>
                  <td><?= e($p['ruang']) ?></td>
                  <td class="<?= $p['status']==='OK'?'ok':'skip' ?>"><?= e($p['status']) ?></td>
                  <td class="small"><?= e($p['reason']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Form kedua untuk import (upload file lagi, simpel & aman) -->
        <h3 style="margin-top:14px;">Proses Import</h3>
        <div class="small">Silakan pilih file yang sama lagi, lalu klik Proses Import.</div>

        <form method="post" enctype="multipart/form-data" style="margin-top:8px;">
          <input type="file" name="csv" accept=".csv,text/csv" required>
          <input type="text" name="default_ruang" placeholder="Default Ruang (jika tidak ada kolom ruang)">
          <input type="text" name="kode_kabupaten" placeholder="Kode Kabupaten (opsional)">
          <input type="text" name="kode_sekolah" placeholder="Kode Sekolah (opsional)">
          <input type="hidden" name="action" value="import">
          <button type="submit">Proses Import</button>
        </form>
      <?php endif; ?>

      <h3 style="margin-top:16px;">Contoh CSV (generate nomor)</h3>
      <pre>no_urut;nama;kelas;ruang
1;AHMAD FAHMI;XII RPL 1;1
2;SITI AMINAH;XII RPL 1;1
9;BUDI SETIAWAN;XII RPL 1;1
10;RINA;XII RPL 1;1</pre>

      <h3>Contoh hasil cek digit</h3>
      <pre>001-9
002-8
003-7
004-6
005-5
006-4
007-3
008-2
009-8
010-7
011-6</pre>
    </section>
  </main>
</body>
</html>
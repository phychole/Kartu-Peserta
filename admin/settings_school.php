<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$pdo = db();
$msg = '';
$error = '';

// pastikan baris profile ada
$pdo->exec("INSERT IGNORE INTO school_profile (id, line1, line2, line3, line4, line5, line6, logo_filename, logo_width_mm, kepsek_nama, kepsek_nip)
            VALUES (1,'','','','','','', 'logo-jatim.svg', 16, '', '')");

$s = school_settings();

function save_logo_upload(array $file, array $current): array {
    if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $current;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload logo gagal.');
    }

    $tmp = $file['tmp_name'];
    $orig = $file['name'] ?? 'logo';
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png','jpg','jpeg','svg'], true)) {
        throw new RuntimeException('Format logo harus PNG/JPG/SVG.');
    }

    $targetDir = __DIR__ . '/../uploads';
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            throw new RuntimeException('Folder uploads tidak bisa dibuat.');
        }
    }

    // Simpan dengan nama tetap agar mudah (logo-school.ext)
    $filename = 'logo-school.' . $ext;
    $dest = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Gagal menyimpan file logo.');
    }
    return array_merge($current, ['logo_filename' => $filename]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'line1' => trim($_POST['line1'] ?? ''),
            'line2' => trim($_POST['line2'] ?? ''),
            'line3' => trim($_POST['line3'] ?? ''),
            'line4' => trim($_POST['line4'] ?? ''),
            'line5' => trim($_POST['line5'] ?? ''),
            'line6' => trim($_POST['line6'] ?? ''),
            'logo_width_mm' => (int)($_POST['logo_width_mm'] ?? 16),
            'kepsek_nama' => trim($_POST['kepsek_nama'] ?? ''),
            'kepsek_nip' => trim($_POST['kepsek_nip'] ?? ''),
            'logo_filename' => $s['logo_filename'] ?? 'logo-jatim.svg',
        ];

        if ($data['logo_width_mm'] < 10) $data['logo_width_mm'] = 10;
        if ($data['logo_width_mm'] > 30) $data['logo_width_mm'] = 30;

        // upload logo (optional)
        $data = save_logo_upload($_FILES['logo'] ?? [], $data);

        $stmt = $pdo->prepare("UPDATE school_profile SET
            line1=?, line2=?, line3=?, line4=?, line5=?, line6=?,
            logo_filename=?, logo_width_mm=?,
            kepsek_nama=?, kepsek_nip=?
            WHERE id=1");

        $stmt->execute([
            $data['line1'], $data['line2'], $data['line3'],
            $data['line4'], $data['line5'], $data['line6'],
            $data['logo_filename'], $data['logo_width_mm'],
            $data['kepsek_nama'], $data['kepsek_nip'],
        ]);

        // refresh cache
        $msg = 'Pengaturan berhasil disimpan.';
        // reload agar school_settings() cache tidak digunakan
        header('Location: ' . url('/admin/settings_school.php?saved=1'));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (($_GET['saved'] ?? '') === '1') {
    $msg = 'Pengaturan berhasil disimpan.';
}

// ambil ulang dari DB (tanpa cache)
$s = $pdo->query("SELECT * FROM school_profile WHERE id=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: school_settings();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengaturan KOP - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Pengaturan Identitas Sekolah</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Beranda</a>
      <a class="danger" href="<?= url('/auth/logout.php') ?>">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2>Identitas KOP</h2>
      <p class="muted">Semua halaman cetak (kartu, absensi, denah) akan menggunakan data di sini.</p>

      <?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <label>Baris 1</label>
        <input type="text" name="line1" value="<?= e($s['line1'] ?? '') ?>" required>

        <label>Baris 2</label>
        <input type="text" name="line2" value="<?= e($s['line2'] ?? '') ?>" required>

        <label>Baris 3</label>
        <input type="text" name="line3" value="<?= e($s['line3'] ?? '') ?>" required>

        <label>Baris 4 (alamat)</label>
        <input type="text" name="line4" value="<?= e($s['line4'] ?? '') ?>" required>

        <label>Baris 5 (email & website)</label>
        <input type="text" name="line5" value="<?= e($s['line5'] ?? '') ?>" required>

        <label>Baris 6 (kota)</label>
        <input type="text" name="line6" value="<?= e($s['line6'] ?? '') ?>" required>

        <hr style="border:none;border-top:1px solid var(--border); margin:14px 0;">

        <h3 style="margin-top:0;">Logo</h3>
        <div class="muted small">Logo saat ini:</div>
        <div style="margin:8px 0 10px; display:flex; gap:12px; align-items:center;">
          <img src="<?= school_logo_url() ?>" alt="logo" style="width:42px;height:auto;border:1px solid var(--border);border-radius:10px;padding:6px;background:#fff;">
          <div class="muted small">Upload logo baru (PNG/JPG/SVG). Disarankan PNG transparan.</div>
        </div>

        <label>Upload Logo (opsional)</label>
        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg">

        <label>Lebar Logo di cetak (mm) (10 - 30)</label>
        <input type="text" name="logo_width_mm" value="<?= e((string)($s['logo_width_mm'] ?? 16)) ?>">

        <hr style="border:none;border-top:1px solid var(--border); margin:14px 0;">

        <h3 style="margin-top:0;">Kepala Sekolah</h3>
        <label>Nama Kepala Sekolah</label>
        <input type="text" name="kepsek_nama" value="<?= e($s['kepsek_nama'] ?? '') ?>">

        <label>NIP Kepala Sekolah</label>
        <input type="text" name="kepsek_nip" value="<?= e($s['kepsek_nip'] ?? '') ?>">

        <button type="submit">Simpan Pengaturan</button>
      </form>
    </section>
  </main>
</body>
</html>

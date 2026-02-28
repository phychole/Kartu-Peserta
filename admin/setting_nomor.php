<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$pdo = db();

function get_setting(string $key, string $default=''): string {
  $st = db()->prepare("SELECT value FROM app_settings WHERE `key`=? LIMIT 1");
  $st->execute([$key]);
  $row = $st->fetch();
  return $row ? (string)$row['value'] : $default;
}
function set_setting(string $key, string $value): void {
  $st = db()->prepare("INSERT INTO app_settings(`key`,`value`) VALUES(?,?)
    ON DUPLICATE KEY UPDATE value=VALUES(value)");
  $st->execute([$key,$value]);
}

$msg=''; $error='';

$kode_kabupaten = get_setting('kode_kabupaten');
$kode_sekolah   = get_setting('kode_sekolah');

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $kode_kabupaten = trim($_POST['kode_kabupaten'] ?? '');
  $kode_sekolah   = trim($_POST['kode_sekolah'] ?? '');

  if ($kode_kabupaten==='' || $kode_sekolah==='') {
    $error = "Kode kabupaten dan kode sekolah wajib diisi.";
  } else {
    set_setting('kode_kabupaten', $kode_kabupaten);
    set_setting('kode_sekolah', $kode_sekolah);
    $msg = "Setting nomor peserta berhasil disimpan.";
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setting Nomor Peserta - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - Setting Nomor Peserta</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
      <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2>Setting Generator Nomor Peserta</h2>

      <?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <label>Kode Kabupaten</label>
        <input type="text" name="kode_kabupaten" value="<?= e($kode_kabupaten) ?>" placeholder="contoh: 30" required>

        <label>Kode Sekolah</label>
        <input type="text" name="kode_sekolah" value="<?= e($kode_sekolah) ?>" placeholder="contoh: 0104" required>

        <button type="submit">Simpan</button>
      </form>

      <p class="muted" style="margin-top:10px;">
        Format hasil: <b>kabupaten-sekolah-urut3digit-cek</b> (contoh: 30-0104-001-9)
      </p>
    </section>
  </main>
</body>
</html>
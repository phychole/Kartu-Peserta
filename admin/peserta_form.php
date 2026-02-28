<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$pdo = db();

$data = ['nomor_peserta'=>'', 'nama'=>'', 'kelas'=>'', 'ruang'=>''];
$isEdit = false;

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id=? LIMIT 1");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if ($row) { $data = $row; $isEdit = true; }
}

$error = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nomor = trim($_POST['nomor_peserta'] ?? '');
  $nama  = trim($_POST['nama'] ?? '');
  $kelas = trim($_POST['kelas'] ?? '');
  $ruang = trim($_POST['ruang'] ?? '');

  if ($nomor==='' || $nama==='' || $kelas==='' || $ruang==='') {
    $error = 'Semua field wajib diisi.';
  } else {
    try {
      if ($isEdit) {
        $stmt = $pdo->prepare("UPDATE peserta SET nomor_peserta=?, nama=?, kelas=?, ruang=? WHERE id=?");
        $stmt->execute([$nomor,$nama,$kelas,$ruang,$id]);
        $msg = 'Data berhasil diupdate.';
      } else {
        $stmt = $pdo->prepare("INSERT INTO peserta (nomor_peserta,nama,kelas,ruang) VALUES (?,?,?,?)");
        $stmt->execute([$nomor,$nama,$kelas,$ruang]);
        $msg = 'Data berhasil ditambahkan.';
        $data = ['nomor_peserta'=>'', 'nama'=>'', 'kelas'=>'', 'ruang'=>''];
      }
    } catch (PDOException $e) {
      // nomor peserta unik
      if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $error = 'Nomor peserta sudah ada (unik).';
      } else {
        $error = 'Gagal menyimpan data.';
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
  <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Peserta - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body class="page">
  <header class="topbar">
    <div>
      <strong>PSAJ - <?= $isEdit ? 'Edit' : 'Tambah' ?> Peserta</strong>
      <span class="muted">| <?= e($_SESSION['username'] ?? '') ?></span>
    </div>
    <nav>
      
      <a href="<?= url('/admin/dashboard.php') ?>">Dashboard</a>
      <a href="<?= url('/auth/logout.php') ?>" class="danger">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="panel">
      <h2><?= $isEdit ? 'Edit' : 'Tambah' ?> Peserta</h2>

      <?php if ($msg): ?><div class="success"><?= e($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post">
        <label>Nomor Peserta (unik)</label>
        <input type="text" name="nomor_peserta" value="<?= e($data['nomor_peserta']) ?>" required>

        <label>Nama Peserta</label>
        <input type="text" name="nama" value="<?= e($data['nama']) ?>" required>

        <label>Kelas</label>
        <input type="text" name="kelas" value="<?= e($data['kelas']) ?>" required>

        <label>Ruang</label>
        <input type="text" name="ruang" value="<?= e($data['ruang']) ?>" required>

        <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah' ?></button>
      </form>
    </section>
  </main>
</body>
</html>
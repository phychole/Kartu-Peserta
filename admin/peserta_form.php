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
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) { $data = $row; $isEdit = true; }
}

/* daftar kelas */
$kelasList = $pdo->query("SELECT DISTINCT kelas FROM peserta WHERE kelas<>'' ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

/* mapping kelas -> ruang */
$map = [];
$st = $pdo->query("SELECT DISTINCT kelas, ruang FROM peserta WHERE kelas<>'' AND ruang<>'' ORDER BY kelas, ruang");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
  $k = (string)$r['kelas'];
  $rg = (string)$r['ruang'];
  if (!isset($map[$k])) $map[$k] = [];
  $map[$k][] = $rg;
}
foreach ($map as $k => $arr) {
  $arr = array_values(array_unique($arr));
  sort($arr);
  $map[$k] = $arr;
}

/* ruang global fallback */
$ruangGlobal = $pdo->query("SELECT DISTINCT ruang FROM peserta WHERE ruang<>'' ORDER BY ruang")->fetchAll(PDO::FETCH_COLUMN);

/* pastikan nilai edit masuk list (agar tetap muncul selected) */
if ($isEdit && $data['kelas'] !== '' && !in_array($data['kelas'], $kelasList, true)) {
  $kelasList[] = $data['kelas'];
  sort($kelasList);
}
if ($isEdit && $data['kelas'] !== '' && $data['ruang'] !== '') {
  if (!isset($map[$data['kelas']])) $map[$data['kelas']] = [];
  if (!in_array($data['ruang'], $map[$data['kelas']], true)) {
    $map[$data['kelas']][] = $data['ruang'];
    $map[$data['kelas']] = array_values(array_unique($map[$data['kelas']]));
    sort($map[$data['kelas']]);
  }
  if (!in_array($data['ruang'], $ruangGlobal, true)) {
    $ruangGlobal[] = $data['ruang'];
    sort($ruangGlobal);
  }
}

$error = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nomor = trim($_POST['nomor_peserta'] ?? '');
  $nama  = trim($_POST['nama'] ?? '');

  $kelas = trim($_POST['kelas'] ?? '');
  if ($kelas === '__manual__') $kelas = trim($_POST['kelas_manual'] ?? '');

  $ruang = trim($_POST['ruang'] ?? '');
  if ($ruang === '__manual__') $ruang = trim($_POST['ruang_manual'] ?? '');

  if ($nomor==='' || $nama==='' || $kelas==='' || $ruang==='') {
    $error = 'Semua field wajib diisi.';
  } else {
    try {
      if ($isEdit) {
        $stmt = $pdo->prepare("UPDATE peserta SET nomor_peserta=?, nama=?, kelas=?, ruang=? WHERE id=?");
        $stmt->execute([$nomor,$nama,$kelas,$ruang,$id]);
        $msg = 'Data berhasil diupdate.';
        $data = ['nomor_peserta'=>$nomor, 'nama'=>$nama, 'kelas'=>$kelas, 'ruang'=>$ruang];
      } else {
        $stmt = $pdo->prepare("INSERT INTO peserta (nomor_peserta,nama,kelas,ruang) VALUES (?,?,?,?)");
        $stmt->execute([$nomor,$nama,$kelas,$ruang]);
        $msg = 'Data berhasil ditambahkan.';
        $data = ['nomor_peserta'=>'', 'nama'=>'', 'kelas'=>'', 'ruang'=>''];
      }
    } catch (PDOException $e) {
      if (stripos($e->getMessage(), 'Duplicate') !== false) $error = 'Nomor peserta sudah ada (unik).';
      else $error = 'Gagal menyimpan data.';
    }
  }
}

/* untuk JS */
$mapJson = json_encode($map, JSON_UNESCAPED_UNICODE);
$ruangGlobalJson = json_encode(array_values($ruangGlobal), JSON_UNESCAPED_UNICODE);
$currentKelas = (string)($data['kelas'] ?? '');
$currentRuang = (string)($data['ruang'] ?? '');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Peserta - PSAJ</title>
  <link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
  <style>
    .hint{ font-size:12px; color:#6b7280; margin-top:6px; }
    .manual{ display:none; margin-top:8px; }
  </style>
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

  <form method="post" id="formPeserta">
    <label>Nomor Peserta (unik)</label>
    <input type="text" name="nomor_peserta" value="<?= e($data['nomor_peserta']) ?>" required>

    <label>Nama Peserta</label>
    <input type="text" name="nama" value="<?= e($data['nama']) ?>" required>

    <label>Kelas</label>
    <select name="kelas" id="kelasSelect" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
      <option value="">-- pilih kelas --</option>
      <?php foreach ($kelasList as $k): ?>
        <option value="<?= e($k) ?>" <?= ($currentKelas===$k?'selected':'') ?>><?= e($k) ?></option>
      <?php endforeach; ?>
      <option value="__manual__">(+ Ketik manual...)</option>
    </select>

    <div class="manual" id="kelasManualWrap">
      <input type="text" name="kelas_manual" id="kelasManual" placeholder="Isi kelas manual (contoh: XII RPL 1)">
      <div class="hint">Jika kelas belum ada di dropdown, isi manual di sini.</div>
    </div>

    <label>Ruang</label>
    <select name="ruang" id="ruangSelect" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:12px;">
      <option value="">-- pilih ruang --</option>
      <option value="__manual__">(+ Ketik manual...)</option>
    </select>

    <div class="manual" id="ruangManualWrap">
      <input type="text" name="ruang_manual" id="ruangManual" placeholder="Isi ruang manual (contoh: 05)">
      <div class="hint">Jika ruang belum ada di dropdown, isi manual di sini.</div>
    </div>

    <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah' ?></button>
  </form>
</section>
</main>

<script>
(function(){
  const map = <?= $mapJson ?>;
  const ruangGlobal = <?= $ruangGlobalJson ?>;

  const kelasSel = document.getElementById('kelasSelect');
  const ruangSel = document.getElementById('ruangSelect');

  const kelasWrap = document.getElementById('kelasManualWrap');
  const ruangWrap = document.getElementById('ruangManualWrap');

  const kelasManual = document.getElementById('kelasManual');
  const ruangManual = document.getElementById('ruangManual');

  const currentKelas = <?= json_encode($currentKelas, JSON_UNESCAPED_UNICODE) ?>;
  const currentRuang = <?= json_encode($currentRuang, JSON_UNESCAPED_UNICODE) ?>;

  function setOptions(selectEl, items, selectedValue){
    selectEl.innerHTML = '';

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '-- pilih ruang --';
    selectEl.appendChild(opt0);

    items.forEach(v => {
      const o = document.createElement('option');
      o.value = v;
      o.textContent = v;
      if (selectedValue && selectedValue === v) o.selected = true;
      selectEl.appendChild(o);
    });

    const oManual = document.createElement('option');
    oManual.value = '__manual__';
    oManual.textContent = '(+ Ketik manual...)';
    selectEl.appendChild(oManual);
  }

  function syncManual(){
    if (kelasSel.value === '__manual__'){
      kelasWrap.style.display = 'block';
      kelasManual.required = true;
    } else {
      kelasWrap.style.display = 'none';
      kelasManual.required = false;
      kelasManual.value = '';
    }

    if (ruangSel.value === '__manual__'){
      ruangWrap.style.display = 'block';
      ruangManual.required = true;
    } else {
      ruangWrap.style.display = 'none';
      ruangManual.required = false;
      ruangManual.value = '';
    }
  }

  function rebuildRuang(keepSelected){
    const k = kelasSel.value;

    if (!k || k === '__manual__') {
      // tidak ada kelas, pakai global
      setOptions(ruangSel, ruangGlobal, keepSelected ? currentRuang : '');
      syncManual();
      return;
    }

    const list = map[k] ? map[k] : [];
    const selected = keepSelected ? currentRuang : '';
    setOptions(ruangSel, list, selected);

    // jika keepSelected tapi currentRuang tidak ada di list, biarkan kosong
    if (keepSelected && selected && list.indexOf(selected) === -1) {
      ruangSel.value = '';
    }

    syncManual();
  }

  // INIT: edit harus tampil sesuai DB
  if (currentKelas) {
    kelasSel.value = currentKelas;
    rebuildRuang(true); // <— ini kunci: ruang ikut kelas DB, dan selected = currentRuang
  } else {
    rebuildRuang(false);
  }

  kelasSel.addEventListener('change', function(){
    // saat user ganti kelas, ruang tidak dipaksa tetap
    rebuildRuang(false);
  });

  ruangSel.addEventListener('change', syncManual);
})();
</script>
</body>
</html>
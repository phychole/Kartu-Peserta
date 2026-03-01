<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$school = school_settings();
$kepsekNama = trim((string)($school['kepsek_nama'] ?? ''));
$kepsekNip  = trim((string)($school['kepsek_nip'] ?? ''));

$kelas = trim($_GET['kelas'] ?? '');
$MAX = 33;

$kepsek_nama = $kepsekNama !== '' ? $kepsekNama : '....................................';
$kepsek_nip  = $kepsekNip;

function fetchPesertaByRuang(string $ruang, string $kelas): array {
  $sql = "SELECT nomor_peserta, nama, kelas, ruang FROM peserta WHERE ruang = ?";
  $params = [$ruang];
  if ($kelas !== '') {
    $sql .= " AND kelas = ?";
    $params[] = $kelas;
  }
  $sql .= " ORDER BY kelas, nomor_peserta";
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

$ruangs = db()->query("SELECT DISTINCT ruang FROM peserta WHERE ruang<>'' ORDER BY ruang")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Daftar Hadir - Semua Ruang</title>
<style>
*{ box-sizing:border-box; }
body{ margin:0; font-family:Arial, Helvetica, sans-serif; }

@page{ size:A4; margin:10mm; }

.toolbar{ display:flex; justify-content:space-between; padding:10px; border-bottom:1px solid #ddd; }
.toolbar button{ padding:6px 12px; background:#2563eb; color:#fff; border:0; border-radius:6px; font-weight:bold; cursor:pointer; }
@media print{ .no-print{ display:none; } }

.sheet{ page-break-after:always; }
.sheet:last-child{ page-break-after:auto; }

.page{
  width:190mm;
  height:277mm;
  margin:0 auto;
  position:relative;
  overflow:hidden;
  padding-bottom:54mm;
}

/* KOP */
.kop-wrap{ display:flex; align-items:center; gap:5mm; }
.kop-logo img{ width:15mm; }
.kop{ text-align:center; flex:1; }
.kop .l1,.kop .l2,.kop .l3{ font-weight:bold; font-size:10.5pt; line-height:1.05; }
.kop .l4{ font-size:8pt; }
.hr{ border-top:2px solid #000; margin:2mm 0 1mm; }
.hr2{ border-top:1px solid #000; margin-bottom:2mm; }

.title{ text-align:center; font-weight:bold; font-size:10.5pt; margin-bottom:2mm; }

/* INFO */
.info{ display:flex; justify-content:space-between; font-size:8.8pt; margin-bottom:2mm; }
.info .row{ display:flex; gap:5px; margin:1mm 0; }
.info .lbl{ width:30mm; }
.info .val{ border-bottom:1px dotted #000; min-width:60mm; }

/* TABLE */
table{ width:100%; border-collapse:collapse; font-size:8pt; }
th,td{ border:1px solid #000; padding:0.6mm 1mm; }
th{ font-weight:bold; }
tbody tr{ height:5mm; }

.center{text-align:center;}
.ttd-left{ text-align:left; padding-left:2mm; }
.ttd-mid{ text-align:center; }

/* REKAP */
.rekap{ font-size:8.8pt; margin-top:2mm; display:flex; gap:10mm; }

/* SIGNATURE GRID (kepsek tanpa garis di atas nama, garis pengawas sejajar) */
.sign{
  position:absolute;
  left:0; right:0;
  bottom:8mm;
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:20mm;
  row-gap:2mm;
  font-size:8.8pt;
}
.sig-top{ line-height:1.2; }
.sig-space{ height:16mm; }
.sig-line{
  width:75mm;
  border-bottom:1px solid #000;
  margin-top:2mm;
}
.sig-name{ font-weight:bold; }
</style>
</head>
<body>

<div class="toolbar no-print">
  <div><strong>Daftar Hadir - Semua Ruang<?= $kelas ? ' | Kelas '.e($kelas) : '' ?></strong></div>
  <button onclick="window.print()">Print</button>
</div>

<?php foreach($ruangs as $rg): ?>
<?php
  $ruang = (string)($rg['ruang'] ?? '');
  if ($ruang === '') continue;

  $rowsAll = fetchPesertaByRuang($ruang, $kelas);
  $jumlahSeharusnya = count($rowsAll);
  $rows = array_slice($rowsAll, 0, $MAX);
?>
<div class="sheet">
  <div class="page">

    <div class="kop-wrap">
      <div class="kop-logo"><img src="<?= school_logo_url() ?>"></div>
      <div class="kop">
        <div class="l1"><?= e($school['line1'] ?? '') ?></div>
        <div class="l2"><?= e($school['line2'] ?? '') ?></div>
        <div class="l3"><?= e($school['line3'] ?? '') ?></div>
        <div class="l4"><?= e($school['line4'] ?? '') ?> | <?= e($school['line5'] ?? '') ?></div>
        <div class="l2"><?= e($school['line6'] ?? '') ?></div>
      </div>
    </div>

    <div class="hr"></div>
    <div class="hr2"></div>

    <div class="title">DAFTAR HADIR PESERTA <?= e(strtoupper(exam_name())) ?> TAHUN PELAJARAN <?= e(school_year()) ?></div>

    <div class="info">
      <div>
        <div class="row"><div class="lbl">Hari / Tanggal</div>: <div class="val"></div></div>
        <div class="row"><div class="lbl">Mata Pelajaran</div>: <div class="val"></div></div>
      </div>
      <div>
        <div class="row"><div class="lbl">Ruang</div>: <?= e($ruang) ?></div>
        <div class="row"><div class="lbl">Waktu</div>: <div class="val"></div></div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th width="8%">No</th>
          <th width="22%">Nomor Peserta</th>
          <th>Nama Peserta</th>
          <th width="20%">Kelas</th>
          <th width="15%">TTD</th>
        </tr>
      </thead>
      <tbody>
        <?php $no=1; foreach($rows as $r): ?>
        <tr>
          <td class="center"><?= $no ?></td>
          <td><?= e($r['nomor_peserta']) ?></td>
          <td><?= e($r['nama']) ?></td>
          <td class="center"><?= e($r['kelas']) ?></td>
          <td class="<?= $no%2 ? 'ttd-left' : 'ttd-mid' ?>"><?= $no ?></td>
        </tr>
        <?php $no++; endforeach; ?>
      </tbody>
    </table>

    <div class="rekap">
      <div>Jumlah Siswa Seharusnya : <?= (int)$jumlahSeharusnya ?> Siswa</div>
      <div>Tidak Hadir : ...............</div>
      <div>Jumlah Siswa Hadir : ...............</div>
    </div>

    <div class="sign">
      <div class="sig-top">Mengetahui,<br>Kepala Sekolah</div>
      <div class="sig-top">Lumajang, .................................<br>Pengawas Ruang</div>

      <div class="sig-space"></div>
      <div class="sig-space"></div>

      <div class="sig-name"><?= e($kepsek_nama) ?></div>
      <div class="sig-line"></div>

      <div>NIP. <?= e($kepsek_nip) ?></div>
      <div>NIP.</div>
    </div>

  </div>
</div>
<?php endforeach; ?>

</body>
</html>
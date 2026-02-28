<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$school = school_settings();
$logoW = (int)($school['logo_width_mm'] ?? 16);
if ($logoW < 10) $logoW = 10;
if ($logoW > 30) $logoW = 30;

$ruang = trim($_GET['ruang'] ?? '');
$cols  = (int)($_GET['cols'] ?? 0);
$rows  = (int)($_GET['rows'] ?? 0);
$door  = strtolower(trim($_GET['door'] ?? 'left')); // left|right
if (!in_array($door, ['left','right'], true)) $door = 'left';

if ($ruang === '' || $cols <= 0 || $rows <= 0) {
  header('Location: ' . url('/admin/denah_menu.php'));
  exit;
}

/* =========================
   DATA PESERTA
========================= */
$kapasitas = $cols * $rows;

$st = db()->prepare("SELECT nomor_peserta, nama, kelas, ruang FROM peserta WHERE ruang=? ORDER BY nomor_peserta");
$st->execute([$ruang]);
$peserta = $st->fetchAll();

$totalPeserta = count($peserta);
$overflow = $totalPeserta > $kapasitas;

// mapping seatNo -> peserta
$seatMap = [];
$limit = min($kapasitas, $totalPeserta);
for ($i=1; $i <= $limit; $i++) $seatMap[$i] = $peserta[$i-1];

/* =========================
   POLA ZIGZAG
========================= */
function seat_number(int $r, int $c, int $cols): int {
  $base = $r * $cols;
  return ($r % 2 === 0) ? ($base + $c + 1) : ($base + ($cols - $c));
}

/* =========================
   GANG PROPORSIONAL (setiap 2 meja)
========================= */
$gangEvery = 2;
$colPlan = [];
$mejaIndex = 0;
while ($mejaIndex < $cols) {
  for ($i=0; $i<$gangEvery && $mejaIndex < $cols; $i++) {
    $colPlan[] = ['type'=>'seat', 'meja'=>$mejaIndex];
    $mejaIndex++;
  }
  if ($mejaIndex < $cols) $colPlan[] = ['type'=>'gang'];
}

// proporsi lebar gang
$seatUnit = 1.0;
$gangUnit = 0.35; // gang ~35% lebar meja
$totalUnits = 0.0;
foreach ($colPlan as $cp) $totalUnits += ($cp['type']==='gang') ? $gangUnit : $seatUnit;

function col_width_percent(array $cp, float $seatUnit, float $gangUnit, float $totalUnits): float {
  $u = ($cp['type']==='gang') ? $gangUnit : $seatUnit;
  return ($u / $totalUnits) * 100.0;
}

$title = "DENAH DUDUK PENILAIAN SUMATIF AKHIR JENJANG TAHUN PELAJARAN 2025 - 2026";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Denah Duduk - Ruang <?= e($ruang) ?></title>

  <style>
    *{ box-sizing:border-box; }
    body{ margin:0; font-family: Arial, Helvetica, sans-serif; color:#000; }

    /* ====== KUNCI 1 LEMBAR: F4 LANDSCAPE ====== */
    :root{
      --page-w: 330mm;
      --page-h: 210mm;
      --page-m: 8mm;
      --content-w: calc(var(--page-w) - (var(--page-m) * 2));
      --content-h: calc(var(--page-h) - (var(--page-m) * 2));
    }
    @page{ size: 330mm 210mm; margin: 8mm; } /* F4 Landscape */

    .toolbar{ display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-bottom:1px solid #ddd; }
    .toolbar button{ padding:8px 12px; border:1px solid #111; background:#111; color:#fff; border-radius:10px; cursor:pointer; }
    @media print{ .no-print{ display:none !important; } }

    /* Area yang akan di-scale */
    .print-wrap{
      width: var(--content-w);
      height: var(--content-h);
      overflow: hidden; /* penting: jangan pernah keluar halaman */
    }
    .scale{
      transform-origin: top left;
      width: var(--content-w);
    }

    /* KOP */
    .kop-wrap{ display:flex; align-items:center; gap: 6mm; }
    .kop-logo img{ width: <?= (int)$logoW ?>mm; height:auto; display:block; }
    .kop{ text-align:center; flex:1; }
    .kop .l1,.kop .l2,.kop .l3{ font-weight:800; font-size: 11pt; line-height:1.03; }
    .kop .l4{ font-size: 8.2pt; line-height:1.08; }
    .hr{ border-top: 2px solid #000; margin: 1.6mm 0 0.6mm; }
    .hr2{ border-top: 1px solid #000; margin-bottom: 1.6mm; }

    .judul{ text-align:center; font-weight:800; font-size: 12pt; margin: 0 0 0.8mm; text-transform: uppercase; }
    .sub{ text-align:center; font-weight:800; font-size: 12pt; margin: 0 0 2mm; text-transform: uppercase; }
    .warn{ font-size: 10pt; color:#b91c1c; margin: 0 0 2mm; text-align:center; }

    /* Baris depan: pintu & meja pengawas */
    .front-row{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 6mm;
      margin-bottom: 2.5mm;
    }
    .pintu{
      width: 30mm;
      border: 2px solid #000;
      padding: 3mm 3mm;
      text-align:center;
      font-weight:800;
      font-size: 11pt;
    }
    .pengawas{
      flex:1;
      display:flex;
      justify-content:center;
    }
    .meja-pengawas{
      width: 90mm;
      border: 2px solid #000;
      padding: 3mm 4mm;
      text-align:center;
      font-weight:800;
      font-size: 11pt;
    }
    .meja-pengawas small{
      display:block;
      font-weight:700;
      font-size: 9pt;
      margin-top: 0.8mm;
    }
    .spacer{ width: 30mm; }

    /* Denah */
    table.denah{
      width:100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    table.denah td{
      border:1px solid #000;
      padding: 1.4mm;
      vertical-align: middle;
      position: relative;
      text-align: center;
      /* tinggi akan di-set JS agar pas 1 halaman */
      height: 20mm;
    }
    td.gang{
      border:none !important;
      background: transparent !important;
      padding:0 !important;
    }

    .seat-no{
      position:absolute;
      top:1mm;
      right:1.5mm;
      font-size: 9pt;
      font-weight: 800;
    }
    .nama{
      font-size: 10.1pt;
      font-weight: 800;
      line-height: 1.08;
      padding: 0 1mm;
      white-space: normal;
      word-break: break-word;
    }
    .nopes{ font-size: 9.0pt; font-weight: 700; margin-top: 0.7mm; }
    .kelas{ font-size: 9.0pt; font-weight: 700; margin-top: 0.3mm; }

  </style>

  <script>
    // ===== Auto Fit 1 halaman =====
    function fitDenahToOnePage() {
      const wrap = document.querySelector('.print-wrap');
      const scaleBox = document.querySelector('.scale');
      const table = document.querySelector('table.denah');
      if (!wrap || !scaleBox || !table) return;

      // 1) set tinggi sel berdasarkan sisa tinggi area
      const usedTop = (() => {
        const kop = document.querySelector('.kop-block');
        const judul = document.querySelector('.judul-block');
        const front = document.querySelector('.front-row');
        let h = 0;
        if (kop) h += kop.getBoundingClientRect().height;
        if (judul) h += judul.getBoundingClientRect().height;
        if (front) h += front.getBoundingClientRect().height;
        // tambah buffer kecil
        return h + 6; // px
      })();

      const wrapH = wrap.getBoundingClientRect().height;
      const remain = Math.max(120, wrapH - usedTop); // minimal tetap aman
      const rowCount = <?= (int)$rows ?>;

      // tinggi per baris (px)
      const perRow = Math.floor(remain / rowCount);

      // set semua td height
      table.querySelectorAll('tbody td').forEach(td => {
        // gang juga ikut, biar baris rapi; border gang tetap none
        td.style.height = perRow + 'px';
      });

      // 2) Kalau masih overflow, scale sampai muat
      scaleBox.style.transform = 'scale(1)';
      const maxH = wrap.getBoundingClientRect().height;

      let scale = 1.0;
      let tries = 0;

      while (scaleBox.getBoundingClientRect().height > maxH && tries < 15) {
        scale -= 0.03; // turunkan 3% tiap iterasi
        if (scale < 0.70) break; // batas aman
        scaleBox.style.transform = 'scale(' + scale.toFixed(2) + ')';
        tries++;
      }
    }

    window.addEventListener('load', () => {
      fitDenahToOnePage();
    });

    window.addEventListener('beforeprint', () => {
      fitDenahToOnePage();
    });

    window.addEventListener('resize', () => {
      fitDenahToOnePage();
    });
  </script>
</head>

<body>
  <div class="toolbar no-print">
    <div><strong>Denah Duduk - Ruang <?= e($ruang) ?> (<?= (int)$cols ?>x<?= (int)$rows ?>) | Pintu: <?= $door==='left'?'Kiri':'Kanan' ?></strong></div>
    <button onclick="window.print()">Print</button>
  </div>

  <!-- wrapper yang dikunci 1 halaman -->
  <div class="print-wrap">
    <div class="scale">

      <!-- KOP -->
      <div class="kop-block">
        <div class="kop-wrap">
          <div class="kop-logo">
            <img src="<?= school_logo_url() ?>" alt="Logo">
          </div>
          <div class="kop">
            <div class="l1"><?= e($school['line1'] ?? '') ?></div>
            <div class="l2"><?= e($school['line2'] ?? '') ?></div>
            <div class="l3"><?= e($school['line3'] ?? '') ?></div>
            <div class="l4"><?= e($school['line4'] ?? '') ?> | <?= e($school['line5'] ?? '') ?></div>
            <div class="l2"><?= e($school['line6'] ?? '') ?></div>
          </div>
          <div style="width:<?= (int)$logoW ?>mm;"></div>
        </div>
        <div class="hr"></div>
        <div class="hr2"></div>

        <?php if ($overflow): ?>
          <div class="warn">* Peserta (<?= (int)$totalPeserta ?>) melebihi kapasitas denah (<?= (int)$kapasitas ?>). Yang ditampilkan hanya <?= (int)$kapasitas ?>.</div>
        <?php endif; ?>
      </div>

      <div class="judul-block">
        <div class="judul"><?= e($title) ?></div>
        <div class="sub">RUANG <?= e($ruang) ?></div>
      </div>

      <!-- DEPAN: Pintu & Meja Pengawas -->
      <div class="front-row">
        <?php if ($door === 'left'): ?>
          <div class="pintu">PINTU</div>
          <div class="pengawas">
            <div class="meja-pengawas">
              MEJA PENGAWAS
             <!-- <small>(Depan - Tengah)</small> -->
            </div>
          </div>
          <div class="spacer"></div>
        <?php else: ?>
          <div class="spacer"></div>
          <div class="pengawas">
            <div class="meja-pengawas">
              MEJA PENGAWAS
             <!-- <small>(Depan - Tengah)</small> -->
            </div>
          </div>
          <div class="pintu">PINTU</div>
        <?php endif; ?>
      </div>

      <!-- DENAH -->
      <table class="denah">
        <colgroup>
          <?php foreach ($colPlan as $cp): ?>
            <?php $w = col_width_percent($cp, $seatUnit, $gangUnit, $totalUnits); ?>
            <col style="width: <?= number_format($w, 4, '.', '') ?>%;">
          <?php endforeach; ?>
        </colgroup>

        <tbody>
          <?php for ($r=0; $r<$rows; $r++): ?>
            <tr>
              <?php foreach ($colPlan as $cp): ?>
                <?php if ($cp['type'] === 'gang'): ?>
                  <td class="gang"></td>
                <?php else: ?>
                  <?php
                    $meja = (int)$cp['meja'];
                    $seatNo = seat_number($r, $meja, $cols);
                    $p = $seatMap[$seatNo] ?? null;
                  ?>
                  <td>
                  <!--  <div class="seat-no"><?= $seatNo ?></div> -->
                    <?php if ($p): ?>
                      <div class="nama"><?= e($p['nama']) ?></div>
                      <div class="nopes"><?= e($p['nomor_peserta']) ?></div>
                      <div class="kelas"><?= e($p['kelas']) ?></div>
                    <?php else: ?>
                      <div class="nama">&nbsp;</div>
                      <div class="nopes">&nbsp;</div>
                      <div class="kelas">&nbsp;</div>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

    </div>
  </div>
</body>
</html>
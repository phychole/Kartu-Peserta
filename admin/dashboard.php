<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$pdo = db();

$stats = $pdo->query('SELECT COUNT(*) AS total FROM peserta')->fetch();
$total = (int)($stats['total'] ?? 0);

// dropdown kelas
$kelasList = $pdo->query("SELECT DISTINCT kelas FROM peserta WHERE kelas <> '' ORDER BY kelas")->fetchAll();

// dropdown ruang awal (semua)
$ruangList = $pdo->query("SELECT DISTINCT ruang FROM peserta WHERE ruang <> '' ORDER BY ruang")->fetchAll();

$ajaxRoomsUrl = url('/admin/ajax_rooms.php');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard</title>
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">

<style>
:root{
  --tc:#2563eb;
  --tc-dark:#1d4ed8;
  --ink:#111827;
  --muted:#6b7280;
  --card:#ffffff;
  --bg-soft:#f5f8ff;
  --border:rgba(17,24,39,.12);
  --shadow:0 10px 25px rgba(17,24,39,.08);
  --radius:18px;
}

body.page{ background: linear-gradient(180deg,var(--bg-soft),#fff 55%); }

/* Topbar */
.dash-topbar{
  background:#fff;
  border-bottom:1px solid var(--border);
  padding:14px 18px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.brand strong{ font-size:16px; color:var(--ink); }

.btn-logout{
  padding:10px 16px;
  border-radius:999px;
  border:1px solid rgba(37,99,235,.35);
  background:rgba(37,99,235,.10);
  color:var(--tc-dark);
  font-weight:800;
  text-decoration:none;
}
.btn-logout:hover{ background:rgba(37,99,235,.18); }

/* Layout */
.dash{
  max-width:1150px;
  margin:0 auto;
  padding:20px;
}
.hero{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:18px;
  flex-wrap:wrap;
}
.stat-card{
  background:linear-gradient(135deg,rgba(37,99,235,.14),rgba(37,99,235,.05));
  border:1px solid rgba(37,99,235,.25);
  border-radius:var(--radius);
  padding:18px;
  box-shadow:var(--shadow);
  min-width:240px;
}
.stat-num{ font-size:28px; font-weight:900; }
.stat-label{ font-size:13px; color:var(--muted); }

/* Grid */
.grid-actions{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:18px;
  margin-top:16px;
}
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:18px;
  box-shadow:0 6px 18px rgba(17,24,39,.06);
  display:flex;
  flex-direction:column;
  gap:10px;
  position:relative;
  transition:.2s;
}
.card:hover{
  transform:translateY(-3px);
  box-shadow:0 14px 28px rgba(17,24,39,.12);
}
.card-icon{
  position:absolute;
  top:16px;
  right:16px;
  width:36px;
  height:36px;
  background:rgba(196,106,74,.12);
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
}
.card-icon svg{
  width:20px;
  height:20px;
  stroke:var(--tc);
  stroke-width:2;
  fill:none;
}
.card-title{ font-size:15px; font-weight:900; margin:0; }
.card-desc{ font-size:13px; color:var(--muted); margin:0; }

.btn{
  margin-top:auto;
  padding:10px 14px;
  border-radius:12px;
  border:none;
  background:var(--tc);
  color:#fff;
  font-weight:900;
  text-decoration:none;
  text-align:center;
  cursor:pointer;
}
.btn:hover{ background:var(--tc-dark); }

/* Print card (full width) */
.print-card{ grid-column: 1 / -1; }
.print-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 8px;
}
.print-box{
  border:1px solid var(--border);
  border-radius:16px;
  padding:12px;
  background:#fff;
}
.print-box h3{
  margin:0;
  font-size:14px;
  font-weight:900;
  color:var(--ink);
}
.print-box p{
  margin:6px 0 10px;
  font-size:12.5px;
  color:var(--muted);
}
.form-inline{
  display:grid;
  grid-template-columns: 70px 1fr 70px 1fr auto;
  gap: 8px 10px;
  align-items:center;
}
.form-inline label{
  font-size:12.5px;
  font-weight:900;
  color:var(--ink);
}
.form-inline select{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid var(--border);
  background:#fff;
}
.form-inline button{
  padding:10px 14px;
  border-radius:12px;
  border:none;
  background:var(--tc);
  color:#fff;
  font-weight:900;
  cursor:pointer;
  white-space:nowrap;
}
.form-inline button:hover{ background:var(--tc-dark); }

.print-actions{
  margin-top: 12px;
  display:flex;
  gap: 10px;
  flex-wrap:wrap;
}
.print-actions a{ flex:0 0 auto; }

.small-hint{
  font-size:12px;
  color:var(--muted);
  margin-top:6px;
}

/* Responsive */
@media (max-width: 860px){
  .print-grid{ grid-template-columns: 1fr; }
  .form-inline{ grid-template-columns: 70px 1fr; }
  .form-inline button{ grid-column: 1 / -1; }
}
</style>
</head>

<body class="page">

<header class="dash-topbar">
  <div class="brand">
    <strong>Administrasi Sumatif - Admin Dashboard</strong>
  </div>
  <a class="btn-logout" href="<?= url('/auth/logout.php') ?>">Logout</a>
</header>

<main class="dash">

  <div class="hero">
    <div>
      <h2 style="margin:0;color:var(--ink);font-size:18px;font-weight:900;">Administrasi PSAJ 2026</h2>
      <p style="margin:6px 0 0;color:var(--muted);font-size:13px;">
        Aplikasi ini digunakan untuk administrasi kegiatan Sumatif
      </p>
    </div>

    <div class="stat-card">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total Peserta</div>
    </div>
  </div>

  <section class="grid-actions">

    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M7 9h10M7 13h6"></path></svg>
      </div>
      <h2 class="card-title">Setting Nomor</h2>
      <p class="card-desc">Generator nomor peserta otomatis.</p>
      <a class="btn" href="<?= url('/admin/setting_nomor.php') ?>">Buka</a>
    </div>


    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><path d="M12 3v12"></path><path d="M7 10l5 5 5-5"></path><rect x="4" y="18" width="16" height="3"></rect></svg>
      </div>
      <h2 class="card-title">Import CSV</h2>
      <p class="card-desc">Import data peserta dari CSV.</p>
      <a class="btn" href="<?= url('/admin/import.php') ?>">Buka</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"></circle><path d="M5 21c0-4 4-6 7-6s7 2 7 6"></path></svg>
      </div>
      <h2 class="card-title">Data Peserta</h2>
      <p class="card-desc">Kelola, edit, filter per kelas/ruang.</p>
      <a class="btn" href="<?= url('/admin/peserta.php') ?>">Buka</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><path d="M12 1l3 5 5 1-4 4 1 6-5-3-5 3 1-6-4-4 5-1 3-5z"></path></svg>
      </div>
      <h2 class="card-title">Pengaturan KOP</h2>
      <p class="card-desc">Ubah identitas sekolah, logo, dan kepala sekolah.</p>
      <a class="btn" href="<?= url('/admin/settings_school.php') ?>">Buka</a>
    </div>
    

    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect></svg>
      </div>
      <h2 class="card-title">Denah Duduk</h2>
      <p class="card-desc">Cetak denah duduk ruang ujian.</p>
      <a class="btn" href="<?= url('/admin/denah_menu.php') ?>">Buka</a>
    </div>

    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7H3V3h11"></path></svg>
      </div>
      <h2 class="card-title">Cetak Absensi</h2>
      <p class="card-desc">Cetak daftar hadir per ruang.</p>
      <a class="btn" href="<?= url('/admin/absensi_menu.php') ?>">Buka</a>
    </div>



    <div class="card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><path d="M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path><path d="M19.4 15a7.9 7.9 0 0 0 .1-2l2-1.5-2-3.4-2.4 1a8.3 8.3 0 0 0-1.7-1l-.3-2.6H9l-.3 2.6c-.6.3-1.2.6-1.7 1l-2.4-1-2 3.4L4.6 13a7.9 7.9 0 0 0 .1 2L2.6 16.5l2 3.4 2.4-1c.5.4 1.1.7 1.7 1L9 22h6l.3-2.6c.6-.3 1.2-.6 1.7-1l2.4 1 2-3.4-2-1.5z"></path></svg>
      </div>
      <h2 class="card-title">Ganti Password</h2>
      <p class="card-desc">Ubah password admin untuk keamanan.</p>
      <a class="btn" href="<?= url('/admin/change_password.php') ?>">Buka</a>
    </div>

    <!-- CETAK KARTU + NOMOR BANGKU (FULL WIDTH) -->
    <div class="card print-card">
      <div class="card-icon">
        <svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M7 10h10M7 14h6"></path></svg>
      </div>

      <h2 class="card-title">Cetak Kartu & Nomor Bangku</h2>
      <p class="card-desc">Pilih kelas dulu → ruang akan menyesuaikan otomatis. (Opsional: bisa “Semua”).</p>

      <div class="print-grid">

        <!-- KARTU -->
        <div class="print-box">
          <h3>Kartu Peserta</h3>
          <p>Cetak kartu peserta PSAJ</p>

          <form class="form-inline" method="get" action="<?= url('/admin/print_cards.php') ?>" target="_blank">
            <input type="hidden" name="type" value="kartu">

            <label>Kelas</label>
            <select name="kelas" id="kartu_kelas" data-target="kartu_ruang">
              <option value="">Semua</option>
              <?php foreach ($kelasList as $k): ?>
                <option value="<?= e($k['kelas']) ?>"><?= e($k['kelas']) ?></option>
              <?php endforeach; ?>
            </select>

            <label>Ruang</label>
            <select name="ruang" id="kartu_ruang">
              <option value="">Semua</option>
              <?php foreach ($ruangList as $r): ?>
                <option value="<?= e($r['ruang']) ?>"><?= e($r['ruang']) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit">Cetak</button>
          </form>

          <div class="small-hint">Tips: pilih kelas dulu agar ruang langsung terfilter.</div>
        </div>

        <!-- NOMOR BANGKU -->
        <div class="print-box">
          <h3>Nomor Bangku</h3>
          <p>Cetak nomor bangku untuk ditempel </p>

          <form class="form-inline" method="get" action="<?= url('/admin/print_cards.php') ?>" target="_blank">
            <input type="hidden" name="type" value="bangku">

            <label>Kelas</label>
            <select name="kelas" id="bangku_kelas" data-target="bangku_ruang">
              <option value="">Semua</option>
              <?php foreach ($kelasList as $k): ?>
                <option value="<?= e($k['kelas']) ?>"><?= e($k['kelas']) ?></option>
              <?php endforeach; ?>
            </select>

            <label>Ruang</label>
            <select name="ruang" id="bangku_ruang">
              <option value="">Semua</option>
              <?php foreach ($ruangList as $r): ?>
                <option value="<?= e($r['ruang']) ?>"><?= e($r['ruang']) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit">Cetak</button>
          </form>

          <div class="small-hint">Ruang akan menyesuaikan berdasarkan kelas.</div>
        </div>

      </div>

      <!-- <div class="print-actions">
        <a class="btn" href="<?= url('/admin/print_cards.php?type=kartu') ?>" target="_blank">Cetak Semua Kartu</a>
        <a class="btn" href="<?= url('/admin/print_cards.php?type=bangku') ?>" target="_blank">Cetak Semua Nomor Bangku</a>
      </div> -->
    </div>

  </section>
</main>

<script>
(function(){
  const ajaxUrl = <?= json_encode($ajaxRoomsUrl) ?>;

  async function loadRooms(kelasValue, roomSelect){
    // simpan value sebelumnya (kalau masih valid)
    const prev = roomSelect.value || '';
    roomSelect.innerHTML = '<option value="">Memuat...</option>';

    const u = new URL(ajaxUrl, window.location.origin);
    if (kelasValue) u.searchParams.set('kelas', kelasValue);

    let data = [];
    try{
      const res = await fetch(u.toString(), { headers: { 'Accept':'application/json' } });
      data = await res.json();
      if (!Array.isArray(data)) data = [];
    }catch(e){
      data = [];
    }

    // rebuild options
    const opts = ['<option value="">Semua</option>'];
    for (const r of data){
      const val = String(r);
      opts.push(`<option value="${val.replace(/"/g,'&quot;')}">${val}</option>`);
    }
    roomSelect.innerHTML = opts.join('');

    // restore previous if still exists
    if (prev && Array.from(roomSelect.options).some(o => o.value === prev)) {
      roomSelect.value = prev;
    } else {
      roomSelect.value = '';
    }
  }

  function attachCascade(kelasSelectId, ruangSelectId){
    const kelasSel = document.getElementById(kelasSelectId);
    const ruangSel = document.getElementById(ruangSelectId);
    if (!kelasSel || !ruangSel) return;

    // on change kelas => fetch rooms
    kelasSel.addEventListener('change', () => {
      loadRooms(kelasSel.value, ruangSel);
    });

    // optional: pertama kali load sesuai default (Semua -> semua ruang)
    loadRooms(kelasSel.value, ruangSel);
  }

  attachCascade('kartu_kelas', 'kartu_ruang');
  attachCascade('bangku_kelas', 'bangku_ruang');
})();
</script>

</body>
</html>
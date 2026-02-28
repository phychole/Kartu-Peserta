<?php
/**
 * Konfigurasi Identitas Sekolah (KOP) + Kepala Sekolah.
 * Disimpan di tabel `school_profile` (1 baris, id=1).
 */

function school_settings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        $pdo = db();
        $row = $pdo->query("SELECT * FROM school_profile WHERE id=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $row = null;
    }

    // Default (bisa diganti lewat menu Pengaturan KOP)
    $defaults = [
        'id' => 1,
        'line1' => 'PEMERINTAH PROVINSI JAWA TIMUR',
        'line2' => 'DINAS PENDIDIKAN',
        'line3' => 'SMK NEGERI 2 LUMAJANG',
        'line4' => 'Jalan Gajah Mada No.- Telp./Fax. (0334) 881925',
        'line5' => 'Email : smkn_02lmj@yahoo.co.id | http://www.smkn2lumajang.sch.id',
        'line6' => 'LUMAJANG',
        'logo_filename' => 'logo-jatim.svg',
        'logo_width_mm' => 16,
        'kepsek_nama' => 'Muhammad Saiful Bahri, S.Ag, M.Pd.I',
        'kepsek_nip' => '197401052003121004',
    ];

    $cache = $row ? array_merge($defaults, $row) : $defaults;
    return $cache;
}

function school_logo_url(): string {
    $s = school_settings();
    $fn = trim((string)($s['logo_filename'] ?? ''));

    // Logo upload disimpan di /uploads/
    if ($fn !== '' && file_exists(__DIR__ . '/../uploads/' . $fn)) {
        return url('/uploads/' . rawurlencode($fn)) . '?v=' . @filemtime(__DIR__ . '/../uploads/' . $fn);
    }

    // fallback ke assets
    if ($fn !== '' && file_exists(__DIR__ . '/../assets/' . $fn)) {
        return url('/assets/' . rawurlencode($fn)) . '?v=' . @filemtime(__DIR__ . '/../assets/' . $fn);
    }

    return url('/assets/logo-jatim.svg');
}

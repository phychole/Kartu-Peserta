<?php
require_once __DIR__ . '/bootstrap.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * App settings helper (tabel: app_settings).
 * Dipakai untuk: kode kabupaten/sekolah, nama ujian, tahun pelajaran, dsb.
 * NOTE: pakai function_exists agar tidak fatal error jika ada file lain yang masih mendeklarasikan fungsi sama.
 */
if (!function_exists('get_setting')) {
    function get_setting(string $key, string $default=''): string {
        try {
            $st = db()->prepare("SELECT `value` FROM app_settings WHERE `key`=? LIMIT 1");
            $st->execute([$key]);
            $row = $st->fetch();
            return $row ? (string)$row['value'] : $default;
        } catch (Throwable $e) {
            // jika tabel belum ada / belum diinstall
            return $default;
        }
    }
}

if (!function_exists('set_setting')) {
    function set_setting(string $key, string $value): void {
        $st = db()->prepare("INSERT INTO app_settings(`key`,`value`) VALUES(?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        $st->execute([$key, $value]);
    }
}

/** Nama ujian yang tampil di semua cetakan */
if (!function_exists('exam_name')) {
    function exam_name(): string {
        return get_setting('nama_ujian', 'Penilaian Sumatif Akhir Jenjang');
    }
}

/** Tahun pelajaran yang tampil di semua cetakan */
if (!function_exists('school_year')) {
    function school_year(): string {
        // fallback ke konstanta lama jika masih ada
        $fallback = defined('TAHUN_PELAJARAN') ? (string)TAHUN_PELAJARAN : '2025 - 2026';
        return get_setting('tahun_pelajaran', $fallback);
    }
}
?>